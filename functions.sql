


DELIMITER $$




DROP TRIGGER IF EXISTS `trg_after_insert_transaction`$$
CREATE TRIGGER `trg_after_insert_transaction`
AFTER INSERT ON `transactions`
FOR EACH ROW
BEGIN
    INSERT INTO `audit_logs` (`table_name`, `action_type`, `description`)
    VALUES ('transactions', 'INSERT', CONCAT('New transaction created with ID: ', NEW.id, ' by User ID: ', NEW.user_id));
END$$


DROP TRIGGER IF EXISTS `trg_before_update_motorcycle`$$
CREATE TRIGGER `trg_before_update_motorcycle`
BEFORE UPDATE ON `motorcycles`
FOR EACH ROW
BEGIN
    IF NEW.stock < 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Error: Stock cannot be less than zero.';
    END IF;
END$$


DROP TRIGGER IF EXISTS `trg_after_insert_review`$$
CREATE TRIGGER `trg_after_insert_review`
AFTER INSERT ON `reviews`
FOR EACH ROW
BEGIN
    INSERT INTO `audit_logs` (`table_name`, `action_type`, `description`)
    VALUES ('reviews', 'INSERT', CONCAT('User ', NEW.user_id, ' added a review for Motorcycle ', NEW.motorcycle_id));
END$$





DROP FUNCTION IF EXISTS `calculate_final_price`$$
CREATE FUNCTION `calculate_final_price`(p_price DECIMAL(15,2), p_discount_pct INT, p_tax_pct INT)
RETURNS DECIMAL(15,2)
DETERMINISTIC
BEGIN
    DECLARE v_discount_amount DECIMAL(15,2);
    DECLARE v_tax_amount DECIMAL(15,2);
    DECLARE v_final_price DECIMAL(15,2);
    
    SET v_discount_amount = p_price * (p_discount_pct / 100);
    SET v_tax_amount = (p_price - v_discount_amount) * (p_tax_pct / 100);
    SET v_final_price = p_price - v_discount_amount + v_tax_amount;
    
    RETURN v_final_price;
END$$


DROP FUNCTION IF EXISTS `get_total_spent`$$
CREATE FUNCTION `get_total_spent`(p_user_id INT)
RETURNS DECIMAL(15,2)
READS SQL DATA
BEGIN
    DECLARE v_total DECIMAL(15,2);
    
    SELECT COALESCE(SUM(m.price * t.quantity), 0)
    INTO v_total
    FROM transactions t
    JOIN motorcycles m ON t.motorcycle_id = m.id
    WHERE t.user_id = p_user_id;
    
    RETURN v_total;
END$$





DROP PROCEDURE IF EXISTS `sp_add_motorcycle`$$
CREATE PROCEDURE `sp_add_motorcycle`(
    IN p_make VARCHAR(100),
    IN p_model VARCHAR(100),
    IN p_year INT,
    IN p_price FLOAT,
    IN p_description TEXT,
    IN p_stock INT,
    IN p_mileage INT
)
BEGIN
    INSERT INTO `motorcycles` (`make`, `model`, `year`, `price`, `description`, `stock`, `mileage`)
    VALUES (p_make, p_model, p_year, p_price, p_description, p_stock, p_mileage);
END$$


DROP PROCEDURE IF EXISTS `sp_process_checkout`$$
CREATE PROCEDURE `sp_process_checkout`(
    IN p_user_id INT,
    IN p_motorcycle_id INT,
    IN p_quantity INT
)
BEGIN
    DECLARE v_stock INT;
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Transaction failed. Rolled back.';
    END;

    START TRANSACTION;
    
    SELECT stock INTO v_stock FROM motorcycles WHERE id = p_motorcycle_id FOR UPDATE;
    
    IF v_stock >= p_quantity THEN
        UPDATE motorcycles SET stock = stock - p_quantity WHERE id = p_motorcycle_id;
        INSERT INTO transactions (user_id, motorcycle_id, quantity) VALUES (p_user_id, p_motorcycle_id, p_quantity);
        COMMIT;
    ELSE
        ROLLBACK;
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Insufficient stock. Rolled back.';
    END IF;
END$$


DROP PROCEDURE IF EXISTS `sp_update_expired_discounts`$$
CREATE PROCEDURE `sp_update_expired_discounts`()
BEGIN
    DECLARE done INT DEFAULT FALSE;
    DECLARE v_id INT;
    DECLARE cur CURSOR FOR SELECT id FROM discounts WHERE valid_until < NOW() AND is_active = 1;
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;
    
    OPEN cur;
    read_loop: LOOP
        FETCH cur INTO v_id;
        IF done THEN
            LEAVE read_loop;
        END IF;
        
        UPDATE discounts SET is_active = 0 WHERE id = v_id;
    END LOOP;
    CLOSE cur;
END$$

DELIMITER ;
