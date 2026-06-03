<?php
// setup.php - Run this ONCE to set up the database, tables, and admin user
// Visit: http://localhost/setup.php

// Don't use config.php here because the database may not exist yet
session_start();

// Load .env if it exists
function loadEnvSetup($path) {
    if (!file_exists($path)) return;
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if (strpos($line, '#') === 0 || empty($line)) continue;
        if (strpos($line, '=') === false) continue;
        list($key, $value) = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);
        if (!array_key_exists($key, $_ENV)) {
            $_ENV[$key] = $value;
            putenv("$key=$value");
        }
    }
}

loadEnvSetup(__DIR__ . '/.env');

$host     = getenv('DB_HOST') ?: 'localhost';
$username = getenv('DB_USERNAME') ?: 'root';
$password = getenv('DB_PASSWORD') ?: '';
$database = getenv('DB_NAME') ?: 'emp';
$admin_password = getenv('ADMIN_PASSWORD') ?: 'admin1234';

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Setup</title>";
echo "<style>body{font-family:Arial,sans-serif;max-width:700px;margin:40px auto;padding:20px;background:#f5f5f5;}";
echo "h1{color:#d81919;} .ok{color:green;font-weight:bold;} .err{color:red;font-weight:bold;}";
echo "pre{background:#222;color:#0f0;padding:15px;border-radius:8px;overflow-x:auto;}";
echo ".btn{display:inline-block;padding:12px 24px;background:#d81919;color:white;text-decoration:none;border-radius:8px;font-weight:bold;margin-top:10px;}</style>";
echo "</head><body>";
echo "<h1>Employee Directory Setup</h1>";

// Step 1: Connect to MySQL (without database)
$conn = @new mysqli($host, $username, $password);
if ($conn->connect_error) {
    echo "<p class='err'>Cannot connect to MySQL: " . $conn->connect_error . "</p>";
    echo "<p>Check your MySQL is running (XAMPP Control Panel → MySQL → Start)</p>";
    echo "<p>Current settings: host=<b>$host</b>, user=<b>$username</b>, password=<b>" . (empty($password) ? '(empty)' : '****') . "</b></p>";
    echo "</body></html>";
    exit();
}
echo "<p class='ok'>1. Connected to MySQL server</p>";

// Step 2: Create database if not exists
$conn->query("CREATE DATABASE IF NOT EXISTS `$database` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
$conn->select_db($database);
if ($conn->error) {
    echo "<p class='err'>Failed to create/select database '$database': " . $conn->error . "</p>";
    echo "</body></html>";
    exit();
}
echo "<p class='ok'>2. Database '$database' ready</p>";

// Step 3: Create tables
$tables_sql = [
    'users' => "CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        role ENUM('admin', 'user') DEFAULT 'user',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    'employees' => "CREATE TABLE IF NOT EXISTS employees (
        id INT AUTO_INCREMENT PRIMARY KEY,
        employee_id VARCHAR(50) UNIQUE,
        first_name VARCHAR(100),
        middle_name VARCHAR(100),
        last_name VARCHAR(100),
        full_name VARCHAR(300),
        nickname VARCHAR(50),
        date_of_birth DATE NULL,
        age INT DEFAULT 0,
        place_of_birth VARCHAR(200),
        gender VARCHAR(20) DEFAULT 'Male',
        mobile_number VARCHAR(20),
        telephone_number VARCHAR(20),
        email VARCHAR(254),
        present_address TEXT,
        permanent_address TEXT,
        civil_status VARCHAR(20),
        religion VARCHAR(50),
        position VARCHAR(100),
        department VARCHAR(100),
        branch VARCHAR(100),
        company VARCHAR(100),
        employment_status VARCHAR(50),
        basic_salary DECIMAL(12,2) DEFAULT 0.00,
        rate DECIMAL(12,2) DEFAULT 0.00,
        allowances DECIMAL(12,2) DEFAULT 0.00,
        pay_frequency VARCHAR(20),
        sss_no VARCHAR(50),
        tin VARCHAR(50),
        pagibig_mid VARCHAR(50),
        philhealth_no VARCHAR(50),
        spouse_name VARCHAR(100),
        spouse_occupation VARCHAR(100),
        father_name VARCHAR(100),
        father_occupation VARCHAR(100),
        mother_name VARCHAR(100),
        mother_occupation VARCHAR(100),
        number_of_siblings INT DEFAULT 0,
        relative_working VARCHAR(10),
        relative_details TEXT,
        emergency_name VARCHAR(100),
        emergency_address TEXT,
        emergency_contact VARCHAR(50),
        date_hired DATE NULL,
        photo_url VARCHAR(500),
        qr_password VARCHAR(255) DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    'activity_logs' => "CREATE TABLE IF NOT EXISTS activity_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT,
        action VARCHAR(100),
        details TEXT,
        ip_address VARCHAR(45),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
];

$all_ok = true;
foreach ($tables_sql as $name => $sql) {
    if ($conn->query($sql)) {
        echo "<p class='ok'>3. Table '$name' ready</p>";
    } else {
        echo "<p class='err'>Failed to create table '$name': " . $conn->error . "</p>";
        $all_ok = false;
    }
}

// Step 4: Add qr_password column if it doesn't exist (for upgrades)
$result = $conn->query("SHOW COLUMNS FROM employees LIKE 'qr_password'");
if ($result && $result->num_rows == 0) {
    $conn->query("ALTER TABLE employees ADD COLUMN qr_password VARCHAR(255) DEFAULT NULL AFTER photo_url");
    echo "<p class='ok'>4. Added qr_password column to employees table</p>";
} else {
    echo "<p class='ok'>4. qr_password column already exists</p>";
}

// Step 5: Create or update admin user
$hashed_password = password_hash($admin_password, PASSWORD_DEFAULT);
$stmt = $conn->prepare("SELECT id FROM users WHERE username = 'admin'");
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $update = $conn->prepare("UPDATE users SET password = ?, role = 'admin' WHERE username = 'admin'");
    $update->bind_param("s", $hashed_password);
    if ($update->execute()) {
        echo "<p class='ok'>5. Admin password updated</p>";
    }
    $update->close();
} else {
    $insert = $conn->prepare("INSERT INTO users (username, password, role) VALUES ('admin', ?, 'admin')");
    $insert->bind_param("s", $hashed_password);
    if ($insert->execute()) {
        echo "<p class='ok'>5. Admin user created</p>";
    }
    $insert->close();
}
$stmt->close();

// Verify password works
$verify_stmt = $conn->prepare("SELECT password FROM users WHERE username = 'admin'");
$verify_stmt->execute();
$verify_result = $verify_stmt->get_result();
$verify_row = $verify_result->fetch_assoc();
$password_works = password_verify($admin_password, $verify_row['password']);
$verify_stmt->close();

echo "<hr>";
echo "<h2>Setup Complete!</h2>";
echo "<p><b>Database:</b> $database</p>";
echo "<p><b>Admin Username:</b> admin</p>";
echo "<p><b>Admin Password:</b> $admin_password</p>";
echo "<p><b>Password Verification:</b> " . ($password_works ? "<span class='ok'>Working</span>" : "<span class='err'>FAILED</span>") . "</p>";
echo "<br>";
echo "<a class='btn' href='login.php'>Go to Login Page</a>";
echo "</body></html>";

$conn->close();
?>
