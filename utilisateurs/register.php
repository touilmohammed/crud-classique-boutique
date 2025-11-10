<?php
require_once __DIR__.'/../includes/db.php';
require_once __DIR__.'/../includes/auth.php';

if (is_logged_in()) { header('Location: '.BASE_URL.'/produits/produit_index.php'); exit; }

$errors = [];
$values = ['email'=>'', 'motdepasse'=>'', 'motdepasse2'=>''];

if ($_SERVER['REQUEST_METHOD']==='POST') {
  $values['email'] = trim($_POST['email'] ?? '');
  $values['motdepasse']  = $_POST['motdepasse'] ?? '';
  $values['motdepasse2'] = $_POST['motdepasse2'] ?? '';

  if (!filter_var($values['email'], FILTER_VALIDATE_EMAIL)) $errors['email'] = "Email invalide";
  if (strlen($values['motdepasse']) < 8) $errors['motdepasse'] = "8 caractères minimum";
  if ($values['motdepasse'] !== $values['motdepasse2']) $errors['motdepasse2'] = "Les mots de passe ne correspondent pas";

  // Unicité email
  $st = $pdo->prepare("SELECT 1 FROM utilisateur WHERE email=:e");
  $st->execute([':e'=>$values['email']]);
  if ($st->fetch()) $errors['email'] = "Un compte existe déjà avec cet email";

  if (!$errors) {
    $hash = password_hash($values['motdepasse'], PASSWORD_DEFAULT);
    $st = $pdo->prepare("INSERT INTO utilisateur (email, motdepasse_hash, role) VALUES (:e,:h,'lecteur')");
    $st->execute([':e'=>$values['email'], ':h'=>$hash]);
    // Option: connexion auto
    $_SESSION['user'] = ['id'=>$pdo->lastInsertId(), 'email'=>$values['email'], 'role'=>'lecteur'];
    header('Location: '.BASE_URL.'/produits/produit_index.php'); exit;
  }
}

include __DIR__.'/../includes/header.php';
?>
<h1 class="h4 mb-3">Créer un compte</h1>
<?php if($errors): ?><div class="alert alert-danger"><ul class="mb-0">
<?php foreach($errors as $m) echo '<li>'.htmlspecialchars($m).'</li>'; ?>
</ul></div><?php endif; ?>
<form method="post" class="row g-3" autocomplete="off" novalidate>
  <div class="col-12">
    <label class="form-label">Email</label>
    <input name="email" type="email" class="form-control" required autocomplete="email"
           value="<?= htmlspecialchars($values['email']) ?>" autocapitalize="off" spellcheck="false">
  </div>
  <div class="col-md-6">
    <label class="form-label">Mot de passe</label>
    <input name="motdepasse" type="password" class="form-control" required autocomplete="new-password">
  </div>
  <div class="col-md-6">
    <label class="form-label">Confirmer le mot de passe</label>
    <input name="motdepasse2" type="password" class="form-control" required autocomplete="new-password">
  </div>
  <div class="col-12">
    <button class="btn btn-primary w-100">Créer le compte</button>
  </div>
</form>
<?php include __DIR__.'/../includes/footer.php'; ?>
