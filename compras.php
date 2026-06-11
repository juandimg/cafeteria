<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/data.php';

$compras     = leer_compras();
$proveedores = leer_proveedores();
$hoy         = date('Y-m-d');
$mes         = date('Y-m');

$compras_mes = array_filter($compras, fn($c) => str_starts_with($c['fecha'] ?? '', $mes));
$total_mes   = array_sum(array_column(array_values($compras_mes), 'total'));
$total_all   = array_sum(array_column($compras, 'total'));
$count_mes   = count($compras_mes);
$count_all   = count($compras);

$prov_nombres = array_unique(array_merge(
    array_column($proveedores, 'nombre'),
    array_column($compras, 'proveedor')
));
sort($prov_nombres);
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>CafePro – Compras</title>
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/style.css">
</head>
<body>
<div class="app">
<?php require __DIR__ . '/includes/header.php'; ?>
<div class="main-content">
    <div class="topbar">
        <span class="topbar-title">🛍️ Compras</span>
        <span class="topbar-date" id="fecha-hoy"></span>
    </div>
    <div class="page-body">

        <!-- Stats -->
        <div class="stats-row cols-4">
            <div class="stat-card bg-amber">
                <span class="stat-ico">🛍️</span>
                <div><div class="stat-val"><?= $count_mes ?></div><div class="stat-lbl">Compras este mes</div></div>
            </div>
            <div class="stat-card bg-red">
                <span class="stat-ico">💸</span>
                <div><div class="stat-val"><?= fmt_money($total_mes) ?></div><div class="stat-lbl">Gastado este mes</div></div>
            </div>
            <div class="stat-card bg-slate">
                <span class="stat-ico">📦</span>
                <div><div class="stat-val"><?= $count_all ?></div><div class="stat-lbl">Total compras</div></div>
            </div>
            <div class="stat-card bg-brown" style="background:#5D4037">
                <span class="stat-ico">💰</span>
                <div><div class="stat-val"><?= fmt_money($total_all) ?></div><div class="stat-lbl">Gastado total</div></div>
            </div>
        </div>

        <!-- Filter bar -->
        <div class="filter-bar">
            <div class="filter-group">
                <label class="filter-label">DESDE</label>
                <input type="date" id="f-desde" class="filter-control" oninput="filtrar()">
            </div>
            <div class="filter-group">
                <label class="filter-label">HASTA</label>
                <input type="date" id="f-hasta" class="filter-control" oninput="filtrar()">
            </div>
            <div class="filter-group">
                <label class="filter-label">PROVEEDOR</label>
                <select id="f-prov" class="filter-control" style="width:180px" onchange="filtrar()">
                    <option value="">Todos</option>
                    <?php foreach ($prov_nombres as $pn): ?>
                    <option value="<?= htmlspecialchars($pn) ?>"><?= htmlspecialchars($pn) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filter-group">
                <label class="filter-label">BUSCAR PRODUCTO</label>
                <input type="text" id="f-buscar" class="filter-control" style="width:200px"
                       placeholder="Ej: Café, Azúcar…" oninput="filtrar()">
            </div>
            <div class="filter-sep"></div>
            <button class="btn btn-amber" onclick="abrirModal()">+ Nueva Compra</button>
            <button class="btn btn-ghost" onclick="limpiarFiltros()">✕ Limpiar</button>
        </div>

        <!-- Table -->
        <div class="page-title-wrap">
            <div class="page-title">Historial de compras <span id="count-label" style="font-weight:400;color:var(--txt-g);font-size:13px"></span></div>
            <div id="total-filtrado" style="font-size:14px;font-weight:700;color:var(--red)"></div>
        </div>

        <div class="report-wrap" id="tabla-wrap">
            <table class="report-table" id="compras-table">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Proveedor</th>
                        <th>Productos</th>
                        <th style="text-align:right">Total</th>
                        <th style="text-align:center">Acción</th>
                    </tr>
                </thead>
                <tbody id="compras-tbody">
                </tbody>
            </table>
            <div id="empty-state" class="empty-state" style="display:none">
                <div class="es-ico">🛍️</div>
                No hay compras que coincidan con los filtros.<br>
                <span style="font-size:12px">Registra la primera compra con el botón <strong>+ Nueva Compra</strong>.</span>
            </div>
        </div>

    </div>
