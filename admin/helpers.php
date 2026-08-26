<?php
// Include helper para sidebar del admin
require_once '../config/db.php';

function adminSidebar($activePage = '') {
    $user = $_SESSION['admin_user'] ?? ['nombre' => 'Admin', 'username' => 'admin'];
    $initial = strtoupper(substr($user['nombre'] ?? 'A', 0, 1));
    ?>
    <aside class="sidebar">
        <div class="sidebar-logo" style="display: flex; flex-direction: column; align-items: flex-start; gap: 8px;">
            <img src="../uploads/logo.svg" alt="MichiXUPING" style="height: 28px; width: auto;">
            <span class="sidebar-subtitle" style="margin-top: 2px;">Panel de Administración</span>
        </div>

        <nav class="sidebar-nav">
            <div class="nav-label">Principal</div>
            <a href="dashboard.php" class="nav-item <?= $activePage === 'dashboard' ? 'active' : '' ?>">
                <span class="nav-icon">📊</span> Dashboard
            </a>

            <div class="nav-label">Catálogo</div>
            <a href="productos.php" class="nav-item <?= $activePage === 'productos' ? 'active' : '' ?>">
                <span class="nav-icon">💎</span> Productos
            </a>
            <a href="nuevo_producto.php" class="nav-item <?= $activePage === 'nuevo_producto' ? 'active' : '' ?>">
                <span class="nav-icon">➕</span> Agregar Producto
            </a>
            <a href="categorias.php" class="nav-item <?= $activePage === 'categorias' ? 'active' : '' ?>">
                <span class="nav-icon">🏷️</span> Categorías
            </a>

            <div class="nav-label">Ventas</div>
            <a href="pedidos.php" class="nav-item <?= $activePage === 'pedidos' ? 'active' : '' ?>">
                <span class="nav-icon">📦</span> Pedidos
            </a>
        </nav>

        <div class="sidebar-bottom">
            <div class="sidebar-user">
                <div class="user-avatar"><?= htmlspecialchars($initial) ?></div>
                <div class="user-info">
                    <div class="user-name"><?= htmlspecialchars($user['nombre']) ?></div>
                    <div class="user-role">Administrador</div>
                </div>
            </div>
            <a href="logout.php" class="btn-logout">🚪 Cerrar Sesión</a>
        </div>
    </aside>
    <?php
}

function adminTopbar($title = '') {
    ?>
    <div class="admin-topbar">
        <span class="topbar-title"><?= htmlspecialchars($title) ?></span>
        <div class="topbar-right">
            <a href="../index.php" target="_blank" class="topbar-store-link">🛍️ Ver Tienda</a>
        </div>
    </div>
    <?php
    pendingOrdersNotif();
}

/**
 * Muestra una notificación flotante si hay pedidos pendientes de confirmación.
 */
function pendingOrdersNotif() {
    $db = getDB();
    $result = $db->query("SELECT COUNT(*) as total FROM pedidos WHERE estado = 'pendiente'");
    $pending = (int)($result->fetch_assoc()['total'] ?? 0);

    if ($pending === 0) return;

    $label = $pending === 1 ? '1 pedido pendiente' : "{$pending} pedidos pendientes";
    $sub   = $pending === 1
        ? 'Hay un pedido esperando confirmación'
        : 'Hay pedidos esperando confirmación';
    ?>
    <div class="pending-notif" id="pending-notif" title="Ver pedidos pendientes">
        <div class="pending-notif-icon">
            📦
            <span class="pending-notif-pulse"></span>
        </div>
        <div class="pending-notif-body">
            <div class="pending-notif-title"><?= htmlspecialchars($label) ?></div>
            <div class="pending-notif-sub"><?= htmlspecialchars($sub) ?> — <a href="pedidos.php?estado=pendiente" style="color:var(--warning); font-weight:600;">Ver ahora →</a></div>
        </div>
        <div class="pending-notif-count"><?= $pending ?></div>
        <button class="pending-notif-close" onclick="dismissPendingNotif(event)" title="Cerrar">✕</button>
    </div>
    <script>
    function dismissPendingNotif(e) {
        e.preventDefault();
        e.stopPropagation();
        const el = document.getElementById('pending-notif');
        if (!el) return;
        el.classList.add('hiding');
        setTimeout(() => el.remove(), 300);
    }
    </script>
    <?php
}

function requireAdmin() {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
        header('Location: login.php');
        exit;
    }
}
?>
