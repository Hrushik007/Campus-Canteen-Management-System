-- =====================================================
-- CAMPUS CANTEEN MANAGEMENT SYSTEM (CCMS)
-- REVIEW-3: Triggers, Procedures, and Functions
-- =====================================================

USE CCMS;

-- =====================================================
-- SECTION 1: STORED FUNCTIONS
-- =====================================================

-- Function 1: Calculate total order amount with offer discount
DELIMITER $$
CREATE FUNCTION Calculate_Order_Total(p_order_id INT)
RETURNS DECIMAL(10,2)
DETERMINISTIC
READS SQL DATA
BEGIN
    DECLARE total_amount DECIMAL(10,2);
    
    SELECT SUM(
        od.Quantity * mi.Price * 
        (1 - IFNULL(o.Discount, 0) / 100)
    ) INTO total_amount
    FROM ORDER_DETAIL od
    JOIN MENU_ITEMS mi ON od.Item_ID = mi.Item_ID
    LEFT JOIN OFFER o ON mi.Offer_ID = o.Offer_ID 
        AND o.Valid_Time > NOW() 
        AND o.Is_Active = TRUE
    WHERE od.Order_ID = p_order_id;
    
    RETURN IFNULL(total_amount, 0.00);
END$$
DELIMITER ;

-- Function 2: Check if customer has sufficient wallet balance
DELIMITER $$
CREATE FUNCTION Check_Wallet_Balance(p_customer_id INT, p_amount DECIMAL(10,2))
RETURNS BOOLEAN
DETERMINISTIC
READS SQL DATA
BEGIN
    DECLARE current_balance DECIMAL(10,2);
    
    SELECT Wallet_Bal INTO current_balance
    FROM CUSTOMER
    WHERE Customer_ID = p_customer_id;
    
    RETURN current_balance >= p_amount;
END$$
DELIMITER ;

-- Function 3: Get active offer discount for a menu item
DELIMITER $$
CREATE FUNCTION Get_Item_Discount(p_item_id INT)
RETURNS DECIMAL(5,2)
DETERMINISTIC
READS SQL DATA
BEGIN
    DECLARE discount_value DECIMAL(5,2);
    
    SELECT o.Discount INTO discount_value
    FROM MENU_ITEMS mi
    JOIN OFFER o ON mi.Offer_ID = o.Offer_ID
    WHERE mi.Item_ID = p_item_id
        AND o.Valid_Time > NOW()
        AND o.Is_Active = TRUE
        AND mi.Is_Active = TRUE;
    
    RETURN IFNULL(discount_value, 0.00);
END$$
DELIMITER ;

-- Function 4: Calculate item price with discount
DELIMITER $$
CREATE FUNCTION Get_Discounted_Price(p_item_id INT)
RETURNS DECIMAL(10,2)
DETERMINISTIC
READS SQL DATA
BEGIN
    DECLARE final_price DECIMAL(10,2);
    DECLARE base_price DECIMAL(10,2);
    DECLARE discount DECIMAL(5,2);
    
    SELECT Price INTO base_price
    FROM MENU_ITEMS
    WHERE Item_ID = p_item_id;
    
    SET discount = Get_Item_Discount(p_item_id);
    SET final_price = base_price * (1 - discount / 100);
    
    RETURN final_price;
END$$
DELIMITER ;

-- Function 5: Count pending orders for a customer
DELIMITER $$
CREATE FUNCTION Count_Pending_Orders(p_customer_id INT)
RETURNS INT
DETERMINISTIC
READS SQL DATA
BEGIN
    DECLARE order_count INT;
    
    SELECT COUNT(*) INTO order_count
    FROM `ORDER`
    WHERE Customer_ID = p_customer_id
        AND Status IN ('Pending', 'Preparing');
    
    RETURN order_count;
END$$
DELIMITER ;

