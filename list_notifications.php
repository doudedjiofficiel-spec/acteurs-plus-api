<?php
// =====================================================================
//  list_notifications.php — LISTE des notifications de l'utilisateur connecte
//  GET + Authorization: Bearer <token>
// ---------------------------------------------------------------------
//  Renvoie SES notifications (user_id = token) + les GLOBALES (user_id IS NULL),
//  les 50 plus recentes. unread = !is_read. Aucune donnee d'autrui.
// =====================================================================

require_once 'config.php';
require_once 'auth_check.php';

$meId = require_auth($pdo);

$stmt = $pdo->prepare(
    "SELECT id, type, text, color, link, is_read, created_at
     FROM notifications
     WHERE user_id = :me OR user_id IS NULL
     ORDER BY created_at DESC, id DESC
     LIMIT 50"
);
$stmt->execute([':me' => $meId]);
$rows = $stmt->fetchAll();

$notifications = [];
foreach ($rows as $r) {
    $notifications[] = [
        'id'        => (string) $r['id'],
        'type'      => $r['type'] ?? 'system',
        'text'      => $r['text'],
        'color'     => $r['color'] ?? '#C97A0A',
        'link'      => $r['link'] ?? '',
        'unread'    => !((int) $r['is_read']),   // is_read 0 -> unread true
        'createdAt' => $r['created_at'],
    ];
}

json_response(['ok' => true, 'notifications' => $notifications]);
