<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

require_login();
require_role(['admin']);

$q     = trim($_GET['q'] ?? '');
$page  = max(1, (int)($_GET['page'] ?? 1));
$per   = 12;
$off   = ($page-1)*$per;

$where = 'WHERE 1=1';
$params = [];
if ($q !== '') {
  $where .= ' AND (email LIKE :q OR role LIKE :q)';
  $params[':q'] = '%'.$q.'%';
}

// total
$st = $pdo->prepare("SELECT COUNT(*) FROM utilisateur $where");
$st->execute($params);
$total = (int)$st->fetchColumn();
$pages = max(1, (int)ceil($total/$per));

// page
$sql = "SELECT id, email, role FROM utilisateur $where ORDER BY id DESC LIMIT :lim OFFSET :off";
$st = $pdo->prepare($sql);
foreach ($params as $k=>$v) $st->bindValue($k,$v);
$st->bindValue(':lim', $per, PDO::PARAM_INT);
$st->bindValue(':off', $off, PDO::PARAM_INT);
$st->execute();
$users = $st->fetchAll();

include __DIR__ . '/../includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="h4 mb-0">Utilisateurs</h1>
  <a class="btn btn-primary" href="<?= BASE_URL ?>/utilisateurs/user_create.php">+ Créer</a>
</div>

<form class="row g-2 mb-3" method="get">
  <div class="col-md-6">
    <input name="q" class="form-control" value="<?= htmlspecialchars($q) ?>" placeholder="Rechercher email ou rôle...">
  </div>
  <div class="col-md-2">
    <button class="btn btn-outline-secondary w-100">Rechercher</button>
  </div>
</form>

<div class="table-responsive">
<table class="table table-striped align-middle">
  <thead>
    <tr>
      <th>#</th>
      <th>Email</th>
      <th>Rôle</th>
      <th class="text-nowrap">Actions</th>
    </tr>
  </thead>
  <tbody>
  <?php if (!$users): ?>
    <tr><td colspan="4" class="text-center text-muted">Aucun utilisateur.</td></tr>
  <?php else: foreach ($users as $u): ?>
    <tr>
      <td><?= (int)$u['id'] ?></td>
      <td><?= htmlspecialchars($u['email']) ?></td>
      <td><span class="badge text-bg-secondary"><?= htmlspecialchars($u['role']) ?></span></td>
      <td class="text-nowrap">
        <a class="btn btn-sm btn-warning" href="<?= BASE_URL ?>/utilisateurs/user_edit.php?id=<?= (int)$u['id'] ?>">Éditer</a>
        <a class="btn btn-sm btn-secondary" href="<?= BASE_URL ?>/utilisateurs/user_reset_password.php?id=<?= (int)$u['id'] ?>">Réinit. MDP</a>
        <?php if ((int)$u['id'] !== (int)($_SESSION['user']['id'])): ?>
          <a class="btn btn-sm btn-outline-danger" href="<?= BASE_URL ?>/utilisateurs/user_delete.php?id=<?= (int)$u['id'] ?>">Supprimer</a>
        <?php else: ?>
          <button class="btn btn-sm btn-outline-secondary" disabled>Suppression interdite (vous)</button>
        <?php endif; ?>
      </td>
    </tr>
  <?php endforeach; endif; ?>
  </tbody>
</table>
</div>

<?php if ($pages>1): ?>
<nav aria-label="Pagination">
  <ul class="pagination">
    <?php for ($i=1;$i<=$pages;$i++): ?>
      <li class="page-item <?= $i===$page?'active':'' ?>">
        <a class="page-link" href="?<?= http_build_query(['q'=>$q,'page'=>$i]) ?>"><?= $i ?></a>
      </li>
    <?php endfor; ?>
  </ul>
</nav>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
