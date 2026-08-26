<?php
require_once '../config/db.php';
require_once 'helpers.php';
requireAdmin();

$db = getDB();
$id = intval($_GET['id'] ?? 0);
if (!$id) { header('Location: productos.php'); exit; }

$stmt = $db->prepare("SELECT * FROM productos WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$prod = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$prod) { header('Location: productos.php'); exit; }

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre      = trim($_POST['nombre'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $precio      = floatval($_POST['precio'] ?? 0);
    $categoria   = intval($_POST['categoria_id'] ?? 0);
    $material    = trim($_POST['material'] ?? '');
    $peso        = trim($_POST['peso'] ?? '');
    $disponible  = isset($_POST['disponible']) ? 1 : 0;
    $destacado   = isset($_POST['destacado']) ? 1 : 0;

    if (empty($nombre)) { $error = 'El nombre es obligatorio.'; }
    elseif ($precio <= 0) { $error = 'El precio debe ser mayor a 0.'; }
    else {
        $imagen = $prod['imagen']; // mantener la existente por defecto

        // Nueva imagen subida
        if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
            $allowed = ['image/jpeg','image/jpg','image/png','image/webp','image/gif'];
            $ftype   = mime_content_type($_FILES['imagen']['tmp_name']);
            if (!in_array($ftype, $allowed)) {
                $error = 'Tipo de imagen no permitido.';
            } elseif ($_FILES['imagen']['size'] > 5 * 1024 * 1024) {
                $error = 'Imagen muy grande (máx 5MB).';
            } else {
                $ext     = pathinfo($_FILES['imagen']['name'], PATHINFO_EXTENSION);
                $newName = uniqid('prod_') . '.' . strtolower($ext);
                $dest    = '../uploads/productos/' . $newName;
                if (move_uploaded_file($_FILES['imagen']['tmp_name'], $dest)) {
                    // Eliminar imagen anterior
                    if ($prod['imagen'] && file_exists('../uploads/productos/' . $prod['imagen'])) {
                        unlink('../uploads/productos/' . $prod['imagen']);
                    }
                    $imagen = $newName;
                } else {
                    $error = 'No se pudo guardar la imagen.';
                }
            }
        }

        // Eliminar imagen si se solicitó
        if (isset($_POST['remove_image']) && $_POST['remove_image'] == '1') {
            if ($prod['imagen'] && file_exists('../uploads/productos/' . $prod['imagen'])) {
                unlink('../uploads/productos/' . $prod['imagen']);
            }
            $imagen = null;
        }

        if (!$error) {
            $stmt = $db->prepare("
                UPDATE productos SET
                    categoria_id=?, nombre=?, descripcion=?, precio=?, imagen=?,
                    disponible=?, destacado=?, material=?, peso=?, updated_at=NOW()
                WHERE id=?
            ");
            $stmt->bind_param("issdsiissi", $categoria, $nombre, $descripcion, $precio, $imagen,
                                            $disponible, $destacado, $material, $peso, $id);
            $stmt->execute();
            $stmt->close();
            // Recargar datos
            $stmt = $db->prepare("SELECT * FROM productos WHERE id=?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $prod = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            header("Location: productos.php?msg=updated");
            exit;
        }
    }
}

$categorias = $db->query("SELECT * FROM categorias WHERE activo=1 ORDER BY orden")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Producto — Admin XUPING</title>
    <link rel="stylesheet" href="../css/admin.css">
</head>
<body>
<div class="admin-layout">
    <?php adminSidebar('productos'); ?>
    <main class="admin-main">
        <?php adminTopbar('Editar Producto'); ?>
        <div class="admin-content">

            <div class="page-header">
                <div>
                    <h2 class="page-heading">✏️ Editar Producto</h2>
                    <p class="page-sub">ID: #<?= str_pad($id,4,'0',STR_PAD_LEFT) ?> — <?= htmlspecialchars($prod['nombre']) ?></p>
                </div>
                <div style="display:flex; gap:0.75rem;">
                    <a href="../producto.php?id=<?= $id ?>" target="_blank" class="btn btn-outline">👁️ Ver en tienda</a>
                    <a href="productos.php" class="btn btn-outline">← Volver</a>
                </div>
            </div>

            <?php if ($error): ?>
            <div class="alert alert-danger">❌ <?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data" id="form-editar-producto">
                <input type="hidden" name="remove_image" id="remove_image" value="0">

                <div style="display:grid; grid-template-columns: 1fr 360px; gap: 1.5rem; align-items:start;">

                    <!-- Columna izquierda -->
                    <div style="display:flex; flex-direction:column; gap:1.5rem;">
                        <div class="card">
                            <div class="card-header"><span class="card-title">📝 Información</span></div>
                            <div class="card-body">
                                <div class="form-group">
                                    <label for="nombre">Nombre *</label>
                                    <input type="text" id="nombre" name="nombre" class="form-control" required
                                           value="<?= htmlspecialchars($prod['nombre']) ?>">
                                </div>
                                <div class="form-group">
                                    <label for="descripcion">Descripción</label>
                                    <textarea id="descripcion" name="descripcion" class="form-control" rows="4"><?= htmlspecialchars($prod['descripcion'] ?? '') ?></textarea>
                                </div>
                                <div class="form-grid">
                                    <div class="form-group">
                                        <label for="precio">Precio (Q) *</label>
                                        <input type="number" id="precio" name="precio" class="form-control"
                                               step="0.01" min="0" required value="<?= $prod['precio'] ?>">
                                    </div>
                                    <div class="form-group">
                                        <label for="categoria_id">Categoría</label>
                                        <select id="categoria_id" name="categoria_id" class="form-control">
                                            <option value="">Sin categoría</option>
                                            <?php foreach ($categorias as $cat): ?>
                                            <option value="<?= $cat['id'] ?>" <?= $prod['categoria_id'] == $cat['id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($cat['icono'].' '.$cat['nombre']) ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label for="material">Material</label>
                                        <input type="text" id="material" name="material" class="form-control"
                                               value="<?= htmlspecialchars($prod['material'] ?? '') ?>">
                                    </div>
                                    <div class="form-group">
                                        <label for="peso">Peso</label>
                                        <input type="text" id="peso" name="peso" class="form-control"
                                               value="<?= htmlspecialchars($prod['peso'] ?? '') ?>">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card">
                            <div class="card-header"><span class="card-title">⚙️ Opciones</span></div>
                            <div class="card-body" style="display:flex; flex-direction:column; gap:1rem;">
                                <div class="toggle-wrap">
                                    <label class="toggle">
                                        <input type="checkbox" id="disponible" name="disponible" <?= $prod['disponible'] ? 'checked' : '' ?>>
                                        <span class="toggle-slider"></span>
                                    </label>
                                    <span class="toggle-label">Disponible para la venta</span>
                                </div>
                                <div class="toggle-wrap">
                                    <label class="toggle">
                                        <input type="checkbox" id="destacado" name="destacado" <?= $prod['destacado'] ? 'checked' : '' ?>>
                                        <span class="toggle-slider"></span>
                                    </label>
                                    <span class="toggle-label">Producto destacado ⭐</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Imagen -->
                    <div style="display:flex; flex-direction:column; gap:1.5rem;">
                        <div class="card">
                            <div class="card-header"><span class="card-title">🖼️ Imagen</span></div>
                            <div class="card-body">
                                <?php if ($prod['imagen'] && file_exists('../uploads/productos/'.$prod['imagen'])): ?>
                                <div id="current-img-wrap" style="margin-bottom:1rem;">
                                    <p style="font-size:0.78rem; color:var(--text-dim); margin-bottom:0.5rem;">Imagen actual:</p>
                                    <img src="../uploads/productos/<?= htmlspecialchars($prod['imagen']) ?>"
                                         alt="" style="width:100%; border-radius:var(--radius-sm); border:1px solid var(--glass-border); max-height:200px; object-fit:cover;">
                                    <button type="button" class="btn btn-sm btn-danger" style="width:100%; margin-top:0.5rem;"
                                            onclick="removeCurrentImage()" id="btn-remove-img">
                                        🗑️ Eliminar imagen actual
                                    </button>
                                </div>
                                <?php endif; ?>
                                <div class="upload-area" id="upload-area">
                                    <input type="file" name="imagen" id="imagen-input" accept="image/*"
                                           onchange="previewImage(this)">
                                    <div class="upload-icon">📷</div>
                                    <div class="upload-text"><?= $prod['imagen'] ? 'Reemplazar imagen' : 'Subir imagen' ?></div>
                                    <div class="upload-hint">JPG, PNG, WEBP — Máx. 5MB</div>
                                </div>
                                <img id="img-preview" class="img-preview" src="" alt="">
                                <button type="button" id="btn-clear-img" onclick="clearImage()"
                                        style="display:none; width:100%; margin-top:0.5rem;" class="btn btn-sm btn-outline">
                                    ✕ Cancelar nueva imagen
                                </button>
                            </div>
                        </div>

                        <!-- Danger zone -->
                        <div class="card" style="border-color: rgba(248,113,113,0.2);">
                            <div class="card-header" style="border-color: rgba(248,113,113,0.15);">
                                <span class="card-title text-danger">⚠️ Zona peligrosa</span>
                            </div>
                            <div class="card-body">
                                <p style="font-size:0.8rem; color:var(--text-dim); margin-bottom:1rem;">
                                    Esta acción eliminará el producto permanentemente.
                                </p>
                                <form method="POST" action="productos.php"
                                      onsubmit="return confirm('¿ELIMINAR este producto? No se puede deshacer.')">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= $id ?>">
                                    <button type="submit" class="btn btn-danger" style="width:100%;" id="btn-delete-prod">
                                        🗑️ Eliminar Producto
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <div style="display:flex; gap:1rem; margin-top:1.5rem; justify-content:flex-end;">
                    <a href="productos.php" class="btn btn-outline">Cancelar</a>
                    <button type="submit" class="btn btn-primary" id="btn-guardar-editar">💾 Guardar Cambios</button>
                </div>
            </form>
        </div>
    </main>
</div>
<script>
function removeCurrentImage() {
    if (!confirm('¿Eliminar la imagen actual?')) return;
    document.getElementById('remove_image').value = '1';
    const wrap = document.getElementById('current-img-wrap');
    if (wrap) wrap.remove();
}
</script>
<script src="admin.js"></script>
</body>
</html>
