<?php
// =====================================================================
//  login.php — se connecter
//  Appel : POST http://localhost/acteurs-plus-api/login.php
//  Body JSON : { "email":"...", "password":"..." }
// =====================================================================

require_once 'config.php';
require_once 'rate_limit.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['ok' => false, 'error' => 'Methode non autorisee'], 405);
}

$input = get_json_input();
$email    = trim($input['email'] ?? '');
$password = $input['password'] ?? '';

if ($email === '' || $password === '') {
    json_response(['ok' => false, 'error' => 'Email et mot de passe sont obligatoires'], 422);
}

// ---- Anti-brute-force : on borne les ECHECS de connexion ----
//  - cle fine (IP + email) : 5 echecs / 15 min  -> stoppe le guessing cible.
//  - cle large (IP seule)  : 20 echecs / 15 min -> stoppe le guessing multi-emails.
//  Un login REUSSI efface le compteur (cf. plus bas) : l'utilisateur qui se
//  trompe 1-2 fois puis reussit n'est jamais penalise.
$ip       = client_ip();
$ipKey    = 'ip:' . $ip;
$comboKey = $ip . '|' . mb_strtolower($email);
if (rl_blocked($pdo, 'login', $comboKey, 5, 900) ||
    rl_blocked($pdo, 'login', $ipKey, 20, 900)) {
    json_response(['ok' => false, 'error' => 'Trop de tentatives. Réessayez dans quelques minutes.'], 429);
}

// ---- On recupere l'utilisateur par email (requete preparee) ----
$stmt = $pdo->prepare(
    'SELECT id, name, email, password_hash, user_type, image, account_status
     FROM users WHERE email = :email'
);
$stmt->execute([':email' => $email]);
$user = $stmt->fetch();

// Message VOLONTAIREMENT identique si email inconnu OU mauvais mot de passe :
// on ne revele jamais a un attaquant lequel des deux est faux.
if (!$user || !password_verify($password, $user['password_hash'])) {
    // Echec : on enregistre la tentative sous les deux cles (IP+email et IP).
    rl_hit($pdo, 'login', $comboKey);
    rl_hit($pdo, 'login', $ipKey);
    json_response(['ok' => false, 'error' => 'Email ou mot de passe incorrect'], 401);
}

// Compte banni ?
if ($user['account_status'] === 'banned') {
    json_response(['ok' => false, 'error' => 'Ce compte est suspendu'], 403);
}

// ---- Connexion OK : on efface le compteur d'echecs de cette IP+email ----
rl_clear($pdo, 'login', $comboKey);

// ---- Connexion OK — on cree un jeton de session (valable 30 jours) ----
$token      = bin2hex(random_bytes(32));
$expires_at = date('Y-m-d H:i:s', strtotime('+30 days'));

$sessionStmt = $pdo->prepare(
    'INSERT INTO sessions (token, user_id, expires_at)
     VALUES (:token, :user_id, :expires_at)'
);
$sessionStmt->execute([
    ':token'      => $token,
    ':user_id'    => (int) $user['id'],
    ':expires_at' => $expires_at,
]);

// ---- On renvoie le jeton + l'utilisateur SANS le hash ----
json_response([
    'ok'    => true,
    'token' => $token,
    'user'  => [
        'id'        => (int) $user['id'],
        'name'      => $user['name'],
        'email'     => $user['email'],
        'user_type' => $user['user_type'],
        'image'     => $user['image'],
    ],
]);
