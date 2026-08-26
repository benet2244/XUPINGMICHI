<?php
$_POST['metodo_pago'] = 'apartado';
$_POST['nombre'] = 'Test';
$_POST['email'] = 'test@test.com';
session_start();
$_SESSION['cart_session'] = 'cart_test';

// Insert a fake item in carrito so it works
require 'config/db.php';
$db = getDB();
$db->query("INSERT IGNORE INTO carrito (session_id, producto_id, cantidad) VALUES ('cart_test', 1, 1)");

require 'api/pago.php';
?>
