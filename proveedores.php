<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/data.php';

$proveedores = leer_proveedores();
$msg         = '';
$msg_type    = '';
$editando    = null;
$edit_id     = -1;

if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $edit_id  = (int)$_GET['edit'];
    foreach ($proveedores as $p) {
        if ((int)$p['id'] === $edit_id) { $editando = $p; break; }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';

    if ($accion === 'eliminar') {
        eliminar_proveedor((int)$_POST['idx']);
        header('Location: ' . BASE_URL . '/proveedores.php?ok=eliminado');
        exit;
    }

    if (in_array($accion, ['agregar', 'editar'])) {
        $nombre = trim($_POST['nombre'] ?? '');
        if (!$nombre) {
            $msg = 'El nombre es requerido.'; $msg_type = 'red';
        } else {
            $prov = [
                'nombre'   => $nombre,
                'contacto' => trim($_POST['contacto'] ?? ''),
                'telefono' => trim($_POST['telefono'] ?? ''),
                'email'    => trim($_POST['email']    ?? ''),
                'notas'    => trim($_POST['notas']    ?? ''),
            ];
            if ($accion === 'editar') {
                actualizar_proveedor((int)$_POST['idx_edit'], $prov);
            } else {
                agregar_proveedor($prov);
            }
            header('Location: ' . BASE_URL . '/proveedores.php?ok=guardado');
            exit;
        }
    }
}
$ok = $_GET['ok'] ?? '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>CafePro – Proveedores</title>
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/style.css">
</head>
<body>
<div class="app">
<?php require __DIR__ . '/includes/header.php'; ?>
<div class="main-content">
    <div class="topbar">
        <span class="topbar-title">🤝 Proveedores</span>
        <span class="topbar-date" id="fecha-hoy"></span>
    </div>

    <div class="split-layout">
        <!-- Formulario -->
        <div class="split-form">
            <div class="form-section-title"><?= $editando ? 'Editar Proveedor' : 'Nuevo Proveedor' ?></div>

            <?php if ($msg): ?>
            <div class="alert alert-<?= $msg_type ?>"><?= htmlspecialchars($msg) ?></div>
            <?php endif; ?>
            <?php if ($ok): ?>
            <div class="alert alert-green">✓ Proveedor <?= $ok === 'eliminado' ? 'eliminado' : 'guardado' ?> correctamente.</div>
            <?php endif; ?>

            <form method="POST">
                <input type="hidden" name="accion" value="<?= $editando ? 'editar' : 'agregar' ?>">
                <?php if ($editando): ?>
                <input type="hidden" name="idx_edit" value="<?= $editando['id'] ?>">
                <?php endif; ?>

                <?php
                $campos = [
                    ['nombre',   'NOMBRE',              'Ej: Distribuidora ABC',  true],
                    ['contacto', 'PERSONA DE CONTACTO', 'Ej: Juan Pérez',         false],
                    ['telefono', 'TELÉFONO',             'Ej: 3001234567',         false],
                    ['email',    'EMAIL',                'Ej: contacto@abc.com',   false],
                    ['notas',    'NOTAS',                'Ej: Entrega los martes', false],
                ];
                foreach ($campos as [$key, $lbl, $ph, $req]): ?>
                <div class="form-group">
                    <label class="form-label"><?= $lbl ?></label>
                    <input class="form-control" type="text" name="<?= $key ?>"
                           placeholder="<?= htmlspecialchars($ph) ?>"
                           value="<?= htmlspecialchars($editando[$key] ?? '') ?>"
                           <?= $req ? 'required' : '' ?>>
                </div>
                <?php endforeach; ?>

                <button class="btn <?= $editando ? 'btn-green' : 'btn-amber' ?> btn-full btn-lg">
                    <?= $editando ? '💾 Guardar Cambios' : '+ Agregar Proveedor' ?>
                </button>
                <?php if ($editando): ?>
                <a href="<?= BASE_URL ?>/proveedores.php" class="btn btn-ghost btn-full mt-8">✕ Cancelar edición</a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Lista -->
        <div class="split-list">
            <div class="page-title-wrap">
                <div class="page-title">Proveedores registrados</div>
            </div>

            <?php if (empty($proveedores)): ?>
            <div class="empty-state">
                <div class="es-ico">🤝</div>
                No hay proveedores registrados.<br>Agrega el primero desde el panel izquierdo.
            </div>
            <?php else: ?>
            <?php foreach ($proveedores as $p): ?>
            <div class="card" style="margin-bottom:14px">
                <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:10px">
                    <strong style="font-size:15px"><?= htmlspecialchars($p['nombre']) ?></strong>
                </div>
                <div style="display:grid;gap:4px;margin-bottom:12px">
                    <?php foreach ([
                        ['👤', $p['contacto'] ?? ''],
                        ['📞', $p['telefono'] ?? ''],
                        ['✉️', $p['email']    ?? ''],
                    ] as [$ico, $val]): ?>
                    <?php if ($val): ?>
                    <div style="display:flex;gap:8px;align-items:center;font-size:12px;color:var(--txt-g)">
                        <span><?= $ico ?></span><span><?= htmlspecialchars($val) ?></span>
                    </div>
                    <?php endif; ?>
                    <?php endforeach; ?>
                    <?php if (!empty($p['notas'])): ?>
                    <div style="font-size:11px;color:#BCAAA4;margin-top:4px">📝 <?= htmlspecialchars($p['notas']) ?></div>
                    <?php endif; ?>
                </div>
                <div style="display:flex;gap:8px">
                    <a href="<?= BASE_URL ?>/proveedores.php?edit=<?= $p['id'] ?>" class="btn btn-edit btn-sm">✏️ Editar</a>
                    <button class="btn btn-del btn-sm" onclick="eliminarProveedor(<?= $p['id'] ?>, '<?= htmlspecialchars(addslashes($p['nombre'])) ?>')">🗑 Eliminar</button>
                </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>
</div>
<script>
const d=new Date();
const dias=['Domingo','Lunes','Martes','Miércoles','Jueves','Viernes','Sábado'];
const meses=['enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre'];
document.getElementById('fecha-hoy').textContent=`${dias[d.getDay()]} ${d.getDate()} de ${meses[d.getMonth()]} de ${d.getFullYear()}`;

async function eliminarProveedor(id, nombre) {
    if (!confirm(`¿Eliminar al proveedor "${nombre}"?\nEsta acción no se puede deshacer.`)) return;
    const fd = new FormData();
    fd.append('action', 'eliminar_proveedor');
    fd.append('id', id);
    try {
        const res  = await fetch(BASE_URL + '/api.php', { method: 'POST', body: fd });
        const data = await res.json();
        if (data.ok) {
            location.reload();
        } else {
            alert('Error al eliminar: ' + (data.msg || 'desconocido'));
        }
    } catch {
        alert('Error de conexión al intentar eliminar.');
    }
}
</script>
</body>
</html>
