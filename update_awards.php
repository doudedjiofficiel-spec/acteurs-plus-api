<?php
// =====================================================================
//  update_awards.php — sauvegarde la liste des recompenses
//  POST + Authorization: Bearer <token>
//  Body : { "awards": [ {"title","year","project"}, ... ] }
//  Methode "on remplace tout" + transaction. Table : awards.
// =====================================================================

require_once 'config.php';
require_once 'auth_check.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['ok' => false, 'error' => 'Methode non autorisee'], 405);
}

$userId = require_auth($pdo);
$input  = get_json_input();
$items  = $input['awards'] ?? null;

if (!is_array($items)) {
    json_response(['ok' => false, 'error' => 'Le champ "awards" doit etre une liste'], 422);
}

try {
    $pdo->beginTransaction();

    $del = $pdo->prepare('DELETE FROM awards WHERE user_id = :user_id');
    $del->execute([':user_id' => $userId]);

    $ins = $pdo->prepare(
        'INSERT INTO awards (user_id, title, year, project)
         VALUES (:user_id, :title, :year, :project)'
    );

    $count = 0;
    foreach ($items as $it) {
        if (!is_array($it)) { continue; }
        $title   = trim($it['title'] ?? '');
        $year    = trim($it['year'] ?? '');
        $project = trim($it['project'] ?? '');

        if ($title === '') { continue; }

        $ins->execute([
            ':user_id' => $userId,
            ':title'   => $title,
            ':year'    => $year,
            ':project' => $project,
        ]);
        $count++;
    }

    $pdo->commit();
    json_response(['ok' => true, 'count' => $count]);

} catch (Throwable $e) {
    if ($pdo->inTransaction()) { $pdo->rollBack(); }
    json_response(['ok' => false, 'error' => 'Echec de la sauvegarde des recompenses'], 500);
}
