<!-- =====================================================
     FILE: admin/orders.php
     Order Management with Status Updates
     ===================================================== -->
<?php
require_once '../config.php';
requireRole('admin');

$conn = getDBConnection();
$message = '';

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $order_id = $_POST['order_id'];
    $new_status = $_POST['status'];
    
    $stmt = $conn->prepare("CALL Update_Order_Status(?, ?, @status_msg)");
    $stmt->bind_param("is", $order_id, $new_status);
    $stmt->execute();
    
    $result = $conn->query("SELECT @status_msg as msg");
    $message = $result->fetch_assoc()['msg'];
}

// Get filter
$status_filter = $_GET['status'] ?? 'all';

// Build query
$query = "
    SELECT 
        o.Order_ID,
        c.Name AS Customer_Name,
        c.Customer_ID,
        o.Date_Time,
        o.Status,
        Calculate_Order_Total(o.Order_ID) AS Total,
        COUNT(od.Item_ID) AS Item_Count
    FROM `ORDER` o
    JOIN CUSTOMER c ON o.Customer_ID = c.Customer_ID
    LEFT JOIN ORDER_DETAIL od ON o.Order_ID = od.Order_ID
";

if ($status_filter !== 'all') {
    $query .= " WHERE o.Status = '$status_filter'";
}

$query .= " GROUP BY o.Order_ID, c.Name, c.Customer_ID, o.Date_Time, o.Status
            ORDER BY o.Date_Time DESC";

$orders = $conn->query($query);

