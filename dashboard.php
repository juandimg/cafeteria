<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/data.php';
$productos = leer_productos();
$clientes_lista = leer_clientes();
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
                <div style="position:relative">
                    <span style="position:absolute;left:10px;top:50%;transform:translateY(-50%);color:#9E9E9E;font-size:15px;pointer-events:none">🔍</span>
                    <input type="text" id="buscador" placeholder="Buscar producto…"
                           oninput="filtrarProductos()"
                           style="padding:8px 12px 8px 34px;border:1.5px solid #E0E0E0;border-radius:10px;font-size:14px;width:220px;outline:none;transition:border-color .2s"
                           onfocus="this.style.borderColor='var(--amber)'"
                           onblur="this.style.borderColor='#E0E0E0'">
                </div>
            </div>
            <div class="product-grid">
            <?php foreach ($productos as $prod):
                $url = img_url($prod['img_path'] ?? null);
                $sin_stock = ($prod['stock'] ?? 0) <= 0;
            ?>
            <div class="product-card" data-name="<?= strtolower(htmlspecialchars($prod['nombre'])) ?>">
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
            <div id="no-resultados" class="empty-state" style="grid-column:1/-1;display:none">
                <div class="es-ico">🔍</div>
                No se encontraron productos con ese nombre.
            </div>
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
    <div class="payment-btns" style="flex-wrap:wrap;gap:6px">
        <button class="payment-btn" id="btn-ef" onclick="selMedio('Efectivo')"
            style="background:var(--green);color:#fff">💵 Efectivo</button>
        <button class="payment-btn" id="btn-tr" onclick="selMedio('Transferencia')"
            style="background:#EEEEEE;color:var(--slate)">📱 Transferencia</button>
        <button class="payment-btn" id="btn-cr" onclick="selMedio('Crédito')"
            style="background:#EEEEEE;color:var(--slate)">📋 Crédito</button>
    </div>

    <div id="credito-section" style="display:none;margin-bottom:8px">
        <p style="font-size:12px;font-weight:700;color:var(--txt-g);margin-bottom:8px">CLIENTE</p>
        <div style="display:flex;gap:8px;margin-bottom:10px">
            <button id="tab-existente" onclick="setClienteTab('existente')"
                style="flex:1;padding:7px;border-radius:8px;border:1.5px solid var(--amber);background:var(--amber);color:#fff;font-size:12px;font-weight:700;cursor:pointer">
                Existente
            </button>
            <button id="tab-nuevo" onclick="setClienteTab('nuevo')"
                style="flex:1;padding:7px;border-radius:8px;border:1.5px solid #E0E0E0;background:#EEE;color:var(--slate);font-size:12px;font-weight:700;cursor:pointer">
                + Nuevo
            </button>
        </div>
        <div id="credito-existente">
            <select id="credito-select" class="form-control" style="height:38px">
                <option value="">— Seleccionar cliente —</option>
                <?php foreach ($clientes_lista as $cl): ?>
                <option value="<?= $cl['id'] ?>"><?= htmlspecialchars($cl['nombre']) ?>
                    <?php if ((float)$cl['saldo'] > 0): ?>(debe <?= fmt_money((float)$cl['saldo']) ?>)<?php endif; ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div id="credito-nuevo" style="display:none">
            <input class="form-control" id="credito-nombre" type="text"
                   placeholder="Nombre del cliente" style="height:38px">
        </div>
    </div>

    <div id="ef-section">
        <div class="flex-row" style="margin-bottom:6px">
            <label style="font-size:12px;color:var(--txt-g)">Recibido ($):</label>
            <input type="number" id="inp-recibido" class="form-control"
                   style="width:140px;height:36px" placeholder="0" oninput="calcCambio()">
        </div>
        <div id="lbl-cambio" style="font-size:12px;font-weight:700;color:var(--blue);margin-bottom:8px">Cambio: —</div>
    </div>

    <!-- Abono parcial (Efectivo / Transferencia) -->
    <div id="abono-wrap" style="display:none;margin-top:6px">
        <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:13px;margin-bottom:6px">
            <input type="checkbox" id="chk-abono" onchange="toggleAbono()">
            Pago parcial (el resto queda como deuda)
        </label>
        <div id="abono-fields" style="display:none">
            <div class="flex-row" style="margin-bottom:6px">
                <label style="font-size:12px;color:var(--txt-g)">Paga ahora ($):</label>
                <input type="number" id="inp-abono" class="form-control"
                       style="width:140px;height:36px" placeholder="0" oninput="calcPendiente()">
            </div>
            <div id="lbl-pendiente" style="font-size:12px;font-weight:700;color:var(--red);margin-bottom:10px">Queda debiendo: —</div>
            <p style="font-size:12px;font-weight:700;color:var(--txt-g);margin-bottom:8px">CLIENTE (deuda)</p>
            <div style="display:flex;gap:8px;margin-bottom:8px">
                <button id="abono-tab-ex" onclick="setAbonoTab('existente')"
                    style="flex:1;padding:7px;border-radius:8px;border:1.5px solid var(--amber);background:var(--amber);color:#fff;font-size:12px;font-weight:700;cursor:pointer">
                    Existente
                </button>
                <button id="abono-tab-nv" onclick="setAbonoTab('nuevo')"
                    style="flex:1;padding:7px;border-radius:8px;border:1.5px solid #E0E0E0;background:#EEE;color:var(--slate);font-size:12px;font-weight:700;cursor:pointer">
                    + Nuevo
                </button>
            </div>
            <div id="abono-existente">
                <select id="abono-select" class="form-control" style="height:38px">
                    <option value="">— Seleccionar cliente —</option>
                    <?php foreach ($clientes_lista as $cl): ?>
                    <option value="<?= $cl['id'] ?>"><?= htmlspecialchars($cl['nombre']) ?>
                        <?php if ((float)$cl['saldo'] > 0): ?>(debe <?= fmt_money((float)$cl['saldo']) ?>)<?php endif; ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div id="abono-nuevo" style="display:none">
                <input class="form-control" id="abono-nombre" type="text"
                       placeholder="Nombre del cliente" style="height:38px">
            </div>
        </div>
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
    document.getElementById('chk-abono').checked = false;
    document.getElementById('abono-fields').style.display = 'none';
    document.getElementById('inp-abono').value = '';
    document.getElementById('lbl-pendiente').textContent = 'Queda debiendo: —';
    abonoTab = 'existente';
    selMedio('Efectivo');
    document.getElementById('modal-cobro').classList.add('open');
}
function closeModal() { document.getElementById('modal-cobro').classList.remove('open'); }

