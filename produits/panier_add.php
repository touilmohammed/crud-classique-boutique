<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

require_login();
verify_csrf(); // vérifie le token en POST

$id  = isset($_POST['id'])  ? (int)$_POST['id']  : 0;
$qty = isset($_POST['qty']) ? (int)$_POST['qty'] : 1;
if ($qty < 1) $qty = 1;

if ($id > 0) {
  cart_add($pdo, $id, $qty); // clamp au stock si nécessaire
}

// Redirection : soit le Referer, soit la page panier
$back = $_SERVER['HTTP_REFERER'] ?? (BASE_URL . '/produits/panier.php');
header('Location: ' . $back);
exit;
