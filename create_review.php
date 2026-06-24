<?php
// =====================================================================
//  create_review.php — laisser un AVIS / NOTE sur un talent (acteur/technicien)
//  POST + Authorization: Bearer <token>
//  Body JSON : { targetUid:'talent_3'|'tech_2', rating:1..5, comment?, recommend? }
// ---------------------------------------------------------------------
//  SECURITE / REGLES (validees) :
//   - L'AUTEUR = require_auth (token), JAMAIS le client (author_uid).
//   - On ne peut PAS noter son propre profil (author_uid != cible, sinon 403).
//   - Plusieurs avis autorises (pas de garde d'unicite).
//   - rating entier borne 1..5 (sinon 422).
//   - author (nom affiche) LU en base (jamais le client).
//   - Recalcul cache : users.rating = AVG, users.reviews_count = COUNT (cible).
//   - Notif au note (create_notification). Requetes preparees PDO.
// =====================================================================

require_once 'config.php';
require_once 'auth_check.php';
require_once 'uid_helpers.php';
require_once 'notif_helper.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['ok' => false, 'error' => 'Methode non autorisee'], 405);
}

$authorId = require_auth($pdo);

$in       = get_json_input();
$targetId = uid_to_id($in['targetUid'] ?? $in['target_uid'] ?? '');
$rating   = (int) ($in['rating'] ?? 0);
$comment  = trim((string) ($in['comment'] ?? ''));
$recommend = array_key_exists('recommend', $in) ? (!empty($in['recommend']) ? 1 : 0) : null;

// ---- Validations ----
if ($targetId <= 0) {
    json_response(['ok' => false, 'error' => 'Cible invalide'], 422);
}
if ($rating < 1 || $rating > 5) {
    json_response(['ok' => false, 'error' => 'La note doit etre comprise entre 1 et 5'], 422);
}
if ($targetId === $authorId) {
    json_response(['ok' => false, 'error' => 'Impossible de noter son propre profil'], 403);
}

// La cible doit exister.
$chk = $pdo->prepare('SELECT id FROM users WHERE id = :id');
$chk->execute([':id' => $targetId]);
if (!$chk->fetch()) {
    json_response(['ok' => false, 'error' => 'Profil cible introuvable'], 404);
}

// Nom affiche de l'auteur : lu EN BASE (jamais le client).
$an = $pdo->prepare('SELECT name FROM users WHERE id = :id');
$an->execute([':id' => $authorId]);
$author = $an->fetch();
$authorName = $author['name'] ?? 'Anonyme';

try {
    $pdo->beginTransaction();

    // ---- INSERT de l'avis ----
    $ins = $pdo->prepare(
        'INSERT INTO reviews (user_id, author, author_uid, rating, comment, recommend)
         VALUES (:user_id, :author, :author_uid, :rating, :comment, :recommend)'
    );
    $ins->execute([
        ':user_id'    => $targetId,
        ':author'     => $authorName,
        ':author_uid' => $authorId,
        ':rating'     => $rating,
        ':comment'    => $comment !== '' ? $comment : null,
        ':recommend'  => $recommend,
    ]);
    $reviewId = (int) $pdo->lastInsertId();

    // ---- Recalcul cache reputation (moyenne + nombre) sur la cible ----
    $upd = $pdo->prepare(
        'UPDATE users
            SET rating = (SELECT ROUND(AVG(rating), 2) FROM reviews WHERE user_id = :t1),
                reviews_count = (SELECT COUNT(*) FROM reviews WHERE user_id = :t2)
          WHERE id = :t3'
    );
    $upd->execute([':t1' => $targetId, ':t2' => $targetId, ':t3' => $targetId]);

    $pdo->commit();
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    json_response(['ok' => false, 'error' => "Echec de l'enregistrement de l'avis"], 500);
}

// ---- Notif au NOTE (hors transaction : non bloquant) ----
create_notification(
    $pdo,
    $targetId,
    'Vous avez recu un avis (' . $rating . '★)',
    '#C97A0A',
    '/dashboard'
);

json_response(['ok' => true, 'id' => $reviewId]);