let clienteTab = 'existente';
let abonoTab   = 'existente';

function setAbonoTab(tab) {
    abonoTab = tab;
    const btnEx = document.getElementById('abono-tab-ex');
    const btnNv = document.getElementById('abono-tab-nv');
    const divEx = document.getElementById('abono-existente');
    const divNv = document.getElementById('abono-nuevo');
    const active   = 'flex:1;padding:7px;border-radius:8px;border:1.5px solid var(--amber);background:var(--amber);color:#fff;font-size:12px;font-weight:700;cursor:pointer';
    const inactive = 'flex:1;padding:7px;border-radius:8px;border:1.5px solid #E0E0E0;background:#EEE;color:var(--slate);font-size:12px;font-weight:700;cursor:pointer';
    if (tab === 'existente') {
        btnEx.style.cssText = active;  btnNv.style.cssText = inactive;
        divEx.style.display = '';      divNv.style.display = 'none';
    } else {
        btnNv.style.cssText = active;  btnEx.style.cssText = inactive;
        divNv.style.display = '';      divEx.style.display = 'none';
    }
}

function toggleAbono() {
    const checked = document.getElementById('chk-abono').checked;
    document.getElementById('abono-fields').style.display = checked ? '' : 'none';
    if (medio === 'Efectivo') {
        document.getElementById('ef-section').style.display = checked ? 'none' : '';
    }
    if (checked) calcPendiente();
}

