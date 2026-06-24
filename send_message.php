<?php
// =====================================================================
//  send_message.php — envoie un message a un autre utilisateur
//  POST + Authorization: Bearer <token>
//  Body JSON : { targetUid:'talent_3'|'tech_2'|'recruiter_1', text:'...' }
// ---------------------------------------------------------------------
//  SECURITE :
//   - L'EXPEDITEUR = require_auth (token), JAMAIS le client.
//   - Conversation 1:1 retrouvee (intersection des participants) ou creee
//     dans une TRANSACTION (1 conversation + 2 participants).
//   - Requetes preparees PDO partout.
// =====================================================================

require_once 'config.php';
require_once 'auth_check.php';
require_once 'uid_helpers.php';
require_once 'notif_helper.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['ok' => false, 'error' => 'Methode non autorisee'], 405);
}

$meId = require_auth($pdo);

$in      = get_json_input();
$otherId = uid_to_id($in['targetUid'] ?? $in['target_uid'] ?? '');
$text    = trim($in['text'] ?? '');

if ($text === '') {
    json_response(['ok' => false, 'error' => 'Message vide'], 422);
}
if ($otherId <= 0) {
    json_response(['ok' => false, 'error' => 'Destinataire invalide'], 422);
}
if ($otherId === $meId) {
    json_response(['ok' => false, 'error' => 'Impossible de se contacter soi-meme'], 400);
}

// Le destinataire doit exister.
$chk = $pdo->prepare('SELECT id FROM users WHERE id = :id');
$chk->execute([':id' => $otherId]);
if (!$chk->fetch()) {
    json_response(['ok' => false, 'error' => 'Destinataire introuvable'], 404);
}

// ---- Retrouver la conversation 1:1 EXACTE (exactement ces 2 participants) ----
// On part des conversations ou JE suis present, puis on exige COUNT=2 (donc 1:1)
// avec l'autre dedans.
$find = $pdo->prepare(
    "SELECT cp.conversation_id
     FROM conversation_participants cp
     JOIN conversation_participants mine
       ON mine.conversation_id = cp.conversation_id AND mine.user_id = :me
     GROUP BY cp.conversation_id
     HAVING COUNT(*) = 2 AND SUM(cp.user_id = :other) = 1
     LIMIT 1"
);
$find->execute([':me' => $meId, ':other' => $otherId]);
$row = $find->fetch();
$cid = $row ? (int) $row['conversation_id'] : 0;

try {
    $pdo->beginTransaction();

    // ---- Creer la conversation + ses 2 participants si elle n'existe pas ----
    if ($cid === 0) {
        $pdo->prepare('INSERT INTO conversations (created_at, updated_at) VALUES (NOW(), NOW())')->execute();
        $cid = (int) $pdo->lastInsertId();

        $insP = $pdo->prepare(
            'INSERT INTO conversation_participants (conversation_id, user_id, last_read_at)
             VALUES (:cid, :uid, :lr)'
        );
        // L'expediteur a "tout lu" (il vient d'ouvrir) ; le destinataire : non lu.
        $insP->execute([':cid' => $cid, ':uid' => $meId,    ':lr' => date('Y-m-d H:i:s')]);
        $insP->execute([':cid' => $cid, ':uid' => $otherId, ':lr' => null]);
    }

    // ---- Inserer le message (sender = token) ----
    $insM = $pdo->prepare(
        'INSERT INTO messages (conversation_id, sender_uid, text) VALUES (:cid, :sender, :text)'
    );
    $insM->execute([':cid' => $cid, ':sender' => $meId, ':text' => $text]);
    $messageId = (int) $pdo->lastInsertId();

    // ---- Remonter la conversation en tete (tri par updated_at) ----
    $pdo->prepare('UPDATE conversations SET updated_at = NOW() WHERE id = :cid')
        ->execute([':cid' => $cid]);

    $pdo->commit();

    // ---- Notif CIBLEE au DESTINATAIRE (hors transaction : non bloquant) ----
    // otherId != meId garanti plus haut (sinon 400). Nom de l'expediteur lu en base.
    $sn = $pdo->prepare('SELECT name FROM users WHERE id = :id');
    $sn->execute([':id' => $meId]);
    $sender = $sn->fetch();
    create_notification(
        $pdo,
        $otherId,
        'Nouveau message de ' . ($sender['name'] ?? 'un utilisateur'),
        '#C97A0A',
        '/dashboard'   // la messagerie vit dans l'espace connecte
    );

    json_response(['ok' => true, 'conversationId' => $cid, 'messageId' => $messageId]);

} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    json_response(['ok' => false, 'error' => "Echec de l'envoi du message"], 500);
}
