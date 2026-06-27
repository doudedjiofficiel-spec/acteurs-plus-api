<?php
// =====================================================================
//  config.example.php — MODELE de configuration (VERSIONNE, sans secret)
//  Au deploiement : copiez ce fichier en "config.local.php" puis
//  renseignez les vraies valeurs. config.local.php n'est PAS versionne.
//      cp config.example.php config.local.php
//  config.local.php a la priorite ; a defaut, les variables
//  d'environnement (DB_HOST, DB_NAME, ...) sont utilisees.
// =====================================================================

// Environnement : 'dev' (erreurs visibles) ou 'prod' (erreurs masquees).
$APP_ENV = 'prod';

// ---- Base de donnees (user MySQL DEDIE, jamais root en prod) ----
$DB_HOST = 'localhost';
$DB_NAME = 'VOTRE_BASE';
$DB_USER = 'VOTRE_USER';
$DB_PASS = 'VOTRE_MOT_DE_PASSE';

// ---- CORS : domaine EXACT du front de production ----
$ALLOWED_ORIGIN = 'https://votre-domaine.com';

// ---- FRONT_BASE : base des liens construits par l'API (reinitialisation
//      de mot de passe). request_password_reset.php fabrique le lien
//      "<FRONT_BASE>/reinitialiser-mot-de-passe?token=...".
//      PROD : domaine HTTPS du front (souvent identique a ALLOWED_ORIGIN).
//      Si absent ici, l'API retombe sur http://localhost:5175 (dev) -> a
//      DEFINIR en prod, sinon les emails de reset pointeraient vers localhost.
$FRONT_BASE = 'https://votre-domaine.com';
