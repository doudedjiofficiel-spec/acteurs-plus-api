<?php
// =====================================================================
//  apply.php — un CANDIDAT postule a une cible (casting ou mission)
//  POST + Authorization: Bearer <token>
//  Body JSON : { kind:'casting'|'mission', target_id:<int> }
// ---------------------------------------------------------------------
//  SECURITE (non negociable) :
//   - candidate_uid = l'utilisateur du TOKEN (jamais un id du client).
//   - owner_uid = le created_by de la cible (LU EN BASE), pas du client.
//   - casting -> reserve au talent ; mission -> reserve au professional
//     (user_type lu en base).
//   - candidate_name / role / image : LUS cote serveur depuis le profil
//     du candidat (jamais le client).
//   - Unicite : on refuse si une candidature (candidat+kind+target) existe
//     deja avec un statut != 'retiree' (une candidature retiree est ré-autorisée).
// =====================================================================

require_once 'config.php';
require_once 'auth_check.php';
require_once 'notif_helper.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['ok' => false, 'error' => 'Methode non autorisee'], 405);
}

$userId = require_auth($pdo);

$in       = get_json_input();
$kind     = (($in['kind'] ?? '') === 'mission') ? 'mission' : 'casting';
$targetId = (int) ($in['target_id'] ?? $in['targetId'] ?? 0);
if ($targetId <= 0) {
    json_response(['ok' => false, 'error' => 'Cible manquante ou invalide'], 422);
}

// ---- Identite + type du candidat (lus en base) ----
$st = $pdo->prepare('SELECT user_type, name, image FROM users WHERE id = :id');
$st->execute([':id' => $userId]);
$me = $st->fetch();
if (!$me) {
    json_response(['ok' => false, 'error' => 'Compte introuvable'], 404);
}

// ---- Bon type pour le bon kind ----
$needType = ($kind === 'mission') ? 'professional' : 'talent';
if ($me['user_type'] !== $needType) {
    $msg = $kind === 'mission'
        ? 'Seuls les techniciens peuvent postuler a une offre.'
        : 'Seuls les acteurs peuvent postuler a un casting.';
    json_response(['ok' => false, 'error' => $msg], 403);
}

// ---- Cible : on en lit created_by (owner) + le titre ----
$table = ($kind === 'mission') ? 'missions' : 'castings';
$ts = $pdo->prepare("SELECT title, created_by FROM {$table} WHERE id = :id");
$ts->execute([':id' => $targetId]);
$target = $ts->fetch();
if (!$target) {
    json_response(['ok' => false, 'error' => 'Cible introuvable'], 404);
}
$ownerUid    = $target['created_by'] !== null ? (int) $target['created_by'] : null;
$targetTitle = $target['title'] ?? '';

// ---- Unicite : deja postule (statut != retiree) ? ----
$ex = $pdo->prepare(
    "SELECT id FROM applications
     WHERE candidate_uid = :uid AND kind = :kind AND target_id = :tid
       AND status <> 'retiree'
     LIMIT 1"
);
$ex->execute([':uid' => $userId, ':kind' => $kind, ':tid' => $targetId]);
if ($ex->fetch()) {
    json_response(['ok' => false, 'error' => 'Vous avez deja postule a cette annonce'], 409);
}

// ---- Role du candidat (depuis son profil, jamais le client) ----
$profileTable = ($kind === 'mission') ? 'technician_profiles' : 'actor_profiles';
$rs = $pdo->prepare("SELECT role FROM {$profileTable} WHERE user_id = :id");
$rs->execute([':id' => $userId]);
$prof = $rs->fetch();
$candidateRole = $prof['role'] ?? '';

// ---- INSERT (status 'recue', to_audition 0) ----
$ins = $pdo->prepare(
    'INSERT INTO applications
        (kind, target_id, target_title, candidate_uid, candidate_name,
         candidate_role, candidate_image, owner_uid, status, to_audition)
     VALUES
        (:kind, :target_id, :target_title, :candidate_uid, :candidate_name,
         :candidate_role, :candidate_image, :owner_uid, "recue", 0)'
);
$ins->execute([
    ':kind'            => $kind,
    ':target_id'       => $targetId,
    ':target_title'    => $targetTitle,
    ':candidate_uid'   => $userId,
    ':candidate_name'  => $me['name'] ?? '',
    ':candidate_role'  => $candidateRole,
    ':candidate_image' => $me['image'] ?? '',
    ':owner_uid'       => $ownerUid,
]);

$appId = (int) $pdo->lastInsertId();

// ---- Notif CIBLEE au RECRUTEUR proprietaire (destinataire decide cote serveur) ----
// (jamais soi-meme : owner = recruteur, candidat = talent/tech.)
if ($ownerUid !== null && $ownerUid !== $userId) {
    create_notification(
        $pdo,
        $ownerUid,
        'Nouvelle candidature : ' . ($me['name'] ?? 'Un candidat') . ' a postule a « ' . $targetTitle . ' »',
        '#C97A0A',
        '/dashboard'   // espace recruteur : gestion des candidatures (kanban)
    );
}

json_response(['ok' => true, 'id' => $appId]);
