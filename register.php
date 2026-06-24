<?php
// =====================================================================
//  register.php — creer un compte
//  Appel : POST http://localhost/acteurs-plus-api/register.php
//  Body JSON : { "name":"...", "email":"...", "password":"...", "user_type":"talent" }
// =====================================================================

require_once 'config.php';

// On n'accepte que le POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['ok' => false, 'error' => 'Methode non autorisee'], 405);
}

$input = get_json_input();

$name     = trim($input['name'] ?? '');
$email    = trim($input['email'] ?? '');
$password = $input['password'] ?? '';
$userType = trim($input['user_type'] ?? 'talent');

// ---- Validation ----
if ($name === '' || $email === '' || $password === '') {
    json_response(['ok' => false, 'error' => 'Nom, email et mot de passe sont obligatoires'], 422);
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    json_response(['ok' => false, 'error' => 'Email invalide'], 422);
}
if (strlen($password) < 6) {
    json_response(['ok' => false, 'error' => 'Le mot de passe doit faire au moins 6 caracteres'], 422);
}

// ---- Email deja utilise ? (requete preparee) ----
$stmt = $pdo->prepare('SELECT id FROM users WHERE email = :email');
$stmt->execute([':email' => $email]);
if ($stmt->fetch()) {
    json_response(['ok' => false, 'error' => 'Cet email est deja utilise'], 409);
}

// ---- Hachage du mot de passe (JAMAIS stocke en clair) ----
$hash = password_hash($password, PASSWORD_DEFAULT);

// Sexe : on n'accepte QUE 'H' ou 'F' (sinon NULL — comptes sans genre renseigne).
$gender = (isset($input['gender']) && ($input['gender'] === 'H' || $input['gender'] === 'F'))
    ? $input['gender']
    : null;

// ---- Insertion (requete preparee = pas d'injection SQL possible) ----
$stmt = $pdo->prepare(
    'INSERT INTO users (name, email, password_hash, user_type, gender)
     VALUES (:name, :email, :hash, :type, :gender)'
);
$stmt->execute([
    ':name'   => $name,
    ':email'  => $email,
    ':hash'   => $hash,
    ':type'   => $userType,
    ':gender' => $gender,
]);

$userId = (int) $pdo->lastInsertId();

// ---- Reponse (on ne renvoie JAMAIS le mot de passe) ----
json_response([
    'ok'   => true,
    'user' => [
        'id'        => $userId,
        'name'      => $name,
        'email'     => $email,
        'user_type' => $userType,
    ],
], 201);
