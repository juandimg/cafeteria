<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/data.php';

$ventas  = leer_ventas();
$compras = leer_compras();
$hoy     = date('Y-m-d');
$mes     = date('Y-m');

$ventas_hoy  = array_filter($ventas,  fn($v) => ($v['fecha'] ?? '') === $hoy);
$ventas_mes  = array_filter($ventas,  fn($v) => str_starts_with($v['fecha'] ?? '', $mes));
$compras_hoy = array_filter($compras, fn($c) => ($c['fecha'] ?? '') === $hoy);
$compras_mes = array_filter($compras, fn($c) => str_starts_with($c['fecha'] ?? '', $mes));

$total_hoy      = array_sum(array_map(fn($v) => (float)$v['total'], array_values($ventas_hoy)));
$total_mes      = array_sum(array_map(fn($v) => (float)$v['total'], array_values($ventas_mes)));
$egresos_hoy    = array_sum(array_map(fn($c) => (float)$c['total'], array_values($compras_hoy)));
$egresos_mes    = array_sum(array_map(fn($c) => (float)$c['total'], array_values($compras_mes)));
$ganancia_hoy   = $total_hoy - $egresos_hoy;
$ganancia_mes   = $total_mes - $egresos_mes;
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

.period-btn { padding:7px 16px; border-radius:20px; border:2px solid #E0E0E0; background:#fff; font-size:12px; font-weight:700; color:var(--txt-g); cursor:pointer; transition:.15s; }
.period-btn:hover { border-color:var(--amber); color:var(--amber); }
.period-btn-active { border-color:var(--amber) !important; background:var(--amber) !important; color:#fff !important; }
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

        <!-- Selector de período -->
        <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:16px">
            <span style="font-size:12px;font-weight:700;color:var(--txt-g);letter-spacing:.4px">PERÍODO:</span>
            <button id="pq-hoy"    class="period-btn" onclick="quickRange('hoy')">Hoy</button>
            <button id="pq-semana" class="period-btn" onclick="quickRange('semana')">Esta semana</button>
            <button id="pq-mes"    class="period-btn period-btn-active" onclick="quickRange('mes')">Este mes</button>
            <button id="pq-todo"   class="period-btn" onclick="quickRange('todo')">Todo</button>
            <div style="display:flex;align-items:center;gap:6px;margin-left:8px">
                <input type="date" id="d-desde" class="filter-control" style="height:34px" oninput="clearPeriodActive();renderDias()">
                <span style="color:var(--txt-g);font-size:12px">—</span>
                <input type="date" id="d-hasta" class="filter-control" style="height:34px" oninput="clearPeriodActive();renderDias()">
            </div>
        </div>

        <!-- Stats cards dinámicas -->
        <div class="stats-row cols-4" style="margin-bottom:20px">
            <div class="stat-card bg-blue">
                <span class="stat-ico">🛒</span>
                <div>
                    <div class="stat-val" id="sc-ventas">—</div>
                    <div class="stat-lbl" id="sc-ventas-lbl">Ventas</div>
                </div>
            </div>
            <div class="stat-card bg-green">
                <span class="stat-ico">💰</span>
                <div>
                    <div class="stat-val" id="sc-ingresos">—</div>
                    <div class="stat-lbl">Ingresos</div>
                    <div style="font-size:11px;color:rgba(255,255,255,.8);margin-top:2px" id="sc-ingresos-sub"></div>
                </div>
            </div>
            <div class="stat-card" style="background:var(--red)">
                <span class="stat-ico">📦</span>
                <div>
                    <div class="stat-val" id="sc-egresos">—</div>
                    <div class="stat-lbl">Egresos (compras)</div>
                    <div style="font-size:11px;color:rgba(255,255,255,.8);margin-top:2px" id="sc-egresos-sub"></div>
                </div>
            </div>
            <div class="stat-card bg-amber" id="sc-ganancia-card">
                <span class="stat-ico" id="sc-ganancia-ico">💹</span>
                <div>
                    <div class="stat-val" id="sc-ganancia">—</div>
                    <div class="stat-lbl">Ganancia neta</div>
                    <div style="font-size:11px;color:rgba(255,255,255,.8);margin-top:2px" id="sc-ganancia-sub"></div>
                </div>
            </div>
        </div>

        <!-- Tabs -->
        <div class="tab-bar">
            <button class="tab-btn active" onclick="setTab('dias', this)">📅 Por día</button>
            <button class="tab-btn" onclick="setTab('movimientos', this)">🧾 Movimientos</button>
        </div>

        <!-- ══ TAB: Por día ══ -->
        <div id="tab-dias">

            <div id="dias-empty" class="empty-state" style="display:none">
                <div class="es-ico">📅</div>No hay ventas en el período seleccionado.
            </div>

            <div class="report-wrap" id="dias-wrap" style="display:none">
                <table class="report-table" style="table-layout:fixed">
                    <thead>
                        <tr>
                            <th style="width:26%">Fecha</th>
                            <th style="width:8%;text-align:center">Ventas</th>
                            <th style="width:13%;text-align:right">💵 Efectivo</th>
                            <th style="width:13%;text-align:right">📱 Transfer.</th>
                            <th style="width:13%;text-align:right">Ingresos</th>
                            <th style="width:13%;text-align:right">🛒 Egresos</th>
                            <th style="width:14%;text-align:right">💹 Neto</th>
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
                        <option value="Crédito">📋 Crédito</option>
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
const VENTAS  = <?= json_encode($ventas) ?>;
const COMPRAS = <?= json_encode($compras) ?>;
const HOY     = '<?= $hoy ?>';

const diasN  = ['Domingo','Lunes','Martes','Miércoles','Jueves','Viernes','Sábado'];
const mesesN = ['enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre'];
const d = new Date();
document.getElementById('fecha-hoy').textContent =
    `${diasN[d.getDay()]} ${d.getDate()} de ${mesesN[d.getMonth()]} de ${d.getFullYear()}`;

function fmtMoney(v) { return '$ ' + Math.round(v).toLocaleString('es-CO'); }
function esc(s) { const e=document.createElement('div'); e.textContent=String(s||''); return e.innerHTML; }
function medioBadge(medio) {
    const esAbono = (medio||'').includes('(abono)');
    const base    = (medio||'').replace(' (abono)', '');
    const abonoTag = esAbono ? ' <span style="font-size:10px;color:var(--amber);font-weight:700">⚡ abono</span>' : '';
    if (base === 'Efectivo')      return '<span class="badge badge-green">💵 Efectivo</span>' + abonoTag;
    if (base === 'Transferencia') return '<span class="badge badge-blue">📱 Transferencia</span>' + abonoTag;
    if (base === 'Crédito')       return '<span class="badge" style="background:#EDE7F6;color:var(--purple)">📋 Crédito</span>' + abonoTag;
    return '<span class="badge" style="background:#F5F5F5;color:var(--slate)">' + esc(medio) + '</span>';
}

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

const PERIOD_LABELS = { hoy: 'Hoy', semana: 'Esta semana', mes: 'Este mes', todo: 'Todo el tiempo' };
let activePeriod = 'mes';

function clearPeriodActive() {
    activePeriod = null;
    ['hoy','semana','mes','todo'].forEach(k => {
        document.getElementById('pq-' + k)?.classList.remove('period-btn-active');
    });
}

function quickRange(r) {
    const desde = document.getElementById('d-desde');
    const hasta = document.getElementById('d-hasta');
    if (r === 'hoy')    { desde.value = HOY; hasta.value = HOY; }
    if (r === 'semana') { desde.value = isoWeekStart(); hasta.value = HOY; }
    if (r === 'mes')    { desde.value = isoMonthStart(); hasta.value = HOY; }
    if (r === 'todo')   { desde.value = ''; hasta.value = ''; }
    clearPeriodActive();
    activePeriod = r;
    document.getElementById('pq-' + r)?.classList.add('period-btn-active');
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
        if (!porFecha[f]) porFecha[f] = { ventas: [], compras: [], ef: 0, tr: 0, total: 0, egresos: 0 };
        porFecha[f].ventas.push(v);
        const t  = parseFloat(v.total) || 0;
        porFecha[f].total += t;
        const mp = v.medio_pago || 'Efectivo';
        if (mp === 'Efectivo' || mp === 'Efectivo (abono)')                porFecha[f].ef += t;
        else if (mp === 'Transferencia' || mp === 'Transferencia (abono)') porFecha[f].tr += t;
    });

    // Add compras (egresos) per date
    COMPRAS.forEach(c => {
        const f = c.fecha;
        if (!porFecha[f]) return; // only show days with sales (or change to also show compra-only days)
        porFecha[f].compras.push(c);
        porFecha[f].egresos += parseFloat(c.total) || 0;
    });

    const fechas = Object.keys(porFecha).sort((a,b) => b.localeCompare(a));

    const empty = document.getElementById('dias-empty');
    const wrap  = document.getElementById('dias-wrap');

    if (!fechas.length) {
        empty.style.display = 'block'; wrap.style.display = 'none';
        // Reset cards to zero when no data
        ['sc-ventas','sc-ingresos','sc-egresos','sc-ganancia'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.textContent = '$ 0';
        });
        document.getElementById('sc-ventas').textContent = '0';
        return;
    }
    empty.style.display = 'none'; wrap.style.display = '';

    // Totals
    const totEf   = Object.values(porFecha).reduce((s,g) => s + g.ef,      0);
    const totTr   = Object.values(porFecha).reduce((s,g) => s + g.tr,      0);
    const totAll  = Object.values(porFecha).reduce((s,g) => s + g.total,   0);
    const totEgr  = Object.values(porFecha).reduce((s,g) => s + g.egresos, 0);
    const totNeto = totAll - totEgr;
    const totVts  = lista.length;
    const periodoLbl = activePeriod ? PERIOD_LABELS[activePeriod] : 'Período';

    // Update stat cards
    document.getElementById('sc-ventas').textContent      = totVts;
    document.getElementById('sc-ventas-lbl').textContent  = 'Ventas · ' + periodoLbl;
    document.getElementById('sc-ingresos').textContent    = fmtMoney(totAll);
    document.getElementById('sc-ingresos-sub').textContent = totEf && totTr
        ? `💵 ${fmtMoney(totEf)}  📱 ${fmtMoney(totTr)}`
        : totEf ? `💵 ${fmtMoney(totEf)}` : totTr ? `📱 ${fmtMoney(totTr)}` : '';
    document.getElementById('sc-egresos').textContent     = totEgr ? fmtMoney(totEgr) : '$ 0';
    document.getElementById('sc-egresos-sub').textContent = totEgr
        ? Object.values(porFecha).filter(g=>g.compras.length).length + ' día(s) con compras'
        : 'Sin egresos';
    document.getElementById('sc-ganancia').textContent    = fmtMoney(totNeto);
    document.getElementById('sc-ganancia-sub').textContent = fmtMoney(totAll) + ' − ' + fmtMoney(totEgr);
    const gCard = document.getElementById('sc-ganancia-card');
    gCard.style.background = totNeto >= 0 ? '' : 'var(--red)';
    document.getElementById('sc-ganancia-ico').textContent = totNeto >= 0 ? '💹' : '📉';

    const tbody = document.getElementById('dias-tbody');
    tbody.innerHTML = '';

    fechas.forEach(fecha => {
        const g      = porFecha[fecha];
        const neto   = g.total - g.egresos;
        const esHoy  = fecha === HOY;
        const dayId  = 'day-' + fecha.replace(/-/g, '');

        const countLabel = g.ventas.length + ' venta' + (g.ventas.length !== 1 ? 's' : '')
            + (g.compras.length ? ` · ${g.compras.length} compra${g.compras.length !== 1 ? 's' : ''}` : '');

        const tr = document.createElement('tr');
        tr.className = 'day-row';
        tr.style.background = esHoy ? '#FFFDE7' : '';
        tr.innerHTML = `
            <td>
                <span class="day-date">${fechaLarga(fecha)}</span>
                ${esHoy ? '<span class="badge badge-amber" style="font-size:9px;padding:2px 7px;margin-left:6px">HOY</span>' : ''}
                <span class="day-chevron" id="chev-${dayId}">▶</span>
                <div class="day-count">${countLabel}</div>
            </td>
            <td style="text-align:center;font-weight:700">${g.ventas.length}</td>
            <td style="text-align:right;color:var(--green);font-weight:600">${g.ef ? fmtMoney(g.ef) : '—'}</td>
            <td style="text-align:right;color:var(--blue);font-weight:600">${g.tr ? fmtMoney(g.tr) : '—'}</td>
            <td style="text-align:right;font-weight:700">${fmtMoney(g.total)}</td>
            <td style="text-align:right;color:var(--red);font-weight:600">${g.egresos ? '− ' + fmtMoney(g.egresos) : '—'}</td>
            <td style="text-align:right;font-weight:700;font-size:14px;color:${neto >= 0 ? 'var(--green)' : 'var(--red)'}">${fmtMoney(neto)}</td>
        `;
        tr.onclick = () => toggleDay(dayId);
        tbody.appendChild(tr);

        // Detail rows
        const detailGroup = document.createElement('tbody');
        detailGroup.id        = dayId;
        detailGroup.className = 'detail-rows';

        g.ventas.slice().reverse().forEach(v => {
            const items    = (v.items || []).map(it => `${esc(it.nombre)} x${it.cantidad}`).join(', ');
            const icoBadge = medioBadge(v.medio_pago || 'Efectivo');
            const dtr = document.createElement('tr');
            dtr.innerHTML = `
                <td style="padding-left:30px;color:var(--txt-g)">${v.hora || ''}</td>
                <td>${icoBadge}</td>
                <td style="color:var(--txt-d)" colspan="2">${items}</td>
                <td style="text-align:right;font-weight:700;color:var(--green)">${fmtMoney(parseFloat(v.total)||0)}</td>
                <td></td>
                <td></td>
            `;
            detailGroup.appendChild(dtr);
        });

        g.compras.forEach(c => {
            const items = (c.items || []).map(it => `${esc(it.producto)} x${it.cantidad}`).join(', ') || esc(c.notas || c.proveedor || '');
            const dtr = document.createElement('tr');
            dtr.style.background = '#FFF3F3';
            dtr.innerHTML = `
                <td style="padding-left:30px;color:var(--red);font-size:11px">🛒 Compra</td>
                <td><span class="badge" style="background:#FFEBEE;color:var(--red);font-size:11px">📦 ${esc(c.proveedor||'—')}</span></td>
                <td style="color:var(--txt-g);font-size:11px" colspan="2">${items}</td>
                <td></td>
                <td style="text-align:right;font-weight:700;color:var(--red)">− ${fmtMoney(parseFloat(c.total)||0)}</td>
                <td></td>
            `;
            detailGroup.appendChild(dtr);
        });

        tbody.parentNode.insertBefore(detailGroup, tbody.nextSibling);
        tbody.parentNode.appendChild(tbody);
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
        if (medio) {
            const mp = v.medio_pago || 'Efectivo';
            if (mp !== medio && mp !== medio + ' (abono)') return false;
        }
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

    const totEf  = lista.filter(v => ['Efectivo','Efectivo (abono)'].includes(v.medio_pago||'Efectivo'))
                        .reduce((s,v) => s + (parseFloat(v.total)||0), 0);
    const totTr  = lista.filter(v => ['Transferencia','Transferencia (abono)'].includes(v.medio_pago||''))
                        .reduce((s,v) => s + (parseFloat(v.total)||0), 0);
    const totAll = lista.reduce((s,v) => s + (parseFloat(v.total)||0), 0);
    document.getElementById('sm-cnt').textContent   = lista.length;
    document.getElementById('sm-ef').textContent    = fmtMoney(totEf);
    document.getElementById('sm-tr').textContent    = fmtMoney(totTr);
    document.getElementById('sm-total').textContent = fmtMoney(totAll);

    const tbody = document.getElementById('mov-tbody');
    tbody.innerHTML = lista.map(v => {
        const resumen = (v.items || []).map(it => `${esc(it.nombre)} x${it.cantidad}`).join(', ');
        const medio   = v.medio_pago || 'Efectivo';
        const badge   = medioBadge(medio);
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
quickRange('mes');
renderMovimientos();
</script>
</body>
</html>