-- Function 6: Get total sales for a specific date
DELIMITER $$
CREATE FUNCTION Get_Daily_Sales(p_date DATE)
RETURNS DECIMAL(10,2)
DETERMINISTIC
READS SQL DATA
BEGIN
    DECLARE total_sales DECIMAL(10,2);
    
    SELECT SUM(Calculate_Order_Total(Order_ID)) INTO total_sales
    FROM `ORDER`
    WHERE DATE(Date_Time) = p_date
        AND Status = 'Delivered';
    
    RETURN IFNULL(total_sales, 0.00);
END$$
DELIMITER ;

-- =====================================================
-- SECTION 2: STORED PROCEDURES
-- =====================================================

-- Procedure 1: Place a new order
DELIMITER $$
CREATE PROCEDURE Place_Order(
    IN p_customer_id INT,
    IN p_item_ids TEXT,
    IN p_quantities TEXT,
    IN p_payment_mode VARCHAR(50),
    OUT p_order_id INT,
    OUT p_total_amount DECIMAL(10,2),
    OUT p_status_message VARCHAR(255)
)
BEGIN
    DECLARE item_id INT;
    DECLARE quantity INT;
    DECLARE item_count INT DEFAULT 0;
    DECLARE idx INT DEFAULT 1;
    DECLARE item_id_str VARCHAR(20);
    DECLARE quantity_str VARCHAR(20);
    DECLARE payment_id INT;
    
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SET p_status_message = 'Error: Order placement failed';
        SET p_order_id = NULL;
    END;
    
    START TRANSACTION;
    
    -- Create order
    INSERT INTO `ORDER` (Date_Time, Status, Customer_ID)
    VALUES (NOW(), 'Pending', p_customer_id);
    
    SET p_order_id = LAST_INSERT_ID();
    
    -- Parse and add order items
    SET item_count = LENGTH(p_item_ids) - LENGTH(REPLACE(p_item_ids, ',', '')) + 1;
    
    WHILE idx <= item_count DO
        SET item_id_str = SUBSTRING_INDEX(SUBSTRING_INDEX(p_item_ids, ',', idx), ',', -1);
        SET quantity_str = SUBSTRING_INDEX(SUBSTRING_INDEX(p_quantities, ',', idx), ',', -1);
        
        SET item_id = CAST(item_id_str AS UNSIGNED);
        SET quantity = CAST(quantity_str AS UNSIGNED);
        
        INSERT INTO ORDER_DETAIL (Quantity, Order_ID, Item_ID)
        VALUES (quantity, p_order_id, item_id);
        
        SET idx = idx + 1;
    END WHILE;
    
    -- Calculate total
    SET p_total_amount = Calculate_Order_Total(p_order_id);
    
    -- Check wallet balance for wallet payments
    IF p_payment_mode = 'Wallet' THEN
        IF NOT Check_Wallet_Balance(p_customer_id, p_total_amount) THEN
            ROLLBACK;
            SET p_status_message = 'Insufficient wallet balance';
            SET p_order_id = NULL;
            SET p_total_amount = NULL;
        ELSE
            -- Deduct from wallet
            UPDATE CUSTOMER
            SET Wallet_Bal = Wallet_Bal - p_total_amount
            WHERE Customer_ID = p_customer_id;
            
            -- Create payment record
            INSERT INTO PAYMENT (Order_ID) VALUES (p_order_id);
            SET payment_id = LAST_INSERT_ID();
            
            INSERT INTO Mode_payment (Mode, Payment_ID)
            VALUES (p_payment_mode, payment_id);
            
            COMMIT;
            SET p_status_message = 'Order placed successfully';
        END IF;
    ELSE
        -- For other payment modes (UPI, Card)
        INSERT INTO PAYMENT (Order_ID) VALUES (p_order_id);
        SET payment_id = LAST_INSERT_ID();
        
        INSERT INTO Mode_payment (Mode, Payment_ID)
        VALUES (p_payment_mode, payment_id);
        
        COMMIT;
        SET p_status_message = 'Order placed successfully';
    END IF;
END$$
DELIMITER ;

