<?php
session_start();
include("regdb.php");

if (isset($_POST["login"])) {
    if (!empty($_POST["username"]) && !empty($_POST["password"])) {
        $username = $_POST["username"];
        $password = $_POST["password"];

        $stmt = $connection->prepare("SELECT Ids, User, Pass, is_admin FROM credentialss WHERE User = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            if (password_verify($password, $row["Pass"])) {
                $_SESSION['user_id']   = $row['Ids'];
                $_SESSION['username']  = $row['User'];
                $_SESSION['is_admin']  = (int)$row['is_admin'];

                // Redirect based on admin status
                if ($_SESSION['is_admin'] == 1) {
                    header("Location: admin.php");
                } else {
                    header("Location: home.php");
                }
                exit;
            } else {
                $error = "Invalid password.";
            }
        } else {
            $error = "No user found with that username.";
        }
        $stmt->close();
    } else {
        $error = "Please fill username and password.";
    }
}
$connection->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>FarmConnect – Login</title>
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

                    <button type="submit" name="login" class="login-btn">Login</button>
                </form>

                <p class="register-link">Don't have an account? <a href="register.php">Create one</a></p>
            </div>
        </div>
    </div>

    <script>
        // Toggle password visibility
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