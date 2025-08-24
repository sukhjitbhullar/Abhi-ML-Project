<?php
// Load environment variables via config
require_once __DIR__ . '/secure/config.php';
require_once __DIR__ . '/secure/router.php';

function getRoute(): string {
    $route = $_GET['route'] ?? '';
    $route = trim(strtolower($route), "/\\ \t\n\r\0\x0B");
    return $route !== '' ? $route : 'login'; // or 'home', 'dashboard', etc.
}


 // Map route names to file paths.
 
function resolveRoute(string $route): string {
    $routes = [
        'login'           => BASE_PATH . '/secure/login_page.php',
        'login_user'  => BASE_PATH . '/secure/login_user.php',
        'user_registeration' => BASE_PATH . '/secure/user_register.php',
        'register_user' => BASE_PATH . '/secure/user_register_action.php',
        'authorize'   => BASE_PATH . '/secure/authorize.php',
        'register'    => BASE_PATH . '/secure/user_register.php',
        'issue_token' => BASE_PATH . '/secure/issue-token.php',
        'dashboard'   => BASE_PATH . '/secure/dashboard.php',
        'logout'      => BASE_PATH . '/secure/logout.php',
        'fetch_cities' => BASE_PATH . '/secure/fetch_cities.php',
        'fetch_temperature' => BASE_PATH . '/secure/fetch_temperature.php',
        'download_excel' => BASE_PATH . '/secure/download_excel.php',
        'download_json' => BASE_PATH . '/secure/download_json.php',

    ];

    return $routes[$route] ?? '';
}

// Get the cleaned route
$path = getRoute();
$targetFile = resolveRoute($path);

// Debug output
//echo "<pre>Route: {$path}\nTarget: {$targetFile}</pre>";


// Dispatch
if ($targetFile && file_exists($targetFile)) {
    require $targetFile;
} else {
    http_response_code(404);
    echo "Page not found.";
}



?>

