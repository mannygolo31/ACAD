<?php
// dashboard.php
require_once 'config.php';
requireLogin();

// Get statistics
$total_employees = $conn->query("SELECT COUNT(*) as count FROM employees")->fetch_assoc()['count'];
$recent_employees = $conn->query("SELECT COUNT(*) as count FROM employees WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetch_assoc()['count'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?php echo SITE_NAME; ?></title>
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
        .header .user-info { display: flex; align-items: center; }
        .header .user-info span { margin-right: 15px; color: #555555; }

        /* Stats Cards */
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 30px; margin-bottom: 40px; }
        .stat-card { background: white; border-radius: 15px; padding: 25px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); transition: transform 0.3s; }
        .stat-card:hover { transform: translateY(-5px); box-shadow: 0 5px 20px rgba(0,0,0,0.15); }
        .stat-icon { font-size: 40px; color: #d81919; margin-bottom: 15px; }
        .stat-title { color: #555555; font-size: 14px; margin-bottom: 5px; }
        .stat-number { color: #333; font-size: 32px; font-weight: bold; }

        /* Welcome Section */
        .welcome-card { background: linear-gradient(135deg, #d81919 0%, #555555 100%); color: white; border-radius: 15px; padding: 40px; margin-bottom: 30px; box-shadow: 0 5px 20px rgba(0,0,0,0.2); }
        .welcome-card h2 { font-size: 28px; margin-bottom: 10px; }
        .welcome-card p { opacity: 0.9; margin-bottom: 20px; }
        .welcome-card .btn { background: white; color: #d81919; padding: 12px 30px; border: none; border-radius: 8px; font-size: 16px; font-weight: 600; cursor: pointer; text-decoration: none; display: inline-block; transition: transform 0.3s; }
        .welcome-card .btn:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(0,0,0,0.2); }

        /* Quick Actions */
        .quick-actions { background: white; border-radius: 15px; padding: 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .quick-actions h3 { color: #333; margin-bottom: 20px; }
        .action-buttons { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; }
        .action-btn { padding: 15px; border: 2px solid #e0e0e0; border-radius: 10px; text-decoration: none; color: #333; text-align: center; transition: all 0.3s; }
        .action-btn i { display: block; font-size: 24px; margin-bottom: 10px; color: #d81919; }
        .action-btn:hover { border-color: #d81919; transform: translateY(-2px); }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-header">
            <h2><i class="fas fa-shield-alt"></i> NSIAI</h2>
            <p>Employee Management System</p>
        </div>
        <div class="sidebar-menu">
            <a href="dashboard.php" class="menu-item active">
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
            <h1>Dashboard</h1>
            <div class="user-info">
                <span>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></span>
                <i class="fas fa-user-circle" style="font-size: 30px; color: #d81919;"></i>
            </div>
        </div>

        <div class="welcome-card">
            <h2>Welcome to Employee Directory System</h2>
            <p>Manage your employees, generate QR codes, and track records all in one place.</p>
            <a href="add_employee.php" class="btn">Add New Employee</a>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-users"></i></div>
                <div class="stat-title">Total Employees</div>
                <div class="stat-number"><?php echo $total_employees; ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-user-plus"></i></div>
                <div class="stat-title">New This Week</div>
                <div class="stat-number"><?php echo $recent_employees; ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-qrcode"></i></div>
                <div class="stat-title">QR Codes Generated</div>
                <div class="stat-number"><?php echo $total_employees; ?></div>
            </div>
        </div>

        <div class="quick-actions">
            <h3>Quick Actions</h3>
            <div class="action-buttons">
                <a href="employees.php" class="action-btn">
                    <i class="fas fa-list"></i>
                    View All Employees
                </a>
                <a href="add_employee.php" class="action-btn">
                    <i class="fas fa-user-plus"></i>
                    Add New Employee
                </a>
                <a href="qr_codes.php" class="action-btn">
                    <i class="fas fa-qrcode"></i>
                    Generate QR Codes
                </a>
                <a href="records.php" class="action-btn">
                    <i class="fas fa-table"></i>
                    View Records
                </a>
            </div>
        </div>
    </div>
</body>
</html>
