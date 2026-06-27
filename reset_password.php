<?php
// =====================================================================
//  reset_password.php — POSER un nouveau mot de passe via un token de reset
//  Appel : POST http://localhost/acteurs-plus-api/reset_password.php
//  Body JSON : { "token":"...", "password":"..." }
// ---------------------------------------------------------------------
//  SECURITE (non negociable) :
//   - Token rejete s'il est introuvable, deja utilise (used=1) ou expire.
//   - Nouveau mot de passe : longueur >= 6 (meme regle que register.php),
//     hache avec password_hash(PASSWORD_DEFAULT) (jamais stocke en clair).
//   - Apres succes (transaction atomique) :
//       * UPDATE users.password_hash ;
//       * ce token -> used=1, et SUPPRESSION des autres tokens reset du user ;
//       * DELETE FROM sessions WHERE user_id -> deconnexion GLOBALE (un eventuel
//         attaquant deja connecte perd l'acces immediatement).
//   - Requetes preparees PDO partout.
// =====================================================================

require_once 'config.php';   // $pdo, json_response, get_json_input

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['ok' => false, 'error' => 'Methode non autorisee'], 405);
}

$input    = get_json_input();
$token    = trim($input['token'] ?? '');
$password = $input['password'] ?? '';

// ---- Validations d'entree ----
if ($token === '') {
    json_response(['ok' => false, 'error' => 'Lien invalide ou expiré'], 400);
}
if (strlen($password) < 6) {
    json_response(['ok' => false, 'error' => 'Le mot de passe doit faire au moins 6 caracteres'], 422);
}

// ---- Lecture du token (requete preparee) ----
$stmt = $pdo->prepare(
    'SELECT id, user_id, expires_at, used FROM password_resets WHERE token = :token'
);
$stmt->execute([':token' => $token]);
$reset = $stmt->fetch();

// Token absent / deja utilise / expire -> message VOLONTAIREMENT identique
// (on ne distingue pas les cas : pas d'info exploitable pour un attaquant).
// Comparaison d'expiration alignee sur auth_check.php (strtotime vs time()).
if (!$reset
    || (int) $reset['used'] === 1
    || strtotime($reset['expires_at']) < time()) {
    json_response(['ok' => false, 'error' => 'Lien invalide ou expiré'], 400);
}

$userId = (int) $reset['user_id'];
$hash   = password_hash($password, PASSWORD_DEFAULT);

// ---- Reinitialisation atomique ----
try {
    $pdo->beginTransaction();

    // 1) Nouveau hash sur le compte.
    $upd = $pdo->prepare('UPDATE users SET password_hash = :hash WHERE id = :id');
    $upd->execute([':hash' => $hash, ':id' => $userId]);

    // 2) Ce token est consomme (usage unique).
    $mark = $pdo->prepare('UPDATE password_resets SET used = 1 WHERE id = :id');
    $mark->execute([':id' => (int) $reset['id']]);

    // 3) On invalide TOUS les autres tokens reset du user (un seul flux a la fois).
    $clr = $pdo->prepare('DELETE FROM password_resets WHERE user_id = :uid AND id <> :id');
    $clr->execute([':uid' => $userId, ':id' => (int) $reset['id']]);

    // 4) Deconnexion globale : toutes les sessions du user sautent.
    $del = $pdo->prepare('DELETE FROM sessions WHERE user_id = :uid');
    $del->execute([':uid' => $userId]);

    $pdo->commit();
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('[acteurs-plus] reset_password: ' . $e->getMessage());
    json_response(['ok' => false, 'error' => 'Echec de la réinitialisation'], 500);
}

json_response(['ok' => true, 'message' => 'Mot de passe réinitialisé, reconnecte-toi.']);