-- Procedure 2: Top up customer wallet
DELIMITER $$
CREATE PROCEDURE TopUp_Wallet(
    IN p_customer_id INT,
    IN p_amount DECIMAL(10,2),
    OUT p_new_balance DECIMAL(10,2),
    OUT p_status_message VARCHAR(255)
)
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        SET p_status_message = 'Error: Wallet top-up failed';
        SET p_new_balance = NULL;
    END;
    
    IF p_amount <= 0 THEN
        SET p_status_message = 'Invalid amount';
        SET p_new_balance = NULL;
    ELSE
        UPDATE CUSTOMER
        SET Wallet_Bal = Wallet_Bal + p_amount
        WHERE Customer_ID = p_customer_id;
        
        SELECT Wallet_Bal INTO p_new_balance
        FROM CUSTOMER
        WHERE Customer_ID = p_customer_id;
        
        SET p_status_message = 'Wallet topped up successfully';
    END IF;
END$$
DELIMITER ;

-- Procedure 3: Update order status
DELIMITER $$
CREATE PROCEDURE Update_Order_Status(
    IN p_order_id INT,
    IN p_new_status VARCHAR(50),
    OUT p_status_message VARCHAR(255)
)
BEGIN
    DECLARE current_status VARCHAR(50);
    
    SELECT Status INTO current_status
    FROM `ORDER`
    WHERE Order_ID = p_order_id;
    
    IF current_status IS NULL THEN
        SET p_status_message = 'Order not found';
    ELSE
        UPDATE `ORDER`
        SET Status = p_new_status
        WHERE Order_ID = p_order_id;
        
        SET p_status_message = CONCAT('Order status updated to ', p_new_status);
    END IF;
END$$
DELIMITER ;

-- Procedure 4: Add or update menu item
DELIMITER $$
CREATE PROCEDURE Manage_Menu_Item(
    IN p_item_id INT,
    IN p_name VARCHAR(100),
    IN p_price DECIMAL(10,2),
    IN p_category VARCHAR(50),
    IN p_offer_id INT,
    OUT p_status_message VARCHAR(255)
)
BEGIN
    DECLARE item_exists INT DEFAULT 0;
    
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SET p_status_message = 'Error: Menu item operation failed';
    END;
    
    START TRANSACTION;
    
    SELECT COUNT(*) INTO item_exists
    FROM MENU_ITEMS
    WHERE Item_ID = p_item_id;
    
    IF item_exists > 0 THEN
        -- Update existing item
        UPDATE MENU_ITEMS
        SET Name = p_name,
            Price = p_price,
            Offer_ID = p_offer_id
        WHERE Item_ID = p_item_id;
        
        -- Update category
        DELETE FROM Category_Menu WHERE Item_ID = p_item_id;
        INSERT INTO Category_Menu (Category, Item_ID)
        VALUES (p_category, p_item_id);
        
        SET p_status_message = 'Menu item updated successfully';
    ELSE
        -- Insert new item
        INSERT INTO MENU_ITEMS (Name, Price, Offer_ID)
        VALUES (p_name, p_price, p_offer_id);
        
        SET p_item_id = LAST_INSERT_ID();
        
        INSERT INTO Category_Menu (Category, Item_ID)
        VALUES (p_category, p_item_id);
        
        SET p_status_message = CONCAT('Menu item added with ID: ', p_item_id);
    END IF;
    
    COMMIT;
END$$
DELIMITER ;

-- Procedure 5: Generate sales report
DELIMITER $$
CREATE PROCEDURE Generate_Sales_Report(
    IN p_start_date DATE,
    IN p_end_date DATE,
    IN p_admin_id INT,
    OUT p_report_id INT
)
BEGIN
    INSERT INTO REPORT (Date, Admin_ID)
    VALUES (CURDATE(), p_admin_id);
    
    SET p_report_id = LAST_INSERT_ID();
    
    INSERT INTO TYPE_REPORT (Type, Report_ID)
    VALUES ('Sales', p_report_id);
    
    -- Return report summary
    SELECT 
        COUNT(DISTINCT o.Order_ID) AS Total_Orders,
        SUM(Calculate_Order_Total(o.Order_ID)) AS Total_Revenue,
        AVG(Calculate_Order_Total(o.Order_ID)) AS Avg_Order_Value,
        COUNT(DISTINCT o.Customer_ID) AS Unique_Customers
    FROM `ORDER` o
    WHERE DATE(o.Date_Time) BETWEEN p_start_date AND p_end_date
        AND o.Status = 'Delivered';
