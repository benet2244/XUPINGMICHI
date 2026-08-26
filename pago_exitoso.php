<?php
require_once 'config/db.php';
$db = getDB();

$pedido_id  = intval($_GET['pedido'] ?? 0);
$referencia = $_GET['ref'] ?? '';

if (!$pedido_id) { header('Location: index.php'); exit; }

$stmt = $db->prepare("SELECT * FROM pedidos WHERE id = ?");
$stmt->bind_param("i", $pedido_id);
$stmt->execute();
$pedido = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$pedido) { header('Location: index.php'); exit; }

$stmt = $db->prepare("SELECT * FROM pedido_items WHERE pedido_id = ?");
$stmt->bind_param("i", $pedido_id);
$stmt->execute();
$items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>¡Apartado Exitoso! — MICHIXUPING Joyería</title>
    <link rel="stylesheet" href="css/main.css">
</head>
<body>
<header class="site-header">
    <nav class="nav-container">
        <a href="index.php" class="logo">
            <img src="uploads/logo.svg" alt="MichiXUPING" style="height: 38px; width: auto;">
        </a>
    </nav>
</header>

<div class="success-container">
    <div class="success-icon">✅</div>
    <h1 class="success-title">¡Apartado Exitoso!</h1>
    <p style="color: var(--text-muted); font-size: 1rem;">
        Gracias <strong style="color: var(--text);"><?= htmlspecialchars($pedido['nombre_cliente']) ?></strong>, 
        tu apartado ha sido registrado y está <strong>pendiente de verificación</strong>.
    </p>
    <div style="margin: 0.5rem 0; color: var(--text-muted); font-size: 0.875rem;">
        Se envió confirmación a <strong style="color: var(--text);"><?= htmlspecialchars($pedido['email']) ?></strong>
    </div>

    <div class="order-code">
        Pedido #<?= str_pad($pedido_id, 6, '0', STR_PAD_LEFT) ?>
    </div>

    <div style="background: var(--dark-2); border: 1px solid var(--glass-border); border-radius: var(--radius); padding: 1.5rem; text-align:left; margin-top: 1rem;">
        <h3 style="font-family: 'Playfair Display', serif; font-size: 1rem; margin-bottom: 1rem; color: var(--text);">Resumen del pedido</h3>
        <?php foreach ($items as $item): ?>
        <div style="display:flex; justify-content:space-between; padding: 0.5rem 0; border-bottom: 1px solid var(--glass-border); font-size:0.875rem;">
            <span style="color: var(--text-muted);"><?= htmlspecialchars($item['nombre_producto']) ?> × <?= $item['cantidad'] ?></span>
            <span style="color: var(--gold);">Q<?= number_format($item['precio_unitario'] * $item['cantidad'], 2) ?></span>
        </div>
        <?php endforeach; ?>
        <div style="display:flex; justify-content:space-between; padding: 1rem 0 0; font-weight:700; font-size: 1.1rem;">
            <span style="color: var(--text);">Total a pagar</span>
            <span style="color: var(--gold);">Q<?= number_format($pedido['total'], 2) ?></span>
        </div>
    </div>

    <div style="margin-top: 1.5rem; padding: 1rem; background: rgba(74,222,128,0.05); border: 1px solid rgba(74,222,128,0.2); border-radius: var(--radius-sm); font-size: 0.8rem; color: var(--text-muted);">
        <strong style="color: var(--success);">Ref. de apartado:</strong> <?= htmlspecialchars($pedido['referencia_pago'] ?? 'N/A') ?><br>
        <strong style="color: var(--success);">Método:</strong> <?= $pedido['metodo_pago'] === 'apartado' ? '📱 Apartado por WhatsApp' : '💳 Otro' ?>
    </div>

    <div style="display: flex; gap: 1rem; margin-top: 2.5rem; flex-wrap: wrap; justify-content:center;">
        <a href="index.php" class="btn btn-primary">💎 Seguir comprando</a>
        <button onclick="window.print()" class="btn btn-outline">🖨️ Imprimir recibo</button>
    </div>
</div>

<footer class="site-footer">
    <div class="footer-bottom" style="max-width:600px; margin: 0 auto; border-top: 1px solid var(--glass-border); padding: 1.5rem 2rem; display:flex; justify-content: space-between;">
        <span>© 2026 MICHIXUPING Joyería</span>
        <span>Hecho con 💎 en Guatemala</span>
    </div>
</footer>
</body>
</html>