</div>
</div>

<!-- Modal nueva compra -->
<div class="modal-overlay" id="modal-overlay" onclick="cerrarSiOverlay(event)">
    <div class="modal modal-wide">
        <div class="modal-title">🛍️ Registrar Compra</div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:16px">
            <div class="form-group" style="margin:0">
                <label class="form-label">FECHA</label>
                <input type="date" id="m-fecha" class="form-control" value="<?= $hoy ?>">
            </div>
            <div class="form-group" style="margin:0">
                <label class="form-label">PROVEEDOR</label>
                <select id="m-proveedor-sel" class="form-control" onchange="onProveedorChange()">
                    <option value="">— Seleccionar proveedor —</option>
                    <?php foreach ($proveedores as $pv): ?>
                    <option value="<?= htmlspecialchars($pv['nombre']) ?>"><?= htmlspecialchars($pv['nombre']) ?></option>
                    <?php endforeach; ?>
                    <option value="__nuevo__">＋ Agregar nuevo proveedor…</option>
                </select>
                <input type="text" id="m-proveedor-nuevo" class="form-control"
                       placeholder="Nombre del nuevo proveedor"
                       style="display:none;margin-top:6px">
            </div>
        </div>

        <label class="form-label" style="margin-bottom:8px;display:block">PRODUCTOS COMPRADOS</label>
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width:40%">Producto</th>
                    <th style="width:15%">Cantidad</th>
                    <th style="width:22%">Costo unit.</th>
                    <th style="width:18%">Subtotal</th>
                    <th style="width:5%"></th>
                </tr>
            </thead>
            <tbody id="items-body">
            </tbody>
        </table>
        <button class="btn btn-ghost btn-sm" onclick="agregarFila()" style="margin-bottom:14px">+ Agregar producto</button>

        <div class="total-row">
            <span class="total-lbl">TOTAL COMPRA</span>
            <span class="total-val" id="modal-total">$ 0</span>
        </div>

        <div class="form-group" style="margin-top:14px">
            <label class="form-label">NOTAS (opcional)</label>
            <input type="text" id="m-notas" class="form-control" placeholder="Ej: Factura #123, pago en efectivo…">
        </div>

        <div style="display:flex;gap:10px;margin-top:4px">
            <button id="btn-guardar-compra" class="btn btn-amber btn-lg" style="flex:1" onclick="guardarCompra()">💾 Guardar Compra</button>
            <button class="btn btn-ghost btn-lg" onclick="cerrarModal()">Cancelar</button>
        </div>
    </div>
</div>

<script>
const COMPRAS = <?= json_encode($compras) ?>;
const dias    = ['Domingo','Lunes','Martes','Miércoles','Jueves','Viernes','Sábado'];
const meses_n = ['enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre'];
const d = new Date();
document.getElementById('fecha-hoy').textContent =
    `${dias[d.getDay()]} ${d.getDate()} de ${meses_n[d.getMonth()]} de ${d.getFullYear()}`;

function fmtMoney(val) {
    return '$ ' + Math.round(val).toLocaleString('es-CO');
}

