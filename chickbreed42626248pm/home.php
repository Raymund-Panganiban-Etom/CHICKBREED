<?php
session_start();

// Redirect to login if user is not logged in
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}

$name = htmlspecialchars($_SESSION['username']); // prevent XSS

// CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

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
    <title>Chickbreed – Dashboard</title>
    <link rel="stylesheet" href="home.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,400;14..32,500;14..32,600;14..32,700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    
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

        <div class="feedback-section">
            <h3><i class="fas fa-comment-dots"></i>Feedback</h3>
            <form id="feedbackForm">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <textarea id="feedbackMessage" rows="3" placeholder="Share your suggestions, report issues, or ask questions..." required></textarea>
                <button type="submit"><i class="fas fa-paper-plane"></i> Send Feedback</button>
                <div id="feedbackStatus" class="status"></div>
            </form>
        </div>
    </div>

<script src="home.js"></script>
</body>
</html>