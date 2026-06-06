<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/data.php';

header('Content-Type: application/json');

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {

    case 'get_productos':
        echo json_encode(leer_productos());
        break;

    case 'cobrar':
        $items = json_decode($_POST['items'] ?? '[]', true);
        $total = (float)($_POST['total'] ?? 0);
        $medio = in_array($_POST['medio'] ?? '', ['Efectivo', 'Transferencia'])
                 ? $_POST['medio'] : 'Efectivo';

        if (empty($items) || $total <= 0) {
            echo json_encode(['ok' => false, 'msg' => 'Carrito vacío.']);
            break;
        }

        foreach ($items as $item) {
            ajustar_stock_por_nombre($item['nombre'], -(int)$item['cantidad']);
        }

        $ahora = new DateTime();
        guardar_venta([
            'fecha'      => $ahora->format('Y-m-d'),
            'hora'       => $ahora->format('H:i:s'),
            'medio_pago' => $medio,
            'items'      => array_map(fn($it) => [
                'nombre'   => $it['nombre'],
                'cantidad' => (int)$it['cantidad'],
                'precio'   => (float)$it['precio'],
                'subtotal' => (float)$it['precio'] * (int)$it['cantidad'],
            ], $items),
            'total' => $total,
        ]);

        echo json_encode(['ok' => true]);
        break;

    case 'ajustar_stock':
        $nombre = trim($_POST['nombre'] ?? '');
        $delta  = (int)($_POST['delta'] ?? 0);
        if (!$nombre) { echo json_encode(['ok' => false]); break; }
        ajustar_stock_por_nombre($nombre, $delta);
        echo json_encode(['ok' => true]);
        break;

    case 'guardar_compra':
        $fecha     = trim($_POST['fecha']     ?? '');
        $proveedor = trim($_POST['proveedor'] ?? '');
        $items     = json_decode($_POST['items'] ?? '[]', true);
        $total     = (float)($_POST['total']  ?? 0);
        $notas     = trim($_POST['notas']     ?? '');

        if (!$fecha || !$proveedor || empty($items) || $total <= 0) {
            echo json_encode(['ok' => false, 'msg' => 'Datos incompletos.']);
            break;
        }

        guardar_compra([
            'fecha'     => $fecha,
            'proveedor' => $proveedor,
            'items'     => $items,
            'total'     => $total,
            'notas'     => $notas,
        ]);
        echo json_encode(['ok' => true]);
        break;

    case 'eliminar_compra':
        $id = (int)($_POST['id'] ?? 0);
        eliminar_compra($id);
        echo json_encode(['ok' => true]);
        break;

    case 'guardar_base':
        $base = (float)($_POST['base'] ?? 0);
        if ($base < 0) { echo json_encode(['ok' => false, 'msg' => 'Valor inválido.']); break; }
        guardar_caja($base);
        echo json_encode(['ok' => true]);
        break;

    default:
        echo json_encode(['ok' => false, 'msg' => 'Acción desconocida.']);
}
