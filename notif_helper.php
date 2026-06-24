<?php
// =====================================================================
//  notif_helper.php — OUTIL reutilisable (pas un endpoint appele seul)
//  create_notification($pdo, $userId|null, $text, $color, $link, $type)
//   - $userId : id DB du DESTINATAIRE (NULL = notification globale, vue par tous).
//   - is_read = 0 a la creation.
//   - NON BLOQUANT : toute erreur est avalee (return null) pour ne JAMAIS
//     interrompre l'action principale (candidature, message...).
//  A appeler DEPUIS les endpoints d'action (apply, update_application,
//  send_message) : le destinataire est decide COTE SERVEUR.
// =====================================================================

function create_notification($pdo, $userId, $text, $color = '#C97A0A', $link = null, $type = 'system') {
    $text = trim((string) $text);
    if ($text === '') {
        return null;
    }
    try {
        $st = $pdo->prepare(
            'INSERT INTO notifications (user_id, type, text, color, link, is_read)
             VALUES (:uid, :type, :text, :color, :link, 0)'
        );
        $st->execute([
            ':uid'   => ($userId === null) ? null : (int) $userId,
            ':type'  => ($type !== '') ? $type : 'system',
            // text = varchar(500) : on borne pour eviter toute troncature silencieuse.
            ':text'  => mb_substr($text, 0, 500),
            ':color' => ($color !== '' && $color !== null) ? $color : null,
            ':link'  => ($link !== '' && $link !== null) ? $link : null,
        ]);
        return (int) $pdo->lastInsertId();
    } catch (Throwable $e) {
        // Notification non critique : on n'interrompt pas l'action principale.
        return null;
    }
}
