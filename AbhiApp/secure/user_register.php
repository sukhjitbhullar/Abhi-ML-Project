<?php
require_once __DIR__ . '/config.php'; // Loads BASE_URL and ENVIRONMENT
require_once __DIR__ . '/router.php'; // Include routing functions
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>User Registration – Base on OAuth System</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body { background-color: #f8f9fa; }
    .container { max-width: 500px; margin-top: 80px; }
    .card { border-radius: 10px; box-shadow: 0 0 15px rgba(0,0,0,0.1); }
  </style>
</head>
<body>
  <div class="container">
    <div class="card">
      <div class="card-body">
        <h4 class="card-title text-center mb-4">📝 User Registration</h4>
       <form action="<?php echo BASE_URL . '/index.php?route=register_user'; ?>" method="POST" novalidate>
          <div class="mb-3">
            <label for="name" class="form-label">👤 Full Name</label>
            <input type="text" class="form-control" id="name" name="name" required>
          </div>
          <div class="mb-3">
            <label for="email" class="form-label">📧 Email Address</label>
            <input type="email" class="form-control" id="email" name="email" required>
          </div>
          <div class="mb-3">
            <label for="username" class="form-label">🆔 Username</label>
            <input type="text" class="form-control" id="username" name="username" required>
          </div>
          <div class="mb-3">
            <label for="password" class="form-label">🔒 Password</label>
            <input type="password" class="form-control" id="password" name="password" required>
          </div>
          <button type="submit" class="btn btn-success w-100">Register</button>
        </form>

        <div class="mt-3 text-center">
          <small>Already registered? <a href="<?php echo BASE_URL.'/index.php/route=login'; ?>">Login here</a></small>
        </div>
      </div>
    </div>
  </div>

  <script>
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