function calcPendiente() {
    const total    = cart.reduce((s, i) => s + i.precio * i.cantidad, 0);
    const abono    = parseFloat(document.getElementById('inp-abono').value) || 0;
    const lbl      = document.getElementById('lbl-pendiente');
    const pendiente = total - abono;
    if (!document.getElementById('inp-abono').value) {
        lbl.textContent = 'Queda debiendo: —'; lbl.style.color = 'var(--red)';
    } else if (pendiente <= 0) {
        lbl.textContent = 'Sin deuda (cubre el total)'; lbl.style.color = 'var(--green)';
    } else {
        lbl.textContent = 'Queda debiendo: ' + fmt(pendiente); lbl.style.color = 'var(--red)';
    }
}

function setClienteTab(tab) {
    clienteTab = tab;
    const btnEx = document.getElementById('tab-existente');
    const btnNv = document.getElementById('tab-nuevo');
    const divEx = document.getElementById('credito-existente');
    const divNv = document.getElementById('credito-nuevo');
    const active  = 'flex:1;padding:7px;border-radius:8px;border:1.5px solid var(--amber);background:var(--amber);color:#fff;font-size:12px;font-weight:700;cursor:pointer';
    const inactive = 'flex:1;padding:7px;border-radius:8px;border:1.5px solid #E0E0E0;background:#EEE;color:var(--slate);font-size:12px;font-weight:700;cursor:pointer';
    if (tab === 'existente') {
        btnEx.style.cssText = active; btnNv.style.cssText = inactive;
        divEx.style.display = ''; divNv.style.display = 'none';
    } else {
        btnNv.style.cssText = active; btnEx.style.cssText = inactive;
        divNv.style.display = ''; divEx.style.display = 'none';
    }
}

