<?php
// API interne JSON pour mettre à jour le panier dynamiquement
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

require_login();
verify_csrf(); // nécessite un champ csrf en POST

// Helpers de calcul
function compute_totals(PDO $pdo): array {
  $d = cart_details($pdo); // ['items'=>[], 'total_ht'=>float]
  $subtotal_ht = (float)($d['total_ht'] ?? 0.0);
  $tva = round($subtotal_ht * TVA_RATE, 2);
  $shipping = ($subtotal_ht >= FREE_SHIPPING_THRESHOLD || $subtotal_ht <= 0) ? 0.0 : (float)SHIPPING_FLAT;
  $total_ttc = round($subtotal_ht + $tva + $shipping, 2);
  return [
    'items'       => $d['items'],
    'subtotal_ht' => round($subtotal_ht, 2),
    'tva'         => $tva,
    'shipping'    => $shipping,
    'total_ttc'   => $total_ttc,
    'count'       => array_sum(array_column($d['items'], 'qty')),
  ];
}

$action = $_POST['action'] ?? '';
$id     = isset($_POST['id'])  ? (int)$_POST['id']  : 0;
$qty    = isset($_POST['qty']) ? (int)$_POST['qty'] : null;

try {
  if ($action === 'set') {
    if ($id <= 0 || $qty === null) throw new RuntimeException('Paramètres manquants');
    cart_set($pdo, $id, $qty); // 0 supprime la ligne
  } elseif ($action === 'add') {
    if ($id <= 0) throw new RuntimeException('Paramètres manquants');
    $q = ($qty ?? 1);
    if ($q < 1) $q = 1;
    cart_add($pdo, $id, $q);
  } elseif ($action === 'remove') {
    if ($id <= 0) throw new RuntimeException('Paramètres manquants');
    cart_remove($id);
  } else {
    throw new RuntimeException('Action invalide');
  }

  // Recalcule le panier + totaux
  $tot = compute_totals($pdo);

  // Retourne aussi la ligne mise à jour si elle existe (pratique pour le front)
  $line = null;
  foreach ($tot['items'] as $it) {
    if ($it['id_p'] === $id) { $line = $it; break; }
  }

  echo json_encode(['ok'=>true, 'totals'=>$tot, 'line'=>$line], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
  http_response_code(400);
  echo json_encode(['ok'=>false, 'error'=>$e->getMessage()], JSON_UNESCAPED_UNICODE);
}
