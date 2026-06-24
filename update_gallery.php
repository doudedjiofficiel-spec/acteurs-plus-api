<?php
// =====================================================================
//  update_gallery.php — sauvegarde la galerie (photos / videos)
//  POST + Authorization: Bearer <token>
//  Body : { "gallery": [ { type, url, title, description, category,
//           visibility }, ... ] }
//  Methode "on remplace tout" + transaction. Table : gallery_items.
//  url = image en dataURL (texte) ou lien. created_at est gere par la base.
// =====================================================================

require_once 'config.php';
require_once 'auth_check.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['ok' => false, 'error' => 'Methode non autorisee'], 405);
}

$userId = require_auth($pdo);
$input  = get_json_input();
$items  = $input['gallery'] ?? null;

if (!is_array($items)) {
    json_response(['ok' => false, 'error' => 'Le champ "gallery" doit etre une liste'], 422);
}

try {
    $pdo->beginTransaction();

    $del = $pdo->prepare('DELETE FROM gallery_items WHERE user_id = :user_id');
    $del->execute([':user_id' => $userId]);

    $ins = $pdo->prepare(
        'INSERT INTO gallery_items
            (user_id, type, url, title, description, category, visibility)
         VALUES
            (:user_id, :type, :url, :title, :description, :category, :visibility)'
    );

    $count = 0;
    foreach ($items as $it) {
        if (!is_array($it)) { continue; }

        // une entree sans image/lien n'a pas de sens -> ignoree
        $url = $it['url'] ?? '';
        if (!is_string($url) || trim($url) === '') { continue; }

        $ins->execute([
            ':user_id'     => $userId,
            ':type'        => trim($it['type'] ?? 'photo'),
            ':url'         => $url,
            ':title'       => trim($it['title'] ?? ''),
            ':description' => trim($it['description'] ?? ''),
            ':category'    => trim($it['category'] ?? ''),
            ':visibility'  => trim($it['visibility'] ?? 'public'),
        ]);
        $count++;
    }

    $pdo->commit();
    json_response(['ok' => true, 'count' => $count]);

} catch (Throwable $e) {
    if ($pdo->inTransaction()) { $pdo->rollBack(); }
    json_response(['ok' => false, 'error' => 'Echec de la sauvegarde de la galerie'], 500);
}
