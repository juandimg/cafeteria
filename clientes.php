<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/data.php';

$clientes = leer_clientes();
$msg      = '';
$msg_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'agregar') {
    $nombre = trim($_POST['nombre'] ?? '');
    if (!$nombre) {
        $msg = 'El nombre es requerido.'; $msg_type = 'red';
    } else {
        agregar_cliente(['nombre' => $nombre, 'telefono' => trim($_POST['telefono'] ?? '')]);
        header('Location: ' . BASE_URL . '/clientes.php?ok=1');
        exit;
    }
}

$ok = $_GET['ok'] ?? '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>CafePro – Clientes</title>
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/style.css">
</head>
<body>
<div class="app">
<?php require __DIR__ . '/includes/header.php'; ?>
<div class="main-content">
    <div class="topbar">
        <span class="topbar-title">👥 Clientes con deuda</span>
        <span class="topbar-date" id="fecha-hoy"></span>
    </div>

    <div class="split-layout">
        <!-- Formulario nuevo cliente -->
        <div class="split-form">
            <div class="form-section-title">Nuevo cliente</div>

            <?php if ($msg): ?>
            <div class="alert alert-<?= $msg_type ?>"><?= htmlspecialchars($msg) ?></div>
            <?php endif; ?>
            <?php if ($ok): ?>
            <div class="alert alert-green">✓ Cliente agregado correctamente.</div>
            <?php endif; ?>

            <form method="POST">
                <input type="hidden" name="accion" value="agregar">
                <div class="form-group">
                    <label class="form-label">NOMBRE</label>
                    <input class="form-control" type="text" name="nombre"
                           placeholder="Ej: Juan Pérez" required autofocus>
                </div>
                <div class="form-group">
                    <label class="form-label">TELÉFONO (opcional)</label>
                    <input class="form-control" type="text" name="telefono"
                           placeholder="Ej: 3001234567">
                </div>
                <button class="btn btn-amber btn-full btn-lg">+ Agregar cliente</button>
            </form>

            <!-- Resumen total -->
            <?php
            $total_deuda = array_sum(array_map(fn($c) => max(0, (float)$c['saldo']), $clientes));
            $con_deuda   = count(array_filter($clientes, fn($c) => (float)$c['saldo'] > 0));
            ?>
            <?php if ($con_deuda > 0): ?>
            <div class="card" style="margin-top:24px;background:#FFF8E1;border:1.5px solid #FFE082">
                <div style="font-size:12px;font-weight:700;color:var(--txt-g);margin-bottom:6px">RESUMEN</div>
                <div style="font-size:22px;font-weight:800;color:var(--red)"><?= fmt_money($total_deuda) ?></div>
                <div style="font-size:12px;color:var(--txt-g);margin-top:2px">
                    en deuda de <?= $con_deuda ?> cliente<?= $con_deuda > 1 ? 's' : '' ?>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Lista de clientes -->
        <div class="split-list">
            <div class="page-title-wrap">
                <div class="page-title">Clientes registrados</div>
            </div>

            <?php if (empty($clientes)): ?>
            <div class="empty-state">
                <div class="es-ico">👥</div>
                No hay clientes registrados.<br>Agrega el primero desde el panel izquierdo.
            </div>
            <?php else: ?>
            <?php foreach ($clientes as $c):
                $saldo    = (float)$c['saldo'];
                $al_dia   = $saldo <= 0;
            ?>
            <div class="card" style="margin-bottom:14px">
                <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:10px">
                    <div>
                        <div style="font-size:15px;font-weight:700"><?= htmlspecialchars($c['nombre']) ?></div>
                        <?php if ($c['telefono']): ?>
                        <div style="font-size:12px;color:var(--txt-g);margin-top:2px">📞 <?= htmlspecialchars($c['telefono']) ?></div>
                        <?php endif; ?>
                    </div>
                    <div style="text-align:right">
                        <div style="font-size:18px;font-weight:800;color:<?= $al_dia ? 'var(--green)' : 'var(--red)' ?>">
                            <?= fmt_money(abs($saldo)) ?>
                        </div>
                        <div style="font-size:11px;color:var(--txt-g)"><?= $al_dia ? '✅ Al día' : '⚠ Debe' ?></div>
                    </div>
                </div>

                <div style="display:flex;gap:8px;flex-wrap:wrap">
                    <button class="btn btn-red btn-sm"
                        onclick="abrirDeuda(<?= $c['id'] ?>, '<?= htmlspecialchars(addslashes($c['nombre'])) ?>')">
                        + Agregar deuda
                    </button>
                    <button class="btn btn-green btn-sm"
                        onclick="abrirAbono(<?= $c['id'] ?>, '<?= htmlspecialchars(addslashes($c['nombre'])) ?>', <?= $saldo ?>)"
                        <?= $al_dia ? 'disabled title="No tiene deuda"' : '' ?>>
                        + Registrar abono
                    </button>
                    <button class="btn btn-edit btn-sm"
                        onclick="verHistorial(<?= $c['id'] ?>, '<?= htmlspecialchars(addslashes($c['nombre'])) ?>')">
                        📋 Historial
                    </button>
                    <button class="btn btn-del btn-sm"
                        onclick="eliminarCliente(<?= $c['id'] ?>, '<?= htmlspecialchars(addslashes($c['nombre'])) ?>')">
                        🗑 Eliminar
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>
</div>

