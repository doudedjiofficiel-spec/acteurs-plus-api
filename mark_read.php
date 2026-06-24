<?php
// =====================================================================
//  mark_read.php — marque une conversation comme lue par l'utilisateur connecte
//  POST ?conversation_id=<id> (ou body { conversation_id }) + Bearer <token>
// ---------------------------------------------------------------------
//  SECURITE : on verifie que le token EST participant (sinon 403).
//  Met a jour SON last_read_at (le WHERE impose user_id = token).
// =====================================================================

require_once 'config.php';
require_once 'auth_check.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['ok' => false, 'error' => 'Methode non autorisee'], 405);
}

$meId = require_auth($pdo);

// conversation_id : query string en priorite, sinon corps JSON.
$cid = isset($_GET['conversation_id']) ? (int) $_GET['conversation_id'] : 0;
if ($cid <= 0) {
    $in  = get_json_input();
    $cid = (int) ($in['conversation_id'] ?? 0);
}
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

// ---- Marque lu (uniquement MA ligne participant) ----
$upd = $pdo->prepare(
    'UPDATE conversation_participants SET last_read_at = NOW()
     WHERE conversation_id = :cid AND user_id = :me'
);
$upd->execute([':cid' => $cid, ':me' => $meId]);

json_response(['ok' => true]);
