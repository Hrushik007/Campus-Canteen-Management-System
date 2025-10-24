-- =====================================================
-- CAMPUS CANTEEN MANAGEMENT SYSTEM (CCMS)
-- COMPLETE DATABASE SETUP WITH SAMPLE DATA
-- =====================================================

-- Drop database if exists (fresh start)
DROP DATABASE IF EXISTS CCMS;
CREATE DATABASE CCMS;
USE CCMS;

-- =====================================================
-- SECTION 1: CREATE ALL TABLES
-- =====================================================

CREATE TABLE ADMIN (
    Admin_ID INT PRIMARY KEY AUTO_INCREMENT,
    Name VARCHAR(100) NOT NULL,
    Password VARCHAR(255) NOT NULL,
    Email VARCHAR(100) UNIQUE NOT NULL,
    Salary DECIMAL(10, 2),
    Is_Active BOOLEAN DEFAULT TRUE
);

CREATE TABLE CUSTOMER (
    Customer_ID INT PRIMARY KEY AUTO_INCREMENT,
    Name VARCHAR(100) NOT NULL,
    DOB DATE,
    Wallet_Bal DECIMAL(10, 2) DEFAULT 0.00,
    Is_Active BOOLEAN DEFAULT TRUE
);

CREATE TABLE Customer_Phone_NO (
    Phone_No VARCHAR(15),
    Customer_ID INT,
    PRIMARY KEY (Phone_No, Customer_ID),
    FOREIGN KEY (Customer_ID) REFERENCES CUSTOMER(Customer_ID) ON DELETE CASCADE
);

CREATE TABLE OFFER (
    Offer_ID INT PRIMARY KEY AUTO_INCREMENT,
    Valid_Time DATETIME NOT NULL,
    Discount DECIMAL(5, 2),
    Description TEXT,
    Is_Active BOOLEAN DEFAULT TRUE
);

CREATE TABLE MENU_ITEMS (
    Item_ID INT PRIMARY KEY AUTO_INCREMENT,
    Price DECIMAL(10, 2) NOT NULL,
    Name VARCHAR(100) NOT NULL,
    Offer_ID INT,
    Is_Active BOOLEAN DEFAULT TRUE
);

CREATE TABLE Category_Menu (
    Category VARCHAR(50),
    Item_ID INT,
    PRIMARY KEY (Category, Item_ID),
    FOREIGN KEY (Item_ID) REFERENCES MENU_ITEMS(Item_ID) ON DELETE CASCADE
);

CREATE TABLE `ORDER` (
    Order_ID INT PRIMARY KEY AUTO_INCREMENT,
    Date_Time DATETIME NOT NULL,
    Status VARCHAR(50),
    Customer_ID INT,
    FOREIGN KEY (Customer_ID) REFERENCES CUSTOMER(Customer_ID) ON DELETE SET NULL
);

CREATE TABLE ORDER_DETAIL (
    Quantity INT NOT NULL,
    Order_ID INT,
    Item_ID INT,
    PRIMARY KEY (Order_ID, Item_ID),
    FOREIGN KEY (Order_ID) REFERENCES `ORDER`(Order_ID) ON DELETE CASCADE,
    FOREIGN KEY (Item_ID) REFERENCES MENU_ITEMS(Item_ID) ON DELETE CASCADE
);

CREATE TABLE PAYMENT (
    Payment_ID INT PRIMARY KEY AUTO_INCREMENT,
    Order_ID INT UNIQUE,
    FOREIGN KEY (Order_ID) REFERENCES `ORDER`(Order_ID) ON DELETE CASCADE
);

CREATE TABLE Mode_payment (
    Mode VARCHAR(50),
    Payment_ID INT,
    PRIMARY KEY (Mode, Payment_ID),
    FOREIGN KEY (Payment_ID) REFERENCES PAYMENT(Payment_ID) ON DELETE CASCADE
);

CREATE TABLE STAFF (
    Staff_ID INT PRIMARY KEY AUTO_INCREMENT,
    Name VARCHAR(100) NOT NULL,
    Role VARCHAR(50),
    Salary DECIMAL(10, 2),
    Is_Active BOOLEAN DEFAULT TRUE
);

CREATE TABLE Shift_Staff (
    Shift VARCHAR(50),
    Staff_ID INT,
    PRIMARY KEY (Shift, Staff_ID),
    FOREIGN KEY (Staff_ID) REFERENCES STAFF(Staff_ID) ON DELETE CASCADE
);

CREATE TABLE REPORT (
    Report_ID INT PRIMARY KEY AUTO_INCREMENT,
    Date DATE NOT NULL,
    Admin_ID INT,
    FOREIGN KEY (Admin_ID) REFERENCES ADMIN(Admin_ID) ON DELETE SET NULL
);

