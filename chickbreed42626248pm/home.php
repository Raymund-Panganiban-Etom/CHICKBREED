<?php

// session = SGB used to store information on a user to be used  across 
// mulitple  pages. A user  is assigned a swssion - id, ex. login credentials



session_start(); // WE CAN USE IT IN LOG IN AND WELCOME PAGE APPEARING THE USERNAME THAT ENTER OF USER IN REGISTRATION!!!!!!!!!!!! IN  WELCOME OR HOMEPAGE 
if(isset($_POST["Logout"])){
    session_destroy(); // To erase session in entering credentials
    header("Location: login.php");
}

// $_SESSION["username"] = "Raymund";
// $_SESSION["password"] = "Ray123";

$name = $_SESSION["username"] . "<br>";

echo "Welcome $name !";


?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
    This is The Home page<br>
    <!-- <a href="homes.php">This goes to the home page</a><br> -->
     <form action="home.php" method="post">
        <input type="submit" name="Logout" value="Logout">
     </form>
</body>
</html>

<?php




?> 