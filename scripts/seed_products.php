<?php
// scripts/seed_products.php
declare(strict_types=1);

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';

// ⚠️ Faker via Composer
$autoloader = __DIR__ . '/../vendor/autoload.php';
if (!is_file($autoloader)) {
  fwrite(STDERR, "Composer autoload introuvable. Lance: composer require fakerphp/faker --dev\n");
  exit(1);
}
require_once $autoloader;

use Faker\Factory;

//
// --------- CONFIG IMAGES DEMO ---------
// Dossier racine des images catégorisées
define('DEMO_IMG_ROOT', __DIR__ . '/../assets/demo');

// mapping "libellé catégorie" -> "dossier"
$CATEGORY_DIRS = [
  'Smartphone'           => 'smartphone',
  'Ordinateur portable'  => 'ordinateur-portable',
  'Casque audio'         => 'casque-audio',
  'Montre connectée'     => 'montre-connectee',
  'Tablette'             => 'tablette',
  'Caméra'               => 'camera',
];

// Assure le dossier uploads
if (!is_dir(UPLOAD_DIR)) @mkdir(UPLOAD_DIR, 0775, true);

// --------- Paramètres CLI ---------
// Usage: php scripts/seed_products.php --count=50
$opts = getopt('', ['count::']);
$count = isset($opts['count']) ? max(1, (int)$opts['count']) : 50;

// Charger catégories depuis data/categories.json
$cats = load_categories(); // helpers.php
$categories = array_map(fn($c) => $c['categorie'], $cats);
if (!$categories) {
  $categories = array_keys($CATEGORY_DIRS); // fallback
}

// Faker FR
$faker = Factory::create('fr_FR');

// Préparer l'INSERT
$sql = "INSERT INTO produit (type_p, designation_p, prix_ht, date_in, stock_p, ppromo, image_path)
        VALUES (:type_p, :designation_p, :prix_ht, :date_in, :stock_p, :ppromo, :image_path)";
$st = $pdo->prepare($sql);

// Petites listes pour intitulés
$brands    = ['Corsify','Auron','Nebula','Altis','Vellum','Kalyx','Zenko','Nexus','Lunox','Orion'];
$adjectifs = ['Pro','Plus','Lite','Ultra','Max','Edge','Mini','One','Air','Neo'];
$features  = ['5G','OLED','Retina','Wi-Fi 6','AMOLED','HDR10','BT 5.3','M2','Ryzen 7','Snapdragon'];

// ---------- helpers d’image ----------
/** Retourne un chemin d’image démo (absolu) pour une catégorie, ou null si aucune */
function pick_demo_image_for(string $type, array $map): ?string {
  $dirName = $map[$type] ?? null;
  if (!$dirName) return null;
  $absDir = rtrim(DEMO_IMG_ROOT, '/\\') . DIRECTORY_SEPARATOR . $dirName;
  if (!is_dir($absDir)) return null;
  $files = glob($absDir . '/*.{jpg,jpeg,png,webp,gif,JPG,JPEG,PNG,WEBP,GIF}', GLOB_BRACE);
  if (!$files) return null;
  return $files[array_rand($files)];
}

/** Copie une image démo vers /uploads et renvoie le nouveau nom de fichier */
function copy_demo_to_uploads(string $src): ?string {
  $ext = strtolower(pathinfo($src, PATHINFO_EXTENSION));
  if (!in_array($ext, ['jpg','jpeg','png','webp','gif'], true)) return null;
  $name = bin2hex(random_bytes(8)) . '.' . ($ext === 'jpeg' ? 'jpg' : $ext);
  $dst  = rtrim(UPLOAD_DIR, '/\\') . DIRECTORY_SEPARATOR . $name;
  if (!@copy($src, $dst)) return null;
  return $name;
}

// Transaction
$pdo->beginTransaction();

for ($i=0; $i<$count; $i++) {
  $type  = $categories[array_rand($categories)];

  $brand = $brands[array_rand($brands)];
  $model = strtoupper($faker->bothify('??-###'));
  $adj   = $adjectifs[array_rand($adjectifs)];
  $feat  = $features[array_rand($features)];
  $designation = "{$brand} {$model} {$adj} {$feat}";

  // Prix / stock / promo
  $min = 19.90; $max = 1999.00;
  if ($type === 'Casque audio')       { $max =  499.00; }
  if ($type === 'Smartphone')         { $min =   99.00; $max = 1599.00; }
  if ($type === 'Tablette')           { $min =   89.00; $max = 1299.00; }
  if ($type === 'Montre connectée')   { $min =   39.00; $max =  599.00; }
  if ($type === 'Caméra')             { $min =   79.00; $max = 1499.00; }
  if ($type === 'Ordinateur portable'){ $min =  249.00; $max = 2499.00; }

  $prix_ht = round($faker->randomFloat(2, $min, $max), 2);
  $stock_p = $faker->numberBetween(0, 150);
  $ppromo  = ($faker->boolean(30)) ? $faker->randomFloat(2, 5, 40) : null;
  $date_in = $faker->dateTimeBetween('-365 days', 'now')->format('Y-m-d');

  // ---- choisir/copier une image démo correspondant au type ----
  $demo = pick_demo_image_for($type, $CATEGORY_DIRS);
  $image_path = $demo ? copy_demo_to_uploads($demo) : null;

  $st->execute([
    ':type_p'        => $type,
    ':designation_p' => $designation,
    ':prix_ht'       => $prix_ht,
    ':date_in'       => $date_in,
    ':stock_p'       => $stock_p,
    ':ppromo'        => $ppromo,
    ':image_path'    => $image_path, // si null => placeholder à l’affichage
  ]);
}

$pdo->commit();

echo "OK: {$count} produits insérés avec images quand dispo dans assets/demo.\n";