CREATE TABLE TYPE_REPORT (
    Type VARCHAR(50),
    Report_ID INT,
    PRIMARY KEY (Type, Report_ID),
    FOREIGN KEY (Report_ID) REFERENCES REPORT(Report_ID) ON DELETE CASCADE
);

CREATE TABLE MANAGES_BY_ADMIN (
    Admin_ID INT,
    Item_ID INT,
    Offer_ID INT,
    Staff_ID INT,
    PRIMARY KEY (Admin_ID, Item_ID, Offer_ID, Staff_ID),
    FOREIGN KEY (Admin_ID) REFERENCES ADMIN(Admin_ID) ON DELETE CASCADE,
    FOREIGN KEY (Item_ID) REFERENCES MENU_ITEMS(Item_ID) ON DELETE CASCADE,
    FOREIGN KEY (Offer_ID) REFERENCES OFFER(Offer_ID) ON DELETE CASCADE,
    FOREIGN KEY (Staff_ID) REFERENCES STAFF(Staff_ID) ON DELETE CASCADE
);

ALTER TABLE MENU_ITEMS 
ADD CONSTRAINT fk_menu_offer 
FOREIGN KEY (Offer_ID) REFERENCES OFFER(Offer_ID) ON DELETE SET NULL;

-- Verify all tables
SHOW TABLES;

-- =====================================================
-- SECTION 2: INSERT SAMPLE DATA
-- =====================================================

-- Insert Admins
INSERT INTO ADMIN (Name, Password, Email, Salary, Is_Active) VALUES
('Rajesh Kumar', 'admin123', 'rajesh.kumar@campus.edu', 75000.00, TRUE),
('Priya Sharma', 'admin456', 'priya.sharma@campus.edu', 72000.00, TRUE),
('Amit Patel', 'admin789', 'amit.patel@campus.edu', 70000.00, TRUE);

-- Insert Customers (Students and Faculty)
INSERT INTO CUSTOMER (Name, DOB, Wallet_Bal, Is_Active) VALUES
('Arjun Reddy', '2003-05-15', 500.00, TRUE),
('Sneha Iyer', '2002-08-22', 750.00, TRUE),
('Karthik Menon', '2003-11-10', 1000.00, TRUE),
('Divya Singh', '2002-03-18', 450.00, TRUE),
('Rohan Verma', '2003-07-25', 800.00, TRUE),
('Anjali Nair', '2002-12-05', 600.00, TRUE),
('Vikram Rao', '2003-02-14', 900.00, TRUE),
('Meera Kulkarni', '2002-09-30', 550.00, TRUE),
('Dr. Suresh Babu', '1980-04-12', 2000.00, TRUE),
('Prof. Lakshmi Devi', '1975-06-20', 1500.00, TRUE);

-- Insert Customer Phone Numbers
INSERT INTO Customer_Phone_NO (Phone_No, Customer_ID) VALUES
('9876543210', 1),
('9876543211', 2),
('9876543212', 3),
('9876543213', 4),
('9876543214', 5),
('9876543215', 6),
('9876543216', 7),
('9876543217', 8),
('9988776655', 9),
('9988776656', 10),
('9876543218', 1),  -- Second phone for customer 1
('9876543219', 5);  -- Second phone for customer 5

-- Insert Offers
INSERT INTO OFFER (Valid_Time, Discount, Description, Is_Active) VALUES
('2025-12-31 23:59:59', 15.00, 'Morning Breakfast Special - 15% off', TRUE),
('2025-12-31 23:59:59', 20.00, 'Student Combo Offer - 20% off', TRUE),
('2025-11-30 23:59:59', 10.00, 'Weekend Special - 10% off', TRUE),
('2025-10-15 23:59:59', 25.00, 'Festival Mega Sale - 25% off', TRUE),
('2025-12-31 23:59:59', 5.00, 'Beverage Discount - 5% off', TRUE);

-- Insert Menu Items (without offers first)
INSERT INTO MENU_ITEMS (Name, Price, Offer_ID, Is_Active) VALUES
-- Breakfast Items
('Idli Sambar', 30.00, 1, TRUE),
('Dosa', 40.00, 1, TRUE),
('Vada', 25.00, 1, TRUE),
('Poha', 35.00, 1, TRUE),
('Upma', 30.00, NULL, TRUE),

-- Main Course
('Chicken Biryani', 120.00, 2, TRUE),
('Veg Biryani', 90.00, 2, TRUE),
('Paneer Butter Masala', 110.00, NULL, TRUE),
('Dal Tadka', 60.00, NULL, TRUE),
('Chapati (2 pcs)', 20.00, NULL, TRUE),
('Rice Bowl', 40.00, NULL, TRUE),

-- Snacks
('Samosa', 15.00, 3, TRUE),
('Spring Roll', 25.00, 3, TRUE),
('Pakora', 30.00, NULL, TRUE),
('French Fries', 40.00, NULL, TRUE),
('Sandwich', 50.00, 2, TRUE),

