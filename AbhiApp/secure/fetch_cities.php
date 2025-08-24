<?php
require_once __DIR__ . '/config.php'; // Adjust path based on file location
require 'myTempDb.php';

$stmt = $pdo->query("SELECT id, city_name FROM data_city ORDER BY city_name");
$cities = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($cities);
?>