<?php
require_once '../config.php';
requireRole('admin');
$conn = getDBConnection();
$customers = $conn->query("SELECT c.*, GROUP_CONCAT(cp.Phone_No) as phones FROM CUSTOMER c LEFT JOIN Customer_Phone_NO cp ON c.Customer_ID = cp.Customer_ID GROUP BY c.Customer_ID");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Customers - CCMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <h2>Customer List</h2>
        <table class="table">
            <thead><tr><th>ID</th><th>Name</th><th>DOB</th><th>Wallet</th><th>Phone</th></tr></thead>
            <tbody>
                <?php while($c = $customers->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $c['Customer_ID']; ?></td>
                    <td><?php echo $c['Name']; ?></td>
                    <td><?php echo $c['DOB']; ?></td>
                    <td>₹<?php echo $c['Wallet_Bal']; ?></td>
                    <td><?php echo $c['phones']; ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</body>

</html>