// Get status counts
$status_counts = $conn->query("
    SELECT Status, COUNT(*) as count 
    FROM `ORDER` 
    GROUP BY Status
");
$counts = [];
while ($row = $status_counts->fetch_assoc()) {
    $counts[$row['Status']] = $row['count'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Management - CCMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <style>
        body { background-color: #f8f9fa; }
        .sidebar {
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .sidebar a {
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            padding: 12px 20px;
            display: block;
        }
        .sidebar a:hover, .sidebar a.active {
            background: rgba(255,255,255,0.2);
            color: white;
        }
        .filter-badge {
            cursor: pointer;
            margin: 5px;
            padding: 8px 15px;
        }
        .filter-badge.active {
            background: #667eea !important;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-2 sidebar p-0">
                <div class="p-3 text-center">
                    <h4><i class="bi bi-shop"></i> CCMS Admin</h4>
                </div>
                <nav class="mt-3">
                    <a href="dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard</a>
                    <a href="menu.php"><i class="bi bi-card-list"></i> Manage Menu</a>
                    <a href="orders.php" class="active"><i class="bi bi-cart"></i> Orders</a>
                    <a href="customers.php"><i class="bi bi-people"></i> Customers</a>
                    <a href="staff.php"><i class="bi bi-person-badge"></i> Staff</a>
                    <a href="offers.php"><i class="bi bi-tag"></i> Offers</a>
                    <a href="reports.php"><i class="bi bi-bar-chart"></i> Reports</a>
                    <a href="../logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a>
                </nav>
            </div>
            
            <!-- Main Content -->
            <div class="col-md-10 p-4">
                <h2 class="mb-4"><i class="bi bi-cart"></i> Order Management</h2>
                
                <?php if ($message): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <?php echo $message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>
                
                <!-- Status Filter -->
                <div class="card mb-4">
                    <div class="card-body">
                        <h6>Filter by Status:</h6>
                        <a href="?status=all">
                            <span class="badge bg-secondary filter-badge <?php echo $status_filter === 'all' ? 'active' : ''; ?>">
                                All Orders
                            </span>
                        </a>
                        <a href="?status=Pending">
                            <span class="badge bg-warning filter-badge <?php echo $status_filter === 'Pending' ? 'active' : ''; ?>">
                                Pending (<?php echo $counts['Pending'] ?? 0; ?>)
                            </span>
                        </a>
                        <a href="?status=Preparing">
                            <span class="badge bg-info filter-badge <?php echo $status_filter === 'Preparing' ? 'active' : ''; ?>">
                                Preparing (<?php echo $counts['Preparing'] ?? 0; ?>)
                            </span>
                        </a>
                        <a href="?status=Delivered">
                            <span class="badge bg-success filter-badge <?php echo $status_filter === 'Delivered' ? 'active' : ''; ?>">
                                Delivered (<?php echo $counts['Delivered'] ?? 0; ?>)
                            </span>
                        </a>
                        <a href="?status=Cancelled">
                            <span class="badge bg-danger filter-badge <?php echo $status_filter === 'Cancelled' ? 'active' : ''; ?>">
                                Cancelled (<?php echo $counts['Cancelled'] ?? 0; ?>)
                            </span>
                        </a>
                    </div>
                </div>
                
                <!-- Orders Table -->
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Order ID</th>
                                        <th>Customer</th>
                                        <th>Date & Time</th>
                                        <th>Items</th>
                                        <th>Total Amount</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($order = $orders->fetch_assoc()): ?>
                                    <tr>
                                        <td><strong>#<?php echo $order['Order_ID']; ?></strong></td>
                                        <td>
                                            <i class="bi bi-person-circle text-primary"></i>
                                            <?php echo $order['Customer_Name']; ?>
                                        </td>
                                        <td><?php echo date('d M Y, h:i A', strtotime($order['Date_Time'])); ?></td>
                                        <td><?php echo $order['Item_Count']; ?> items</td>
                                        <td class="text-success fw-bold">₹<?php echo number_format($order['Total'], 2); ?></td>
                                        <td>
                                            <?php 
                                            $badge_class = [
                                                'Pending' => 'warning',
                                                'Preparing' => 'info',
                                                'Delivered' => 'success',
                                                'Cancelled' => 'danger'
                                            ];
                                            $class = $badge_class[$order['Status']] ?? 'secondary';
                                            ?>
                                            <span class="badge bg-<?php echo $class; ?>">
                                                <?php echo $order['Status']; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-primary" 
                                                    onclick="viewOrder(<?php echo $order['Order_ID']; ?>)">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                            <?php if ($order['Status'] !== 'Delivered' && $order['Status'] !== 'Cancelled'): ?>
                                            <button class="btn btn-sm btn-success" 
                                                    onclick="updateStatus(<?php echo $order['Order_ID']; ?>, '<?php echo $order['Status']; ?>')">
                                                <i class="bi bi-arrow-right-circle"></i>
                                            </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Update Status Modal -->
    <div class="modal fade" id="statusModal">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title">Update Order Status</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="order_id" id="modal_order_id">
                        <div class="mb-3">
                            <label class="form-label">Current Status: <strong id="current_status"></strong></label>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">New Status</label>
                            <select name="status" class="form-control" required>
                                <option value="Pending">Pending</option>
                                <option value="Preparing">Preparing</option>
                                <option value="Delivered">Delivered</option>
                                <option value="Cancelled">Cancelled</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" name="update_status" class="btn btn-primary">Update Status</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Order Details Modal -->
    <div class="modal fade" id="orderDetailsModal">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Order Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="orderDetailsContent">
                    Loading...
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function updateStatus(orderId, currentStatus) {
            document.getElementById('modal_order_id').value = orderId;
            document.getElementById('current_status').textContent = currentStatus;
            new bootstrap.Modal(document.getElementById('statusModal')).show();
        }
        
        function viewOrder(orderId) {
            const modal = new bootstrap.Modal(document.getElementById('orderDetailsModal'));
            modal.show();
            
            fetch('get_order_details.php?id=' + orderId)
                .then(response => response.text())
                .then(html => {
                    document.getElementById('orderDetailsContent').innerHTML = html;
                })
                .catch(error => {
                    document.getElementById('orderDetailsContent').innerHTML = 
                        '<div class="alert alert-danger">Error loading order details</div>';
                });
        }
    </script>
</body>
</html>
