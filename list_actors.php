<?php
// =====================================================================
//  list_actors.php — LISTE de tous les acteurs (version legere)
//  Appel : GET http://localhost/acteurs-plus-api/list_actors.php
//  (pas de token : annuaire public)
// ---------------------------------------------------------------------
//  Renvoie seulement les champs utiles aux CARTES de l'annuaire.
//  Filtre : uniquement les acteurs (talent), on exclut les bannis.
//  NOTE : disponibilite et tarif ne sont PAS dans actor_profiles ;
//  on les rebranchera plus tard quand on saura ou elles sont rangees.
// =====================================================================

require_once 'config.php';

// users + actor_profiles. LEFT JOIN : un acteur sans details apparait
// quand meme (champs vides).
$sql = "
    SELECT
        u.id, u.name, u.gender, u.image, u.is_verified, u.account_status,
        u.score, u.rating,
        p.role, p.location, p.city, p.country,
        p.currency, p.birthdate, p.height, p.represented_by_agency
    FROM users u
    LEFT JOIN actor_profiles p ON p.user_id = u.id
    WHERE u.user_type = 'talent'
      AND (u.account_status IS NULL OR u.account_status <> 'banned')
    ORDER BY u.id DESC
";

$rows = $pdo->query($sql)->fetchAll();

// On recompose chaque carte avec les noms que le React attend.
$actors = [];
foreach ($rows as $r) {
    $actors[] = [
        'id'                    => (string) $r['id'],
        'name'                  => $r['name'],
        'gender'                => $r['gender'] ?? null,
        'image'                 => $r['image'],
        'isVerified'            => (bool) $r['is_verified'],
        'accountStatus'         => $r['account_status'],
        'score'                 => (int) $r['score'],
        'rating'                => $r['rating'],
        'role'                  => $r['role'] ?? '',
        'location'              => $r['location'] ?? '',
        'city'                  => $r['city'] ?? '',
        'country'               => $r['country'] ?? '',
        'currency'              => $r['currency'] ?? 'FCFA',
        'birthdate'             => $r['birthdate'] ?? '',
        'height'                => $r['height'] ?? '',
        'represented_by_agency' => isset($r['represented_by_agency']) ? (bool) $r['represented_by_agency'] : false,
    ];
}

json_response(['ok' => true, 'actors' => $actors]);
