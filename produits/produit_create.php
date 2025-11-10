<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

require_login();
require_role(['admin','editeur']);
verify_csrf(); // ne fait rien en GET, vérifie le token en POST

$errors = [];
$values = [
  'type_p'       => '',
  'designation_p'=> '',
  'prix_ht'      => '',
  'date_in'      => date('Y-m-d'),
  'stock_p'      => '0',
  'ppromo'       => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  foreach ($values as $k => $_) $values[$k] = trim($_POST[$k] ?? '');

  // validations
  if ($values['type_p'] === '')            $errors['type_p'] = 'Catégorie requise';
  if ($values['designation_p'] === '')     $errors['designation_p'] = 'Désignation requise';
  if ($values['prix_ht'] === '' || !is_numeric($values['prix_ht']) || (float)$values['prix_ht'] < 0)
                                           $errors['prix_ht'] = 'Prix HT invalide';
  if ($values['date_in'] === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/',$values['date_in']))
                                           $errors['date_in'] = 'Date invalide (YYYY-MM-DD)';
  if ($values['stock_p'] === '' || !ctype_digit($values['stock_p']))
                                           $errors['stock_p'] = 'Stock invalide (entier ≥ 0)';
  if ($values['ppromo'] !== '' && (!is_numeric($values['ppromo']) || (float)$values['ppromo'] < 0 || (float)$values['ppromo'] > 100))
                                           $errors['ppromo'] = 'Pourcentage entre 0 et 100';

  // upload image (facultatif)
  $imageFileName = null;
  try {
    $imageFileName = handle_image_upload('image_p'); // peut rester null (image par défaut à l'affichage)
  } catch (Throwable $ex) {
    $errors['image_p'] = $ex->getMessage();
  }

  if (!$errors) {
    $stmt = $pdo->prepare("
      INSERT INTO produit (type_p, designation_p, prix_ht, date_in, stock_p, ppromo, image_path)
      VALUES (:type_p, :designation_p, :prix_ht, :date_in, :stock_p, :ppromo, :image_path)
    ");
    $stmt->execute([
      ':type_p'       => $values['type_p'],
      ':designation_p'=> $values['designation_p'],
      ':prix_ht'      => $values['prix_ht'],
      ':date_in'      => $values['date_in'],
      ':stock_p'      => $values['stock_p'],
      ':ppromo'       => ($values['ppromo'] === '' ? null : $values['ppromo']),
      ':image_path'   => $imageFileName
    ]);
    header('Location: ' . BASE_URL . '/produits/produit_index.php');
    exit;
  }
}

$categories = load_categories();
include __DIR__ . '/../includes/header.php';
?>
<h1 class="h3 mb-3">Nouveau produit</h1>

<?php if ($errors): ?>
<div class="alert alert-danger">
  <strong>Veuillez corriger :</strong>
  <ul class="mb-0">
    <?php foreach ($errors as $e) echo '<li>'.htmlspecialchars($e).'</li>'; ?>
  </ul>
</div>
<?php endif; ?>

<form method="post" enctype="multipart/form-data" class="row g-3">
  <input type="hidden" name="csrf" value="<?= csrf_token() ?>">

  <div class="col-md-6">
    <label class="form-label">Catégorie</label>
    <select name="type_p" id="type_p" class="form-select <?= isset($errors['type_p'])?'is-invalid':'' ?>">
      <option value="" disabled <?= $values['type_p']===''?'selected':'' ?>>— Choisir —</option>
      <?php foreach ($categories as $cat): ?>
        <option value="<?= htmlspecialchars($cat['categorie']) ?>" <?= $values['type_p']===$cat['categorie']?'selected':'' ?>>
          <?= htmlspecialchars($cat['categorie']) ?>
        </option>
      <?php endforeach; ?>
    </select>
    <div class="invalid-feedback"><?= $errors['type_p'] ?? '' ?></div>
  </div>

  <div class="col-md-6">
    <label class="form-label">Désignation</label>
    <input name="designation_p" class="form-control <?= isset($errors['designation_p'])?'is-invalid':'' ?>" value="<?= htmlspecialchars($values['designation_p']) ?>">
    <div class="invalid-feedback"><?= $errors['designation_p'] ?? '' ?></div>
  </div>

  <div class="col-md-4">
    <label class="form-label">Prix HT (€)</label>
    <input name="prix_ht" type="number" step="0.01" min="0" class="form-control <?= isset($errors['prix_ht'])?'is-invalid':'' ?>" value="<?= htmlspecialchars($values['prix_ht']) ?>">
    <div class="invalid-feedback"><?= $errors['prix_ht'] ?? '' ?></div>
  </div>

  <div class="col-md-4">
    <label class="form-label">Date d’entrée</label>
    <input name="date_in" type="date" class="form-control <?= isset($errors['date_in'])?'is-invalid':'' ?>" value="<?= htmlspecialchars($values['date_in']) ?>">
    <div class="invalid-feedback"><?= $errors['date_in'] ?? '' ?></div>
  </div>

  <div class="col-md-4">
    <label class="form-label">Stock</label>
    <input name="stock_p" type="number" min="0" step="1" class="form-control <?= isset($errors['stock_p'])?'is-invalid':'' ?>" value="<?= htmlspecialchars($values['stock_p']) ?>">
    <div class="invalid-feedback"><?= $errors['stock_p'] ?? '' ?></div>
  </div>

  <div class="col-md-4">
    <label class="form-label">Promotion (%)</label>
    <input name="ppromo" type="number" step="0.01" min="0" max="100" class="form-control <?= isset($errors['ppromo'])?'is-invalid':'' ?>" value="<?= htmlspecialchars($values['ppromo']) ?>">
    <div class="invalid-feedback"><?= $errors['ppromo'] ?? '' ?></div>
  </div>

  <div class="col-md-8">
    <label class="form-label">Image produit</label>
    <input type="file" name="image_p" accept="image/*" class="form-control <?= isset($errors['image_p'])?'is-invalid':'' ?>">
    <div class="invalid-feedback"><?= $errors['image_p'] ?? '' ?></div>
    <div class="form-text">JPEG/PNG/WebP/GIF, 2 Mo max. Si aucune image n’est sélectionnée, une image par défaut sera utilisée.</div>
  </div>

  <div class="col-12">
    <a href="<?= BASE_URL ?>/produits/produit_index.php" class="btn btn-light">Annuler</a>
    <button class="btn btn-primary">Enregistrer</button>
  </div>
</form>

<!-- (facultatif) recharge côté client depuis le JSON -->
<script>
(async function(){
  try {
    const res = await fetch('<?= BASE_URL ?>/data/categories.json', {cache:'no-store'});
    if (!res.ok) return;
    const cats = await res.json();
    const sel = document.getElementById('type_p');
    if (!sel) return;
    const current = sel.value;

    //  on reconstruit le select avec le placeholder
    sel.innerHTML = '<option value="" disabled>— Choisir —</option>';
    cats.forEach(c=>{
      const opt = document.createElement('option');
      opt.value = c.categorie;
      opt.textContent = c.categorie;
      if (c.categorie === current) opt.selected = true;
      sel.appendChild(opt);
    });
    if (!current) sel.firstElementChild.selected = true;
  } catch(e) {}
})();
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
