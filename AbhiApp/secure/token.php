
<?php
require_once __DIR__ . '/config.php'; // Adjust path based on file location
//Grant Access token to the client application
// token.php – OAuth 2.0 Token Endpoint

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('Pragma: no-cache');

require 'oauthDB.php'; // sets up $pdo

// 1. Enforce POST method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'error'             => 'invalid_request',
        'error_description' => 'HTTP method must be POST'
    ]);
    exit;
}

// 2. Collect and validate input
$grantType    = $_POST['grant_type']    ?? '';
$authCode     = $_POST['code']          ?? '';
$redirectUri  = $_POST['redirect_uri']  ?? '';
$clientId     = $_POST['client_id']     ?? '';
$clientSecret = $_POST['client_secret'] ?? '';

if ($grantType !== 'authorization_code') {
    http_response_code(400);
    echo json_encode([
        'error'             => 'unsupported_grant_type',
        'error_description' => 'Only authorization_code is supported'
    ]);
    exit;
}

if (! $authCode || ! $redirectUri || ! $clientId || ! $clientSecret) {
    http_response_code(400);
    echo json_encode([
        'error'             => 'invalid_request',
        'error_description' => 'Missing required parameters'
    ]);
    exit;
}

// 3. Validate client credentials and redirect URI
$stmt = $pdo_user->prepare(
    'SELECT client_secret, redirect_uri
     FROM clients
     WHERE client_id = :cid'
);
$stmt->execute(['cid' => $clientId]);
$client = $stmt->fetch();

if (! $client || ! hash_equals($client['client_secret'], $clientSecret)) {
    http_response_code(401);
    echo json_encode([
        'error'             => 'invalid_client',
        'error_description' => 'Client authentication failed'
    ]);
    exit;
}

if ($client['redirect_uri'] !== $redirectUri) {
    http_response_code(400);
    echo json_encode([
        'error'             => 'invalid_request',
        'error_description' => 'Mismatched redirect_uri'
    ]);
    exit;
}

// 4. Validate authorization code
$stmt = $pdo_user->prepare(
    'SELECT user_id, scope, expires_at, used
     FROM authorization_codes
     WHERE authorization_code = :code AND client_id = :cid'
);
$stmt->execute([
    'code' => $authCode,
    'cid'  => $clientId
]);
$codeRow = $stmt->fetch();

if (! $codeRow || $codeRow['used'] || strtotime($codeRow['expires_at']) < time()) {
    http_response_code(400);
    echo json_encode([
        'error'             => 'invalid_grant',
        'error_description' => 'Authorization code is invalid, expired, or already used'
    ]);
    exit;
}

// 5. Mark code as used
$pdo_user->prepare('UPDATE authorization_codes SET used = 1 WHERE authorization_code = :code')
    ->execute(['code' => $authCode]);

// 6. Generate tokens
$accessToken  = bin2hex(random_bytes(32));
//$refreshToken = bin2hex(random_bytes(32));
$expiresIn    = 600; // 10 Minutes
$expiresAt    = date('Y-m-d H:i:s', time() + $expiresIn);

// 7. Store tokens
$pdo_user->prepare(
    'INSERT INTO access_tokens
        (token,client_id, user_id, scope, expires_at)
     VALUES
        (:at, :cid, :uid, :scope, :exp)'
)->execute([
    'at'    => $accessToken,
    'cid'   => $clientId,
    'uid'   => $codeRow['user_id'],
    'scope' => $codeRow['scope'],
    'exp'   => $expiresAt
]);

// 8. Return token response

header('Content-Type: application/json');
header('Cache-Control: no-store');
header('Pragma: no-cache');

echo json_encode([
    'access_token'  => $accessToken,
    'token_type'    => 'Bearer',
    'expires_in'    => $expiresIn,
    'scope'         => $codeRow['scope']
]);
exit;

?>