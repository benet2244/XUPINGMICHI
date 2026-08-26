<?php
require_once 'config/db.php';
$db = getDB();

$cart_session = $_SESSION['cart_session'];

// Obtener items del carrito con datos de producto
$stmt = $db->prepare("
    SELECT c.id as cart_id, c.cantidad, p.id as producto_id, p.nombre, p.precio,
           p.imagen, p.disponible, cat.nombre as cat_nombre, cat.icono as cat_icono
    FROM carrito c
    JOIN productos p ON c.producto_id = p.id
    LEFT JOIN categorias cat ON p.categoria_id = cat.id
    WHERE c.session_id = ?
    ORDER BY c.created_at DESC
");
$stmt->bind_param("s", $cart_session);
$stmt->execute();
$items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$total = 0;
foreach ($items as $item) {
    $total += $item['precio'] * $item['cantidad'];
}
$cart_count = array_sum(array_column($items, 'cantidad'));
$categorias = $db->query("SELECT * FROM categorias WHERE activo=1 ORDER BY orden")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Carrito — XUPING Joyería</title>
    <meta name="description" content="Revisa los artículos en tu carrito de compras XUPING.">
    <link rel="stylesheet" href="css/main.css">
</head>
<body>
<header class="site-header">
    <nav class="nav-container">
        <a href="index.php" class="logo">
            <img src="uploads/logo.svg" alt="MichiXUPING" style="height: 38px; width: auto;">
        </a>
        <ul class="nav-links">
            <li><a href="index.php">Inicio</a></li>
            <?php foreach ($categorias as $cat): ?>
            <li><a href="index.php?categoria=<?= $cat['slug'] ?>"><?= $cat['nombre'] ?></a></li>
            <?php endforeach; ?>
        </ul>
        <div class="nav-actions">
            <a href="carrito.php" class="cart-btn">
                🛒 Carrito
                <span class="cart-badge <?= $cart_count == 0 ? 'hidden' : '' ?>" id="cart-badge"><?= $cart_count ?></span>
            </a>
        </div>
    </nav>
</header>

<main class="page-container">
    <h1 class="page-title">🛒 Mi Carrito</h1>

    <?php if (empty($items)): ?>
    <div class="cart-empty">
        <div class="cart-empty-icon">🛒</div>
        <h3 style="font-family: 'Playfair Display', serif; font-size: 1.5rem; margin-bottom: 0.75rem; color: var(--text);">Tu carrito está vacío</h3>
        <p>Explora nuestra colección y agrega las piezas que te enamoren.</p>
        <a href="index.php" class="btn btn-primary" style="margin-top: 2rem;">Ver Colección 💎</a>
    </div>
    <?php else: ?>

    <div class="cart-layout">
        <!-- Items -->
        <div class="cart-items" id="cart-items-container">
            <?php foreach ($items as $item): ?>
            <div class="cart-item" id="cart-item-<?= $item['cart_id'] ?>">
                <!-- Imagen -->
                <div class="cart-item-img">
                    <?php if ($item['imagen'] && file_exists('uploads/productos/' . $item['imagen'])): ?>
                        <img src="uploads/productos/<?= htmlspecialchars($item['imagen']) ?>"
                             alt="<?= htmlspecialchars($item['nombre']) ?>">
                    <?php else: ?>
                        <?= htmlspecialchars($item['cat_icono'] ?? '💎') ?>
                    <?php endif; ?>
                </div>

                <!-- Info -->
                <div class="cart-item-info">
                    <div class="cart-item-cat"><?= htmlspecialchars($item['cat_nombre'] ?? '') ?></div>
                    <div class="cart-item-name"><?= htmlspecialchars($item['nombre']) ?></div>
                    <div class="cart-item-price" id="item-subtotal-<?= $item['cart_id'] ?>">
                        Q<?= number_format($item['precio'] * $item['cantidad'], 2) ?>
                        <span style="font-size: 0.75rem; color: var(--text-dim); font-weight:400;">
                            (Q<?= number_format($item['precio'], 2) ?> c/u)
                        </span>
                    </div>
                </div>

                <!-- Controles de cantidad -->
                <div class="qty-controls">
                    <button class="qty-btn"
                            id="qty-minus-<?= $item['cart_id'] ?>"
                            onclick="updateQty(<?= $item['cart_id'] ?>, <?= $item['cantidad'] - 1 ?>, <?= $item['precio'] ?>)">−</button>
                    <span class="qty-display" id="qty-<?= $item['cart_id'] ?>"><?= $item['cantidad'] ?></span>
                    <button class="qty-btn"
                            id="qty-plus-<?= $item['cart_id'] ?>"
                            onclick="updateQty(<?= $item['cart_id'] ?>, <?= $item['cantidad'] + 1 ?>, <?= $item['precio'] ?>)">+</button>
                </div>

                <!-- Eliminar -->
                <button class="btn-remove"
                        id="remove-<?= $item['cart_id'] ?>"
                        onclick="removeItem(<?= $item['cart_id'] ?>)"
                        title="Eliminar">🗑️</button>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Resumen de orden -->
        <aside class="order-summary">
            <h3>📋 Resumen del Pedido</h3>
            <?php foreach ($items as $item): ?>
            <div class="summary-row">
                <span><?= htmlspecialchars($item['nombre']) ?> × <?= $item['cantidad'] ?></span>
                <span id="summary-item-<?= $item['cart_id'] ?>">Q<?= number_format($item['precio'] * $item['cantidad'], 2) ?></span>
            </div>
            <?php endforeach; ?>

            <div class="summary-total">
                <span>Total</span>
                <span id="cart-total-display">Q<?= number_format($total, 2) ?></span>
            </div>

            <a href="checkout.php" class="btn-checkout" id="btn-checkout">
                💳 Proceder al Pago
            </a>

            <div style="margin-top: 1rem;">
                <a href="index.php" class="btn btn-outline" style="width:100%; justify-content:center;">
                    ← Seguir comprando
                </a>
            </div>

            <button onclick="clearCart()" id="btn-clear-cart"
                    style="width:100%; margin-top:0.75rem; background:none; border:none; color:var(--text-dim); font-size:0.8rem; cursor:pointer; padding:0.5rem;">
                🗑️ Vaciar carrito
            </button>



        </aside>
    </div>

    <?php endif; ?>
</main>

<footer class="site-footer">
    <div class="footer-bottom" style="max-width:1100px; margin: 0 auto; border-top: 1px solid var(--glass-border); padding: 1.5rem 2rem; display:flex; justify-content: space-between;">
        <span>© 2026 XUPING Joyería</span>
        <span>Hecho con 💎 en Guatemala</span>
    </div>
</footer>

<div class="toast-container" id="toast-container"></div>
<script>
const PRODUCT_PRICES = {
    <?php foreach ($items as $i => $item): ?>
    <?= $item['cart_id'] ?>: <?= $item['precio'] ?><?= $i < count($items)-1 ? ',' : '' ?>
    <?php endforeach; ?>
};
</script>
<script src="js/main.js"></script>
</body>
</html>
