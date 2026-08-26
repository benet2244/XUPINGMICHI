<?php
$_POST['metodo_pago'] = 'apartado';
$_POST['nombre'] = 'Test';
$_POST['email'] = 'test@test.com';
session_start();
$_SESSION['cart_session'] = 'cart_test';

require 'config/db.php';
$db = getDB();
$db->query("INSERT IGNORE INTO carrito (session_id, producto_id, cantidad) VALUES ('cart_test', 1, 1)");

ob_start();
require 'api/pago.php';
$out = ob_get_clean();
echo "OUTPUT:\n" . $out;
?>
