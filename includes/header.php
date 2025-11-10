<?php
// S'assurer que BASE_URL est dispo
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/helpers.php';

/* Petits defaults */
$SITE_NAME        = defined('SITE_NAME')        ? SITE_NAME        : 'Mon Super Store';
$SITE_LOGO        = defined('SITE_LOGO')        ? SITE_LOGO        : (BASE_URL . '/assets/img/logo.svg');
$FAVICON_32       = defined('FAVICON_32')       ? FAVICON_32       : (BASE_URL . '/assets/img/favicon-32x32.png');
$FAVICON_16       = defined('FAVICON_16')       ? FAVICON_16       : (BASE_URL . '/assets/img/favicon-16x16.png');
$APPLE_TOUCH_ICON = defined('APPLE_TOUCH_ICON') ? APPLE_TOUCH_ICON : (BASE_URL . '/assets/img/apple-touch-icon.png');
$FAVICON_ICO      = defined('FAVICON_ICO')      ? FAVICON_ICO      : (BASE_URL . '/assets/img/favicon.ico');
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">

  <!-- Titre dynamique -->
  <title><?= htmlspecialchars($SITE_NAME) ?></title>

  <!-- Favicons -->
  <link rel="icon" type="image/png" sizes="32x32" href="<?= $FAVICON_32 ?>">
  <link rel="icon" type="image/png" sizes="16x16" href="<?= $FAVICON_16 ?>">
  <link rel="apple-touch-icon" sizes="180x180" href="<?= $APPLE_TOUCH_ICON ?>">
  <link rel="shortcut icon" href="<?= $FAVICON_ICO ?>">

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="<?= BASE_URL ?>/assets/css/corsify.css" rel="stylesheet">
  <link href="<?= BASE_URL ?>/assets/css/styles.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-dark navbar-expand-lg bg-dark mb-4">
  <div class="container">

    <!-- Logo à la place du texte -->
    <a class="navbar-brand d-flex align-items-center gap-2"
       href="<?= BASE_URL ?>/index.php"
       aria-label="<?= htmlspecialchars($SITE_NAME) ?>">
      <img src="<?= $SITE_LOGO ?>" alt="" style="height:28px;width:auto;">
    </a>

    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMain"
            aria-controls="navMain" aria-expanded="false" aria-label="Ouvrir le menu">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="navMain">
      <ul class="navbar-nav me-auto"></ul>

      <ul class="navbar-nav">
        <?php if (is_logged_in()): ?>
          <?php if (current_user_role() === 'admin'): ?>
            <!-- Menu admin -->
            <li class="nav-item dropdown">
              <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">Admin</a>
              <ul class="dropdown-menu dropdown-menu-end">
                <li><a class="dropdown-item" href="<?= BASE_URL ?>/utilisateurs/user_index.php">Utilisateurs (liste & rôles)</a></li>
                <li><a class="dropdown-item" href="<?= BASE_URL ?>/utilisateurs/user_create.php">Créer un utilisateur</a></li>
              </ul>
            </li>
          <?php endif; ?>

          <!-- Panier -->
          <li class="nav-item">
            <a class="nav-link" href="<?= BASE_URL ?>/produits/panier.php">
              Panier (<span id="cart-count"><?= cart_count() ?></span>)
            </a>
          </li>

          <!-- Infos user -->
          <?php
            // Extraire la partie avant @ de l'email
            $fullEmail = $_SESSION['user']['email'] ?? '';
            $usernamePart = $fullEmail ? explode('@', $fullEmail)[0] : 'Utilisateur';
          ?>
          <li class="nav-item">
            <span class="navbar-text ms-2 me-2">
              <?= htmlspecialchars($usernamePart) ?> (<?= htmlspecialchars(current_user_role()) ?>)
            </span>
          </li>

          <!-- Déconnexion -->
          <li class="nav-item">
            <a class="nav-link" href="<?= BASE_URL ?>/utilisateurs/logout.php">Déconnexion</a>
          </li>

        <?php else: ?>
          <!-- Non connecté : Créer un compte + Connexion -->
          <li class="nav-item">
            <a class="nav-link" href="<?= BASE_URL ?>/utilisateurs/register.php">Créer un compte</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="<?= BASE_URL ?>/utilisateurs/login.php">Connexion</a>
          </li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>

<div class="container">
