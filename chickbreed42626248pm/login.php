<?php
session_start();
include("regdb.php");

// Rate limiting: track attempts per IP (or username) in session/db
$max_attempts = 5;
$lockout_time = 300; // seconds
if (!isset($_SESSION['login_attempts'])) $_SESSION['login_attempts'] = 0;
if (!isset($_SESSION['first_attempt_time'])) $_SESSION['first_attempt_time'] = time();

if ($_SESSION['login_attempts'] >= $max_attempts && (time() - $_SESSION['first_attempt_time']) < $lockout_time) {
    $error = "Too many failed attempts. Please try again later.";
} elseif (isset($_POST["login"])) {
    // CSRF token
    if (empty($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
        $error = "Invalid request.";
    } elseif (!empty($_POST["username"]) && !empty($_POST["password"])) {
        $username = $_POST["username"];
        $password = $_POST["password"];
        $stmt = $connection->prepare("SELECT Ids, User, Pass, is_admin FROM credentialss WHERE User = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            if (password_verify($password, $row["Pass"])) {
                // success – reset attempts
                $_SESSION['login_attempts'] = 0;
                $_SESSION['first_attempt_time'] = 0;
                session_regenerate_id(true); // prevent fixation
                $_SESSION['user_id']   = $row['Ids'];
                $_SESSION['username']  = $row['User'];
                $_SESSION['is_admin']  = (int)$row['is_admin'];
                header("Location: " . ($_SESSION['is_admin'] ? "admin.php" : "home.php"));
                exit;
            } else {
                $_SESSION['login_attempts']++;
                if ($_SESSION['login_attempts'] === 1) $_SESSION['first_attempt_time'] = time();
                $error = "Invalid username or password.";
            }
        } else {
            $_SESSION['login_attempts']++;
            if ($_SESSION['login_attempts'] === 1) $_SESSION['first_attempt_time'] = time();
            $error = "Invalid username or password.";
        }
        $stmt->close();
    } else {
        $error = "Please fill username and password.";
    }
}
$connection->close();

// Generate CSRF token if missing
if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Chickbreed – Login</title>
    <link rel="stylesheet" href="login.css">
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
                <form action="login.php" method="post" class="login-form">
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
                    <button type="submit" name="login" class="login-btn">Login</button>
                </form>
                <p class="register-link">Don't have an account? <a href="register.php">Create one</a></p>
            </div>
        </div>
    </div>
   <script src="login.js"></script>
</body>
</html>