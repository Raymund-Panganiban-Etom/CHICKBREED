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
<style>
    *{
    margin: 0;

}

.frameall{

background: linear-gradient(135deg, #ffffc4 0.000%, #ff6164 50.000%, #b00012 100.000%);
height: 100vh;
    width: 100wh;
    display:flex;
justify-content: center;
align-items: center;
    
}

.frameall img{
    margin-right: 2 0px;
    margin-left: -100px;
    height: 400px;
    width: 700px;
}

.Ff{

height: 300px;
width: 450px;

background-color: white;
border-radius: 20px;
display:flex;
flex-direction: column;
justify-content: center;
align-items: center;
font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;


}

.i1{
   padding-left: 10px;
   margin-top: 20px;
    padding-right: 20px;
    
}
.i2{
    margin-right: 20px;
    padding: 10px;
    
  
}

.i2 input {
  padding-left: 10px;
 padding-right: 10px;

}



.i2 button{
  background: none;
  border: none;
  cursor: pointer;
  
}








.Ff input{
    height: 30px;
    width: 200px;
    border-radius: 10px;
    padding-left: 10px;
    border: 1px solid black;

}


b{
    font-size: 40px;
}




/* .btn{
    height: 25px;
    width: 150px;
    border-radius: 10px;
    background-color: white;
    color: #ff6164;
    border: none;
    cursor: pointer;
}

.btn:hover{
    background-color: #ff6164;
    color: white;
} */

.btn {
  position: relative;
  padding: 1rem 2rem;
  font-size: 1rem;
  font-weight: 600;
  color: #ff6164;
  background: none;
  border: 2px solid #ff6164;
  border-radius: 8px;
  cursor: pointer;
  overflow: hidden;
  transition: all 0.3s ease;
}

.liquid {
  background: linear-gradient(#ff6164 0 0) no-repeat calc(200% - var(--p, 0%))
    100% / 200% var(--p, 0.2em);
  transition: 0.3s var(--t, 0s),
    background-position 0.3s calc(0.3s - var(--t, 0s));
}

.liquid:hover {
  --p: 100%;
  --t: 0.3s;
  color: #fff;
}



</style>

</head>
<body>
   
    
<div class="frameall">
    <p><img src="Pic/Copilot_20260427_022306.png" alt=""></p>
     <form class="Ff"  action="logtest.php" method="post">
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
   
<script>
    const togglePassword = document.getElementById("togglePassword");
const password = document.getElementById("password");

togglePassword.addEventListener("click", function () {
  const type = password.getAttribute("type") === "password" ? "text" : "password";
  password.setAttribute("type", type);
  this.textContent = type === "password" ? "👁️" : "🙈"; // change icon
});
    </script>
</body>
</html>
