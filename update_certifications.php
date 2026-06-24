<?php
// =====================================================================
//  update_certifications.php — certifications (technicien)
//  POST + Authorization: Bearer <token>
//  Body : { "certifications": [ {"name":"...","year":"2023"}, ... ] }
//  Methode "on remplace tout" + transaction. Table : certifications.
// =====================================================================

require_once 'config.php';
require_once 'auth_check.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['ok' => false, 'error' => 'Methode non autorisee'], 405);
}

$userId = require_auth($pdo);
$input  = get_json_input();
$items  = $input['certifications'] ?? null;

if (!is_array($items)) {
    json_response(['ok' => false, 'error' => 'Le champ "certifications" doit etre une liste'], 422);
}

try {
    $pdo->beginTransaction();

    $del = $pdo->prepare('DELETE FROM certifications WHERE user_id = :user_id');
    $del->execute([':user_id' => $userId]);

    $ins = $pdo->prepare(
        'INSERT INTO certifications (user_id, name, year) VALUES (:user_id, :name, :year)'
    );

    $count = 0;
    foreach ($items as $it) {
        if (!is_array($it)) { continue; }
        $name = trim($it['name'] ?? '');
        $year = trim($it['year'] ?? '');
        if ($name === '') { continue; }

        $ins->execute([
            ':user_id' => $userId,
            ':name'    => $name,
            ':year'    => $year,
        ]);
        $count++;
    }

    $pdo->commit();
    json_response(['ok' => true, 'count' => $count]);

} catch (Throwable $e) {
    if ($pdo->inTransaction()) { $pdo->rollBack(); }
    json_response(['ok' => false, 'error' => 'Echec de la sauvegarde des certifications'], 500);
}
