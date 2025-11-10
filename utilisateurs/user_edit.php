<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

require_login();
require_role(['admin']);
verify_csrf();

$id = (int)($_GET['id'] ?? 0);
$st = $pdo->prepare("SELECT id, email, role FROM utilisateur WHERE id=:id");
$st->execute([':id'=>$id]);
$user = $st->fetch();
if (!$user) { http_response_code(404); die('Utilisateur introuvable'); }

$errors = [];
$values = ['email'=>$user['email'], 'role'=>$user['role']];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $values['email'] = trim($_POST['email'] ?? '');
  $values['role']  = $_POST['role'] ?? 'lecteur';

  if (!filter_var($values['email'], FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Email invalide';
  if (!in_array($values['role'], ['lecteur','editeur','admin'], true)) $errors['role'] = 'Rôle invalide';

  // unicité email (autres comptes)
  $st = $pdo->prepare("SELECT id FROM utilisateur WHERE email=:e AND id<>:id");
  $st->execute([':e'=>$values['email'], ':id'=>$id]);
  if ($st->fetch()) $errors['email'] = 'Email déjà utilisé par un autre compte';

  // garder au moins 1 admin (si tu veux empêcher de retirer le dernier admin, il faut un test supplémentaire)

  if (!$errors) {
    $st = $pdo->prepare("UPDATE utilisateur SET email=:e, role=:r WHERE id=:id");
    $st->execute([':e'=>$values['email'], ':r'=>$values['role'], ':id'=>$id]);
    header('Location: ' . BASE_URL . '/utilisateurs/user_index.php');
    exit;
  }
}

include __DIR__ . '/../includes/header.php';
?>
<h1 class="h4 mb-3">Éditer utilisateur #<?= (int)$id ?></h1>

<?php if ($errors): ?>
<div class="alert alert-danger"><ul class="mb-0">
  <?php foreach ($errors as $m) echo '<li>'.htmlspecialchars($m).'</li>'; ?>
</ul></div>
<?php endif; ?>

<form method="post" class="row g-3" autocomplete="off" novalidate>
  <input type="hidden" name="csrf" value="<?= csrf_token() ?>">

  <div class="col-md-6">
    <label class="form-label">Email</label>
    <input name="email" type="email" class="form-control <?= isset($errors['email'])?'is-invalid':'' ?>"
           value="<?= htmlspecialchars($values['email']) ?>" autocapitalize="off" spellcheck="false">
    <div class="invalid-feedback"><?= $errors['email'] ?? '' ?></div>
  </div>

  <div class="col-md-6">
    <label class="form-label">Rôle</label>
    <select name="role" class="form-select <?= isset($errors['role'])?'is-invalid':'' ?>">
      <option value="lecteur" <?= $values['role']==='lecteur'?'selected':'' ?>>lecteur</option>
      <option value="editeur" <?= $values['role']==='editeur'?'selected':'' ?>>editeur</option>
      <option value="admin"   <?= $values['role']==='admin'?'selected':'' ?>>admin</option>
    </select>
    <div class="invalid-feedback"><?= $errors['role'] ?? '' ?></div>
  </div>

  <div class="col-12">
    <a class="btn btn-light" href="<?= BASE_URL ?>/utilisateurs/user_index.php">Annuler</a>
    <button class="btn btn-primary">Enregistrer</button>
  </div>
</form>

<?php include __DIR__ . '/../includes/footer.php'; ?>
