<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/oauthDB.php';
require_once __DIR__ . '/router.php';
session_start();

$token = $_POST['token'] ?? '';
$newPassword = $_POST['new_password'] ?? '';

if (empty($token) || empty($newPassword)) {
    $_SESSION['flash'] = 'Missing token or password.';
    header('Location: ' . route('reset_password') . '&token=' . urlencode($token));
    exit;
}

// Validate token
$stmt = $pdo_user->prepare("SELECT email FROM password_resets WHERE token = ?");
$stmt->execute([$token]);
$reset = $stmt->fetch();

if (! $reset) {
    $_SESSION['flash'] = 'Invalid or expired token.';
    header('Location: ' . route('reset_password') . '&token=' . urlencode($token));
    exit;
}

$email = $reset['email'];
$hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

// Update password
$stmt = $pdo_user->prepare("UPDATE users SET password = ? WHERE email = ?");
$stmt->execute([$hashedPassword, $email]);

// Clean up token
$stmt = $pdo_user->prepare("DELETE FROM password_resets WHERE token = ?");
$stmt->execute([$token]);

$_SESSION['flash'] = 'Password updated successfully. You can now log in.';
header('Location: ' . route('login'));
exit;