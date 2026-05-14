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
        <div class="feedback-section" style="margin-top: 2rem; border-top: 1px solid #FFE0B2; padding-top: 1.5rem;">
    <h3 style="color: #C62828; margin-bottom: 0.5rem;"><i class="fas fa-comment-dots"></i> Send Feedback to Admin</h3>
    <form id="feedbackForm">
        <textarea id="feedbackMessage" rows="3" placeholder="Share your suggestions, report issues, or ask questions..." required style="width:100%; padding:0.8rem; border:1px solid #FFE0B2; border-radius:20px; background:#FEF9F0;"></textarea>
        <div style="display: flex; justify-content: flex-end; margin-top: 0.8rem;">
            <button type="submit" class="action-btn buy" style="background:#F9A825; color:#5D2906; padding:0.6rem 1.5rem;">Send Feedback</button>
        </div>
    </form>
    <div id="feedbackStatus" class="status" style="display:none; margin-top:0.8rem;"></div>
</div>
    </div>
    <script>
        // Feedback submission
const feedbackForm = document.getElementById('feedbackForm');
const feedbackMsg = document.getElementById('feedbackMessage');
const feedbackStatus = document.getElementById('feedbackStatus');

feedbackForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    const message = feedbackMsg.value.trim();
    if (!message) return alert('Please enter a message');

    const fd = new FormData();
    fd.append('message', message);
    try {
        const res = await fetch('submit_feedback.php', { method: 'POST', body: fd });
        const data = await res.json();
        if (data.success) {
            feedbackStatus.textContent = '✅ Thank you! Your feedback has been sent to the admin.';
            feedbackStatus.className = 'status success';
            feedbackStatus.style.display = 'block';
            feedbackMsg.value = '';
            setTimeout(() => { feedbackStatus.style.display = 'none'; }, 5000);
        } else {
            alert('Error: ' + data.error);
        }
    } catch (err) {
        alert('Network error: ' + err.message);
    }
});
    </script>
</body>
</html>