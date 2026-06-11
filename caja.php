<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/data.php';

$hoy    = date('Y-m-d');
$ventas = leer_ventas();
$caja   = leer_caja();

$base = ($caja['fecha'] ?? '') === $hoy ? (float)($caja['base'] ?? 0) : 0;
$ventas_hoy = array_filter($ventas, fn($v) => ($v['fecha'] ?? '') === $hoy);
$ingresos   = 0;
foreach ($ventas_hoy as $v) $ingresos += (float)($v['total'] ?? 0);
$total_caja = $base + $ingresos;
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>CafePro – Caja</title>
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/style.css">
</head>
<body>
<div class="app">
<?php require __DIR__ . '/includes/header.php'; ?>
<div class="main-content">
    <div class="topbar">
        <span class="topbar-title">💰 Caja</span>
        <span class="topbar-date" id="fecha-hoy"></span>
    </div>
    <div class="page-body">

        <div class="stats-row cols-3">
            <div class="stat-card bg-blue">
                <span class="stat-ico">💵</span>
                <div><div class="stat-val"><?= fmt_money($base) ?></div><div class="stat-lbl">Base del día</div></div>
            </div>
            <div class="stat-card bg-green">
                <span class="stat-ico">📈</span>
                <div><div class="stat-val"><?= fmt_money($ingresos) ?></div><div class="stat-lbl">Ingresos del día</div></div>
            </div>
            <div class="stat-card bg-amber">
                <span class="stat-ico">🏦</span>
                <div><div class="stat-val"><?= fmt_money($total_caja) ?></div><div class="stat-lbl">Total en caja</div></div>
            </div>
        </div>

        <!-- Base del día -->
        <div class="card">
            <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap">
                <span style="font-size:13px;font-weight:700">💵 Base del día</span>
                <input type="number" id="inp-base" class="form-control" style="width:180px;height:40px"
                       placeholder="Ej: 50000"
                       value="<?= $base ? number_format($base, 0, '', '') : '' ?>">
                <span id="base-err" class="text-red" style="font-size:12px"></span>
                <button class="btn btn-amber" onclick="guardarBase()">Guardar base</button>
            </div>
        </div>

        <!-- Ventas de hoy -->
        <div class="page-title-wrap">
            <div class="page-title">Ventas de hoy</div>
        </div>

        <?php
        $ventas_hoy_arr = array_values(array_reverse(array_filter($ventas, fn($v) => ($v['fecha'] ?? '') === $hoy)));
        if (empty($ventas_hoy_arr)):
        ?>
        <div class="empty-state">
            <div class="es-ico">💰</div>
            No hay ventas registradas hoy.
        </div>
        <?php else: ?>
        <?php foreach ($ventas_hoy_arr as $v):
            $resumen    = implode(', ', array_map(fn($it) => $it['nombre'].' x'.$it['cantidad'], $v['items']));
            $medio      = $v['medio_pago'] ?? 'Efectivo';
            $es_abono   = str_contains($medio, '(abono)');
            $medio_base = $es_abono ? str_replace(' (abono)', '', $medio) : $medio;
            $ico_m   = match($medio_base) { 'Efectivo' => '💵', 'Transferencia' => '📱', 'Crédito' => '📋', default => '💳' };
            $cls_m   = match($medio_base) { 'Efectivo' => 'badge-green', 'Transferencia' => 'badge-blue', default => 'badge-purple' };
        ?>
        <div class="row-item">
            <div style="min-width:80px">
                <div style="font-size:12px;font-weight:700;color:var(--slate)"><?= htmlspecialchars($v['hora']) ?></div>
            </div>
            <div class="ri-main">
                <div class="ri-name" style="font-weight:400"><?= htmlspecialchars($resumen) ?></div>
                <?php if ($es_abono): ?>
                <div style="font-size:11px;color:var(--amber);font-weight:700;margin-top:2px">⚡ Abono parcial</div>
                <?php endif; ?>
            </div>
            <span class="badge <?= $cls_m ?>"><?= $ico_m ?> <?= htmlspecialchars($medio_base) ?></span>
            <div style="font-size:13px;font-weight:700;color:var(--green);min-width:100px;text-align:right">
                <?= fmt_money($v['total']) ?>
                <?php if ($es_abono): ?>
                <div style="font-size:11px;font-weight:400;color:var(--amber)">abonado</div>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
</div>

<script>
async function guardarBase() {
    const val = parseFloat(document.getElementById('inp-base').value) || 0;
    const err = document.getElementById('base-err');
    if (val < 0) { err.textContent = 'Valor inválido.'; return; }
    err.textContent = '';
    const fd = new FormData();
    fd.append('action', 'guardar_base');
    fd.append('base', val);
    const r = await fetch(BASE_URL + '/api.php', {method:'POST', body:fd});
    const d = await r.json();
    if (d.ok) location.reload();
}
const d=new Date();
const dias=['Domingo','Lunes','Martes','Miércoles','Jueves','Viernes','Sábado'];
const meses=['enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre'];
document.getElementById('fecha-hoy').textContent=`${dias[d.getDay()]} ${d.getDate()} de ${meses[d.getMonth()]} de ${d.getFullYear()}`;
</script>
</body>
</html>
