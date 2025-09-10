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
  <!--<link rel="stylesheet" href="<?php echo BASE_URL; ?>/secure/assets/Style/myStyle.css?v=1.2"> My style sheet -->
  <style>
    body {
      font-family: 'Segoe UI', sans-serif;
      background-color: #f4f6f9;
      margin: 0;
      padding: 0;
    }

    .container {
      max-width: 400px;
      margin: 80px auto;
      background: #fff;
      padding: 30px;
      border-radius: 8px;
      box-shadow: 0 0 10px rgba(0,0,0,0.1);
    }

    h2 {
      margin-bottom: 20px;
      color: #333;
      text-align: center;
    }

    label {
      display: block;
      margin-bottom: 8px;
      font-weight: 500;
      color: #555;
    }

    input[type="password"] {
      width: 100%;
      padding: 10px;
      margin-bottom: 20px;
      border: 1px solid #ccc;
      border-radius: 4px;
      font-size: 14px;
    }
    .form-row {
      font-family: Arial, sans-serif;
      display: flex;
      gap: 40px;
      margin-bottom: 20px;
      flex-wrap: nowrap;
      justify-content: center;
      align-items: flex-start;
    }

    button {
      width: 100%;
      padding: 10px;
      background-color: #007bff;
      border: none;
      color: white;
      font-size: 15px;
      border-radius: 4px;
      cursor: pointer;
    }

    button:hover {
      background-color: #0056b3;
    }

    .flash {
      background-color: #e9f7ef;
      color: #2e7d32;
      padding: 10px;
      border-radius: 4px;
      margin-bottom: 15px;
      text-align: center;
    }

    .back-link {
      text-align: center;
      margin-top: 15px;
    }

    .back-link a {
      color: #007bff;
      text-decoration: none;
      font-size: 14px;
    }

    .back-link a:hover {
      text-decoration: underline;
    }
  </style>
</head>
<body>
  <div class="container">
    <h2 class="center-heading">Reset Your Password</h2>

    <?php if (isset($_SESSION['flash'])): ?>
      <div class="flash"><?= htmlspecialchars($_SESSION['flash']) ?></div>
      <?php unset($_SESSION['flash']); ?>
    <?php endif; ?>

    <form action="<?= route('update_password') ?>" method="POST" style="width: 100%; max-width: 400px;">
        <div class="form-row">
          <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
        </div>
        <div class="form-row">
          <label for="new_password">New Password:</label>
          <input type="password" name="new_password" id="new_password" required>
        </div>
        <div class="form-row">
          <button type="submit">Update Password</button>
        </div>

        
      </form>

    <div class="back-link" style="margin-top: 20px;">
      <a href="<?= route('login') ?>" style="color: #007bff; text-decoration: none;">← Back to Login</a>
    </div>
  </div>
</body>
</html>