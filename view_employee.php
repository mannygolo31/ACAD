<?php
// view_employee.php
require_once 'config.php';

$id = isset($_GET['id']) ? sanitize($_GET['id']) : 0;
$employee_id = isset($_GET['employee_id']) ? sanitize($_GET['employee_id']) : '';

// Check if password is provided (POST only for security)
$show_info = false;
$password_error = false;

// Also allow logged-in admins to view without password
if (isLoggedIn()) {
    $show_info = true;
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pass'])) {
    // Rate limit password attempts on view_employee
    $view_ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $view_key = 'view_employee:' . $view_ip;
    $view_limiter = new RateLimiter(5, 900);
    if ($view_limiter->isRateLimited($view_key)) {
        $password_error = true;
    } elseif ($_POST['pass'] === ADMIN_PASSWORD) {
        $show_info = true;
        $view_limiter->reset($view_key);
    } else {
        $view_limiter->recordAttempt($view_key);
        $password_error = true;
    }
}

// Get employee data
if ($id > 0) {
    $stmt = $conn->prepare("SELECT * FROM employees WHERE id = ?");
    $stmt->bind_param("i", $id);
} elseif (!empty($employee_id)) {
    $stmt = $conn->prepare("SELECT * FROM employees WHERE employee_id = ?");
    $stmt->bind_param("s", $employee_id);
} else {
    header("Location: login.php");
    exit();
}

$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    header("Location: not_found.php");
    exit();
}

$employee = $result->fetch_assoc();
$stmt->close();

// Add full_name field for display
$employee['full_name'] = trim($employee['first_name'] . ' ' . $employee['middle_name'] . ' ' . $employee['last_name']);
$employee['full_name'] = preg_replace('/\s+/', ' ', $employee['full_name']); // Remove extra spaces

// Format date of birth for display
$formatted_dob = 'N/A';
if (!empty($employee['date_of_birth']) && $employee['date_of_birth'] != '0000-00-00' && $employee['date_of_birth'] != null) {
    try {
        $timestamp = strtotime($employee['date_of_birth']);
        if ($timestamp !== false) {
            $formatted_dob = date('F j, Y', $timestamp); // Format as "Month Day, Year"
        } else {
            $formatted_dob = $employee['date_of_birth'];
        }
    } catch (Exception $e) {
        $formatted_dob = $employee['date_of_birth'];
    }
}

