<?php
// setup.php - Run this once to set up the admin user
require_once 'config.php';

echo "<h1>Employee Directory Setup</h1>";

// Check if users table exists
$result = $conn->query("SHOW TABLES LIKE 'users'");
if ($result->num_rows == 0) {
    echo "<p style='color: red;'>Users table does not exist. Please create the database first.</p>";
    echo "<p>Run this SQL:</p>";
    echo "<pre>
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'user') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
    </pre>";
    exit();
}

// Check if admin user exists
$stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
$admin_user = 'admin';
$stmt->bind_param("s", $admin_user);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    // Update existing admin password
    $hashed_password = password_hash(ADMIN_PASSWORD, PASSWORD_DEFAULT);
    $update = $conn->prepare("UPDATE users SET password = ? WHERE username = ?");
    $update->bind_param("ss", $hashed_password, $admin_user);
    
    if ($update->execute()) {
        echo "<p style='color: green;'>✓ Admin password updated successfully!</p>";
    } else {
        echo "<p style='color: red;'>✗ Failed to update admin password: " . $conn->error . "</p>";
    }
    $update->close();
} else {
    // Create new admin user
    $hashed_password = password_hash(ADMIN_PASSWORD, PASSWORD_DEFAULT);
    $insert = $conn->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, 'admin')");
    $insert->bind_param("ss", $admin_user, $hashed_password);
    
    if ($insert->execute()) {
        echo "<p style='color: green;'>✓ Admin user created successfully!</p>";
    } else {
        echo "<p style='color: red;'>✗ Failed to create admin user: " . $conn->error . "</p>";
    }
    $insert->close();
}
$stmt->close();

// Display login info
echo "<h2>Login Information</h2>";
echo "<ul>";
echo "<li><strong>Username:</strong> admin</li>";
echo "<li><strong>Password:</strong> " . ADMIN_PASSWORD . "</li>";
echo "<li><strong>Login URL:</strong> <a href='login.php'>" . BASE_URL . "/login.php</a></li>";
echo "</ul>";

// Test the password
echo "<h2>Password Test</h2>";
$test_password = ADMIN_PASSWORD;
$test_hash = password_hash($test_password, PASSWORD_DEFAULT);
echo "<p>Test hash for '$test_password': " . $test_hash . "</p>";
echo "<p>Verification test: " . (password_verify($test_password, $test_hash) ? "✓ Working" : "✗ Failed") . "</p>";

echo "<hr>";
echo "<p><a href='login.php' style='padding: 10px 20px; background: #667eea; color: white; text-decoration: none; border-radius: 5px;'>Go to Login Page →</a></p>";
?>