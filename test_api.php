<?php
header('Content-Type: application/json');
$url = 'http://localhost/buy_handler.php?action=getSession';
$response = @file_get_contents($url);
if ($response === false) {
    echo json_encode(['error' => 'Cannot reach buy_handler.php', 'url' => $url]);
} else {
    echo $response;
}
?>