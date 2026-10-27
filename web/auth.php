<?php
session_start([
    'cookie_httponly' => true,
    'cookie_samesite' => 'Lax'
]);

// Configuración de credenciales predeterminadas
define('AUTH_USER', 'admin');
// Hash BCRYPT generado con password_hash('TuPassword', PASSWORD_BCRYPT)
define('AUTH_PASS_HASH', '$2y$10$7R4dSmJ72r78UaA5tVw8zOQh2Y7c9m0H3f6b8D2u1K9v5M4L7w2pG');

if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
    header('Location: login.php');
    exit;
}

function check_auth() {
    if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
        if (basename($_SERVER['PHP_SELF']) === 'api.php' || basename($_SERVER['PHP_SELF']) === 'api_history.php') {
            header('Content-Type: application/json', true, 401);
            echo json_encode(['error' => 'No autorizado']);
            exit;
        }
        header('Location: login.php');
        exit;
    }
}
