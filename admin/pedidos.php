<?php
require_once '../config/db.php';
require_once 'helpers.php';
requireAdmin();

$db = getDB();

// Cambiar estado del pedido
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pedido_id'])) {
    $pid    = intval($_POST['pedido_id']);
    $estado = $_POST['estado'] ?? 'pendiente';
    $allowed_states = ['pendiente','pagado','enviado','cancelado'];
    if (in_array($estado, $allowed_states)) {
        $stmt = $db->prepare("UPDATE pedidos SET estado = ? WHERE id = ?");
        $stmt->bind_param("si", $estado, $pid);
        $stmt->execute();
        $stmt->close();

        // Si el estado cambia a pagado o enviado, descontar inventario (marcar no disponible)
        if ($estado === 'pagado' || $estado === 'enviado') {
            $stmt = $db->prepare("
                UPDATE productos p
                JOIN pedido_items pi ON p.id = pi.producto_id
                SET p.disponible = 0
                WHERE pi.pedido_id = ?
            ");
            $stmt->bind_param("i", $pid);
            $stmt->execute();
            $stmt->close();
        }
    }
    header('Location: pedidos.php?msg=updated');
    exit;
}

// Filtro
$estado_filter = $_GET['estado'] ?? '';
$where = '';
$params = [];
$types = '';
if ($estado_filter) {
    $where = "WHERE p.estado = ?";
    $params[] = $estado_filter;
    $types .= 's';
}

$stmt = $db->prepare("SELECT p.*, COUNT(pi.id) as total_items FROM pedidos p LEFT JOIN pedido_items pi ON p.id = pi.pedido_id $where GROUP BY p.id ORDER BY p.created_at DESC");
if ($params) $stmt->bind_param($types, ...$params);
$stmt->execute();
$pedidos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$msg = $_GET['msg'] ?? '';
$ver_id = intval($_GET['ver'] ?? 0);

