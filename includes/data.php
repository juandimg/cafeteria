<?php
require_once __DIR__ . '/db.php';

define('UPLOADS_DIR', __DIR__ . '/../uploads/');

function db(): PDO { return get_pdo(); }

// ---- Utilidades generales -----------------------------------

function img_url(?string $img_path): ?string {
    if (!$img_path) return null;
    if (str_starts_with($img_path, 'uploads/')) return BASE_URL . '/' . $img_path;
    return null;
}

function fmt_money(float $val): string {
    return '$ ' . number_format($val, 0, ',', '.');
}

// ---- Productos ----------------------------------------------

function leer_productos(): array {
    return db()->query('SELECT * FROM productos ORDER BY nombre')->fetchAll();
}

function buscar_producto_por_id(int $id): ?array {
    $st = db()->prepare('SELECT * FROM productos WHERE id = ?');
    $st->execute([$id]);
    $row = $st->fetch();
    return $row ?: null;
}

function agregar_producto(array $p): void {
    $st = db()->prepare('INSERT INTO productos (nombre, precio, img_path, stock) VALUES (?, ?, ?, ?)');
    $st->execute([$p['nombre'], $p['precio'], $p['img_path'], $p['stock']]);
}

function actualizar_producto(int $id, array $p): void {
    $st = db()->prepare('UPDATE productos SET nombre=?, precio=?, img_path=?, stock=? WHERE id=?');
    $st->execute([$p['nombre'], $p['precio'], $p['img_path'], $p['stock'], $id]);
}

function eliminar_producto(int $id): void {
    db()->prepare('DELETE FROM productos WHERE id=?')->execute([$id]);
}

function ajustar_stock_por_nombre(string $nombre, int $delta): void {
    $st = db()->prepare('UPDATE productos SET stock = GREATEST(0, stock + ?) WHERE nombre = ?');
    $st->execute([$delta, $nombre]);
}

// ---- Ventas -------------------------------------------------

function leer_ventas(): array {
    $ventas = db()->query('SELECT * FROM ventas ORDER BY fecha DESC, hora DESC')->fetchAll();
    $si = db()->prepare('SELECT * FROM venta_items WHERE venta_id = ?');
    foreach ($ventas as &$v) {
        $si->execute([$v['id']]);
        $v['items'] = $si->fetchAll();
    }
    return $ventas;
}

function guardar_venta(array $venta): void {
    $d = db();
    $st = $d->prepare('INSERT INTO ventas (fecha, hora, medio_pago, total) VALUES (?, ?, ?, ?)');
    $st->execute([$venta['fecha'], $venta['hora'], $venta['medio_pago'], $venta['total']]);
    $id = (int)$d->lastInsertId();

    $si = $d->prepare('INSERT INTO venta_items (venta_id, nombre, cantidad, precio, subtotal) VALUES (?, ?, ?, ?, ?)');
    foreach ($venta['items'] as $item) {
        $si->execute([$id, $item['nombre'], (int)$item['cantidad'], (float)$item['precio'], (float)$item['subtotal']]);
    }
}

// ---- Proveedores --------------------------------------------

function leer_proveedores(): array {
    return db()->query('SELECT * FROM proveedores ORDER BY nombre')->fetchAll();
}

function agregar_proveedor(array $p): void {
    $st = db()->prepare('INSERT INTO proveedores (nombre, contacto, telefono, email, notas) VALUES (?, ?, ?, ?, ?)');
    $st->execute([$p['nombre'], $p['contacto'], $p['telefono'], $p['email'], $p['notas']]);
}

function actualizar_proveedor(int $id, array $p): void {
    $st = db()->prepare('UPDATE proveedores SET nombre=?, contacto=?, telefono=?, email=?, notas=? WHERE id=?');
    $st->execute([$p['nombre'], $p['contacto'], $p['telefono'], $p['email'], $p['notas'], $id]);
}

function eliminar_proveedor(int $id): void {
    db()->prepare('DELETE FROM proveedores WHERE id=?')->execute([$id]);
}

// ---- Compras ------------------------------------------------

function leer_compras(): array {
    $compras = db()->query('SELECT * FROM compras ORDER BY fecha DESC, id DESC')->fetchAll();
    $si = db()->prepare('SELECT * FROM compra_items WHERE compra_id = ?');
    foreach ($compras as &$c) {
        $si->execute([$c['id']]);
        $c['items'] = $si->fetchAll();
    }
    return $compras;
}

function guardar_compra(array $c): void {
    $d = db();
    $st = $d->prepare('INSERT INTO compras (fecha, proveedor, total, notas) VALUES (?, ?, ?, ?)');
    $st->execute([$c['fecha'], $c['proveedor'], $c['total'], $c['notas']]);
    $id = (int)$d->lastInsertId();

    $si = $d->prepare('INSERT INTO compra_items (compra_id, producto, cantidad, costo_unitario, subtotal) VALUES (?, ?, ?, ?, ?)');
    foreach ($c['items'] as $item) {
        $si->execute([$id, $item['producto'], $item['cantidad'], $item['costo_unitario'], $item['subtotal']]);
    }
}

function eliminar_compra(int $id): void {
    db()->prepare('DELETE FROM compras WHERE id=?')->execute([$id]);
}

// ---- Caja ---------------------------------------------------

function leer_caja(): array {
    $row = db()->query('SELECT * FROM caja WHERE id = 1')->fetch();
    return $row ?: ['fecha' => '', 'base' => 0];
}

function guardar_caja(float $base): void {
    $st = db()->prepare('INSERT INTO caja (id, fecha, base) VALUES (1, ?, ?) ON DUPLICATE KEY UPDATE fecha=VALUES(fecha), base=VALUES(base)');
    $st->execute([date('Y-m-d'), $base]);
}

// ---- Usuarios -----------------------------------------------

function leer_users(): array {
    $row = db()->query("SELECT * FROM usuarios WHERE username = 'admin'")->fetch();
    if (!$row) {
        $hash = password_hash('1234', PASSWORD_DEFAULT);
        db()->prepare("INSERT INTO usuarios (username, password_hash, email) VALUES ('admin', ?, '')")->execute([$hash]);
        return ['admin' => ['password' => $hash, 'email' => '', 'reset_token' => null, 'reset_expires' => null]];
    }
    return ['admin' => [
        'password'      => $row['password_hash'],
        'email'         => $row['email'],
        'reset_token'   => $row['reset_token'],
        'reset_expires' => $row['reset_expires'],
    ]];
}

function guardar_users(array $data): void {
    $admin = $data['admin'];
    $st = db()->prepare("UPDATE usuarios SET password_hash=?, email=?, reset_token=?, reset_expires=? WHERE username='admin'");
    $st->execute([$admin['password'], $admin['email'] ?? '', $admin['reset_token'], $admin['reset_expires']]);
}