function selMedio(m) {
    medio = m;
    const ef  = document.getElementById('btn-ef');
    const tr  = document.getElementById('btn-tr');
    const cr  = document.getElementById('btn-cr');
    const sec = document.getElementById('ef-section');
    const crsec = document.getElementById('credito-section');
    const base = ';flex:1;height:42px;border-radius:10px;border:none;cursor:pointer;font-size:13px;font-weight:700';
    ef.style.cssText  = (m==='Efectivo'      ? 'background:var(--green);color:#fff'  : 'background:#EEEEEE;color:var(--slate)') + base;
    tr.style.cssText  = (m==='Transferencia' ? 'background:var(--blue);color:#fff'   : 'background:#EEEEEE;color:var(--slate)') + base;
    cr.style.cssText  = (m==='Crédito'       ? 'background:var(--purple);color:#fff' : 'background:#EEEEEE;color:var(--slate)') + base;
    sec.style.display    = m === 'Efectivo' ? '' : 'none';
    crsec.style.display  = m === 'Crédito'  ? '' : 'none';
    const abonow = document.getElementById('abono-wrap');
    abonow.style.display = (m === 'Efectivo' || m === 'Transferencia') ? '' : 'none';
    document.getElementById('chk-abono').checked = false;
    document.getElementById('abono-fields').style.display = 'none';
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

    // Flujo abono parcial
    if (document.getElementById('chk-abono').checked && (medio === 'Efectivo' || medio === 'Transferencia')) {
        const abono = parseFloat(document.getElementById('inp-abono').value) || 0;
        if (abono <= 0) {
            errEl.textContent = '⚠ Ingresa el monto a abonar.';
            errEl.style.display = 'block'; return;
        }
        if (abono >= total) {
            errEl.textContent = '⚠ El abono cubre el total. Usá pago completo.';
            errEl.style.display = 'block'; return;
        }

        let abonoClienteId = null;
        if (abonoTab === 'existente') {
            abonoClienteId = document.getElementById('abono-select').value;
            if (!abonoClienteId) {
                errEl.textContent = '⚠ Seleccioná un cliente para registrar la deuda.';
                errEl.style.display = 'block'; return;
            }
        } else {
            const nombre = document.getElementById('abono-nombre').value.trim();
            if (!nombre) {
                errEl.textContent = '⚠ Ingresá el nombre del cliente.';
                errEl.style.display = 'block'; return;
            }
            const fd2 = new FormData();
            fd2.append('action', 'agregar_cliente');
            fd2.append('nombre', nombre);
            const r2 = await fetch(BASE_URL + '/api.php', {method:'POST', body:fd2});
            const d2 = await r2.json();
            if (!d2.ok) {
                errEl.textContent = d2.msg || 'Error al crear cliente.';
                errEl.style.display = 'block'; return;
            }
            abonoClienteId = d2.id;
        }

        const fdA = new FormData();
        fdA.append('action',  'cobrar');
        fdA.append('items',   JSON.stringify(cart));
        fdA.append('total',   abono);
        fdA.append('medio',   medio);
        fdA.append('parcial', '1');
        const rA = await fetch(BASE_URL + '/api.php', {method:'POST', body:fdA});
        const dA = await rA.json();
        if (!dA.ok) {
            errEl.textContent = dA.msg || 'Error al registrar la venta.';
            errEl.style.display = 'block'; return;
        }

        const pendiente     = total - abono;
        const productos_str = cart.map(i => `${i.nombre} x${i.cantidad}`).join(', ');
        const fdD = new FormData();
        fdD.append('action',     'agregar_movimiento_cliente');
        fdD.append('cliente_id', abonoClienteId);
        fdD.append('tipo',       'deuda');
        fdD.append('concepto',   'Deuda (abono parcial): ' + productos_str);
        fdD.append('monto',      pendiente);
        fdD.append('fecha',      new Date().toISOString().split('T')[0]);
        await fetch(BASE_URL + '/api.php', {method:'POST', body:fdD});

        closeModal(); cart = []; renderCart(); location.reload();
        return;
    }

    if (medio === 'Efectivo') {
        const rec = parseFloat(document.getElementById('inp-recibido').value) || 0;
        if (rec < total) {
            errEl.textContent = '⚠ El monto recibido es menor al total.';
            errEl.style.display = 'block';
            return;
        }
    }

    let clienteId = null;
    if (medio === 'Crédito') {
        if (clienteTab === 'existente') {
            clienteId = document.getElementById('credito-select').value;
            if (!clienteId) {
                errEl.textContent = '⚠ Selecciona un cliente.';
                errEl.style.display = 'block';
                return;
            }
        } else {
            const nombre = document.getElementById('credito-nombre').value.trim();
            if (!nombre) {
                errEl.textContent = '⚠ Ingresa el nombre del cliente.';
                errEl.style.display = 'block';
                return;
            }
            const fd2 = new FormData();
            fd2.append('action', 'agregar_cliente');
            fd2.append('nombre', nombre);
            const r2 = await fetch(BASE_URL + '/api.php', {method:'POST', body:fd2});
            const d2 = await r2.json();
            if (!d2.ok) {
                errEl.textContent = d2.msg || 'Error al crear cliente.';
                errEl.style.display = 'block';
                return;
            }
            clienteId = d2.id;
        }
    }

    const fd = new FormData();
    fd.append('action', 'cobrar');
    fd.append('items',  JSON.stringify(cart));
    fd.append('total',  total);
    fd.append('medio',  medio);

    const r = await fetch(BASE_URL + '/api.php', {method: 'POST', body: fd});
    const d = await r.json();
    if (!d.ok) {
        errEl.textContent = d.msg || 'Error al registrar la venta.';
        errEl.style.display = 'block';
        return;
    }

    if (medio === 'Crédito' && clienteId) {
        const productos_str = cart.map(i => `${i.nombre} x${i.cantidad}`).join(', ');
        const fd3 = new FormData();
        fd3.append('action',     'agregar_movimiento_cliente');
        fd3.append('cliente_id', clienteId);
        fd3.append('tipo',       'deuda');
        fd3.append('concepto',   'Venta: ' + productos_str);
        fd3.append('monto',      total);
        fd3.append('fecha',      new Date().toISOString().split('T')[0]);
        await fetch(BASE_URL + '/api.php', {method:'POST', body:fd3});
    }

    closeModal();
    cart = [];
    renderCart();
    location.reload();
}

function filtrarProductos() {
    const q = document.getElementById('buscador').value.toLowerCase().trim();
    let visibles = 0;
    document.querySelectorAll('.product-card').forEach(card => {
        const match = card.dataset.name.includes(q);
        card.style.display = match ? '' : 'none';
        if (match) visibles++;
    });
    const empty = document.getElementById('no-resultados');
    if (empty) empty.style.display = visibles === 0 ? '' : 'none';
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
