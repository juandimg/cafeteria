<?php
// ─── Entorno ───────────────────────────────────────────────────────────────
$_is_local = (($_SERVER['HTTP_HOST'] ?? '') === 'localhost');

define('BASE_URL', $_is_local ? '/Iglesia' : '');

// ─── Base de datos ─────────────────────────────────────────────────────────
if ($_is_local) {
    define('DB_HOST', 'localhost');
    define('DB_NAME', 'cafepro');
    define('DB_USER', 'root');
    define('DB_PASS', 'Esognare2020.');
} else {
    // En producción usa variables de entorno de Render
    define('DB_HOST', getenv('DB_HOST') ?: 'sql102.infinityfree.com');
    define('DB_NAME', getenv('DB_NAME') ?: 'if0_42110635_cafepro');
    define('DB_USER', getenv('DB_USER') ?: 'if0_42110635');
    define('DB_PASS', getenv('DB_PASS') ?: 'Juandiluz10');
}

// ─── Configuración SMTP ────────────────────────────────────────────────────
// Gmail: usa una Contraseña de Aplicación, NO tu contraseña normal.
// Cómo obtenerla: myaccount.google.com → Seguridad → Verificación en 2 pasos
//                 → Contraseñas de aplicaciones → Generar

define('SMTP_HOST',      'smtp.gmail.com');
define('SMTP_PORT',      587);
define('SMTP_USER',      'ovelogic1@gmail.com');   // <-- cambia esto
define('SMTP_PASS',      'rfxutqhlrwyhbylw');    // <-- pega aquí la contraseña de app
define('SMTP_FROM_NAME', 'CafePro');
