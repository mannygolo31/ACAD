<?php
// login.php
require_once 'config.php';

$error = '';
$csrf_token = generateCSRFToken();

// Rate limit check for login page
$ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
$login_limiter = new RateLimiter(5, 900);
$login_key = 'login:' . $ip;
$remaining = $login_limiter->getRemainingAttempts($login_key);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $error = 'Invalid request. Please try again.';
    } elseif ($login_limiter->isRateLimited($login_key)) {
        $retry = ceil($login_limiter->getRetryAfter($login_key) / 60);
        $error = "Too many login attempts. Please try again in $retry minute(s).";
    } else {
        $username = isset($_POST['username']) ? sanitize($_POST['username']) : '';
        $password_input = isset($_POST['password']) ? $_POST['password'] : '';

        // Validate input lengths
        if (!InputValidator::validateLength('username', $username)) {
            $error = 'Username is too long.';
        } elseif (strlen($password_input) > 128) {
            $error = 'Password is too long.';
        } elseif (empty($username) || empty($password_input)) {
            $error = 'Please enter username and password';
        } else {
            $stmt = $conn->prepare("SELECT id, username, password, role FROM users WHERE username = ?");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows == 1) {
                $user = $result->fetch_assoc();
                if (password_verify($password_input, $user['password'])) {
                    // Reset rate limit on success
                    $login_limiter->reset($login_key);

                    // Regenerate session ID to prevent fixation
                    session_regenerate_id(true);

                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['role'] = $user['role'];

                    logActivity($user['id'], 'Login', 'User logged in successfully');
                    header("Location: dashboard.php");
                    exit();
                } else {
                    $login_limiter->recordAttempt($login_key);
                    $remaining = $login_limiter->getRemainingAttempts($login_key);
                    $error = "Invalid password. $remaining attempt(s) remaining.";
                }
            } else {
                $login_limiter->recordAttempt($login_key);
                $remaining = $login_limiter->getRemainingAttempts($login_key);
                $error = "Invalid credentials. $remaining attempt(s) remaining.";
            }
            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Futura', 'Helvetica Neue', Arial, sans-serif; background: linear-gradient(135deg, #d81919 0%, #555555 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; }
        .login-container { background: white; border-radius: 20px; padding: 40px; width: 100%; max-width: 400px; box-shadow: 0 20px 40px rgba(0,0,0,0.2); animation: slideUp 0.5s ease; }
        @keyframes slideUp { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }
        .header { text-align: center; margin-bottom: 30px; }
        .header h1 { color: #d81919; font-size: 28px; margin-bottom: 10px; }
        .header p { color: #555555; font-size: 14px; }
        .input-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 8px; color: #555555; font-weight: 500; }
        .input-icon { position: relative; }
        .input-icon i { position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: #999; }
        input { width: 100%; padding: 15px 15px 15px 45px; border: 2px solid #e0e0e0; border-radius: 10px; font-size: 16px; outline: none; transition: all 0.3s; }
        input:focus { border-color: #d81919; }
        button { width: 100%; padding: 15px; background: linear-gradient(135deg, #d81919 0%, #a01414 100%); color: white; border: none; border-radius: 10px; font-size: 16px; font-weight: 600; cursor: pointer; transition: all 0.3s; }
        button:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(216,25,25,0.4); }
        .error-message { background: #ffebee; color: #f44336; padding: 15px; border-radius: 10px; margin-bottom: 20px; border: 1px solid #ffcdd2; font-size: 14px; }
        .info { text-align: center; margin-top: 20px; color: #999; font-size: 12px; }
        .info strong { color: #d81919; }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="header">
            <h1><i class="fas fa-shield-alt"></i> NSIAI Directory</h1>
            <p>Employee Management System</p>
        </div>

        <?php if ($error): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
            <div class="input-group">
                <label>Username</label>
                <div class="input-icon">
                    <i class="fas fa-user"></i>
                    <input type="text" name="username" placeholder="Enter username" required autofocus maxlength="50">
                </div>
            </div>

            <div class="input-group">
                <label>Password</label>
                <div class="input-icon">
                    <i class="fas fa-lock"></i>
                    <input type="password" name="password" placeholder="Enter password" required maxlength="128">
                </div>
            </div>

            <button type="submit">
                <i class="fas fa-sign-in-alt"></i> Login
            </button>
        </form>

        <div class="info">
        </div>
    </div>
</body>
</html>
