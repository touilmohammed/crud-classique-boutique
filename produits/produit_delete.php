<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

require_login();
require_role(['admin']); // seuls les admins suppriment
verify_csrf();

$id = (int)($_GET['id'] ?? 0);

// récupérer le produit pour afficher la confirmation et supprimer l'image
$stmt = $pdo->prepare("SELECT * FROM produit WHERE id_p=:id");
$stmt->execute([':id'=>$id]);
$produit = $stmt->fetch();
if (!$produit) { http_response_code(404); die('Produit introuvable'); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // efface le fichier image si présent
  delete_image_file($produit['image_path']);

  $stmt = $pdo->prepare("DELETE FROM produit WHERE id_p=:id");
  $stmt->execute([':id'=>$id]);

  header('Location: ' . BASE_URL . '/produits/produit_index.php');
  exit;
}

include __DIR__ . '/../includes/header.php';
?>
<h1 class="h4 mb-3">Supprimer le produit #<?= (int)$id ?></h1>

<div class="card">
  <div class="card-body">
    <div class="d-flex gap-3">
      <img src="<?= image_url_or_default($produit['image_path']) ?>" style="width:100px;height:100px;object-fit:cover;border-radius:8px;">
      <div>
        <p class="mb-1"><strong>Catégorie :</strong> <?= htmlspecialchars($produit['type_p']) ?></p>
        <p class="mb-1"><strong>Désignation :</strong> <?= htmlspecialchars($produit['designation_p']) ?></p>
        <p class="mb-1"><strong>Prix HT :</strong> <?= fmt_eur($produit['prix_ht']) ?><?= $produit['ppromo']!==null && (float)$produit['ppromo']>0 ? ' (promo '.$produit['ppromo'].'%)' : '' ?></p>
      </div>
    </div>

    <hr>
    <form method="post">
      <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
      <a class="btn btn-light" href="<?= BASE_URL ?>/produits/produit_index.php">Annuler</a>
      <button class="btn btn-danger">Confirmer la suppression</button>
    </form>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
