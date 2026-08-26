<?php
require_once '../config/db.php';

if (session_status() === PHP_SESSION_NONE) session_start();

// Si ya está logueado, redirigir
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in']) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($username) || empty($password)) {
        $error = 'Por favor ingresa usuario y contraseña.';
    } else {
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM admin_users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_user'] = [
                'id' => $user['id'],
                'username' => $user['username'],
                'nombre' => $user['nombre']
            ];
            // Actualizar last_login
            $db->query("UPDATE admin_users SET last_login = NOW() WHERE id = " . $user['id']);
            header('Location: dashboard.php');
            exit;
        } else {
            $error = 'Usuario o contraseña incorrectos.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión — Admin XUPING</title>
    <link rel="stylesheet" href="../css/admin.css">
</head>
<body>
<div class="login-page">
    <div class="login-card">
        <div class="login-logo">
            <img src="../uploads/logo.svg" alt="MichiXUPING" style="height: 48px; width: auto; margin-bottom: 1rem;">
            <p>Panel de Administración</p>
        </div>

        <?php if ($error): ?>
        <div class="alert alert-danger">❌ <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="login.php" id="login-form">
            <div class="form-group">
                <label for="username">Usuario</label>
                <input type="text" id="username" name="username" class="form-control"
                       placeholder="admin" required autocomplete="username"
                       value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label for="password">Contraseña</label>
                <div style="position:relative;">
                    <input type="password" id="password" name="password" class="form-control"
                           placeholder="••••••••" required autocomplete="current-password"
                           style="padding-right: 3rem;">
                    <button type="button" id="toggle-pass"
                            style="position:absolute; right:10px; top:50%; transform:translateY(-50%); background:none; border:none; cursor:pointer; color: var(--text-muted); font-size:1rem;"
                            onclick="togglePassword()">👁️</button>
                </div>
            </div>

            <button type="submit" class="btn btn-primary w-100" style="margin-top:0.5rem; justify-content:center; font-size:0.95rem; padding:0.85rem;" id="btn-login">
                🔐 Iniciar Sesión
            </button>
        </form>

        <div style="margin-top: 2rem; padding-top: 1.5rem; border-top: 1px solid var(--glass-border); text-align: center;">
            <a href="../index.php" style="font-size: 0.8rem; color: var(--text-dim);">← Volver a la Tienda</a>
        </div>

        <div style="margin-top: 1rem; padding: 0.75rem; background: rgba(201,168,76,0.06); border-radius: var(--radius-sm); border: 1px solid rgba(201,168,76,0.15); font-size: 0.75rem; color: var(--text-dim); text-align:center;">
            Demo: <strong style="color:var(--gold);">admin</strong> / <strong style="color:var(--gold);">password</strong>
        </div>
    </div>
</div>
<script>
function togglePassword() {
    const inp = document.getElementById('password');
    const btn = document.getElementById('toggle-pass');
    if (inp.type === 'password') {
        inp.type = 'text';
        btn.textContent = '🙈';
    } else {
        inp.type = 'password';
        btn.textContent = '👁️';
    }
}
</script>
</body>
</html>
