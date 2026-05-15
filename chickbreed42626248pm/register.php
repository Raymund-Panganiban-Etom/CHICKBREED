<?php
session_start();
// Rate limiting for registration
$max_attempts = 5;
$lockout_time = 600; // seconds (10 minutes)
if (!isset($_SESSION['reg_attempts'])) $_SESSION['reg_attempts'] = 0;
if (!isset($_SESSION['reg_first_attempt'])) $_SESSION['reg_first_attempt'] = time();

if ($_SESSION['reg_attempts'] >= $max_attempts && (time() - $_SESSION['reg_first_attempt']) < $lockout_time) {
    $error = "Too many registration attempts. Please try again later.";
    // Prevent form from being processed
    $rate_limited = true;
} else {
    $rate_limited = false;
}
include("regdb.php");

// Generate CSRF token if not exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$error = null;
$success = false;

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["register"])) {
    // CSRF validation
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = "Invalid request. Please try again.";
    }
    
    if (!$rate_limited && $error) { // only increment if not already locked and there is an error
    $_SESSION['reg_attempts']++;
    if ($_SESSION['reg_attempts'] == 1) $_SESSION['reg_first_attempt'] = time();
}

if ($success) {
    $_SESSION['reg_attempts'] = 0;
    $_SESSION['reg_first_attempt'] = 0;
}
    
    else {
        $username = trim($_POST["username"] ?? "");
        $password = $_POST["password"] ?? "";

        // Validate password strength
        $password_errors = [];
        if (strlen($password) < 8) $password_errors[] = "at least 8 characters";
        if (!preg_match('/[A-Z]/', $password)) $password_errors[] = "an uppercase letter";
        if (!preg_match('/[a-z]/', $password)) $password_errors[] = "a lowercase letter";
        if (!preg_match('/[0-9]/', $password)) $password_errors[] = "a number";
        if (!preg_match('/[^A-Za-z0-9]/', $password)) $password_errors[] = "a special character";

        if (!empty($password_errors)) {
            $error = "Password must contain " . implode(", ", $password_errors);
        } elseif (empty($username)) {
            $error = "Username required.";
        } else {
            // Check duplicate username
            $stmt = mysqli_prepare($connection, "SELECT Ids FROM credentialss WHERE User = ?");
            mysqli_stmt_bind_param($stmt, "s", $username);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_store_result($stmt);
            if (mysqli_stmt_num_rows($stmt) > 0) {
                $error = "Registration failed. Please try again."; // Generic message (no “username taken”)
            } else {
                $hashed = password_hash($password, PASSWORD_DEFAULT);
                $insert = mysqli_prepare($connection, "INSERT INTO credentialss (User, Pass) VALUES (?, ?)");
                mysqli_stmt_bind_param($insert, "ss", $username, $hashed);
                if (mysqli_stmt_execute($insert)) {
                    $success = true;
                    // Regenerate CSRF token after successful registration
                    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                    header("Location: login.php");
                    exit;
                } else {
                    $error = "Registration failed. Please try again.";
                }
                mysqli_stmt_close($insert);
            }
            mysqli_stmt_close($stmt);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Chickbreed – Register</title>
    <link rel="stylesheet" href="login.css">
</head>
<body>
    <div class="login-wrapper">
        <div class="login-container">
            <div class="brand-image">
                <img src="Pic/Copilot_20260427_022306.png" alt="Chickbreed Logo">
            </div>
            <div class="login-card">
                <h2>Create Account</h2>
                <p class="subtitle">Join Chickbreed today</p>

                <?php if ($error): ?>
                    <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
                <?php elseif ($success): ?>
                    <div class="error-message" style="background:#D4EDDA; color:#2E7D32;">Registration successful! Redirecting to login...</div>
                <?php endif; ?>

                <form action="" method="post" class="login-form">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                    <div class="input-group">
                        <label for="username">Username</label>
                        <input type="text" id="username" name="username" required autofocus>
                    </div>
                    <div class="input-group">
                        <label for="password">Password</label>
                        <div class="password-wrapper">
                            <input type="password" id="password" name="password" required>
                            <button type="button" id="togglePassword" aria-label="Show password">👁️</button>
                        </div>
                        <div class="small">Minimum 8 chars, one uppercase, one lowercase, one number, one special character.</div>
                    </div>
                    <button type="submit" name="register" class="login-btn">Register</button>
                </form>
                <p class="register-link">Already have an account? <a href="login.php">Login</a></p>
            </div>
        </div>
    </div>
   <script src="login.js"></script>
</body>
</html>