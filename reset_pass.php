<?php
require_once __DIR__ . '/includes/data.php';
$hash = password_hash('1234', PASSWORD_DEFAULT);
db()->prepare("UPDATE usuarios SET password_hash = ? WHERE username = 'admin'")->execute([$hash]);
echo "Listo. Usuario: admin | Contraseña: 1234";
