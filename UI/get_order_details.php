<!-- =====================================================
     FILE: admin/get_order_details.php
     AJAX endpoint for order details
     ===================================================== -->
<?php
require_once '../config.php';
requireRole('admin');

$conn = getDBConnection();
$order_id = $_GET['id'] ?? 0;

// Get order info
$order_query = "
    SELECT 
        o.Order_ID,
        o.Date_Time,
        o.Status,
        c.Name AS Customer_Name,
        c.Customer_ID,
        Calculate_Order_Total(o.Order_ID) AS Total
    FROM `ORDER` o
    JOIN CUSTOMER c ON o.Customer_ID = c.Customer_ID
    WHERE o.Order_ID = ?
";
$stmt = $conn->prepare($order_query);
$stmt->bind_param("i", $order_id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

// Get order items
$items_query = "
    SELECT 
        mi.Name,
        od.Quantity,
        mi.Price,
        Get_Item_Discount(mi.Item_ID) AS Discount,
        (od.Quantity * Get_Discounted_Price(mi.Item_ID)) AS Item_Total
    FROM ORDER_DETAIL od
    JOIN MENU_ITEMS mi ON od.Item_ID = mi.Item_ID
    WHERE od.Order_ID = ?
";
$stmt = $conn->prepare($items_query);
$stmt->bind_param("i", $order_id);
$stmt->execute();
$items = $stmt->get_result();
?>

<div class="row mb-3">
    <div class="col-md-6">
        <p><strong>Order ID:</strong> #<?php echo $order['Order_ID']; ?></p>
        <p><strong>Customer:</strong> <?php echo $order['Customer_Name']; ?> (ID: <?php echo $order['Customer_ID']; ?>)</p>
        <p><strong>Order Date:</strong> <?php echo date('d M Y, h:i A', strtotime($order['Date_Time'])); ?></p>
    </div>
    <div class="col-md-6 text-end">
        <p><strong>Status:</strong> 
            <span class="badge bg-<?php 
                $badges = ['Pending'=>'warning', 'Preparing'=>'info', 'Delivered'=>'success', 'Cancelled'=>'danger'];
                echo $badges[$order['Status']];
            ?>">
                <?php echo $order['Status']; ?>
            </span>
        </p>
    </div>
</div>

<table class="table table-striped">
    <thead>
        <tr>
            <th>Item</th>
            <th>Price</th>
            <th>Discount</th>
            <th>Quantity</th>
            <th>Total</th>
        </tr>
    </thead>
    <tbody>
        <?php while ($item = $items->fetch_assoc()): ?>
        <tr>
            <td><?php echo $item['Name']; ?></td>
            <td>₹<?php echo number_format($item['Price'], 2); ?></td>
            <td><?php echo $item['Discount'] > 0 ? $item['Discount'] . '%' : '-'; ?></td>
            <td><?php echo $item['Quantity']; ?></td>
            <td>₹<?php echo number_format($item['Item_Total'], 2); ?></td>
        </tr>
        <?php endwhile; ?>
    </tbody>
    <tfoot>
        <tr class="table-success fw-bold">
            <td colspan="4" class="text-end">Grand Total:</td>
            <td>₹<?php echo number_format($order['Total'], 2); ?></td>
        </tr>
    </tfoot>
</table>

