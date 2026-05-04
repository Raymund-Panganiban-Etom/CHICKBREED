<?php

session_start();

include ("regdb.php");

if (isset($_POST["login"])){
    if(!empty($_POST["username"]) && !empty($_POST["password"])){
      
         $_SESSION["username"] = $_POST["username"];
        $_SESSION["password"] = $_POST["password"];

$username = $_SESSION["username"];
    $password = $_SESSION["password"];

    // Prepare statement to prevent SQL injection
    $stmt = $connection->prepare("SELECT User, Pass FROM credentialss WHERE User = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        // Verify hashed password
        if (password_verify($password, $row["Pass"])) {
            $_SESSION["username"] = $row["User"];
            header("Location: home.php"); // Go to other local file no need use hyperlinks in Html
        exit; // stop script after redirect
            
        } else {
            echo "Invalid password.";
        }
         } else {
        echo "No user found with that username.";
    }
    $stmt->close();
}
$connection->close();




        // echo $_SESSION["username"] . "<br>";
        // echo $_SESSION["password"]  . "<br>";
      
    }
    else{
            echo "Please Fill Username or Password <br>";
    }
    
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
