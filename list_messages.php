<?php
// =====================================================================
//  list_messages.php — LISTE des messages d'une conversation
//  GET ?conversation_id=<id> + Authorization: Bearer <token>
// ---------------------------------------------------------------------
//  SECURITE : on verifie que le token EST participant de la conversation
//  (sinon 403) -> personne ne lit une conversation qui n'est pas la sienne.
//  sender_uid renvoye en uid prefixe. Aucun email.
// =====================================================================

require_once 'config.php';
require_once 'auth_check.php';
require_once 'uid_helpers.php';

$meId = require_auth($pdo);

$cid = isset($_GET['conversation_id']) ? (int) $_GET['conversation_id'] : 0;
if ($cid <= 0) {
    json_response(['ok' => false, 'error' => 'Conversation manquante ou invalide'], 422);
}

// ---- Controle "participant" : sinon 403 ----
$chk = $pdo->prepare(
    'SELECT 1 FROM conversation_participants WHERE conversation_id = :cid AND user_id = :me'
);
$chk->execute([':cid' => $cid, ':me' => $meId]);
if (!$chk->fetch()) {
    json_response(['ok' => false, 'error' => 'Acces refuse a cette conversation'], 403);
}

// ---- Messages (ordre chronologique) ----
$stmt = $pdo->prepare(
    'SELECT m.id, m.sender_uid, m.text, m.created_at, u.user_type
     FROM messages m
     JOIN users u ON u.id = m.sender_uid
     WHERE m.conversation_id = :cid
     ORDER BY m.created_at ASC, m.id ASC'
);
$stmt->execute([':cid' => $cid]);
$rows = $stmt->fetchAll();

$messages = [];
foreach ($rows as $r) {
    $messages[] = [
        'id'        => (string) $r['id'],
        'senderUid' => id_to_uid($r['user_type'], $r['sender_uid']),
        'text'      => $r['text'],
        'createdAt' => $r['created_at'],
    ];
}

json_response(['ok' => true, 'messages' => $messages]);
