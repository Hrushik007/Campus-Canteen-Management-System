<!-- =====================================================
     FILE: customer/dashboard.php
     Customer Dashboard with Menu & Order Placement
     ===================================================== -->
<?php
require_once '../config.php';
requireRole('customer');

$conn = getDBConnection();

// Get customer info
$customer_id = $_SESSION['user_id'];
$customer = $conn->query("SELECT * FROM CUSTOMER WHERE Customer_ID = $customer_id")->fetch_assoc();

// Update session wallet balance
$_SESSION['wallet_bal'] = $customer['Wallet_Bal'];

// Get menu with offers
$menu_result = $conn->query("
    SELECT 
        mi.Item_ID,
        mi.Name,
        cm.Category,
        mi.Price AS Original_Price,
        IFNULL(o.Discount, 0) AS Discount_Percent,
        Get_Discounted_Price(mi.Item_ID) AS Final_Price,
        o.Description AS Offer_Description
    FROM MENU_ITEMS mi
    JOIN Category_Menu cm ON mi.Item_ID = cm.Item_ID
    LEFT JOIN OFFER o ON mi.Offer_ID = o.Offer_ID 
        AND o.Valid_Time > NOW() 
        AND o.Is_Active = TRUE
    WHERE mi.Is_Active = TRUE
    ORDER BY cm.Category, mi.Name
");

// Get recent orders
$recent_orders = $conn->query("
    SELECT 
        o.Order_ID,
        o.Date_Time,
        o.Status,
        Calculate_Order_Total(o.Order_ID) as Total
    FROM `ORDER` o
    WHERE o.Customer_ID = $customer_id
    ORDER BY o.Date_Time DESC
    LIMIT 5
");

// Handle order placement
$order_message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    $items = $_POST['items'] ?? [];
    $quantities = $_POST['quantities'] ?? [];
    $payment_mode = $_POST['payment_mode'] ?? 'Wallet';
    
    if (count($items) > 0) {
        $item_ids = implode(',', $items);
        $item_quantities = implode(',', $quantities);
        
        $stmt = $conn->prepare("CALL Place_Order(?, ?, ?, ?, @order_id, @total, @status)");
        $stmt->bind_param("isss", $customer_id, $item_ids, $item_quantities, $payment_mode);
        $stmt->execute();
        
        $result = $conn->query("SELECT @order_id as oid, @total as total, @status as status");
        $order_result = $result->fetch_assoc();
        
        if ($order_result['oid']) {
            $order_message = "success|Order placed successfully! Order ID: #" . $order_result['oid'] . " | Total: ₹" . number_format($order_result['total'], 2);
            // Refresh wallet balance
            $customer = $conn->query("SELECT * FROM CUSTOMER WHERE Customer_ID = $customer_id")->fetch_assoc();
            $_SESSION['wallet_bal'] = $customer['Wallet_Bal'];
        } else {
            $order_message = "danger|" . $order_result['status'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Dashboard - CCMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <style>
        body { background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%); }
        .navbar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .menu-card {
            transition: transform 0.3s;
            border: none;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .menu-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.2);
        }
        .discount-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            background: #ff6b6b;
            color: white;
            padding: 5px 10px;
            border-radius: 20px;
            font-weight: bold;
        }
        .wallet-card {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
            color: white;
            border-radius: 15px;
            padding: 20px;
        }
        .cart-item {
            background: #f8f9fa;
            padding: 10px;
            margin: 5px 0;
            border-radius: 5px;
        }
        .category-badge {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
            margin: 5px;
            cursor: pointer;
        }
        .category-badge.active {
            background: #667eea;
            color: white;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-dark">
        <div class="container-fluid">
            <span class="navbar-brand mb-0 h1">
                <i class="bi bi-shop"></i> Campus Canteen
            </span>
            <div class="d-flex align-items-center text-white">
                <span class="me-3">
                    <i class="bi bi-person-circle"></i> <?php echo $_SESSION['name']; ?>
                </span>
                <span class="me-3">
                    <i class="bi bi-wallet2"></i> ₹<?php echo number_format($_SESSION['wallet_bal'], 2); ?>
                </span>
                <a href="wallet.php" class="btn btn-light btn-sm me-2">Top Up</a>
                <a href="../logout.php" class="btn btn-outline-light btn-sm">Logout</a>
            </div>
        </div>
    </nav>
    
    <div class="container mt-4">
        <?php if ($order_message): 
            list($type, $message) = explode('|', $order_message, 2);
        ?>
        <div class="alert alert-<?php echo $type; ?> alert-dismissible fade show">
            <?php echo $message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>
        
        <div class="row">
            <!-- Menu Section -->
            <div class="col-md-8">
                <div class="card mb-4">
                    <div class="card-header bg-white">
                        <h4><i class="bi bi-card-list"></i> Menu</h4>
                        <div id="categoryFilter">
                            <span class="category-badge active" onclick="filterCategory('all')">All</span>
                            <span class="category-badge" onclick="filterCategory('Breakfast')">Breakfast</span>
                            <span class="category-badge" onclick="filterCategory('Main Course')">Main Course</span>
                            <span class="category-badge" onclick="filterCategory('Snacks')">Snacks</span>
                            <span class="category-badge" onclick="filterCategory('Beverages')">Beverages</span>
                            <span class="category-badge" onclick="filterCategory('Desserts')">Desserts</span>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row" id="menuContainer">
                            <?php while ($item = $menu_result->fetch_assoc()): ?>
                            <div class="col-md-6 mb-3 menu-item" data-category="<?php echo $item['Category']; ?>">
                                <div class="card menu-card position-relative">
                                    <?php if ($item['Discount_Percent'] > 0): ?>
                                    <div class="discount-badge"><?php echo $item['Discount_Percent']; ?>% OFF</div>
                                    <?php endif; ?>
                                    <div class="card-body">
                                        <h5 class="card-title"><?php echo $item['Name']; ?></h5>
                                        <span class="badge bg-info"><?php echo $item['Category']; ?></span>
                                        <div class="mt-2">
                                            <?php if ($item['Discount_Percent'] > 0): ?>
                                            <span class="text-muted text-decoration-line-through">
                                                ₹<?php echo number_format($item['Original_Price'], 2); ?>
                                            </span>
                                            <?php endif; ?>
                                            <span class="h5 text-success ms-2">
                                                ₹<?php echo number_format($item['Final_Price'], 2); ?>
                                            </span>
                                        </div>
                                        <?php if ($item['Offer_Description']): ?>
                                        <small class="text-primary"><i class="bi bi-tag"></i> <?php echo $item['Offer_Description']; ?></small>
                                        <?php endif; ?>
                                        <div class="mt-3">
                                            <button class="btn btn-primary btn-sm" onclick="addToCart(<?php echo $item['Item_ID']; ?>, '<?php echo addslashes($item['Name']); ?>', <?php echo $item['Final_Price']; ?>)">
                                                <i class="bi bi-cart-plus"></i> Add to Cart
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endwhile; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Recent Orders -->
                <div class="card">
                    <div class="card-header bg-white">
                        <h5><i class="bi bi-clock-history"></i> Recent Orders</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Order ID</th>
                                        <th>Date & Time</th>
                                        <th>Status</th>
                                        <th>Total</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($order = $recent_orders->fetch_assoc()): ?>
                                    <tr>
                                        <td>#<?php echo $order['Order_ID']; ?></td>
                                        <td><?php echo date('d M Y, h:i A', strtotime($order['Date_Time'])); ?></td>
                                        <td>
                                            <?php 
                                            $badge = ['Pending'=>'warning', 'Preparing'=>'info', 'Delivered'=>'success', 'Cancelled'=>'danger'];
                                            ?>
                                            <span class="badge bg-<?php echo $badge[$order['Status']]; ?>">
                                                <?php echo $order['Status']; ?>
                                            </span>
                                        </td>
                                        <td>₹<?php echo number_format($order['Total'], 2); ?></td>
                                        <td>
                                            <a href="order_details.php?id=<?php echo $order['Order_ID']; ?>" class="btn btn-sm btn-outline-primary">
                                                View
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Cart Section -->
            <div class="col-md-4">
                <div class="wallet-card mb-3">
                    <h5><i class="bi bi-wallet2"></i> Wallet Balance</h5>
                    <h2>₹<?php echo number_format($customer['Wallet_Bal'], 2); ?></h2>
                    <a href="wallet.php" class="btn btn-light btn-sm">Top Up Wallet</a>
                </div>
                
                <div class="card sticky-top" style="top: 20px;">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="bi bi-cart"></i> Your Cart (<span id="cartCount">0</span>)</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div id="cartItems">
                                <p class="text-muted text-center">Cart is empty</p>
                            </div>
                            
                            <hr>
                            
                            <div class="d-flex justify-content-between mb-3">
                                <strong>Total:</strong>
                                <strong class="text-success" id="cartTotal">₹0.00</strong>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Payment Method</label>
                                <select name="payment_mode" class="form-select" required>
                                    <option value="Wallet">Wallet</option>
                                    <option value="UPI">UPI</option>
                                    <option value="Card">Card</option>
                                </select>
                            </div>
                            
                            <button type="submit" name="place_order" class="btn btn-success w-100" id="placeOrderBtn" disabled>
                                <i class="bi bi-check-circle"></i> Place Order
                            </button>
                            <button type="button" class="btn btn-outline-danger w-100 mt-2" onclick="clearCart()">
                                <i class="bi bi-trash"></i> Clear Cart
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let cart = [];
        
        function addToCart(itemId, itemName, price) {
            const existingItem = cart.find(item => item.id === itemId);
            if (existingItem) {
                existingItem.quantity++;
            } else {
                cart.push({ id: itemId, name: itemName, price: price, quantity: 1 });
            }
            updateCart();
        }
        
        function removeFromCart(itemId) {
            cart = cart.filter(item => item.id !== itemId);
            updateCart();
        }
        
        function updateQuantity(itemId, change) {
            const item = cart.find(item => item.id === itemId);
            if (item) {
                item.quantity += change;
                if (item.quantity <= 0) {
                    removeFromCart(itemId);
                } else {
                    updateCart();
                }
            }
        }
        
        function updateCart() {
            const cartItemsDiv = document.getElementById('cartItems');
            const cartCount = document.getElementById('cartCount');
            const cartTotal = document.getElementById('cartTotal');
            const placeOrderBtn = document.getElementById('placeOrderBtn');
            
            if (cart.length === 0) {
                cartItemsDiv.innerHTML = '<p class="text-muted text-center">Cart is empty</p>';
                cartCount.textContent = '0';
                cartTotal.textContent = '₹0.00';
                placeOrderBtn.disabled = true;
            } else {
                let html = '';
                let total = 0;
                let itemIds = [];
                let quantities = [];
                
                cart.forEach(item => {
                    const itemTotal = item.price * item.quantity;
                    total += itemTotal;
                    itemIds.push(item.id);
                    quantities.push(item.quantity);
                    
                    html += `
                        <div class="cart-item">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <strong>${item.name}</strong><br>
                                    <small>₹${item.price.toFixed(2)} × ${item.quantity}</small>
                                </div>
                                <div>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="updateQuantity(${item.id}, -1)">-</button>
                                    <span class="mx-2">${item.quantity}</span>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="updateQuantity(${item.id}, 1)">+</button>
                                    <button type="button" class="btn btn-sm btn-danger ms-2" onclick="removeFromCart(${item.id})">×</button>
                                </div>
                            </div>
                            <input type="hidden" name="items[]" value="${item.id}">
                            <input type="hidden" name="quantities[]" value="${item.quantity}">
                        </div>
                    `;
                });
                
                cartItemsDiv.innerHTML = html;
                cartCount.textContent = cart.length;
                cartTotal.textContent = '₹' + total.toFixed(2);
                placeOrderBtn.disabled = false;
            }
        }
        
        function clearCart() {
            if (confirm('Clear all items from cart?')) {
                cart = [];
                updateCart();
            }
        }
        
        function filterCategory(category) {
            const items = document.querySelectorAll('.menu-item');
            const badges = document.querySelectorAll('.category-badge');
            
            badges.forEach(badge => badge.classList.remove('active'));
            event.target.classList.add('active');
            
            items.forEach(item => {
                if (category === 'all' || item.dataset.category === category) {
                    item.style.display = 'block';
                } else {
                    item.style.display = 'none';
                }
            });
        }
    </script>
</body>
</html>