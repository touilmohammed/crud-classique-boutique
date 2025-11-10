<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

require_login();
require_role(['admin','editeur']);
verify_csrf();

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM produit WHERE id_p=:id");
$stmt->execute([':id'=>$id]);
$produit = $stmt->fetch();
if (!$produit) { http_response_code(404); die('Produit introuvable'); }

$errors = [];
$values = $produit;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $values['type_p']        = trim($_POST['type_p'] ?? '');
  $values['designation_p'] = trim($_POST['designation_p'] ?? '');
  $values['prix_ht']       = trim($_POST['prix_ht'] ?? '');
  $values['date_in']       = trim($_POST['date_in'] ?? '');
  $values['stock_p']       = trim($_POST['stock_p'] ?? '');
  $values['ppromo']        = trim($_POST['ppromo'] ?? '');

  // validations
  if ($values['type_p'] === '')            $errors['type_p'] = 'Catégorie requise';
  if ($values['designation_p'] === '')     $errors['designation_p'] = 'Désignation requise';
  if ($values['prix_ht'] === '' || !is_numeric($values['prix_ht']) || (float)$values['prix_ht'] < 0)
                                           $errors['prix_ht'] = 'Prix HT invalide';
  if ($values['date_in'] === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/',$values['date_in']))
                                           $errors['date_in'] = 'Date invalide';
  if ($values['stock_p'] === '' || !ctype_digit((string)$values['stock_p']))
                                           $errors['stock_p'] = 'Stock invalide';
  if ($values['ppromo'] !== '' && (!is_numeric($values['ppromo']) || (float)$values['ppromo'] < 0 || (float)$values['ppromo'] > 100))
                                           $errors['ppromo'] = 'Pourcentage entre 0 et 100';

  // historisation (à partir de l'ANCIEN produit)
  $prix_avant  = (float)$produit['prix_ht'];
  $promo_avant = $produit['ppromo'];
  $chgPrix     = ($values['prix_ht'] !== '' && (float)$values['prix_ht'] !== $prix_avant);
  $newPromo    = ($values['ppromo'] === '' ? null : (float)$values['ppromo']);
  $chgPromo    = ($newPromo != $promo_avant);

  // gestion image : upload éventuel
  $newImage = null;
  try {
    $newImage = handle_image_upload('image_p'); // null si pas de fichier
  } catch (Throwable $ex) {
    $errors['image_p'] = $ex->getMessage();
  }

  if (!$errors) {
    // si prix ou promo changent -> on stocke l'ANCIEN état dans l'historique
    if ($chgPrix || $chgPromo) {
      $stmtH = $pdo->prepare("
        INSERT INTO produit_prix_histo (produit_id, prix_ht, ppromo)
        VALUES (:pid, :prix_ht, :ppromo)
      ");
      $stmtH->execute([
        ':pid'     => $id,
        ':prix_ht' => $prix_avant,
        ':ppromo'  => $promo_avant
      ]);
    }

    // si nouvelle image -> on supprime l'ancienne du disque et on met la nouvelle
    $image_to_store = $produit['image_path'];
    if ($newImage) {
      delete_image_file($produit['image_path']);
      $image_to_store = $newImage;
    }

    // UPDATE
    $stmt = $pdo->prepare("
      UPDATE produit
      SET type_p=:type_p,
          designation_p=:designation_p,
          prix_ht=:prix_ht,
          date_in=:date_in,
          stock_p=:stock_p,
          ppromo=:ppromo,
          image_path=:image_path
      WHERE id_p=:id
    ");
    $stmt->execute([
      ':type_p'        => $values['type_p'],
      ':designation_p' => $values['designation_p'],
      ':prix_ht'       => $values['prix_ht'],
      ':date_in'       => $values['date_in'],
      ':stock_p'       => $values['stock_p'],
      ':ppromo'        => ($values['ppromo'] === '' ? null : $values['ppromo']),
      ':image_path'    => $image_to_store,
      ':id'            => $id
    ]);

    header('Location: ' . BASE_URL . '/produits/produit_index.php');
    exit;
  }
}

$categories = load_categories();
include __DIR__ . '/../includes/header.php';
?>
<h1 class="h3 mb-3">Modifier produit #<?= (int)$id ?></h1>

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
    <label class="form-label">Image actuelle</label><br>
    <img src="<?= image_url_or_default($produit['image_path']) ?>" alt="" style="width:120px;height:120px;object-fit:cover;border-radius:8px;">
  </div>

  <div class="col-12">
    <label class="form-label">Remplacer l’image</label>
    <input type="file" name="image_p" accept="image/*" class="form-control <?= isset($errors['image_p'])?'is-invalid':'' ?>">
    <div class="invalid-feedback"><?= $errors['image_p'] ?? '' ?></div>
    <div class="form-text">Laisse vide pour conserver l'image actuelle.</div>
  </div>

  <div class="col-12">
    <a href="<?= BASE_URL ?>/produits/produit_index.php" class="btn btn-light">Annuler</a>
    <button class="btn btn-primary">Mettre à jour</button>
  </div>
</form>

<!-- recharge coté client des catégories -->
<script>
(async function(){
  try {
    const res = await fetch('<?= BASE_URL ?>/data/categories.json', {cache:'no-store'});
    if (!res.ok) return;
    const cats = await res.json();
    const sel = document.getElementById('type_p');
    if (!sel) return;
    const current = sel.value;
    sel.innerHTML = '';
    cats.forEach(c=>{
      const opt = document.createElement('option');
      opt.value = c.categorie;
      opt.textContent = c.categorie;
      if (c.categorie === current) opt.selected = true;
      sel.appendChild(opt);
    });
  } catch(e) {}
})();
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
