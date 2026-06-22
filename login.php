<?php
// =====================================================================
//  login.php — se connecter
//  Appel : POST http://localhost/acteurs-plus-api/login.php
//  Body JSON : { "email":"...", "password":"..." }
// =====================================================================

require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['ok' => false, 'error' => 'Methode non autorisee'], 405);
}

$input = get_json_input();
$email    = trim($input['email'] ?? '');
$password = $input['password'] ?? '';

if ($email === '' || $password === '') {
    json_response(['ok' => false, 'error' => 'Email et mot de passe sont obligatoires'], 422);
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
    json_response(['ok' => false, 'error' => 'Email ou mot de passe incorrect'], 401);
}

// Compte banni ?
if ($user['account_status'] === 'banned') {
    json_response(['ok' => false, 'error' => 'Ce compte est suspendu'], 403);
}

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
