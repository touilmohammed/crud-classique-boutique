<?php
define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'boutique_db');
define('DB_USER', 'root');   // par défaut XAMPP
define('DB_PASS', '');       // mot de passe vide par défaut
define('BASE_URL', '/crud-classique'); // http://localhost/crud-classique
// Nom de la boutique et logos/icônes
define('SITE_NAME',       'Corsify'); 
define('SITE_LOGO',       BASE_URL . '/assets/img/logo.png'); 
// Icônes onglet / favicon 
define('FAVICON_32',      BASE_URL . '/assets/img/favicon-32x32.png');
define('FAVICON_16',      BASE_URL . '/assets/img/favicon-16x16.png');
define('APPLE_TOUCH_ICON',BASE_URL . '/assets/img/apple-touch-icon.png');
define('FAVICON_ICO',     BASE_URL . '/assets/img/favicon.ico');
// ---- Panier : TVA et livraison ----
define('TVA_RATE', 0.20);                  // 20 %
define('SHIPPING_FLAT', 4.99);             // Frais fixes si sous-total HT < seuil
define('FREE_SHIPPING_THRESHOLD', 79.00);  // Livraison offerte à partir de ce montant HT
