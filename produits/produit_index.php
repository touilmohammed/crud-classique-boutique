<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

require_login(); 

// --- Filtres / tri / pagination ---
$q     = trim($_GET['q'] ?? '');
$sort  = $_GET['sort'] ?? 'id_p';
$dir   = strtolower($_GET['dir'] ?? 'desc') === 'asc' ? 'ASC' : 'DESC';
$page  = max(1, (int)($_GET['page'] ?? 1));
$perPage = 10;
$offset  = ($page - 1) * $perPage;

$where  = 'WHERE 1=1';
$params = [];
if ($q !== '') {
  $where .= ' AND (type_p LIKE :q OR designation_p LIKE :q)';
  $params[':q'] = '%' . $q . '%';
}

$allowedSort = ['id_p','type_p','designation_p','prix_ht','date_in','stock_p','timeS_in','ppromo'];
if (!in_array($sort, $allowedSort, true)) $sort = 'id_p';

// total
$stmt = $pdo->prepare("SELECT COUNT(*) FROM produit $where");
$stmt->execute($params);
$total = (int)$stmt->fetchColumn();
$pages = max(1, (int)ceil($total / $perPage));

// page
$sql = "SELECT * FROM produit $where ORDER BY $sort $dir LIMIT :limit OFFSET :offset";
$stmt = $pdo->prepare($sql);
foreach ($params as $k=>$v) $stmt->bindValue($k, $v);
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$produits = $stmt->fetchAll();

include __DIR__ . '/../includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="h3 mb-0">Produits</h1>
  <?php if (in_array(current_user_role(), ['admin','editeur'], true)): ?>
    <a class="btn btn-primary" href="<?= BASE_URL ?>/produits/produit_create.php">+ Nouveau produit</a>
  <?php endif; ?>
</div>

<form class="row g-2 mb-3" method="get">
  <div class="col-md-4">
    <input type="text" name="q" value="<?= htmlspecialchars($q) ?>" class="form-control" placeholder="Rechercher type ou désignation...">
  </div>
  <div class="col-md-3">
    <?php
    $labels = [
      'id_p' => 'ID',
      'type_p' => 'Type',
      'designation_p' => 'Désignation',
      'prix_ht' => 'Prix HT',
      'date_in' => 'Date d’entrée',
      'stock_p' => 'Stock',
      'timeS_in' => 'Créé le',
      'ppromo' => 'Promo (%)',
    ];
    ?>
    <select name="sort" class="form-select">
      <?php foreach ($allowedSort as $col): ?>
        <option value="<?= $col ?>" <?= $col===$sort?'selected':'' ?>>Trier par <?= htmlspecialchars($labels[$col] ?? $col) ?>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="col-md-2">
    <select name="dir" class="form-select">
      <option value="asc"  <?= $dir==='ASC'?'selected':'' ?>>Ascendant</option>
      <option value="desc" <?= $dir==='DESC'?'selected':'' ?>>Descendant</option>
    </select>
  </div>
  <div class="col-md-3">
    <button class="btn btn-outline-secondary w-100">Appliquer</button>
  </div>
</form>

<div class="table-responsive">
<table class="table table-striped table-hover align-middle">
  <thead>
    <tr>
      <th>Image</th>
      <th>#</th>
      <th>Catégorie</th>
      <th>Désignation</th>
      <th>Prix</th>
      <th>Stock</th>
      <th>Créé</th>
      <th class="text-nowrap">Actions</th>
    </tr>
  </thead>
  <tbody>
  <?php if (!$produits): ?>
    <tr><td colspan="8" class="text-center text-muted">Aucun produit pour l’instant</td></tr>
  <?php else: foreach ($produits as $p): ?>
    <tr>
      <td style="width:72px">
        <img src="<?= image_url_or_default($p['image_path'] ?? null) ?>" alt=""
             style="width:60px;height:60px;object-fit:cover;border-radius:6px;">
      </td>
      <td><?= (int)$p['id_p'] ?></td>
      <td><?= htmlspecialchars($p['type_p']) ?></td>
      <td><?= htmlspecialchars($p['designation_p']) ?></td>
      <td>
        <?php
          $final = prix_final((float)$p['prix_ht'], $p['ppromo']);
          if ($p['ppromo'] !== null && (float)$p['ppromo'] > 0): ?>
            <span class="badge text-bg-danger me-1">-<?= (float)$p['ppromo'] ?>%</span>
            <span class="text-decoration-line-through text-muted me-1"><?= fmt_eur($p['prix_ht']) ?></span>
            <strong><?= fmt_eur($final) ?></strong>
        <?php else: ?>
            <strong><?= fmt_eur($p['prix_ht']) ?></strong>
        <?php endif; ?>
      </td>
      <td><?= (int)$p['stock_p'] ?></td>
      <td><?= htmlspecialchars($p['timeS_in']) ?></td>
      <td class="text-nowrap">
        <!-- Historique (toujours autorisé) -->
        <a class="btn btn-sm btn-secondary" href="<?= BASE_URL ?>/produits/produit_history.php?id=<?= (int)$p['id_p'] ?>">Historique</a>

        <!-- + Panier (pour tout utilisateur connecté) -->
        <?php if ((int)$p['stock_p'] > 0): ?>
          <form method="post" action="<?= BASE_URL ?>/produits/cart_add.php" class="d-inline">
            <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
            <input type="hidden" name="id" value="<?= (int)$p['id_p'] ?>">
            <input type="hidden" name="qty" value="1">
            <button class="btn btn-sm btn-success">+ Panier</button>
          </form>
        <?php else: ?>
          <button class="btn btn-sm btn-outline-secondary" disabled>Rupture</button>
        <?php endif; ?>

        <!-- Éditer (éditeur/admin) -->
        <?php if (in_array(current_user_role(), ['admin','editeur'], true)): ?>
          <a class="btn btn-sm btn-warning" href="<?= BASE_URL ?>/produits/produit_edit.php?id=<?= (int)$p['id_p'] ?>">Éditer</a>
        <?php endif; ?>

        <!-- Supprimer (admin) -->
        <?php if (current_user_role()==='admin'): ?>
          <a class="btn btn-sm btn-outline-danger" href="<?= BASE_URL ?>/produits/produit_delete.php?id=<?= (int)$p['id_p'] ?>">Supprimer</a>
        <?php endif; ?>
      </td>
    </tr>
  <?php endforeach; endif; ?>
  </tbody>
</table>
</div>

<?php if ($pages > 1): ?>
<nav aria-label="Pagination">
  <ul class="pagination">
    <?php for ($i=1; $i<=$pages; $i++): ?>
      <li class="page-item <?= $i===$page?'active':'' ?>">
        <a class="page-link" href="?<?= http_build_query(['q'=>$q,'sort'=>$sort,'dir'=>strtolower($dir),'page'=>$i]) ?>"><?= $i ?></a>
      </li>
    <?php endfor; ?>
  </ul>
</nav>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
