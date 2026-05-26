<?php
// edit_employee.php
require_once 'config.php';
requireLogin();

$error = '';
$success = '';
$id = isset($_GET['id']) ? sanitize($_GET['id']) : 0;

// Get employee data
$stmt = $conn->prepare("SELECT * FROM employees WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    $stmt->close();
    header("Location: employees.php");
    exit();
}

$employee = $result->fetch_assoc();
$stmt->close();

// Format date for HTML5 date input (Y-m-d)
$employee['date_of_birth_formatted'] = '';

// Check if date_of_birth exists and is not empty/null/zero
if (!empty($employee['date_of_birth']) && $employee['date_of_birth'] != '0000-00-00' && $employee['date_of_birth'] != null) {
    
    // If it's already in Y-m-d format, use it directly
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $employee['date_of_birth'])) {
        $employee['date_of_birth_formatted'] = $employee['date_of_birth'];
    } else {
        // Try to convert from other formats
        try {
            // Handle different date formats
            $timestamp = strtotime($employee['date_of_birth']);
            if ($timestamp !== false) {
                $employee['date_of_birth_formatted'] = date('Y-m-d', $timestamp);
            } else {
                $employee['date_of_birth_formatted'] = '';
            }
        } catch (Exception $e) {
            $employee['date_of_birth_formatted'] = '';
            error_log("Date formatting error: " . $e->getMessage());
        }
    }
}

