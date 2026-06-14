
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    is_verified BOOLEAN DEFAULT FALSE,
    role VARCHAR(20) DEFAULT 'user' -- Nilai: 'user', 'admin', 'owner'
);

CREATE TABLE motorcycles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    make VARCHAR(100) NOT NULL,
    model VARCHAR(100) NOT NULL,
    year INT NOT NULL,
    price FLOAT NOT NULL,
    description TEXT,
    stock INT NOT NULL DEFAULT 0
);

CREATE TABLE transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    motorcycle_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    type enum('buy','booking') NOT NULL DEFAULT 'buy', -- 'booking' atau 'buy'
    payment_status enum('unpaid','paid','refunded') DEFAULT 'unpaid', -- 'unpaid', 'paid', 'refunded'
    transaction_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    status enum('pending','confirmed','cancelled') DEFAULT 'pending',
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (motorcycle_id) REFERENCES motorcycles(id) ON DELETE CASCADE
);

CREATE TABLE logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    action_detail TEXT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ==============================================
-- DUMMY DATA / SEEDER
-- (Semua password akun di bawah ini adalah: 123456)
-- ==============================================

-- Insert 3 Dummy Users (User, Admin, Owner)
INSERT INTO users (username, email, password_hash, is_verified, role) VALUES 
('Joko (User)', 'user@gmail.com', '$2y$10$eTRsaGXo7UfTXmImDfGMSed92bRieteYTkUx91z6ndxsiOCLqiYTm', 1, 'user'),
('Budi (Admin)', 'admin@gmail.com', '$2y$10$eTRsaGXo7UfTXmImDfGMSed92bRieteYTkUx91z6ndxsiOCLqiYTm', 1, 'admin'),
('Tono (Owner)', 'owner@gmail.com', '$2y$10$eTRsaGXo7UfTXmImDfGMSed92bRieteYTkUx91z6ndxsiOCLqiYTm', 1, 'owner');

-- Insert Dummy Motorcycles
INSERT INTO motorcycles (make, model, year, price, description, stock) VALUES 
('Honda', 'CBR250RR', 2024, 65000000, 'Motor sport 250cc dengan fitur quickshifter.', 5),
('Yamaha', 'NMAX 155', 2023, 31000000, 'Skuter matik bongsor nyaman untuk harian.', 12),
('Kawasaki', 'Ninja ZX-25R', 2024, 105000000, 'Motor sport 4 silinder 250cc suara gahar.', 2),
('Suzuki', 'GSX-R150', 2022, 34000000, 'Motor sport entry level dengan keyless ignition.', 8);
