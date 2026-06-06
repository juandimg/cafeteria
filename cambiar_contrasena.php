<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/data.php';

$msg = '';
$err = '';

$users = leer_users();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion    = $_POST['accion']    ?? '';
    $actual    = $_POST['actual']    ?? '';
    $nueva     = $_POST['nueva']     ?? '';
    $confirmar = $_POST['confirmar'] ?? '';
    $email     = trim($_POST['email'] ?? '');

    if ($accion === 'email') {
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $err = 'El correo ingresado no es válido.';
        } elseif (!password_verify($actual, $users['admin']['password'] ?? '')) {
            $err = 'La contraseña actual es incorrecta.';
        } else {
            $users['admin']['email'] = $email;
            guardar_users($users);
            $msg = 'Correo de recuperación guardado.';
        }
    } else {
        if (!password_verify($actual, $users['admin']['password'] ?? '')) {
            $err = 'La contraseña actual es incorrecta.';
        } elseif (strlen($nueva) < 4) {
            $err = 'La nueva contraseña debe tener al menos 4 caracteres.';
        } elseif ($nueva !== $confirmar) {
            $err = 'Las contraseñas nuevas no coinciden.';
        } else {
            $users['admin']['password'] = password_hash($nueva, PASSWORD_DEFAULT);
            guardar_users($users);
            $msg = 'Contraseña cambiada correctamente.';
        }
    }
    $users = leer_users();
}

$email_guardado = $users['admin']['email'] ?? '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>CafePro – Cambiar contraseña</title>
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/style.css">
</head>
<body>
<div class="app">
<?php require __DIR__ . '/includes/header.php'; ?>
<div class="main-content">
    <div class="topbar">
        <span class="topbar-title">🔑 Cambiar contraseña</span>
    </div>

    <div style="max-width:480px;margin:40px auto;padding:0 16px;display:flex;flex-direction:column;gap:20px">

        <?php if ($msg): ?>
        <div class="alert alert-green">✅ <?= htmlspecialchars($msg) ?></div>
        <?php endif; ?>
        <?php if ($err): ?>
        <div class="alert alert-red">❌ <?= htmlspecialchars($err) ?></div>
        <?php endif; ?>

        <!-- Cambiar contraseña -->
        <div class="card" style="padding:28px">
            <h2 style="margin:0 0 4px;font-size:17px">Cambiar contraseña</h2>
            <p style="color:var(--txt-g);font-size:13px;margin-bottom:20px">
                Ingresa tu contraseña actual y luego la nueva.
            </p>
            <form method="POST" action="">
                <input type="hidden" name="accion" value="password">
                <div class="form-group">
                    <label class="form-label">CONTRASEÑA ACTUAL</label>
                    <input class="form-control" type="password" name="actual"
                           placeholder="Ingresa tu contraseña actual" required>
                </div>
                <div class="form-group">
                    <label class="form-label">NUEVA CONTRASEÑA</label>
                    <input class="form-control" type="password" name="nueva"
                           placeholder="Mínimo 4 caracteres" required>
                </div>
                <div class="form-group">
                    <label class="form-label">CONFIRMAR NUEVA CONTRASEÑA</label>
                    <input class="form-control" type="password" name="confirmar"
                           placeholder="Repite la nueva contraseña" required>
                </div>
                <button class="btn btn-amber btn-full btn-lg mt-16" type="submit">
                    Guardar nueva contraseña
                </button>
            </form>
        </div>

        <!-- Correo de recuperación -->
        <div class="card" style="padding:28px">
            <h2 style="margin:0 0 4px;font-size:17px">Correo de recuperación</h2>
            <p style="color:var(--txt-g);font-size:13px;margin-bottom:20px">
                Si olvidas tu contraseña, se enviará un enlace a este correo.
            </p>
            <form method="POST" action="">
                <input type="hidden" name="accion" value="email">
                <div class="form-group">
                    <label class="form-label">CORREO ELECTRÓNICO</label>
                    <input class="form-control" type="email" name="email"
                           placeholder="admin@ejemplo.com"
                           value="<?= htmlspecialchars($email_guardado) ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">CONTRASEÑA ACTUAL (para confirmar)</label>
                    <input class="form-control" type="password" name="actual"
                           placeholder="Ingresa tu contraseña actual" required>
                </div>
                <button class="btn btn-full btn-lg mt-8" type="submit"
                        style="background:var(--blue);color:#fff">
                    Guardar correo
                </button>
            </form>
        </div>

    </div>
</div>
</div>
</body>
</html>
