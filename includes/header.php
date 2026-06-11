<?php
$pagina = basename($_SERVER['PHP_SELF'], '.php');
?>
<script>const BASE_URL = '<?= BASE_URL ?>';</script>
<?php
$nav_items = [
    ['dashboard', '🏠', 'Inicio'],
    ['productos',  '🍴', 'Productos'],
    ['proveedores','🤝', 'Proveedores'],
    ['inventario', '📦', 'Inventario'],
    ['compras',    '🛍️', 'Compras'],
    ['caja',       '💰', 'Caja'],
    ['clientes',   '👥', 'Clientes'],
    ['reportes',   '📊', 'Reportes'],
];
?>
<aside class="sidebar">
    <div class="sidebar-logo">☕ Iglesia Poblado</div>

    <nav class="sidebar-nav">
        <?php foreach ($nav_items as [$slug, $ico, $label]): ?>
        <a href="<?= BASE_URL ?>/<?= $slug ?>.php"
           class="nav-item <?= $pagina === $slug ? 'active' : '' ?>">
            <span class="nav-ico"><?= $ico ?></span> <?= $label ?>
        </a>
        <?php endforeach; ?>
    </nav>

    <a href="<?= BASE_URL ?>/cambiar_contrasena.php"
       class="nav-item <?= $pagina === 'cambiar_contrasena' ? 'active' : '' ?>"
       style="margin-top:auto">
        <span class="nav-ico">🔑</span> Cambiar contraseña
    </a>

    <div class="sidebar-footer">
        <span class="sidebar-avatar">👤</span>
        <div class="sidebar-user">
            <strong>Administrador</strong>
            <small>Acceso total</small>
        </div>
        <a href="<?= BASE_URL ?>/logout.php" class="btn-logout" title="Cerrar sesión">⏻</a>
    </div>
</aside>
