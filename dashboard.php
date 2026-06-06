<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/data.php';
$productos = leer_productos();
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>CafePro – Inicio</title>
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/style.css">
</head>
<body>
<div class="app">
<?php require __DIR__ . '/includes/header.php'; ?>
<div class="main-content">
    <div class="topbar">
        <span class="topbar-title">🏠 Inicio</span>
        <span class="topbar-date" id="fecha-hoy"></span>
    </div>

    <div class="pos-layout">
        <!-- Catálogo -->
        <div class="pos-catalog">
            <div class="page-title-wrap">
                <div class="page-title">Productos disponibles</div>
            </div>
            <div class="product-grid">
            <?php foreach ($productos as $prod):
                $url = img_url($prod['img_path'] ?? null);
                $sin_stock = ($prod['stock'] ?? 0) <= 0;
            ?>
            <div class="product-card">
                <div class="prod-img">
                    <?php if ($url): ?>
                    <img src="<?= htmlspecialchars($url) ?>" alt="">
                    <?php else: ?>🍽️<?php endif; ?>
                </div>
                <div class="prod-body">
                    <div class="prod-name"><?= htmlspecialchars($prod['nombre']) ?></div>
                    <div class="prod-price"><?= fmt_money($prod['precio']) ?></div>
                    <div class="prod-stock">Stock: <?= (int)($prod['stock'] ?? 0) ?></div>
                    <div class="prod-actions">
                        <?php if ($sin_stock): ?>
                        <button class="btn btn-full" disabled>Sin stock</button>
                        <?php else: ?>
                        <button class="btn btn-amber btn-full"
                            onclick='addToCart(<?= json_encode([
                                "nombre"  => $prod["nombre"],
                                "precio"  => (float)$prod["precio"],
                                "stock"   => (int)($prod["stock"] ?? 0),
                            ]) ?>)'>+ Agregar</button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
            <?php if (empty($productos)): ?>
            <div class="empty-state" style="grid-column:1/-1">
                <div class="es-ico">🍽️</div>
                Sin productos registrados. Ve a <a href="<?= BASE_URL ?>/productos.php">Productos</a> para agregar.
            </div>
            <?php endif; ?>
            </div>
        </div>

        <!-- Carrito -->
        <div class="pos-cart">
            <div class="cart-header">🛒 Venta actual</div>
            <div class="cart-items" id="cart-items">
                <div class="cart-empty" id="cart-empty">
                    Sin productos.<br>Toca + Agregar para iniciar la venta.
                </div>
            </div>
            <div class="cart-footer">
                <hr style="border:none;border-top:1px solid #E0E0E0;margin-bottom:10px">
                <div class="cart-total-row">
                    <span class="cart-total-lbl">TOTAL</span>
                    <span class="cart-total-val" id="cart-total">$ 0</span>
                </div>
                <button class="btn btn-green btn-full btn-lg" onclick="openModal()" id="btn-cobrar">Cobrar $ 0</button>
                <button class="btn btn-red btn-full mt-8" onclick="cancelarVenta()">✕ Cancelar venta</button>
            </div>
        </div>
    </div>
</div>
</div>

<!-- Modal cobro -->
<div class="modal-overlay" id="modal-cobro">
<div class="modal">
    <div class="modal-title">Total a cobrar</div>
    <div class="modal-total" id="modal-total-val">$ 0</div>

    <p style="font-size:12px;font-weight:700;color:var(--txt-g);margin-bottom:8px">MEDIO DE PAGO</p>
    <div class="payment-btns">
        <button class="payment-btn" id="btn-ef" onclick="selMedio('Efectivo')"
            style="background:var(--green);color:#fff">💵 Efectivo</button>
        <button class="payment-btn" id="btn-tr" onclick="selMedio('Transferencia')"
            style="background:#EEEEEE;color:var(--slate)">📱 Transferencia</button>
    </div>

    <div id="ef-section">
        <div class="flex-row" style="margin-bottom:6px">
            <label style="font-size:12px;color:var(--txt-g)">Recibido ($):</label>
            <input type="number" id="inp-recibido" class="form-control"
                   style="width:140px;height:36px" placeholder="0" oninput="calcCambio()">
        </div>
        <div id="lbl-cambio" style="font-size:12px;font-weight:700;color:var(--blue);margin-bottom:8px">Cambio: —</div>
    </div>

    <div id="modal-err" class="alert alert-red" style="display:none"></div>

    <button class="btn btn-amber btn-full btn-lg" onclick="confirmarVenta()">✓ Confirmar venta</button>
    <button class="btn btn-ghost btn-full mt-8" onclick="closeModal()">Cancelar</button>
</div>
</div>

<script>
let cart = [];
let medio = 'Efectivo';

const stockMap = <?= json_encode(
    array_combine(
        array_column($productos, 'nombre'),
        array_map(fn($p) => (int)($p['stock'] ?? 0), $productos)
    )
) ?>;

function fmt(n) {
    return '$ ' + Math.round(n).toLocaleString('es-CO');
}

function addToCart(prod) {
    const idx = cart.findIndex(i => i.nombre === prod.nombre);
    const maxStock = stockMap[prod.nombre] ?? 0;
    if (idx >= 0) {
        if (cart[idx].cantidad < maxStock) cart[idx].cantidad++;
    } else {
        if (maxStock > 0) cart.push({nombre: prod.nombre, precio: prod.precio, cantidad: 1});
    }
    renderCart();
}