-- Beverages
('Tea', 10.00, 5, TRUE),
('Coffee', 15.00, 5, TRUE),
('Cold Coffee', 40.00, 5, TRUE),
('Fresh Juice', 35.00, NULL, TRUE),
('Soft Drink', 20.00, NULL, TRUE),

-- Desserts
('Ice Cream', 30.00, NULL, TRUE),
('Gulab Jamun', 25.00, NULL, TRUE),
('Rasmalai', 35.00, NULL, TRUE);

-- Insert Categories for Menu Items
INSERT INTO Category_Menu (Category, Item_ID) VALUES
('Breakfast', 1), ('Breakfast', 2), ('Breakfast', 3), ('Breakfast', 4), ('Breakfast', 5),
('Main Course', 6), ('Main Course', 7), ('Main Course', 8), ('Main Course', 9), 
('Main Course', 10), ('Main Course', 11),
('Snacks', 12), ('Snacks', 13), ('Snacks', 14), ('Snacks', 15), ('Snacks', 16),
('Beverages', 17), ('Beverages', 18), ('Beverages', 19), ('Beverages', 20), ('Beverages', 21),
('Desserts', 22), ('Desserts', 23), ('Desserts', 24);

-- Insert Staff
INSERT INTO STAFF (Name, Role, Salary, Is_Active) VALUES
('Ramesh Chef', 'Head Chef', 35000.00, TRUE),
('Ganesh Kumar', 'Chef', 28000.00, TRUE),
('Lakshman Rao', 'Assistant Chef', 22000.00, TRUE),
('Radha Devi', 'Counter Staff', 18000.00, TRUE),
('Mohan Lal', 'Counter Staff', 18000.00, TRUE),
('Sunita Sharma', 'Helper', 15000.00, TRUE),
('Prakash Reddy', 'Helper', 15000.00, TRUE),
('Kavita Singh', 'Cashier', 20000.00, TRUE);

-- Insert Staff Shifts
INSERT INTO Shift_Staff (Shift, Staff_ID) VALUES
('Morning', 1), ('Morning', 3), ('Morning', 4), ('Morning', 6),
('Afternoon', 1), ('Afternoon', 2), ('Afternoon', 5), ('Afternoon', 7),
('Evening', 2), ('Evening', 3), ('Evening', 5), ('Evening', 8);

-- Insert Orders
INSERT INTO `ORDER` (Date_Time, Status, Customer_ID) VALUES
('2025-10-01 08:30:00', 'Delivered', 1),
('2025-10-01 09:15:00', 'Delivered', 2),
('2025-10-01 12:45:00', 'Delivered', 3),
('2025-10-01 13:00:00', 'Delivered', 4),
('2025-10-02 08:45:00', 'Delivered', 5),
('2025-10-02 12:30:00', 'Delivered', 1),
('2025-10-03 09:00:00', 'Delivered', 6),
('2025-10-03 13:15:00', 'Preparing', 7),
('2025-10-04 08:30:00', 'Pending', 8),
('2025-10-04 12:00:00', 'Pending', 9);

-- Insert Order Details
INSERT INTO ORDER_DETAIL (Quantity, Order_ID, Item_ID) VALUES
-- Order 1 (Breakfast)
(2, 1, 1), (1, 1, 17),
-- Order 2 (Breakfast)
(1, 2, 2), (1, 2, 18),
-- Order 3 (Lunch)
(1, 3, 6), (1, 3, 21),
-- Order 4 (Lunch)
(1, 4, 7), (2, 4, 10), (1, 4, 20),
-- Order 5 (Breakfast)
(2, 5, 1), (1, 5, 3), (2, 5, 17),
-- Order 6 (Lunch)
(1, 6, 8), (3, 6, 10), (1, 6, 19),
-- Order 7 (Breakfast)
(1, 7, 4), (1, 7, 5), (1, 7, 18),
-- Order 8 (Lunch - Preparing)
(1, 8, 6), (2, 8, 11), (1, 8, 22),
-- Order 9 (Breakfast - Pending)
(2, 9, 2), (1, 9, 17),
-- Order 10 (Lunch - Pending)
(1, 10, 7), (1, 10, 16), (1, 10, 21);

-- Insert Payments
INSERT INTO PAYMENT (Order_ID) VALUES
(1), (2), (3), (4), (5), (6), (7), (8), (9), (10);

-- Insert Payment Modes
INSERT INTO Mode_payment (Mode, Payment_ID) VALUES
('Wallet', 1),
('UPI', 2),
('Wallet', 3),
('Card', 4),
('Wallet', 5),
('Wallet', 6),
('UPI', 7),
('Card', 8),
('Wallet', 9),
('UPI', 10);

