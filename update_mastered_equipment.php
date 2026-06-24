<?php
// =====================================================================
//  update_mastered_equipment.php — materiel maitrise (technicien)
//  POST + Authorization: Bearer <token>
//  Body : { "mastered_equipment": ["Steadicam", "Grue", ...] }
//  Liste de TEXTES simples. Methode "on remplace tout" + transaction.
// =====================================================================

require_once 'config.php';
require_once 'auth_check.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['ok' => false, 'error' => 'Methode non autorisee'], 405);
}

$userId = require_auth($pdo);
$input  = get_json_input();
$items  = $input['mastered_equipment'] ?? null;

if (!is_array($items)) {
    json_response(['ok' => false, 'error' => 'Le champ "mastered_equipment" doit etre une liste'], 422);
}

try {
    $pdo->beginTransaction();

    $del = $pdo->prepare('DELETE FROM mastered_equipment WHERE user_id = :user_id');
    $del->execute([':user_id' => $userId]);

    $ins = $pdo->prepare(
        'INSERT INTO mastered_equipment (user_id, value) VALUES (:user_id, :value)'
    );

    $count = 0;
    foreach ($items as $it) {
        $value = is_string($it) ? trim($it) : '';
        if ($value === '') { continue; }

        $ins->execute([':user_id' => $userId, ':value' => $value]);
        $count++;
    }

    $pdo->commit();
    json_response(['ok' => true, 'count' => $count]);

} catch (Throwable $e) {
    if ($pdo->inTransaction()) { $pdo->rollBack(); }
    json_response(['ok' => false, 'error' => 'Echec de la sauvegarde du materiel maitrise'], 500);
}
