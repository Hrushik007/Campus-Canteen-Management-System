<!-- =====================================================
     FILE: admin/reports.php
     Reports with Complex Queries (Nested, Join, Aggregate)
     ===================================================== -->
<?php
require_once '../config.php';
requireRole('admin');

$conn = getDBConnection();

// NESTED QUERY: Customers who ordered more than average
$nested_query = "
    SELECT c.Customer_ID, c.Name, c.Wallet_Bal,
           COUNT(o.Order_ID) as Total_Orders,
           SUM(Calculate_Order_Total(o.Order_ID)) as Total_Spent
    FROM CUSTOMER c
    JOIN `ORDER` o ON c.Customer_ID = o.Customer_ID
    WHERE o.Status = 'Delivered'
    GROUP BY c.Customer_ID, c.Name, c.Wallet_Bal
    HAVING COUNT(o.Order_ID) > (
        SELECT AVG(order_count) 
        FROM (
            SELECT COUNT(*) as order_count 
            FROM `ORDER` 
            WHERE Status = 'Delivered'
            GROUP BY Customer_ID
        ) as avg_orders
    )
    ORDER BY Total_Orders DESC
";
$nested_result = $conn->query($nested_query);

// JOIN QUERY: Order details with customer and items
$join_query = "
    SELECT 
        o.Order_ID,
        o.Date_Time,
        c.Name AS Customer_Name,
        c.Customer_ID,
        GROUP_CONCAT(mi.Name SEPARATOR ', ') AS Items,
        SUM(od.Quantity) AS Total_Items,
        o.Status,
        Calculate_Order_Total(o.Order_ID) AS Order_Total
    FROM `ORDER` o
    JOIN CUSTOMER c ON o.Customer_ID = c.Customer_ID
    JOIN ORDER_DETAIL od ON o.Order_ID = od.Order_ID
    JOIN MENU_ITEMS mi ON od.Item_ID = mi.Item_ID
    GROUP BY o.Order_ID, o.Date_Time, c.Name, c.Customer_ID, o.Status
    ORDER BY o.Date_Time DESC
    LIMIT 20
";
$join_result = $conn->query($join_query);

// AGGREGATE QUERY: Sales by Category
$aggregate_query = "
    SELECT 
        cm.Category,
        COUNT(DISTINCT od.Order_ID) AS Total_Orders,
        SUM(od.Quantity) AS Items_Sold,
        SUM(od.Quantity * mi.Price) AS Gross_Revenue,
        AVG(od.Quantity * mi.Price) AS Avg_Item_Value,
        MAX(od.Quantity * mi.Price) AS Max_Transaction
    FROM ORDER_DETAIL od
    JOIN MENU_ITEMS mi ON od.Item_ID = mi.Item_ID
    JOIN Category_Menu cm ON mi.Item_ID = cm.Item_ID
    JOIN `ORDER` o ON od.Order_ID = o.Order_ID
    WHERE o.Status = 'Delivered'
    GROUP BY cm.Category
    ORDER BY Gross_Revenue DESC
";
$aggregate_result = $conn->query($aggregate_query);

