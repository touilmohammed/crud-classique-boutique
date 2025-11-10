<?php
require_once __DIR__ . '/config.php';

define('DEFAULT_IMAGE', BASE_URL . '/assets/img/placeholder.png');
define('UPLOAD_DIR', __DIR__ . '/../uploads');
define('UPLOAD_URL', BASE_URL . '/uploads');

function prix_final(float $prix_ht, $ppromo): float {
  if ($ppromo === null || $ppromo === '' || (float)$ppromo <= 0) return $prix_ht;
  $pp = max(0.0, min(100.0, (float)$ppromo));
  return round($prix_ht * (1 - $pp / 100), 2);
}

/** Format € */
function fmt_eur($n): string {
  return number_format((float)$n, 2, ',', ' ') . ' €';
}

function image_url_or_default(?string $image_path): string {
  return $image_path ? (UPLOAD_URL . '/' . rawurlencode($image_path)) : DEFAULT_IMAGE;
}

function handle_image_upload(string $field = 'image_p'): ?string {
  // Aucun fichier envoyé : on retourne NULL (l'affichage basculera sur l'image par défaut)
  if (!isset($_FILES[$field]) || $_FILES[$field]['error'] === UPLOAD_ERR_NO_FILE) {
    return null; // pas d'image = pas d'erreur
  }

  $f = $_FILES[$field];

  // Erreur d'upload "technique"
  if ($f['error'] !== UPLOAD_ERR_OK) {
    throw new RuntimeException('Erreur upload (' . $f['error'] . ')');
  }

  if ($f['size'] > 2 * 1024 * 1024) {
    throw new RuntimeException('Image trop grande (max 2 Mo)');
  }

  $finfo = new finfo(FILEINFO_MIME_TYPE);
  $mime  = $finfo->file($f['tmp_name']);
  $allowed = [
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/webp' => 'webp',
    'image/gif'  => 'gif'
  ];
  if (!isset($allowed[$mime])) {
    throw new RuntimeException('Format non supporté');
  }

  // Création du dossier d'upload si nécessaire
  if (!is_dir(UPLOAD_DIR)) {
    @mkdir(UPLOAD_DIR, 0775, true);
  }

  // Génération d'un nom de fichier aléatoire
  $ext  = $allowed[$mime];
  $name = bin2hex(random_bytes(8)) . '.' . $ext;
  $dest = UPLOAD_DIR . '/' . $name;

  // Déplacement du fichier vers /uploads
  if (!move_uploaded_file($f['tmp_name'], $dest)) {
    throw new RuntimeException('Erreur lors de la sauvegarde de l’image');
  }

  // On renvoie UNIQUEMENT le nom de fichier (à stocker en BDD)
  return $name;
}

/** Supprime physiquement une image si elle existe */
function delete_image_file(?string $filename): void {
  if (!$filename) return;
  $path = UPLOAD_DIR . '/' . $filename;
  if (is_file($path)) {
    @unlink($path);
  }
}

/** Charge la liste des catégories depuis /data/categories.json */
function load_categories(): array {
  $p = __DIR__ . '/../data/categories.json';
  if (!is_file($p)) return [];
  $arr = json_decode(file_get_contents($p), true);
  return is_array($arr) ? $arr : [];
}

/** Récupère le panier en session (créé si absent) */
function cart_get(): array {
  if (session_status() !== PHP_SESSION_ACTIVE) session_start();
  if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
  }
  return $_SESSION['cart'];
}

/** Sauvegarde le panier en session */
function cart_save(array $cart): void {
  if (session_status() !== PHP_SESSION_ACTIVE) session_start();
  $_SESSION['cart'] = $cart;
}

/** Nombre total d'articles dans le panier */
function cart_count(): int {
  $cart = cart_get();
  return array_sum($cart);
}

/** Ajoute une quantité (>=1) pour un produit donné ; limite par stock */
function cart_add(PDO $pdo, int $prodId, int $qty): bool {
  if ($qty < 1) $qty = 1;

  $st = $pdo->prepare("SELECT stock_p FROM produit WHERE id_p=:id");
  $st->execute([':id' => $prodId]);
  $row = $st->fetch();
  if (!$row) return false;

  $stock = (int)$row['stock_p'];
  $cart  = cart_get();
  $newQty = ($cart[$prodId] ?? 0) + $qty;

  if ($newQty > $stock) $newQty = $stock;
  if ($newQty < 1) $newQty = 1;

  $cart[$prodId] = $newQty;
  cart_save($cart);
  return true;
}

/** Mettre à jour la quantité exacte ; 0 => suppression de la ligne */
function cart_set(PDO $pdo, int $prodId, int $qty): bool {
  $cart = cart_get();

  if ($qty <= 0) {
    unset($cart[$prodId]);
    cart_save($cart);
    return true;
  }

  $st = $pdo->prepare("SELECT stock_p FROM produit WHERE id_p=:id");
  $st->execute([':id' => $prodId]);
  $row = $st->fetch();
  if (!$row) return false;

  $stock = (int)$row['stock_p'];
  if ($qty > $stock) $qty = $stock;

  $cart[$prodId] = $qty;
  cart_save($cart);
  return true;
}

/** Retire une ligne du panier */
function cart_remove(int $prodId): void {
  $cart = cart_get();
  unset($cart[$prodId]);
  cart_save($cart);
}

function cart_details(PDO $pdo): array {
  $cart = cart_get();
  if (!$cart) return ['items' => [], 'total_ht' => 0.0];

  $ids = array_map('intval', array_keys($cart));
  $in  = implode(',', array_fill(0, count($ids), '?'));

  $sql = "SELECT id_p, designation_p, type_p, prix_ht, ppromo, image_path, stock_p
          FROM produit WHERE id_p IN ($in)";
  $st = $pdo->prepare($sql);
  $st->execute($ids);
  $rows = $st->fetchAll();

  $items = [];
  $total = 0.0;

  foreach ($rows as $r) {
    $id  = (int)$r['id_p'];
    $qty = $cart[$id] ?? 0;
    if ($qty < 1) continue;

    $unit = prix_final((float)$r['prix_ht'], $r['ppromo']);
    $line = round($unit * $qty, 2);
    $total += $line;

    $items[] = [
      'id_p'        => $id,
      'designation_p' => $r['designation_p'],
      'type_p'      => $r['type_p'],
      'image_url'   => image_url_or_default($r['image_path'] ?? null),
      'ppromo'      => $r['ppromo'],
      'prix_ht'     => (float)$r['prix_ht'],
      'unit_final'  => $unit,
      'qty'         => $qty,
      'stock_p'     => (int)$r['stock_p'],
      'line_total'  => $line,
    ];
  }

  return ['items' => $items, 'total_ht' => round($total, 2)];
}
