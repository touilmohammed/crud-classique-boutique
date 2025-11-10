<?php
// Session
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

/**
 * Désactive le cache navigateur pour éviter les "pages fantômes"
 * (après login/logout, bouton retour, etc.).
 */
function send_no_cache_headers(): void {
  header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
  header('Pragma: no-cache');
  header('Expires: 0');
}

// --- Auth de base ---
function is_logged_in(): bool {
  return isset($_SESSION['user']);
}

function current_user_role(): string {
  return $_SESSION['user']['role'] ?? 'lecteur';
}

/**
 * À appeler en haut de chaque page protégée.
 * Redirige vers /utilisateurs/login.php si non connecté.
 */
function require_login(): void {
  send_no_cache_headers();

  // BASE_URL vient de includes/config.php, qui doit être chargé par la page appelante
  if (!is_logged_in()) {
    header('Location: ' . BASE_URL . '/utilisateurs/login.php');
    exit;
  }
}

/**
 * À appeler sur les pages avec restrictions par rôle (admin/editeur/lecteur).
 */
function require_role(array $roles): void {
  send_no_cache_headers();

  if (!is_logged_in() || !in_array(current_user_role(), $roles, true)) {
    http_response_code(403);
    die('Accès interdit');
  }
}

// --- CSRF ---
function csrf_token(): string {
  if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
  }
  return $_SESSION['csrf'];
}

function verify_csrf(): void {
  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $t = $_POST['csrf'] ?? '';
    if (!$t || !hash_equals($_SESSION['csrf'] ?? '', $t)) {
      http_response_code(400);
      die('Token CSRF invalide');
    }
  }
}
