<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/oauthDB.php'; // uses $pdo_user
require_once __DIR__ . '/Mailer.php';
session_start();

$email = trim($_POST['email'] ?? '');
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['flash'] = 'Invalid email address.';
    header('Location: ' . route('forgot_password'));
    exit;
}

// Check if user exists
$stmt = $pdo_user->prepare("SELECT id FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user) {
    $_SESSION['flash'] = 'No account found with that email.';
    header('Location: ' . route('forgot_password'));
    exit;
}

// Generate secure token
$token = bin2hex(random_bytes(32));
$stmt = $pdo_user->prepare("INSERT INTO password_resets (email, token) VALUES (?, ?)");
$stmt->execute([$email, $token]);

// Build reset link
$resetLink = BASE_URL . '/index.php?route=reset_password&token=' . urlencode($token);
$message = "Click the link below to reset your password:\n\n$resetLink";

// Send email
if (Mailer::send($email, "Password Reset", $message)) {
    $_SESSION['flash'] = 'Reset link sent to your email.';
} else {
    $_SESSION['flash'] = 'Failed to send email. Try again later.';
}

header('Location: ' . route('forgot_password'));
exit;