function renderCart() {
    const container = document.getElementById('cart-items');
    const empty     = document.getElementById('cart-empty');
    container.innerHTML = '';

    if (cart.length === 0) {
        container.innerHTML = '<div class="cart-empty">Sin productos.<br>Toca + Agregar para iniciar la venta.</div>';
        document.getElementById('cart-total').textContent = '$ 0';
        document.getElementById('btn-cobrar').textContent = 'Cobrar $ 0';
        return;
    }

    cart.forEach((item, i) => {
        const sub = item.precio * item.cantidad;
        const div = document.createElement('div');
        div.className = 'cart-item';
        div.innerHTML = `
            <div class="cart-item-top">
                <span class="cart-item-name">${item.nombre}</span>
                <button class="qty-btn" style="background:#FFEBEE;color:var(--red)" onclick="rmItem(${i})">×</button>
            </div>
            <div class="cart-item-bot">
                <button class="qty-btn" onclick="dec(${i})">−</button>
                <span class="cart-qty">${item.cantidad}</span>
                <button class="qty-btn" onclick="inc(${i})">+</button>
                <span class="cart-sub">${fmt(sub)}</span>
            </div>`;
        container.appendChild(div);
    });

    const total = cart.reduce((s, i) => s + i.precio * i.cantidad, 0);
    document.getElementById('cart-total').textContent = fmt(total);
    document.getElementById('btn-cobrar').textContent = 'Cobrar ' + fmt(total);
}

function rmItem(i) { cart.splice(i, 1); renderCart(); }
function inc(i) {
    const max = stockMap[cart[i].nombre] ?? 0;
    if (cart[i].cantidad < max) { cart[i].cantidad++; renderCart(); }
}
function dec(i) {
    if (cart[i].cantidad > 1) { cart[i].cantidad--; } else { cart.splice(i, 1); }
    renderCart();
}
function cancelarVenta() { cart = []; renderCart(); }

function openModal() {
    if (cart.length === 0) return;
    const total = cart.reduce((s, i) => s + i.precio * i.cantidad, 0);
    document.getElementById('modal-total-val').textContent = fmt(total);
    document.getElementById('inp-recibido').value = '';
    document.getElementById('lbl-cambio').textContent = 'Cambio: —';
    document.getElementById('modal-err').style.display = 'none';
    selMedio('Efectivo');
    document.getElementById('modal-cobro').classList.add('open');
}
function closeModal() { document.getElementById('modal-cobro').classList.remove('open'); }

function selMedio(m) {
    medio = m;
    const ef = document.getElementById('btn-ef');
    const tr = document.getElementById('btn-tr');
    const sec = document.getElementById('ef-section');
    if (m === 'Efectivo') {
        ef.style.cssText = 'background:var(--green);color:#fff;flex:1;height:42px;border-radius:10px;border:none;cursor:pointer;font-size:13px;font-weight:700';
        tr.style.cssText = 'background:#EEEEEE;color:var(--slate);flex:1;height:42px;border-radius:10px;border:none;cursor:pointer;font-size:13px;font-weight:700';
        sec.style.display = '';
    } else {
        tr.style.cssText = 'background:var(--blue);color:#fff;flex:1;height:42px;border-radius:10px;border:none;cursor:pointer;font-size:13px;font-weight:700';
        ef.style.cssText = 'background:#EEEEEE;color:var(--slate);flex:1;height:42px;border-radius:10px;border:none;cursor:pointer;font-size:13px;font-weight:700';
        sec.style.display = 'none';
    }
}

function calcCambio() {
    const total = cart.reduce((s, i) => s + i.precio * i.cantidad, 0);
    const rec   = parseFloat(document.getElementById('inp-recibido').value) || 0;
    const cambio = rec - total;
    const lbl = document.getElementById('lbl-cambio');
    if (isNaN(cambio) || document.getElementById('inp-recibido').value === '') {
        lbl.textContent = 'Cambio: —'; lbl.style.color = 'var(--blue)';
    } else {
        lbl.textContent = 'Cambio: ' + fmt(cambio);
        lbl.style.color = cambio >= 0 ? 'var(--green)' : 'var(--red)';
    }
}

async function confirmarVenta() {
    const total = cart.reduce((s, i) => s + i.precio * i.cantidad, 0);
    const errEl = document.getElementById('modal-err');
    errEl.style.display = 'none';

    if (medio === 'Efectivo') {
        const rec = parseFloat(document.getElementById('inp-recibido').value) || 0;
        if (rec < total) {
            errEl.textContent = '⚠ El monto recibido es menor al total.';
            errEl.style.display = 'block';
            return;
        }
    }

    const fd = new FormData();
    fd.append('action', 'cobrar');
    fd.append('items',  JSON.stringify(cart));
    fd.append('total',  total);
    fd.append('medio',  medio);

    const r = await fetch(BASE_URL + '/api.php', {method: 'POST', body: fd});
    const d = await r.json();
    if (d.ok) {
        closeModal();
        cart = [];
        renderCart();
        location.reload();
    } else {
        errEl.textContent = d.msg || 'Error al registrar la venta.';
        errEl.style.display = 'block';
    }
}

// Fecha en topbar
const dias = ['Domingo','Lunes','Martes','Miércoles','Jueves','Viernes','Sábado'];
const meses = ['enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre'];
const hoy = new Date();
document.getElementById('fecha-hoy').textContent =
    `${dias[hoy.getDay()]} ${hoy.getDate()} de ${meses[hoy.getMonth()]} de ${hoy.getFullYear()}`;
</script>
</body>
</html>
