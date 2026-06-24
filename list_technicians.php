<?php
// =====================================================================
//  list_technicians.php — LISTE de tous les techniciens (version legere)
//  Appel : GET http://localhost/acteurs-plus-api/list_technicians.php
//  (pas de token : annuaire public)
// ---------------------------------------------------------------------
//  Frere jumeau de list_actors.php. Filtre : uniquement les techniciens
//  (user_type = 'professional'), on exclut les bannis.
//  Renvoie le role (metier) pour l'affichage nom + metier.
// =====================================================================

require_once 'config.php';

// users + technician_profiles. LEFT JOIN : un tech sans details apparait
// quand meme (champs vides).
$sql = "
    SELECT
        u.id, u.name, u.gender, u.image, u.is_verified, u.account_status,
        u.score, u.rating,
        p.role, p.location, p.crew_ready,
        p.availability_status, p.daily_rate, p.daily_rate_visibility
    FROM users u
    LEFT JOIN technician_profiles p ON p.user_id = u.id
    WHERE u.user_type = 'professional'
      AND (u.account_status IS NULL OR u.account_status <> 'banned')
    ORDER BY u.id DESC
";

$rows = $pdo->query($sql)->fetchAll();

// On recompose chaque carte avec les noms que le React attend.
$technicians = [];
foreach ($rows as $r) {
    $technicians[] = [
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
        'crew_ready'            => isset($r['crew_ready']) ? (bool) $r['crew_ready'] : false,
        'availability_status'   => $r['availability_status'] ?? '',
        'daily_rate'            => $r['daily_rate'] ?? '',
        'daily_rate_visibility' => isset($r['daily_rate_visibility']) ? (bool) $r['daily_rate_visibility'] : false,
    ];
}

json_response(['ok' => true, 'technicians' => $technicians]);
