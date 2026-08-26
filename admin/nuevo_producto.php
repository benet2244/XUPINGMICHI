<?php
require_once '../config/db.php';
require_once 'helpers.php';
requireAdmin();

$db = getDB();
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre      = trim($_POST['nombre'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $precio      = floatval($_POST['precio'] ?? 0);
    $categoria   = intval($_POST['categoria_id'] ?? 0);
    $material    = trim($_POST['material'] ?? '');
    $peso        = trim($_POST['peso'] ?? '');
    $disponible  = isset($_POST['disponible']) ? 1 : 0;
    $destacado   = isset($_POST['destacado']) ? 1 : 0;

    if (empty($nombre)) { $error = 'El nombre del producto es obligatorio.'; }
    elseif ($precio <= 0) { $error = 'El precio debe ser mayor a 0.'; }
    else {
        // Procesar imagen
        $imagen = null;
        if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
            $allowed = ['image/jpeg','image/jpg','image/png','image/webp','image/gif'];
            $ftype   = mime_content_type($_FILES['imagen']['tmp_name']);
            if (!in_array($ftype, $allowed)) {
                $error = 'Tipo de imagen no permitido. Usa JPG, PNG, WEBP o GIF.';
            } elseif ($_FILES['imagen']['size'] > 5 * 1024 * 1024) {
                $error = 'La imagen no puede pesar más de 5MB.';
            } else {
                $ext    = pathinfo($_FILES['imagen']['name'], PATHINFO_EXTENSION);
                $imagen = uniqid('prod_') . '.' . strtolower($ext);
                $dest   = '../uploads/productos/' . $imagen;
                if (!move_uploaded_file($_FILES['imagen']['tmp_name'], $dest)) {
                    $error = 'No se pudo guardar la imagen.';
                    $imagen = null;
                }
            }
        }

        if (!$error) {
            $stmt = $db->prepare("
                INSERT INTO productos (categoria_id, nombre, descripcion, precio, imagen, disponible, destacado, material, peso)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->bind_param("issdsssss", $categoria, $nombre, $descripcion, $precio, $imagen, $disponible, $destacado, $material, $peso);
            $stmt->execute();
            $stmt->close();
            header('Location: productos.php?msg=created');
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
    <title>Nuevo Producto — Admin XUPING</title>
    <link rel="stylesheet" href="../css/admin.css">
</head>
<body>
<div class="admin-layout">
    <?php adminSidebar('nuevo_producto'); ?>
    <main class="admin-main">
        <?php adminTopbar('Agregar Producto'); ?>
        <div class="admin-content">

            <div class="page-header">
                <div>
                    <h2 class="page-heading">➕ Nuevo Producto</h2>
                    <p class="page-sub">Completa los datos de la nueva joya</p>
                </div>
                <a href="productos.php" class="btn btn-outline">← Volver</a>
            </div>

            <?php if ($error): ?>
            <div class="alert alert-danger">❌ <?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data" id="form-nuevo-producto">
                <div style="display: grid; grid-template-columns: 1fr 360px; gap: 1.5rem; align-items: start;">

                    <!-- Columna izquierda -->
                    <div style="display:flex; flex-direction:column; gap:1.5rem;">

                        <!-- Información básica -->
                        <div class="card">
                            <div class="card-header"><span class="card-title">📝 Información Básica</span></div>
                            <div class="card-body">
                                <div class="form-group">
                                    <label for="nombre">Nombre del Producto *</label>
                                    <input type="text" id="nombre" name="nombre" class="form-control"
                                           placeholder="Ej: Cadena Serpiente Oro 18K" required
                                           value="<?= htmlspecialchars($_POST['nombre'] ?? '') ?>">
                                </div>
                                <div class="form-group">
                                    <label for="descripcion">Descripción</label>
                                    <textarea id="descripcion" name="descripcion" class="form-control"
                                              rows="4" placeholder="Describe la joya, materiales, características..."><?= htmlspecialchars($_POST['descripcion'] ?? '') ?></textarea>
                                </div>
                                <div class="form-grid">
                                    <div class="form-group">
                                        <label for="precio">Precio (Q) *</label>
                                        <input type="number" id="precio" name="precio" class="form-control"
                                               placeholder="0.00" step="0.01" min="0" required
                                               value="<?= htmlspecialchars($_POST['precio'] ?? '') ?>">
                                    </div>
                                    <div class="form-group">
                                        <label for="categoria_id">Categoría</label>
                                        <select id="categoria_id" name="categoria_id" class="form-control">
                                            <option value="">Sin categoría</option>
                                            <?php foreach ($categorias as $cat): ?>
                                            <option value="<?= $cat['id'] ?>" <?= (($_POST['categoria_id'] ?? '') == $cat['id']) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($cat['icono'].' '.$cat['nombre']) ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label for="material">Material</label>
                                        <input type="text" id="material" name="material" class="form-control"
                                               placeholder="Oro 18K, Plata 925..."
                                               value="<?= htmlspecialchars($_POST['material'] ?? '') ?>">
                                    </div>
                                    <div class="form-group">
                                        <label for="peso">Peso</label>
                                        <input type="text" id="peso" name="peso" class="form-control"
                                               placeholder="5.2g, 10g..."
                                               value="<?= htmlspecialchars($_POST['peso'] ?? '') ?>">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Opciones -->
                        <div class="card">
                            <div class="card-header"><span class="card-title">⚙️ Opciones</span></div>
                            <div class="card-body" style="display:flex; flex-direction:column; gap:1rem;">
                                <div class="toggle-wrap">
                                    <label class="toggle">
                                        <input type="checkbox" id="disponible" name="disponible" checked>
                                        <span class="toggle-slider"></span>
                                    </label>
                                    <span class="toggle-label">Producto disponible para la venta</span>
                                </div>
                                <div class="toggle-wrap">
                                    <label class="toggle">
                                        <input type="checkbox" id="destacado" name="destacado">
                                        <span class="toggle-slider"></span>
                                    </label>
                                    <span class="toggle-label">Marcar como producto destacado ⭐</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Columna derecha — Imagen -->
                    <div class="card">
                        <div class="card-header"><span class="card-title">🖼️ Imagen del Producto</span></div>
                        <div class="card-body">
                            <div class="upload-area" id="upload-area">
                                <input type="file" name="imagen" id="imagen-input" accept="image/*"
                                       onchange="previewImage(this)">
                                <div class="upload-icon">📷</div>
                                <div class="upload-text">Haz clic o arrastra la imagen aquí</div>
                                <div class="upload-hint">JPG, PNG, WEBP — Máx. 5MB</div>
                            </div>
                            <img id="img-preview" class="img-preview" src="" alt="Vista previa">
                            <button type="button" id="btn-clear-img" onclick="clearImage()"
                                    style="display:none; width:100%; margin-top:0.75rem;" class="btn btn-outline btn-sm">
                                ✕ Quitar imagen
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Botones -->
                <div style="display:flex; gap:1rem; margin-top:1.5rem; justify-content:flex-end;">
                    <a href="productos.php" class="btn btn-outline" id="btn-cancelar-nuevo">Cancelar</a>
                    <button type="submit" class="btn btn-primary" id="btn-guardar-nuevo">
                        💾 Guardar Producto
                    </button>
                </div>
            </form>
        </div>
    </main>
</div>
<script src="admin.js"></script>
</body>
</html>