function renderTabla(lista) {
    const tbody = document.getElementById('compras-tbody');
    const empty = document.getElementById('empty-state');
    const wrap  = document.getElementById('tabla-wrap');

    document.getElementById('count-label').textContent =
        lista.length ? `(${lista.length} resultado${lista.length !== 1 ? 's' : ''})` : '';

    const totalFilt = lista.reduce((s, c) => s + (c.total || 0), 0);
    document.getElementById('total-filtrado').textContent =
        lista.length ? 'Total: ' + fmtMoney(totalFilt) : '';

    if (!lista.length) {
        tbody.innerHTML = '';
        empty.style.display = 'block';
        wrap.style.overflowX = 'hidden';
        return;
    }
    empty.style.display = 'none';

    const sorted = [...lista].sort((a, b) => {
        if (b.fecha !== a.fecha) return b.fecha.localeCompare(a.fecha);
        return (b.id || 0) - (a.id || 0);
    });

    tbody.innerHTML = sorted.map(c => {
        const resumen = (c.items || [])
            .map(it => `${it.producto} x${it.cantidad}`)
            .join(', ');
        const detalle = (c.items || [])
            .map(it => `${it.producto}: ${it.cantidad} × ${fmtMoney(it.costo_unitario)} = ${fmtMoney(it.subtotal)}`)
            .join(' | ');
        return `
        <tr>
            <td>
                <div style="font-weight:700;font-size:12px">${c.fecha}</div>
                ${c.notas ? `<div style="font-size:10px;color:var(--txt-g);margin-top:2px">📝 ${esc(c.notas)}</div>` : ''}
            </td>
            <td><span class="badge badge-amber">${esc(c.proveedor)}</span></td>
            <td>
                <div style="font-size:12px;color:var(--txt-d)">${esc(resumen)}</div>
                <div class="compra-items-detail">${esc(detalle)}</div>
            </td>
            <td style="text-align:right;font-weight:700;color:var(--red)">${fmtMoney(c.total)}</td>
            <td style="text-align:center">
                <button class="btn btn-del btn-sm" onclick="eliminarCompra(${c.id})">🗑</button>
            </td>
        </tr>`;
    }).join('');
}

function esc(str) {
    const d = document.createElement('div');
    d.textContent = String(str || '');
    return d.innerHTML;
}

function filtrar() {
    const desde  = document.getElementById('f-desde').value;
    const hasta  = document.getElementById('f-hasta').value;
    const prov   = document.getElementById('f-prov').value;
    const buscar = document.getElementById('f-buscar').value.toLowerCase().trim();

    const result = COMPRAS.filter(c => {
        if (desde && c.fecha < desde) return false;
        if (hasta && c.fecha > hasta) return false;
        if (prov && c.proveedor !== prov) return false;
        if (buscar) {
            const enProductos = (c.items || []).some(it =>
                (it.producto || '').toLowerCase().includes(buscar)
            );
            const enNotas = (c.notas || '').toLowerCase().includes(buscar);
            if (!enProductos && !enNotas) return false;
        }
        return true;
    });

    renderTabla(result);
}

function limpiarFiltros() {
    document.getElementById('f-desde').value  = '';
    document.getElementById('f-hasta').value  = '';
    document.getElementById('f-prov').value   = '';
    document.getElementById('f-buscar').value = '';
    filtrar();
}

// ── Modal ──

function onProveedorChange() {
    const sel   = document.getElementById('m-proveedor-sel');
    const nuevo = document.getElementById('m-proveedor-nuevo');
    if (sel.value === '__nuevo__') {
        nuevo.style.display = '';
        nuevo.focus();
    } else {
        nuevo.style.display = 'none';
        nuevo.value = '';
    }
}

function getProveedorValue() {
    const sel = document.getElementById('m-proveedor-sel');
    if (sel.value === '__nuevo__') return document.getElementById('m-proveedor-nuevo').value.trim();
    return sel.value;
}

function abrirModal() {
    document.getElementById('m-proveedor-sel').value   = '';
    document.getElementById('m-proveedor-nuevo').value = '';
    document.getElementById('m-proveedor-nuevo').style.display = 'none';
    document.getElementById('modal-overlay').classList.add('open');
    const tbody = document.getElementById('items-body');
    if (!tbody.children.length) agregarFila();
    calcularTotal();
}

function cerrarModal() {
    document.getElementById('modal-overlay').classList.remove('open');
}

function cerrarSiOverlay(e) {
    if (e.target === document.getElementById('modal-overlay')) cerrarModal();
}