END$$
DELIMITER ;

-- Procedure 6: Get popular menu items
DELIMITER $$
CREATE PROCEDURE Get_Popular_Items(
    IN p_start_date DATE,
    IN p_end_date DATE,
    IN p_limit INT
)
BEGIN
    SELECT 
        mi.Item_ID,
        mi.Name,
        cm.Category,
        SUM(od.Quantity) AS Total_Quantity_Sold,
        COUNT(DISTINCT od.Order_ID) AS Times_Ordered,
        SUM(od.Quantity * mi.Price) AS Total_Revenue
    FROM MENU_ITEMS mi
    JOIN ORDER_DETAIL od ON mi.Item_ID = od.Item_ID
    JOIN `ORDER` o ON od.Order_ID = o.Order_ID
    JOIN Category_Menu cm ON mi.Item_ID = cm.Item_ID
    WHERE DATE(o.Date_Time) BETWEEN p_start_date AND p_end_date
        AND o.Status = 'Delivered'
    GROUP BY mi.Item_ID, mi.Name, cm.Category
    ORDER BY Total_Quantity_Sold DESC
    LIMIT p_limit;
END$$
DELIMITER ;

-- Procedure 7: Cancel order (with refund)
DELIMITER $$
CREATE PROCEDURE Cancel_Order(
    IN p_order_id INT,
    OUT p_status_message VARCHAR(255)
)
BEGIN
    DECLARE order_status VARCHAR(50);
    DECLARE customer_id INT;
    DECLARE order_amount DECIMAL(10,2);
    DECLARE payment_mode VARCHAR(50);
    
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SET p_status_message = 'Error: Order cancellation failed';
    END;
    
    START TRANSACTION;
    
    -- Get order details
    SELECT Status, Customer_ID INTO order_status, customer_id
    FROM `ORDER`
    WHERE Order_ID = p_order_id;
    
    IF order_status IS NULL THEN
        SET p_status_message = 'Order not found';
        ROLLBACK;
    ELSEIF order_status IN ('Delivered', 'Cancelled') THEN
        SET p_status_message = CONCAT('Cannot cancel order with status: ', order_status);
        ROLLBACK;
    ELSE
        -- Calculate order amount
        SET order_amount = Calculate_Order_Total(p_order_id);
        
        -- Get payment mode
        SELECT mp.Mode INTO payment_mode
        FROM PAYMENT p
        JOIN Mode_payment mp ON p.Payment_ID = mp.Payment_ID
        WHERE p.Order_ID = p_order_id
        LIMIT 1;
        
        -- Refund to wallet if payment was made via wallet
        IF payment_mode = 'Wallet' THEN
            UPDATE CUSTOMER
            SET Wallet_Bal = Wallet_Bal + order_amount
            WHERE Customer_ID = customer_id;
        END IF;
        
        -- Update order status
        UPDATE `ORDER`
        SET Status = 'Cancelled'
        WHERE Order_ID = p_order_id;
        
        COMMIT;
        SET p_status_message = 'Order cancelled successfully';
    END IF;
END$$
DELIMITER ;

-- Procedure 8: Get customer order history
DELIMITER $$
CREATE PROCEDURE Get_Customer_Orders(
    IN p_customer_id INT,
    IN p_limit INT
)
BEGIN
    SELECT 
        o.Order_ID,
        o.Date_Time,
        o.Status,
        Calculate_Order_Total(o.Order_ID) AS Total_Amount,
        COUNT(od.Item_ID) AS Item_Count
    FROM `ORDER` o
    LEFT JOIN ORDER_DETAIL od ON o.Order_ID = od.Order_ID
    WHERE o.Customer_ID = p_customer_id
    GROUP BY o.Order_ID, o.Date_Time, o.Status
    ORDER BY o.Date_Time DESC
    LIMIT p_limit;
