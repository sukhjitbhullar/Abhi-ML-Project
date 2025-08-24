<?php
declare(strict_types=1);

// 1. Bootstrap environment and constants
require_once __DIR__ . '/config.php';    // loads BASE_URL, ENVIRONMENT
session_start();

// 2. Database connection
require_once __DIR__ . '/oauthDB.php';   // DB Connection provides $pdo_user

// 3. Helper: show JS alert then redirect
function redirectWithAlert(string $message, string $url): void
{
    // JSON-encode to escape quotes safely
    $msg = json_encode($message, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    $dest = json_encode($url,     JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

    header('Content-Type: text/html; charset=utf-8');
    echo <<<HTML
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>Redirecting…</title>
  <script>
    alert({$msg});
    window.location.href = {$dest};
  </script>
</head>
<body></body>
</html>
HTML;
    exit;
}

// 4. Grab & sanitize input
$name     = trim($_POST['name']     ?? '');
$email    = trim($_POST['email']    ?? '');
$username = trim($_POST['username'] ?? '');
$password = $_POST['password']      ?? '';

// 5. Validate presence
if ($name === '' || $email === '' || $username === '' || $password === '') {
    redirectWithAlert('All fields are required.', BASE_URL);
}

// 6. Validate email format
if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
    redirectWithAlert('Invalid email format.', BASE_URL);
}

// 7. Check for existing user
$stmt = $pdo_user->prepare(
    "SELECT id FROM users WHERE username = ? OR email = ?"
);
$stmt->execute([$username, $email]);

if ($stmt->fetch()) {
    redirectWithAlert(
      'Username or Email already exists. Please login.',
      BASE_URL
    );
}

// 8. Hash & insert new user
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

$stmt = $pdo_user->prepare(
    "INSERT INTO users (name, email, username, password_hash)
     VALUES (?, ?, ?, ?)"
);
$stmt->execute([$name, $email, $username, $hashedPassword]);

$userId = (int) $pdo_user->lastInsertId();

// 9. Create OAuth client credentials
$clientId     = bin2hex(random_bytes(16));
$clientSecret = bin2hex(random_bytes(32));
$redirectUri  = BASE_URL;   // use your dynamic base URL

$stmt = $pdo_user->prepare(
    "INSERT INTO clients (user_id, client_id, client_secret, redirect_uri)
     VALUES (?, ?, ?, ?)"
);
$stmt->execute([$userId, $clientId, $clientSecret, $redirectUri]);

// 10. Success → redirect back to home (or dashboard)
header('Location: ' . BASE_URL);
exit;