<?php
// =====================================================================
//  me.php — QUI SUIS-JE ? (identite de l'utilisateur connecte)
//  Appel : GET http://localhost/acteurs-plus-api/me.php
//  Header OBLIGATOIRE : Authorization: Bearer <token>
// ---------------------------------------------------------------------
//  Renvoie SEULEMENT l'identite (id, name, email, user_type).
//  Le profil complet se recupere ensuite via get_actor.php (acteur)
//  ou get_technician.php (technicien) -> un fichier = un seul role.
//  id renvoye en STRING (le React compare les id en texte).
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
        'id'        => (string) $user['id'],   // string pour coller au React
        'name'      => $user['name'],
        'email'     => $user['email'],
        'user_type' => $user['user_type'],      // talent | professional | recruiter
    ],
]);