-- Insert Reports
INSERT INTO REPORT (Date, Admin_ID) VALUES
('2025-10-01', 1),
('2025-10-02', 1),
('2025-10-03', 2);

-- Insert Report Types
INSERT INTO TYPE_REPORT (Type, Report_ID) VALUES
('Sales', 1),
('Sales', 2),
('Inventory', 3);

-- Insert Admin Management Records
INSERT INTO MANAGES_BY_ADMIN (Admin_ID, Item_ID, Offer_ID, Staff_ID) VALUES
(1, 1, 1, 1),
(1, 2, 1, 1),
(1, 6, 2, 1),
(2, 7, 2, 2),
(2, 12, 3, 3),
(3, 17, 5, 4);

-- =====================================================
-- SECTION 3: VERIFICATION QUERIES
-- =====================================================

SELECT '=== DATABASE CREATED SUCCESSFULLY ===' AS Status;

SELECT 'Total Tables:' AS Info, COUNT(*) AS Count FROM information_schema.tables WHERE table_schema = 'CCMS';

SELECT 'Admins:' AS Table_Name, COUNT(*) AS Row_Count FROM ADMIN
UNION ALL
SELECT 'Customers:', COUNT(*) FROM CUSTOMER
UNION ALL
SELECT 'Customer Phones:', COUNT(*) FROM Customer_Phone_NO
UNION ALL
SELECT 'Offers:', COUNT(*) FROM OFFER
UNION ALL
SELECT 'Menu Items:', COUNT(*) FROM MENU_ITEMS
UNION ALL
SELECT 'Categories:', COUNT(*) FROM Category_Menu
UNION ALL
SELECT 'Orders:', COUNT(*) FROM `ORDER`
UNION ALL
SELECT 'Order Details:', COUNT(*) FROM ORDER_DETAIL
UNION ALL
SELECT 'Payments:', COUNT(*) FROM PAYMENT
UNION ALL
SELECT 'Payment Modes:', COUNT(*) FROM Mode_payment
UNION ALL
SELECT 'Staff:', COUNT(*) FROM STAFF
UNION ALL
SELECT 'Staff Shifts:', COUNT(*) FROM Shift_Staff
UNION ALL
SELECT 'Reports:', COUNT(*) FROM REPORT
UNION ALL
SELECT 'Report Types:', COUNT(*) FROM TYPE_REPORT
UNION ALL
SELECT 'Admin Management:', COUNT(*) FROM MANAGES_BY_ADMIN;

-- =====================================================
-- SECTION 4: SAMPLE DATA DISPLAY
-- =====================================================

SELECT '=== SAMPLE MENU WITH CATEGORIES ===' AS Info;
SELECT 
    mi.Item_ID,
    mi.Name,
    cm.Category,
    mi.Price,
    IFNULL(o.Discount, 0) AS Discount_Percent,
    o.Description AS Offer_Description
FROM MENU_ITEMS mi
JOIN Category_Menu cm ON mi.Item_ID = cm.Item_ID
LEFT JOIN OFFER o ON mi.Offer_ID = o.Offer_ID
WHERE mi.Is_Active = TRUE
ORDER BY cm.Category, mi.Name;

SELECT '=== RECENT ORDERS ===' AS Info;
SELECT 
    ord.Order_ID,
    c.Name AS Customer_Name,
    ord.Date_Time,
    ord.Status,
    COUNT(od.Item_ID) AS Items_Count
FROM `ORDER` ord
JOIN CUSTOMER c ON ord.Customer_ID = c.Customer_ID
LEFT JOIN ORDER_DETAIL od ON ord.Order_ID = od.Order_ID
GROUP BY ord.Order_ID, c.Name, ord.Date_Time, ord.Status
ORDER BY ord.Date_Time DESC;

SELECT '=== CUSTOMER WALLET BALANCES ===' AS Info;
SELECT 
    Customer_ID,
    Name,
    Wallet_Bal,
    GROUP_CONCAT(cpn.Phone_No SEPARATOR ', ') AS Phone_Numbers
FROM CUSTOMER c
LEFT JOIN Customer_Phone_NO cpn ON c.Customer_ID = cpn.Customer_ID
GROUP BY Customer_ID, Name, Wallet_Bal
ORDER BY Name;

SELECT '=== STAFF SCHEDULE ===' AS Info;
SELECT 
    s.Staff_ID,
    s.Name,
    s.Role,
    GROUP_CONCAT(ss.Shift ORDER BY ss.Shift SEPARATOR ', ') AS Shifts,
    s.Salary
FROM STAFF s
LEFT JOIN Shift_Staff ss ON s.Staff_ID = ss.Staff_ID
GROUP BY s.Staff_ID, s.Name, s.Role, s.Salary
ORDER BY s.Role, s.Name;


SELECT '=== DATABASE SETUP COMPLETE ===' AS Status;
