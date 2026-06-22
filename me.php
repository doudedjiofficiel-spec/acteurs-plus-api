<?php
// =====================================================================
//  me.php — renvoie l'utilisateur lie au jeton (endpoint de test/protege)
//  Appel : GET ou POST http://localhost/acteurs-plus-api/me.php
//  Header : Authorization: Bearer <token>
//  - token valide   -> {ok:true, user:{...}}
//  - token absent/faux/expire -> 401 (gere par require_auth)
// =====================================================================

require_once 'config.php';
require_once 'auth_check.php';

// Verifie le jeton. Si invalide, require_auth a deja repondu 401 et arrete.
$userId = require_auth($pdo);

$stmt = $pdo->prepare(
    'SELECT id, name, email, user_type FROM users WHERE id = :id'
);
$stmt->execute([':id' => $userId]);
$user = $stmt->fetch();

if (!$user) {
    json_response(['ok' => false, 'error' => 'Utilisateur introuvable'], 404);
}

json_response([
    'ok'   => true,
    'user' => [
        'id'        => (int) $user['id'],
        'name'      => $user['name'],
        'email'     => $user['email'],
        'user_type' => $user['user_type'],
    ],
]);
