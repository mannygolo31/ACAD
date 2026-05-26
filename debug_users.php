<?php
require_once 'config.php';

echo "<h2>Database Debug Info</h2>";

// Check connection
echo "<h3>Connection Status:</h3>";
echo "Connected to database: " . DB_NAME . "<br>";
echo "Database host: " . DB_HOST . "<br>";

// Check if users table exists
$table_check = $conn->query("SHOW TABLES LIKE 'users'");
if ($table_check->num_rows == 0) {
    echo "<p style='color:red'>❌ Users table does not exist!</p>";
    
    // Create users table if it doesn't exist
    $create_table = "CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        role ENUM('admin', 'employee') DEFAULT 'employee',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    
    if ($conn->query($create_table)) {
        echo "<p style='color:green'>✅ Users table created successfully!</p>";
        
        // Insert default admin user
        $admin_password = password_hash('northcom', PASSWORD_DEFAULT);
        $insert = $conn->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
        $username = 'admin';
        $role = 'admin';
        $insert->bind_param("sss", $username, $admin_password, $role);
        
        if ($insert->execute()) {
            echo "<p style='color:green'>✅ Default admin user created!<br>";
            echo "Username: admin<br>";
            echo "Password: northcom</p>";
        } else {
            echo "<p style='color:red'>❌ Failed to create admin user: " . $insert->error . "</p>";
        }
        $insert->close();
    } else {
        echo "<p style='color:red'>❌ Failed to create table: " . $conn->error . "</p>";
    }
} else {
    echo "<p style='color:green'>✅ Users table exists</p>";
    
    // Check users in table
    $result = $conn->query("SELECT id, username, password, role FROM users");
    
    if ($result->num_rows > 0) {
        echo "<h3>Users in database:</h3>";
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID</th><th>Username</th><th>Password Hash (first 50 chars)</th><th>Role</th><th>Valid Hash?</th></tr>";
        
        while ($row = $result->fetch_assoc()) {
            $hash_valid = password_info($row['password']) ? '✅ Yes' : '❌ No (may be plain text)';
            echo "<tr>";
            echo "<td>" . $row['id'] . "</td>";
            echo "<td>" . $row['username'] . "</td>";
            echo "<td>" . substr($row['password'], 0, 50) . "...</td>";
            echo "<td>" . $row['role'] . "</td>";
            echo "<td>" . $hash_valid . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color:orange'>⚠️ No users found in the users table!</p>";
        
        // Insert default admin user
        $admin_password = password_hash('northcom', PASSWORD_DEFAULT);
        $insert = $conn->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
        $username = 'admin';
        $role = 'admin';
        $insert->bind_param("sss", $username, $admin_password, $role);
        
        if ($insert->execute()) {
            echo "<p style='color:green'>✅ Default admin user created!<br>";
            echo "Username: admin<br>";
            echo "Password: northcom</p>";
        } else {
            echo "<p style='color:red'>❌ Failed to create admin user: " . $insert->error . "</p>";
        }
        $insert->close();
    }
}

// Function to check if string is a valid password hash
function password_info($hash) {
    return preg_match('/^\$2[ayb]\$[0-9]{2}\$[A-Za-z0-9\.\/]{53}$/', $hash);
}

$conn->close();
?>