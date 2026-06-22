<?php
// =====================================================================
//  logout.php — se deconnecter (invalide le jeton de session)
//  Appel : POST http://localhost/acteurs-plus-api/logout.php
//  Header : Authorization: Bearer <token>
// =====================================================================

require_once 'config.php';
require_once 'auth_check.php';   // pour read_bearer_token()

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['ok' => false, 'error' => 'Methode non autorisee'], 405);
}

// On lit le jeton et on supprime la ligne correspondante (si elle existe).
$token = read_bearer_token();
if ($token !== null && $token !== '') {
    $stmt = $pdo->prepare('DELETE FROM sessions WHERE token = :token');
    $stmt->execute([':token' => $token]);
}

// On repond OK dans tous les cas (meme si le token n'existait pas / deja expire).
json_response(['ok' => true]);
