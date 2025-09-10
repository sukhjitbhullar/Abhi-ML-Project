<?php
session_start();
require_once __DIR__ . '/config.php'; // Ensure BASE_URL is defined
require_once __DIR__ . '/oauthDB.php'; // DB connection

// Step 1: Ensure user is authenticated
if (!isset($_SESSION['user_id'])) {
    echo "User not authenticated.";
    exit;
}

// Step 2: Retrieve OAuth request details
$client_id    = $_SESSION['client_id'] ?? '';
$redirect_uri = $_SESSION['redirect_uri'] ?? '';
$scope        = $_SESSION['scope'] ?? '';
$state        = $_SESSION['state'] ?? '';
$user_id      = $_SESSION['user_id'];

// Step 3: Validate client
$stmt = $pdo_user->prepare("SELECT * FROM clients WHERE client_id = ? AND redirect_uri = ?");
$stmt->execute([$client_id, $redirect_uri]);
$client = $stmt->fetch();

if (!$client) {
    echo "Invalid client or redirect URI.";
    exit;
}

// Step 4: Generate authorization code
$auth_code = bin2hex(random_bytes(16));
$stmt = $pdo_user->prepare("
    INSERT INTO authorization_codes 
    (authorization_code, user_id, client_id, scope, expires_at) 
    VALUES (?, ?, ?, ?, DATE_ADD(NOW(), INTERVAL 10 MINUTE))
");
$stmt->execute([$auth_code, $user_id, $client_id, $scope]);

// Step 5: Redirect back to client with code and state
$query = http_build_query([
    'code'  => $auth_code,
    'state' => $state
]);

//echo "Authorization successful. Redirecting to Client Callback...";
header("Location: " . route('issue_token') . '?&' . $query);

exit;
?>