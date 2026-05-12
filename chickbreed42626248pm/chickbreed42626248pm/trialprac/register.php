<?php
// register.php
session_start();
require 'db.php';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $username = trim($_POST['username'] ?? '');
  $password = $_POST['password'] ?? '';
  $role = ($_POST['role'] === 'seller') ? 'seller' : 'buyer';
  if ($username === '' || $password === '') {
    $error = 'Username and password are required.';
  } else {
    // check exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = :u LIMIT 1");
    $stmt->execute([':u'=>$username]);
    if ($stmt->fetch()) {
      $error = 'Username already taken.';
    } else {
      $hash = password_hash($password, PASSWORD_DEFAULT);
      $ins = $pdo->prepare("INSERT INTO users (username, password_hash, role, display_name) VALUES (:u,:p,:r,:d)");
      $ins->execute([':u'=>$username,':p'=>$hash,':r'=>$role,':d'=>$username]);
      $_SESSION['user_id'] = (int)$pdo->lastInsertId();
      $_SESSION['role'] = $role;
      header('Location: ' . ($role === 'seller' ? 'seller.php' : 'buyer.php'));
      exit;
    }
  }
}
?>
<!doctype html>
<html>
<head><meta charset="utf-8"><title>Register</title></head>
<body>
  <h2>Register</h2>
  <?php if ($error): ?><div style="color:red"><?=htmlspecialchars($error)?></div><?php endif; ?>
  <form method="post">
    <label>Username<br><input name="username" required></label><br>
    <label>Password<br><input name="password" type="password" required></label><br>
    <label>Role
      <select name="role">
        <option value="buyer">Buyer</option>
        <option value="seller">Seller</option>
      </select>
    </label><br><br>
    <button type="submit">Register</button>
  </form>
  <p><a href="login.php">Already have account? Login</a></p>
</body>
</html>
