<?php
// config.php
session_start();

// Load environment variables from .env file
function loadEnv($path) {
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

loadEnv(__DIR__ . '/.env');

// Database configuration from environment variables
$host     = getenv('DB_HOST') ?: 'localhost';
$username = getenv('DB_USERNAME') ?: 'root';
$password = getenv('DB_PASSWORD') ?: '';
$database = getenv('DB_NAME') ?: 'emp';

// Create database connection
$conn = new mysqli($host, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    error_log("Database connection failed: " . $conn->connect_error);
    die("Database connection failed. Please check your configuration.");
}

// Set charset to UTF-8
$conn->set_charset("utf8mb4");

// Application configuration from environment variables
define('ADMIN_PASSWORD', getenv('ADMIN_PASSWORD') ?: 'changeme');
define('SITE_NAME', getenv('SITE_NAME') ?: 'Employee Directory with QR System');
define('BASE_URL', 'http://' . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\'));

// Encryption keys from environment variables
define('ENCRYPTION_KEY', getenv('ENCRYPTION_KEY') ?: 'default-key-change-in-production');
define('ENCRYPTION_IV', getenv('ENCRYPTION_IV') ?: '1234567890123456');

// Security headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');

// Session security settings
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_samesite', 'Strict');
ini_set('session.use_strict_mode', 1);

// Include security utilities
require_once __DIR__ . '/rate_limiter.php';
require_once __DIR__ . '/input_validator.php';

// Reject oversized requests
InputValidator::rejectOversizedRequest();

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
    if (!is_string($data)) return '';
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $conn->real_escape_string($data);
}

// Function to log activity
function logActivity($user_id, $action, $details = '') {
    global $conn;
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $stmt = $conn->prepare("INSERT INTO activity_logs (user_id, action, details, ip_address) VALUES (?, ?, ?, ?)");
    if ($stmt) {
        $stmt->bind_param("isss", $user_id, $action, $details, $ip);
        $stmt->execute();
        $stmt->close();
    }
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
    // Rate limit authenticated endpoints
    checkRateLimit('authenticated');
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
