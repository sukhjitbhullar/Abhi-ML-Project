<?php
require_once __DIR__ . '/config.php'; // Ensure BASE_URL is defined here
require_once __DIR__. '/router.php'; // Include routing functions
require_once __DIR__ . '/oauthDB.php'; // DB connection

session_start();
header('Content-Type: text/html; charset=utf-8');

// $error_invalid = "Invalid username or password. Please try again";
// $msg_success   = "Login successful. Redirecting to authorization page...";


// Validate input
$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

if (empty($username) || empty($password)) {
    echo "Username and password are required.";
    exit;
}

try {
    // Step 1: Authenticate user
    $stmt = $pdo_user->prepare("SELECT id, password_hash FROM users WHERE username = ? OR email = ?");
    $stmt->execute([$username, $username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || !password_verify($password, $user['password_hash'])) {
        echo "<script>
                    alert('Invalid username or password. Please try again.');
                    window.location.href = '" . BASE_URL . "/index.php';
                </script>";
        exit;
    }

    // Step 2: Fetch client metadata
    $stmt = $pdo_user->prepare("SELECT client_id, redirect_uri, scope FROM clients WHERE user_id = ?");
    $stmt->execute([$user['id']]);
    $client = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$client) {
        echo "<script>
                    alert('No OAuth client configuration found for this user!Please contact admin.');
                    window.location.href = '" . BASE_URL . "/index.php';
                </script>";
        exit;
    }

    // Step 3: Store session data
    $_SESSION['user_id']      = $user['id'];
    $_SESSION['client_id']    = $client['client_id'];
    $_SESSION['redirect_uri'] = $client['redirect_uri'];
    $_SESSION['scope']        = $client['scope'];
    $_SESSION['state']        = bin2hex(random_bytes(8)); // CSRF protection

    // Step 4: Redirect to authorize.php
    //echo $msg_success;
    header("Location: ". route('authorize'));
    exit;

} catch (Exception $e) {
    // In production, log the error securely
    echo "An error occurred during login.";
    exit;
}
?>