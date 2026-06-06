<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/data.php';

$productos = leer_productos();
$msg       = '';
$msg_type  = '';
$editando  = null;
$edit_id   = -1;

if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $edit_id  = (int)$_GET['edit'];
    $editando = buscar_producto_por_id($edit_id);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';

    if ($accion === 'eliminar') {
        eliminar_producto((int)$_POST['idx']);
        header('Location: ' . BASE_URL . '/productos.php?ok=eliminado');
        exit;
    }

    if (in_array($accion, ['agregar', 'editar'])) {
        $nombre   = trim($_POST['nombre'] ?? '');
        $precio_s = str_replace(',', '.', trim($_POST['precio'] ?? ''));
        $stock_s  = trim($_POST['stock'] ?? '0');
        $err = '';

        if (!$nombre) $err = 'El nombre es requerido.';
        elseif (!is_numeric($precio_s) || (float)$precio_s < 0) $err = 'Precio inválido.';
        elseif ($stock_s !== '' && (!ctype_digit($stock_s) || (int)$stock_s < 0)) $err = 'Stock inválido.';

        if (!$err) {
            $id_edit  = (int)($_POST['idx_edit'] ?? 0);
            $existing = $id_edit ? buscar_producto_por_id($id_edit) : null;
            $img_path = $existing['img_path'] ?? null;

            if (!empty($_FILES['imagen']['name'])) {
                $ext   = strtolower(pathinfo($_FILES['imagen']['name'], PATHINFO_EXTENSION));
                $allow = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                if (in_array($ext, $allow)) {
                    $fname = uniqid('prod_') . '.' . $ext;
                    if (move_uploaded_file($_FILES['imagen']['tmp_name'], UPLOADS_DIR . $fname)) {
                        $img_path = 'uploads/' . $fname;
                    }
                }
            }

            $prod = [
                'nombre'   => $nombre,
                'precio'   => (float)$precio_s,
                'img_path' => $img_path,
                'stock'    => (int)$stock_s,
            ];

            if ($accion === 'editar') {
                actualizar_producto($id_edit, $prod);
            } else {
                agregar_producto($prod);
            }
            header('Location: ' . BASE_URL . '/productos.php?ok=guardado');
            exit;
        }
        $msg = $err; $msg_type = 'red';
    }
}

$ok = $_GET['ok'] ?? '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>CafePro – Productos</title>
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/style.css">
</head>
<body>
<div class="app">
<?php require __DIR__ . '/includes/header.php'; ?>
<div class="main-content">
    <div class="topbar">
        <span class="topbar-title">🍴 Productos</span>
        <span class="topbar-date" id="fecha-hoy"></span>
    </div>

    <div class="split-layout">
        <!-- Formulario -->
        <div class="split-form">
            <div class="form-section-title"><?= $editando ? 'Editar Producto' : 'Nuevo Producto' ?></div>

            <?php if ($msg): ?>
            <div class="alert alert-<?= $msg_type ?>"><?= htmlspecialchars($msg) ?></div>
            <?php endif; ?>
            <?php if ($ok): ?>
            <div class="alert alert-green">✓ Producto <?= $ok === 'eliminado' ? 'eliminado' : 'guardado' ?> correctamente.</div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="accion" value="<?= $editando ? 'editar' : 'agregar' ?>">
                <?php if ($editando): ?>
                <input type="hidden" name="idx_edit" value="<?= $editando['id'] ?>">
                <?php endif; ?>

                <div class="form-group">
                    <label class="form-label">IMAGEN</label>
                    <div class="img-preview" id="img-preview">
                        <?php
                        $cur_url = $editando ? img_url($editando['img_path'] ?? null) : null;
                        if ($cur_url): ?>
                        <img src="<?= htmlspecialchars($cur_url) ?>" id="preview-img">
                        <?php else: ?>
                        <span id="preview-text">Sin imagen seleccionada</span>
                        <?php endif; ?>
                    </div>
                    <label class="btn btn-ghost btn-full" style="cursor:pointer">
                        📁 Elegir imagen
                        <input type="file" name="imagen" accept="image/*" style="display:none" onchange="previewImg(this)">
                    </label>
                </div>

                <div class="form-group">
                    <label class="form-label">NOMBRE DEL PRODUCTO</label>
                    <input class="form-control" type="text" name="nombre"
                           placeholder="Ej: Café Americano"
                           value="<?= htmlspecialchars($editando['nombre'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label class="form-label">PRECIO ($)</label>
                    <input class="form-control" type="number" name="precio" step="0.01" min="0"
                           placeholder="Ej: 2500"
                           value="<?= $editando ? ((int)$editando['precio'] == $editando['precio'] ? (int)$editando['precio'] : $editando['precio']) : '' ?>">
                </div>

                <div class="form-group">
                    <label class="form-label">STOCK INICIAL</label>
                    <input class="form-control" type="number" name="stock" min="0" step="1"
                           placeholder="Ej: 20"
                           value="<?= htmlspecialchars((string)($editando['stock'] ?? '')) ?>">
                </div>

                <button class="btn <?= $editando ? 'btn-green' : 'btn-amber' ?> btn-full btn-lg">
                    <?= $editando ? '💾 Guardar Cambios' : '+ Agregar Producto' ?>
                </button>
                <?php if ($editando): ?>
                <a href="<?= BASE_URL ?>/productos.php" class="btn btn-ghost btn-full mt-8">✕ Cancelar edición</a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Catálogo -->
        <div class="split-list">
            <div class="page-title-wrap">
                <div class="page-title">Catálogo de Productos</div>
            </div>

            <?php if (empty($productos)): ?>
            <div class="empty-state">
                <div class="es-ico">🍽️</div>
                No hay productos registrados.<br>Agrega el primero desde el panel izquierdo.
            </div>
            <?php else: ?>
            <div class="product-grid">
            <?php foreach ($productos as $prod):
                $url = img_url($prod['img_path'] ?? null);
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
                        <a href="<?= BASE_URL ?>/productos.php?edit=<?= $prod['id'] ?>" class="btn btn-edit btn-sm">✏️ Editar</a>
                        <form method="POST" style="flex:1" onsubmit="return confirm('¿Eliminar este producto?')">
                            <input type="hidden" name="accion" value="eliminar">
                            <input type="hidden" name="idx" value="<?= $prod['id'] ?>">
                            <button class="btn btn-del btn-sm btn-full" type="submit">🗑 Eliminar</button>
                        </form>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
</div>

<script>
function previewImg(input) {
    if (!input.files || !input.files[0]) return;
    const reader = new FileReader();
    reader.onload = e => {
        document.getElementById('img-preview').innerHTML =
            `<img src="${e.target.result}" style="width:100%;height:100%;object-fit:cover">`;
    };
    reader.readAsDataURL(input.files[0]);
}
const d = new Date();
const dias=['Domingo','Lunes','Martes','Miércoles','Jueves','Viernes','Sábado'];
const meses=['enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre'];
document.getElementById('fecha-hoy').textContent=`${dias[d.getDay()]} ${d.getDate()} de ${meses[d.getMonth()]} de ${d.getFullYear()}`;
</script>
</body>
</html>
