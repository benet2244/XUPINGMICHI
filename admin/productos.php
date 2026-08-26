<?php
require_once '../config/db.php';
require_once 'helpers.php';
requireAdmin();

$db = getDB();

// Acciones: toggle disponibilidad, eliminar
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id     = intval($_POST['id'] ?? 0);

    if ($action === 'toggle_disponible' && $id) {
        $db->query("UPDATE productos SET disponible = NOT disponible WHERE id = $id");
        header('Location: productos.php?msg=updated');
        exit;
    }
    if ($action === 'toggle_destacado' && $id) {
        $db->query("UPDATE productos SET destacado = NOT destacado WHERE id = $id");
        header('Location: productos.php?msg=updated');
        exit;
    }
    if ($action === 'delete' && $id) {
        // Eliminar imagen si existe
        $prod = $db->query("SELECT imagen FROM productos WHERE id = $id")->fetch_assoc();
        if ($prod && $prod['imagen']) {
            $path = '../uploads/productos/' . $prod['imagen'];
            if (file_exists($path)) unlink($path);
        }
        $db->query("DELETE FROM productos WHERE id = $id");
        header('Location: productos.php?msg=deleted');
        exit;
    }
}

// Filtros
$cat_filter = intval($_GET['categoria'] ?? 0);
$search     = trim($_GET['q'] ?? '');

$where = [];
$params = [];
$types = '';

if ($cat_filter) {
    $where[] = "p.categoria_id = ?";
    $params[] = $cat_filter;
    $types .= 'i';
}
if ($search) {
    $where[] = "p.nombre LIKE ?";
    $params[] = "%$search%";
    $types .= 's';
}

$whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$stmt = $db->prepare("
    SELECT p.*, c.nombre as cat_nombre, c.icono as cat_icono
    FROM productos p
    LEFT JOIN categorias c ON p.categoria_id = c.id
    $whereSQL
    ORDER BY p.created_at DESC
");
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$productos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$categorias = $db->query("SELECT * FROM categorias WHERE activo=1 ORDER BY orden")->fetch_all(MYSQLI_ASSOC);
$msg = $_GET['msg'] ?? '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Productos — Admin XUPING</title>
    <link rel="stylesheet" href="../css/admin.css">
</head>
<body>
<div class="admin-layout">
    <?php adminSidebar('productos'); ?>
    <main class="admin-main">
        <?php adminTopbar('Gestión de Productos'); ?>
        <div class="admin-content">

            <?php if ($msg === 'created'): ?>
            <div class="alert alert-success">✅ Producto creado exitosamente.</div>
            <?php elseif ($msg === 'updated'): ?>
            <div class="alert alert-success">✅ Producto actualizado.</div>
            <?php elseif ($msg === 'deleted'): ?>
            <div class="alert alert-danger">🗑️ Producto eliminado.</div>
            <?php endif; ?>

            <div class="page-header">
                <div>
                    <h2 class="page-heading">💎 Productos</h2>
                    <p class="page-sub"><?= count($productos) ?> producto(s) encontrado(s)</p>
                </div>
                <a href="nuevo_producto.php" class="btn btn-primary" id="btn-nuevo-producto">➕ Nuevo Producto</a>
            </div>

            <!-- Filtros -->
            <div class="card" style="margin-bottom: 1.5rem;">
                <div class="card-body">
                    <form method="GET" action="productos.php" style="display: flex; gap: 1rem; flex-wrap: wrap; align-items: flex-end;">
                        <div class="form-group" style="margin-bottom:0; flex:1; min-width:200px;">
                            <label>Buscar producto</label>
                            <input type="text" name="q" class="form-control" placeholder="Nombre del producto..."
                                   value="<?= htmlspecialchars($search) ?>" id="search-input">
                        </div>
                        <div class="form-group" style="margin-bottom:0; min-width:160px;">
                            <label>Categoría</label>
                            <select name="categoria" class="form-control" id="filter-categoria">
                                <option value="">Todas</option>
                                <?php foreach ($categorias as $cat): ?>
                                <option value="<?= $cat['id'] ?>" <?= $cat_filter == $cat['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cat['nombre']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary" id="btn-filtrar">🔍 Filtrar</button>
                        <?php if ($search || $cat_filter): ?>
                        <a href="productos.php" class="btn btn-outline" id="btn-limpiar">✕ Limpiar</a>
                        <?php endif; ?>
                    </form>
                </div>
            </div>

            <!-- Tabla -->
            <div class="card">
                <div class="table-wrap">
                    <table id="productos-table">
                        <thead>
                            <tr>
                                <th>Imagen</th>
                                <th>Nombre</th>
                                <th>Categoría</th>
                                <th>Precio</th>
                                <th>Disponible</th>
                                <th>Destacado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (empty($productos)): ?>
                        <tr><td colspan="7" style="text-align:center; padding:3rem; color:var(--text-dim);">
                            No se encontraron productos.
                            <a href="nuevo_producto.php" style="color:var(--gold);">Agregar uno</a>
                        </td></tr>
                        <?php else: ?>
                        <?php foreach ($productos as $p): ?>
                        <tr id="product-row-<?= $p['id'] ?>">
                            <!-- Imagen -->
                            <td>
                                <div class="table-img">
                                    <?php if ($p['imagen'] && file_exists('../uploads/productos/'.$p['imagen'])): ?>
                                    <img src="../uploads/productos/<?= htmlspecialchars($p['imagen']) ?>" alt="">
                                    <?php else: ?>
                                    <?= htmlspecialchars($p['cat_icono'] ?? '💎') ?>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <!-- Nombre -->
                            <td class="td-name" style="max-width:220px;">
                                <?= htmlspecialchars($p['nombre']) ?>
                                <?php if ($p['material']): ?>
                                <div style="font-size:0.72rem; color:var(--text-dim);"><?= htmlspecialchars($p['material']) ?></div>
                                <?php endif; ?>
                            </td>
                            <!-- Categoría -->
                            <td><span class="badge badge-gold"><?= htmlspecialchars($p['cat_icono'] ?? '') ?> <?= htmlspecialchars($p['cat_nombre'] ?? '—') ?></span></td>
                            <!-- Precio -->
                            <td class="td-price">Q<?= number_format($p['precio'],2) ?></td>
                            <!-- Disponible (toggle) -->
                            <td>
                                <form method="POST" action="productos.php" style="display:inline;">
                                    <input type="hidden" name="action" value="toggle_disponible">
                                    <input type="hidden" name="id" value="<?= $p['id'] ?>">
                                    <label class="toggle-wrap">
                                        <label class="toggle">
                                            <input type="checkbox" <?= $p['disponible'] ? 'checked' : '' ?>
                                                   onchange="this.form.submit()"
                                                   id="toggle-disp-<?= $p['id'] ?>">
                                            <span class="toggle-slider"></span>
                                        </label>
                                        <span class="badge <?= $p['disponible'] ? 'badge-success' : 'badge-danger' ?>" style="font-size:0.68rem;">
                                            <?= $p['disponible'] ? 'Sí' : 'No' ?>
                                        </span>
                                    </label>
                                </form>
                            </td>
                            <!-- Destacado (toggle) -->
                            <td>
                                <form method="POST" action="productos.php" style="display:inline;">
                                    <input type="hidden" name="action" value="toggle_destacado">
                                    <input type="hidden" name="id" value="<?= $p['id'] ?>">
                                    <label class="toggle">
                                        <input type="checkbox" <?= $p['destacado'] ? 'checked' : '' ?>
                                               onchange="this.form.submit()"
                                               id="toggle-dest-<?= $p['id'] ?>">
                                        <span class="toggle-slider"></span>
                                    </label>
                                </form>
                            </td>
                            <!-- Acciones -->
                            <td>
                                <div class="action-btns">
                                    <a href="editar_producto.php?id=<?= $p['id'] ?>" class="btn btn-sm btn-outline btn-icon" title="Editar" id="edit-<?= $p['id'] ?>">✏️</a>
                                    <a href="../producto.php?id=<?= $p['id'] ?>" target="_blank" class="btn btn-sm btn-outline btn-icon" title="Ver en tienda">👁️</a>
                                    <form method="POST" action="productos.php" style="display:inline;"
                                          onsubmit="return confirm('¿Eliminar este producto? Esta acción no se puede deshacer.')">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= $p['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-danger btn-icon" title="Eliminar" id="del-<?= $p['id'] ?>">🗑️</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </main>
</div>
</body>
</html>
