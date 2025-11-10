<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

require_login();
require_role(['admin']);
verify_csrf();

$id = (int)($_GET['id'] ?? 0);
$st = $pdo->prepare("SELECT id, email FROM utilisateur WHERE id=:id");
$st->execute([':id'=>$id]);
$user = $st->fetch();
if (!$user) { http_response_code(404); die('Utilisateur introuvable'); }

$errors = [];
$ok = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $pass1 = $_POST['motdepasse']  ?? '';
  $pass2 = $_POST['motdepasse2'] ?? '';

  if (strlen($pass1) < 8) $errors['motdepasse'] = 'Minimum 8 caractères';
  if ($pass1 !== $pass2)  $errors['motdepasse2'] = 'Les mots de passe ne correspondent pas';

  if (!$errors) {
    $hash = password_hash($pass1, PASSWORD_DEFAULT);
    $st = $pdo->prepare("UPDATE utilisateur SET motdepasse_hash=:h WHERE id=:id");
    $st->execute([':h'=>$hash, ':id'=>$id]);
    $ok = true;
  }
}

include __DIR__ . '/../includes/header.php';
?>
<h1 class="h4 mb-3">Réinitialiser le mot de passe — #<?= (int)$id ?> (<?= htmlspecialchars($user['email']) ?>)</h1>

<?php if ($ok): ?>
  <div class="alert alert-success">Mot de passe mis à jour avec succès.</div>
  <a class="btn btn-primary" href="<?= BASE_URL ?>/utilisateurs/user_index.php">← Retour à la liste</a>
<?php else: ?>
  <?php if ($errors): ?>
    <div class="alert alert-danger"><ul class="mb-0">
      <?php foreach ($errors as $m) echo '<li>'.htmlspecialchars($m).'</li>'; ?>
    </ul></div>
  <?php endif; ?>

  <form method="post" class="row g-3" autocomplete="off" novalidate>
    <input type="hidden" name="csrf" value="<?= csrf_token() ?>">

    <div class="col-md-6">
      <label class="form-label">Nouveau mot de passe</label>
      <input name="motdepasse" type="password" class="form-control <?= isset($errors['motdepasse'])?'is-invalid':'' ?>" autocomplete="new-password">
      <div class="invalid-feedback"><?= $errors['motdepasse'] ?? '' ?></div>
    </div>
    <div class="col-md-6">
      <label class="form-label">Confirmer le mot de passe</label>
      <input name="motdepasse2" type="password" class="form-control <?= isset($errors['motdepasse2'])?'is-invalid':'' ?>" autocomplete="new-password">
      <div class="invalid-feedback"><?= $errors['motdepasse2'] ?? '' ?></div>
    </div>

    <div class="col-12">
      <a class="btn btn-light" href="<?= BASE_URL ?>/utilisateurs/user_index.php">Annuler</a>
      <button class="btn btn-primary">Mettre à jour</button>
    </div>
  </form>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
