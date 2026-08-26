<?php
require_once 'config/db.php';
$db = getDB();

$cart_session = $_SESSION['cart_session'];

// Obtener items del carrito
$stmt = $db->prepare("
    SELECT c.id as cart_id, c.cantidad, p.id as producto_id, p.nombre, p.precio, p.imagen
    FROM carrito c
    JOIN productos p ON c.producto_id = p.id
    WHERE c.session_id = ?
");
$stmt->bind_param("s", $cart_session);
$stmt->execute();
$items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

if (empty($items)) { header('Location: carrito.php'); exit; }

$total = array_sum(array_map(fn($i) => $i['precio'] * $i['cantidad'], $items));
$cart_count = array_sum(array_column($items, 'cantidad'));
$categorias = $db->query("SELECT * FROM categorias WHERE activo=1 ORDER BY orden")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout — XUPING Joyería</title>
    <meta name="description" content="Completa tu compra de joyería XUPING de forma segura.">
    <link rel="stylesheet" href="css/main.css">
    <style>
        .secure-badge {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 0.75rem;
            color: var(--text-dim);
            margin-top: 1rem;
            justify-content: center;
        }
        .checkout-items { margin-bottom: 1.5rem; }
        .checkout-item { display: flex; gap: 0.75rem; align-items: center; padding: 0.75rem 0; border-bottom: 1px solid var(--glass-border); }
        .checkout-item:last-child { border-bottom: none; }
        .checkout-item-img { width: 50px; height: 50px; background: var(--dark-3); border-radius: 8px; display:flex; align-items:center; justify-content:center; font-size: 1.5rem; flex-shrink:0; overflow:hidden; }
        .checkout-item-img img { width:100%; height:100%; object-fit:cover; }
        .checkout-item-info { flex:1; }
        .checkout-item-name { font-size: 0.875rem; font-weight:500; color: var(--text); }
        .checkout-item-qty { font-size: 0.75rem; color: var(--text-dim); }
        .checkout-item-price { font-size: 0.95rem; font-weight:700; color: var(--gold); }
        .visa-link-box {
            display: none;
            padding: 1rem;
            background: linear-gradient(135deg, rgba(201,168,76,0.1), rgba(201,168,76,0.05));
            border: 1px solid rgba(201,168,76,0.3);
            border-radius: var(--radius-sm);
            margin-top: 0.5rem;
            text-align: center;
        }
        .visa-link-box.visible { display: block; }
        .card-number-input { letter-spacing: 0.15em; }
    </style>
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
                <span class="cart-badge" id="cart-badge"><?= $cart_count ?></span>
            </a>
        </div>
    </nav>
</header>

<main class="page-container">
    <h1 class="page-title">💳 Finalizar Compra</h1>

    <form id="checkout-form" novalidate>
    <div class="checkout-layout">

        <!-- COLUMNA IZQUIERDA -->
        <div style="display: flex; flex-direction: column; gap: 1.5rem;">

            <!-- Datos del cliente -->
            <div class="form-section">
                <h3>👤 Datos del Cliente</h3>
                <div class="form-row">
                    <div class="form-group">
                        <label for="nombre">Nombre completo *</label>
                        <input type="text" id="nombre" name="nombre" class="form-control" required placeholder="Tu nombre">
                    </div>
                    <div class="form-group">
                        <label for="email">Correo electrónico *</label>
                        <input type="email" id="email" name="email" class="form-control" required placeholder="tu@email.com">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="telefono">Teléfono</label>
                        <input type="tel" id="telefono" name="telefono" class="form-control" placeholder="+502 0000-0000">
                    </div>
                    <div class="form-group">
                        <label for="direccion">Dirección de entrega</label>
                        <input type="text" id="direccion" name="direccion" class="form-control" placeholder="Zona, calle, número">
                    </div>
                </div>
            </div>

            <!-- Método de pago -->
            <div class="form-section">
                <h3>💳 Método de Pago</h3>
                    <!-- Apartado (Reserva) -->
                    <label class="payment-option selected" id="opt-apartado" for="pay-apartado">
                        <input type="radio" id="pay-apartado" name="metodo_pago" value="apartado" checked>
                        <span class="payment-icon">📱</span>
                        <div class="payment-label">
                            <strong>Apartar por WhatsApp</strong>
                            <small>Reserva tu joya y coordina el pago y entrega por WhatsApp</small>
                        </div>
                    </label>

                    <div style="padding: 1rem; background: var(--dark); border-radius: var(--radius-sm); border: 1px solid var(--glass-border); margin-top: 0.5rem; text-align: center;">
                        <div style="font-size: 1.5rem; margin-bottom: 0.5rem;">🛍️</div>
                        <p style="font-size: 0.875rem; color: var(--text-muted);">
                            Al confirmar, tu pedido quedará en estado <strong>Pendiente de verificación</strong>.<br>
                            El administrador revisará el apartado (primer llegado, primer servido) y la venta se completará vía WhatsApp.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- COLUMNA DERECHA — Resumen -->
        <aside class="order-summary">
            <h3>📋 Tu Pedido</h3>

            <div class="checkout-items">
                <?php foreach ($items as $item): ?>
                <div class="checkout-item">
                    <div class="checkout-item-img">
                        <?php if ($item['imagen'] && file_exists('uploads/productos/' . $item['imagen'])): ?>
                            <img src="uploads/productos/<?= htmlspecialchars($item['imagen']) ?>" alt="<?= htmlspecialchars($item['nombre']) ?>">
                        <?php else: ?>
                            💎
                        <?php endif; ?>
                    </div>
                    <div class="checkout-item-info">
                        <div class="checkout-item-name"><?= htmlspecialchars($item['nombre']) ?></div>
                        <div class="checkout-item-qty">Cantidad: <?= $item['cantidad'] ?></div>
                    </div>
                    <div class="checkout-item-price">Q<?= number_format($item['precio'] * $item['cantidad'], 2) ?></div>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="summary-row">
                <span>Subtotal (<?= $cart_count ?> artículos)</span>
                <span>Q<?= number_format($total, 2) ?></span>
            </div>
            <div class="summary-row">
                <span>Envío</span>
                <span style="color: var(--success);">Gratis</span>
            </div>
            <div class="summary-total">
                <span>Total</span>
                <span>Q<?= number_format($total, 2) ?></span>
            </div>

            <button type="submit" class="btn-checkout" id="btn-pay">
                🔒 Confirmar y apartar
            </button>

            <div class="secure-badge">🔒 Pago 100% seguro — Cifrado SSL</div>

            <div style="margin-top: 1rem;">
                <a href="carrito.php" class="btn btn-outline" style="width:100%; justify-content:center; margin-top:0.5rem;">
                    ← Volver al Carrito
                </a>
            </div>
        </aside>

    </div>
    </form>
</main>

<div class="toast-container" id="toast-container"></div>

<!-- Modal de procesamiento -->
<div id="processing-modal" style="display:none; position:fixed; inset:0; background:rgba(10,10,15,0.9); z-index:9999; align-items:center; justify-content:center; flex-direction:column; gap:1.5rem; text-align:center;">
    <div class="spinner" style="width:50px; height:50px; border-width:4px;"></div>
    <p style="font-family: 'Playfair Display', serif; font-size: 1.4rem; color: var(--gold);">Procesando tu pago...</p>
    <p style="color: var(--text-muted); font-size: 0.875rem;">Por favor espera, no cierres esta ventana</p>
</div>

<footer class="site-footer">
    <div class="footer-bottom" style="max-width:1100px; margin: 0 auto; border-top: 1px solid var(--glass-border); padding: 1.5rem 2rem; display:flex; justify-content: space-between;">
        <span>© 2026 MICHIXUPING Joyería</span>
        <span>🔒 Pagos seguros</span>
    </div>
</footer>

<script src="js/main.js"></script>
</body>
</html>
