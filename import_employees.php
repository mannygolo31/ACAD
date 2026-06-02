<?php
// import_employees.php - Import employees from CSV
require_once 'config.php';
requireLogin();

$success = '';
$error = '';
$imported = 0;
$skipped = 0;
$errors_list = [];
$csrf_token = generateCSRFToken();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $error = 'Invalid request.';
    } elseif (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
        $error = 'Please select a valid CSV file to upload.';
    } else {
        $file = $_FILES['csv_file'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if ($ext !== 'csv') {
            $error = 'Only CSV files are allowed.';
        } elseif ($file['size'] > 10485760) {
            $error = 'File is too large. Maximum size: 10MB.';
        } else {
            $handle = fopen($file['tmp_name'], 'r');
            if (!$handle) {
                $error = 'Could not read the uploaded file.';
            } else {
                // Read header row
                $header = fgetcsv($handle);
                if (!$header) {
                    $error = 'CSV file is empty.';
                    fclose($handle);
                } else {
                    // Normalize header names
                    $header = array_map(function($h) {
                        return strtolower(trim(str_replace(['-', ' '], '_', $h)));
                    }, $header);

                    // Map CSV headers to database columns
                    $column_map = [
                        'employee_id' => 'employee_id',
                        'first_name' => 'first_name',
                        'middle_name' => 'middle_name',
                        'last_name' => 'last_name',
                        'full_name' => 'full_name',
                        'nickname' => 'nickname',
                        'date_of_birth' => 'date_of_birth',
                        'age' => 'age',
                        'place_of_birth' => 'place_of_birth',
                        'gender' => 'gender',
                        'mobile_number' => 'mobile_number',
                        'telephone_number' => 'telephone_number',
                        'email' => 'email',
                        'present_address' => 'present_address',
                        'permanent_address' => 'permanent_address',
                        'civil_status' => 'civil_status',
                        'religion' => 'religion',
                        'position' => 'position',
                        'department' => 'department',
                        'branch' => 'branch',
                        'company' => 'company',
                        'employment_status' => 'employment_status',
                        'basic_salary' => 'basic_salary',
                        'rate' => 'rate',
                        'allowances' => 'allowances',
                        'pay_frequency' => 'pay_frequency',
                        'sss_no' => 'sss_no',
                        'tin' => 'tin',
                        'pag_ibig_mid' => 'pagibig_mid',
                        'pagibig_mid' => 'pagibig_mid',
                        'philhealth_no' => 'philhealth_no',
                        'spouse_name' => 'spouse_name',
                        'spouse_occupation' => 'spouse_occupation',
                        'father_name' => 'father_name',
                        'father_occupation' => 'father_occupation',
                        'mother_name' => 'mother_name',
                        'mother_occupation' => 'mother_occupation',
                        'number_of_siblings' => 'number_of_siblings',
                        'relative_working' => 'relative_working',
                        'relative_details' => 'relative_details',
                        'emergency_name' => 'emergency_name',
                        'emergency_address' => 'emergency_address',
                        'emergency_contact' => 'emergency_contact',
                        'date_hired' => 'date_hired',
                        'qr_password' => 'qr_password',
                    ];

                    // Build index map
                    $index_map = [];
                    foreach ($header as $i => $col) {
                        if (isset($column_map[$col])) {
                            $index_map[$column_map[$col]] = $i;
                        }
                    }

                    // Check minimum required columns
                    if (!isset($index_map['first_name']) && !isset($index_map['full_name'])) {
                        $error = 'CSV must have at least a "First Name" or "Full Name" column.';
                        fclose($handle);
                    } else {
                        $row_num = 1;
                        while (($data = fgetcsv($handle)) !== false) {
                            $row_num++;

                            // Get field value by column name
                            $get = function($col) use ($index_map, $data) {
                                if (!isset($index_map[$col]) || !isset($data[$index_map[$col]])) return '';
                                return trim($data[$index_map[$col]]);
                            };

                            $first_name = sanitize($get('first_name'));
                            $middle_name = sanitize($get('middle_name'));
                            $last_name = sanitize($get('last_name'));
                            $full_name_csv = sanitize($get('full_name'));

                            // Skip empty rows
                            if (empty($first_name) && empty($last_name) && empty($full_name_csv)) {
                                $skipped++;
                                continue;
                            }

                            // Build full name if not provided
                            $full_name = !empty($full_name_csv) ? $full_name_csv : trim($first_name . ' ' . $middle_name . ' ' . $last_name);

                            $employee_id = sanitize($get('employee_id'));
                            $nickname = sanitize($get('nickname'));
                            $date_of_birth = $get('date_of_birth');
                            $age_val = $get('age');
                            $age = is_numeric($age_val) ? intval($age_val) : 0;

                            // Calculate age from DOB if age not provided
                            if ($age == 0 && !empty($date_of_birth)) {
                                $dob = new DateTime($date_of_birth);
                                $now = new DateTime();
                                $age = $now->diff($dob)->y;
                            }

                            $place_of_birth = sanitize($get('place_of_birth'));
                            $gender = sanitize($get('gender')) ?: 'Male';
                            $mobile_number = sanitize($get('mobile_number'));
                            $telephone_number = sanitize($get('telephone_number'));
                            $email = sanitize($get('email'));
                            $present_address = sanitize($get('present_address'));
                            $permanent_address = sanitize($get('permanent_address'));
                            $civil_status = sanitize($get('civil_status'));
                            $religion = sanitize($get('religion'));
                            $position = sanitize($get('position'));
                            $department = sanitize($get('department'));
                            $branch = sanitize($get('branch'));
                            $company = sanitize($get('company'));
                            $employment_status = sanitize($get('employment_status'));
                            $basic_salary = is_numeric($get('basic_salary')) ? floatval($get('basic_salary')) : 0;
                            $rate = is_numeric($get('rate')) ? floatval($get('rate')) : 0;
                            $allowances = is_numeric($get('allowances')) ? floatval($get('allowances')) : 0;
                            $pay_frequency = sanitize($get('pay_frequency'));
                            $sss_no = sanitize($get('sss_no'));
                            $tin = sanitize($get('tin'));
                            $pagibig_mid = sanitize($get('pagibig_mid'));
                            $philhealth_no = sanitize($get('philhealth_no'));
                            $spouse_name = sanitize($get('spouse_name'));
                            $spouse_occupation = sanitize($get('spouse_occupation'));
                            $father_name = sanitize($get('father_name'));
                            $father_occupation = sanitize($get('father_occupation'));
                            $mother_name = sanitize($get('mother_name'));
                            $mother_occupation = sanitize($get('mother_occupation'));
                            $number_of_siblings = is_numeric($get('number_of_siblings')) ? intval($get('number_of_siblings')) : 0;
                            $relative_working = sanitize($get('relative_working'));
                            $relative_details = sanitize($get('relative_details'));
                            $emergency_name = sanitize($get('emergency_name'));
                            $emergency_address = sanitize($get('emergency_address'));
                            $emergency_contact = sanitize($get('emergency_contact'));
                            $date_hired = $get('date_hired');
                            $qr_password_val = $get('qr_password');
                            $qr_password = !empty($qr_password_val) ? sanitize($qr_password_val) : $last_name . 'North';

                            // Auto-generate employee_id if empty
                            if (empty($employee_id)) {
                                $employee_id = 'EMP' . date('Y') . str_pad(rand(1, 99999), 5, '0', STR_PAD_LEFT);
                            }

                            // Check for duplicate employee_id
                            $check = $conn->prepare("SELECT id FROM employees WHERE employee_id = ?");
                            $check->bind_param("s", $employee_id);
                            $check->execute();
                            if ($check->get_result()->num_rows > 0) {
                                $errors_list[] = "Row $row_num: Employee ID '$employee_id' already exists — skipped.";
                                $skipped++;
                                $check->close();
                                continue;
                            }
                            $check->close();

                            $sql = "INSERT INTO employees (
                                employee_id, first_name, middle_name, last_name, full_name, nickname,
                                date_of_birth, age, place_of_birth, gender, mobile_number, telephone_number,
                                email, present_address, permanent_address, civil_status, religion,
                                position, department, branch, company, employment_status,
                                basic_salary, rate, allowances, pay_frequency,
                                sss_no, tin, pagibig_mid, philhealth_no,
                                spouse_name, spouse_occupation, father_name, father_occupation,
                                mother_name, mother_occupation, number_of_siblings,
                                relative_working, relative_details,
                                emergency_name, emergency_address, emergency_contact,
                                date_hired, qr_password
                            ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";

                            $ins = $conn->prepare($sql);
                            if ($ins) {
                                $ins->bind_param(
                                    "sssssssissssssssssssssdddssssssssssssisssssss",
                                    $employee_id, $first_name, $middle_name, $last_name, $full_name, $nickname,
                                    $date_of_birth, $age, $place_of_birth, $gender, $mobile_number, $telephone_number,
                                    $email, $present_address, $permanent_address, $civil_status, $religion,
                                    $position, $department, $branch, $company, $employment_status,
                                    $basic_salary, $rate, $allowances, $pay_frequency,
                                    $sss_no, $tin, $pagibig_mid, $philhealth_no,
                                    $spouse_name, $spouse_occupation, $father_name, $father_occupation,
                                    $mother_name, $mother_occupation, $number_of_siblings,
                                    $relative_working, $relative_details,
                                    $emergency_name, $emergency_address, $emergency_contact,
                                    $date_hired, $qr_password
                                );

                                if ($ins->execute()) {
                                    $imported++;
                                } else {
                                    $errors_list[] = "Row $row_num: " . $ins->error;
                                    $skipped++;
                                }
                                $ins->close();
                            } else {
                                $errors_list[] = "Row $row_num: " . $conn->error;
                                $skipped++;
                            }
                        }
                        fclose($handle);

                        if ($imported > 0) {
                            $success = "Successfully imported $imported employee(s).";
                            if ($skipped > 0) {
                                $success .= " Skipped $skipped row(s).";
                            }
                            logActivity($_SESSION['user_id'], 'Import Employees', "Imported $imported employees from CSV");
                        } else {
                            $error = "No employees were imported. $skipped row(s) skipped.";
                        }
                    }
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Import Employees - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Futura', 'Helvetica Neue', Arial, sans-serif; background: #f5f5f5; display: flex; }
        .sidebar { width: 280px; background: linear-gradient(135deg, #d81919 0%, #555555 100%); color: white; height: 100vh; position: fixed; left: 0; top: 0; overflow-y: auto; }
        .sidebar-header { padding: 30px 20px; text-align: center; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .sidebar-header h2 { font-size: 24px; margin-bottom: 5px; }
        .sidebar-header p { font-size: 14px; opacity: 0.8; }
        .sidebar-menu { padding: 20px 0; }
        .menu-item { padding: 15px 25px; display: flex; align-items: center; color: white; text-decoration: none; transition: all 0.3s; }
        .menu-item i { width: 25px; margin-right: 10px; }
        .menu-item:hover, .menu-item.active { background: rgba(255,255,255,0.2); }
        .menu-item.logout { position: absolute; bottom: 20px; width: 100%; border-top: 1px solid rgba(255,255,255,0.1); }
        .main-content { margin-left: 280px; padding: 30px; width: calc(100% - 280px); }
        .header { background: white; padding: 20px 30px; border-radius: 15px; margin-bottom: 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); display: flex; justify-content: space-between; align-items: center; }
        .header h1 { color: #333; font-size: 24px; }
        .btn { padding: 10px 20px; border: none; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 5px; transition: all 0.3s; }
        .btn-primary { background: linear-gradient(135deg, #d81919 0%, #a01414 100%); color: white; }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(216,25,25,0.3); }
        .btn-secondary { background: #555555; color: white; }
        .card { background: white; border-radius: 15px; padding: 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 20px; }
        .card h2 { color: #333; margin-bottom: 15px; }
        .card p { color: #666; margin-bottom: 15px; line-height: 1.6; }
        .upload-area { border: 3px dashed #e0e0e0; border-radius: 15px; padding: 40px; text-align: center; margin: 20px 0; transition: all 0.3s; }
        .upload-area:hover { border-color: #d81919; }
        .upload-area i { font-size: 48px; color: #ccc; margin-bottom: 15px; }
        .upload-area p { color: #999; }
        input[type="file"] { margin: 15px 0; }
        .success-msg { background: #e8f5e9; color: #2e7d32; padding: 15px 20px; border-radius: 10px; margin-bottom: 20px; border-left: 4px solid #4caf50; }
        .error-msg { background: #ffebee; color: #c62828; padding: 15px 20px; border-radius: 10px; margin-bottom: 20px; border-left: 4px solid #f44336; }
        .errors-list { background: #fff3e0; color: #e65100; padding: 15px 20px; border-radius: 10px; margin-top: 10px; border-left: 4px solid #ff9800; }
        .errors-list ul { margin-left: 20px; }
        .template-info { background: #e3f2fd; padding: 20px; border-radius: 10px; margin-top: 20px; }
        .template-info h3 { color: #1565c0; margin-bottom: 10px; }
        .template-info code { background: #bbdefb; padding: 2px 6px; border-radius: 4px; font-size: 13px; }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-header">
            <h2><i class="fas fa-shield-alt"></i> NSIAI</h2>
            <p>Employee Management System</p>
        </div>
        <div class="sidebar-menu">
            <a href="dashboard.php" class="menu-item"><i class="fas fa-home"></i> Dashboard</a>
            <a href="employees.php" class="menu-item active"><i class="fas fa-users"></i> Employees</a>
            <a href="add_employee.php" class="menu-item"><i class="fas fa-user-plus"></i> Add Employee</a>
            <a href="qr_codes.php" class="menu-item"><i class="fas fa-qrcode"></i> QR Codes</a>
            <a href="records.php" class="menu-item"><i class="fas fa-table"></i> Records</a>
            <a href="reset_password.php" class="menu-item"><i class="fas fa-key"></i> Reset Password</a>
            <a href="logout.php" class="menu-item logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>

    <div class="main-content">
        <div class="header">
            <h1><i class="fas fa-file-import"></i> Import Employees</h1>
            <a href="employees.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back to Employees</a>
        </div>

        <?php if (!empty($success)): ?>
            <div class="success-msg"><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="error-msg"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if (!empty($errors_list)): ?>
            <div class="errors-list">
                <strong><i class="fas fa-exclamation-triangle"></i> Import Warnings:</strong>
                <ul>
                    <?php foreach ($errors_list as $err): ?>
                        <li><?php echo htmlspecialchars($err); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <div class="card">
            <h2><i class="fas fa-upload"></i> Upload CSV File</h2>
            <p>Upload a CSV file containing employee data. The first row must be the header row with column names.</p>

            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                <div class="upload-area">
                    <i class="fas fa-cloud-upload-alt"></i>
                    <p>Select a CSV file to import</p>
                    <input type="file" name="csv_file" accept=".csv" required>
                </div>
                <button type="submit" class="btn btn-primary"><i class="fas fa-file-import"></i> Import Employees</button>
                <a href="export_employees.php" class="btn btn-secondary" style="margin-left:10px;"><i class="fas fa-download"></i> Download Template (Export Current)</a>
            </form>
        </div>

        <div class="card">
            <div class="template-info">
                <h3><i class="fas fa-info-circle"></i> CSV Format Guide</h3>
                <p>Your CSV file should have these column headers (order doesn't matter, all optional except First Name or Full Name):</p>
                <p>
                    <code>Employee ID</code>, <code>First Name</code>, <code>Middle Name</code>, <code>Last Name</code>,
                    <code>Full Name</code>, <code>Nickname</code>, <code>Date of Birth</code>, <code>Age</code>,
                    <code>Gender</code>, <code>Email</code>, <code>Mobile Number</code>, <code>Position</code>,
                    <code>Department</code>, <code>Branch</code>, <code>Company</code>, <code>Employment Status</code>,
                    <code>Basic Salary</code>, <code>Date Hired</code>, <code>QR Password</code>
                </p>
                <p style="margin-top:10px; font-size:13px; color:#666;">
                    <strong>Tip:</strong> Use the "Export" button on the Employees page to download a template CSV with all columns.
                </p>
            </div>
        </div>
    </div>
</body>
</html>
