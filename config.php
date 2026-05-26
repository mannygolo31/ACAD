<?php
// config.php
session_start();

$host = "sql309.infinityfree.com";
$username = "if0_40195878"; // Change this to your MySQL username
$password = "Im37WoESkqG"; // Change this to your MySQL password
$database = "if0_40195878_emp";

// Create database connection
$conn = new mysqli($host, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// Set charset to UTF-8
$conn->set_charset("utf8mb4");

// Application configuration
define('ADMIN_PASSWORD', 'northcom');
define('SITE_NAME', 'Employee Directory with QR System');
define('BASE_URL', 'http://' . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\'));

// Encryption key (change this to a secure random string)
define('ENCRYPTION_KEY', 'your-256-bit-secret-key-here-change-this');
define('ENCRYPTION_IV', '1234567890123456'); // 16 chars for AES-256-CBC

// Functions for encryption/decryption
function encryptData($data) {
    if (empty($data)) return '';
    $cipher = "AES-256-CBC";
    $key = hash('sha256', ENCRYPTION_KEY, true);
    $iv = ENCRYPTION_IV;
    return base64_encode(openssl_encrypt($data, $cipher, $key, OPENSSL_RAW_DATA, $iv));
}

function decryptData($data) {
    if (empty($data)) return '';
    $cipher = "AES-256-CBC";
    $key = hash('sha256', ENCRYPTION_KEY, true);
    $iv = ENCRYPTION_IV;
    return openssl_decrypt(base64_decode($data), $cipher, $key, OPENSSL_RAW_DATA, $iv);
}

// Function to sanitize input
function sanitize($data) {
    global $conn;
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $conn->real_escape_string($data);
}

// Function to log activity
function logActivity($user_id, $action, $details = '') {
    global $conn;
    $ip = $_SERVER['REMOTE_ADDR'];
    $stmt = $conn->prepare("INSERT INTO activity_logs (user_id, action, details, ip_address) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isss", $user_id, $action, $details, $ip);
    $stmt->execute();
    $stmt->close();
}

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// Redirect if not logged in
function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: login.php");
        exit();
    }
}

// Generate CSRF token
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Verify CSRF token
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}
?>