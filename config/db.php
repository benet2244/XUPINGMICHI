<?php
// Configuración de la base de datos en Wasmer
define('DB_HOST', 'db.us-losa1.bengt.wasmernet.com');
define('DB_USER', 'user_a3d67eb9');
define('DB_PASS', 'pw_KZx0TuvlFMzQ2jp1IbCfXYpaFTip8EhJ');
define('DB_NAME', 'xuping');
define('DB_PORT', 16751); // Puerto específico proporcionado por Wasmer

define('SITE_NAME', 'XUPING Joyería');
// Se usa una URL dinámica para que detecte automáticamente tu dominio en Wasmer
define('SITE_URL', (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']));

// Crear conexión usando mysqli con soporte para puerto personalizado
function getDB() {
    static $conn = null;
    if ($conn === null) {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
        if ($conn->connect_error) {
            die(json_encode(['error' => 'Error de conexión: ' . $conn->connect_error]));
        }
        $conn->set_charset('utf8mb4');
    }
    return $conn;
}

// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Generar session_id para el carrito si no existe
if (!isset($_SESSION['cart_session'])) {
    $_SESSION['cart_session'] = uniqid('cart_', true);
}
?>
