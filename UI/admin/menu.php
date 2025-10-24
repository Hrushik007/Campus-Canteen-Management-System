<!-- =====================================================
     FILE: admin/menu.php
     Menu Management - CRUD Operations
     ===================================================== -->
<?php
require_once '../config.php';
requireRole('admin');

$conn = getDBConnection();
$message = '';

// Handle Create/Update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $item_id = $_POST['item_id'] ?? null;
    $name = $_POST['name'];
    $price = $_POST['price'];
    $category = $_POST['category'];
    $offer_id = $_POST['offer_id'] ?: null;
    
    $stmt = $conn->prepare("CALL Manage_Menu_Item(?, ?, ?, ?, ?, @status_msg)");
    $stmt->bind_param("isdsi", $item_id, $name, $price, $category, $offer_id);
    $stmt->execute();
    
    $result = $conn->query("SELECT @status_msg as msg");
    $message = $result->fetch_assoc()['msg'];
}

// Handle Delete
if (isset($_GET['delete'])) {
    $item_id = $_GET['delete'];
    $stmt = $conn->prepare("UPDATE MENU_ITEMS SET Is_Active = 0 WHERE Item_ID = ?");
    $stmt->bind_param("i", $item_id);
    if ($stmt->execute()) {
        $message = "Item deleted successfully";
    }
}

// Get menu items
$menu_items = $conn->query("
    SELECT mi.Item_ID, mi.Name, mi.Price, cm.Category, 
           mi.Offer_ID, o.Discount, mi.Is_Active
    FROM MENU_ITEMS mi
    LEFT JOIN Category_Menu cm ON mi.Item_ID = cm.Item_ID
    LEFT JOIN OFFER o ON mi.Offer_ID = o.Offer_ID
    ORDER BY cm.Category, mi.Name
");

// Get offers for dropdown
$offers = $conn->query("SELECT Offer_ID, Description, Discount FROM OFFER WHERE Is_Active = 1");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menu Management - CCMS</title>
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
        .sidebar a:hover { background: rgba(255,255,255,0.2); color: white; }
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
                    <a href="menu.php" class="active"><i class="bi bi-card-list"></i> Manage Menu</a>
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
                <h2 class="mb-4">Menu Management</h2>
                
                <?php if ($message): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <?php echo $message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <!-- Add New Item Button -->
                <button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#itemModal">
                    <i class="bi bi-plus-circle"></i> Add New Item
                </button>
                
                <!-- Menu Items Table -->
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Name</th>
                                        <th>Category</th>
                                        <th>Price</th>
                                        <th>Discount</th>
                                        <th>Final Price</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($item = $menu_items->fetch_assoc()): 
                                        $discount = $item['Discount'] ?? 0;
                                        $final_price = $item['Price'] * (1 - $discount / 100);
                                    ?>
                                    <tr>
                                        <td><?php echo $item['Item_ID']; ?></td>
                                        <td><?php echo $item['Name']; ?></td>
                                        <td><span class="badge bg-info"><?php echo $item['Category']; ?></span></td>
                                        <td>₹<?php echo number_format($item['Price'], 2); ?></td>
                                        <td><?php echo $discount > 0 ? $discount . '%' : '-'; ?></td>
                                        <td>₹<?php echo number_format($final_price, 2); ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo $item['Is_Active'] ? 'success' : 'danger'; ?>">
                                                <?php echo $item['Is_Active'] ? 'Active' : 'Inactive'; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-warning" onclick="editItem(<?php echo htmlspecialchars(json_encode($item)); ?>)">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <?php if ($item['Is_Active']): ?>
                                            <a href="?delete=<?php echo $item['Item_ID']; ?>" class="btn btn-sm btn-danger" 
                                               onclick="return confirm('Delete this item?')">
                                                <i class="bi bi-trash"></i>
                                            </a>
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
    
    <!-- Add/Edit Modal -->
    <div class="modal fade" id="itemModal">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title">Add/Edit Menu Item</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="item_id" id="item_id">
                        <div class="mb-3">
                            <label class="form-label">Item Name</label>
                            <input type="text" class="form-control" name="name" id="name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Price</label>
                            <input type="number" step="0.01" class="form-control" name="price" id="price" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Category</label>
                            <select class="form-control" name="category" id="category" required>
                                <option value="Breakfast">Breakfast</option>
                                <option value="Main Course">Main Course</option>
                                <option value="Snacks">Snacks</option>
                                <option value="Beverages">Beverages</option>
                                <option value="Desserts">Desserts</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Offer (Optional)</label>
                            <select class="form-control" name="offer_id" id="offer_id">
                                <option value="">No Offer</option>
                                <?php 
                                $offers->data_seek(0);
                                while ($offer = $offers->fetch_assoc()): 
                                ?>
                                <option value="<?php echo $offer['Offer_ID']; ?>">
                                    <?php echo $offer['Description'] . ' (' . $offer['Discount'] . '%)'; ?>
                                </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Save Item</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editItem(item) {
            document.getElementById('item_id').value = item.Item_ID;
            document.getElementById('name').value = item.Name;
            document.getElementById('price').value = item.Price;
            document.getElementById('category').value = item.Category;
            document.getElementById('offer_id').value = item.Offer_ID || '';
            new bootstrap.Modal(document.getElementById('itemModal')).show();
        }
    </script>
</body>
</html>
