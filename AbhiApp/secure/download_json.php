<?php
require_once __DIR__ . '/config.php';
// Include necessary libraries

require_once __DIR__. '/myTempdb.php';



session_start();

// Validate session and input (same as fetch_temperature.php)
$cityId = $_POST['city_id'] ?? '';
$startDate = $_POST['start_date'] ?? '';
$endDate = $_POST['end_date'] ?? '';

if (!$cityId || !$startDate || !$endDate) {
    die("Missing parameters");
}

// Fetch temperature data
$stmt = $pdo->prepare("
    SELECT dd.temperature_date AS date, 
           HOUR(dtm.temperature_time) AS hour, 
           dtemp.temperature_value
    FROM data_temperature dtemp
    JOIN data_city dc ON dtemp.id_city = dc.id
    JOIN data_date dd ON dtemp.id_date = dd.id
    JOIN data_time dtm ON dtemp.id_time = dtm.id
    WHERE dc.id = :cid AND dd.temperature_date BETWEEN :start AND :end
    ORDER BY dd.temperature_date, dtm.temperature_time
");
$stmt->execute([':cid' => $cityId, ':start' => $startDate, ':end' => $endDate]);
$data_json = $stmt->fetchAll(PDO::FETCH_ASSOC);



header('Content-Type: application/json');
header('Content-Disposition: attachment; filename="temperature_data.json"');


// Return JSON response

echo json_encode($data_json,JSON_PRETTY_PRINT);
exit;