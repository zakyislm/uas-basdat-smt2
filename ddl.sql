


SET FOREIGN_KEY_CHECKS = 0;


DROP TABLE IF EXISTS `carts`;
DROP TABLE IF EXISTS `logs`;
DROP TABLE IF EXISTS `transactions`;
DROP TABLE IF EXISTS `wishlists`;
DROP TABLE IF EXISTS `reviews`;
DROP TABLE IF EXISTS `notifications`;
DROP TABLE IF EXISTS `users`;
DROP TABLE IF EXISTS `motorcycles`;
DROP TABLE IF EXISTS `dealers`;
DROP TABLE IF EXISTS `cities`;
DROP TABLE IF EXISTS `tax_rates`;
DROP TABLE IF EXISTS `provinces`;
DROP TABLE IF EXISTS `brands`;
DROP TABLE IF EXISTS `categories`;
DROP TABLE IF EXISTS `colors`;
DROP TABLE IF EXISTS `engine_types`;
DROP TABLE IF EXISTS `payment_methods`;
DROP TABLE IF EXISTS `discounts`;
DROP TABLE IF EXISTS `shipping_methods`;
DROP TABLE IF EXISTS `audit_logs`;


CREATE TABLE `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(100) NOT NULL UNIQUE,
  `email` VARCHAR(100) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `is_verified` TINYINT(1) DEFAULT 0,
  `role` VARCHAR(20) DEFAULT 'user',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


CREATE TABLE `motorcycles` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `make` VARCHAR(100) NOT NULL,
  `model` VARCHAR(100) NOT NULL,
  `year` INT NOT NULL,
  `price` FLOAT NOT NULL,
  `description` TEXT DEFAULT NULL,
  `stock` INT NOT NULL DEFAULT 0,
  `mileage` INT DEFAULT 0,
  `discount_percent` INT NOT NULL DEFAULT 0,
  `discount_until` DATETIME NULL,
  `category_id` INT NULL,
  `color_id` INT NULL,
  `engine_type_id` INT NULL,
  FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL,
  FOREIGN KEY (`color_id`) REFERENCES `colors` (`id`) ON DELETE SET NULL,
  FOREIGN KEY (`engine_type_id`) REFERENCES `engine_types` (`id`) ON DELETE SET NULL,
  KEY `idx_motorcycles_make` (`make`),
  KEY `idx_motorcycles_price` (`price`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


CREATE TABLE `transactions` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `motorcycle_id` INT NOT NULL,
  `quantity` INT NOT NULL DEFAULT 1,
  `type` ENUM('buy','booking') NOT NULL DEFAULT 'buy',
  `payment_status` ENUM('unpaid','pending_verification','paid','refunded') DEFAULT 'unpaid',
  `transaction_date` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `status` ENUM('pending','confirmed','cancelled') DEFAULT 'pending',
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`motorcycle_id`) REFERENCES `motorcycles` (`id`) ON DELETE CASCADE,
  KEY `idx_transactions_date` (`transaction_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


CREATE TABLE `logs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `action_detail` TEXT NOT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


CREATE TABLE `notifications` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NULL,
  `target_role` VARCHAR(20) NULL,
  `message` TEXT NOT NULL,
  `link` VARCHAR(255) DEFAULT NULL,
  `icon` VARCHAR(50) DEFAULT NULL,
  `color` VARCHAR(50) DEFAULT 'text-slate-500',
  `bg` VARCHAR(50) DEFAULT 'bg-slate-100',
  `is_read` TINYINT(1) DEFAULT 0,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


CREATE TABLE `carts` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `motorcycle_id` INT NOT NULL,
  `quantity` INT NOT NULL DEFAULT 1,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`motorcycle_id`) REFERENCES `motorcycles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


CREATE TABLE `brands` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `origin_country` VARCHAR(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


CREATE TABLE `categories` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `description` TEXT DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


CREATE TABLE `colors` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `color_name` VARCHAR(50) NOT NULL,
  `hex_code` VARCHAR(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


CREATE TABLE `engine_types` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `type_name` VARCHAR(100) NOT NULL,
  `description` TEXT DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


CREATE TABLE `payment_methods` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `method_name` VARCHAR(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


CREATE TABLE `provinces` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


CREATE TABLE `cities` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `province_id` INT NOT NULL,
  `name` VARCHAR(100) NOT NULL,
  FOREIGN KEY (`province_id`) REFERENCES `provinces` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


CREATE TABLE `dealers` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `city_id` INT NOT NULL,
  `name` VARCHAR(100) NOT NULL,
  `address` TEXT DEFAULT NULL,
  FOREIGN KEY (`city_id`) REFERENCES `cities` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


CREATE TABLE `discounts` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `code` VARCHAR(50) NOT NULL,
  `percentage` INT NOT NULL,
  `valid_until` DATETIME NOT NULL,
  `usage_limit` INT NOT NULL DEFAULT 10,
  `used_count` INT NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) DEFAULT 1,
  KEY `idx_discounts_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


CREATE TABLE `wishlists` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `motorcycle_id` INT NOT NULL,
  `added_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`motorcycle_id`) REFERENCES `motorcycles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


CREATE TABLE `reviews` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `motorcycle_id` INT NOT NULL,
  `rating` INT NOT NULL CHECK (`rating` >= 1 AND `rating` <= 5),
  `comment` TEXT DEFAULT NULL,
  `is_anonymous` TINYINT(1) DEFAULT 0,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`motorcycle_id`) REFERENCES `motorcycles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


CREATE TABLE `shipping_methods` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `method_name` VARCHAR(100) NOT NULL,
  `base_cost` DECIMAL(15,2) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


CREATE TABLE `tax_rates` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `province_id` INT NOT NULL,
  `percentage` INT NOT NULL,
  FOREIGN KEY (`province_id`) REFERENCES `provinces` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


CREATE TABLE `audit_logs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `table_name` VARCHAR(50) NOT NULL,
  `action_type` VARCHAR(50) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `occurred_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

SET FOREIGN_KEY_CHECKS = 1;
