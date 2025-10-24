<!-- =====================================================
     FILE: login.php
     Login Page with Role-based Authentication
     ===================================================== -->
<?php
require_once 'config.php';

// If already logged in, redirect to dashboard
if (isLoggedIn()) {
    header("Location: dashboard.php");
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? '';
    
    $conn = getDBConnection();
    
    if ($role === 'admin') {
        // Admin login
        $stmt = $conn->prepare("SELECT Admin_ID, Name, Email FROM ADMIN WHERE Email = ? AND Password = ? AND Is_Active = 1");
        $stmt->bind_param("ss", $email, $password);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            $_SESSION['user_id'] = $user['Admin_ID'];
            $_SESSION['name'] = $user['Name'];
            $_SESSION['email'] = $user['Email'];
            $_SESSION['role'] = 'admin';
            header("Location: admin/dashboard.php");
            exit();
        }
    } elseif ($role === 'staff') {
        // Staff login (using email as username for demo)
        $stmt = $conn->prepare("SELECT Staff_ID, Name, Role FROM STAFF WHERE Name = ? AND Is_Active = 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            $_SESSION['user_id'] = $user['Staff_ID'];
            $_SESSION['name'] = $user['Name'];
            $_SESSION['role'] = 'staff';
            $_SESSION['staff_role'] = $user['Role'];
            header("Location: staff/dashboard.php");
            exit();
        }
    } elseif ($role === 'customer') {
        // Customer login (using name for demo)
        $stmt = $conn->prepare("SELECT Customer_ID, Name, Wallet_Bal FROM CUSTOMER WHERE Name = ? AND Is_Active = 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            $_SESSION['user_id'] = $user['Customer_ID'];
            $_SESSION['name'] = $user['Name'];
            $_SESSION['wallet_bal'] = $user['Wallet_Bal'];
            $_SESSION['role'] = 'customer';
            header("Location: customer/dashboard.php");
            exit();
        }
    }
    
    $error = "Invalid credentials or inactive account";
    $stmt->close();
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Campus Canteen Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
        }
        .login-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            overflow: hidden;
        }
        .login-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .login-body {
            padding: 40px;
        }
        .role-btn {
            margin: 5px;
            border-radius: 25px;
        }
        .role-btn.active {
            background: #667eea;
            color: white;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="login-container">
                    <div class="login-header">
                        <i class="bi bi-shop" style="font-size: 3rem;"></i>
                        <h2 class="mt-3">Campus Canteen Management</h2>
                        <p class="mb-0">Please login to continue</p>
                    </div>
                    <div class="login-body">
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>
                        
                        <form method="POST" action="">
                            <div class="mb-4">
                                <label class="form-label fw-bold">Select Role</label>
                                <div class="d-flex justify-content-center">
                                    <button type="button" class="btn role-btn active" onclick="selectRole('admin')">
                                        <i class="bi bi-person-badge"></i> Admin
                                    </button>
                                    <button type="button" class="btn role-btn" onclick="selectRole('staff')">
                                        <i class="bi bi-person"></i> Staff
                                    </button>
                                    <button type="button" class="btn role-btn" onclick="selectRole('customer')">
                                        <i class="bi bi-person-circle"></i> Customer
                                    </button>
                                </div>
                                <input type="hidden" name="role" id="role" value="admin">
                            </div>
                            
                            <div class="mb-3">
                                <label for="email" class="form-label">Email/Username</label>
                                <input type="text" class="form-control" id="email" name="email" required>
                                <small class="text-muted" id="loginHint">Admin: rajesh.kumar@campus.edu</small>
                            </div>
                            
                            <div class="mb-3" id="passwordField">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                                <small class="text-muted">Admin password: admin123</small>
                            </div>
                            
                            <button type="submit" class="btn btn-primary w-100 py-2">
                                <i class="bi bi-box-arrow-in-right"></i> Login
                            </button>
                        </form>
                        
                        <div class="mt-4 text-center">
                            <h6>Demo Credentials:</h6>
                            <small class="d-block">Admin: rajesh.kumar@campus.edu / admin123</small>
                            <small class="d-block">Staff: Ramesh Chef / (no password)</small>
                            <small class="d-block">Customer: Arjun Reddy / (no password)</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        function selectRole(role) {
            document.getElementById('role').value = role;
            document.querySelectorAll('.role-btn').forEach(btn => btn.classList.remove('active'));
            event.target.closest('.role-btn').classList.add('active');
            
            const hints = {
                'admin': 'Admin: rajesh.kumar@campus.edu',
                'staff': 'Staff: Ramesh Chef',
                'customer': 'Customer: Arjun Reddy'
            };
            document.getElementById('loginHint').textContent = hints[role];
            
            if (role === 'admin') {
                document.getElementById('passwordField').style.display = 'block';
                document.getElementById('password').required = true;
            } else {
                document.getElementById('passwordField').style.display = 'none';
                document.getElementById('password').required = false;
            }
        }
    </script>
</body>
</html>