<?php
require_once '../config/db.php';
require_once 'helpers.php';
requireAdmin();

$db = getDB();

// Estadísticas
$total_productos  = $db->query("SELECT COUNT(*) as c FROM productos")->fetch_assoc()['c'];
$disponibles      = $db->query("SELECT COUNT(*) as c FROM productos WHERE disponible = 1")->fetch_assoc()['c'];
$total_categorias = $db->query("SELECT COUNT(*) as c FROM categorias WHERE activo = 1")->fetch_assoc()['c'];
$total_pedidos    = $db->query("SELECT COUNT(*) as c FROM pedidos")->fetch_assoc()['c'];
$ingresos         = $db->query("SELECT COALESCE(SUM(total),0) as s FROM pedidos WHERE estado = 'pagado'")->fetch_assoc()['s'];

// Últimos pedidos
$ultimos_pedidos = $db->query("SELECT * FROM pedidos ORDER BY created_at DESC LIMIT 5")->fetch_all(MYSQLI_ASSOC);

// Productos recientes
$prod_recientes = $db->query("
    SELECT p.*, c.nombre as cat_nombre FROM productos p
    LEFT JOIN categorias c ON p.categoria_id = c.id
    ORDER BY p.created_at DESC LIMIT 6
")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard — Admin XUPING</title>
    <link rel="stylesheet" href="../css/admin.css">
</head>
<body>
<div class="admin-layout">
    <?php adminSidebar('dashboard'); ?>
    <main class="admin-main">
        <?php adminTopbar('Dashboard'); ?>
        <div class="admin-content">

            <!-- Bienvenida -->
            <div style="margin-bottom: 2rem;">
                <h2 class="page-heading">Bienvenido, <?= htmlspecialchars($_SESSION['admin_user']['nombre'] ?? 'Admin') ?> 👋</h2>
                <p class="page-sub">Aquí tienes el resumen general de tu tienda XUPING</p>
            </div>

            <!-- Estadísticas -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">💎</div>
                    <div class="stat-value"><?= $total_productos ?></div>
                    <div class="stat-label">Total Productos</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">✅</div>
                    <div class="stat-value"><?= $disponibles ?></div>
                    <div class="stat-label">Disponibles</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">🏷️</div>
                    <div class="stat-value"><?= $total_categorias ?></div>
                    <div class="stat-label">Categorías</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">📦</div>
                    <div class="stat-value"><?= $total_pedidos ?></div>
                    <div class="stat-label">Pedidos</div>
                </div>
                <div class="stat-card" style="grid-column: span 2;">
                    <div class="stat-icon">💰</div>
                    <div class="stat-value">Q<?= number_format($ingresos, 0) ?></div>
                    <div class="stat-label">Ingresos Totales (pagados)</div>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">

                <!-- Acciones rápidas -->
                <div class="card">
                    <div class="card-header"><span class="card-title">⚡ Acciones Rápidas</span></div>
                    <div class="card-body" style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem;">
                        <a href="nuevo_producto.php" class="btn btn-primary" id="dash-nuevo-prod">➕ Nuevo Producto</a>
                        <a href="categorias.php" class="btn btn-outline" id="dash-categorias">🏷️ Categorías</a>
                        <a href="pedidos.php" class="btn btn-outline" id="dash-pedidos">📦 Ver Pedidos</a>
                        <a href="../index.php" target="_blank" class="btn btn-outline" id="dash-tienda">🛍️ Ver Tienda</a>
                    </div>
                </div>

                <!-- Últimos pedidos -->
                <div class="card">
                    <div class="card-header">
                        <span class="card-title">📦 Últimos Pedidos</span>
                        <a href="pedidos.php" class="btn btn-sm btn-outline">Ver todos</a>
                    </div>
                    <div class="table-wrap">
                        <table>
                            <thead><tr><th>#</th><th>Cliente</th><th>Total</th><th>Estado</th></tr></thead>
                            <tbody>
                            <?php if (empty($ultimos_pedidos)): ?>
                            <tr><td colspan="4" style="text-align:center; color:var(--text-dim); padding: 2rem;">Sin pedidos aún</td></tr>
                            <?php else: ?>
                            <?php foreach ($ultimos_pedidos as $p): ?>
                            <tr>
                                <td>#<?= str_pad($p['id'],4,'0',STR_PAD_LEFT) ?></td>
                                <td class="td-name"><?= htmlspecialchars($p['nombre_cliente']) ?></td>
                                <td class="td-price">Q<?= number_format($p['total'],2) ?></td>
                                <td>
                                    <span class="badge <?= $p['estado'] === 'pagado' ? 'badge-success' : 'badge-info' ?>">
                                        <?= ucfirst($p['estado']) ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Productos recientes -->
            <div class="card" style="margin-top: 1.5rem;">
                <div class="card-header">
                    <span class="card-title">💎 Productos Recientes</span>
                    <a href="productos.php" class="btn btn-sm btn-outline">Ver todos</a>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>Imagen</th><th>Producto</th><th>Categoría</th><th>Precio</th><th>Estado</th><th>Acciones</th></tr></thead>
                        <tbody>
                        <?php foreach ($prod_recientes as $p): ?>
                        <tr>
                            <td>
                                <div class="table-img">
                                    <?php if ($p['imagen'] && file_exists('../uploads/productos/'.$p['imagen'])): ?>
                                    <img src="../uploads/productos/<?= htmlspecialchars($p['imagen']) ?>" alt="">
                                    <?php else: ?>💎<?php endif; ?>
                                </div>
                            </td>
                            <td class="td-name"><?= htmlspecialchars($p['nombre']) ?></td>
                            <td><span class="badge badge-gold"><?= htmlspecialchars($p['cat_nombre'] ?? '-') ?></span></td>
                            <td class="td-price">Q<?= number_format($p['precio'],2) ?></td>
                            <td><span class="badge <?= $p['disponible'] ? 'badge-success' : 'badge-danger' ?>">
                                <?= $p['disponible'] ? 'Disponible' : 'No disponible' ?>
                            </span></td>
                            <td class="action-btns">
                                <a href="editar_producto.php?id=<?= $p['id'] ?>" class="btn btn-sm btn-outline btn-icon" title="Editar">✏️</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </main>
</div>
</body>
</html>