<!-- Modal: Agregar deuda -->
<div class="modal-overlay" id="modal-deuda">
<div class="modal">
    <div class="modal-title">➕ Agregar deuda</div>
    <p id="deuda-nombre" style="font-size:13px;font-weight:600;color:var(--txt-g);margin-bottom:16px"></p>
    <div class="form-group">
        <label class="form-label">CONCEPTO</label>
        <input class="form-control" id="deuda-concepto" type="text" placeholder="Ej: Café + Pan">
    </div>
    <div class="form-group">
        <label class="form-label">MONTO ($)</label>
        <input class="form-control" id="deuda-monto" type="number" min="1" placeholder="0">
    </div>
    <div class="form-group">
        <label class="form-label">FECHA</label>
        <input class="form-control" id="deuda-fecha" type="date">
    </div>
    <div id="deuda-err" class="alert alert-red" style="display:none"></div>
    <button class="btn btn-red btn-full btn-lg" onclick="guardarDeuda()">Guardar deuda</button>
    <button class="btn btn-ghost btn-full mt-8" onclick="cerrarModales()">Cancelar</button>
</div>
</div>

<!-- Modal: Registrar abono -->
<div class="modal-overlay" id="modal-abono">
<div class="modal">
    <div class="modal-title">💰 Registrar abono</div>
    <p id="abono-nombre" style="font-size:13px;font-weight:600;color:var(--txt-g);margin-bottom:4px"></p>
    <p id="abono-saldo-lbl" style="font-size:12px;color:var(--red);margin-bottom:16px"></p>
    <div class="form-group">
        <label class="form-label">MEDIO DE PAGO</label>
        <div style="display:flex;gap:8px">
            <button id="abono-btn-ef" onclick="selAbonoMedio('Efectivo')"
                style="flex:1;padding:9px;border-radius:8px;border:none;background:var(--green);color:#fff;font-size:13px;font-weight:700;cursor:pointer">
                💵 Efectivo
            </button>
            <button id="abono-btn-tr" onclick="selAbonoMedio('Transferencia')"
                style="flex:1;padding:9px;border-radius:8px;border:none;background:#EEE;color:var(--slate);font-size:13px;font-weight:700;cursor:pointer">
                📱 Transferencia
            </button>
        </div>
        <input type="hidden" id="abono-medio" value="Efectivo">
    </div>
    <div class="form-group">
        <label class="form-label">MONTO ABONADO ($)</label>
        <input class="form-control" id="abono-monto" type="number" min="1" placeholder="0">
    </div>
    <div class="form-group">
        <label class="form-label">NOTA (opcional)</label>
        <input class="form-control" id="abono-nota" type="text" placeholder="Ej: Pago parcial">
    </div>
    <div class="form-group">
        <label class="form-label">FECHA</label>
        <input class="form-control" id="abono-fecha" type="date">
    </div>
    <div id="abono-err" class="alert alert-red" style="display:none"></div>
    <button class="btn btn-green btn-full btn-lg" onclick="guardarAbono()">Guardar abono</button>
    <button class="btn btn-ghost btn-full mt-8" onclick="cerrarModales()">Cancelar</button>
</div>
</div>

<!-- Modal: Historial -->
<div class="modal-overlay" id="modal-historial">
<div class="modal" style="max-width:560px;width:95%">
    <div class="modal-title">📋 Historial de movimientos</div>
    <p id="hist-nombre" style="font-size:13px;font-weight:600;color:var(--txt-g);margin-bottom:16px"></p>
    <div id="hist-body" style="max-height:380px;overflow-y:auto"></div>
    <button class="btn btn-ghost btn-full mt-8" onclick="cerrarModales()">Cerrar</button>
</div>
</div>

<script>
let _clienteId   = null;
let _clienteSaldo = 0;

