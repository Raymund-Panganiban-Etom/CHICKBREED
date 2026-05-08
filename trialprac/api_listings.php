<?php
// api_listings.php - returns nearby listings as JSON
require 'db.php';
$lat = isset($_GET['lat']) ? floatval($_GET['lat']) : null;
$lon = isset($_GET['lon']) ? floatval($_GET['lon']) : null;
$radius = isset($_GET['radius']) ? floatval($_GET['radius']) : 5.0;
if ($lat === null || $lon === null) { http_response_code(400); echo json_encode(['error'=>'lat/lon required']); exit; }

$sql = "SELECT l.id, l.seller_id, l.title, l.description, l.lat, l.lon,
(6371 * 2 * ASIN(SQRT(
 POWER(SIN((RADIANS(l.lat) - RADIANS(:lat)) / 2), 2) +
 COS(RADIANS(:lat)) * COS(RADIANS(l.lat)) *
 POWER(SIN((RADIANS(l.lon) - RADIANS(:lon)) / 2), 2)
))) AS distance_km,
u.username AS seller_username
FROM listings l
JOIN users u ON u.id = l.seller_id
HAVING distance_km <= :radius
ORDER BY distance_km ASC
LIMIT 500";
$stmt = $pdo->prepare($sql);
$stmt->execute([':lat'=>$lat, ':lon'=>$lon, ':radius'=>$radius]);
$rows = $stmt->fetchAll();
header('Content-Type: application/json; charset=utf-8');
echo json_encode($rows);
?>