let filaIdx = 0;
function agregarFila() {
    const idx = filaIdx++;
    const tr = document.createElement('tr');
    tr.id = 'fila-' + idx;
    tr.innerHTML = `
        <td><input class="item-input item-prod" type="text" placeholder="Ej: Café molido" list="lista-proveedores"></td>
        <td><input class="item-input item-cant" type="number" min="0.01" step="0.01" placeholder="0" oninput="calcularTotal()"></td>
        <td><input class="item-input item-costo" type="number" min="0" step="1" placeholder="0" oninput="calcularTotal()"></td>
        <td><input class="item-input item-subtotal" type="text" readonly tabindex="-1" style="background:#F5F5F5;color:var(--green);font-weight:700"></td>
        <td><button class="btn btn-del btn-sm" onclick="eliminarFila(${idx})" tabindex="-1">✕</button></td>
    `;
    document.getElementById('items-body').appendChild(tr);
}

function eliminarFila(idx) {
    const fila = document.getElementById('fila-' + idx);
    if (fila) fila.remove();
    calcularTotal();
}

function calcularTotal() {
    let total = 0;
    document.querySelectorAll('#items-body tr').forEach(row => {
        const cant  = parseFloat(row.querySelector('.item-cant')?.value)  || 0;
        const costo = parseFloat(row.querySelector('.item-costo')?.value) || 0;
        const sub   = cant * costo;
        const subInput = row.querySelector('.item-subtotal');
        if (subInput) subInput.value = sub ? fmtMoney(sub) : '';
        total += sub;
    });
    document.getElementById('modal-total').textContent = fmtMoney(total);
}

async function guardarCompra() {
    const btn = document.getElementById('btn-guardar-compra');
    const fecha     = document.getElementById('m-fecha').value.trim();
    const proveedor = getProveedorValue();
    const esNuevo   = document.getElementById('m-proveedor-sel').value === '__nuevo__';
    const notas     = document.getElementById('m-notas').value.trim();

    const items = [];
    let total = 0;
    document.querySelectorAll('#items-body tr').forEach(row => {
        const producto       = row.querySelector('.item-prod')?.value.trim();
        const cantidad       = parseFloat(row.querySelector('.item-cant')?.value) || 0;
        const costo_unitario = parseFloat(row.querySelector('.item-costo')?.value) || 0;
        const subtotal       = cantidad * costo_unitario;
        if (producto && cantidad > 0 && costo_unitario > 0) {
            items.push({ producto, cantidad, costo_unitario, subtotal });
            total += subtotal;
        }
    });

    if (!fecha)     { alert('Selecciona una fecha.'); return; }
    if (!proveedor) { alert('Selecciona o ingresá el nombre del proveedor.'); return; }
    if (!items.length) { alert('Agrega al menos un producto con cantidad y costo.'); return; }

    // Guardar nuevo proveedor en la tabla de proveedores
    if (esNuevo && proveedor) {
        const fdp = new FormData();
        fdp.append('action', 'agregar_proveedor_rapido');
        fdp.append('nombre', proveedor);
        await fetch(BASE_URL + '/api.php', { method: 'POST', body: fdp });
    }

    const fd = new FormData();
    fd.append('action',     'guardar_compra');
    fd.append('fecha',      fecha);
    fd.append('proveedor',  proveedor);
    fd.append('items',      JSON.stringify(items));
    fd.append('total',      total);
    fd.append('notas',      notas);

    btn.disabled = true;
    btn.textContent = 'Guardando…';

    try {
        const res  = await fetch(BASE_URL + '/api.php', { method: 'POST', body: fd });
        const data = await res.json();
        if (data.ok) {
            location.reload();
        } else {
            alert(data.msg || 'Error al guardar la compra.');
            btn.disabled = false;
            btn.textContent = '💾 Guardar Compra';
        }
    } catch {
        alert('Error de conexión.');
        btn.disabled = false;
        btn.textContent = '💾 Guardar Compra';
    }
}

async function eliminarCompra(id) {
    if (!confirm('¿Eliminar esta compra? Esta acción no se puede deshacer.')) return;
    const fd = new FormData();
    fd.append('action', 'eliminar_compra');
    fd.append('id', id);
    const res  = await fetch(BASE_URL + '/api.php', { method: 'POST', body: fd });
    const data = await res.json();
    if (data.ok) location.reload();
    else alert('No se pudo eliminar.');
}

// Render initial table
renderTabla(COMPRAS);
</script>
</body>
</html>
