<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

require_login();
verify_csrf();

$id  = (int)($_POST['id']  ?? 0);
$qty = (int)($_POST['qty'] ?? 1);
if ($id > 0 && $qty > 0) {
  cart_add($pdo, $id, $qty);
}

// Retour sur la liste (ou provenance)
$back = $_SERVER['HTTP_REFERER'] ?? (BASE_URL . '/produits/produit_index.php');
header('Location: ' . $back);
exit;
