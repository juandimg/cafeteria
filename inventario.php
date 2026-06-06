<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/data.php';
$productos = leer_productos();

$total_prods = count($productos);
$bajo_stock  = count(array_filter($productos, fn($p) => ($p['stock'] ?? 0) > 0 && ($p['stock'] ?? 0) <= 5));
$sin_stock   = count(array_filter($productos, fn($p) => ($p['stock'] ?? 0) === 0));
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>CafePro – Inventario</title>
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/style.css">
</head>
<body>
<div class="app">
<?php require __DIR__ . '/includes/header.php'; ?>
<div class="main-content">
    <div class="topbar">
        <span class="topbar-title">📦 Inventario</span>
        <span class="topbar-date" id="fecha-hoy"></span>
    </div>
    <div class="page-body">
        <div class="stats-row cols-3">
            <div class="stat-card bg-blue">
                <span class="stat-ico">📦</span>
                <div><div class="stat-val"><?= $total_prods ?></div><div class="stat-lbl">Total productos</div></div>
            </div>
            <div class="stat-card bg-amber">
                <span class="stat-ico">⚠️</span>
                <div><div class="stat-val"><?= $bajo_stock ?></div><div class="stat-lbl">Stock bajo (≤5)</div></div>
            </div>
            <div class="stat-card bg-red">
                <span class="stat-ico">🚫</span>
                <div><div class="stat-val"><?= $sin_stock ?></div><div class="stat-lbl">Sin stock</div></div>
            </div>
        </div>

        <div class="page-title-wrap">
            <div class="page-title">Gestión de Stock</div>
        </div>

        <?php if (empty($productos)): ?>
        <div class="empty-state">
            <div class="es-ico">📦</div>
            No hay productos registrados.<br>Ve a <a href="<?= BASE_URL ?>/productos.php">Productos</a> para agregar.
        </div>
        <?php else: ?>
        <div id="inv-list">
        <?php foreach ($productos as $prod):
            $stock = (int)($prod['stock'] ?? 0);
            if ($stock === 0)     { $color = 'var(--red)';   $badge = 'Sin stock'; }
            elseif ($stock <= 5)  { $color = 'var(--amber)'; $badge = 'Stock bajo'; }
            else                  { $color = 'var(--green)'; $badge = 'OK'; }
            $url = img_url($prod['img_path'] ?? null);
        ?>
        <div class="row-item">
            <div class="ri-thumb">
                <?php if ($url): ?>
                <img src="<?= htmlspecialchars($url) ?>" alt="">
                <?php else: ?>🍽️<?php endif; ?>
            </div>
            <div class="ri-main">
                <div class="ri-name"><?= htmlspecialchars($prod['nombre']) ?></div>
                <div class="ri-sub"><?= fmt_money($prod['precio']) ?></div>
            </div>
            <div style="text-align:center;min-width:80px">
                <div class="ri-stock-val" style="color:<?= $color ?>"><?= $stock ?></div>
                <div class="ri-stock-lbl" style="color:<?= $color ?>"><?= $badge ?></div>
                <div class="ri-sub">unidades</div>
            </div>
            <div class="ri-actions">
                <label style="font-size:11px;color:var(--txt-g)">Agregar:</label>
                <input type="number" class="qty-input" id="qty-<?= htmlspecialchars($prod['nombre']) ?>" placeholder="0">
                <button class="btn btn-green btn-sm"
                    onclick="ajustar('<?= htmlspecialchars(addslashes($prod['nombre'])) ?>')">✓</button>
            </div>
        </div>
        <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>
</div>

<script>
async function ajustar(nombre) {
    const inp = document.getElementById('qty-' + nombre);
    const delta = parseInt(inp.value) || 0;
    if (delta === 0) return;
    const fd = new FormData();
    fd.append('action', 'ajustar_stock');
    fd.append('nombre', nombre);
    fd.append('delta',  delta);
    await fetch(BASE_URL + '/api.php', {method:'POST', body:fd});
    location.reload();
}
const d=new Date();
const dias=['Domingo','Lunes','Martes','Miércoles','Jueves','Viernes','Sábado'];
const meses=['enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre'];
document.getElementById('fecha-hoy').textContent=`${dias[d.getDay()]} ${d.getDate()} de ${meses[d.getMonth()]} de ${d.getFullYear()}`;
</script>
</body>
</html>
