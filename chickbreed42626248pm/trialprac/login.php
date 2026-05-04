<?php
// login.php
session_start();
require 'db.php';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $username = trim($_POST['username'] ?? '');
  $password = $_POST['password'] ?? '';
  if ($username === '' || $password === '') {
    $error = 'Username and password required.';
  } else {
    $stmt = $pdo->prepare("SELECT id, password_hash, role FROM users WHERE username = :u LIMIT 1");
    $stmt->execute([':u'=>$username]);
    $user = $stmt->fetch();
    if ($user && password_verify($password, $user['password_hash'])) {
      session_regenerate_id(true);
      $_SESSION['user_id'] = (int)$user['id'];
      $_SESSION['role'] = $user['role'];
      header('Location: ' . ($user['role'] === 'seller' ? 'seller.php' : 'buyer.php'));
      exit;
    } else {
      $error = 'Invalid credentials.';
    }
  }
}
?>
<!doctype html>
<html>
<head><meta charset="utf-8"><title>Login</title></head>
<body>
  <h2>Login</h2>
  <?php if ($error): ?><div style="color:red"><?=htmlspecialchars($error)?></div><?php endif; ?>
  <form method="post">
    <label>Username<br><input name="username" required></label><br>
    <label>Password<br><input name="password" type="password" required></label><br><br>
    <button type="submit">Login</button>
  </form>
  <p><a href="register.php">Register</a></p>
</body>
</html>
