<?php
// =====================================================================
//  mark_one_read.php — marque UNE de mes notifications comme lue
//  POST ?id=<id> (ou body { id }) + Authorization: Bearer <token>
// ---------------------------------------------------------------------
//  SECURITE : UPDATE WHERE id=:id AND user_id=:me (jamais celle d'un autre).
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

$stmt = $pdo->prepare('UPDATE notifications SET is_read = 1 WHERE id = :id AND user_id = :me');
$stmt->execute([':id' => $id, ':me' => $meId]);

json_response(['ok' => true]);
