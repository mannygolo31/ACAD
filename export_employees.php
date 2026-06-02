<?php
// export_employees.php - Export employees to CSV/Excel
require_once 'config.php';
requireLogin();

// Get all employees
$result = $conn->query("SELECT * FROM employees ORDER BY id ASC");

if (!$result || $result->num_rows == 0) {
    header("Location: employees.php");
    exit();
}

// Set headers for CSV download
$filename = 'employees_' . date('Y-m-d_His') . '.csv';
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$output = fopen('php://output', 'w');

// Add BOM for Excel UTF-8 compatibility
fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

// CSV header row
$headers = [
    'Employee ID',
    'First Name',
    'Middle Name',
    'Last Name',
    'Full Name',
    'Nickname',
    'Date of Birth',
    'Age',
    'Place of Birth',
    'Gender',
    'Mobile Number',
    'Telephone Number',
    'Email',
    'Present Address',
    'Permanent Address',
    'Civil Status',
    'Religion',
    'Position',
    'Department',
    'Branch',
    'Company',
    'Employment Status',
    'Basic Salary',
    'Rate',
    'Allowances',
    'Pay Frequency',
    'SSS No',
    'TIN',
    'Pag-IBIG MID',
    'PhilHealth No',
    'Spouse Name',
    'Spouse Occupation',
    'Father Name',
    'Father Occupation',
    'Mother Name',
    'Mother Occupation',
    'Number of Siblings',
    'Relative Working',
    'Relative Details',
    'Emergency Name',
    'Emergency Address',
    'Emergency Contact',
    'Date Hired',
    'QR Password'
];

fputcsv($output, $headers);

// Data rows
while ($row = $result->fetch_assoc()) {
    $full_name = $row['full_name'];
    if (empty($full_name)) {
        $full_name = trim($row['first_name'] . ' ' . $row['middle_name'] . ' ' . $row['last_name']);
    }

    $data = [
        $row['employee_id'],
        $row['first_name'],
        $row['middle_name'],
        $row['last_name'],
        $full_name,
        $row['nickname'],
        $row['date_of_birth'],
        $row['age'],
        $row['place_of_birth'],
        $row['gender'],
        $row['mobile_number'],
        $row['telephone_number'],
        $row['email'],
        $row['present_address'],
        $row['permanent_address'],
        $row['civil_status'],
        $row['religion'],
        $row['position'],
        $row['department'],
        $row['branch'],
        $row['company'],
        $row['employment_status'],
        $row['basic_salary'],
        $row['rate'],
        $row['allowances'],
        $row['pay_frequency'],
        $row['sss_no'],
        $row['tin'],
        $row['pagibig_mid'],
        $row['philhealth_no'],
        $row['spouse_name'],
        $row['spouse_occupation'],
        $row['father_name'],
        $row['father_occupation'],
        $row['mother_name'],
        $row['mother_occupation'],
        $row['number_of_siblings'],
        $row['relative_working'],
        $row['relative_details'],
        $row['emergency_name'],
        $row['emergency_address'],
        $row['emergency_contact'],
        $row['date_hired'],
        !empty($row['qr_password']) ? $row['qr_password'] : $row['last_name'] . 'North'
    ];

    fputcsv($output, $data);
}

fclose($output);
logActivity($_SESSION['user_id'], 'Export Employees', 'Exported employees data to CSV');
exit();
?>
