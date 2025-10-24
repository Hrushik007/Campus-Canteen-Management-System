-- =====================================================
-- CAMPUS CANTEEN MANAGEMENT SYSTEM (CCMS)
-- DATABASE USERS & PRIVILEGE MANAGEMENT
-- This demonstrates varied user privileges for Review-4
-- =====================================================

USE CCMS;

-- =====================================================
-- SECTION 1: DROP EXISTING USERS (if any)
-- =====================================================

DROP USER IF EXISTS 'ccms_admin'@'localhost';
DROP USER IF EXISTS 'ccms_staff'@'localhost';
DROP USER IF EXISTS 'ccms_customer'@'localhost';
DROP USER IF EXISTS 'ccms_readonly'@'localhost';

-- =====================================================
-- SECTION 2: CREATE ADMIN USER (Full Privileges)
-- =====================================================

-- Admin User: Full access to all operations
CREATE USER 'ccms_admin'@'localhost' IDENTIFIED BY 'admin@123';

-- Grant all privileges on CCMS database
GRANT ALL PRIVILEGES ON CCMS.* TO 'ccms_admin'@'localhost';

-- Grant procedure execution rights
GRANT EXECUTE ON CCMS.* TO 'ccms_admin'@'localhost';

-- Admin can create/modify/delete tables, triggers, procedures
GRANT CREATE, ALTER, DROP ON CCMS.* TO 'ccms_admin'@'localhost';

-- Grant privilege to create other users (admin management)
GRANT CREATE USER ON *.* TO 'ccms_admin'@'localhost';

FLUSH PRIVILEGES;

SELECT 'Admin User Created: ccms_admin' AS Status;

-- =====================================================
-- SECTION 3: CREATE STAFF USER (Limited Privileges)
-- =====================================================

-- Staff User: Can view menu, update order status, view customer info
CREATE USER 'ccms_staff'@'localhost' IDENTIFIED BY 'staff@123';

-- READ access to reference tables
GRANT SELECT ON CCMS.MENU_ITEMS TO 'ccms_staff'@'localhost';
GRANT SELECT ON CCMS.Category_Menu TO 'ccms_staff'@'localhost';
GRANT SELECT ON CCMS.CUSTOMER TO 'ccms_staff'@'localhost';
GRANT SELECT ON CCMS.OFFER TO 'ccms_staff'@'localhost';
GRANT SELECT ON CCMS.STAFF TO 'ccms_staff'@'localhost';
GRANT SELECT ON CCMS.Shift_Staff TO 'ccms_staff'@'localhost';

-- READ and UPDATE access to orders (can change order status)
GRANT SELECT, UPDATE ON CCMS.`ORDER` TO 'ccms_staff'@'localhost';
GRANT SELECT ON CCMS.ORDER_DETAIL TO 'ccms_staff'@'localhost';
GRANT SELECT ON CCMS.PAYMENT TO 'ccms_staff'@'localhost';
GRANT SELECT ON CCMS.Mode_payment TO 'ccms_staff'@'localhost';

-- Allow staff to execute specific procedures
GRANT EXECUTE ON PROCEDURE CCMS.Update_Order_Status TO 'ccms_staff'@'localhost';
GRANT EXECUTE ON PROCEDURE CCMS.Get_Order_Details TO 'ccms_staff'@'localhost';
GRANT EXECUTE ON PROCEDURE CCMS.Get_Menu_With_Offers TO 'ccms_staff'@'localhost';

-- Allow staff to use specific functions
GRANT EXECUTE ON FUNCTION CCMS.Calculate_Order_Total TO 'ccms_staff'@'localhost';
GRANT EXECUTE ON FUNCTION CCMS.Get_Discounted_Price TO 'ccms_staff'@'localhost';

FLUSH PRIVILEGES;

SELECT 'Staff User Created: ccms_staff' AS Status;

-- =====================================================
-- SECTION 4: CREATE CUSTOMER USER (Restricted Privileges)
-- =====================================================

-- Customer User: Can view menu, place orders, view own orders
CREATE USER 'ccms_customer'@'localhost' IDENTIFIED BY 'customer@123';

-- READ-ONLY access to menu and offers
GRANT SELECT ON CCMS.MENU_ITEMS TO 'ccms_customer'@'localhost';
GRANT SELECT ON CCMS.Category_Menu TO 'ccms_customer'@'localhost';
GRANT SELECT ON CCMS.OFFER TO 'ccms_customer'@'localhost';

-- Limited access to customer table (own data only - enforced by application)
GRANT SELECT, UPDATE ON CCMS.CUSTOMER TO 'ccms_customer'@'localhost';
GRANT SELECT ON CCMS.Customer_Phone_NO TO 'ccms_customer'@'localhost';

-- Limited access to orders (own orders only - enforced by application)
GRANT SELECT, INSERT ON CCMS.`ORDER` TO 'ccms_customer'@'localhost';
GRANT SELECT, INSERT ON CCMS.ORDER_DETAIL TO 'ccms_customer'@'localhost';
GRANT SELECT ON CCMS.PAYMENT TO 'ccms_customer'@'localhost';
GRANT SELECT ON CCMS.Mode_payment TO 'ccms_customer'@'localhost';

