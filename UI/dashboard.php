<?php
require_once '../config.php';
requireRole('staff');
$conn = getDBConnection();
$orders = $conn->query("SELECT o.*, c.Name as customer FROM `ORDER` o JOIN CUSTOMER c ON o.Customer_ID=c.Customer_ID WHERE o.Status IN ('Pending','Preparing') ORDER BY o.Date_Time");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Staff Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <h2>Active Orders</h2>
        <table class="table">
            <thead><tr><th>Order ID</th><th>Customer</th><th>Time</th><th>Status</th></tr></thead>
            <tbody>
                <?php while($o = $orders->fetch_assoc()): ?>
                <tr>
                    <td>#<?php echo $o['Order_ID']; ?></td>
                    <td><?php echo $o['customer']; ?></td>
                    <td><?php echo $o['Date_Time']; ?></td>
                    <td><?php echo $o['Status']; ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</body>
</html>