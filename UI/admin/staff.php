<!-- =====================================================
     FILE: admin/staff.php
     Staff Management with CRUD Operations
     ===================================================== -->
<?php
require_once '../config.php';
requireRole('admin');

$conn = getDBConnection();
$message = '';

// Handle Create/Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_staff'])) {
    $staff_id = $_POST['staff_id'] ?? null;
    $name = $_POST['name'];
    $role = $_POST['role'];
    $salary = $_POST['salary'];
    $shift = $_POST['shift'];
    
    if ($staff_id) {
        // Update
        $stmt = $conn->prepare("UPDATE STAFF SET Name=?, Role=?, Salary=? WHERE Staff_ID=?");
        $stmt->bind_param("ssdi", $name, $role, $salary, $staff_id);
        $stmt->execute();
        
        // Update shift
        $conn->query("DELETE FROM Shift_Staff WHERE Staff_ID=$staff_id");
        $stmt = $conn->prepare("INSERT INTO Shift_Staff (Shift, Staff_ID) VALUES (?, ?)");
        $stmt->bind_param("si", $shift, $staff_id);
        $stmt->execute();
        
        $message = "Staff updated successfully";
    } else {
        // Insert
        $stmt = $conn->prepare("INSERT INTO STAFF (Name, Role, Salary) VALUES (?, ?, ?)");
        $stmt->bind_param("ssd", $name, $role, $salary);
        $stmt->execute();
        
        $new_id = $conn->insert_id;
        
        // Add shift
        $stmt = $conn->prepare("INSERT INTO Shift_Staff (Shift, Staff_ID) VALUES (?, ?)");
        $stmt->bind_param("si", $shift, $new_id);
        $stmt->execute();
        
        $message = "Staff added successfully";
    }
}

// Handle Delete
if (isset($_GET['delete'])) {
    $staff_id = $_GET['delete'];
    $stmt = $conn->prepare("UPDATE STAFF SET Is_Active = 0 WHERE Staff_ID = ?");
    $stmt->bind_param("i", $staff_id);
    if ($stmt->execute()) {
        $message = "Staff deactivated successfully";
    }
}

// Get all staff
$staff_list = $conn->query("
    SELECT 
        s.Staff_ID,
        s.Name,
        s.Role,
        s.Salary,
        s.Is_Active,
        GROUP_CONCAT(ss.Shift) AS Shifts
    FROM STAFF s
    LEFT JOIN Shift_Staff ss ON s.Staff_ID = ss.Staff_ID
    GROUP BY s.Staff_ID, s.Name, s.Role, s.Salary, s.Is_Active
    ORDER BY s.Name
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Management - CCMS</title>
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
                    <a href="staff.php" class="active"><i class="bi bi-person-badge"></i> Staff</a>
                    <a href="offers.php"><i class="bi bi-tag"></i> Offers</a>
                    <a href="reports.php"><i class="bi bi-bar-chart"></i> Reports</a>
                    <a href="../logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a>
                </nav>
            </div>
            
            <!-- Main Content -->
            <div class="col-md-10 p-4">
                <h2 class="mb-4"><i class="bi bi-person-badge"></i> Staff Management</h2>
                
                <?php if ($message): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <?php echo $message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>
                
                <button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#staffModal">
                    <i class="bi bi-plus-circle"></i> Add New Staff
                </button>
                
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>ID</th>
                                        <th>Name</th>
                                        <th>Role</th>
                                        <th>Shifts</th>
                                        <th>Salary</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($staff = $staff_list->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo $staff['Staff_ID']; ?></td>
                                        <td>
                                            <i class="bi bi-person text-primary"></i>
                                            <?php echo $staff['Name']; ?>
                                        </td>
                                        <td><span class="badge bg-info"><?php echo $staff['Role']; ?></span></td>
                                        <td><?php echo $staff['Shifts'] ?? 'Not assigned'; ?></td>
                                        <td>₹<?php echo number_format($staff['Salary'], 2); ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo $staff['Is_Active'] ? 'success' : 'danger'; ?>">
                                                <?php echo $staff['Is_Active'] ? 'Active' : 'Inactive'; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-warning" 
                                                    onclick='editStaff(<?php echo json_encode($staff); ?>)'>
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <?php if ($staff['Is_Active']): ?>
                                            <a href="?delete=<?php echo $staff['Staff_ID']; ?>" 
                                               class="btn btn-sm btn-danger"
                                               onclick="return confirm('Deactivate this staff member?')">
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
    
    <!-- Staff Modal -->
    <div class="modal fade" id="staffModal">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title">Add/Edit Staff Member</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="staff_id" id="staff_id">
                        <div class="mb-3">
                            <label class="form-label">Name</label>
                            <input type="text" class="form-control" name="name" id="name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Role</label>
                            <select class="form-control" name="role" id="role" required>
                                <option value="Chef">Chef</option>
                                <option value="Assistant Chef">Assistant Chef</option>
                                <option value="Counter Staff">Counter Staff</option>
                                <option value="Helper">Helper</option>
                                <option value="Cashier">Cashier</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Shift</label>
                            <select class="form-control" name="shift" id="shift" required>
                                <option value="Morning">Morning</option>
                                <option value="Afternoon">Afternoon</option>
                                <option value="Evening">Evening</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Salary</label>
                            <input type="number" step="0.01" class="form-control" name="salary" id="salary" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" name="save_staff" class="btn btn-primary">Save Staff</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editStaff(staff) {
            document.getElementById('staff_id').value = staff.Staff_ID;
            document.getElementById('name').value = staff.Name;
            document.getElementById('role').value = staff.Role;
            document.getElementById('salary').value = staff.Salary;
            if (staff.Shifts) {
                document.getElementById('shift').value = staff.Shifts.split(',')[0];
            }
            new bootstrap.Modal(document.getElementById('staffModal')).show();
        }
    </script>
</body>
</html>
