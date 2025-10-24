<?php
require_once '../config.php';
requireRole('admin');
$conn = getDBConnection();
$offers = $conn->query("SELECT * FROM OFFER ORDER BY Valid_Time DESC");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Offers - CCMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <h2>Offers Management</h2>
        <table class="table">
            <thead><tr><th>ID</th><th>Description</th><th>Discount</th><th>Valid Until</th><th>Status</th></tr></thead>
            <tbody>
                <?php while($o = $offers->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $o['Offer_ID']; ?></td>
                    <td><?php echo $o['Description']; ?></td>
                    <td><?php echo $o['Discount']; ?>%</td>
                    <td><?php echo $o['Valid_Time']; ?></td>
                    <td><?php echo $o['Is_Active'] ? 'Active' : 'Inactive'; ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</body>
</html>