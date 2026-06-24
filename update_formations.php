<?php
// =====================================================================
//  update_formations.php — sauvegarde la liste des formations
//  POST + Authorization: Bearer <token>
//  Body : { "formations": [ {"school","degree","year","description"}, ... ] }
//  Methode "on remplace tout" + transaction. Table : formations.
// =====================================================================

require_once 'config.php';
require_once 'auth_check.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['ok' => false, 'error' => 'Methode non autorisee'], 405);
}

$userId = require_auth($pdo);
$input  = get_json_input();
$items  = $input['formations'] ?? null;

if (!is_array($items)) {
    json_response(['ok' => false, 'error' => 'Le champ "formations" doit etre une liste'], 422);
}

try {
    $pdo->beginTransaction();

    $del = $pdo->prepare('DELETE FROM formations WHERE user_id = :user_id');
    $del->execute([':user_id' => $userId]);

    $ins = $pdo->prepare(
        'INSERT INTO formations (user_id, school, degree, year, description)
         VALUES (:user_id, :school, :degree, :year, :description)'
    );

    $count = 0;
    foreach ($items as $it) {
        if (!is_array($it)) { continue; }
        $school = trim($it['school'] ?? '');
        $degree = trim($it['degree'] ?? '');
        $year   = trim($it['year'] ?? '');
        $desc   = trim($it['description'] ?? '');

        // on ignore une formation totalement vide
        if ($school === '' && $degree === '') { continue; }

        $ins->execute([
            ':user_id'     => $userId,
            ':school'      => $school,
            ':degree'      => $degree,
            ':year'        => $year,
            ':description' => $desc,
        ]);
        $count++;
    }

    $pdo->commit();
    json_response(['ok' => true, 'count' => $count]);

} catch (Throwable $e) {
    if ($pdo->inTransaction()) { $pdo->rollBack(); }
    json_response(['ok' => false, 'error' => 'Echec de la sauvegarde des formations'], 500);
}
