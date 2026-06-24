<?php
// =====================================================================
//  update_filmography.php — sauvegarde la filmographie (TEXTE + LIENS + MEDIAS)
//  POST + Authorization: Bearer <token>
//  Body : { "filmography": [ { project_title, project_type, role,
//           production_name, director, year, description, project_link,
//           link_type, poster_image, media:[...] }, ... ] }
//  Methode "on remplace tout" + transaction. Table : filmography.
//  -> Cette version gere AUSSI poster_image (affiche) et media (liste).
//     Les fichiers sont stockes tels quels (texte/dataURL ou lien).
// =====================================================================

require_once 'config.php';
require_once 'auth_check.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['ok' => false, 'error' => 'Methode non autorisee'], 405);
}

$userId = require_auth($pdo);
$input  = get_json_input();
$items  = $input['filmography'] ?? null;

if (!is_array($items)) {
    json_response(['ok' => false, 'error' => 'Le champ "filmography" doit etre une liste'], 422);
}

try {
    $pdo->beginTransaction();

    $del = $pdo->prepare('DELETE FROM filmography WHERE user_id = :user_id');
    $del->execute([':user_id' => $userId]);

    $ins = $pdo->prepare(
        'INSERT INTO filmography
            (user_id, project_title, project_type, role, production_name,
             director, year, description, project_link, link_type,
             poster_image, media)
         VALUES
            (:user_id, :project_title, :project_type, :role, :production_name,
             :director, :year, :description, :project_link, :link_type,
             :poster_image, :media)'
    );

    $count = 0;
    foreach ($items as $it) {
        if (!is_array($it)) { continue; }

        $title = trim($it['project_title'] ?? '');
        if ($title === '') { continue; } // entree sans titre ignoree

        // affiche : texte (dataURL ou lien) ou vide
        $poster = $it['poster_image'] ?? '';
        if (!is_string($poster)) { $poster = ''; }

        // media : liste de chaines -> stockee en JSON
        $media = $it['media'] ?? [];
        if (!is_array($media)) { $media = []; }
        $mediaJson = json_encode(array_values($media));

        $ins->execute([
            ':user_id'         => $userId,
            ':project_title'   => $title,
            ':project_type'    => trim($it['project_type'] ?? ''),
            ':role'            => trim($it['role'] ?? ''),
            ':production_name' => trim($it['production_name'] ?? ''),
            ':director'        => trim($it['director'] ?? ''),
            ':year'            => trim($it['year'] ?? ''),
            ':description'     => trim($it['description'] ?? ''),
            ':project_link'    => trim($it['project_link'] ?? ''),
            ':link_type'       => trim($it['link_type'] ?? ''),
            ':poster_image'    => $poster,
            ':media'           => $mediaJson,
        ]);
        $count++;
    }

    $pdo->commit();
    json_response(['ok' => true, 'count' => $count]);

} catch (Throwable $e) {
    if ($pdo->inTransaction()) { $pdo->rollBack(); }
    json_response(['ok' => false, 'error' => 'Echec de la sauvegarde de la filmographie'], 500);
}
