<?php
require_once __DIR__ . '/config.php';
require __DIR__ . '/oauthDB.php';   // $pdo_user
require __DIR__ . '/myTempdb.php';  // $pdo
session_start();
header('Content-Type: application/json');



//  Validate session token
if (empty($_SESSION['access_token']) || empty($_SESSION['token_expires'])) {
    http_response_code(401);
    echo json_encode([
        'error'    => 'No Access Token! Please re-login.',
        'redirect' => BASE_URL . '/index.php'
    ]);
    session_unset();
    session_destroy();
    exit;
}

if (time() > $_SESSION['token_expires']) {
    http_response_code(401);
    echo json_encode([
        'error'    => 'Access token has expired! Please log in again.',
        'redirect' => BASE_URL . '/index.php'
    ]);
    session_unset();
    session_destroy();
    exit;
}

$accessToken = $_SESSION['access_token'];

//  Validate token in DB
$stmt = $pdo_user->prepare("
    SELECT user_id 
    FROM access_tokens 
    WHERE token = ? AND expires_at > NOW()
");
$stmt->execute([$accessToken]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    http_response_code(403);
    echo json_encode([
        'error'    => 'Invalid or expired token! Please re-login.',
        'redirect' => BASE_URL . '/index.php'
    ]);
    session_unset();
    session_destroy();
    exit;
}

//  Validate input
$cityId    = $_POST['city_id']    ?? '';
$startDate = $_POST['start_date'] ?? '';
$endDate   = $_POST['end_date']   ?? '';

if (!$cityId || !$startDate || !$endDate) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid request – missing parameters']);
    exit;
}

//  Prepare SQL query
$sql = "
    SELECT 
        dd.temperature_date AS date,
        HOUR(dtm.temperature_time) AS hour,
        dtemp.temperature_value
    FROM data_temperature dtemp
    JOIN data_city dc ON dtemp.id_city = dc.id
    JOIN data_date dd ON dtemp.id_date = dd.id
    JOIN data_time dtm ON dtemp.id_time = dtm.id
    WHERE dc.id = :cid
      AND dd.temperature_date BETWEEN :startDate AND :endDate
    ORDER BY dd.temperature_date, dtm.temperature_time
";

$stmt = $pdo->prepare($sql);
$stmt->bindParam(':cid', $cityId);
$stmt->bindParam(':startDate', $startDate);
$stmt->bindParam(':endDate', $endDate);
$stmt->execute();

$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($rows)) {
    http_response_code(404);
    echo json_encode(['error' => 'No temperature data found for the given parameters']);
    exit;
}

// Initialize padded structure
$data = [];
$dateRange = new DatePeriod(
    new DateTime($startDate),
    new DateInterval('P1D'),
    (new DateTime($endDate))->modify('+1 day')
);

foreach ($dateRange as $dateObj) {
    $dateStr = $dateObj->format('Y-m-d');
    $data[$dateStr] = [];
    for ($h = 0; $h < 24; $h++) {
        $hourStr = str_pad($h, 2, '0', STR_PAD_LEFT);
        $data[$dateStr][$hourStr] = null;
    }
}

// Fill actual temperature values
foreach ($rows as $row) {
    $date = $row['date'];
    $hour = str_pad($row['hour'], 2, '0', STR_PAD_LEFT);
    $value = number_format((float)$row['temperature_value'], 4);
    $data[$date][$hour] = $value;
}

//  Final response
echo json_encode($data);