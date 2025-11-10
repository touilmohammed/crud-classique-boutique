<?php
require_once __DIR__.'/../includes/db.php';
require_once __DIR__.'/../includes/auth.php';

if (is_logged_in()) {
  header('Location: '.BASE_URL.'/produits/produit_index.php');
  exit;
}

$error = '';

// Vérif CSRF si POST (la fonction ne fait rien en GET)
verify_csrf();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // IMPORTANT: on lit désormais les champs renommés pour éviter l'autofill
  $email = trim($_POST['login_email'] ?? '');
  $pass  = $_POST['login_password'] ?? '';

  // Validation simple côté serveur
  if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $pass === '') {
    $error = 'Identifiants invalides';
  } else {
    $st = $pdo->prepare('SELECT * FROM utilisateur WHERE email=:e');
    $st->execute([':e' => $email]);
    $u = $st->fetch();

    if ($u && password_verify($pass, $u['motdepasse_hash'])) {
      $_SESSION['user'] = ['id'=>$u['id'], 'email'=>$u['email'], 'role'=>$u['role']];
      header('Location: '.BASE_URL.'/produits/produit_index.php');
      exit;
    } else {
      $error = 'Identifiants invalides';
    }
  }
}

include __DIR__.'/../includes/header.php';
?>
<h1 class="h4 mb-3">Connexion</h1>

<?php if ($error): ?>
  <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<form method="post" class="row g-3" autocomplete="off" novalidate>
  <!-- Token CSRF -->
  <input type="hidden" name="csrf" value="<?= csrf_token() ?>">

  <!-- Leurres anti-autofill : les gestionnaires vont remplir ces champs invisibles -->
  <div style="position:absolute; left:-10000px; top:auto; width:1px; height:1px; overflow:hidden;" aria-hidden="true">
    <input type="text"     name="fakeusernameremembered" autocomplete="username">
    <input type="password" name="fakepasswordremembered" autocomplete="current-password">
  </div>

  <!-- VRAIS CHAMPS (noms différents pour casser l'autofill) -->
  <div class="col-12">
    <label class="form-label">Email</label>
    <input name="login_email" type="email" class="form-control" required
           autocomplete="off" autocapitalize="off" spellcheck="false" value="">
  </div>

  <div class="col-12">
    <label class="form-label">Mot de passe</label>
    <input name="login_password" type="password" class="form-control" required
           autocomplete="new-password" value="">
  </div>

  <div class="col-12">
    <button class="btn btn-primary w-100">Se connecter</button>
  </div>

  <p class="mt-3 text-center">
    <a href="<?= BASE_URL ?>/utilisateurs/register.php">Créer un compte</a>
  </p>
</form>

<!-- Vide explicitement les champs au (re)chargement de page -->
<script>
  window.addEventListener('pageshow', function(){
    const e = document.querySelector('input[name="login_email"]');
    const p = document.querySelector('input[name="login_password"]');
    if (e) e.value = '';
    if (p) p.value = '';
  });
</script>

<?php include __DIR__.'/../includes/footer.php'; ?>
