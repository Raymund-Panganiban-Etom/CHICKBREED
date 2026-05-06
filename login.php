<?php
session_start();
include("regdb.php");

if (isset($_POST["login"])) {
    if (!empty($_POST["username"]) && !empty($_POST["password"])) {

        $username = $_POST["username"];
        $password = $_POST["password"];

        $stmt = $connection->prepare("SELECT Ids, User, Pass FROM credentialss WHERE User = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            if (password_verify($password, $row["Pass"])) {
                
                // ✅ IMPORTANT
                $_SESSION['user_id']   = $row['Ids'];
                $_SESSION['username']  = $row['User'];

                header("Location: home.php");   // ← Back to your original
                exit;
            } else {
                echo "<p style='color:red'>Invalid password.</p>";
            }
        } else {
            echo "<p style='color:red'>No user found with that username.</p>";
        }
        $stmt->close();
    } else {
        echo "<p style='color:red'>Please fill username and password.</p>";
    }
}
$connection->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="login.css">


</head>
<body>
   
    
<div class="frameall">
    <p><img src="Pic/Copilot_20260427_022306.png" alt=""></p>
     <form class="Ff"  action="login.php" method="post">
        <b>Login</b>
        <label class="i1" >Username <input type="text" name="username" placeholder="ex:Juan123"></label>
        <br>

        
        <label class="i2" >Password <input type="password" id="password" name="password">
         <button type="button" id="togglePassword">👁️</button>
        </label>
        <br>

        <div class="holographic-container">
        <div class="holographic-card">
        <button type="submit" value="Submit" name="login" class="btn liquid"><span>Submit</span></button>
        </div>
        </div>
    </form>
     <script src="app.js"></script>
</div>
   

</body>
</html>
