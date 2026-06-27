<?php
// =====================================================================
//  admin_list_users.php — LISTE de TOUS les comptes (reserve a l'admin)
//  GET + Authorization: Bearer <token admin>
// ---------------------------------------------------------------------
//  - Reserve ADMIN (identite via le TOKEN, re-SELECT user_type, sinon 403).
//  - INCLUT les bannis (contrairement aux annuaires publics list_actors /
//    list_technicians qui les excluent) : sans ca, impossible de debannir.
//  - Renvoie l'email : c'est un back-office, les donnees ne transitent que vers
//    l'admin authentifie (jamais expose au public).
//  - Chaque user : uid prefixe front (talent_/tech_/recruiter_/admin_) + id brut.
// =====================================================================

require_once 'config.php';        // $pdo, json_response
require_once 'auth_check.php';    // require_auth
require_once 'uid_helpers.php';   // id_to_uid

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json_response(['ok' => false, 'error' => 'Methode non autorisee'], 405);
}

// ---- Authentification + controle du role admin (via le token) ----
$adminId = require_auth($pdo);

$a = $pdo->prepare('SELECT user_type FROM users WHERE id = :id');
$a->execute([':id' => $adminId]);
$admin = $a->fetch();
if (!$admin || $admin['user_type'] !== 'admin') {
    json_response(['ok' => false, 'error' => "Réservé à l'administrateur"], 403);
}

// ---- Liste complete (bannis inclus), du plus recent au plus ancien ----
$stmt = $pdo->query(
    'SELECT id, name, email, user_type, account_status, created_at
     FROM users
     ORDER BY created_at DESC'
);
$rows = $stmt->fetchAll();

$users = array_map(function ($r) {
    $id = (int) $r['id'];
    return [
        'id'             => $id,
        'uid'            => id_to_uid($r['user_type'], $id),
        'name'           => $r['name'],
        'email'          => $r['email'],
        'user_type'      => $r['user_type'],
        'account_status' => $r['account_status'],
        'created_at'     => $r['created_at'],
    ];
}, $rows);

json_response(['ok' => true, 'users' => $users]);
