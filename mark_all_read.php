<?php
// =====================================================================
//  mark_all_read.php — marque TOUTES mes notifications comme lues
//  POST + Authorization: Bearer <token>
// ---------------------------------------------------------------------
//  SECURITE : n'agit QUE sur MES notifications (user_id = token).
//  Les globales (user_id IS NULL) sont partagees -> on n'y touche pas.
// =====================================================================

require_once 'config.php';
require_once 'auth_check.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['ok' => false, 'error' => 'Methode non autorisee'], 405);
}

$meId = require_auth($pdo);

$stmt = $pdo->prepare('UPDATE notifications SET is_read = 1 WHERE user_id = :me AND is_read = 0');
$stmt->execute([':me' => $meId]);

json_response(['ok' => true, 'updated' => $stmt->rowCount()]);
