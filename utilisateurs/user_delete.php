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

// Interdit de se supprimer soi-même
if ((int)$user['id'] === (int)($_SESSION['user']['id'])) {
  http_response_code(400);
  die('Vous ne pouvez pas supprimer votre propre compte.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $st = $pdo->prepare("DELETE FROM utilisateur WHERE id=:id");
  $st->execute([':id'=>$id]);
  header('Location: ' . BASE_URL . '/utilisateurs/user_index.php');
  exit;
}

include __DIR__ . '/../includes/header.php';
?>
<h1 class="h4 mb-3">Supprimer l’utilisateur #<?= (int)$user['id'] ?></h1>

<div class="card">
  <div class="card-body">
    <p><strong>Email :</strong> <?= htmlspecialchars($user['email']) ?></p>
    <p><strong>Rôle :</strong> <span class="badge text-bg-secondary"><?= htmlspecialchars($user['role']) ?></span></p>

    <div class="alert alert-warning">
      <strong>Attention :</strong> cette action est irréversible.
    </div>

    <form method="post">
      <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
      <a class="btn btn-light" href="<?= BASE_URL ?>/utilisateurs/user_index.php">Annuler</a>
      <button class="btn btn-danger">Confirmer la suppression</button>
    </form>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