-- Allow customer to execute ordering procedures
GRANT EXECUTE ON PROCEDURE CCMS.Place_Order TO 'ccms_customer'@'localhost';
GRANT EXECUTE ON PROCEDURE CCMS.TopUp_Wallet TO 'ccms_customer'@'localhost';
GRANT EXECUTE ON PROCEDURE CCMS.Get_Customer_Orders TO 'ccms_customer'@'localhost';
GRANT EXECUTE ON PROCEDURE CCMS.Get_Order_Details TO 'ccms_customer'@'localhost';
GRANT EXECUTE ON PROCEDURE CCMS.Get_Menu_With_Offers TO 'ccms_customer'@'localhost';
GRANT EXECUTE ON PROCEDURE CCMS.Cancel_Order TO 'ccms_customer'@'localhost';

-- Allow customer to use utility functions
GRANT EXECUTE ON FUNCTION CCMS.Calculate_Order_Total TO 'ccms_customer'@'localhost';
GRANT EXECUTE ON FUNCTION CCMS.Check_Wallet_Balance TO 'ccms_customer'@'localhost';
GRANT EXECUTE ON FUNCTION CCMS.Get_Discounted_Price TO 'ccms_customer'@'localhost';
GRANT EXECUTE ON FUNCTION CCMS.Get_Item_Discount TO 'ccms_customer'@'localhost';
GRANT EXECUTE ON FUNCTION CCMS.Count_Pending_Orders TO 'ccms_customer'@'localhost';

FLUSH PRIVILEGES;

SELECT 'Customer User Created: ccms_customer' AS Status;

-- =====================================================
-- SECTION 5: CREATE READ-ONLY USER (Reports Only)
-- =====================================================

-- Read-Only User: For reporting and analytics only
CREATE USER 'ccms_readonly'@'localhost' IDENTIFIED BY 'readonly@123';

-- Grant SELECT on all tables
GRANT SELECT ON CCMS.* TO 'ccms_readonly'@'localhost';

-- Allow execution of reporting procedures
GRANT EXECUTE ON PROCEDURE CCMS.Generate_Sales_Report TO 'ccms_readonly'@'localhost';
GRANT EXECUTE ON PROCEDURE CCMS.Get_Popular_Items TO 'ccms_readonly'@'localhost';
GRANT EXECUTE ON PROCEDURE CCMS.Get_Peak_Hours_Analysis TO 'ccms_readonly'@'localhost';

-- Allow execution of all functions (read-only)
GRANT EXECUTE ON FUNCTION CCMS.Calculate_Order_Total TO 'ccms_readonly'@'localhost';
GRANT EXECUTE ON FUNCTION CCMS.Get_Daily_Sales TO 'ccms_readonly'@'localhost';
GRANT EXECUTE ON FUNCTION CCMS.Get_Discounted_Price TO 'ccms_readonly'@'localhost';

FLUSH PRIVILEGES;

SELECT 'Read-Only User Created: ccms_readonly' AS Status;

-- =====================================================
-- SECTION 6: VERIFICATION - Show User Privileges
-- =====================================================

SELECT '========================================' AS '';
SELECT '   USER PRIVILEGES SUMMARY' AS '';
SELECT '========================================' AS '';

-- Show Admin privileges
SELECT 'ADMIN USER PRIVILEGES (ccms_admin):' AS Info;
SHOW GRANTS FOR 'ccms_admin'@'localhost';

SELECT '' AS '';
SELECT 'STAFF USER PRIVILEGES (ccms_staff):' AS Info;
SHOW GRANTS FOR 'ccms_staff'@'localhost';

SELECT '' AS '';
SELECT 'CUSTOMER USER PRIVILEGES (ccms_customer):' AS Info;
SHOW GRANTS FOR 'ccms_customer'@'localhost';

SELECT '' AS '';
SELECT 'READ-ONLY USER PRIVILEGES (ccms_readonly):' AS Info;
SHOW GRANTS FOR 'ccms_readonly'@'localhost';

-- =====================================================
-- SECTION 7: USER CREDENTIALS SUMMARY
-- =====================================================

SELECT '========================================' AS '';
SELECT '   USER CREDENTIALS FOR APPLICATION' AS '';
SELECT '========================================' AS '';

SELECT 
    'User Type' AS User_Type,
    'Username' AS Username,
    'Password' AS Password,
    'Access Level' AS Access_Level
UNION ALL
SELECT 
    'Admin',
    'ccms_admin',
    'admin@123',
    'Full Access - All Operations'
UNION ALL
SELECT 
    'Staff',
    'ccms_staff',
    'staff@123',
    'Limited - View Menu, Update Orders'
UNION ALL
SELECT 
    'Customer',
    'ccms_customer',
    'customer@123',
    'Restricted - View Menu, Place Orders'