END$$
DELIMITER ;

-- =====================================================
-- SECTION 3: TRIGGERS
-- =====================================================

-- Trigger 1: Validate menu item price before insert
DELIMITER $$
CREATE TRIGGER trg_validate_price_before_insert
BEFORE INSERT ON MENU_ITEMS
FOR EACH ROW
BEGIN
    IF NEW.Price <= 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Price must be greater than zero';
    END IF;
END$$
DELIMITER ;

-- Trigger 2: Validate menu item price before update
DELIMITER $$
CREATE TRIGGER trg_validate_price_before_update
BEFORE UPDATE ON MENU_ITEMS
FOR EACH ROW
BEGIN
    IF NEW.Price <= 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Price must be greater than zero';
    END IF;
END$$
DELIMITER ;

-- Trigger 3: Validate order quantity
DELIMITER $$
CREATE TRIGGER trg_validate_quantity_before_insert
BEFORE INSERT ON ORDER_DETAIL
FOR EACH ROW
BEGIN
    IF NEW.Quantity <= 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Quantity must be greater than zero';
    END IF;
END$$
DELIMITER ;

-- Trigger 4: Validate offer discount
DELIMITER $$
CREATE TRIGGER trg_validate_discount_before_insert
BEFORE INSERT ON OFFER
FOR EACH ROW
BEGIN
    IF NEW.Discount < 0 OR NEW.Discount > 100 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Discount must be between 0 and 100';
    END IF;
END$$
DELIMITER ;

-- Trigger 5: Validate wallet balance before update
DELIMITER $$
CREATE TRIGGER trg_validate_wallet_before_update
BEFORE UPDATE ON CUSTOMER
FOR EACH ROW
BEGIN
    IF NEW.Wallet_Bal < 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Wallet balance cannot be negative';
    END IF;
END$$
DELIMITER ;

-- Trigger 6: Auto-set order date-time if not provided
DELIMITER $$
CREATE TRIGGER trg_set_order_datetime
BEFORE INSERT ON `ORDER`
FOR EACH ROW
BEGIN
    IF NEW.Date_Time IS NULL THEN
        SET NEW.Date_Time = NOW();
    END IF;
    
    IF NEW.Status IS NULL THEN
        SET NEW.Status = 'Pending';
    END IF;
END$$
DELIMITER ;

-- Trigger 7: Prevent deletion of items with active orders
DELIMITER $$
CREATE TRIGGER trg_prevent_item_delete_with_orders
BEFORE DELETE ON MENU_ITEMS
FOR EACH ROW
BEGIN
    DECLARE active_orders INT;
    
    SELECT COUNT(*) INTO active_orders
    FROM ORDER_DETAIL od
    JOIN `ORDER` o ON od.Order_ID = o.Order_ID
    WHERE od.Item_ID = OLD.Item_ID
        AND o.Status IN ('Pending', 'Preparing');
    
    IF active_orders > 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Cannot delete item with active orders';
    END IF;
END$$
DELIMITER ;

-- Trigger 8: Log order status changes (audit trail)
-- First create audit table
CREATE TABLE IF NOT EXISTS ORDER_STATUS_AUDIT (
    Audit_ID INT PRIMARY KEY AUTO_INCREMENT,
    Order_ID INT,
    Old_Status VARCHAR(50),
    New_Status VARCHAR(50),
    Changed_At DATETIME DEFAULT CURRENT_TIMESTAMP
);

DELIMITER $$
CREATE TRIGGER trg_audit_order_status
AFTER UPDATE ON `ORDER`
FOR EACH ROW
BEGIN
    IF OLD.Status != NEW.Status THEN
        INSERT INTO ORDER_STATUS_AUDIT (Order_ID, Old_Status, New_Status)
        VALUES (NEW.Order_ID, OLD.Status, NEW.Status);
    END IF;
