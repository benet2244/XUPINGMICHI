<?php
require_once '../config/db.php';
require_once 'helpers.php';
requireAdmin();

$db = getDB();
$error = '';
$success = '';

// Acciones
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $nombre      = trim($_POST['nombre'] ?? '');
        $slug        = trim($_POST['slug'] ?? '');
        $descripcion = trim($_POST['descripcion'] ?? '');
        $icono       = trim($_POST['icono'] ?? '✨');
        $orden       = intval($_POST['orden'] ?? 0);

        if (empty($nombre) || empty($slug)) { $error = 'Nombre y slug son obligatorios.'; }
        else {
            // Limpiar slug
            $slug = strtolower(preg_replace('/[^a-z0-9-]/', '', str_replace(' ', '-', $slug)));
            $stmt = $db->prepare("INSERT INTO categorias (nombre, slug, descripcion, icono, orden) VALUES (?,?,?,?,?)");
            $stmt->bind_param("ssssi", $nombre, $slug, $descripcion, $icono, $orden);
            if ($stmt->execute()) { $success = 'Categoría creada.'; }
            else { $error = 'Error al crear la categoría. El slug puede estar duplicado.'; }
            $stmt->close();
        }
    }
    elseif ($action === 'update') {
        $id          = intval($_POST['id']);
        $nombre      = trim($_POST['nombre'] ?? '');
        $descripcion = trim($_POST['descripcion'] ?? '');
        $icono       = trim($_POST['icono'] ?? '✨');
        $orden       = intval($_POST['orden'] ?? 0);
        $activo      = isset($_POST['activo']) ? 1 : 0;

        $stmt = $db->prepare("UPDATE categorias SET nombre=?, descripcion=?, icono=?, orden=?, activo=? WHERE id=?");
        $stmt->bind_param("ssssii", $nombre, $descripcion, $icono, $orden, $activo, $id);
        $stmt->execute();
        $stmt->close();
        $success = 'Categoría actualizada.';
    }
    elseif ($action === 'delete') {
        $id = intval($_POST['id']);
        // Verificar si tiene productos
        $count = $db->query("SELECT COUNT(*) as c FROM productos WHERE categoria_id = $id")->fetch_assoc()['c'];
        if ($count > 0) {
            $error = "No se puede eliminar: tiene $count producto(s) asignado(s). Cambia su categoría primero.";
        } else {
            $db->query("DELETE FROM categorias WHERE id = $id");
            $success = 'Categoría eliminada.';
        }
    }
}

$categorias = $db->query("SELECT c.*, (SELECT COUNT(*) FROM productos p WHERE p.categoria_id = c.id) as total_productos FROM categorias c ORDER BY c.orden ASC")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Categorías — Admin XUPING</title>
    <link rel="stylesheet" href="../css/admin.css">
