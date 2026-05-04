<?php



include("regdb.php");
// 1. MySQLi Extension -- use by now
// 2. PDO (PHP DATA OBJECTS) --PREFER BY MANY DEVELOPER

if (isset($_POST["register"])){
    if(!empty($_POST["username"]) && !empty($_POST["password"])){
      
        //  $_SESSION["username"] =
        $username = $_POST["username"]; 
        // $_SESSION["password"] = 
        $password = $_POST["password"];



$hash = password_hash($password, PASSWORD_DEFAULT); // hash password to avoid hacking

$sql = "INSERT INTO credentialss ( User, Pass )
        VALUES ('$username', '$hash')";
        

// use this to avoid the user to see many text when there is an arror
try{ 
mysqli_query( $connection,$sql);
echo "Username is now Registered <br>";
}
catch(mysqli_sql_exception){
    echo "Could not able to Register <br> ";  // for simplicity
}

mysqli_close($connection);// ignore red line in $connection



        // echo $_SESSION["username"] . "<br>";
        // echo $_SESSION["password"]  . "<br>";
       header("Location: login.php"); // Go to other local file no need use hyperlinks in Html
       
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
    <title>Register</title>
    <link rel="stylesheet" href="login.css">


</head>
<body>
   
    
<div class="frameall">
    <p><img src="Pic/Copilot_20260427_022306.png" alt=""></p>
     <form class="Ff"  action="register.php" method="post">
        <b>Register</b>
        <label class="i1" >Username <input type="text" name="username" placeholder="ex:Juan123"></label>
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
