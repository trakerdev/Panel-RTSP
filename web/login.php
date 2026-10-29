<?php
require_once __DIR__ . '/auth.php';

$error = '';

if (isset($_SESSION['authenticated']) && $_SESSION['authenticated'] === true) {
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = trim($_POST['username'] ?? '');
    $pass = $_POST['password'] ?? '';

    if ($user === AUTH_USER && password_verify($pass, AUTH_PASS_HASH)) {
        session_regenerate_id(true);
        $_SESSION['authenticated'] = true;
        $_SESSION['username'] = $user;
        header('Location: index.php');
        exit;
    } else {
        $error = 'Usuario o contraseña incorrectos';
    }
}
?>
<!DOCTYPE html>
<html lang="es" data-bs-theme="dark">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Acceso al Sistema - Cámaras</title>
  <!-- Dependencias CSS (se pueden usar locales o desde CDN) -->
  <link href="bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="materialsymbolsoutlined.css" />
  <style>
    body {
      background-color: #0d1117;
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
    }
    .login-box {
      width: 100%;
      max-width: 400px;
      background-color: #161b22;
      border: 1px solid #30363d;
      border-radius: 0.75rem;
    }
    .form-control:focus {
      background-color: #0d1117;
      border-color: #58a6ff;
      color: #e6edf3;
      box-shadow: 0 0 0 0.25rem rgba(88, 166, 255, 0.25);
    }
  </style>
</head>
<body class="p-3">

  <div class="login-box p-4 shadow-lg">
    <div class="text-center mb-4">
      <span class="material-symbols-outlined text-primary" style="font-size: 3rem;">shield</span>
      <h1 class="h4 mt-2 fw-bold text-white">Panel de Seguridad</h1>
      <p class="text-secondary small">Ingresa tus credenciales para continuar</p>
    </div>

    <?php if ($error): ?>
      <div class="alert alert-danger d-flex align-items-center gap-2 py-2 small" role="alert">
        <span class="material-symbols-outlined fs-5">error</span>
        <div><?= htmlspecialchars($error) ?></div>
      </div>
    <?php endif; ?>

    <form method="POST" action="login.php">
      <div class="mb-3">
        <label class="form-label small text-secondary">Usuario</label>
        <div class="input-group">
          <span class="input-group-text bg-dark border-secondary text-secondary">
            <span class="material-symbols-outlined fs-6">person</span>
          </span>
          <input type="text" name="username" class="form-control bg-dark border-secondary text-white" required autofocus autocomplete="username">
        </div>
      </div>

      <div class="mb-4">
        <label class="form-label small text-secondary">Contraseña</label>
        <div class="input-group">
          <span class="input-group-text bg-dark border-secondary text-secondary">
            <span class="material-symbols-outlined fs-6">lock</span>
          </span>
          <input type="password" name="password" class="form-control bg-dark border-secondary text-white" required autocomplete="current-password">
        </div>
      </div>

      <button type="submit" class="btn btn-primary w-100 d-flex align-items-center justify-content-center gap-2 fw-semibold">
        <span class="material-symbols-outlined fs-6">login</span>
        <span>Iniciar Sesión</span>
      </button>
    </form>
  </div>

</body>
</html>
