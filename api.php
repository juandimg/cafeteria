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
        $items   = json_decode($_POST['items'] ?? '[]', true);
        $total   = (float)($_POST['total'] ?? 0);
        $parcial = !empty($_POST['parcial']);
        $medio_raw = $_POST['medio'] ?? '';
        $medio = in_array($medio_raw, ['Efectivo', 'Transferencia', 'Crédito'])
                 ? $medio_raw : 'Efectivo';
        if ($parcial && in_array($medio, ['Efectivo', 'Transferencia'])) {
            $medio = $medio . ' (abono)';
        }

        if (empty($items) || $total <= 0) {
            echo json_encode(['ok' => false, 'msg' => 'Carrito vacío.']);
            break;
        }

        foreach ($items as $item) {
            ajustar_stock_por_nombre($item['nombre'], -(int)$item['cantidad']);
        }

        if ($medio !== 'Crédito') {
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
        }

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

    case 'eliminar_proveedor':
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) { echo json_encode(['ok' => false, 'msg' => 'ID inválido.']); break; }
        eliminar_proveedor($id);
        echo json_encode(['ok' => true]);
        break;

    case 'agregar_proveedor_rapido':
        $nombre = trim($_POST['nombre'] ?? '');
        if (!$nombre) { echo json_encode(['ok' => false, 'msg' => 'Nombre requerido.']); break; }
        try {
            agregar_proveedor(['nombre' => $nombre, 'contacto' => '', 'telefono' => '', 'email' => '', 'notas' => '']);
        } catch (Exception $e) { /* ya existe, no importa */ }
        echo json_encode(['ok' => true]);
        break;

    case 'agregar_cliente':
        $nombre = trim($_POST['nombre'] ?? '');
        if (!$nombre) { echo json_encode(['ok' => false, 'msg' => 'Nombre requerido.']); break; }
        $id = agregar_cliente(['nombre' => $nombre, 'telefono' => trim($_POST['telefono'] ?? '')]);
        echo json_encode(['ok' => true, 'id' => $id]);
        break;

    case 'eliminar_cliente':
        eliminar_cliente((int)($_POST['id'] ?? 0));
        echo json_encode(['ok' => true]);
        break;

    case 'agregar_movimiento_cliente':
        $cid     = (int)($_POST['cliente_id'] ?? 0);
        $tipo    = in_array($_POST['tipo'] ?? '', ['deuda','abono']) ? $_POST['tipo'] : '';
        $monto   = (float)($_POST['monto'] ?? 0);
        $fecha   = trim($_POST['fecha'] ?? date('Y-m-d'));
        $concepto = trim($_POST['concepto'] ?? '');
        if (!$cid || !$tipo || $monto <= 0) { echo json_encode(['ok' => false, 'msg' => 'Datos inválidos.']); break; }
        agregar_movimiento_cliente($cid, $tipo, $concepto, $monto, $fecha);

        if ($tipo === 'abono') {
            $medio_pago     = in_array($_POST['medio_pago'] ?? '', ['Efectivo','Transferencia']) ? $_POST['medio_pago'] : 'Efectivo';
            $cliente_nombre = trim($_POST['cliente_nombre'] ?? 'Cliente');
            $concepto_venta = 'Abono – ' . $cliente_nombre . ($concepto ? ': ' . $concepto : '');
            guardar_venta([
                'fecha'      => $fecha,
                'hora'       => (new DateTime())->format('H:i:s'),
                'medio_pago' => $medio_pago,
                'total'      => $monto,
                'items'      => [[
                    'nombre'   => $concepto_venta,
                    'cantidad' => 1,
                    'precio'   => $monto,
                    'subtotal' => $monto,
                ]],
            ]);
        }

        echo json_encode(['ok' => true]);
        break;

    case 'eliminar_movimiento_cliente':
        eliminar_movimiento_cliente((int)($_POST['id'] ?? 0));
        echo json_encode(['ok' => true]);
        break;

    case 'get_movimientos_cliente':
        $cid = (int)($_GET['cliente_id'] ?? 0);
        echo json_encode(leer_movimientos_cliente($cid));
        break;

    default:
        echo json_encode(['ok' => false, 'msg' => 'Acción desconocida.']);
}
