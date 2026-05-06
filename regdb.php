<?php



$db_server = "localhost";
$db_user = "root";
$db_pass= "";
$db_name = "Chickacc";// data base name
$connection = "";

// $connection = mysqli_connect($db_server, $db_user, $db_pass, $db_name);
// use this to avoid the user to see many text when there is an arror
try{ 
$connection = mysqli_connect($db_server, $db_user, $db_pass, $db_name);
}
catch(mysqli_sql_exception){
    echo "Could not able to connect";  // for simplicity
}


if($connection){    // FOR CHECKING ONLY ON NOT NECESSARY TO SEE BY USER
    echo "You're connected <br>";
}else{
    echo " You're not connected! <br>";
}

?>