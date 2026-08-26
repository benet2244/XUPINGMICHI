<?php
require_once 'config/db.php';
$db = getDB();

$id = intval($_GET['id'] ?? 0);
if (!$id) { header('Location: index.php'); exit; }

// Obtener producto
$stmt = $db->prepare("
    SELECT p.*, c.nombre as cat_nombre, c.slug as cat_slug, c.icono as cat_icono
    FROM productos p
    LEFT JOIN categorias c ON p.categoria_id = c.id
    WHERE p.id = ?
");
$stmt->bind_param("i", $id);
$stmt->execute();
$prod = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$prod) { header('Location: index.php'); exit; }

// Productos relacionados
$stmt = $db->prepare("
    SELECT p.*, c.icono as cat_icono FROM productos p
    LEFT JOIN categorias c ON p.categoria_id = c.id
    WHERE p.categoria_id = ? AND p.id != ? AND p.disponible = 1
    ORDER BY RAND() LIMIT 4
");
$stmt->bind_param("ii", $prod['categoria_id'], $id);
$stmt->execute();
$relacionados = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Cart count
$cart_session = $_SESSION['cart_session'];
$stmt = $db->prepare("SELECT COALESCE(SUM(cantidad),0) as total FROM carrito WHERE session_id = ?");
$stmt->bind_param("s", $cart_session);
$stmt->execute();
$cart_count = (int)$stmt->get_result()->fetch_assoc()['total'];
$stmt->close();
$categorias = $db->query("SELECT * FROM categorias WHERE activo=1 ORDER BY orden")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($prod['nombre']) ?> — XUPING Joyería</title>
    <meta name="description" content="<?= htmlspecialchars(substr($prod['descripcion'] ?? '', 0, 160)) ?>">
    <link rel="stylesheet" href="css/main.css">
    <style>
        .product-detail { max-width: 1100px; margin: 3rem auto; padding: 0 2rem; }
        .product-detail-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 4rem; align-items: start; }
        .product-detail-img {
            aspect-ratio: 1;
            border-radius: var(--radius);
            overflow: hidden;
            background: var(--dark-2);
            border: 1px solid var(--glass-border);
            display: flex; align-items: center; justify-content: center;
        }
        .product-detail-img img { width: 100%; height: 100%; object-fit: cover; }
        .product-detail-info { padding: 1rem 0; }
        .breadcrumb { display: flex; align-items: center; gap: 0.5rem; font-size: 0.8rem; color: var(--text-dim); margin-bottom: 1.5rem; }
        .breadcrumb a { color: var(--gold); }
        .detail-badge { display: inline-flex; align-items: center; gap: 6px; padding: 0.35rem 0.9rem; border-radius: 20px; font-size: 0.78rem; font-weight: 600; margin-bottom: 1rem; }
        .detail-title { font-family: 'Playfair Display', serif; font-size: 2rem; font-weight: 700; margin-bottom: 1rem; line-height: 1.2; }
        .detail-price { font-size: 2.5rem; font-weight: 700; color: var(--gold); margin-bottom: 1.5rem; }
        .detail-desc { font-size: 0.95rem; color: var(--text-muted); line-height: 1.8; margin-bottom: 2rem; }
        .detail-specs { display: flex; flex-direction: column; gap: 0.75rem; margin-bottom: 2rem; padding: 1.5rem; background: var(--dark-2); border-radius: var(--radius-sm); border: 1px solid var(--glass-border); }
        .spec-row { display: flex; justify-content: space-between; font-size: 0.875rem; }
        .spec-row .spec-label { color: var(--text-dim); }
        .spec-row .spec-val { color: var(--text); font-weight: 500; }
        .btn-detail-cart { width: 100%; font-size: 1rem; padding: 1rem; }
        .related-section { max-width: 1100px; margin: 4rem auto; padding: 0 2rem; }
        @media (max-width: 768px) { .product-detail-grid { grid-template-columns: 1fr; gap: 2rem; } }
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
                <span class="cart-badge <?= $cart_count == 0 ? 'hidden' : '' ?>" id="cart-badge"><?= $cart_count ?></span>
            </a>
        </div>
    </nav>
</header>

