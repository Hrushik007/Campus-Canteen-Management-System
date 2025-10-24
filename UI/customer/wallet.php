<!-- =====================================================
     FILE: customer/wallet.php
     Wallet Top-up Page
     ===================================================== -->
<?php
require_once '../config.php';
requireRole('customer');

$conn = getDBConnection();
$customer_id = $_SESSION['user_id'];
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $amount = $_POST['amount'];
    
    $stmt = $conn->prepare("CALL TopUp_Wallet(?, ?, @new_balance, @status)");
    $stmt->bind_param("id", $customer_id, $amount);
    $stmt->execute();
    
    $result = $conn->query("SELECT @new_balance as balance, @status as status");
    $topup_result = $result->fetch_assoc();
    
    if ($topup_result['balance']) {
        $_SESSION['wallet_bal'] = $topup_result['balance'];
        $message = "success|Wallet topped up successfully! New balance: ₹" . number_format($topup_result['balance'], 2);
    } else {
        $message = "danger|" . $topup_result['status'];
    }
}

$customer = $conn->query("SELECT * FROM CUSTOMER WHERE Customer_ID = $customer_id")->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wallet - CCMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <style>
        body { background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%); min-height: 100vh; }
        .wallet-container { max-width: 600px; margin: 50px auto; }
        .balance-card {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
            color: white;
            border-radius: 20px;
            padding: 40px;
            text-align: center;
        }
        .amount-btn {
            width: 100%;
            margin: 5px 0;
        }
    </style>
</head>
<body>
    <div class="container wallet-container">
        <div class="text-center mb-4">
            <a href="dashboard.php" class="btn btn-outline-primary">
                <i class="bi bi-arrow-left"></i> Back to Dashboard
            </a>
        </div>
        
        <?php if ($message): 
            list($type, $msg) = explode('|', $message, 2);
        ?>
        <div class="alert alert-<?php echo $type; ?> alert-dismissible fade show">
            <?php echo $msg; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>
        
        <div class="balance-card mb-4">
            <h3><i class="bi bi-wallet2"></i> Current Balance</h3>
            <h1 class="display-3">₹<?php echo number_format($customer['Wallet_Bal'], 2); ?></h1>
            <p class="mb-0"><?php echo $customer['Name']; ?></p>
        </div>
        
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="bi bi-plus-circle"></i> Top Up Wallet</h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">Enter Amount</label>
                        <input type="number" name="amount" class="form-control form-control-lg" 
                               min="1" step="0.01" required placeholder="Enter amount">
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-4">
                            <button type="button" class="btn btn-outline-primary amount-btn" onclick="setAmount(100)">
                                ₹100
                            </button>
                        </div>
                        <div class="col-4">
                            <button type="button" class="btn btn-outline-primary amount-btn" onclick="setAmount(500)">
                                ₹500
                            </button>
                        </div>
                        <div class="col-4">
                            <button type="button" class="btn btn-outline-primary amount-btn" onclick="setAmount(1000)">
                                ₹1000
                            </button>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-success w-100 btn-lg">
                        <i class="bi bi-check-circle"></i> Add Money
                    </button>
                </form>
            </div>
        </div>
    </div>
    
    <script>
        function setAmount(amount) {
            document.querySelector('input[name="amount"]').value = amount;
        }
    </script>
</body>
</html>