const hoy = new Date().toISOString().split('T')[0];
document.getElementById('deuda-fecha').value = hoy;
document.getElementById('abono-fecha').value = hoy;

function cerrarModales() {
    document.querySelectorAll('.modal-overlay').forEach(m => m.classList.remove('open'));
}

function abrirDeuda(id, nombre) {
    _clienteId = id;
    document.getElementById('deuda-nombre').textContent = 'Cliente: ' + nombre;
    document.getElementById('deuda-concepto').value = '';
    document.getElementById('deuda-monto').value    = '';
    document.getElementById('deuda-fecha').value    = hoy;
    document.getElementById('deuda-err').style.display = 'none';
    document.getElementById('modal-deuda').classList.add('open');
}

function selAbonoMedio(m) {
    document.getElementById('abono-medio').value = m;
    const base = ';flex:1;padding:9px;border-radius:8px;border:none;font-size:13px;font-weight:700;cursor:pointer';
    document.getElementById('abono-btn-ef').style.cssText =
        (m === 'Efectivo' ? 'background:var(--green);color:#fff' : 'background:#EEE;color:var(--slate)') + base;
    document.getElementById('abono-btn-tr').style.cssText =
        (m === 'Transferencia' ? 'background:var(--blue);color:#fff' : 'background:#EEE;color:var(--slate)') + base;
}

function abrirAbono(id, nombre, saldo) {
    _clienteId    = id;
    _clienteSaldo = saldo;
    document.getElementById('abono-nombre').textContent    = 'Cliente: ' + nombre;
    document.getElementById('abono-saldo-lbl').textContent = 'Deuda actual: ' + fmt(saldo);
    document.getElementById('abono-monto').value = '';
    document.getElementById('abono-nota').value  = '';
    document.getElementById('abono-fecha').value = hoy;
    document.getElementById('abono-err').style.display = 'none';
    selAbonoMedio('Efectivo');
    document.getElementById('modal-abono').classList.add('open');
}

async function verHistorial(id, nombre) {
    document.getElementById('hist-nombre').textContent = 'Cliente: ' + nombre;
    document.getElementById('hist-body').innerHTML = '<p style="color:var(--txt-g);font-size:13px">Cargando…</p>';
    document.getElementById('modal-historial').classList.add('open');

    const r = await fetch(BASE_URL + '/api.php?action=get_movimientos_cliente&cliente_id=' + id);
    const movs = await r.json();

    if (!movs.length) {
        document.getElementById('hist-body').innerHTML =
            '<p style="color:var(--txt-g);font-size:13px;text-align:center;padding:20px">Sin movimientos registrados.</p>';
        return;
    }

    let html = '<table style="width:100%;border-collapse:collapse;font-size:13px">';
    html += '<thead><tr style="border-bottom:2px solid #EEE">'
          + '<th style="text-align:left;padding:6px 4px;color:var(--txt-g)">Fecha</th>'
          + '<th style="text-align:left;padding:6px 4px;color:var(--txt-g)">Tipo</th>'
          + '<th style="text-align:left;padding:6px 4px;color:var(--txt-g)">Concepto</th>'
          + '<th style="text-align:right;padding:6px 4px;color:var(--txt-g)">Monto</th>'
          + '<th style="padding:6px 4px"></th>'
          + '</tr></thead><tbody>';

    let saldo = 0;
    movs.forEach(m => {
        const esDeuda = m.tipo === 'deuda';
        saldo += esDeuda ? parseFloat(m.monto) : -parseFloat(m.monto);
        html += `<tr style="border-bottom:1px solid #F5F5F5">
            <td style="padding:8px 4px;color:var(--txt-g)">${m.fecha}</td>
            <td style="padding:8px 4px">
                <span style="padding:2px 8px;border-radius:20px;font-size:11px;font-weight:700;
                    background:${esDeuda ? '#FFEBEE' : '#E8F5E9'};color:${esDeuda ? 'var(--red)' : 'var(--green)'}">
                    ${esDeuda ? 'DEUDA' : 'ABONO'}
                </span>
            </td>
            <td style="padding:8px 4px">${m.concepto || '—'}</td>
            <td style="padding:8px 4px;text-align:right;font-weight:700;color:${esDeuda ? 'var(--red)' : 'var(--green)'}">
                ${esDeuda ? '+' : '-'} ${fmt(parseFloat(m.monto))}
            </td>
            <td style="padding:8px 4px;text-align:right">
                <button onclick="eliminarMovimiento(${m.id})"
                    style="background:none;border:none;cursor:pointer;color:var(--txt-g);font-size:15px"
                    title="Eliminar">🗑</button>
            </td>
        </tr>`;
    });

    html += `</tbody><tfoot><tr style="border-top:2px solid #EEE;font-weight:800">
        <td colspan="3" style="padding:10px 4px">Saldo actual</td>
        <td style="padding:10px 4px;text-align:right;font-size:16px;color:${saldo > 0 ? 'var(--red)' : 'var(--green)'}">
            ${fmt(Math.abs(saldo))} ${saldo > 0 ? '(debe)' : '(al día)'}
        </td>
        <td></td>
    </tr></tfoot></table>`;

    document.getElementById('hist-body').innerHTML = html;
    document.getElementById('hist-body')._clienteId   = id;
    document.getElementById('hist-body')._clienteNombre = nombre;
}

