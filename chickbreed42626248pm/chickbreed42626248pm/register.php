<?php
session_start();
include("regdb.php");

$error = null;
$success = false;

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["register"])) {
    $username = trim($_POST["username"] ?? "");
    $password = $_POST["password"] ?? "";

    if (empty($username) || empty($password)) {
        $error = "Please fill in both username and password.";
    } else {
        // Check if username already exists
        $checkStmt = mysqli_prepare($connection, "SELECT Ids FROM credentialss WHERE User = ?");
        mysqli_stmt_bind_param($checkStmt, "s", $username);
        mysqli_stmt_execute($checkStmt);
        mysqli_stmt_store_result($checkStmt);
        
        if (mysqli_stmt_num_rows($checkStmt) > 0) {
            $error = "Username already taken. Please choose another.";
        } else {
            // Hash password and insert new user
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $insertStmt = mysqli_prepare($connection, "INSERT INTO credentialss (User, Pass) VALUES (?, ?)");
            mysqli_stmt_bind_param($insertStmt, "ss", $username, $hashedPassword);
            
            if (mysqli_stmt_execute($insertStmt)) {
                $success = true;
                // Redirect to login page after 2 seconds (or immediately)
                header("Location: login.php");
                exit;
            } else {
                $error = "Registration failed. Please try again.";
            }
            mysqli_stmt_close($insertStmt);
        }
        mysqli_stmt_close($checkStmt);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>FarmConnect – Register</title>
    <link rel="stylesheet" href="login.css">
</head>
<body>
    <div class="login-wrapper">
        <div class="login-container">
            <div class="brand-image">
                <img src="Pic/Copilot_20260427_022306.png" alt="FarmConnect Logo">
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
                    <div class="input-group">
                        <label for="username">Username</label>
                        <input type="text" id="username" name="username" placeholder="ex: Juan123" required autofocus>
                    </div>

                    <div class="input-group">
                        <label for="password">Password</label>
                        <div class="password-wrapper">
                            <input type="password" id="password" name="password" placeholder="••••••••" required>
                            <button type="button" id="togglePassword" aria-label="Show password">👁️</button>
                        </div>
                    </div>

                    <button type="submit" name="register" class="login-btn">Register</button>
                </form>

                <p class="register-link">Already have an account? <a href="login.php">Login</a></p>
            </div>
        </div>
    </div>

    <script>
        const toggleBtn = document.getElementById('togglePassword');
        const passwordInput = document.getElementById('password');
        if (toggleBtn && passwordInput) {
            toggleBtn.addEventListener('click', function() {
                const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordInput.setAttribute('type', type);
                this.textContent = type === 'password' ? '👁️' : '🙈';
            });
        }
    </script>
</body>
</html>