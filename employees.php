<?php
// employees.php
require_once 'config.php';
requireLogin();

$success = '';

// Handle delete with CSRF protection
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_id'])) {
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $error = 'Invalid request.';
    } else {
        $id = intval($_POST['delete_id']);
        $stmt = $conn->prepare("DELETE FROM employees WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            logActivity($_SESSION['user_id'], 'Delete Employee', "Deleted employee ID: $id");
            $success = "Employee deleted successfully!";
        }
        $stmt->close();
    }
}

// Pagination settings
$per_page = 10;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $per_page;

// Get total count
$total_result = $conn->query("SELECT COUNT(*) as total FROM employees");
$total_rows = $total_result->fetch_assoc()['total'];
$total_pages = max(1, ceil($total_rows / $per_page));
$page = min($page, $total_pages);
$offset = ($page - 1) * $per_page;

// Get employees ordered ascending (1, 2, 3...)
$stmt = $conn->prepare("SELECT * FROM employees ORDER BY id ASC LIMIT ? OFFSET ?");
$stmt->bind_param("ii", $per_page, $offset);
$stmt->execute();
$result = $stmt->get_result();

$csrf_token = generateCSRFToken();
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
        body { font-family: 'Futura', 'Helvetica Neue', Arial, sans-serif; background: #f5f5f5; display: flex; }

        /* Sidebar */
        .sidebar { width: 280px; background: linear-gradient(135deg, #d81919 0%, #555555 100%); color: white; height: 100vh; position: fixed; left: 0; top: 0; overflow-y: auto; }
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
        .btn-primary { background: linear-gradient(135deg, #d81919 0%, #a01414 100%); color: white; }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(216,25,25,0.3); }
        .btn-success { background: #4caf50; color: white; }
        .btn-danger { background: #f44336; color: white; }

        /* Table */
        .table-container { background: white; border-radius: 15px; padding: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; padding: 15px 10px; background: #f8f9fa; color: #333; font-weight: 600; border-bottom: 2px solid #e0e0e0; }
        td { padding: 15px 10px; border-bottom: 1px solid #e0e0e0; color: #555555; }
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
        .search-box input:focus { border-color: #d81919; outline: none; }

        /* Pagination */
        .pagination { margin-top: 20px; display: flex; justify-content: center; align-items: center; gap: 5px; }
        .page-btn { padding: 8px 14px; border: 1px solid #e0e0e0; border-radius: 6px; background: white; cursor: pointer; text-decoration: none; color: #333; font-size: 14px; transition: all 0.3s; }
        .page-btn:hover { border-color: #d81919; color: #d81919; }
        .page-btn.active { background: #d81919; color: white; border-color: #d81919; }
        .page-btn.disabled { opacity: 0.5; cursor: not-allowed; pointer-events: none; }
        .page-info { color: #555555; font-size: 14px; margin: 0 10px; }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-header">
            <h2><i class="fas fa-shield-alt"></i> NSIAI</h2>
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
            <a href="reset_password.php" class="menu-item">
                <i class="fas fa-key"></i> Reset Password
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
                <a href="import_employees.php" class="btn" style="background:#2196f3;color:white;">
                    <i class="fas fa-file-import"></i> Import
                </a>
                <a href="export_employees.php" class="btn" style="background:#4caf50;color:white;">
                    <i class="fas fa-file-export"></i> Export CSV
                </a>
                <a href="add_employee.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Add Employee
                </a>
                <a href="qr_codes.php" class="btn btn-success">
                    <i class="fas fa-qrcode"></i> Generate QR
                </a>
            </div>
        </div>

        <?php if (!empty($success)): ?>
            <div class="success-message"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <div class="search-box">
            <input type="text" id="searchInput" placeholder="Search employees..." onkeyup="searchTable()">
        </div>

        <div class="table-container">
            <table id="employeesTable">
                <thead>
                    <tr>
                        <th>#</th>
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
                    <?php
                    $row_number = $offset + 1;
                    while ($row = $result->fetch_assoc()):
                    ?>
                    <tr>
                        <td><?php echo $row_number++; ?></td>
                        <td><?php echo htmlspecialchars($row['employee_id']); ?></td>
                        <td><?php 
                            $display_name = $row['full_name'];
                            if (empty($display_name)) {
                                $display_name = trim($row['first_name'] . ' ' . $row['middle_name'] . ' ' . $row['last_name']);
                            }
                            echo htmlspecialchars($display_name); 
                        ?></td>
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
                            <form method="POST" action="" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this employee?')">
                                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                <input type="hidden" name="delete_id" value="<?php echo $row['id']; ?>">
                                <button type="submit" class="action-btn delete" title="Delete">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>

            <?php if ($total_pages > 1): ?>
            <div class="pagination">
                <a href="?page=1" class="page-btn <?php echo $page <= 1 ? 'disabled' : ''; ?>"><i class="fas fa-angle-double-left"></i></a>
                <a href="?page=<?php echo max(1, $page - 1); ?>" class="page-btn <?php echo $page <= 1 ? 'disabled' : ''; ?>"><i class="fas fa-angle-left"></i></a>

                <?php
                $start_page = max(1, $page - 2);
                $end_page = min($total_pages, $page + 2);
                for ($i = $start_page; $i <= $end_page; $i++):
                ?>
                    <a href="?page=<?php echo $i; ?>" class="page-btn <?php echo $i == $page ? 'active' : ''; ?>"><?php echo $i; ?></a>
                <?php endfor; ?>

                <a href="?page=<?php echo min($total_pages, $page + 1); ?>" class="page-btn <?php echo $page >= $total_pages ? 'disabled' : ''; ?>"><i class="fas fa-angle-right"></i></a>
                <a href="?page=<?php echo $total_pages; ?>" class="page-btn <?php echo $page >= $total_pages ? 'disabled' : ''; ?>"><i class="fas fa-angle-double-right"></i></a>

                <span class="page-info">Page <?php echo $page; ?> of <?php echo $total_pages; ?> (<?php echo $total_rows; ?> records)</span>
            </div>
            <?php endif; ?>
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
