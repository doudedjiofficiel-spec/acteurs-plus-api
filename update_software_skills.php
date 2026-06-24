<?php
// =====================================================================
//  update_software_skills.php — logiciels maitrises (technicien)
//  POST + Authorization: Bearer <token>
//  Body : { "software_skills": [ {"name":"DaVinci","level":"Expert"}, ... ] }
//  Methode "on remplace tout" + transaction. Table : software_skills.
// =====================================================================

require_once 'config.php';
require_once 'auth_check.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['ok' => false, 'error' => 'Methode non autorisee'], 405);
}

$userId = require_auth($pdo);
$input  = get_json_input();
$items  = $input['software_skills'] ?? null;

if (!is_array($items)) {
    json_response(['ok' => false, 'error' => 'Le champ "software_skills" doit etre une liste'], 422);
}

try {
    $pdo->beginTransaction();

    $del = $pdo->prepare('DELETE FROM software_skills WHERE user_id = :user_id');
    $del->execute([':user_id' => $userId]);

    $ins = $pdo->prepare(
        'INSERT INTO software_skills (user_id, name, level) VALUES (:user_id, :name, :level)'
    );

    $count = 0;
    foreach ($items as $it) {
        if (!is_array($it)) { continue; }
        $name  = trim($it['name'] ?? '');
        $level = trim($it['level'] ?? '');
        if ($name === '') { continue; }

        $ins->execute([
            ':user_id' => $userId,
            ':name'    => $name,
            ':level'   => $level !== '' ? $level : null,
        ]);
        $count++;
    }

    $pdo->commit();
    json_response(['ok' => true, 'count' => $count]);

} catch (Throwable $e) {
    if ($pdo->inTransaction()) { $pdo->rollBack(); }
    json_response(['ok' => false, 'error' => 'Echec de la sauvegarde des logiciels'], 500);
}