</head>
<body>
<div class="admin-layout">
    <?php adminSidebar('categorias'); ?>
    <main class="admin-main">
        <?php adminTopbar('Gestión de Categorías'); ?>
        <div class="admin-content">

            <div class="page-header">
                <div>
                    <h2 class="page-heading">🏷️ Categorías</h2>
                    <p class="page-sub">Gestiona las colecciones de la tienda</p>
                </div>
            </div>

            <?php if ($error): ?><div class="alert alert-danger">❌ <?= htmlspecialchars($error) ?></div><?php endif; ?>
            <?php if ($success): ?><div class="alert alert-success">✅ <?= htmlspecialchars($success) ?></div><?php endif; ?>

            <div style="display: grid; grid-template-columns: 1fr 380px; gap: 1.5rem; align-items: start;">

                <!-- Lista de categorías -->
                <div class="card">
                    <div class="card-header"><span class="card-title">📋 Lista de Categorías</span></div>
                    <div class="table-wrap">
                        <table id="categorias-table">
                            <thead>
                                <tr><th>Icono</th><th>Nombre</th><th>Slug</th><th>Productos</th><th>Estado</th><th>Acciones</th></tr>
                            </thead>
                            <tbody>
                            <?php if (empty($categorias)): ?>
                            <tr><td colspan="6" style="text-align:center; padding:2rem; color:var(--text-dim);">Sin categorías</td></tr>
                            <?php else: ?>
                            <?php foreach ($categorias as $cat): ?>
                            <tr id="cat-row-<?= $cat['id'] ?>">
                                <td style="font-size:1.5rem; text-align:center;"><?= htmlspecialchars($cat['icono']) ?></td>
                                <td class="td-name"><?= htmlspecialchars($cat['nombre']) ?></td>
                                <td><code style="font-size:0.75rem; color:var(--text-dim); background:var(--dark-3); padding:0.2rem 0.5rem; border-radius:4px;"><?= htmlspecialchars($cat['slug']) ?></code></td>
                                <td><span class="badge badge-gold"><?= $cat['total_productos'] ?> productos</span></td>
                                <td><span class="badge <?= $cat['activo'] ? 'badge-success' : 'badge-danger' ?>"><?= $cat['activo'] ? 'Activa' : 'Inactiva' ?></span></td>
                                <td>
                                    <div class="action-btns">
                                        <button onclick="editCategory(<?= htmlspecialchars(json_encode($cat)) ?>)"
                                                class="btn btn-sm btn-outline btn-icon" id="edit-cat-<?= $cat['id'] ?>" title="Editar">✏️</button>
                                        <?php if ($cat['total_productos'] == 0): ?>
                                        <form method="POST" style="display:inline;" onsubmit="return confirm('¿Eliminar categoría?')">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?= $cat['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-danger btn-icon" id="del-cat-<?= $cat['id'] ?>" title="Eliminar">🗑️</button>
                                        </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Formulario crear/editar -->
                <div class="card" id="cat-form-card">
                    <div class="card-header"><span class="card-title" id="form-title">➕ Nueva Categoría</span></div>
                    <div class="card-body">
                        <form method="POST" id="cat-form">
                            <input type="hidden" name="action" id="cat-action" value="create">
                            <input type="hidden" name="id" id="cat-id" value="">

                            <div class="form-group">
                                <label for="cat-nombre">Nombre *</label>
                                <input type="text" id="cat-nombre" name="nombre" class="form-control" required
                                       placeholder="Ej: Cadenas, Aretes...">
                            </div>
                            <div class="form-group" id="slug-group">
                                <label for="cat-slug">Slug (URL) *</label>
                                <input type="text" id="cat-slug" name="slug" class="form-control" required
                                       placeholder="cadenas, aretes...">
                                <p class="form-note">Letras minúsculas, números y guiones. No se puede cambiar después.</p>
                            </div>
                            <div class="form-group">
                                <label for="cat-icono">Icono (emoji)</label>
                                <input type="text" id="cat-icono" name="icono" class="form-control"
                                       placeholder="💎" value="✨" maxlength="10">
                            </div>
                            <div class="form-group">
                                <label for="cat-descripcion">Descripción</label>
                                <textarea id="cat-descripcion" name="descripcion" class="form-control" rows="3"
                                          placeholder="Breve descripción de esta colección"></textarea>
                            </div>
                            <div class="form-group">
                                <label for="cat-orden">Orden de aparición</label>
                                <input type="number" id="cat-orden" name="orden" class="form-control"
                                       placeholder="1, 2, 3..." min="0" value="0">
                            </div>
                            <div class="toggle-wrap" id="activo-toggle-wrap" style="display:none; margin-bottom:1.25rem;">
                                <label class="toggle">
                                    <input type="checkbox" id="cat-activo" name="activo" checked>
                                    <span class="toggle-slider"></span>
                                </label>
                                <span class="toggle-label">Categoría activa</span>
                            </div>

                            <div style="display:flex; gap:0.75rem;">
                                <button type="submit" class="btn btn-primary" id="btn-cat-submit">➕ Crear Categoría</button>
                                <button type="button" onclick="resetCatForm()" class="btn btn-outline" id="btn-cat-cancel" style="display:none;">✕ Cancelar</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>
<script>
// Auto-generar slug
document.getElementById('cat-nombre').addEventListener('input', function() {
    if (document.getElementById('cat-action').value === 'create') {
        const slug = this.value.toLowerCase()
            .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
            .replace(/[^a-z0-9\s-]/g, '').replace(/\s+/g, '-').trim();
        document.getElementById('cat-slug').value = slug;
    }
});

function editCategory(cat) {
    document.getElementById('form-title').textContent = '✏️ Editar Categoría';
    document.getElementById('cat-action').value = 'update';
    document.getElementById('cat-id').value = cat.id;
    document.getElementById('cat-nombre').value = cat.nombre;
    document.getElementById('cat-slug').value = cat.slug;
    document.getElementById('cat-icono').value = cat.icono;
    document.getElementById('cat-descripcion').value = cat.descripcion || '';
    document.getElementById('cat-orden').value = cat.orden;
    document.getElementById('cat-activo').checked = cat.activo == 1;
    document.getElementById('slug-group').style.opacity = '0.5';
    document.getElementById('cat-slug').readOnly = true;
    document.getElementById('activo-toggle-wrap').style.display = 'flex';
    document.getElementById('btn-cat-submit').textContent = '💾 Guardar Cambios';
    document.getElementById('btn-cat-cancel').style.display = 'inline-flex';
    document.getElementById('cat-form-card').scrollIntoView({ behavior: 'smooth' });
}

function resetCatForm() {
    document.getElementById('form-title').textContent = '➕ Nueva Categoría';
    document.getElementById('cat-action').value = 'create';
    document.getElementById('cat-id').value = '';
    document.getElementById('cat-form').reset();
    document.getElementById('cat-icono').value = '✨';
    document.getElementById('cat-orden').value = '0';
    document.getElementById('cat-slug').readOnly = false;
    document.getElementById('slug-group').style.opacity = '1';
    document.getElementById('activo-toggle-wrap').style.display = 'none';
    document.getElementById('btn-cat-submit').textContent = '➕ Crear Categoría';
    document.getElementById('btn-cat-cancel').style.display = 'none';
}
</script>
</body>
</html>