<div class="product-detail">
    <!-- Breadcrumb -->
    <nav class="breadcrumb">
        <a href="index.php">Inicio</a> /
        <a href="index.php?categoria=<?= htmlspecialchars($prod['cat_slug'] ?? '') ?>">
            <?= htmlspecialchars($prod['cat_nombre'] ?? 'Categoría') ?>
        </a> /
        <span><?= htmlspecialchars($prod['nombre']) ?></span>
    </nav>

    <div class="product-detail-grid">
        <!-- Imagen -->
        <div class="product-detail-img">
            <?php if ($prod['imagen'] && file_exists('uploads/productos/' . $prod['imagen'])): ?>
                <img src="uploads/productos/<?= htmlspecialchars($prod['imagen']) ?>"
                     alt="<?= htmlspecialchars($prod['nombre']) ?>">
            <?php else: ?>
                <div style="font-size: 6rem; color: var(--gold-dark); text-align:center;">
                    <?= htmlspecialchars($prod['cat_icono'] ?? '💎') ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Información -->
        <div class="product-detail-info">
            <span class="detail-badge <?= $prod['disponible'] ? 'badge-available' : 'badge-unavailable' ?>">
                <?= $prod['disponible'] ? '● Disponible' : '● No disponible' ?>
            </span>
            <?php if ($prod['destacado']): ?>
            <span class="detail-badge" style="margin-left:0.5rem; background: rgba(201,168,76,0.1); color: var(--gold); border: 1px solid rgba(201,168,76,0.3);">⭐ Destacado</span>
            <?php endif; ?>

            <h1 class="detail-title"><?= htmlspecialchars($prod['nombre']) ?></h1>
            <div class="detail-price">Q<?= number_format($prod['precio'], 2) ?> <span style="font-size:1rem; color:var(--text-dim); font-weight:400;">GTQ</span></div>

            <?php if ($prod['descripcion']): ?>
            <p class="detail-desc"><?= nl2br(htmlspecialchars($prod['descripcion'])) ?></p>
            <?php endif; ?>

            <!-- Especificaciones -->
            <div class="detail-specs">
                <?php if ($prod['material']): ?>
                <div class="spec-row"><span class="spec-label">Material</span><span class="spec-val"><?= htmlspecialchars($prod['material']) ?></span></div>
                <?php endif; ?>
                <?php if ($prod['peso']): ?>
                <div class="spec-row"><span class="spec-label">Peso</span><span class="spec-val"><?= htmlspecialchars($prod['peso']) ?></span></div>
                <?php endif; ?>
                <div class="spec-row"><span class="spec-label">Categoría</span><span class="spec-val"><?= htmlspecialchars($prod['cat_nombre'] ?? '-') ?></span></div>
                <div class="spec-row"><span class="spec-label">Ref.</span><span class="spec-val">#<?= str_pad($prod['id'], 4, '0', STR_PAD_LEFT) ?></span></div>
            </div>

            <button class="btn-add-cart btn-detail-cart"
                    id="add-cart-<?= $prod['id'] ?>"
                    <?= !$prod['disponible'] ? 'disabled' : '' ?>
                    onclick="addToCart(<?= $prod['id'] ?>, this)">
                <?= $prod['disponible'] ? '🛒 Agregar al Carrito' : '🚫 No disponible' ?>
            </button>

            <div style="margin-top: 1rem; display: flex; gap: 0.75rem;">
                <a href="carrito.php" class="btn btn-outline" style="flex:1; justify-content:center;">Ver Carrito 🛒</a>
                <a href="index.php?categoria=<?= htmlspecialchars($prod['cat_slug'] ?? '') ?>" class="btn btn-outline" style="flex:1; justify-content:center;">← Volver</a>
            </div>
        </div>
    </div>
</div>

<!-- Productos relacionados -->
<?php if (!empty($relacionados)): ?>
<section class="related-section">
    <div class="section-header">
        <h2 class="section-title">✨ También te puede gustar</h2>
    </div>
    <div class="products-grid">
        <?php foreach ($relacionados as $rel): ?>
        <article class="product-card" onclick="window.location='producto.php?id=<?= $rel['id'] ?>'">
            <div class="product-img-wrap">
                <?php if ($rel['imagen'] && file_exists('uploads/productos/' . $rel['imagen'])): ?>
                    <img src="uploads/productos/<?= htmlspecialchars($rel['imagen']) ?>" alt="<?= htmlspecialchars($rel['nombre']) ?>" loading="lazy">
                <?php else: ?>
                    <div class="product-img-placeholder"><?= htmlspecialchars($rel['cat_icono'] ?? '💎') ?></div>
                <?php endif; ?>
                <span class="product-badge badge-available">● Disponible</span>
            </div>
            <div class="product-info">
                <h3 class="product-name"><?= htmlspecialchars($rel['nombre']) ?></h3>
                <div class="product-price">Q<?= number_format($rel['precio'], 2) ?></div>
                <button class="btn-add-cart" onclick="event.stopPropagation(); addToCart(<?= $rel['id'] ?>, this)">🛒 Agregar</button>
            </div>
        </article>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<footer class="site-footer">
    <div class="footer-bottom" style="max-width:1100px; margin: 0 auto; border-top: 1px solid var(--glass-border); padding: 1.5rem 2rem; display:flex; justify-content: space-between;">
        <span>© 2026 XUPING Joyería</span>
        <span>Hecho con 💎 en Guatemala</span>
    </div>
</footer>

<div class="toast-container" id="toast-container"></div>
<script src="js/main.js"></script>
</body>
</html>
