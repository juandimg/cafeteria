<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/data.php';

$ventas = leer_ventas();
$hoy    = date('Y-m-d');
$mes    = date('Y-m');

$ventas_hoy = array_filter($ventas, fn($v) => ($v['fecha'] ?? '') === $hoy);
$ventas_mes = array_filter($ventas, fn($v) => str_starts_with($v['fecha'] ?? '', $mes));

$total_hoy = array_sum(array_column(array_values($ventas_hoy), 'total'));
$total_mes = array_sum(array_column(array_values($ventas_mes), 'total'));
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>CafePro – Reportes</title>
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/style.css">
<style>
.tab-bar { display:flex; gap:4px; margin-bottom:20px; background:var(--white); padding:6px; border-radius:12px; width:fit-content; }
.tab-btn { padding:8px 20px; border-radius:8px; border:none; background:transparent; font-size:13px; font-weight:600; color:var(--txt-g); cursor:pointer; transition:.15s; }
.tab-btn.active { background:var(--amber); color:#fff; }
.tab-btn:hover:not(.active) { background:#F5F5F5; color:var(--txt-d); }

.day-row { cursor:pointer; transition:background .12s; }
.day-row:hover { background:#FFF8E1 !important; }
.day-row td { padding:12px 14px; border-bottom:1px solid #F0F0F0; }
.day-row .day-date { font-weight:700; font-size:13px; }
.day-row .day-count { font-size:11px; color:var(--txt-g); }
.day-chevron { transition:transform .2s; display:inline-block; font-size:12px; color:var(--txt-g); margin-left:6px; }
.day-chevron.open { transform:rotate(90deg); }

.detail-rows { display:none; }
.detail-rows.open { display:table-row-group; }
.detail-rows tr td { padding:8px 14px; background:#FAFAFA; font-size:12px; border-bottom:1px solid #F5F5F5; }
.detail-rows tr:last-child td { border-bottom:2px solid #EEEEEE; }

.quick-btns { display:flex; gap:6px; flex-wrap:wrap; }
.quick-btn { padding:5px 12px; border-radius:6px; border:2px solid #E0E0E0; background:#fff; font-size:11px; font-weight:600; color:var(--txt-g); cursor:pointer; transition:.12s; }
.quick-btn:hover, .quick-btn.active { border-color:var(--amber); color:var(--amber); background:#FFF8E1; }

.summary-band { background:var(--white); border-radius:10px; padding:12px 18px; margin-bottom:16px; display:flex; gap:24px; align-items:center; flex-wrap:wrap; }
.sb-item { display:flex; flex-direction:column; }
.sb-lbl { font-size:10px; font-weight:700; color:var(--txt-g); letter-spacing:.4px; }
.sb-val { font-size:16px; font-weight:700; }
.sb-sep { width:1px; height:32px; background:#EEEEEE; }
</style>
</head>
<body>
<div class="app">
<?php require __DIR__ . '/includes/header.php'; ?>
<div class="main-content">
    <div class="topbar">
        <span class="topbar-title">📊 Reportes</span>
        <span class="topbar-date" id="fecha-hoy"></span>
    </div>
    <div class="page-body">

        <!-- Stats cards -->
        <div class="stats-row cols-4" style="margin-bottom:20px">
            <div class="stat-card bg-blue">
                <span class="stat-ico">🛒</span>
                <div><div class="stat-val"><?= count($ventas_hoy) ?></div><div class="stat-lbl">Ventas hoy</div></div>
            </div>
            <div class="stat-card bg-green">
                <span class="stat-ico">💰</span>
                <div><div class="stat-val"><?= fmt_money($total_hoy) ?></div><div class="stat-lbl">Ingresos hoy</div></div>
            </div>
            <div class="stat-card bg-purple">
                <span class="stat-ico">📅</span>
                <div><div class="stat-val"><?= count($ventas_mes) ?></div><div class="stat-lbl">Ventas este mes</div></div>
            </div>
            <div class="stat-card bg-amber">
                <span class="stat-ico">💵</span>
                <div><div class="stat-val"><?= fmt_money($total_mes) ?></div><div class="stat-lbl">Ingresos del mes</div></div>
            </div>
        </div>

        <!-- Tabs -->
        <div class="tab-bar">
            <button class="tab-btn active" onclick="setTab('dias', this)">📅 Por día</button>
            <button class="tab-btn" onclick="setTab('movimientos', this)">🧾 Movimientos</button>
        </div>

        <!-- ══ TAB: Por día ══ -->
        <div id="tab-dias">
            <div class="filter-bar" style="margin-bottom:16px">
                <div class="filter-group">
                    <label class="filter-label">DESDE</label>
                    <input type="date" id="d-desde" class="filter-control" oninput="renderDias()">
                </div>
                <div class="filter-group">
                    <label class="filter-label">HASTA</label>
                    <input type="date" id="d-hasta" class="filter-control" oninput="renderDias()">
                </div>
                <div class="filter-group">
                    <label class="filter-label">ACCESO RÁPIDO</label>
                    <div class="quick-btns">
                        <button class="quick-btn" onclick="quickRange('hoy')">Hoy</button>
                        <button class="quick-btn" onclick="quickRange('semana')">Esta semana</button>
                        <button class="quick-btn" onclick="quickRange('mes')">Este mes</button>
                        <button class="quick-btn" onclick="quickRange('todo')">Todo</button>
                    </div>
                </div>
            </div>

            <!-- Summary band días -->
            <div class="summary-band" id="dias-summary" style="display:none">
                <div class="sb-item"><span class="sb-lbl">DÍAS CON VENTAS</span><span class="sb-val" id="sd-dias">0</span></div>
                <div class="sb-sep"></div>
                <div class="sb-item"><span class="sb-lbl">TOTAL VENTAS</span><span class="sb-val" id="sd-ventas">0</span></div>
                <div class="sb-sep"></div>
                <div class="sb-item"><span class="sb-lbl">💵 EFECTIVO</span><span class="sb-val text-green" id="sd-ef">$ 0</span></div>
                <div class="sb-sep"></div>
                <div class="sb-item"><span class="sb-lbl">📱 TRANSFERENCIA</span><span class="sb-val text-blue" id="sd-tr">$ 0</span></div>
                <div class="sb-sep"></div>
                <div class="sb-item"><span class="sb-lbl">TOTAL INGRESOS</span><span class="sb-val text-amber" id="sd-total">$ 0</span></div>
            </div>

            <div id="dias-empty" class="empty-state" style="display:none">
                <div class="es-ico">📅</div>No hay ventas en el período seleccionado.
            </div>

            <div class="report-wrap" id="dias-wrap" style="display:none">
                <table class="report-table" style="table-layout:fixed">
                    <thead>
                        <tr>
                            <th style="width:38%">Fecha</th>
                            <th style="width:12%;text-align:center">Ventas</th>
                            <th style="width:18%;text-align:right">💵 Efectivo</th>
                            <th style="width:18%;text-align:right">📱 Transfer.</th>
                            <th style="width:14%;text-align:right">Total</th>
                        </tr>
                    </thead>
                    <tbody id="dias-tbody"></tbody>
                </table>
            </div>
        </div>

        <!-- ══ TAB: Movimientos ══ -->
        <div id="tab-movimientos" style="display:none">
            <div class="filter-bar" style="margin-bottom:16px">
                <div class="filter-group">
                    <label class="filter-label">DESDE</label>
                    <input type="date" id="m-desde" class="filter-control" oninput="renderMovimientos()">
                </div>
                <div class="filter-group">
                    <label class="filter-label">HASTA</label>
                    <input type="date" id="m-hasta" class="filter-control" oninput="renderMovimientos()">
                </div>
                <div class="filter-group">
                    <label class="filter-label">MEDIO DE PAGO</label>
                    <select id="m-medio" class="filter-control" onchange="renderMovimientos()">
                        <option value="">Todos</option>
                        <option value="Efectivo">💵 Efectivo</option>
                        <option value="Transferencia">📱 Transferencia</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label class="filter-label">BUSCAR PRODUCTO</label>
                    <input type="text" id="m-buscar" class="filter-control" style="width:180px"
                           placeholder="Ej: Café…" oninput="renderMovimientos()">
                </div>
                <div class="filter-sep"></div>
                <div class="quick-btns" style="align-self:flex-end">
                    <button class="quick-btn" onclick="quickMov('hoy')">Hoy</button>
                    <button class="quick-btn" onclick="quickMov('semana')">Semana</button>
                    <button class="quick-btn" onclick="quickMov('mes')">Mes</button>
                    <button class="quick-btn" onclick="quickMov('todo')">Todo</button>
                </div>
            </div>

            <!-- Summary band movimientos -->
            <div class="summary-band" id="mov-summary" style="display:none">
                <div class="sb-item"><span class="sb-lbl">VENTAS</span><span class="sb-val" id="sm-cnt">0</span></div>
                <div class="sb-sep"></div>
                <div class="sb-item"><span class="sb-lbl">💵 EFECTIVO</span><span class="sb-val text-green" id="sm-ef">$ 0</span></div>
                <div class="sb-sep"></div>
                <div class="sb-item"><span class="sb-lbl">📱 TRANSFERENCIA</span><span class="sb-val text-blue" id="sm-tr">$ 0</span></div>
                <div class="sb-sep"></div>
                <div class="sb-item"><span class="sb-lbl">TOTAL</span><span class="sb-val text-amber" id="sm-total">$ 0</span></div>
            </div>

            <div id="mov-empty" class="empty-state" style="display:none">
                <div class="es-ico">🧾</div>No hay ventas que coincidan con los filtros.
            </div>

            <div class="report-wrap" id="mov-wrap" style="display:none">
                <table class="report-table">
                    <thead>
                        <tr>
                            <th style="width:160px">Fecha y hora</th>
                            <th>Medio de pago</th>
                            <th>Productos</th>
                            <th style="text-align:right">Total</th>
                        </tr>
                    </thead>
                    <tbody id="mov-tbody"></tbody>
                </table>
            </div>
        </div>

    </div>
</div>
</div>

<script>
const VENTAS = <?= json_encode($ventas) ?>;
const HOY    = '<?= $hoy ?>';

const diasN  = ['Domingo','Lunes','Martes','Miércoles','Jueves','Viernes','Sábado'];
const mesesN = ['enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre'];
const d = new Date();
document.getElementById('fecha-hoy').textContent =
    `${diasN[d.getDay()]} ${d.getDate()} de ${mesesN[d.getMonth()]} de ${d.getFullYear()}`;

function fmtMoney(v) { return '$ ' + Math.round(v).toLocaleString('es-CO'); }
function esc(s) { const e=document.createElement('div'); e.textContent=String(s||''); return e.innerHTML; }

function fechaLarga(iso) {
    const [y, m, dd] = iso.split('-').map(Number);
    const dt = new Date(y, m - 1, dd);
    const nom = diasN[dt.getDay()];
    return `${nom} ${dd} de ${mesesN[m-1]} de ${y}`;
}

// ── Tabs ──
function setTab(tab, btn) {
    document.getElementById('tab-dias').style.display        = tab === 'dias'        ? '' : 'none';
    document.getElementById('tab-movimientos').style.display = tab === 'movimientos' ? '' : 'none';
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
}

// ── Quick date helpers ──
function isoWeekStart() {
    const now = new Date(); const day = now.getDay();
    const diff = now.getDate() - day + (day === 0 ? -6 : 1);
    return new Date(now.setDate(diff)).toISOString().slice(0,10);
}
function isoMonthStart() { return HOY.slice(0,7) + '-01'; }

function quickRange(r) {
    const desde = document.getElementById('d-desde');
    const hasta = document.getElementById('d-hasta');
    if (r === 'hoy')    { desde.value = HOY; hasta.value = HOY; }
    if (r === 'semana') { desde.value = isoWeekStart(); hasta.value = HOY; }
    if (r === 'mes')    { desde.value = isoMonthStart(); hasta.value = HOY; }
    if (r === 'todo')   { desde.value = ''; hasta.value = ''; }
    renderDias();
}
function quickMov(r) {
    const desde = document.getElementById('m-desde');
    const hasta = document.getElementById('m-hasta');
    if (r === 'hoy')    { desde.value = HOY; hasta.value = HOY; }
    if (r === 'semana') { desde.value = isoWeekStart(); hasta.value = HOY; }
    if (r === 'mes')    { desde.value = isoMonthStart(); hasta.value = HOY; }
    if (r === 'todo')   { desde.value = ''; hasta.value = ''; }
    renderMovimientos();
}

function filtrarBase(desde, hasta) {
    return VENTAS.filter(v => {
        if (desde && v.fecha < desde) return false;
        if (hasta && v.fecha > hasta) return false;
        return true;
    });
}

// ── Tab 1: Por día ──
function renderDias() {
    const desde = document.getElementById('d-desde').value;
    const hasta = document.getElementById('d-hasta').value;
    const lista = filtrarBase(desde, hasta);

    // Group by date
    const porFecha = {};
    lista.forEach(v => {
        const f = v.fecha;
        if (!porFecha[f]) porFecha[f] = { ventas: [], ef: 0, tr: 0, total: 0 };
        porFecha[f].ventas.push(v);
        const t = v.total || 0;
        porFecha[f].total += t;
        if ((v.medio_pago || 'Efectivo') === 'Efectivo') porFecha[f].ef += t;
        else porFecha[f].tr += t;
    });

    const fechas = Object.keys(porFecha).sort((a,b) => b.localeCompare(a));

    const empty = document.getElementById('dias-empty');
    const wrap  = document.getElementById('dias-wrap');
    const summ  = document.getElementById('dias-summary');

    if (!fechas.length) {
        empty.style.display = 'block'; wrap.style.display = 'none'; summ.style.display = 'none';
        return;
    }
    empty.style.display = 'none'; wrap.style.display = ''; summ.style.display = '';

    // Update summary band
    const totEf  = Object.values(porFecha).reduce((s,g) => s + g.ef,    0);
    const totTr  = Object.values(porFecha).reduce((s,g) => s + g.tr,    0);
    const totAll = Object.values(porFecha).reduce((s,g) => s + g.total, 0);
    const totVts = lista.length;
    document.getElementById('sd-dias').textContent   = fechas.length;
    document.getElementById('sd-ventas').textContent = totVts;
    document.getElementById('sd-ef').textContent     = fmtMoney(totEf);
    document.getElementById('sd-tr').textContent     = fmtMoney(totTr);
    document.getElementById('sd-total').textContent  = fmtMoney(totAll);

    const tbody = document.getElementById('dias-tbody');
    tbody.innerHTML = '';

    fechas.forEach(fecha => {
        const g      = porFecha[fecha];
        const esHoy  = fecha === HOY;
        const dayId  = 'day-' + fecha.replace(/-/g, '');

        // Day summary row
        const tr = document.createElement('tr');
        tr.className = 'day-row';
        tr.style.background = esHoy ? '#FFFDE7' : '';
        tr.innerHTML = `
            <td>
                <span class="day-date">${fechaLarga(fecha)}</span>
                ${esHoy ? '<span class="badge badge-amber" style="font-size:9px;padding:2px 7px;margin-left:6px">HOY</span>' : ''}
                <span class="day-chevron" id="chev-${dayId}">▶</span>
                <div class="day-count">${g.ventas.length} venta${g.ventas.length !== 1 ? 's' : ''}</div>
            </td>
            <td style="text-align:center;font-weight:700">${g.ventas.length}</td>
            <td style="text-align:right;color:var(--green);font-weight:600">${g.ef ? fmtMoney(g.ef) : '—'}</td>
            <td style="text-align:right;color:var(--blue);font-weight:600">${g.tr ? fmtMoney(g.tr) : '—'}</td>
            <td style="text-align:right;font-weight:700;font-size:14px">${fmtMoney(g.total)}</td>
        `;
        tr.onclick = () => toggleDay(dayId);
        tbody.appendChild(tr);

        // Detail rows (hidden by default)
        const detailGroup = document.createElement('tbody');
        detailGroup.id      = dayId;
        detailGroup.className = 'detail-rows';

        g.ventas.slice().reverse().forEach(v => {
            const items   = (v.items || []).map(it => `${esc(it.nombre)} x${it.cantidad}`).join(', ');
            const medio   = v.medio_pago || 'Efectivo';
            const icoBadge = medio === 'Efectivo'
                ? '<span class="badge badge-green" style="font-size:10px">💵 Efectivo</span>'
                : '<span class="badge badge-blue" style="font-size:10px">📱 Transferencia</span>';
            const dtr = document.createElement('tr');
            dtr.innerHTML = `
                <td style="padding-left:30px;color:var(--txt-g)">${v.hora || ''}</td>
                <td>${icoBadge}</td>
                <td style="color:var(--txt-d)">${items}</td>
                <td></td>
                <td style="text-align:right;font-weight:700;color:var(--green)">${fmtMoney(v.total)}</td>
            `;
            detailGroup.appendChild(dtr);
        });

        tbody.parentNode.insertBefore(detailGroup, tbody.nextSibling);
        tbody.parentNode.appendChild(tbody); // keep tbody at end for next iteration — actually let's just append after
    });
}

function toggleDay(dayId) {
    const group = document.getElementById(dayId);
    const chev  = document.getElementById('chev-' + dayId);
    if (!group) return;
    const isOpen = group.classList.toggle('open');
    chev.classList.toggle('open', isOpen);
}

// ── Tab 2: Movimientos ──
function renderMovimientos() {
    const desde  = document.getElementById('m-desde').value;
    const hasta  = document.getElementById('m-hasta').value;
    const medio  = document.getElementById('m-medio').value;
    const buscar = document.getElementById('m-buscar').value.toLowerCase().trim();

    let lista = filtrarBase(desde, hasta).filter(v => {
        if (medio && (v.medio_pago || 'Efectivo') !== medio) return false;
        if (buscar) {
            const ok = (v.items || []).some(it => (it.nombre || '').toLowerCase().includes(buscar));
            if (!ok) return false;
        }
        return true;
    });

    lista = [...lista].sort((a, b) => {
        if (b.fecha !== a.fecha) return b.fecha.localeCompare(a.fecha);
        return (b.hora || '').localeCompare(a.hora || '');
    });

    const empty = document.getElementById('mov-empty');
    const wrap  = document.getElementById('mov-wrap');
    const summ  = document.getElementById('mov-summary');

    if (!lista.length) {
        empty.style.display = 'block'; wrap.style.display = 'none'; summ.style.display = 'none';
        return;
    }
    empty.style.display = 'none'; wrap.style.display = ''; summ.style.display = '';

    const totEf  = lista.filter(v => (v.medio_pago||'Efectivo') === 'Efectivo').reduce((s,v) => s+v.total, 0);
    const totTr  = lista.filter(v => (v.medio_pago||'Efectivo') !== 'Efectivo').reduce((s,v) => s+v.total, 0);
    const totAll = lista.reduce((s,v) => s + (v.total||0), 0);
    document.getElementById('sm-cnt').textContent   = lista.length;
    document.getElementById('sm-ef').textContent    = fmtMoney(totEf);
    document.getElementById('sm-tr').textContent    = fmtMoney(totTr);
    document.getElementById('sm-total').textContent = fmtMoney(totAll);

    const tbody = document.getElementById('mov-tbody');
    tbody.innerHTML = lista.map(v => {
        const resumen = (v.items || []).map(it => `${esc(it.nombre)} x${it.cantidad}`).join(', ');
        const medio   = v.medio_pago || 'Efectivo';
        const badge   = medio === 'Efectivo'
            ? '<span class="badge badge-green">💵 Efectivo</span>'
            : '<span class="badge badge-blue">📱 Transferencia</span>';
        const esHoy   = v.fecha === HOY;
        return `<tr>
            <td>
                <div style="font-weight:700;font-size:12px">${esc(v.fecha)}</div>
                <div style="color:var(--txt-g);font-size:11px">${esc(v.hora || '')}</div>
            </td>
            <td>${badge}</td>
            <td style="color:var(--txt-d);max-width:280px;font-size:12px">${resumen}</td>
            <td style="text-align:right;font-weight:700;color:${esHoy ? 'var(--green)' : 'var(--txt-d)'}">
                ${fmtMoney(v.total)}
            </td>
        </tr>`;
    }).join('');
}

// Initial render — default: this month
document.getElementById('d-desde').value = isoMonthStart();
document.getElementById('d-hasta').value = HOY;
renderDias();
renderMovimientos();
</script>
</body>
</html>