// Ensure age is displayed properly
$age_display = 'N/A';
if (isset($employee['age']) && $employee['age'] > 0) {
    $age_display = $employee['age'];
}
// If not showing info, show password page
if (!$show_info) {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Verify Access - Employee Directory</title>
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body { font-family: 'Futura', 'Helvetica Neue', Arial, sans-serif; background: linear-gradient(135deg, #d81919 0%, #555555 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; }
            .card { background: white; border-radius: 20px; padding: 30px; max-width: 400px; width: 100%; box-shadow: 0 20px 40px rgba(0,0,0,0.2); animation: slideUp 0.5s ease; }
            @keyframes slideUp { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }
            .header { text-align: center; margin-bottom: 30px; }
            .header h1 { color: #d81919; font-size: 24px; margin-bottom: 10px; }
            .password-icon { font-size: 60px; text-align: center; margin-bottom: 20px; }
            .input-group { margin-bottom: 20px; }
            label { display: block; margin-bottom: 8px; color: #555; font-weight: 500; }
            input { width: 100%; padding: 15px; border: 2px solid #e0e0e0; border-radius: 10px; font-size: 16px; outline: none; }
            input:focus { border-color: #d81919; }
            button { width: 100%; padding: 15px; background: linear-gradient(135deg, #d81919 0%, #555555 100%); color: white; border: none; border-radius: 10px; font-size: 16px; font-weight: 600; cursor: pointer; }
            button:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(0,0,0,0.2); }
            .error-message { color: #f44336; font-size: 14px; text-align: center; margin-top: 10px; padding: 10px; background: #ffebee; border-radius: 5px; display: <?php echo $password_error ? 'block' : 'none'; ?>; }
            .info-text { text-align: center; color: #999; font-size: 12px; margin-top: 20px; }
        </style>
    </head>
    <body>
        <div class="card">
            <div class="password-icon">🔒</div>
            <div class="header">
                <h1>Password Required</h1>
                <p>This employee information is protected. Please enter the password to continue.</p>
            </div>
            
            <form method="POST" action="view_employee.php?id=<?php echo urlencode($id); ?>&employee_id=<?php echo urlencode($employee_id); ?>">
                
                <div class="input-group">
                    <label>Password</label>
                    <input type="password" name="pass" placeholder="Enter password" autofocus maxlength="128">
                </div>
                
                <button type="submit">Verify & Continue</button>
                
                <div class="error-message">Incorrect password. Please try again.</div>
                
                <div class="info-text">
                    Employee ID: <strong><?php echo htmlspecialchars($employee['employee_id']); ?></strong>
                </div>
            </form>
        </div>
    </body>
    </html>
    <?php
    exit();
}

// Show employee information
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($employee['full_name']); ?> - Employee Directory</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Futura', 'Helvetica Neue', Arial, sans-serif; background: linear-gradient(135deg, #d81919 0%, #555555 100%); min-height: 100vh; padding: 20px; }
        .container { max-width: 1000px; margin: 0 auto; }
        .card { background: white; border-radius: 20px; padding: 30px; box-shadow: 0 20px 40px rgba(0,0,0,0.2); animation: slideUp 0.5s ease; }
        @keyframes slideUp { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }
        
        .profile-header { display: flex; align-items: center; margin-bottom: 30px; padding-bottom: 20px; border-bottom: 2px solid #f0f0f0; }
        .profile-photo { width: 120px; height: 120px; border-radius: 60px; background: linear-gradient(135deg, #d81919 0%, #555555 100%); display: flex; align-items: center; justify-content: center; margin-right: 30px; overflow: hidden; }
        .profile-photo img { width: 100%; height: 100%; object-fit: cover; }
        .profile-photo .initials { color: white; font-size: 48px; font-weight: bold; }
        .profile-title h1 { font-size: 32px; color: #333; margin-bottom: 5px; }
        .profile-title p { color: #d81919; font-size: 18px; font-weight: 600; }
        
        .info-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; margin-bottom: 30px; }
        .info-section { background: #f8f9fa; border-radius: 15px; padding: 20px; }
        .info-section h2 { color: #d81919; font-size: 18px; margin-bottom: 15px; padding-bottom: 10px; border-bottom: 2px solid #e0e0e0; }
        .info-section h2 i { margin-right: 8px; }
        .info-row { display: flex; margin-bottom: 10px; }
        .info-label { width: 150px; color: #666; font-size: 14px; }
        .info-value { flex: 1; color: #333; font-weight: 500; }
        
        .action-buttons { display: flex; gap: 15px; margin-top: 20px; }
        .btn { padding: 12px 24px; border: none; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 5px; transition: all 0.3s; }
        .btn-primary { background: linear-gradient(135deg, #d81919 0%, #555555 100%); color: white; }
        .btn-secondary { background: #f0f0f0; color: #333; }
        .btn:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(0,0,0,0.2); }
        
        @media print {
            body { background: white; padding: 0; }
            .card { box-shadow: none; padding: 20px; }
            .action-buttons { display: none; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="profile-header">
                <div class="profile-photo">
                    <?php if (!empty($employee['photo_url']) && file_exists($employee['photo_url'])): ?>
                        <img src="<?php echo htmlspecialchars($employee['photo_url']); ?>" alt="<?php echo htmlspecialchars($employee['full_name']); ?>">
                    <?php else: ?>
                        <?php 
                        $initials = strtoupper(substr($employee['first_name'], 0, 1) . substr($employee['last_name'], 0, 1));
                        ?>
                        <span class="initials"><?php echo $initials; ?></span>
                    <?php endif; ?>
                </div>
                <div class="profile-title">
                    <h1><?php echo htmlspecialchars($employee['full_name']); ?></h1>
                    <p><?php echo htmlspecialchars($employee['position'] ?: 'Position Not Set'); ?></p>
                </div>
            </div>
            
            <div class="info-grid">
                <!-- Personal Information -->
                <div class="info-section">
                    <h2><i class="fas fa-user"></i> Personal Information</h2>
                    <div class="info-row">
                        <span class="info-label">Employee ID:</span>
                        <span class="info-value"><?php echo htmlspecialchars($employee['employee_id']); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Full Name:</span>
                        <span class="info-value"><?php echo htmlspecialchars($employee['full_name']); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Nickname:</span>
                        <span class="info-value"><?php echo htmlspecialchars($employee['nickname'] ?: 'N/A'); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Date of Birth:</span>
                        <span class="info-value"><?php echo $formatted_dob; ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Age:</span>
                        <span class="info-value"><?php echo $age_display; ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Place of Birth:</span>
                        <span class="info-value"><?php echo htmlspecialchars($employee['place_of_birth'] ?: 'N/A'); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Gender:</span>
                        <span class="info-value"><?php echo htmlspecialchars($employee['gender']); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Civil Status:</span>
                        <span class="info-value"><?php echo htmlspecialchars($employee['civil_status'] ?: 'N/A'); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Religion:</span>
                        <span class="info-value"><?php echo htmlspecialchars($employee['religion'] ?: 'N/A'); ?></span>
                    </div>
                </div>
                
                <!-- Contact Information -->
                <div class="info-section">
                    <h2><i class="fas fa-address-book"></i> Contact Information</h2>
                    <div class="info-row">
                        <span class="info-label">Mobile Number:</span>
                        <span class="info-value"><?php echo htmlspecialchars($employee['mobile_number'] ?: 'N/A'); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Telephone:</span>
                        <span class="info-value"><?php echo htmlspecialchars($employee['telephone_number'] ?: 'N/A'); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Email:</span>
                        <span class="info-value"><?php echo htmlspecialchars($employee['email'] ?: 'N/A'); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Present Address:</span>
                        <span class="info-value"><?php echo nl2br(htmlspecialchars($employee['present_address'] ?: 'N/A')); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Permanent Address:</span>
                        <span class="info-value"><?php echo nl2br(htmlspecialchars($employee['permanent_address'] ?: 'N/A')); ?></span>
                    </div>
                </div>
                
                <!-- Employment Details -->
                <div class="info-section">
                    <h2><i class="fas fa-briefcase"></i> Employment Details</h2>
                    <div class="info-row">
                        <span class="info-label">Department:</span>
                        <span class="info-value"><?php echo htmlspecialchars($employee['department'] ?: 'N/A'); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Position:</span>
                        <span class="info-value"><?php echo htmlspecialchars($employee['position'] ?: 'N/A'); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Branch:</span>
                        <span class="info-value"><?php echo htmlspecialchars($employee['branch'] ?: 'N/A'); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Company:</span>
                        <span class="info-value"><?php echo htmlspecialchars($employee['company'] ?: 'N/A'); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Employment Status:</span>
                        <span class="info-value"><?php echo htmlspecialchars($employee['employment_status'] ?: 'N/A'); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Date Hired:</span>
                        <span class="info-value"><?php echo htmlspecialchars($employee['date_hired'] ?: 'N/A'); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Basic Salary:</span>
                        <span class="info-value">₱<?php echo number_format($employee['basic_salary'], 2); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Rate:</span>
                        <span class="info-value">₱<?php echo number_format($employee['rate'], 2); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Allowances:</span>
                        <span class="info-value">₱<?php echo number_format($employee['allowances'], 2); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Pay Frequency:</span>
                        <span class="info-value"><?php echo htmlspecialchars($employee['pay_frequency'] ?: 'N/A'); ?></span>
                    </div>
                </div>
                
                <!-- Government IDs -->
                <div class="info-section">
                    <h2><i class="fas fa-id-card"></i> Government IDs</h2>
                    <div class="info-row">
                        <span class="info-label">SSS No.:</span>
                        <span class="info-value"><?php echo htmlspecialchars($employee['sss_no'] ?: 'N/A'); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">TIN:</span>
                        <span class="info-value"><?php echo htmlspecialchars($employee['tin'] ?: 'N/A'); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Pag-ibig MID:</span>
                        <span class="info-value"><?php echo htmlspecialchars($employee['pagibig_mid'] ?: 'N/A'); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Philhealth No.:</span>
                        <span class="info-value"><?php echo htmlspecialchars($employee['philhealth_no'] ?: 'N/A'); ?></span>
                    </div>
                </div>
                
                <!-- Family Background -->
                <div class="info-section">
                    <h2><i class="fas fa-users"></i> Family Background</h2>
                    <div class="info-row">
                        <span class="info-label">Spouse:</span>
                        <span class="info-value"><?php echo htmlspecialchars($employee['spouse_name'] ?: 'N/A'); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Spouse Occupation:</span>
                        <span class="info-value"><?php echo htmlspecialchars($employee['spouse_occupation'] ?: 'N/A'); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Father:</span>
                        <span class="info-value"><?php echo htmlspecialchars($employee['father_name'] ?: 'N/A'); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Father Occupation:</span>
                        <span class="info-value"><?php echo htmlspecialchars($employee['father_occupation'] ?: 'N/A'); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Mother:</span>
                        <span class="info-value"><?php echo htmlspecialchars($employee['mother_name'] ?: 'N/A'); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Mother Occupation:</span>
                        <span class="info-value"><?php echo htmlspecialchars($employee['mother_occupation'] ?: 'N/A'); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Number of Siblings:</span>
                        <span class="info-value"><?php echo htmlspecialchars($employee['number_of_siblings'] ?: 'N/A'); ?></span>
                    </div>
                </div>
                
                <!-- Emergency Contact -->
                <div class="info-section">
                    <h2><i class="fas fa-phone-alt"></i> Emergency Contact</h2>
                    <div class="info-row">
                        <span class="info-label">Name:</span>
                        <span class="info-value"><?php echo htmlspecialchars($employee['emergency_name'] ?: 'N/A'); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Address:</span>
                        <span class="info-value"><?php echo nl2br(htmlspecialchars($employee['emergency_address'] ?: 'N/A')); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Contact Number:</span>
                        <span class="info-value"><?php echo htmlspecialchars($employee['emergency_contact'] ?: 'N/A'); ?></span>
                    </div>
                </div>
            </div>
            
            <div class="action-buttons">
                <button onclick="window.print()" class="btn btn-primary">
                    <i class="fas fa-print"></i> Print
                </button>
                <a href="edit_employee.php?id=<?php echo $employee['id']; ?>" class="btn btn-primary">
                    <i class="fas fa-edit"></i> Edit
                </a>
                <a href="login.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Login
                </a>
            </div>
        </div>
    </div>
</body>
</html>