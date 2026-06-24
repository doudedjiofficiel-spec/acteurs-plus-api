<?php
// =====================================================================
//  delete_notification.php — supprime UNE de mes notifications
//  POST ?id=<id> (ou body { id }) + Authorization: Bearer <token>
// ---------------------------------------------------------------------
//  SECURITE : DELETE WHERE id=:id AND user_id=:me -> on ne supprime jamais
//  la notification d'un autre, ni une globale (user_id IS NULL).
// =====================================================================

require_once 'config.php';
require_once 'auth_check.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['ok' => false, 'error' => 'Methode non autorisee'], 405);
}

$meId = require_auth($pdo);

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($id <= 0) {
    $in = get_json_input();
    $id = (int) ($in['id'] ?? 0);
}
if ($id <= 0) {
    json_response(['ok' => false, 'error' => 'Notification manquante ou invalide'], 422);
}

$stmt = $pdo->prepare('DELETE FROM notifications WHERE id = :id AND user_id = :me');
$stmt->execute([':id' => $id, ':me' => $meId]);

json_response(['ok' => true, 'deleted' => $stmt->rowCount()]);
