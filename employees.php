<?php
// employees.php
require_once 'config.php';
requireLogin();

// Handle delete
if (isset($_GET['delete'])) {
    $id = sanitize($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM employees WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        logActivity($_SESSION['user_id'], 'Delete Employee', "Deleted employee ID: $id");
        $success = "Employee deleted successfully!";
    }
    $stmt->close();
}

// Get all employees
$result = $conn->query("SELECT * FROM employees ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employees - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f5f5; display: flex; }
        
        /* Sidebar (same as dashboard) */
        .sidebar { width: 280px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; height: 100vh; position: fixed; left: 0; top: 0; overflow-y: auto; }
        .sidebar-header { padding: 30px 20px; text-align: center; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .sidebar-header h2 { font-size: 24px; margin-bottom: 5px; }
        .sidebar-header p { font-size: 14px; opacity: 0.8; }
        .sidebar-menu { padding: 20px 0; }
        .menu-item { padding: 15px 25px; display: flex; align-items: center; color: white; text-decoration: none; transition: all 0.3s; }
        .menu-item i { width: 25px; margin-right: 10px; }
        .menu-item:hover, .menu-item.active { background: rgba(255,255,255,0.2); }
        .menu-item.logout { position: absolute; bottom: 20px; width: 100%; border-top: 1px solid rgba(255,255,255,0.1); }
        
        /* Main Content */
        .main-content { margin-left: 280px; padding: 30px; width: calc(100% - 280px); }
        .header { background: white; padding: 20px 30px; border-radius: 15px; margin-bottom: 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); display: flex; justify-content: space-between; align-items: center; }
        .header h1 { color: #333; font-size: 24px; }
        .header .actions { display: flex; gap: 15px; }
        .btn { padding: 10px 20px; border: none; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 5px; transition: all 0.3s; }
        .btn-primary { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(0,0,0,0.2); }
        .btn-success { background: #4caf50; color: white; }
        .btn-danger { background: #f44336; color: white; }
        .btn-warning { background: #ff9800; color: white; }
        
        /* Table */
        .table-container { background: white; border-radius: 15px; padding: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; padding: 15px 10px; background: #f8f9fa; color: #333; font-weight: 600; border-bottom: 2px solid #e0e0e0; }
        td { padding: 15px 10px; border-bottom: 1px solid #e0e0e0; color: #666; }
        tr:hover { background: #f8f9fa; }
        .action-buttons { display: flex; gap: 5px; }
        .action-btn { padding: 5px 10px; border: none; border-radius: 4px; cursor: pointer; font-size: 12px; text-decoration: none; color: white; }
        .action-btn.view { background: #2196f3; }
        .action-btn.edit { background: #ff9800; }
        .action-btn.delete { background: #f44336; }
        .action-btn.qr { background: #9c27b0; }
        .success-message { background: #4caf50; color: white; padding: 15px; border-radius: 8px; margin-bottom: 20px; }
        .search-box { margin-bottom: 20px; }
        .search-box input { width: 100%; padding: 12px; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 16px; }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-header">
            <h2>📱 QR Directory</h2>
            <p>Employee Management System</p>
        </div>
        <div class="sidebar-menu">
            <a href="dashboard.php" class="menu-item">
                <i class="fas fa-home"></i> Dashboard
            </a>
            <a href="employees.php" class="menu-item active">
                <i class="fas fa-users"></i> Employees
            </a>
            <a href="add_employee.php" class="menu-item">
                <i class="fas fa-user-plus"></i> Add Employee
            </a>
            <a href="qr_codes.php" class="menu-item">
                <i class="fas fa-qrcode"></i> QR Codes
            </a>
            <a href="records.php" class="menu-item">
                <i class="fas fa-table"></i> Records
            </a>
            <a href="logout.php" class="menu-item logout">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </div>

    <div class="main-content">
        <div class="header">
            <h1>Employees</h1>
            <div class="actions">
                <a href="add_employee.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Add Employee
                </a>
                <a href="qr_codes.php" class="btn btn-success">
                    <i class="fas fa-qrcode"></i> Generate QR
                </a>
            </div>
        </div>

        <?php if (isset($success)): ?>
            <div class="success-message"><?php echo $success; ?></div>
        <?php endif; ?>

        <div class="search-box">
            <input type="text" id="searchInput" placeholder="Search employees..." onkeyup="searchTable()">
        </div>

        <div class="table-container">
            <table id="employeesTable">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Employee ID</th>
                        <th>Full Name</th>
                        <th>Position</th>
                        <th>Department</th>
                        <th>Email</th>
                        <th>Mobile</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $row['id']; ?></td>
                        <td><?php echo htmlspecialchars($row['employee_id']); ?></td>
                        <td><?php echo htmlspecialchars($row['full_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['position']); ?></td>
                        <td><?php echo htmlspecialchars($row['department']); ?></td>
                        <td><?php echo htmlspecialchars($row['email']); ?></td>
                        <td><?php echo htmlspecialchars($row['mobile_number']); ?></td>
                        <td class="action-buttons">
                            <a href="view_employee.php?id=<?php echo $row['id']; ?>" class="action-btn view" title="View">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="edit_employee.php?id=<?php echo $row['id']; ?>" class="action-btn edit" title="Edit">
                                <i class="fas fa-edit"></i>
                            </a>
                            <a href="generate_qr.php?id=<?php echo $row['id']; ?>" class="action-btn qr" title="QR Code">
                                <i class="fas fa-qrcode"></i>
                            </a>
                            <a href="?delete=<?php echo $row['id']; ?>" class="action-btn delete" title="Delete" onclick="return confirm('Are you sure you want to delete this employee?')">
                                <i class="fas fa-trash"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        function searchTable() {
            let input = document.getElementById("searchInput").value.toLowerCase();
            let rows = document.getElementById("employeesTable").getElementsByTagName("tr");
            
            for (let i = 1; i < rows.length; i++) {
                let rowData = rows[i].textContent.toLowerCase();
                if (rowData.includes(input)) {
                    rows[i].style.display = "";
                } else {
                    rows[i].style.display = "none";
                }
            }
        }
    </script>
</body>
</html>