<?php
// =====================================================================
//  request_password_reset.php — DEMANDER un lien de reinitialisation
//  Appel : POST http://localhost/acteurs-plus-api/request_password_reset.php
//  Body JSON : { "email":"..." }
// ---------------------------------------------------------------------
//  SECURITE (non negociable) :
//   - ANTI-ENUMERATION : reponse TOUJOURS identique, que l'email existe ou non.
//     On ne revele JAMAIS si un compte existe (meme principe que login.php).
//   - RATE-LIMIT (action 'pwreset', par IP+email et par IP) pour stopper le spam.
//   - Token aleatoire bin2hex(random_bytes(32)), expiration 1h, usage unique
//     (colonne password_resets.used).
//   - DEV (APP_ENV != 'prod') : le lien est logge ET renvoye dans la reponse
//     (debug_reset_link) pour pouvoir tester sans email. PROD : rien n'est expose
//     (le vrai envoi par email sera branche a la mise en ligne).
//   - Requetes preparees PDO.
// =====================================================================

require_once 'config.php';        // $pdo, $IS_PROD, json_response, get_json_input
require_once 'rate_limit.php';    // client_ip, rl_blocked, rl_hit

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['ok' => false, 'error' => 'Methode non autorisee'], 405);
}

// ---- FRONT_BASE : base URL du front pour construire le lien de reset ----
//  DEV  : front Vite (http://localhost:5175).
//  PROD : DEFINIR $FRONT_BASE dans config.local.php (= domaine HTTPS du front,
//         ex. 'https://acteurs-plus.com'). Mirror exact du pattern config.php.
//  -> A AJUSTER ICI/EN CONFIG le jour du deploiement.
$FRONT_BASE = isset($FRONT_BASE) ? $FRONT_BASE : (getenv('FRONT_BASE') ?: 'http://localhost:5175');

// Reponse generique unique : reutilisee dans TOUS les cas (anti-enumeration).
$GENERIC = ['ok' => true, 'message' => "Si un compte existe, un lien de réinitialisation a été généré."];

$input = get_json_input();
$email = trim($input['email'] ?? '');

// Validation minimale : email vide/invalide -> on repond quand meme generique
// (ne rien reveler, et ne pas creer de bruit en base).
if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    json_response($GENERIC);
}

// ---- Rate-limit : on borne les DEMANDES (pas plus de 5 / email / h, 20 / IP / h) ----
//  Si bloque : on renvoie la MEME reponse generique (aucune distinction visible).
$ip       = client_ip();
$ipKey    = 'ip:' . $ip;
$comboKey = $ip . '|' . mb_strtolower($email);
if (rl_blocked($pdo, 'pwreset', $comboKey, 5, 3600) ||
    rl_blocked($pdo, 'pwreset', $ipKey, 20, 3600)) {
    json_response($GENERIC);
}
// Chaque demande compte (sous les deux cles), qu'elle aboutisse ou non.
rl_hit($pdo, 'pwreset', $comboKey);
rl_hit($pdo, 'pwreset', $ipKey);

// ---- Recherche du compte (requete preparee) ----
$stmt = $pdo->prepare('SELECT id FROM users WHERE email = :email');
$stmt->execute([':email' => $email]);
$user = $stmt->fetch();

// Email inconnu : on s'arrete ICI mais on renvoie la MEME reponse (anti-enum).
// Aucune ligne creee, aucune fuite.
if (!$user) {
    json_response($GENERIC);
}

// ---- Compte existant : on genere un token a usage unique, valable 1h ----
$token      = bin2hex(random_bytes(32));
$expires_at = date('Y-m-d H:i:s', strtotime('+1 hour'));

$ins = $pdo->prepare(
    'INSERT INTO password_resets (user_id, token, expires_at, used, created_at)
     VALUES (:user_id, :token, :expires_at, 0, NOW())'
);
$ins->execute([
    ':user_id'    => (int) $user['id'],
    ':token'      => $token,
    ':expires_at' => $expires_at,
]);

// Lien que l'utilisateur ouvrira (la page front lira ?token=...).
$resetLink = $FRONT_BASE . '/reinitialiser-mot-de-passe?token=' . $token;

// ---- DEV : on logge le lien ET on l'expose pour pouvoir tester ----
// PROD : on n'expose RIEN (envoi par email a venir).
$response = $GENERIC;
if (!$IS_PROD) {
    error_log('[acteurs-plus] reset link (DEV) for ' . $email . ' : ' . $resetLink);
    $response['debug_reset_link'] = $resetLink;
}

json_response($response);
