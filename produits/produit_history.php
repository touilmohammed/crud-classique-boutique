<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

require_login();

$id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("SELECT * FROM produit WHERE id_p=:id");
$stmt->execute([':id'=>$id]);
$produit = $stmt->fetch();
if (!$produit) { http_response_code(404); die('Produit introuvable'); }

$stmt = $pdo->prepare("
  SELECT id, prix_ht, ppromo, changed_at
  FROM produit_prix_histo
  WHERE produit_id=:id
  ORDER BY changed_at DESC, id DESC
");
$stmt->execute([':id'=>$id]);
$histos = $stmt->fetchAll();

include __DIR__ . '/../includes/header.php';
?>
<h1 class="h4 mb-3">Historique des prix — #<?= (int)$id ?> / <?= htmlspecialchars($produit['designation_p']) ?></h1>

<div class="mb-3">
  <img src="<?= image_url_or_default($produit['image_path']) ?>" style="width:120px;height:120px;object-fit:cover;border-radius:8px;">
  <div class="mt-2 text-muted">
    Actuel :
    <?php if ($produit['ppromo'] !== null && (float)$produit['ppromo'] > 0): ?>
      <span class="badge text-bg-danger">-<?= (float)$produit['ppromo'] ?>%</span>
      <span class="text-decoration-line-through"><?= fmt_eur($produit['prix_ht']) ?></span>
      <strong class="ms-1"><?= fmt_eur(prix_final((float)$produit['prix_ht'], $produit['ppromo'])) ?></strong>
    <?php else: ?>
      <strong><?= fmt_eur($produit['prix_ht']) ?></strong>
    <?php endif; ?>
  </div>
</div>

<div class="table-responsive">
<table class="table table-sm table-striped align-middle">
  <thead>
    <tr>
      <th>Ancien prix HT</th>
      <th>Ancienne promo</th>
      <th>Prix final (ancien)</th>
      <th>Modifié le</th>
    </tr>
  </thead>
  <tbody>
    <?php if (!$histos): ?>
      <tr><td colspan="4" class="text-center text-muted">Aucun historique pour l’instant.</td></tr>
    <?php else: foreach ($histos as $h): ?>
      <tr>
        <td><?= fmt_eur($h['prix_ht']) ?></td>
        <td><?= $h['ppromo'] !== null ? (float)$h['ppromo'].' %' : '—' ?></td>
        <td><?= fmt_eur(prix_final((float)$h['prix_ht'], $h['ppromo'])) ?></td>
        <td><?= htmlspecialchars($h['changed_at']) ?></td>
      </tr>
    <?php endforeach; endif; ?>
  </tbody>
</table>
</div>

<a class="btn btn-light" href="<?= BASE_URL ?>/produits/produit_index.php">← Retour à la liste</a>

<?php include __DIR__ . '/../includes/footer.php'; ?>
