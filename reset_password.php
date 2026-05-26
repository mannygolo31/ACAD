<?php
// reset_password.php
require_once 'config.php';
requireLogin();

$error = '';
$success = '';
$csrf_token = generateCSRFToken();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $error = 'Invalid request. Please try again.';
    } else {
        $action = isset($_POST['action']) ? $_POST['action'] : '';

        if ($action === 'reset_admin') {
            $new_username = 'admin';
            $new_password = 'admin1234';
            $hashed = password_hash($new_password, PASSWORD_DEFAULT);

            $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->bind_param("s", $new_username);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $stmt->close();
                $update = $conn->prepare("UPDATE users SET password = ? WHERE username = ?");
                $update->bind_param("ss", $hashed, $new_username);
                if ($update->execute()) {
                    logActivity($_SESSION['user_id'], 'Reset Password', 'Admin password reset to default');
                    $success = 'Admin password has been reset successfully! New credentials: admin / admin1234';
                } else {
                    $error = 'Failed to reset password: ' . $conn->error;
                }
                $update->close();
            } else {
                $stmt->close();
                $insert = $conn->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, 'admin')");
                $insert->bind_param("ss", $new_username, $hashed);
                if ($insert->execute()) {
                    logActivity($_SESSION['user_id'], 'Create Admin', 'Admin user created with default password');
                    $success = 'Admin user created successfully! Credentials: admin / admin1234';
                } else {
                    $error = 'Failed to create admin user: ' . $conn->error;
                }
                $insert->close();
            }
        } elseif ($action === 'change_password') {
            $target_user = isset($_POST['target_user']) ? sanitize($_POST['target_user']) : '';
            $new_pass = isset($_POST['new_password']) ? $_POST['new_password'] : '';

            if (empty($target_user) || empty($new_pass)) {
                $error = 'Please fill in all fields.';
            } elseif (strlen($new_pass) < 6) {
                $error = 'Password must be at least 6 characters.';
            } elseif (strlen($new_pass) > 128) {
                $error = 'Password is too long.';
            } else {
                $hashed = password_hash($new_pass, PASSWORD_DEFAULT);
                $update = $conn->prepare("UPDATE users SET password = ? WHERE username = ?");
                $update->bind_param("ss", $hashed, $target_user);
                if ($update->execute() && $update->affected_rows > 0) {
                    logActivity($_SESSION['user_id'], 'Change Password', "Password changed for user: $target_user");
                    $success = "Password changed successfully for user: $target_user";
                } else {
                    $error = 'User not found or password not changed.';
                }
                $update->close();
            }
        }

        // Regenerate CSRF token after successful action
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $csrf_token = $_SESSION['csrf_token'];
    }
}

// Get all users
$users_result = $conn->query("SELECT id, username, role, created_at FROM users ORDER BY id ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - <?php echo SITE_NAME; ?></title>
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
        .header { background: white; padding: 20px 30px; border-radius: 15px; margin-bottom: 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .header h1 { color: #333; font-size: 24px; }

        .card { background: white; border-radius: 15px; padding: 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 30px; }
        .card h2 { color: #d81919; font-size: 20px; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 2px solid #f0f0f0; }
        .card h2 i { margin-right: 8px; }

        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; color: #555555; font-weight: 500; }
        .form-group input, .form-group select { width: 100%; padding: 12px; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 14px; outline: none; }
        .form-group input:focus, .form-group select:focus { border-color: #d81919; }

        .btn { padding: 12px 24px; border: none; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer; transition: all 0.3s; display: inline-flex; align-items: center; gap: 8px; }
        .btn-danger { background: #d81919; color: white; }
        .btn-danger:hover { background: #b01515; transform: translateY(-2px); box-shadow: 0 5px 15px rgba(216,25,25,0.3); }
        .btn-primary { background: linear-gradient(135deg, #d81919 0%, #a01414 100%); color: white; }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(216,25,25,0.3); }

        .error-message { background: #ffebee; color: #f44336; padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #ffcdd2; }
        .success-message { background: #e8f5e8; color: #4caf50; padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #a5d6a7; }
        .warning-box { background: #fff3e0; border-left: 4px solid #ff9800; padding: 15px; border-radius: 8px; margin-bottom: 20px; }

        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th { text-align: left; padding: 12px 10px; background: #f8f9fa; color: #333; font-weight: 600; border-bottom: 2px solid #e0e0e0; }
        td { padding: 12px 10px; border-bottom: 1px solid #e0e0e0; color: #555555; }
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
            <a href="reset_password.php" class="menu-item active">
                <i class="fas fa-key"></i> Reset Password
            </a>
            <a href="logout.php" class="menu-item logout">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </div>

    <div class="main-content">
        <div class="header">
            <h1><i class="fas fa-key"></i> Password Management</h1>
        </div>

        <?php if ($error): ?>
            <div class="error-message"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="success-message"><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <!-- Quick Reset Admin Password -->
        <div class="card">
            <h2><i class="fas fa-redo"></i> Quick Reset Admin Password</h2>
            <div class="warning-box">
                <strong><i class="fas fa-exclamation-triangle"></i> Warning:</strong> This will reset the admin password to the default credentials: <strong>admin / admin1234</strong>
            </div>
            <form method="POST" action="" onsubmit="return confirm('Are you sure you want to reset the admin password to admin/admin1234?')">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                <input type="hidden" name="action" value="reset_admin">
                <button type="submit" class="btn btn-danger">
                    <i class="fas fa-redo"></i> Reset Admin Password to admin/admin1234
                </button>
            </form>
        </div>

        <!-- Change Any User Password -->
        <div class="card">
            <h2><i class="fas fa-user-lock"></i> Change User Password</h2>
            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                <input type="hidden" name="action" value="change_password">
                <div class="form-group">
                    <label>Select User</label>
                    <select name="target_user" required>
                        <option value="">-- Select User --</option>
                        <?php
                        if ($users_result && $users_result->num_rows > 0) {
                            $users_result->data_seek(0);
                            while ($u = $users_result->fetch_assoc()) {
                                echo '<option value="' . htmlspecialchars($u['username']) . '">' . htmlspecialchars($u['username']) . ' (' . htmlspecialchars($u['role']) . ')</option>';
                            }
                        }
                        ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>New Password</label>
                    <input type="password" name="new_password" placeholder="Enter new password (min 6 chars)" required minlength="6" maxlength="128">
                </div>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Change Password
                </button>
            </form>
        </div>

        <!-- Users List -->
        <div class="card">
            <h2><i class="fas fa-users-cog"></i> Registered Users</h2>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Role</th>
                        <th>Created</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($users_result && $users_result->num_rows > 0) {
                        $users_result->data_seek(0);
                        while ($u = $users_result->fetch_assoc()):
                    ?>
                        <tr>
                            <td><?php echo $u['id']; ?></td>
                            <td><?php echo htmlspecialchars($u['username']); ?></td>
                            <td><?php echo htmlspecialchars($u['role']); ?></td>
                            <td><?php echo $u['created_at']; ?></td>
                        </tr>
                    <?php
                        endwhile;
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
