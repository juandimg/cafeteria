<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (!empty($_SESSION['loggedin'])) {
    header('Location: ' . BASE_URL . '/dashboard.php');
    exit;
}

require_once __DIR__ . '/includes/data.php';
$users = leer_users();

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = trim($_POST['usuario'] ?? '');
    $pass = $_POST['password'] ?? '';
    $hash = $users['admin']['password'] ?? '';
    if ($user === 'admin' && password_verify($pass, $hash)) {
        $_SESSION['loggedin'] = true;
        $_SESSION['usuario']  = 'admin';
        header('Location: ' . BASE_URL . '/dashboard.php');
        exit;
    }
    $error = 'Usuario o contraseña incorrectos.';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>CafePro – Iniciar sesión</title>
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/style.css">
</head>
<body>
<div class="login-wrap">
    <div class="login-brand">
        <div class="brand-ico">☕</div>
        <h1>CafePro</h1>
        <hr>
        <p class="brand-sub">Sistema de Gestión<br>de Cafetería</p>
        <div class="brand-mod">
            🍴&nbsp; Pedidos<br>
            📋&nbsp; Menú<br>
            📦&nbsp; Inventario<br>
            💰&nbsp; Caja<br>
            📊&nbsp; Reportes
        </div>
    </div>

    <div class="login-form-area">
        <form class="login-form" method="POST" action="">
            <h2>Bienvenido</h2>
            <p>Ingresa tus credenciales para continuar</p>

            <?php if ($error): ?>
            <div class="alert alert-red">❌ <?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <div class="form-group">
                <label class="form-label">USUARIO</label>
                <input class="form-control" type="text" name="usuario"
                       placeholder="Ingresa tu usuario" autofocus
                       value="<?= htmlspecialchars($_POST['usuario'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label class="form-label">CONTRASEÑA</label>
                <input class="form-control" type="password" name="password"
                       placeholder="Ingresa tu contraseña">
            </div>

            <button class="btn btn-amber btn-full btn-lg mt-16" type="submit">
                INICIAR SESIÓN
            </button>

            <div style="text-align:center;margin-top:12px">
                <a href="<?= BASE_URL ?>/olvide_contrasena.php"
                   style="font-size:13px;color:var(--txt-g);text-decoration:none">
                   ¿Olvidaste tu contraseña?
                </a>
            </div>
        </form>
        <div class="login-footer">© JDSoftware solutions</div>
    </div>
</div>
</body>
</html>
