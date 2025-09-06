<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/oauthDB.php';
require_once __DIR__ . '/router.php';
session_start();

$token = $_GET['token'] ?? '';
if (empty($token)) {
    echo "Invalid or missing token.";
    exit;
}

// Validate token exists
$stmt = $pdo_user->prepare("SELECT email FROM password_resets WHERE token = ?");
$stmt->execute([$token]);
$reset = $stmt->fetch();

if (!$reset) {
    echo "Invalid or expired token.";
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Reset Password</title>
  <link rel="stylesheet" href="<?= BASE_URL ?>/secure/assets/style/mystyle.css">
</head>
<body>
  <div class="card">
    <h2 class="center-heading">Reset Your Password</h2>

    <?php if (isset($_SESSION['flash'])): ?>
      <div class="flash"><?= htmlspecialchars($_SESSION['flash']) ?></div>
      <?php unset($_SESSION['flash']); ?>
    <?php endif; ?>

    <form action="<?= route('update_password') ?>" method="POST" style="width: 100%; max-width: 400px;">
      <div class="form-row" style="flex-direction: column; gap: 12px;">
        <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">

        <label for="new_password">New Password:</label>
        <input type="password" name="new_password" id="new_password" required>

        <button type="submit">Update Password</button>
      </div>
    </form>

    <div class="back-link" style="margin-top: 20px;">
      <a href="<?= route('login') ?>" style="color: #007bff; text-decoration: none;">← Back to Login</a>
    </div>
  </div>
</body>
</html>