<?php
session_start();
require_once __DIR__ . '/config.php'; // Ensure BASE_URL is defined here
require_once __DIR__.'/router.php'; // Include routing functions
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>User Login – Based on OAuth System</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body { background-color: #f8f9fa; }
    .container { max-width: 450px; margin-top: 80px; }
    .card { border-radius: 10px; box-shadow: 0 0 15px rgba(0,0,0,0.1); }
  </style>
</head>
<body>
  <div class="container">
    <div class="card">
      <div class="card-body">
        <h4 class="card-title text-center mb-4">🔐 User Login</h4>
        <form action="<?php echo route('login_user'); ?>" method="POST" novalidate>
          <div class="mb-3">
            <label for="username" class="form-label">👤 Username</label>
            <input type="text" class="form-control" id="username" name="username" required>
          </div>
          <div class="mb-3">
            <label for="password" class="form-label">🔑 Password</label>
            <input type="password" class="form-control" id="password" name="password" required>
          </div>
          <button type="submit" class="btn btn-primary w-100">Login</button>
        </form>

        <div class="mt-3 text-center">
          <small>New user? <a href="<?php echo route('register'); ?>">Register here</a></small>
        </div>
      </div>
    </div>
  </div>

  <script>
    // Optional client-side validation
    (() => {
      'use strict';
      const form = document.querySelector('form');
      form.addEventListener('submit', event => {
        if (!form.checkValidity()) {
          event.preventDefault();
          event.stopPropagation();
        }
        form.classList.add('was-validated');
      }, false);
    })();
  </script>
</body>
</html>