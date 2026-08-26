<?php
require_once '../config/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

$db = getDB();
$cart_session = $_SESSION['cart_session'];
$metodo_pago  = trim($_POST['metodo_pago'] ?? 'tarjeta');
$nombre       = trim($_POST['nombre'] ?? '');
$email        = trim($_POST['email'] ?? '');
$telefono     = trim($_POST['telefono'] ?? '');
$direccion    = trim($_POST['direccion'] ?? '');

// Validaciones básicas
if (empty($nombre) || empty($email)) {
    echo json_encode(['success' => false, 'message' => 'Nombre y email son obligatorios']);
    exit;
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Email inválido']);
    exit;
}

// Obtener items del carrito
$stmt = $db->prepare("
    SELECT c.id, c.cantidad, p.id as producto_id, p.nombre, p.precio, p.disponible
    FROM carrito c
    JOIN productos p ON c.producto_id = p.id
    WHERE c.session_id = ?
");
$stmt->bind_param("s", $cart_session);
$stmt->execute();
$items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

if (empty($items)) {
    echo json_encode(['success' => false, 'message' => 'Tu carrito está vacío']);
    exit;
}

// Calcular total
$total = 0;
foreach ($items as $item) {
    $total += $item['precio'] * $item['cantidad'];
}

// Simulación de procesamiento
$referencia = '';
if ($metodo_pago === 'apartado') {
    $referencia = 'APT-' . strtoupper(substr(md5(uniqid()), 0, 8));
} else {
    // Si mandan otra cosa por error
    $metodo_pago = 'apartado';
    $referencia = 'APT-' . strtoupper(substr(md5(uniqid()), 0, 8));
}

// Insertar pedido en BD
$db->begin_transaction();
try {
    $stmt = $db->prepare("
        INSERT INTO pedidos (session_id, nombre_cliente, email, telefono, direccion, total, estado, metodo_pago, referencia_pago)
        VALUES (?, ?, ?, ?, ?, ?, 'pendiente', ?, ?)
    ");
    $stmt->bind_param("sssssdss", $cart_session, $nombre, $email, $telefono, $direccion, $total, $metodo_pago, $referencia);
    $stmt->execute();
    $pedido_id = $db->insert_id;
    $stmt->close();

    // Insertar items del pedido
    foreach ($items as $item) {
        $stmt = $db->prepare("
            INSERT INTO pedido_items (pedido_id, producto_id, nombre_producto, cantidad, precio_unitario)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("iisid", $pedido_id, $item['producto_id'], $item['nombre'], $item['cantidad'], $item['precio']);
        $stmt->execute();
        $stmt->close();
    }

    // Vaciar carrito
    $stmt = $db->prepare("DELETE FROM carrito WHERE session_id = ?");
    $stmt->bind_param("s", $cart_session);
    $stmt->execute();
    $stmt->close();

    $db->commit();

    echo json_encode([
        'success'    => true,
        'pedido_id'  => $pedido_id,
        'referencia' => $referencia,
        'total'      => number_format($total, 2),
        'message'    => '¡Apartado procesado exitosamente!'
    ]);
} catch (Exception $e) {
    $db->rollback();
    echo json_encode(['success' => false, 'message' => 'Error al procesar el pago. Intente nuevamente.']);
}
?>
