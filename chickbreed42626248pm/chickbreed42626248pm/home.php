<?php
session_start();

// Redirect to login if user is not logged in
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}

$name = htmlspecialchars($_SESSION['username']); // prevent XSS

// Handle logout
if (isset($_POST['Logout'])) {
    session_destroy();
    header("Location: login.php");
    exit;
}

// Handle Sell / Buy navigation
if (isset($_POST['Sell'])) {
    header("Location: sell.php");
    exit;
}
if (isset($_POST['Buy'])) {
    header("Location: buy.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <link rel="stylesheet" href="home.css">
    <title>FarmConnect – Dashboard</title>
    
</head>
<body>
    <div class="dashboard">
        <div class="welcome">
            <h1>🐓 CHICKBREED</h1>
            <p>Your local poultry marketplace</p>
            <div class="username-badge">
                👋 Welcome, <?php echo $name; ?>
            </div>
        </div>

        <form method="post" action="">
            <div class="actions">
                <button type="submit" name="Sell" class="action-btn sell">
                    🛒 Sell
                </button>
                <button type="submit" name="Buy" class="action-btn buy">
                    🔍 Buy
                </button>
            </div>
            <div class="logout-wrapper">
                <button type="submit" name="Logout" class="logout-btn">
                    🚪 Logout
                </button>
            </div>
        </form>
    </div>
</body>
</html>