<?php
// =====================================================================
//  admin_set_ban.php — BANNIR / DEBANNIR un compte (reserve a l'admin)
//  POST + Authorization: Bearer <token admin>
//  Body JSON : { "uid":"talent_3"|3, "banned":true|false, "reason"?:"..." }
// ---------------------------------------------------------------------
//  SECURITE (non negociable) :
//   - Reserve ADMIN : l'identite vient du TOKEN (require_auth) ; on re-SELECT le
//     user_type en base et on exige 'admin' (jamais un champ envoye par le client).
//   - Pas d'AUTO-bannissement : target == admin -> 403.
//   - Bannir POSE account_status='banned' (seule valeur bloquante, cf. login.php /
//     auth_check.php) ET supprime les sessions du banni (deconnexion immediate).
//   - Debannir POSE account_status='active'.
//   - Journalisation dans `sanctions` (action 'ban' / 'lift'). Requetes preparees.
// =====================================================================

require_once 'config.php';        // $pdo, json_response, get_json_input
require_once 'auth_check.php';    // require_auth
require_once 'uid_helpers.php';   // uid_to_id, id_to_uid

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['ok' => false, 'error' => 'Methode non autorisee'], 405);
}

// ---- 1. Authentification + controle du role admin (via le token) ----
$adminId = require_auth($pdo);

$a = $pdo->prepare('SELECT user_type FROM users WHERE id = :id');
$a->execute([':id' => $adminId]);
$admin = $a->fetch();
if (!$admin || $admin['user_type'] !== 'admin') {
    json_response(['ok' => false, 'error' => "Réservé à l'administrateur"], 403);
}

// ---- 2. Lecture + validation de l'entree ----
$in = get_json_input();

if (!array_key_exists('banned', $in)) {
    json_response(['ok' => false, 'error' => 'Champ "banned" requis'], 422);
}
$banned   = filter_var($in['banned'], FILTER_VALIDATE_BOOLEAN);
$targetId = uid_to_id($in['uid'] ?? '');
$reason   = isset($in['reason']) ? trim((string) $in['reason']) : '';

if ($targetId <= 0) {
    json_response(['ok' => false, 'error' => 'Cible invalide'], 422);
}

// Pas d'auto-bannissement (un admin ne peut pas se sanctionner lui-meme).
if ($targetId === $adminId) {
    json_response(['ok' => false, 'error' => 'Impossible de se bannir soi-même'], 403);
}

// La cible doit exister (on recupere aussi son type pour reconstruire l'uid).
$t = $pdo->prepare('SELECT id, user_type FROM users WHERE id = :id');
$t->execute([':id' => $targetId]);
$target = $t->fetch();
if (!$target) {
    json_response(['ok' => false, 'error' => 'Compte cible introuvable'], 404);
}

// ---- 3. Application atomique (statut + sessions + journal) ----
$newStatus = $banned ? 'banned' : 'active';
$action    = $banned ? 'ban' : 'lift';

try {
    $pdo->beginTransaction();

    // Pose le statut. 'banned' = seule valeur bloquante (login.php / auth_check.php).
    $upd = $pdo->prepare('UPDATE users SET account_status = :st WHERE id = :id');
    $upd->execute([':st' => $newStatus, ':id' => $targetId]);

    // Bannissement -> on coupe immediatement toutes les sessions du banni
    // (meme pattern que reset_password.php / auth_check.php).
    if ($banned) {
        $del = $pdo->prepare('DELETE FROM sessions WHERE user_id = :uid');
        $del->execute([':uid' => $targetId]);
    }

    // Journal de moderation (table `sanctions`).
    $log = $pdo->prepare(
        'INSERT INTO sanctions (target_uid, action, reason, created_at)
         VALUES (:t, :a, :r, NOW())'
    );
    $log->execute([
        ':t' => $targetId,
        ':a' => $action,
        ':r' => $reason !== '' ? $reason : null,
    ]);

    $pdo->commit();
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('[acteurs-plus] admin_set_ban: ' . $e->getMessage());
    json_response(['ok' => false, 'error' => 'Echec de la mise a jour du statut'], 500);
}

json_response([
    'ok'             => true,
    'uid'            => id_to_uid($target['user_type'], $targetId),
    'account_status' => $newStatus,
]);
