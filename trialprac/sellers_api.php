<?php
// sellers_api.php
require 'db.php';
$lat = isset($_GET['lat']) ? floatval($_GET['lat']) : null;
$lon = isset($_GET['lon']) ? floatval($_GET['lon']) : null;
$radius = isset($_GET['radius']) ? floatval($_GET['radius']) : 5.0;
if ($lat === null || $lon === null) { http_response_code(400); echo json_encode([]); exit; }

$sql = "SELECT id, seller_id, title, description, lat, lon,
(6371 * 2 * ASIN(SQRT(
 POWER(SIN((RADIANS(lat) - RADIANS(:lat)) / 2), 2) +
 COS(RADIANS(:lat)) * COS(RADIANS(lat)) *
 POWER(SIN((RADIANS(lon) - RADIANS(:lon)) / 2), 2)
))) AS distance_km
FROM listings
HAVING distance_km <= :radius
ORDER BY distance_km ASC
LIMIT 200";

$stmt = $pdo->prepare($sql);
$stmt->execute([':lat'=>$lat, ':lon'=>$lon, ':radius'=>$radius]);
$rows = $stmt->fetchAll();
header('Content-Type: application/json; charset=utf-8');
echo json_encode($rows);
?>