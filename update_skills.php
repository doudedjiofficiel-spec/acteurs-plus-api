<?php
// =====================================================================
//  update_skills.php — sauvegarde la liste des competences (skills)
//  Appel : POST http://localhost/acteurs-plus-api/update_skills.php
//  Header : Authorization: Bearer <token>
//  Body JSON : { "skills": [ {"name":"Chant","level":"Professionnel"}, ... ] }
// ---------------------------------------------------------------------
//  METHODE "on remplace tout" :
//   1. on efface les competences existantes de l'utilisateur
//   2. on reinsere la liste complete envoyee par React
//  Le tout dans une TRANSACTION (tout ou rien : pas de demi-sauvegarde).
// ---------------------------------------------------------------------
//  SECURITE : le token decide QUI (jamais un id du client) ; requetes
//  preparees ; on ne lit que name + level (l'id client est ignore, la
//  base genere ses propres ids).
// =====================================================================

require_once 'config.php';
require_once 'auth_check.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['ok' => false, 'error' => 'Methode non autorisee'], 405);
}

$userId = require_auth($pdo);

$input  = get_json_input();
$skills = $input['skills'] ?? null;

// On attend bien un tableau (meme vide = l'utilisateur a tout supprime).
if (!is_array($skills)) {
    json_response(['ok' => false, 'error' => 'Le champ "skills" doit etre une liste'], 422);
}

try {
    $pdo->beginTransaction();

    // 1. Effacer les anciennes competences de CET utilisateur
    $del = $pdo->prepare('DELETE FROM skills WHERE user_id = :user_id');
    $del->execute([':user_id' => $userId]);

    // 2. Reinserer la liste complete
    $ins = $pdo->prepare(
        'INSERT INTO skills (user_id, name, level) VALUES (:user_id, :name, :level)'
    );

    $count = 0;
    foreach ($skills as $skill) {
        if (!is_array($skill)) {
            continue;
        }
        $name  = trim($skill['name'] ?? '');
        $level = trim($skill['level'] ?? '');

        // on ignore les entrees sans nom
        if ($name === '') {
            continue;
        }

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
    // En cas de souci, on annule TOUT (la liste reste comme avant).
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    json_response(['ok' => false, 'error' => 'Echec de la sauvegarde des competences'], 500);
}
