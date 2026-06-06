<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (!empty($_SESSION['loggedin'])) {
    header('Location: ' . BASE_URL . '/dashboard.php');
    exit;
}
require_once __DIR__ . '/includes/data.php';
require_once __DIR__ . '/includes/mailer.php';

$msg = '';
$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $users = leer_users();
    $admin_email = $users['admin']['email'] ?? '';

    if ($admin_email !== '' && $email === $admin_email) {
        $token   = bin2hex(random_bytes(32));
        $expires = time() + 3600; // 1 hora

        $users['admin']['reset_token']   = $token;
        $users['admin']['reset_expires'] = $expires;
        guardar_users($users);

        $proto = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $link  = $proto . '://' . $_SERVER['HTTP_HOST'] . BASE_URL . '/reset_contrasena.php?token=' . $token;

        $subject = 'Restablecer contraseña – CafePro';
        $body    = "Hola,\n\nRecibiste este correo porque solicitaste restablecer tu contraseña.\n\n"
                 . "Haz clic en el siguiente enlace (válido por 1 hora):\n$link\n\n"
                 . "Si no solicitaste esto, ignora este mensaje.";

        enviar_email($email, $subject, $body);
    }

    // Mismo mensaje siempre para no revelar si el correo existe
    $msg = 'Si el correo es correcto, recibirás un enlace en los próximos minutos.';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>CafePro – Olvidé mi contraseña</title>
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/style.css">
</head>
<body>
<div class="login-wrap">
    <div class="login-brand">
        <div class="brand-ico">📧</div>
        <h1>CafePro</h1>
        <hr>
        <p class="brand-sub">Recuperar<br>contraseña</p>
    </div>

    <div class="login-form-area">
        <form class="login-form" method="POST" action="">
            <h2>¿Olvidaste tu contraseña?</h2>
            <p>Ingresa tu correo y te enviaremos un enlace para restablecerla.</p>

            <?php if ($msg): ?>
            <div class="alert alert-green">✅ <?= htmlspecialchars($msg) ?></div>
            <?php endif; ?>
            <?php if ($err): ?>
            <div class="alert alert-red">❌ <?= htmlspecialchars($err) ?></div>
            <?php endif; ?>

            <?php if (!$msg): ?>
            <div class="form-group">
                <label class="form-label">CORREO ELECTRÓNICO</label>
                <input class="form-control" type="email" name="email"
                       placeholder="Ingresa tu correo" required autofocus>
            </div>
            <button class="btn btn-amber btn-full btn-lg mt-16" type="submit">
                Enviar enlace
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
