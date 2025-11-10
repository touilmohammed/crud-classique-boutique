<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

require_login();
require_role(['admin']);

$errors = [];
$values = ['email'=>'','motdepasse'=>'','role'=>'lecteur'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  verify_csrf();

  $values['email']      = trim($_POST['email'] ?? '');
  $values['motdepasse'] = $_POST['motdepasse'] ?? '';
  $values['role']       = $_POST['role'] ?? 'lecteur';

  if (!filter_var($values['email'], FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Email invalide';
  if (strlen($values['motdepasse']) < 8) $errors['motdepasse'] = '8 caractères minimum';
  if (!in_array($values['role'], ['lecteur','editeur','admin'], true)) $errors['role'] = 'Rôle invalide';

  $st = $pdo->prepare("SELECT 1 FROM utilisateur WHERE email=:e");
  $st->execute([':e'=>$values['email']]);
  if ($st->fetch()) $errors['email'] = 'Email déjà utilisé';

  if (!$errors) {
    $hash = password_hash($values['motdepasse'], PASSWORD_DEFAULT);
    $st = $pdo->prepare("INSERT INTO utilisateur (email, motdepasse_hash, role) VALUES (:e,:h,:r)");
    $st->execute([':e'=>$values['email'], ':h'=>$hash, ':r'=>$values['role']]);
    header('Location: ' . BASE_URL . '/produits/produit_index.php');
    exit;
  }
}

include __DIR__ . '/../includes/header.php';
?>
<h1 class="h4 mb-3">Créer un utilisateur</h1>

<?php if ($errors): ?>
<div class="alert alert-danger"><ul class="mb-0">
  <?php foreach ($errors as $m) echo '<li>'.htmlspecialchars($m).'</li>'; ?>
</ul></div>
<?php endif; ?>

<form method="post" class="row g-3" autocomplete="off" novalidate>
  <input type="hidden" name="csrf" value="<?= csrf_token() ?>">

  <div class="col-md-6">
    <label class="form-label">Email</label>
    <input name="email" type="email" class="form-control" required
           value="<?= htmlspecialchars($values['email']) ?>" autocapitalize="off" spellcheck="false">
  </div>

  <div class="col-md-3">
    <label class="form-label">Mot de passe</label>
    <input name="motdepasse" type="password" class="form-control" required autocomplete="new-password">
  </div>

  <div class="col-md-3">
    <label class="form-label">Rôle</label>
    <select name="role" class="form-select">
      <option value="lecteur" <?= $values['role']==='lecteur'?'selected':'' ?>>lecteur</option>
      <option value="editeur" <?= $values['role']==='editeur'?'selected':'' ?>>editeur</option>
      <option value="admin"   <?= $values['role']==='admin'?'selected':'' ?>>admin</option>
    </select>
  </div>

  <div class="col-12">
    <a class="btn btn-light" href="<?= BASE_URL ?>/produits/produit_index.php">Annuler</a>
    <button class="btn btn-primary">Créer</button>
  </div>
</form>
<?php include __DIR__ . '/../includes/footer.php'; ?>
