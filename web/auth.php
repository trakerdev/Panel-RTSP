<?php
session_start([
    'cookie_httponly' => true,
    'cookie_samesite' => 'Lax'
]);

// Usuario web
define('AUTH_USER', 'USUARIO_WEB');

// Hash BCRYPT de la contraseña.
// Genera tu hash con: php -r "echo password_hash('TU_CONTRASEÑA', PASSWORD_BCRYPT) . PHP_EOL;"
// Hash de demostración correspondiente a la clave: 'CONTRASEÑA_WEB'
define('AUTH_PASS_HASH', '$2y$10$fW3qZ4gO7G4XWjU12j4j3eWn6pWz5A1pD3tqXoXqE2H9lO9eG5Ayy');

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