UNION ALL
SELECT 
    'Reports',
    'ccms_readonly',
    'readonly@123',
    'Read-Only - Reports & Analytics Only';

-- =====================================================
-- SECTION 8: PRIVILEGE TESTING QUERIES
-- =====================================================

SELECT '========================================' AS '';
SELECT '   PRIVILEGE TESTING EXAMPLES' AS '';
SELECT '========================================' AS '';

SELECT 'To test different user privileges, run these commands:' AS Info;

SELECT 'Test Admin User:' AS Test
UNION ALL SELECT '  mysql -u ccms_admin -p'
UNION ALL SELECT '  Password: admin@123'
UNION ALL SELECT '  Should have full access to all operations'
UNION ALL SELECT ''
UNION ALL SELECT 'Test Staff User:'
UNION ALL SELECT '  mysql -u ccms_staff -p'
UNION ALL SELECT '  Password: staff@123'
UNION ALL SELECT '  Can SELECT from MENU_ITEMS, UPDATE ORDER status'
UNION ALL SELECT '  Cannot DELETE or CREATE tables'
UNION ALL SELECT ''
UNION ALL SELECT 'Test Customer User:'
UNION ALL SELECT '  mysql -u ccms_customer -p'
UNION ALL SELECT '  Password: customer@123'
UNION ALL SELECT '  Can SELECT menu, place orders via procedures'
UNION ALL SELECT '  Cannot access ADMIN or STAFF tables'
UNION ALL SELECT ''
UNION ALL SELECT 'Test Read-Only User:'
UNION ALL SELECT '  mysql -u ccms_readonly -p'
UNION ALL SELECT '  Password: readonly@123'
UNION ALL SELECT '  Can only SELECT and run report procedures'
UNION ALL SELECT '  Cannot INSERT, UPDATE, or DELETE';

-- =====================================================
-- SECTION 9: PRIVILEGE DEMONSTRATION
-- =====================================================

SELECT '========================================' AS '';
SELECT '   PRIVILEGE LEVELS EXPLAINED' AS '';
SELECT '========================================' AS '';

CREATE TEMPORARY TABLE IF NOT EXISTS Privilege_Matrix (
    Operation VARCHAR(50),
    Admin VARCHAR(10),
    Staff VARCHAR(10),
    Customer VARCHAR(10),
    ReadOnly VARCHAR(10)
);

INSERT INTO Privilege_Matrix VALUES
('Create Tables', 'YES', 'NO', 'NO', 'NO'),
('Drop Tables', 'YES', 'NO', 'NO', 'NO'),
('View Menu', 'YES', 'YES', 'YES', 'YES'),
('Add Menu Items', 'YES', 'NO', 'NO', 'NO'),
('Update Menu', 'YES', 'NO', 'NO', 'NO'),
('Delete Menu', 'YES', 'NO', 'NO', 'NO'),
('View Orders', 'YES', 'YES', 'Own Only', 'YES'),
('Place Orders', 'YES', 'NO', 'YES', 'NO'),
('Update Order Status', 'YES', 'YES', 'NO', 'NO'),
('Cancel Orders', 'YES', 'NO', 'YES', 'NO'),
('View Customers', 'YES', 'YES', 'Self Only', 'YES'),
('Add Customers', 'YES', 'NO', 'NO', 'NO'),
('Update Wallet', 'YES', 'NO', 'Self Only', 'NO'),
('Manage Staff', 'YES', 'NO', 'NO', 'NO'),
('Manage Offers', 'YES', 'NO', 'NO', 'NO'),
('Generate Reports', 'YES', 'NO', 'NO', 'YES'),
('Execute Procedures', 'ALL', 'Limited', 'Limited', 'Reports'),
('Execute Functions', 'ALL', 'Limited', 'Limited', 'ALL');

SELECT * FROM Privilege_Matrix;

-- =====================================================
-- SECTION 10: SECURITY NOTES
-- =====================================================

SELECT '========================================' AS '';
SELECT '   SECURITY BEST PRACTICES' AS '';
SELECT '========================================' AS '';

SELECT 
    'Security Measure' AS Measure,
    'Implementation' AS Implementation
UNION ALL SELECT 
    'Password Protection',
    'All users require passwords'
UNION ALL SELECT 
    'Principle of Least Privilege',
    'Each user has minimum required access'
UNION ALL SELECT 
    'Role-Based Access Control',
    'Privileges based on job function'
UNION ALL SELECT 
    'Sensitive Data Protection',
    'Customers can only access own data'
UNION ALL SELECT 
    'Procedure-Based Operations',
    'Business logic enforced via procedures'
UNION ALL SELECT 
    'Audit Trail',
    'ORDER_STATUS_AUDIT tracks changes'
UNION ALL SELECT 
    'Data Validation',
    'Triggers prevent invalid data';

SELECT '========================================' AS '';
SELECT '   SETUP COMPLETE!' AS '';
SELECT '========================================' AS '';
SELECT 'Four user types created with varied privileges' AS Summary
UNION ALL SELECT 'Ready for Review-4 demonstration';