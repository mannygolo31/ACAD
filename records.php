<?php
// records.php
require_once 'config.php';
requireLogin();

// Get filter parameters
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$department = isset($_GET['department']) ? sanitize($_GET['department']) : '';
$status = isset($_GET['status']) ? sanitize($_GET['status']) : '';

// Build query
$query = "SELECT * FROM employees WHERE 1=1";
$params = [];
$types = "";

if ($search) {
    $query .= " AND (first_name LIKE ? OR last_name LIKE ? OR employee_id LIKE ? OR email LIKE ?)";
    $searchTerm = "%$search%";
    $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
    $types .= "ssss";
}

if ($department) {
    $query .= " AND department = ?";
    $params[] = $department;
    $types .= "s";
}

if ($status) {
    $query .= " AND employment_status = ?";
    $params[] = $status;
    $types .= "s";
}

$query .= " ORDER BY created_at DESC";

// Get departments for filter
$depts = $conn->query("SELECT DISTINCT department FROM employees WHERE department IS NOT NULL AND department != '' ORDER BY department");

// Get statuses for filter
$statuses = $conn->query("SELECT DISTINCT employment_status FROM employees WHERE employment_status IS NOT NULL AND employment_status != '' ORDER BY employment_status");

// Execute query
$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Records - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f5f5; display: flex; }
        
        /* Sidebar */
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
        
        /* Filters */
        .filters { background: white; border-radius: 15px; padding: 20px; margin-bottom: 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .filter-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; }
        .filter-group { display: flex; flex-direction: column; }
        .filter-group label { margin-bottom: 5px; color: #555; font-size: 14px; font-weight: 500; }
        .filter-group input, .filter-group select { padding: 10px; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 14px; outline: none; }
        .filter-group input:focus, .filter-group select:focus { border-color: #667eea; }
        .filter-actions { display: flex; gap: 10px; align-items: flex-end; }
        .filter-actions button { padding: 10px 20px; border: none; border-radius: 8px; font-size: 14px; cursor: pointer; }
        .btn-apply { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
        .btn-reset { background: #f0f0f0; color: #333; }
        
        /* Table */
        .table-container { background: white; border-radius: 15px; padding: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; padding: 15px 10px; background: #f8f9fa; color: #333; font-weight: 600; border-bottom: 2px solid #e0e0e0; position: sticky; top: 0; }
        td { padding: 15px 10px; border-bottom: 1px solid #e0e0e0; color: #666; }
        tr:hover { background: #f8f9fa; }
        .status-badge { padding: 5px 10px; border-radius: 20px; font-size: 12px; font-weight: 600; display: inline-block; }
        .status-fulltime { background: #e8f5e8; color: #4caf50; }
        .status-parttime { background: #fff3e0; color: #ff9800; }
        .status-contractual { background: #ffebee; color: #f44336; }
        .action-buttons { display: flex; gap: 5px; }
        .action-btn { padding: 5px 10px; border: none; border-radius: 4px; cursor: pointer; font-size: 12px; text-decoration: none; color: white; }
        .action-btn.view { background: #2196f3; }
        .action-btn.edit { background: #ff9800; }
        .action-btn.delete { background: #f44336; }
        .action-btn.qr { background: #9c27b0; }
        .export-buttons { display: flex; gap: 10px; margin-bottom: 20px; }
        .btn-export { padding: 10px 20px; border: none; border-radius: 8px; font-size: 14px; cursor: pointer; background: #4caf50; color: white; }
        
        /* Pagination */
        .pagination { margin-top: 20px; display: flex; justify-content: center; gap: 10px; }
        .page-btn { padding: 8px 12px; border: 1px solid #e0e0e0; border-radius: 4px; background: white; cursor: pointer; }
        .page-btn.active { background: #667eea; color: white; border-color: #667eea; }
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
            <a href="employees.php" class="menu-item">
                <i class="fas fa-users"></i> Employees
            </a>
            <a href="add_employee.php" class="menu-item">
                <i class="fas fa-user-plus"></i> Add Employee
            </a>
            <a href="qr_codes.php" class="menu-item">
                <i class="fas fa-qrcode"></i> QR Codes
            </a>
            <a href="records.php" class="menu-item active">
                <i class="fas fa-table"></i> Records
            </a>
            <a href="logout.php" class="menu-item logout">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </div>

    <div class="main-content">
        <div class="header">
            <h1>Employee Records</h1>
            <div class="export-buttons">
                <button onclick="exportToCSV()" class="btn-export">
                    <i class="fas fa-file-csv"></i> Export CSV
                </button>
                <button onclick="exportToExcel()" class="btn-export">
                    <i class="fas fa-file-excel"></i> Export Excel
                </button>
            </div>
        </div>

        <div class="filters">
            <form method="GET" action="">
                <div class="filter-grid">
                    <div class="filter-group">
                        <label>Search</label>
                        <input type="text" name="search" placeholder="Name, ID, Email..." value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    <div class="filter-group">
                        <label>Department</label>
                        <select name="department">
                            <option value="">All Departments</option>
                            <?php while ($dept = $depts->fetch_assoc()): ?>
                                <option value="<?php echo htmlspecialchars($dept['department']); ?>" <?php echo $department == $dept['department'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($dept['department']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label>Employment Status</label>
                        <select name="status">
                            <option value="">All Status</option>
                            <?php while ($stat = $statuses->fetch_assoc()): ?>
                                <option value="<?php echo htmlspecialchars($stat['employment_status']); ?>" <?php echo $status == $stat['employment_status'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($stat['employment_status']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="filter-actions">
                        <button type="submit" class="btn-apply">Apply Filters</button>
                        <a href="records.php" class="btn-reset" style="text-decoration: none; color: #333; padding: 10px 20px; display: inline-block;">Reset</a>
                    </div>
                </div>
            </form>
        </div>

        <div class="table-container">
            <table id="recordsTable">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Employee ID</th>
                        <th>Full Name</th>
                        <th>Position</th>
                        <th>Department</th>
                        <th>Email</th>
                        <th>Mobile</th>
                        <th>Status</th>
                        <th>Date Hired</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <?php
                        $statusClass = '';
                        if ($row['employment_status'] == 'Full-time') $statusClass = 'status-fulltime';
                        elseif ($row['employment_status'] == 'Part-time') $statusClass = 'status-parttime';
                        elseif ($row['employment_status'] == 'Contractual') $statusClass = 'status-contractual';
                        ?>
                        <tr>
                            <td><?php echo $row['id']; ?></td>
                            <td><?php echo htmlspecialchars($row['employee_id']); ?></td>
                            <td><?php echo htmlspecialchars($row['full_name']); ?></td>
                            <td><?php echo htmlspecialchars($row['position']); ?></td>
                            <td><?php echo htmlspecialchars($row['department']); ?></td>
                            <td><?php echo htmlspecialchars($row['email']); ?></td>
                            <td><?php echo htmlspecialchars($row['mobile_number']); ?></td>
                            <td>
                                <span class="status-badge <?php echo $statusClass; ?>">
                                    <?php echo htmlspecialchars($row['employment_status'] ?: 'N/A'); ?>
                                </span>
                            </td>
                            <td><?php echo $row['date_hired'] ? date('M d, Y', strtotime($row['date_hired'])) : 'N/A'; ?></td>
                            <td class="action-buttons">
                                <a href="view_employee.php?id=<?php echo $row['id']; ?>" class="action-btn view" title="View" target="_blank">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="edit_employee.php?id=<?php echo $row['id']; ?>" class="action-btn edit" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="generate_qr.php?id=<?php echo $row['id']; ?>" class="action-btn qr" title="QR Code">
                                    <i class="fas fa-qrcode"></i>
                                </a>
                                <a href="employees.php?delete=<?php echo $row['id']; ?>" class="action-btn delete" title="Delete" onclick="return confirm('Are you sure you want to delete this employee?')">
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
        function exportToCSV() {
            let csv = [];
            let rows = document.querySelectorAll("#recordsTable tr");
            
            for (let i = 0; i < rows.length; i++) {
                let row = [], cols = rows[i].querySelectorAll("td, th");
                
                for (let j = 0; j < cols.length - 1; j++) { // Exclude actions column
                    let data = cols[j].innerText.replace(/,/g, ';'); // Replace commas to avoid CSV issues
                    row.push('"' + data + '"');
                }
                csv.push(row.join(','));
            }
            
            let csvContent = csv.join("\n");
            let blob = new Blob([csvContent], { type: 'text/csv' });
            let url = window.URL.createObjectURL(blob);
            let a = document.createElement('a');
            a.href = url;
            a.download = 'employee_records.csv';
            a.click();
        }
        
        function exportToExcel() {
            let table = document.getElementById('recordsTable');
            let html = table.outerHTML;
            let url = 'data:application/vnd.ms-excel,' + encodeURIComponent(html);
            let a = document.createElement('a');
            a.href = url;
            a.download = 'employee_records.xls';
            a.click();
        }
    </script>
</body>
</html>