// Additional aggregate - Peak hours
$peak_hours = $conn->query("
    SELECT 
        HOUR(Date_Time) AS Hour,
        COUNT(*) AS Order_Count,
        SUM(Calculate_Order_Total(Order_ID)) AS Revenue
    FROM `ORDER`
    WHERE Status = 'Delivered'
    GROUP BY HOUR(Date_Time)
    ORDER BY Order_Count DESC
    LIMIT 5
");

// Popular items
$popular_items = $conn->query("
    SELECT 
        mi.Name,
        cm.Category,
        SUM(od.Quantity) AS Times_Ordered,
        SUM(od.Quantity * mi.Price) AS Revenue
    FROM ORDER_DETAIL od
    JOIN MENU_ITEMS mi ON od.Item_ID = mi.Item_ID
    JOIN Category_Menu cm ON mi.Item_ID = cm.Item_ID
    JOIN `ORDER` o ON od.Order_ID = o.Order_ID
    WHERE o.Status = 'Delivered'
    GROUP BY mi.Name, cm.Category
    ORDER BY Times_Ordered DESC
    LIMIT 10
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports & Analytics - CCMS</title>
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
        .query-badge {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .nested-badge { background: #f093fb; color: white; }
        .join-badge { background: #4facfe; color: white; }
        .aggregate-badge { background: #11998e; color: white; }
        .report-card {
            border-left: 4px solid #667eea;
            margin-bottom: 30px;
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
                    <a href="orders.php"><i class="bi bi-cart"></i> Orders</a>
                    <a href="customers.php"><i class="bi bi-people"></i> Customers</a>
                    <a href="staff.php"><i class="bi bi-person-badge"></i> Staff</a>
                    <a href="offers.php"><i class="bi bi-tag"></i> Offers</a>
                    <a href="reports.php" class="active"><i class="bi bi-bar-chart"></i> Reports</a>
                    <a href="../logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a>
                </nav>
            </div>
            
            <!-- Main Content -->
            <div class="col-md-10 p-4">
                <h2 class="mb-4"><i class="bi bi-bar-chart-fill"></i> Reports & Analytics</h2>
                
                <!-- NESTED QUERY REPORT -->
                <div class="card report-card">
                    <div class="card-header bg-white">
                        <span class="query-badge nested-badge">
                            <i class="bi bi-layers"></i> NESTED QUERY
                        </span>
                        <h5 class="mt-2 mb-0">Top Customers (Above Average Orders)</h5>
                        <small class="text-muted">Customers who have placed more orders than the average customer</small>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Customer ID</th>
                                        <th>Name</th>
                                        <th>Total Orders</th>
                                        <th>Total Spent</th>
                                        <th>Current Wallet Balance</th>
                                        <th>Avg per Order</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    if ($nested_result->num_rows > 0):
                                        while ($row = $nested_result->fetch_assoc()): 
                                            $avg_per_order = $row['Total_Spent'] / $row['Total_Orders'];
                                    ?>
                                    <tr>
                                        <td><strong>#<?php echo $row['Customer_ID']; ?></strong></td>
                                        <td>
                                            <i class="bi bi-person-circle text-primary"></i>
                                            <?php echo $row['Name']; ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-info"><?php echo $row['Total_Orders']; ?> orders</span>
                                        </td>
                                        <td class="text-success fw-bold">₹<?php echo number_format($row['Total_Spent'], 2); ?></td>
                                        <td>₹<?php echo number_format($row['Wallet_Bal'], 2); ?></td>
                                        <td>₹<?php echo number_format($avg_per_order, 2); ?></td>
                                    </tr>
                                    <?php 
                                        endwhile;
                                    else:
                                    ?>
                                    <tr>
                                        <td colspan="6" class="text-center text-muted">No data available</td>
                                    </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="alert alert-info mt-3">
                            <strong>Query Type:</strong> Nested Subquery with HAVING clause<br>
                            <strong>Complexity:</strong> Uses AVG() in subquery to filter customers above average
                        </div>
                    </div>
                </div>
                
                <!-- JOIN QUERY REPORT -->
                <div class="card report-card">
                    <div class="card-header bg-white">
                        <span class="query-badge join-badge">
                            <i class="bi bi-diagram-3"></i> JOIN QUERY
                        </span>
                        <h5 class="mt-2 mb-0">Complete Order Details</h5>
                        <small class="text-muted">Multi-table join showing orders with customer and item information</small>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead class="table-light">
                                    <tr>
                                        <th>Order ID</th>
                                        <th>Date & Time</th>
                                        <th>Customer</th>
                                        <th>Items Ordered</th>
                                        <th>Qty</th>
                                        <th>Status</th>
                                        <th>Total Amount</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($row = $join_result->fetch_assoc()): ?>
                                    <tr>
                                        <td><strong>#<?php echo $row['Order_ID']; ?></strong></td>
                                        <td><?php echo date('d M Y, h:i A', strtotime($row['Date_Time'])); ?></td>
                                        <td>
                                            <i class="bi bi-person-badge text-primary"></i>
                                            <?php echo $row['Customer_Name']; ?>
                                        </td>
                                        <td><small><?php echo $row['Items']; ?></small></td>
                                        <td><?php echo $row['Total_Items']; ?></td>
                                        <td>
                                            <?php 
                                            $badge_class = [
                                                'Pending' => 'warning',
                                                'Preparing' => 'info',
                                                'Delivered' => 'success',
                                                'Cancelled' => 'danger'
                                            ];
                                            $class = $badge_class[$row['Status']] ?? 'secondary';
                                            ?>
                                            <span class="badge bg-<?php echo $class; ?>"><?php echo $row['Status']; ?></span>
                                        </td>
                                        <td class="fw-bold text-success">₹<?php echo number_format($row['Order_Total'], 2); ?></td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="alert alert-info mt-3">
                            <strong>Query Type:</strong> Multiple INNER JOINs with GROUP_CONCAT<br>
                            <strong>Tables Joined:</strong> ORDER → CUSTOMER → ORDER_DETAIL → MENU_ITEMS (4 tables)
                        </div>
                    </div>
                </div>
                
                <!-- AGGREGATE QUERY REPORT -->
                <div class="card report-card">
                    <div class="card-header bg-white">
                        <span class="query-badge aggregate-badge">
                            <i class="bi bi-calculator"></i> AGGREGATE QUERY
                        </span>
                        <h5 class="mt-2 mb-0">Sales Analysis by Category</h5>
                        <small class="text-muted">Revenue and performance metrics using GROUP BY and aggregate functions</small>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead class="table-success">
                                    <tr>
                                        <th>Category</th>
                                        <th>Total Orders</th>
                                        <th>Items Sold</th>
                                        <th>Gross Revenue</th>
                                        <th>Avg Item Value</th>
                                        <th>Max Transaction</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $total_revenue = 0;
                                    $aggregate_result->data_seek(0);
                                    while ($row = $aggregate_result->fetch_assoc()): 
                                        $total_revenue += $row['Gross_Revenue'];
                                    ?>
                                    <tr>
                                        <td>
                                            <strong>
                                                <i class="bi bi-tag-fill text-primary"></i>
                                                <?php echo $row['Category']; ?>
                                            </strong>
                                        </td>
                                        <td><?php echo $row['Total_Orders']; ?></td>
                                        <td><span class="badge bg-primary"><?php echo $row['Items_Sold']; ?></span></td>
                                        <td class="text-success fw-bold">₹<?php echo number_format($row['Gross_Revenue'], 2); ?></td>
                                        <td>₹<?php echo number_format($row['Avg_Item_Value'], 2); ?></td>
                                        <td>₹<?php echo number_format($row['Max_Transaction'], 2); ?></td>
                                    </tr>
                                    <?php endwhile; ?>
                                    <tr class="table-info fw-bold">
                                        <td colspan="3" class="text-end">TOTAL REVENUE:</td>
                                        <td colspan="3" class="text-success">₹<?php echo number_format($total_revenue, 2); ?></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="alert alert-info mt-3">
                            <strong>Query Type:</strong> Aggregate with GROUP BY<br>
                            <strong>Functions Used:</strong> COUNT(), SUM(), AVG(), MAX()<br>
                            <strong>Purpose:</strong> Business intelligence for category performance
                        </div>
                    </div>
                </div>
                
                <!-- Additional Analytics -->
                <div class="row">
                    <!-- Peak Hours -->
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header bg-warning text-white">
                                <h6 class="mb-0"><i class="bi bi-clock"></i> Peak Hours Analysis</h6>
                            </div>
                            <div class="card-body">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Hour</th>
                                            <th>Orders</th>
                                            <th>Revenue</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($row = $peak_hours->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo $row['Hour']; ?>:00</td>
                                            <td><span class="badge bg-info"><?php echo $row['Order_Count']; ?></span></td>
                                            <td>₹<?php echo number_format($row['Revenue'], 2); ?></td>
                                        </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Popular Items -->
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header bg-success text-white">
                                <h6 class="mb-0"><i class="bi bi-star"></i> Top Selling Items</h6>
                            </div>
                            <div class="card-body">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Item</th>
                                            <th>Category</th>
                                            <th>Sold</th>
                                            <th>Revenue</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($row = $popular_items->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo $row['Name']; ?></td>
                                            <td><small><?php echo $row['Category']; ?></small></td>
                                            <td><span class="badge bg-warning"><?php echo $row['Times_Ordered']; ?></span></td>
                                            <td>₹<?php echo number_format($row['Revenue'], 2); ?></td>
                                        </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- SQL Query Display -->
                <div class="card mt-4">
                    <div class="card-header bg-dark text-white">
                        <h6 class="mb-0"><i class="bi bi-code-square"></i> SQL Queries Used</h6>
                    </div>
                    <div class="card-body">
                        <div class="accordion" id="queryAccordion">
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#nestedSQL">
                                        Nested Query SQL
                                    </button>
                                </h2>
                                <div id="nestedSQL" class="accordion-collapse collapse show" data-bs-parent="#queryAccordion">
                                    <div class="accordion-body">
                                        <pre class="bg-light p-3"><code><?php echo htmlspecialchars($nested_query); ?></code></pre>
                                    </div>
                                </div>
                            </div>
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#joinSQL">
                                        Join Query SQL
                                    </button>
                                </h2>
                                <div id="joinSQL" class="accordion-collapse collapse" data-bs-parent="#queryAccordion">
                                    <div class="accordion-body">
                                        <pre class="bg-light p-3"><code><?php echo htmlspecialchars($join_query); ?></code></pre>
                                    </div>
                                </div>
                            </div>
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#aggregateSQL">
                                        Aggregate Query SQL
                                    </button>
                                </h2>
                                <div id="aggregateSQL" class="accordion-collapse collapse" data-bs-parent="#queryAccordion">
                                    <div class="accordion-body">
                                        <pre class="bg-light p-3"><code><?php echo htmlspecialchars($aggregate_query); ?></code></pre>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
