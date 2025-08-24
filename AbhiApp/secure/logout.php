<?php
require_once __DIR__ . '/config.php'; // Adjust path based on file location
session_start();

// Clear session variables
session_unset();
session_destroy();

// Optional: Clear access token cookie
//setcookie('access_token', '', time() - 3600, '/', '', true, true);

// Redirect to login page
header('Location:'.BASE_URL.'/index.php');
exit;
?>