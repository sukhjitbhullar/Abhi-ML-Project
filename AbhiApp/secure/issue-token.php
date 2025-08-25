<?php
require_once __DIR__ . '/config.php'; // Loads BASE_URL and ENVIRONMENT
require_once __DIR__. '/router.php'; // Include routing functions
require_once __DIR__ . '/oauthDB.php'; // DB connection
session_start();



// 1. Validate incoming code and state
$code  = $_GET['code']  ?? '';
$state = $_GET['state'] ?? '';

if (! $code || ! $state || ! isset($_SESSION['state']) || ! hash_equals($_SESSION['state'], $state)) {
    exit('Invalid or missing code/state.');
}

// 2. Identify client and fetch its secret + redirect URI
$clientId = $_SESSION['client_id'] ?? '';

$stmt = $pdo_user->prepare("SELECT client_secret, redirect_uri FROM clients WHERE client_id = ?");
$stmt->execute([$clientId]);
$client = $stmt->fetch(PDO::FETCH_ASSOC);

if (! $client) {
    exit('Unknown client_id.');
}

// 3. Build token exchange payload
$postData = [
    'grant_type'    => 'authorization_code',
    'code'          => $code,
    'redirect_uri'  => $client['redirect_uri'],
    'client_id'     => $clientId,
    'client_secret' => $client['client_secret'],
];

// 4. Prepare stream context for POST
$options = [
    'http' => [
        'method'  => 'POST',
        'header'  => implode("\r\n", [
            'Content-Type: application/x-www-form-urlencoded',
            'Accept: application/json'
        ]),
        'content' => http_build_query($postData),
        'timeout' => 10
    ]
];

$context = stream_context_create($options);

// 5. Send request to token endpoint
$tokenEndpoint = route('token'); //  No full path as BASE_URL is included in route()

$response = file_get_contents($tokenEndpoint, false, $context);
$response = trim($response);

if ($response === false) {
    $err = error_get_last();
    exit('Token request failed: ' . htmlspecialchars($err['message']));
}

// 6. Decode and validate response
$data = json_decode($response, true);

if (json_last_error() !== JSON_ERROR_NONE || empty($data['access_token'])) {
    exit('Invalid token response: ' . htmlspecialchars($response));
}

// 7. Store token in session
$_SESSION['access_token']  = $data['access_token'];
$_SESSION['token_expires'] = time() + ($data['expires_in'] ?? 600);
$_SESSION['token_type']    = $data['token_type'] ?? 'Bearer';

// 8. Redirect to dashboard
header('Location: ' .route('dashboard'));
exit;