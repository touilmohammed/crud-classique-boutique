<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

require_login();
verify_csrf();

$id = (int)($_POST['id'] ?? 0);
if ($id > 0) cart_remove($id);

header('Location: ' . BASE_URL . '/produits/panier.php');
exit;
