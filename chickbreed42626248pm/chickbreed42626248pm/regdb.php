<?php
// regdb.php – silent database connection, no output
$db_server = "localhost";
$db_user = "root";
$db_pass = "";
$db_name = "Chickacc";

$connection = null;

try {
    $connection = mysqli_connect($db_server, $db_user, $db_pass, $db_name);
    if (!$connection) {
        throw new Exception("Connection failed");
    }
    // Set charset to utf8mb4 for full Unicode support
    mysqli_set_charset($connection, "utf8mb4");
} catch (Exception $e) {
    // In production, log error instead of displaying
    error_log("Database connection error: " . $e->getMessage());
    die("Database connection error. Please try again later.");
}
?>