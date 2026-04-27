<?php

session_start();

if (isset($_POST["register"])){
    if(!empty($_POST["username"]) && !empty($_POST["password"])){
      
         $_SESSION["username"] = $_POST["username"];
        $_SESSION["password"] = $_POST["password"];

        // echo $_SESSION["username"] . "<br>";
        // echo $_SESSION["password"]  . "<br>";
       header("Location: Login.php"); // Go to other local file no need use hyperlinks in Html
        exit; // stop script after redirect
    }
    else{
            echo "Please Fill Username or Password <br>";
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="style.css">


</head>
<body>
   
    
<div class="frameall">
    <p><img src="Pic/Copilot_20260427_022306.png" alt=""></p>
     <form class="Ff"  action="index.php" method="post">
        <b>Register</b>
        <label class="i1" >Username <input type="text" name="username" placeholder="Juan123"></label>
        <br>

        
        <label class="i2" >Password <input type="password" id="password" name="password">
         <button type="button" id="togglePassword">👁️</button>
        </label>
        <br>

        <div class="holographic-container">
        <div class="holographic-card">
        <button type="submit" value="Submit" name="register" class="btn liquid"><span>Submit</span></button>
        </div>
        </div>
    </form>
     <script src="app.js"></script>
</div>
   

</body>
</html>