// Ver detalle de pedido
$pedido_detalle = null;
$pedido_items_detail = [];
if ($ver_id) {
    $stmt = $db->prepare("SELECT * FROM pedidos WHERE id = ?");
    $stmt->bind_param("i", $ver_id);
    $stmt->execute();
    $pedido_detalle = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if ($pedido_detalle) {
        $stmt = $db->prepare("SELECT * FROM pedido_items WHERE pedido_id = ?");
        $stmt->bind_param("i", $ver_id);
        $stmt->execute();
        $pedido_items_detail = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pedidos — Admin XUPING</title>
    <link rel="stylesheet" href="../css/admin.css">
</head>
<body>
<div class="admin-layout">
    <?php adminSidebar('pedidos'); ?>
    <main class="admin-main">
        <?php adminTopbar('Gestión de Pedidos'); ?>
        <div class="admin-content">

            <?php if ($msg === 'updated'): ?>
            <div class="alert alert-success">✅ Estado del pedido actualizado.</div>
            <?php endif; ?>

            <!-- Detalle de pedido (si se solicitó) -->
            <?php if ($pedido_detalle): ?>
            <div class="card" style="margin-bottom: 1.5rem; border-color: rgba(201,168,76,0.3);">
                <div class="card-header">
                    <span class="card-title">📦 Pedido #<?= str_pad($pedido_detalle['id'],6,'0',STR_PAD_LEFT) ?></span>
                    <a href="pedidos.php" class="btn btn-sm btn-outline">← Cerrar</a>
                </div>
                <div class="card-body">
                    <div style="display:grid; grid-template-columns: 1fr 1fr 1fr; gap:1.5rem; margin-bottom:1.5rem;">
                        <div>
                            <p style="font-size:0.72rem; color:var(--text-dim); font-weight:700; text-transform:uppercase; margin-bottom:0.25rem;">Cliente</p>
                            <p style="font-weight:600;"><?= htmlspecialchars($pedido_detalle['nombre_cliente']) ?></p>
                            <p style="font-size:0.8rem; color:var(--text-muted);"><?= htmlspecialchars($pedido_detalle['email']) ?></p>
                            <?php if ($pedido_detalle['telefono']): ?>
                            <p style="font-size:0.8rem; color:var(--text-muted);"><?= htmlspecialchars($pedido_detalle['telefono']) ?></p>
                            <?php endif; ?>
                        </div>
                        <div>
                            <p style="font-size:0.72rem; color:var(--text-dim); font-weight:700; text-transform:uppercase; margin-bottom:0.25rem;">Pago</p>
                            <p style="font-weight:600; color:var(--gold);">Q<?= number_format($pedido_detalle['total'],2) ?></p>
                            <p style="font-size:0.8rem; color:var(--text-muted);"><?= $pedido_detalle['metodo_pago'] === 'apartado' ? '📱 Apartado (WhatsApp)' : '💳 Otro' ?></p>
                            <p style="font-size:0.72rem; color:var(--text-dim);"><?= htmlspecialchars($pedido_detalle['referencia_pago'] ?? '') ?></p>
                        </div>
                        <div>
                            <p style="font-size:0.72rem; color:var(--text-dim); font-weight:700; text-transform:uppercase; margin-bottom:0.25rem;">Cambiar Estado</p>
                            <form method="POST" id="form-estado-<?= $pedido_detalle['id'] ?>">
                                <input type="hidden" name="pedido_id" value="<?= $pedido_detalle['id'] ?>">
                                <select name="estado" class="form-control" style="margin-bottom:0.5rem;" onchange="this.form.submit()">
                                    <?php foreach (['pendiente','pagado','enviado','cancelado'] as $e): ?>
                                    <option value="<?= $e ?>" <?= $pedido_detalle['estado'] === $e ? 'selected' : '' ?>><?= ucfirst($e) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </form>
                        </div>
                    </div>
                    <!-- Items del pedido -->
                    <table style="width:100%; border-collapse:collapse;">
                        <thead><tr>
                            <th style="padding:0.5rem; text-align:left; font-size:0.72rem; color:var(--text-dim); border-bottom:1px solid var(--glass-border);">Producto</th>
                            <th style="padding:0.5rem; text-align:center; font-size:0.72rem; color:var(--text-dim); border-bottom:1px solid var(--glass-border);">Qty</th>
                            <th style="padding:0.5rem; text-align:right; font-size:0.72rem; color:var(--text-dim); border-bottom:1px solid var(--glass-border);">Precio Unit.</th>
                            <th style="padding:0.5rem; text-align:right; font-size:0.72rem; color:var(--text-dim); border-bottom:1px solid var(--glass-border);">Subtotal</th>
                        </tr></thead>
                        <tbody>
                        <?php foreach ($pedido_items_detail as $it): ?>
                        <tr>
                            <td style="padding:0.6rem 0.5rem; font-size:0.875rem; color:var(--text);"><?= htmlspecialchars($it['nombre_producto']) ?></td>
                            <td style="padding:0.6rem 0.5rem; text-align:center; color:var(--text-muted);"><?= $it['cantidad'] ?></td>
                            <td style="padding:0.6rem 0.5rem; text-align:right; color:var(--text-muted);">Q<?= number_format($it['precio_unitario'],2) ?></td>
                            <td style="padding:0.6rem 0.5rem; text-align:right; color:var(--gold); font-weight:600;">Q<?= number_format($it['precio_unitario']*$it['cantidad'],2) ?></td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>

                    <?php
                    $wa_phone = preg_replace('/[^0-9]/', '', $pedido_detalle['telefono'] ?? '');
                    $wa_url = "https://wa.me/{$wa_phone}";
                    $msg_confirm = urlencode("Hola {$pedido_detalle['nombre_cliente']}, tu pedido #{$pedido_detalle['id']} por Q" . number_format($pedido_detalle['total'],2) . " de MichiXUPING ha sido confirmado. Puedes realizar el pago y coordinaremos la entrega.");
                    ?>
                    <div style="margin-top: 1.5rem; padding-top: 1.5rem; border-top: 1px solid var(--glass-border);">
                        <h4 style="margin-bottom: 1rem; color: var(--text); font-size: 0.9rem;">📱 Acciones por WhatsApp</h4>
                        <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
                            <a href="<?= $wa_url ?>?text=<?= $msg_confirm ?>" target="_blank" class="btn btn-sm" style="background-color: #25D366; border-color: #25D366;" onclick="document.getElementById('form-estado-<?= $pedido_detalle['id'] ?>').querySelector('select').value='pagado'; document.getElementById('form-estado-<?= $pedido_detalle['id'] ?>').submit();">
                                ✅ Confirmar Pedido
                            </a>
                            <button type="button" class="btn btn-sm btn-danger" onclick="document.getElementById('rechazo-box').style.display='block';">
                                ❌ Rechazar Pedido
                            </button>
                        </div>
                        
                        <div id="rechazo-box" style="display:none; margin-top: 1rem; background: var(--dark-2); padding: 1rem; border-radius: var(--radius-sm); border: 1px solid var(--glass-border);">
                            <p style="font-size: 0.8rem; margin-bottom: 0.5rem; color: var(--text-muted);">Escribe el motivo del rechazo (se enviará al cliente):</p>
                            <textarea id="rechazo-motivo" class="form-control" rows="2" placeholder="Ej. Lo siento, esta pieza ya se agotó hace un momento."></textarea>
                            <button type="button" class="btn btn-sm btn-danger" style="margin-top: 0.5rem;" onclick="enviarRechazo(<?= $pedido_detalle['id'] ?>, '<?= addslashes($pedido_detalle['nombre_cliente']) ?>', '<?= $wa_url ?>')">Enviar Rechazo y Cancelar Orden</button>
                        </div>
                    </div>
                    
                    <script>
                    function enviarRechazo(id, nombre, baseUrl) {
                        const motivo = document.getElementById('rechazo-motivo').value.trim();
                        if(!motivo) { alert('Por favor, ingresa un motivo para rechazar.'); return; }
                        const msg = `Hola ${nombre}, sobre tu apartado del pedido #${id} en MichiXUPING, lamentamos informarte que no pudo ser confirmado debido a que: ${motivo}`;
                        
                        const select = document.getElementById('form-estado-' + id).querySelector('select');
                        select.value = 'cancelado';
                        window.open(baseUrl + '?text=' + encodeURIComponent(msg), '_blank');
                        document.getElementById('form-estado-' + id).submit();
                    }
                    </script>

                </div>
            </div>
            <?php endif; ?>

            <div class="page-header">
                <div>
                    <h2 class="page-heading">📦 Pedidos</h2>
                    <p class="page-sub"><?= count($pedidos) ?> pedido(s)</p>
                </div>
                <!-- Filtro de estado -->
                <div style="display:flex; gap:0.5rem;">
                    <a href="pedidos.php" class="btn btn-sm <?= !$estado_filter ? 'btn-primary' : 'btn-outline' ?>">Todos</a>
                    <a href="pedidos.php?estado=pendiente" class="btn btn-sm <?= $estado_filter === 'pendiente' ? 'btn-primary' : 'btn-outline' ?>">Pendientes</a>
                    <a href="pedidos.php?estado=pagado" class="btn btn-sm <?= $estado_filter === 'pagado' ? 'btn-primary' : 'btn-outline' ?>">Pagados</a>
                    <a href="pedidos.php?estado=enviado" class="btn btn-sm <?= $estado_filter === 'enviado' ? 'btn-primary' : 'btn-outline' ?>">Enviados</a>
                </div>
            </div>

            <div class="card">
                <div class="table-wrap">
                    <table id="pedidos-table">
                        <thead>
                            <tr><th>#</th><th>Cliente</th><th>Email</th><th>Items</th><th>Total</th><th>Método</th><th>Estado</th><th>Fecha</th><th>Acciones</th></tr>
                        </thead>
                        <tbody>
                        <?php if (empty($pedidos)): ?>
                        <tr><td colspan="9" style="text-align:center; padding:3rem; color:var(--text-dim);">Sin pedidos registrados</td></tr>
                        <?php else: ?>
                        <?php foreach ($pedidos as $p): ?>
                        <tr>
                            <td style="font-weight:600;">#<?= str_pad($p['id'],4,'0',STR_PAD_LEFT) ?></td>
                            <td class="td-name"><?= htmlspecialchars($p['nombre_cliente']) ?></td>
                            <td style="font-size:0.8rem; color:var(--text-dim);"><?= htmlspecialchars($p['email']) ?></td>
                            <td style="text-align:center;"><?= $p['total_items'] ?></td>
                            <td class="td-price">Q<?= number_format($p['total'],2) ?></td>
                            <td><span class="badge badge-info"><?= $p['metodo_pago'] === 'apartado' ? '📱 Apartado' : '💳 Otro' ?></span></td>
                            <td>
                                <?php
                                $estclass = ['pendiente'=>'badge-info','pagado'=>'badge-success','enviado'=>'badge-gold','cancelado'=>'badge-danger'];
                                ?>
                                <span class="badge <?= $estclass[$p['estado']] ?? 'badge-info' ?>"><?= ucfirst($p['estado']) ?></span>
                            </td>
                            <td style="font-size:0.78rem; color:var(--text-dim);"><?= date('d/m/Y H:i', strtotime($p['created_at'])) ?></td>
                            <td>
                                <a href="pedidos.php?ver=<?= $p['id'] ?>" class="btn btn-sm btn-outline btn-icon" title="Ver detalle" id="ver-pedido-<?= $p['id'] ?>">👁️</a>
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
