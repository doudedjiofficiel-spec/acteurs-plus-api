<?php
// =====================================================================
//  list_conversations.php — LISTE des conversations de l'utilisateur connecte
//  GET + Authorization: Bearer <token>
// ---------------------------------------------------------------------
//  Pour chaque conversation ou JE participe :
//   - conversationId
//   - l'AUTRE participant : uid prefixe + nom + image + role (SANS email)
//   - dernier message (texte + date)
//   - compteur de non-lus (messages de l'autre apres mon last_read_at)
//  Trie par updated_at DESC. Requetes preparees (EMULATE=false : placeholders
//  distincts pour la meme valeur $meId).
// =====================================================================

require_once 'config.php';
require_once 'auth_check.php';
require_once 'uid_helpers.php';

$meId = require_auth($pdo);

$sql = "
    SELECT
        c.id              AS conversation_id,
        c.updated_at      AS updated_at,
        me.last_read_at   AS my_last_read,
        o.user_id         AS other_id,
        u.name            AS other_name,
        u.image           AS other_image,
        u.user_type       AS other_type,
        COALESCE(ap.role, tp.role) AS other_role,
        (SELECT m.text      FROM messages m WHERE m.conversation_id = c.id ORDER BY m.created_at DESC, m.id DESC LIMIT 1) AS last_text,
        (SELECT m.created_at FROM messages m WHERE m.conversation_id = c.id ORDER BY m.created_at DESC, m.id DESC LIMIT 1) AS last_at,
        (SELECT COUNT(*) FROM messages m2
           WHERE m2.conversation_id = c.id
             AND m2.sender_uid <> :me_count
             AND (me.last_read_at IS NULL OR m2.created_at > me.last_read_at)) AS unread
    FROM conversations c
    JOIN conversation_participants me ON me.conversation_id = c.id AND me.user_id = :me_self
    JOIN conversation_participants o  ON o.conversation_id  = c.id AND o.user_id <> :me_other
    JOIN users u ON u.id = o.user_id
    LEFT JOIN actor_profiles      ap ON ap.user_id = o.user_id
    LEFT JOIN technician_profiles tp ON tp.user_id = o.user_id
    ORDER BY c.updated_at DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute([':me_count' => $meId, ':me_self' => $meId, ':me_other' => $meId]);
$rows = $stmt->fetchAll();

$conversations = [];
foreach ($rows as $r) {
    $conversations[] = [
        'conversationId' => (string) $r['conversation_id'],
        'otherUid'       => id_to_uid($r['other_type'], $r['other_id']),
        'otherName'      => $r['other_name'] ?? '',
        'otherImage'     => $r['other_image'] ?? '',
        'otherRole'      => $r['other_role'] ?? '',
        'lastText'       => $r['last_text'] ?? '',
        'lastAt'         => $r['last_at'],
        'unread'         => (int) $r['unread'],
        'updatedAt'      => $r['updated_at'],
    ];
}

json_response(['ok' => true, 'conversations' => $conversations]);
