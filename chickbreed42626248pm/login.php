<?php
session_start();
header("X-Frame-Options: DENY");
header("frame-ancestors 'none';");
header("X-Content-Type-Options: nosniff");
header("Referrer-Policy: strict-origin-when-cross-origin");
header("Permissions-Policy: geolocation=(self), microphone=()");
// Content Security Policy – adjust as needed:
header("Content-Security-Policy: default-src 'self'; script-src 'self' https://www.google.com https://www.gstatic.com; style-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com https://fonts.googleapis.com; img-src 'self' data: blob:; font-src 'self' https://cdnjs.cloudflare.com https://fonts.gstatic.com; connect-src 'self'; frame-src 'self' https://www.google.com; connect-src https://www.google.com; ");
include("regdb.php");

// Rate limiting
$max_attempts = 5;
$lockout_time = 300;
if (!isset($_SESSION['login_attempts'])) $_SESSION['login_attempts'] = 0;
if (!isset($_SESSION['first_attempt_time'])) $_SESSION['first_attempt_time'] = time();

if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

$error = null;

if ($_SESSION['login_attempts'] >= $max_attempts && (time() - $_SESSION['first_attempt_time']) < $lockout_time) {
    $error = "Too many failed attempts. Please try again later.";
} elseif (isset($_POST["login"])) {
    // CSRF validation
    if (empty($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = "Invalid request.";
    } else {
        // reCAPTCHA v2 validation
        $secretKey = '6LcCjvAsAAAAAJLW1hJIrvz4HYvJyYGIcTnoyFAn';
        $captcha = $_POST['g-recaptcha-response'] ?? '';
        if (!$captcha) {
            $error = "Please complete the reCAPTCHA verification.";
        } else {
            $url = 'https://www.google.com/recaptcha/api/siteverify';
            $data = [
                'secret' => $secretKey,
                'response' => $captcha,
                'remoteip' => $_SERVER['REMOTE_ADDR']
            ];
            $options = [
                'http' => [
                    'header' => "Content-type: application/x-www-form-urlencoded\r\n",
                    'method' => 'POST',
                    'content' => http_build_query($data)
                ]
            ];
            $context = stream_context_create($options);
            $result = @file_get_contents($url, false, $context);
            if ($result === false) {
                $error = "reCAPTCHA service unavailable. Please try again later.";
            } else {
                $response = json_decode($result, true);
                if (!$response['success']) {
                    $error = "reCAPTCHA verification failed. Please try again.";
                }
            }
        }
    }

    // Proceed with login if no error
    if (!$error && !empty($_POST["username"]) && !empty($_POST["password"])) {
        $username = $_POST["username"];
        $password = $_POST["password"];
        $stmt = $connection->prepare("SELECT Ids, User, Pass, is_admin FROM credentialss WHERE User = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            if (password_verify($password, $row["Pass"])) {
                $_SESSION['login_attempts'] = 0;
                $_SESSION['first_attempt_time'] = time();
                session_regenerate_id(true);
                $_SESSION['user_id'] = $row['Ids'];
                $_SESSION['username'] = $row['User'];
                $_SESSION['is_admin'] = (int)$row['is_admin'];
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                header("Location: " . ($_SESSION['is_admin'] ? "admin.php" : "home.php"));
                exit;
            } else {
                $error = "Invalid username or password.";
            }
        } else {
            $error = "Invalid username or password.";
        }
        $stmt->close();
    } elseif (!$error) {
        $error = "Please fill username and password.";
    }

    if ($error) {
        $_SESSION['login_attempts']++;
        if ($_SESSION['login_attempts'] == 1) $_SESSION['first_attempt_time'] = time();
    }
}
$connection->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Chickbreed – Login</title>
     <link rel="icon" type="image/png" sizes="32x32" href="favicon.png">
    <link rel="stylesheet" href="login.css">
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
</head>
<body>
    <div class="login-wrapper">
        <div class="login-container">
            <div class="brand-image">
                <img src="Pic/Copilot_20260427_022306.png" alt="FarmConnect Logo">
            </div>
            <div class="login-card">
                <h2>Welcome Back</h2>
                <p class="subtitle">Sign in to access your marketplace</p>
                <?php if (isset($error)): ?>
                    <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                <form id="loginForm" action="login.php" method="post" class="login-form">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                    <div class="input-group">
                        <label for="username">Username</label>
                        <input type="text" id="username" name="username" required autofocus>
                    </div>
                    <div class="input-group">
                        <label for="password">Password</label>
                        <div class="password-wrapper">
                            <input type="password" id="password" name="password" required>
                            <button type="button" id="togglePassword">👁️</button>
                        </div>
                    </div>
                     <div class="recaptcha-container">
                     <div class="g-recaptcha" data-sitekey="6LcCjvAsAAAAAKC7lF3pXw0FpKbFiA4bc58jWnEb"></div>
                   </div>
                    <button type="submit" name="login" class="login-btn">Login</button>
                     
                </form>
                 
                <p class="register-link">Don't have an account? <a href="register.php">Create one</a></p>
            </div>
        </div>
    
    </div>
    
    <script src="login.js" ></script>
</body>
</html>