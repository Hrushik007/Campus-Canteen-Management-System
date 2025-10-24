<!-- =====================================================
     FILE: customer/order_details.php
     View Order Details
     ===================================================== -->
<?php
require_once '../config.php';
requireRole('customer');

$conn = getDBConnection();
$customer_id = $_SESSION['user_id'];
$order_id = $_GET['id'] ?? 0;

// Get order details
$order_query = "
    SELECT 
        o.Order_ID,
        o.Date_Time,
        o.Status,
        c.Name AS Customer_Name,
        Calculate_Order_Total(o.Order_ID) AS Total
    FROM `ORDER` o
    JOIN CUSTOMER c ON o.Customer_ID = c.Customer_ID
    WHERE o.Order_ID = ? AND o.Customer_ID = ?
";
$stmt = $conn->prepare($order_query);
$stmt->bind_param("ii", $order_id, $customer_id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) {
    header("Location: dashboard.php");
    exit();
}

// Get order items
$items_query = "
    SELECT 
        mi.Name,
        od.Quantity,
        mi.Price,
        (od.Quantity * mi.Price) AS Item_Total
    FROM ORDER_DETAIL od
    JOIN MENU_ITEMS mi ON od.Item_ID = mi.Item_ID
    WHERE od.Order_ID = ?
";
$stmt = $conn->prepare($items_query);
$stmt->bind_param("i", $order_id);
$stmt->execute();
$items = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Details - CCMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <style>
        body { background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%); min-height: 100vh; }
    </style>
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <a href="dashboard.php" class="btn btn-outline-primary mb-3">
                    <i class="bi bi-arrow-left"></i> Back to Dashboard
                </a>
                
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0"><i class="bi bi-receipt"></i> Order #<?php echo $order['Order_ID']; ?></h4>
                    </div>
                    <div class="card-body">
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <p><strong>Customer:</strong> <?php echo $order['Customer_Name']; ?></p>
                                <p><strong>Order Date:</strong> <?php echo date('d M Y, h:i A', strtotime($order['Date_Time'])); ?></p>
                            </div>
                            <div class="col-md-6 text-end">
                                <p><strong>Status:</strong>
                                    <?php 
                                    $badge = ['Pending'=>'warning', 'Preparing'=>'info', 'Delivered'=>'success', 'Cancelled'=>'danger'];
                                    ?>
                                    <span class="badge bg-<?php echo $badge[$order['Status']]; ?> fs-6">
                                        <?php echo $order['Status']; ?>
                                    </span>
                                </p>
                            </div>
                        </div>
                        
                        <h5>Order Items:</h5>
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Item</th>
                                    <th>Price</th>
                                    <th>Quantity</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($item = $items->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $item['Name']; ?></td>
                                    <td>₹<?php echo number_format($item['Price'], 2); ?></td>
                                    <td><?php echo $item['Quantity']; ?></td>
                                    <td>₹<?php echo number_format($item['Item_Total'], 2); ?></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                            <tfoot>
                                <tr class="table-success">
                                    <td colspan="3" class="text-end"><strong>Grand Total:</strong></td>
                                    <td><strong>₹<?php echo number_format($order['Total'], 2); ?></strong></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>