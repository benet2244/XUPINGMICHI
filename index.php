<?php
require_once 'config/db.php';
$db = getDB();

// Obtener cantidad del carrito
$cart_session = $_SESSION['cart_session'];
$stmt = $db->prepare("SELECT COALESCE(SUM(cantidad),0) as total FROM carrito WHERE session_id = ?");
$stmt->bind_param("s", $cart_session);
$stmt->execute();
$cart_count = (int)$stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

// Filtro por categoría
$categoria_slug = $_GET['categoria'] ?? '';

// Obtener todas las categorías activas
$categorias = $db->query("SELECT * FROM categorias WHERE activo = 1 ORDER BY orden ASC")->fetch_all(MYSQLI_ASSOC);

// Construir consulta de productos
if ($categoria_slug) {
    $stmt = $db->prepare("
        SELECT p.*, c.nombre as cat_nombre, c.slug as cat_slug, c.icono as cat_icono
        FROM productos p
        JOIN categorias c ON p.categoria_id = c.id
        WHERE c.slug = ? AND c.activo = 1
        ORDER BY p.destacado DESC, p.created_at DESC
    ");
    $stmt->bind_param("s", $categoria_slug);
    $stmt->execute();
    $productos_all = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    $productos_por_cat = [];
    foreach ($categorias as $cat) {
        if ($cat['slug'] === $categoria_slug) {
            $productos_por_cat[] = ['categoria' => $cat, 'productos' => $productos_all];
        }
    }
} else {
    // Todos los productos agrupados por categoría
    $productos_por_cat = [];
    foreach ($categorias as $cat) {
        $stmt = $db->prepare("
            SELECT p.*, c.nombre as cat_nombre, c.slug as cat_slug, c.icono as cat_icono
            FROM productos p
            JOIN categorias c ON p.categoria_id = c.id
            WHERE p.categoria_id = ?
            ORDER BY p.destacado DESC, p.created_at DESC
        ");
        $stmt->bind_param("i", $cat['id']);
        $stmt->execute();
        $prods = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        if (!empty($prods)) {
            $productos_por_cat[] = ['categoria' => $cat, 'productos' => $prods];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MICHIXUPING Joyería — Lujo a tu alcance</title>
    <meta name="description" content="Descubre la colección exclusiva de joyería XUPING. Cadenas, aretes, anillos y pulseras en oro, plata y diamantes.">
    <link rel="stylesheet" href="css/main.css">
</head>
<body>

<!-- ═══ HEADER ═══════════════════════════════════════════ -->
<header class="site-header">
    <nav class="nav-container">
        <a href="index.php" class="logo">
            <img src="uploads/logo.svg" alt="MICHIXUPING" style="height: 38px; width: auto;">
        </a>
        <ul class="nav-links">
            <li><a href="index.php" class="<?= !$categoria_slug ? 'active' : '' ?>">Inicio</a></li>
            <?php foreach ($categorias as $cat): ?>
            <li><a href="index.php?categoria=<?= htmlspecialchars($cat['slug']) ?>"
                   class="<?= $categoria_slug === $cat['slug'] ? 'active' : '' ?>">
                <?= htmlspecialchars($cat['nombre']) ?>
            </a></li>
            <?php endforeach; ?>
        </ul>
        <div class="nav-actions">
            <a href="carrito.php" class="cart-btn" id="cart-btn-header">
                🛒 Carrito
                <span class="cart-badge <?= $cart_count == 0 ? 'hidden' : '' ?>" id="cart-badge"><?= $cart_count ?></span>
            </a>
        </div>
    </nav>
</header>

<!-- ═══ HERO ═════════════════════════════════════════════ -->
<?php if (!$categoria_slug): ?>
<section class="hero">
    <div class="hero-eyebrow">✨ Colección Exclusiva 2026</div>
    <h1>Joyería que<br>define tu estilo</h1>
    <p>Descubre piezas únicas elaboradas con los materiales más finos. Oro, plata y diamantes para cada momento especial.</p>
    <a href="#coleccion" class="hero-cta">Ver Colección 💎</a>
</section>
<?php endif; ?>

<!-- ═══ FILTROS ═══════════════════════════════════════════ -->
<div class="filter-section">
    <div class="filter-bar">
        <button class="filter-btn <?= !$categoria_slug ? 'active' : '' ?>"
                onclick="location.href='index.php'" id="filter-all">
            ✨ Todo
        </button>
        <?php foreach ($categorias as $cat): ?>
        <button class="filter-btn <?= $categoria_slug === $cat['slug'] ? 'active' : '' ?>"
                onclick="location.href='index.php?categoria=<?= htmlspecialchars($cat['slug']) ?>'"
                id="filter-<?= htmlspecialchars($cat['slug']) ?>">
            <?= htmlspecialchars($cat['icono']) ?> <?= htmlspecialchars($cat['nombre']) ?>
        </button>
        <?php endforeach; ?>
    </div>
</div>

<!-- ═══ COLECCIÓN ════════════════════════════════════════ -->
<main id="coleccion">
<?php if (empty($productos_por_cat)): ?>
    <div class="page-container" style="text-align:center; padding: 4rem 2rem;">
        <div style="font-size: 3rem; margin-bottom: 1rem;">🔍</div>
        <p style="color: var(--text-muted); font-size: 1.1rem;">No hay productos en esta categoría todavía.</p>
    </div>
<?php else: ?>
    <?php foreach ($productos_por_cat as $group): ?>
    <section class="category-section">
        <div class="section-header">
            <h2 class="section-title">
                <?= htmlspecialchars($group['categoria']['icono']) ?>
                <?= htmlspecialchars($group['categoria']['nombre']) ?>
                <span style="font-size:1rem; color: var(--text-dim); font-family: Inter, sans-serif; font-weight: 400;">
                    (<?= count($group['productos']) ?> piezas)
                </span>
            </h2>
            <?php if ($group['categoria']['descripcion']): ?>
            <p style="font-size:0.875rem; color: var(--text-muted);">
                <?= htmlspecialchars($group['categoria']['descripcion']) ?>
            </p>
            <?php endif; ?>
        </div>

        <div class="products-grid">
            <?php foreach ($group['productos'] as $prod): ?>
            <article class="product-card <?= !$prod['disponible'] ? 'unavailable' : '' ?>"
                     onclick="<?= $prod['disponible'] ? 'window.location=\'producto.php?id='.$prod['id'].'\'' : '' ?>"
                     style="<?= $prod['disponible'] ? 'cursor:pointer' : 'cursor:default' ?>">

                <!-- Imagen -->
                <div class="product-img-wrap">
                    <?php if ($prod['imagen'] && file_exists('uploads/productos/' . $prod['imagen'])): ?>
                        <img src="uploads/productos/<?= htmlspecialchars($prod['imagen']) ?>"
                             alt="<?= htmlspecialchars($prod['nombre']) ?>" loading="lazy">
                    <?php else: ?>
                        <div class="product-img-placeholder">
                            <?= htmlspecialchars($prod['cat_icono'] ?? '💎') ?>
                            <span style="font-size:0.75rem; color: var(--text-dim);">Sin imagen</span>
                        </div>
                    <?php endif; ?>

                    <!-- Badge disponibilidad -->
                    <span class="product-badge <?= $prod['disponible'] ? 'badge-available' : 'badge-unavailable' ?>">
                        <?= $prod['disponible'] ? '● Disponible' : '● No disponible' ?>
                    </span>

                    <!-- Badge destacado -->
                    <?php if ($prod['destacado']): ?>
                    <span class="badge-featured">⭐ Destacado</span>
                    <?php endif; ?>
                </div>

                <!-- Info -->
                <div class="product-info">
                    <div class="product-category-tag"><?= htmlspecialchars($prod['cat_nombre']) ?></div>
                    <h3 class="product-name"><?= htmlspecialchars($prod['nombre']) ?></h3>
                    <?php if ($prod['material']): ?>
                    <p class="product-material">🔩 <?= htmlspecialchars($prod['material']) ?></p>
                    <?php endif; ?>
                    <div class="product-price">
                        Q<?= number_format($prod['precio'], 2) ?>
                        <span>GTQ</span>
                    </div>
                    <button class="btn-add-cart"
                            id="add-cart-<?= $prod['id'] ?>"
                            <?= !$prod['disponible'] ? 'disabled' : '' ?>
                            onclick="event.stopPropagation(); addToCart(<?= $prod['id'] ?>, this)">
                        <?= $prod['disponible'] ? '🛒 Agregar al Carrito' : '🚫 No disponible' ?>
                    </button>
                </div>
            </article>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endforeach; ?>
<?php endif; ?>
</main>

<!-- ═══ FOOTER ════════════════════════════════════════════ -->
<footer class="site-footer">
    <div class="footer-grid">
        <div class="footer-brand">
            <div class="logo">
                <img src="uploads/logo.svg" alt="MichiXUPING" style="height: 38px; width: auto;">
            </div>
            <p>Especialistas en joyería fina desde 2010. Cada pieza es un testimonio de artesanía y lujo atemporal.</p>
        </div>
        <div class="footer-col">
            <h4>Colecciones</h4>
            <ul>
                <?php foreach ($categorias as $cat): ?>
                <li><a href="index.php?categoria=<?= $cat['slug'] ?>"><?= $cat['nombre'] ?></a></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <div class="footer-col">
            <h4>Tienda</h4>
            <ul>
                <li><a href="carrito.php">Mi Carrito</a></li>
                <li><a href="checkout.php">Checkout</a></li>
                <li><a href="admin/login.php">Admin</a></li>
            </ul>
        </div>
        <div class="footer-col">
            <h4>Contacto</h4>
            <ul>
                <li><a href="#">📞 +502 0000-0000</a></li>
                <li><a href="#">✉️ info@Michixuping.com</a></li>
                <li><a href="#">📍 Guatemala City</a></li>
            </ul>
        </div>
    </div>
    <div class="footer-bottom">
        <span>© 2026 MichiXUPING Joyería. Todos los derechos reservados.</span>
        <span>Hecho con 💎 en Guatemala</span>
    </div>
</footer>

<!-- TOAST CONTAINER -->
<div class="toast-container" id="toast-container"></div>

<script src="js/main.js"></script>
</body>
</html>
