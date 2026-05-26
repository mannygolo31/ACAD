<?php
// test_login.php - Run this to test your login system
require_once 'config.php';

echo "<h1>Login System Test</h1>";

// Test 1: Check database connection
echo "<h2>1. Database Connection</h2>";
if ($conn->ping()) {
    echo "<p style='color: green;'>✓ Database connected successfully</p>";
} else {
    echo "<p style='color: red;'>✗ Database connection failed</p>";
}

// Test 2: Check users table
echo "<h2>2. Users Table</h2>";
$result = $conn->query("SHOW TABLES LIKE 'users'");
if ($result->num_rows > 0) {
    echo "<p style='color: green;'>✓ Users table exists</p>";
    
    // Show table structure
    $columns = $conn->query("DESCRIBE users");
    echo "<p>Table structure:</p>";
    echo "<ul>";
    while ($col = $columns->fetch_assoc()) {
        echo "<li>" . $col['Field'] . " - " . $col['Type'] . "</li>";
    }
    echo "</ul>";
} else {
    echo "<p style='color: red;'>✗ Users table does not exist</p>";
    echo "<p>Run this SQL to create the table:</p>";
    echo "<pre>
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'user') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
    </pre>";
}

// Test 3: Check admin user
echo "<h2>3. Admin User</h2>";
$stmt = $conn->prepare("SELECT id, username, password, role FROM users WHERE username = ?");
$admin = 'admin';
$stmt->bind_param("s", $admin);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
    echo "<p style='color: green;'>✓ Admin user found</p>";
    echo "<ul>";
    echo "<li>User ID: " . $user['id'] . "</li>";
    echo "<li>Username: " . $user['username'] . "</li>";
    echo "<li>Role: " . $user['role'] . "</li>";
    echo "<li>Password Hash: " . substr($user['password'], 0, 20) . "...</li>";
    echo "</ul>";
    
    // Test password verification
    $test_password = ADMIN_PASSWORD;
    if (password_verify($test_password, $user['password'])) {
        echo "<p style='color: green;'>✓ Password 'northcom' verifies correctly</p>";
    } else {
        echo "<p style='color: red;'>✗ Password 'northcom' does NOT verify</p>";
        
        // Generate new hash
        $new_hash = password_hash(ADMIN_PASSWORD, PASSWORD_DEFAULT);
        echo "<p>New hash for 'northcom': " . $new_hash . "</p>";
        
        // Update the password
        $update = $conn->prepare("UPDATE users SET password = ? WHERE username = ?");
        $update->bind_param("ss", $new_hash, $admin);
        if ($update->execute()) {
            echo "<p style='color: green;'>✓ Password updated successfully</p>";
        } else {
            echo "<p style='color: red;'>✗ Failed to update password</p>";
        }
        $update->close();
    }
} else {
    echo "<p style='color: red;'>✗ Admin user not found</p>";
    
    // Create admin user
    $hashed_password = password_hash(ADMIN_PASSWORD, PASSWORD_DEFAULT);
    $insert = $conn->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, 'admin')");
    $insert->bind_param("ss", $admin, $hashed_password);
    
    if ($insert->execute()) {
        echo "<p style='color: green;'>✓ Admin user created successfully</p>";
    } else {
        echo "<p style='color: red;'>✗ Failed to create admin user: " . $conn->error . "</p>";
    }
    $insert->close();
}
$stmt->close();

// Test 4: Session configuration
echo "<h2>4. Session Configuration</h2>";
echo "<p>Session save path: " . session_save_path() . "</p>";
echo "<p>Session name: " . session_name() . "</p>";
echo "<p>Session status: " . (session_status() == PHP_SESSION_ACTIVE ? 'Active' : 'Inactive') . "</p>";

// Test 5: Create a test login
echo "<h2>5. Manual Login Test</h2>";
echo "<form method='post' action='login.php'>";
echo "<input type='hidden' name='username' value='admin'>";
echo "<input type='hidden' name='password' value='" . ADMIN_PASSWORD . "'>";
echo "<button type='submit' style='padding: 10px 20px; background: #667eea; color: white; border: none; border-radius: 5px; cursor: pointer;'>Test Login with admin/northcom</button>";
echo "</form>";

echo "<hr>";
echo "<p><a href='login.php' style='padding: 10px 20px; background: #667eea; color: white; text-decoration: none; border-radius: 5px;'>Go to Login Page →</a></p>";
?>