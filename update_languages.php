<?php
// =====================================================================
//  update_languages.php — sauvegarde la liste des langues
//  POST + Authorization: Bearer <token>
//  Body : { "languages": [ {"name":"Francais","level":"Courant"}, ... ] }
//  Methode "on remplace tout" + transaction. Table : profile_languages.
// =====================================================================

require_once 'config.php';
require_once 'auth_check.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['ok' => false, 'error' => 'Methode non autorisee'], 405);
}

$userId = require_auth($pdo);
$input  = get_json_input();
$items  = $input['languages'] ?? null;

if (!is_array($items)) {
    json_response(['ok' => false, 'error' => 'Le champ "languages" doit etre une liste'], 422);
}

try {
    $pdo->beginTransaction();

    $del = $pdo->prepare('DELETE FROM profile_languages WHERE user_id = :user_id');
    $del->execute([':user_id' => $userId]);

    $ins = $pdo->prepare(
        'INSERT INTO profile_languages (user_id, name, level) VALUES (:user_id, :name, :level)'
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
            ':level'   => $level !== '' ? $level : 'Courant',
        ]);
        $count++;
    }

    $pdo->commit();
    json_response(['ok' => true, 'count' => $count]);

} catch (Throwable $e) {
    if ($pdo->inTransaction()) { $pdo->rollBack(); }
    json_response(['ok' => false, 'error' => 'Echec de la sauvegarde des langues'], 500);
}
