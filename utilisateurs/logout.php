<?php
// Toujours commencer par charger config (définit BASE_URL), puis auth
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

/* Vider les données de session */
$_SESSION = [];

/* Invalider le cookie de session si utilisé */
if (ini_get('session.use_cookies')) {
  $params = session_get_cookie_params();
  setcookie(
    session_name(),
    '',
    time() - 42000,
    $params['path'],
    $params['domain'],
    $params['secure'],
    $params['httponly']
  );
}

/* Détruire la session côté serveur */
session_destroy();

/* Désactiver le cache pour cette réponse */
send_no_cache_headers();

/* (Optionnel, recommandé) redémarrer une session “neuve” et régénérer l’ID
      => évite toute réutilisation d’un ancien identifiant par le navigateur */
session_start();
session_regenerate_id(true);

/* Redirection vers la page de login */
header('Location: ' . BASE_URL . '/utilisateurs/login.php');
exit;
