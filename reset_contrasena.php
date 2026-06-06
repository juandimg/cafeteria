<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (!empty($_SESSION['loggedin'])) {
    header('Location: ' . BASE_URL . '/dashboard.php');
    exit;
}
require_once __DIR__ . '/includes/data.php';

$token   = trim($_GET['token'] ?? '');
$users   = leer_users();
$admin   = $users['admin'] ?? [];
$msg     = '';
$err     = '';
$valido  = false;

if ($token !== ''
    && isset($admin['reset_token'])
    && hash_equals($admin['reset_token'], $token)
    && isset($admin['reset_expires'])
    && time() < $admin['reset_expires']
) {
    $valido = true;
}

if ($valido && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $nueva     = $_POST['nueva']     ?? '';
    $confirmar = $_POST['confirmar'] ?? '';

    if (strlen($nueva) < 4) {
        $err = 'La contraseña debe tener al menos 4 caracteres.';
    } elseif ($nueva !== $confirmar) {
        $err = 'Las contraseñas no coinciden.';
    } else {
        $users['admin']['password']      = password_hash($nueva, PASSWORD_DEFAULT);
        $users['admin']['reset_token']   = null;
        $users['admin']['reset_expires'] = null;
        guardar_users($users);
        $msg = 'Contraseña restablecida correctamente.';
        $valido = false;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>CafePro – Restablecer contraseña</title>
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/style.css">
</head>
<body>
<div class="login-wrap">
    <div class="login-brand">
        <div class="brand-ico">🔓</div>
        <h1>CafePro</h1>
        <hr>
        <p class="brand-sub">Restablecer<br>contraseña</p>
    </div>

    <div class="login-form-area">
        <form class="login-form" method="POST" action="">
            <h2>Nueva contraseña</h2>

            <?php if ($msg): ?>
            <div class="alert alert-green" style="margin-bottom:16px">
                ✅ <?= htmlspecialchars($msg) ?>
            </div>
            <a href="<?= BASE_URL ?>/index.php" class="btn btn-amber btn-full btn-lg">
                Ir al login
            </a>

            <?php elseif (!$valido): ?>
            <div class="alert alert-red">
                ❌ El enlace no es válido o ya expiró.<br>
                <a href="<?= BASE_URL ?>/olvide_contrasena.php">Solicitar un nuevo enlace</a>
            </div>

            <?php else: ?>
            <?php if ($err): ?>
            <div class="alert alert-red" style="margin-bottom:12px">❌ <?= htmlspecialchars($err) ?></div>
            <?php endif; ?>
            <p style="font-size:13px;color:var(--txt-g);margin-bottom:16px">
                Ingresa tu nueva contraseña.
            </p>
            <div class="form-group">
                <label class="form-label">NUEVA CONTRASEÑA</label>
                <input class="form-control" type="password" name="nueva"
                       placeholder="Mínimo 4 caracteres" required autofocus>
            </div>
            <div class="form-group">
                <label class="form-label">CONFIRMAR CONTRASEÑA</label>
                <input class="form-control" type="password" name="confirmar"
                       placeholder="Repite la contraseña" required>
            </div>
            <button class="btn btn-amber btn-full btn-lg mt-16" type="submit">
                Guardar nueva contraseña
            </button>
            <?php endif; ?>

            <div style="text-align:center;margin-top:14px">
                <a href="<?= BASE_URL ?>/index.php"
                   style="font-size:13px;color:var(--txt-g);text-decoration:none">
                    ← Volver al login
                </a>
            </div>
        </form>
        <div class="login-footer">© JDSoftware solutions</div>
    </div>
</div>
</body>
</html>
