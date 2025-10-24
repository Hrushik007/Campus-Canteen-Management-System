<!-- =====================================================
     FILE: admin/dashboard.php
     Admin Dashboard - Main Overview
     ===================================================== -->
<?php
require_once '../config.php';
requireRole('admin');

$conn = getDBConnection();

// Get statistics
$stats = [];

// Total customers
$result = $conn->query("SELECT COUNT(*) as count FROM CUSTOMER WHERE Is_Active = 1");
$stats['customers'] = $result->fetch_assoc()['count'];

// Total orders today
$result = $conn->query("SELECT COUNT(*) as count FROM `ORDER` WHERE DATE(Date_Time) = CURDATE()");
$stats['orders_today'] = $result->fetch_assoc()['count'];

// Today's revenue
$result = $conn->query("SELECT Get_Daily_Sales(CURDATE()) as revenue");
$stats['revenue_today'] = $result->fetch_assoc()['revenue'];

// Pending orders
$result = $conn->query("SELECT COUNT(*) as count FROM `ORDER` WHERE Status IN ('Pending', 'Preparing')");
$stats['pending_orders'] = $result->fetch_assoc()['count'];

// Recent orders
$recent_orders = $conn->query("
    SELECT o.Order_ID, c.Name as Customer, o.Date_Time, o.Status,
           Calculate_Order_Total(o.Order_ID) as Total
    FROM `ORDER` o
    JOIN CUSTOMER c ON o.Customer_ID = c.Customer_ID
    ORDER BY o.Date_Time DESC
    LIMIT 5
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - CCMS</title>
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
            transition: all 0.3s;
        }
        .sidebar a:hover, .sidebar a.active {
            background: rgba(255,255,255,0.2);
            color: white;
        }
        .stat-card {
            border-radius: 10px;
            padding: 20px;
            color: white;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .stat-card.success { background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); }
        .stat-card.warning { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); }
        .stat-card.info { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-2 sidebar p-0">
                <div class="p-3 text-center">
                    <h4><i class="bi bi-shop"></i> CCMS Admin</h4>
                    <hr class="bg-white">
                    <p class="mb-0"><?php echo $_SESSION['name']; ?></p>
                    <small>Administrator</small>
                </div>
                <nav class="mt-3">
                    <a href="dashboard.php" class="active"><i class="bi bi-speedometer2"></i> Dashboard</a>
                    <a href="menu.php"><i class="bi bi-card-list"></i> Manage Menu</a>
                    <a href="orders.php"><i class="bi bi-cart"></i> Orders</a>
                    <a href="customers.php"><i class="bi bi-people"></i> Customers</a>
                    <a href="staff.php"><i class="bi bi-person-badge"></i> Staff</a>
                    <a href="offers.php"><i class="bi bi-tag"></i> Offers</a>
                    <a href="reports.php"><i class="bi bi-bar-chart"></i> Reports</a>
                    <a href="../logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a>
                </nav>
            </div>
            
            <!-- Main Content -->
            <div class="col-md-10 p-4">
                <h2 class="mb-4">Dashboard Overview</h2>
                
                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-md-3 mb-3">
                        <div class="stat-card">
                            <h6>Total Customers</h6>
                            <h2><?php echo $stats['customers']; ?></h2>
                            <small><i class="bi bi-people"></i> Active Users</small>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="stat-card success">
                            <h6>Orders Today</h6>
                            <h2><?php echo $stats['orders_today']; ?></h2>
                            <small><i class="bi bi-cart"></i> Total Orders</small>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="stat-card warning">
                            <h6>Pending Orders</h6>
                            <h2><?php echo $stats['pending_orders']; ?></h2>
                            <small><i class="bi bi-clock"></i> Need Attention</small>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="stat-card info">
                            <h6>Today's Revenue</h6>
                            <h2>₹<?php echo number_format($stats['revenue_today'], 2); ?></h2>
                            <small><i class="bi bi-currency-rupee"></i> Sales</small>
                        </div>
                    </div>
                </div>
                
                <!-- Recent Orders -->
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="bi bi-clock-history"></i> Recent Orders</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Order ID</th>
                                        <th>Customer</th>
                                        <th>Date & Time</th>
                                        <th>Status</th>
                                        <th>Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($order = $recent_orders->fetch_assoc()): ?>
                                    <tr>
                                        <td>#<?php echo $order['Order_ID']; ?></td>
                                        <td><?php echo $order['Customer']; ?></td>
                                        <td><?php echo date('d M Y, h:i A', strtotime($order['Date_Time'])); ?></td>
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
                                            <span class="badge bg-<?php echo $class; ?>"><?php echo $order['Status']; ?></span>
                                        </td>
                                        <td>₹<?php echo number_format($order['Total'], 2); ?></td>
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
</body>
</html>