// Ensure age is set
if (!isset($employee['age']) || $employee['age'] == '') {
    $employee['age'] = 0;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validate inputs
    $validation_errors = InputValidator::validateAllInputs($_POST, ['first_name', 'last_name']);
    if (!empty($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $file_errors = InputValidator::validateFileUpload($_FILES['photo']);
        $validation_errors = array_merge($validation_errors, $file_errors);
    }
    if (!empty($validation_errors)) {
        $error = implode(' ', $validation_errors);
    } else {
    // Sanitize and prepare data - use existing values as fallback
    $first_name = isset($_POST['first_name']) ? sanitize($_POST['first_name']) : $employee['first_name'];
    $middle_name = isset($_POST['middle_name']) ? sanitize($_POST['middle_name']) : $employee['middle_name'];
    $last_name = isset($_POST['last_name']) ? sanitize($_POST['last_name']) : $employee['last_name'];
    $nickname = isset($_POST['nickname']) ? sanitize($_POST['nickname']) : $employee['nickname'];
    $date_of_birth = isset($_POST['date_of_birth']) && !empty($_POST['date_of_birth']) ? sanitize($_POST['date_of_birth']) : null;
    
    // Calculate age from date of birth
    $age = 0;
    if (!empty($date_of_birth) && $date_of_birth != null) {
        $dob = new DateTime($date_of_birth);
        $today = new DateTime();
        $age = $dob->diff($today)->y;
    } elseif (!empty($employee['age'])) {
        $age = $employee['age'];
    }
    
    // Debug: Check if age is calculated
    error_log("Edit - Age calculated: " . $age . " for DOB: " . $date_of_birth);
    
    $place_of_birth = isset($_POST['place_of_birth']) ? sanitize($_POST['place_of_birth']) : $employee['place_of_birth'];
    $gender = isset($_POST['gender']) ? sanitize($_POST['gender']) : $employee['gender'];
    $mobile_number = isset($_POST['mobile_number']) ? sanitize($_POST['mobile_number']) : $employee['mobile_number'];
    $telephone_number = isset($_POST['telephone_number']) ? sanitize($_POST['telephone_number']) : $employee['telephone_number'];
    $email = isset($_POST['email']) ? sanitize($_POST['email']) : $employee['email'];
    $present_address = isset($_POST['present_address']) ? sanitize($_POST['present_address']) : $employee['present_address'];
    $permanent_address = isset($_POST['permanent_address']) ? sanitize($_POST['permanent_address']) : $employee['permanent_address'];
    $civil_status = isset($_POST['civil_status']) ? sanitize($_POST['civil_status']) : $employee['civil_status'];
    $religion = isset($_POST['religion']) ? sanitize($_POST['religion']) : $employee['religion'];
    $position = isset($_POST['position']) ? sanitize($_POST['position']) : $employee['position'];
    $department = isset($_POST['department']) ? sanitize($_POST['department']) : $employee['department'];
    $branch = isset($_POST['branch']) ? sanitize($_POST['branch']) : $employee['branch'];
    $company = isset($_POST['company']) ? sanitize($_POST['company']) : $employee['company'];
    $employment_status = isset($_POST['employment_status']) ? sanitize($_POST['employment_status']) : $employee['employment_status'];
    $basic_salary = isset($_POST['basic_salary']) && is_numeric($_POST['basic_salary']) ? floatval($_POST['basic_salary']) : $employee['basic_salary'];
    $rate = isset($_POST['rate']) && is_numeric($_POST['rate']) ? floatval($_POST['rate']) : $employee['rate'];
    $allowances = isset($_POST['allowances']) && is_numeric($_POST['allowances']) ? floatval($_POST['allowances']) : $employee['allowances'];
    $pay_frequency = isset($_POST['pay_frequency']) ? sanitize($_POST['pay_frequency']) : $employee['pay_frequency'];
    $sss_no = isset($_POST['sss_no']) ? sanitize($_POST['sss_no']) : $employee['sss_no'];
    $tin = isset($_POST['tin']) ? sanitize($_POST['tin']) : $employee['tin'];
    $pagibig_mid = isset($_POST['pagibig_mid']) ? sanitize($_POST['pagibig_mid']) : $employee['pagibig_mid'];
    $philhealth_no = isset($_POST['philhealth_no']) ? sanitize($_POST['philhealth_no']) : $employee['philhealth_no'];
    $spouse_name = isset($_POST['spouse_name']) ? sanitize($_POST['spouse_name']) : $employee['spouse_name'];
    $spouse_occupation = isset($_POST['spouse_occupation']) ? sanitize($_POST['spouse_occupation']) : $employee['spouse_occupation'];
    $father_name = isset($_POST['father_name']) ? sanitize($_POST['father_name']) : $employee['father_name'];
    $father_occupation = isset($_POST['father_occupation']) ? sanitize($_POST['father_occupation']) : $employee['father_occupation'];
    $mother_name = isset($_POST['mother_name']) ? sanitize($_POST['mother_name']) : $employee['mother_name'];
    $mother_occupation = isset($_POST['mother_occupation']) ? sanitize($_POST['mother_occupation']) : $employee['mother_occupation'];
    $number_of_siblings = isset($_POST['number_of_siblings']) && is_numeric($_POST['number_of_siblings']) ? intval($_POST['number_of_siblings']) : $employee['number_of_siblings'];
    $relative_working = isset($_POST['relative_working']) ? sanitize($_POST['relative_working']) : $employee['relative_working'];
    $relative_details = isset($_POST['relative_details']) ? sanitize($_POST['relative_details']) : $employee['relative_details'];
    $emergency_name = isset($_POST['emergency_name']) ? sanitize($_POST['emergency_name']) : $employee['emergency_name'];
    $emergency_address = isset($_POST['emergency_address']) ? sanitize($_POST['emergency_address']) : $employee['emergency_address'];
    $emergency_contact = isset($_POST['emergency_contact']) ? sanitize($_POST['emergency_contact']) : $employee['emergency_contact'];
    $date_hired = isset($_POST['date_hired']) && !empty($_POST['date_hired']) ? sanitize($_POST['date_hired']) : $employee['date_hired'];
    $photo_url = $employee['photo_url']; // Keep existing photo by default
    
    // Handle file upload for photo
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] == 0) {
        $target_dir = "uploads/";
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        $file_extension = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
        $file_name = $employee['employee_id'] . '_' . time() . '.' . $file_extension;
        $target_file = $target_dir . $file_name;
        
        // Delete old photo if exists
        if (!empty($employee['photo_url']) && file_exists($employee['photo_url'])) {
            unlink($employee['photo_url']);
        }
        
        if (move_uploaded_file($_FILES['photo']['tmp_name'], $target_file)) {
            $photo_url = $target_file;
        }
    }
    
    // Update database
    $sql = "UPDATE employees SET 
        first_name = ?, 
        middle_name = ?, 
        last_name = ?, 
        nickname = ?, 
        date_of_birth = ?, 
        age = ?, 
        place_of_birth = ?, 
        gender = ?,
        mobile_number = ?, 
        telephone_number = ?, 
        email = ?, 
        present_address = ?,
        permanent_address = ?, 
        civil_status = ?, 
        religion = ?, 
        position = ?,
        department = ?, 
        branch = ?, 
        company = ?, 
        employment_status = ?,
        basic_salary = ?, 
        rate = ?, 
        allowances = ?, 
        pay_frequency = ?,
        sss_no = ?, 
        tin = ?, 
        pagibig_mid = ?, 
        philhealth_no = ?,
        spouse_name = ?, 
        spouse_occupation = ?, 
        father_name = ?,
        father_occupation = ?, 
        mother_name = ?, 
        mother_occupation = ?,
        number_of_siblings = ?, 
        relative_working = ?, 
        relative_details = ?,
        emergency_name = ?, 
        emergency_address = ?, 
        emergency_contact = ?,
        date_hired = ?, 
        photo_url = ? 
        WHERE id = ?";
    
    $update_stmt = $conn->prepare($sql);
    
    if (!$update_stmt) {
        $error = "Prepare failed: " . $conn->error;
    } else {
        // Build type string
        $types = '';
        $types .= 's'; // first_name
        $types .= 's'; // middle_name
        $types .= 's'; // last_name
        $types .= 's'; // nickname
        $types .= 's'; // date_of_birth
        $types .= 'i'; // age - INTEGER
        $types .= 's'; // place_of_birth
        $types .= 's'; // gender
        $types .= 's'; // mobile_number
        $types .= 's'; // telephone_number
        $types .= 's'; // email
        $types .= 's'; // present_address
        $types .= 's'; // permanent_address
        $types .= 's'; // civil_status
        $types .= 's'; // religion
        $types .= 's'; // position
        $types .= 's'; // department
        $types .= 's'; // branch
        $types .= 's'; // company
        $types .= 's'; // employment_status
        $types .= 'd'; // basic_salary - DOUBLE
        $types .= 'd'; // rate - DOUBLE
        $types .= 'd'; // allowances - DOUBLE
        $types .= 's'; // pay_frequency
        $types .= 's'; // sss_no
        $types .= 's'; // tin
        $types .= 's'; // pagibig_mid
        $types .= 's'; // philhealth_no
        $types .= 's'; // spouse_name
        $types .= 's'; // spouse_occupation
        $types .= 's'; // father_name
        $types .= 's'; // father_occupation
        $types .= 's'; // mother_name
        $types .= 's'; // mother_occupation
        $types .= 'i'; // number_of_siblings - INTEGER
        $types .= 's'; // relative_working
        $types .= 's'; // relative_details
        $types .= 's'; // emergency_name
        $types .= 's'; // emergency_address
        $types .= 's'; // emergency_contact
        $types .= 's'; // date_hired
        $types .= 's'; // photo_url
        $types .= 'i'; // id - INTEGER
        
        // Debug: Check type string
        error_log("Edit - Type string length: " . strlen($types) . " - Types: " . $types);
        
        $update_stmt->bind_param(
            $types,
            $first_name,
            $middle_name,
            $last_name,
            $nickname,
            $date_of_birth,
            $age,
            $place_of_birth,
            $gender,
            $mobile_number,
            $telephone_number,
            $email,
            $present_address,
            $permanent_address,
            $civil_status,
            $religion,
            $position,
            $department,
            $branch,
            $company,
            $employment_status,
            $basic_salary,
            $rate,
            $allowances,
            $pay_frequency,
            $sss_no,
            $tin,
            $pagibig_mid,
            $philhealth_no,
            $spouse_name,
            $spouse_occupation,
            $father_name,
            $father_occupation,
            $mother_name,
            $mother_occupation,
            $number_of_siblings,
            $relative_working,
            $relative_details,
            $emergency_name,
            $emergency_address,
            $emergency_contact,
            $date_hired,
            $photo_url,
            $id
        );
        
       if ($update_stmt->execute()) {
            logActivity($_SESSION['user_id'], 'Edit Employee', "Edited employee: $first_name $last_name");
            $success = "Employee updated successfully!";
            
            // Refresh employee data
            $refresh_stmt = $conn->prepare("SELECT * FROM employees WHERE id = ?");
            $refresh_stmt->bind_param("i", $id);
            $refresh_stmt->execute();
            $result = $refresh_stmt->get_result();
            $employee = $result->fetch_assoc();
            $refresh_stmt->close();
            
            // Re-format date after refresh
            $employee['date_of_birth_formatted'] = '';
            if (!empty($employee['date_of_birth']) && $employee['date_of_birth'] != '0000-00-00') {
                if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $employee['date_of_birth'])) {
                    $employee['date_of_birth_formatted'] = $employee['date_of_birth'];
                } else {
                    try {
                        $date = new DateTime($employee['date_of_birth']);
                        $employee['date_of_birth_formatted'] = $date->format('Y-m-d');
                    } catch (Exception $e) {
                        $employee['date_of_birth_formatted'] = '';
                    }
                }
            }
        } else {
            $error = "Error: " . $update_stmt->error;
        }
        $update_stmt->close();
    }
    } // end validation check
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Employee - <?php echo SITE_NAME; ?></title>
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
        .header .employee-id { color: #d81919; font-size: 14px; }
        
        /* Form */
        .form-container { background: white; border-radius: 15px; padding: 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .form-section { margin-bottom: 30px; padding-bottom: 20px; border-bottom: 2px solid #f0f0f0; }
        .form-section h2 { color: #d81919; font-size: 20px; margin-bottom: 20px; }
        .form-section h2 i { margin-right: 10px; }
        .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 8px; color: #555; font-weight: 500; font-size: 14px; }
        label i { margin-right: 5px; color: #d81919; }
        input, select, textarea { width: 100%; padding: 12px; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 14px; outline: none; transition: all 0.3s; }
        input:focus, select:focus, textarea:focus { border-color: #d81919; }
        textarea { min-height: 100px; resize: vertical; }
        .btn { padding: 15px 30px; border: none; border-radius: 8px; font-size: 16px; font-weight: 600; cursor: pointer; transition: all 0.3s; }
        .btn-primary { background: linear-gradient(135deg, #d81919 0%, #555555 100%); color: white; }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(0,0,0,0.2); }
        .btn-secondary { background: #f0f0f0; color: #333; }
        .btn-secondary:hover { background: #e0e0e0; }
        .form-actions { display: flex; gap: 15px; justify-content: flex-end; margin-top: 30px; }
        .error-message { background: #ffebee; color: #f44336; padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #ffcdd2; }
        .success-message { background: #e8f5e8; color: #4caf50; padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #a5d6a7; }
        .photo-preview { max-width: 200px; margin-top: 10px; border-radius: 8px; }
        .current-photo { margin: 10px 0; }
        .current-photo img { max-width: 100px; border-radius: 8px; border: 2px solid #d81919; }
        .required-field::after { content: " *"; color: red; }
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
            <div>
                <h1><i class="fas fa-edit"></i> Edit Employee</h1>
                <span class="employee-id">Employee ID: <?php echo htmlspecialchars($employee['employee_id']); ?></span>
            </div>
        </div>

        <?php if ($error): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-circle"></i> 
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="success-message">
                <i class="fas fa-check-circle"></i> 
                <?php echo $success; ?>
            </div>
        <?php endif; ?>

        <div class="form-container">
            <form method="POST" action="" enctype="multipart/form-data" onsubmit="return validateForm()">
                <!-- Personal Information -->
                <div class="form-section">
                    <h2><i class="fas fa-user"></i> Personal Information</h2>
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="required-field"><i class="fas fa-user"></i> First Name</label>
                            <input type="text" name="first_name" id="first_name" value="<?php echo htmlspecialchars($employee['first_name']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-user"></i> Middle Name</label>
                            <input type="text" name="middle_name" value="<?php echo htmlspecialchars($employee['middle_name']); ?>">
                        </div>
                        <div class="form-group">
                            <label class="required-field"><i class="fas fa-user"></i> Last Name</label>
                            <input type="text" name="last_name" id="last_name" value="<?php echo htmlspecialchars($employee['last_name']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-smile"></i> Nickname</label>
                            <input type="text" name="nickname" value="<?php echo htmlspecialchars($employee['nickname']); ?>">
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-calendar"></i> Date of Birth</label>
                            <input type="date" name="date_of_birth" value="<?php echo $employee['date_of_birth_formatted']; ?>">
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-map-marker-alt"></i> Place of Birth</label>
                            <input type="text" name="place_of_birth" value="<?php echo htmlspecialchars($employee['place_of_birth']); ?>">
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-venus-mars"></i> Gender</label>
                            <select name="gender">
                                <option value="Male" <?php echo $employee['gender'] == 'Male' ? 'selected' : ''; ?>>Male</option>
                                <option value="Female" <?php echo $employee['gender'] == 'Female' ? 'selected' : ''; ?>>Female</option>
                                <option value="Other" <?php echo $employee['gender'] == 'Other' ? 'selected' : ''; ?>>Other</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-heart"></i> Civil Status</label>
                            <select name="civil_status">
                                <option value="">Select Status</option>
                                <option value="Single" <?php echo $employee['civil_status'] == 'Single' ? 'selected' : ''; ?>>Single</option>
                                <option value="Married" <?php echo $employee['civil_status'] == 'Married' ? 'selected' : ''; ?>>Married</option>
                                <option value="Divorced" <?php echo $employee['civil_status'] == 'Divorced' ? 'selected' : ''; ?>>Divorced</option>
                                <option value="Widowed" <?php echo $employee['civil_status'] == 'Widowed' ? 'selected' : ''; ?>>Widowed</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-church"></i> Religion</label>
                            <input type="text" name="religion" value="<?php echo htmlspecialchars($employee['religion']); ?>">
                        </div>
                    </div>
                </div>

                <!-- Contact Information -->
                <div class="form-section">
                    <h2><i class="fas fa-address-book"></i> Contact Information</h2>
                    <div class="form-grid">
                        <div class="form-group">
                            <label><i class="fas fa-mobile-alt"></i> Mobile Number</label>
                            <input type="text" name="mobile_number" value="<?php echo htmlspecialchars($employee['mobile_number']); ?>" placeholder="09123456789">
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-phone"></i> Telephone Number</label>
                            <input type="text" name="telephone_number" value="<?php echo htmlspecialchars($employee['telephone_number']); ?>" placeholder="02-1234567">
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-envelope"></i> Email</label>
                            <input type="email" name="email" value="<?php echo htmlspecialchars($employee['email']); ?>" placeholder="employee@company.com">
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-home"></i> Present Address</label>
                            <textarea name="present_address"><?php echo htmlspecialchars($employee['present_address']); ?></textarea>
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-home"></i> Permanent Address</label>
                            <textarea name="permanent_address"><?php echo htmlspecialchars($employee['permanent_address']); ?></textarea>
                        </div>
                    </div>
                </div>

                <!-- Employment Details -->
                <div class="form-section">
                    <h2><i class="fas fa-briefcase"></i> Employment Details</h2>
                    <div class="form-grid">
                        <div class="form-group">
                            <label><i class="fas fa-user-tie"></i> Position</label>
                            <input type="text" name="position" value="<?php echo htmlspecialchars($employee['position']); ?>">
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-building"></i> Department</label>
                            <input type="text" name="department" value="<?php echo htmlspecialchars($employee['department']); ?>">
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-code-branch"></i> Branch</label>
                            <input type="text" name="branch" value="<?php echo htmlspecialchars($employee['branch']); ?>">
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-building"></i> Company</label>
                            <input type="text" name="company" value="<?php echo htmlspecialchars($employee['company']); ?>">
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-clock"></i> Employment Status</label>
                            <select name="employment_status">
                                <option value="">Select Status</option>
                                <option value="Full-time" <?php echo $employee['employment_status'] == 'Full-time' ? 'selected' : ''; ?>>Full-time</option>
                                <option value="Part-time" <?php echo $employee['employment_status'] == 'Part-time' ? 'selected' : ''; ?>>Part-time</option>
                                <option value="Contractual" <?php echo $employee['employment_status'] == 'Contractual' ? 'selected' : ''; ?>>Contractual</option>
                                <option value="Probationary" <?php echo $employee['employment_status'] == 'Probationary' ? 'selected' : ''; ?>>Probationary</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-calendar-check"></i> Date Hired</label>
                            <input type="date" name="date_hired" value="<?php echo $employee['date_hired']; ?>">
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-money-bill"></i> Basic Salary</label>
                            <input type="number" step="0.01" name="basic_salary" value="<?php echo $employee['basic_salary']; ?>">
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-chart-line"></i> Rate</label>
                            <input type="number" step="0.01" name="rate" value="<?php echo $employee['rate']; ?>">
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-gift"></i> Allowances</label>
                            <input type="number" step="0.01" name="allowances" value="<?php echo $employee['allowances']; ?>">
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-calendar-alt"></i> Pay Frequency</label>
                            <select name="pay_frequency">
                                <option value="">Select Frequency</option>
                                <option value="Daily" <?php echo $employee['pay_frequency'] == 'Daily' ? 'selected' : ''; ?>>Daily</option>
                                <option value="Weekly" <?php echo $employee['pay_frequency'] == 'Weekly' ? 'selected' : ''; ?>>Weekly</option>
                                <option value="Bi-weekly" <?php echo $employee['pay_frequency'] == 'Bi-weekly' ? 'selected' : ''; ?>>Bi-weekly</option>
                                <option value="Monthly" <?php echo $employee['pay_frequency'] == 'Monthly' ? 'selected' : ''; ?>>Monthly</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Government IDs -->
                <div class="form-section">
                    <h2><i class="fas fa-id-card"></i> Government IDs</h2>
                    <div class="form-grid">
                        <div class="form-group">
                            <label><i class="fas fa-id-card"></i> SSS No.</label>
                            <input type="text" name="sss_no" value="<?php echo htmlspecialchars($employee['sss_no']); ?>">
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-id-card"></i> TIN</label>
                            <input type="text" name="tin" value="<?php echo htmlspecialchars($employee['tin']); ?>">
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-id-card"></i> Pag-ibig MID</label>
                            <input type="text" name="pagibig_mid" value="<?php echo htmlspecialchars($employee['pagibig_mid']); ?>">
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-id-card"></i> Philhealth ID No.</label>
                            <input type="text" name="philhealth_no" value="<?php echo htmlspecialchars($employee['philhealth_no']); ?>">
                        </div>
                    </div>
                </div>

                <!-- Family Background -->
                <div class="form-section">
                    <h2><i class="fas fa-users"></i> Family Background</h2>
                    <div class="form-grid">
                        <div class="form-group">
                            <label><i class="fas fa-user"></i> Name of Spouse</label>
                            <input type="text" name="spouse_name" value="<?php echo htmlspecialchars($employee['spouse_name']); ?>">
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-briefcase"></i> Spouse's Occupation</label>
                            <input type="text" name="spouse_occupation" value="<?php echo htmlspecialchars($employee['spouse_occupation']); ?>">
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-user"></i> Father's Name</label>
                            <input type="text" name="father_name" value="<?php echo htmlspecialchars($employee['father_name']); ?>">
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-briefcase"></i> Father's Occupation</label>
                            <input type="text" name="father_occupation" value="<?php echo htmlspecialchars($employee['father_occupation']); ?>">
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-user"></i> Mother's Name</label>
                            <input type="text" name="mother_name" value="<?php echo htmlspecialchars($employee['mother_name']); ?>">
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-briefcase"></i> Mother's Occupation</label>
                            <input type="text" name="mother_occupation" value="<?php echo htmlspecialchars($employee['mother_occupation']); ?>">
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-users"></i> Number of Siblings</label>
                            <input type="number" name="number_of_siblings" value="<?php echo $employee['number_of_siblings']; ?>">
                        </div>
                    </div>
                </div>

                <!-- Relative Information -->
                <div class="form-section">
                    <h2><i class="fas fa-handshake"></i> Relative Information</h2>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Are you related to someone working for us?</label>
                            <select name="relative_working">
                                <option value="">Select</option>
                                <option value="Yes" <?php echo $employee['relative_working'] == 'Yes' ? 'selected' : ''; ?>>Yes</option>
                                <option value="No" <?php echo $employee['relative_working'] == 'No' ? 'selected' : ''; ?>>No</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>If yes, give name(s) and relationship(s)</label>
                            <textarea name="relative_details"><?php echo htmlspecialchars($employee['relative_details']); ?></textarea>
                        </div>
                    </div>
                </div>

                <!-- Emergency Contact -->
                <div class="form-section">
                    <h2><i class="fas fa-phone-alt"></i> Emergency Contact</h2>
                    <div class="form-grid">
                        <div class="form-group">
                            <label><i class="fas fa-user"></i> Name</label>
                            <input type="text" name="emergency_name" value="<?php echo htmlspecialchars($employee['emergency_name']); ?>">
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-map-marker-alt"></i> Address</label>
                            <textarea name="emergency_address"><?php echo htmlspecialchars($employee['emergency_address']); ?></textarea>
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-phone"></i> Contact Number</label>
                            <input type="text" name="emergency_contact" value="<?php echo htmlspecialchars($employee['emergency_contact']); ?>">
                        </div>
                    </div>
                </div>

                <!-- Photo -->
                <div class="form-section">
                    <h2><i class="fas fa-camera"></i> Photo</h2>
                    <?php if (!empty($employee['photo_url']) && file_exists($employee['photo_url'])): ?>
                        <div class="current-photo">
                            <label>Current Photo:</label>
                            <img src="<?php echo $employee['photo_url']; ?>" alt="Current Photo">
                        </div>
                    <?php endif; ?>
                    <div class="form-group">
                        <label><i class="fas fa-upload"></i> Upload New Photo</label>
                        <input type="file" name="photo" accept="image/*" onchange="previewImage(this)">
                        <img id="photoPreview" class="photo-preview" src="#" alt="New Photo Preview" style="display: none;">
                    </div>
                </div>

                <div class="form-actions">
                    <a href="view_employee.php?id=<?php echo $id; ?>" class="btn btn-secondary">
                        <i class="fas fa-eye"></i> View
                    </a>
                    <a href="employees.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                    <button type="submit" class="btn btn-primary" id="submitBtn">
                        <i class="fas fa-save"></i> Update Employee
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
    function previewImage(input) {
        let preview = document.getElementById('photoPreview');
        if (input.files && input.files[0]) {
            let reader = new FileReader();
            reader.onload = function(e) {
                preview.src = e.target.result;
                preview.style.display = 'block';
            }
            reader.readAsDataURL(input.files[0]);
        } else {
            preview.style.display = 'none';
            preview.src = '#';
        }
    }
    
    function validateForm() {
        let firstName = document.getElementById('first_name').value.trim();
        let lastName = document.getElementById('last_name').value.trim();
        let submitBtn = document.getElementById('submitBtn');
        
        if (firstName === '') {
            alert('Please enter First Name');
            document.getElementById('first_name').focus();
            return false;
        }
        
        if (lastName === '') {
            alert('Please enter Last Name');
            document.getElementById('last_name').focus();
            return false;
        }
        
        // Disable button to prevent double submission
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Updating...';
        
        return true;
    }
    </script>
</body>
</html>