async function eliminarMovimiento(id) {
    if (!confirm('¿Eliminar este movimiento?')) return;
    const fd = new FormData();
    fd.append('action', 'eliminar_movimiento_cliente');
    fd.append('id', id);
    await fetch(BASE_URL + '/api.php', {method:'POST', body: fd});
    const body = document.getElementById('hist-body');
    await verHistorial(body._clienteId, body._clienteNombre);
    location.reload();
}

async function guardarDeuda() {
    const concepto = document.getElementById('deuda-concepto').value.trim();
    const monto    = parseFloat(document.getElementById('deuda-monto').value) || 0;
    const fecha    = document.getElementById('deuda-fecha').value;
    const errEl    = document.getElementById('deuda-err');
    errEl.style.display = 'none';

    if (monto <= 0) { errEl.textContent = 'Ingresa un monto válido.'; errEl.style.display='block'; return; }
    if (!fecha)     { errEl.textContent = 'Selecciona una fecha.';    errEl.style.display='block'; return; }

    const fd = new FormData();
    fd.append('action',     'agregar_movimiento_cliente');
    fd.append('cliente_id', _clienteId);
    fd.append('tipo',       'deuda');
    fd.append('concepto',   concepto);
    fd.append('monto',      monto);
    fd.append('fecha',      fecha);

    const r = await fetch(BASE_URL + '/api.php', {method:'POST', body: fd});
    const d = await r.json();
    if (d.ok) { cerrarModales(); location.reload(); }
    else { errEl.textContent = d.msg || 'Error.'; errEl.style.display='block'; }
}

async function guardarAbono() {
    const monto = parseFloat(document.getElementById('abono-monto').value) || 0;
    const nota  = document.getElementById('abono-nota').value.trim();
    const fecha = document.getElementById('abono-fecha').value;
    const medio = document.getElementById('abono-medio').value;
    const nombre = document.getElementById('abono-nombre').textContent.replace('Cliente: ', '').trim();
    const errEl = document.getElementById('abono-err');
    errEl.style.display = 'none';

    if (monto <= 0) { errEl.textContent = 'Ingresa un monto válido.'; errEl.style.display='block'; return; }
    if (!fecha)     { errEl.textContent = 'Selecciona una fecha.';    errEl.style.display='block'; return; }

    const fd = new FormData();
    fd.append('action',         'agregar_movimiento_cliente');
    fd.append('cliente_id',     _clienteId);
    fd.append('tipo',           'abono');
    fd.append('concepto',       nota);
    fd.append('monto',          monto);
    fd.append('fecha',          fecha);
    fd.append('medio_pago',     medio);
    fd.append('cliente_nombre', nombre);

    const r = await fetch(BASE_URL + '/api.php', {method:'POST', body: fd});
    const d = await r.json();
    if (d.ok) { cerrarModales(); location.reload(); }
    else { errEl.textContent = d.msg || 'Error.'; errEl.style.display='block'; }
}

async function eliminarCliente(id, nombre) {
    if (!confirm(`¿Eliminar a "${nombre}" y todo su historial?`)) return;
    const fd = new FormData();
    fd.append('action', 'eliminar_cliente');
    fd.append('id', id);
    await fetch(BASE_URL + '/api.php', {method:'POST', body: fd});
    location.reload();
}

function fmt(n) {
    return '$ ' + Math.round(n).toLocaleString('es-CO');
}

document.querySelectorAll('.modal-overlay').forEach(m =>
    m.addEventListener('click', e => { if (e.target === m) cerrarModales(); })
);

const d = new Date();
const dias   = ['Domingo','Lunes','Martes','Miércoles','Jueves','Viernes','Sábado'];
const meses  = ['enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre'];
document.getElementById('fecha-hoy').textContent =
    `${dias[d.getDay()]} ${d.getDate()} de ${meses[d.getMonth()]} de ${d.getFullYear()}`;
</script>
</body>
</html>
