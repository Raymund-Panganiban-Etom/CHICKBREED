<?php
/**
 * LOGIN INTEGRATION EXAMPLE
 * 
 * Add this code to your login.php to set the user_id in session.
 * This is required for sell.php to work with the database system.
 */

session_start();

// Example: After verifying username and password from form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['User'] ?? '';
    $password = $_POST['Pass'] ?? '';
    
    // Connect to database
    $db_host = 'localhost';
    $db_user = 'root';
    $db_pass = '';
    $db_name = 'Chickacc';
    
    $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
    if ($conn->connect_error) {
        die('Database connection failed: ' . $conn->connect_error);
    }
    
    // Query user from credentialss table
    $query = "SELECT ids, User, Pass FROM credentialss WHERE User = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        // Verify password (adjust based on your password storage method)
        // If plain text:
        if ($row['Pass'] === $password) {
            // ✓ LOGIN SUCCESSFUL - SET SESSION
            $_SESSION['user_id'] = $row['ids'];      // CRITICAL for sell.php!
            $_SESSION['username'] = $row['User'];
            $_SESSION['login_time'] = time();
            
            // Redirect to dashboard or sell.php
            header('Location: sell.php');
            exit;
        }
        
        // If password is hashed (recommended):
        // if (password_verify($password, $row['Pass'])) {
        //     $_SESSION['user_id'] = $row['ids'];
        //     $_SESSION['username'] = $row['User'];
        //     header('Location: sell.php');
        //     exit;
        // }
    }
    
    // ✗ LOGIN FAILED
    echo "Invalid username or password";
    $stmt->close();
    $conn->close();
}
?>

<!-- LOGIN FORM EXAMPLE -->
<!DOCTYPE html>
<html>
<head>
    <title>Login</title>
    <style>
        body { font-family: Arial; padding: 40px; }
        .login-form { max-width: 300px; }
        input { display: block; width: 100%; padding: 8px; margin: 8px 0; }
        button { padding: 10px 20px; background: #0b78d1; color: white; border: none; cursor: pointer; }
    </style>
</head>
<body>
    <div class="login-form">
        <h2>Login</h2>
        <form method="POST">
            <label>Username:</label>
            <input type="text" name="User" required>
            
            <label>Password:</label>
            <input type="password" name="Pass" required>
            
            <button type="submit">Login</button>
        </form>
    </div>
</body>
</html>

<?php
/**
 * IMPORTANT NOTES:
 * 
 * 1. The user_id MUST be set from credentialss.ids (not a separate value)
 * 2. Once $_SESSION['user_id'] is set, sell.php will automatically:
 *    - Fetch the session user_id
 *    - Load only that user's entries
 *    - Save new entries with that user_id
 *    - Prevent users from accessing other users' entries
 * 
 * 3. For this to work in sell.php:
 *    - Users MUST be logged in before accessing sell.php
 *    - OR sell.php will prompt them for their user_id manually
 * 
 * 4. Security recommendations:
 *    - Use password hashing: password_hash() and password_verify()
 *    - Add HTTPS in production
 *    - Implement logout that clears $_SESSION
 *    - Add login attempt rate limiting
 */
?>