END$$
DELIMITER ;

-- Trigger 9: Validate staff salary
DELIMITER $$
CREATE TRIGGER trg_validate_staff_salary
BEFORE INSERT ON STAFF
FOR EACH ROW
BEGIN
    IF NEW.Salary < 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Salary cannot be negative';
    END IF;
END$$
DELIMITER ;

-- Trigger 10: Validate admin salary
DELIMITER $$
CREATE TRIGGER trg_validate_admin_salary
BEFORE INSERT ON ADMIN
FOR EACH ROW
BEGIN
    IF NEW.Salary < 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Salary cannot be negative';
    END IF;
END$$
DELIMITER ;

-- =====================================================
-- SECTION 4: ADDITIONAL USEFUL PROCEDURES
-- =====================================================

-- Procedure: Get menu with active offers
DELIMITER $$
CREATE PROCEDURE Get_Menu_With_Offers()
BEGIN
    SELECT 
        mi.Item_ID,
        mi.Name,
        cm.Category,
        mi.Price AS Original_Price,
        IFNULL(o.Discount, 0) AS Discount_Percent,
        Get_Discounted_Price(mi.Item_ID) AS Final_Price,
        o.Description AS Offer_Description,
        o.Valid_Time AS Offer_Valid_Until
    FROM MENU_ITEMS mi
    JOIN Category_Menu cm ON mi.Item_ID = cm.Item_ID
    LEFT JOIN OFFER o ON mi.Offer_ID = o.Offer_ID 
        AND o.Valid_Time > NOW() 
        AND o.Is_Active = TRUE
    WHERE mi.Is_Active = TRUE
    ORDER BY cm.Category, mi.Name;
END$$
DELIMITER ;

-- Procedure: Get peak hours analysis
DELIMITER $$
CREATE PROCEDURE Get_Peak_Hours_Analysis(
    IN p_start_date DATE,
    IN p_end_date DATE
)
BEGIN
    SELECT 
        HOUR(Date_Time) AS Hour_Of_Day,
        COUNT(*) AS Order_Count,
        SUM(Calculate_Order_Total(Order_ID)) AS Total_Revenue,
        AVG(Calculate_Order_Total(Order_ID)) AS Avg_Order_Value
    FROM `ORDER`
    WHERE DATE(Date_Time) BETWEEN p_start_date AND p_end_date
        AND Status = 'Delivered'
    GROUP BY HOUR(Date_Time)
    ORDER BY Order_Count DESC;
END$$
DELIMITER ;

-- Procedure: Get order details
DELIMITER $$
CREATE PROCEDURE Get_Order_Details(IN p_order_id INT)
BEGIN
    SELECT 
        o.Order_ID,
        o.Date_Time,
        o.Status,
        c.Customer_ID,
        c.Name AS Customer_Name,
        mi.Name AS Item_Name,
        od.Quantity,
        mi.Price AS Unit_Price,
        Get_Item_Discount(mi.Item_ID) AS Discount_Percent,
        od.Quantity * Get_Discounted_Price(mi.Item_ID) AS Item_Total
    FROM `ORDER` o
    JOIN CUSTOMER c ON o.Customer_ID = c.Customer_ID
    JOIN ORDER_DETAIL od ON o.Order_ID = od.Order_ID
    JOIN MENU_ITEMS mi ON od.Item_ID = mi.Item_ID
    WHERE o.Order_ID = p_order_id;
    
    SELECT Calculate_Order_Total(p_order_id) AS Order_Total;
END$$
DELIMITER ;

-- =====================================================
-- END OF TRIGGERS, PROCEDURES, AND FUNCTIONS
-- =====================================================

-- Verification queries
SELECT 'Functions created:' AS Info;
SHOW FUNCTION STATUS WHERE Db = 'CCMS';

SELECT 'Procedures created:' AS Info;
SHOW PROCEDURE STATUS WHERE Db = 'CCMS';

SELECT 'Triggers created:' AS Info;
SHOW TRIGGERS FROM CCMS;
