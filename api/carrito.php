<?php
require_once '../config/db.php';

// Solo procesar peticiones POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('HTTP/1.1 405 Method Not Allowed');
    exit;
}

$db = getDB();
$action = $_POST['action'] ?? '';
$cart_session = $_SESSION['cart_session'];

header('Content-Type: application/json');

switch ($action) {
    // ─── Agregar al carrito ───────────────────────────
    case 'add':
        $producto_id = intval($_POST['producto_id'] ?? 0);
        if ($producto_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Producto inválido']);
            exit;
        }

        // Verificar que el producto existe y está disponible
        $stmt = $db->prepare("SELECT id, nombre, precio, disponible FROM productos WHERE id = ?");
        $stmt->bind_param("i", $producto_id);
        $stmt->execute();
        $producto = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$producto || !$producto['disponible']) {
            echo json_encode(['success' => false, 'message' => 'Producto no disponible']);
            exit;
        }

        // Ver si ya está en el carrito
        $stmt = $db->prepare("SELECT id, cantidad FROM carrito WHERE session_id = ? AND producto_id = ?");
        $stmt->bind_param("si", $cart_session, $producto_id);
        $stmt->execute();
        $existing = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($existing) {
            // Incrementar cantidad
            $stmt = $db->prepare("UPDATE carrito SET cantidad = cantidad + 1 WHERE id = ?");
            $stmt->bind_param("i", $existing['id']);
            $stmt->execute();
            $stmt->close();
        } else {
            // Insertar nuevo item
            $stmt = $db->prepare("INSERT INTO carrito (session_id, producto_id, cantidad) VALUES (?, ?, 1)");
            $stmt->bind_param("si", $cart_session, $producto_id);
            $stmt->execute();
            $stmt->close();
        }

        // Contar total de items
        $count = getCartCount($db, $cart_session);
        echo json_encode(['success' => true, 'message' => '¡Agregado al carrito!', 'cart_count' => $count]);
        break;

    // ─── Actualizar cantidad ──────────────────────────
    case 'update':
        $item_id   = intval($_POST['item_id'] ?? 0);
        $cantidad  = intval($_POST['cantidad'] ?? 1);

        if ($cantidad < 1) $cantidad = 1;
        if ($cantidad > 99) $cantidad = 99;

        $stmt = $db->prepare("UPDATE carrito SET cantidad = ? WHERE id = ? AND session_id = ?");
        $stmt->bind_param("iis", $cantidad, $item_id, $cart_session);
        $stmt->execute();
        $stmt->close();

        $count = getCartCount($db, $cart_session);
        $total = getCartTotal($db, $cart_session);
        echo json_encode(['success' => true, 'cart_count' => $count, 'cart_total' => number_format($total, 2)]);
        break;

    // ─── Eliminar item ────────────────────────────────
    case 'remove':
        $item_id = intval($_POST['item_id'] ?? 0);
        $stmt = $db->prepare("DELETE FROM carrito WHERE id = ? AND session_id = ?");
        $stmt->bind_param("is", $item_id, $cart_session);
        $stmt->execute();
        $stmt->close();

        $count = getCartCount($db, $cart_session);
        $total = getCartTotal($db, $cart_session);
        echo json_encode(['success' => true, 'message' => 'Eliminado del carrito', 'cart_count' => $count, 'cart_total' => number_format($total, 2)]);
        break;

    // ─── Vaciar carrito ───────────────────────────────
    case 'clear':
        $stmt = $db->prepare("DELETE FROM carrito WHERE session_id = ?");
        $stmt->bind_param("s", $cart_session);
        $stmt->execute();
        $stmt->close();
        echo json_encode(['success' => true, 'cart_count' => 0]);
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Acción no reconocida']);
}

// ─── Funciones helper ─────────────────────────────────
function getCartCount($db, $session_id) {
    $stmt = $db->prepare("SELECT COALESCE(SUM(cantidad), 0) as total FROM carrito WHERE session_id = ?");
    $stmt->bind_param("s", $session_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return (int)$result['total'];
}

function getCartTotal($db, $session_id) {
    $stmt = $db->prepare("
        SELECT COALESCE(SUM(c.cantidad * p.precio), 0) as total
        FROM carrito c
        JOIN productos p ON c.producto_id = p.id
        WHERE c.session_id = ?
    ");
    $stmt->bind_param("s", $session_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return (float)$result['total'];
}
?>
