<?php
// =====================================================================
//  update_availability_calendar.php — calendrier de dispo (technicien)
//  POST + Authorization: Bearer <token>
//  Body : { "availability_calendar": [
//            { start_date, end_date, status, note }, ... ] }
//  Methode "on remplace tout" + transaction. Table : availability_calendar.
// =====================================================================

require_once 'config.php';
require_once 'auth_check.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['ok' => false, 'error' => 'Methode non autorisee'], 405);
}

$userId = require_auth($pdo);
$input  = get_json_input();
$items  = $input['availability_calendar'] ?? null;

if (!is_array($items)) {
    json_response(['ok' => false, 'error' => 'Le champ "availability_calendar" doit etre une liste'], 422);
}

try {
    $pdo->beginTransaction();

    $del = $pdo->prepare('DELETE FROM availability_calendar WHERE user_id = :user_id');
    $del->execute([':user_id' => $userId]);

    $ins = $pdo->prepare(
        'INSERT INTO availability_calendar (user_id, start_date, end_date, status, note)
         VALUES (:user_id, :start_date, :end_date, :status, :note)'
    );

    $count = 0;
    foreach ($items as $it) {
        if (!is_array($it)) { continue; }

        $start = trim($it['start_date'] ?? '');
        $end   = trim($it['end_date'] ?? '');

        // une periode sans dates n'a pas de sens -> ignoree
        if ($start === '' && $end === '') { continue; }

        $ins->execute([
            ':user_id'    => $userId,
            ':start_date' => $start !== '' ? $start : null,
            ':end_date'   => $end   !== '' ? $end   : null,
            ':status'     => trim($it['status'] ?? ''),
            ':note'       => trim($it['note'] ?? ''),
        ]);
        $count++;
    }

    $pdo->commit();
    json_response(['ok' => true, 'count' => $count]);

} catch (Throwable $e) {
    if ($pdo->inTransaction()) { $pdo->rollBack(); }
    json_response(['ok' => false, 'error' => 'Echec de la sauvegarde du calendrier'], 500);
}
