<?php
// =====================================================================
//  get_technician.php — LIT un profil TECHNICIEN complet depuis la base
//  Appel : GET http://localhost/acteurs-plus-api/get_technician.php?id=2
//  (pas besoin de token : profil public)
// ---------------------------------------------------------------------
//  Frere jumeau de get_actor.php, cote technicien.
//  Recompose : users + technician_profiles (avec equipment/mobility/
//  profession en JSON) + listes enfant (skills, software_skills,
//  certifications, secondary_roles, mastered_equipment,
//  availability_calendar, filmography, gallery, formations, awards,
//  reviews). password JAMAIS renvoye. Requetes preparees.
// =====================================================================

require_once 'config.php';

// --- recuperer l'id demande ---
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($id <= 0) {
    json_response(['ok' => false, 'error' => 'id manquant ou invalide'], 422);
}

// --- 1. le compte (users) ---
// Garde-fou : on ne renvoie QUE des techniciens (user_type = 'professional').
$stmt = $pdo->prepare(
    "SELECT id, name, user_type, gender, image, cover_image, is_verified,
            account_status, score, credibility_level, rating, reviews_count
     FROM users WHERE id = :id AND user_type = 'professional'"
);
$stmt->execute([':id' => $id]);
$u = $stmt->fetch();

if (!$u) {
    json_response(['ok' => false, 'error' => 'Profil introuvable'], 404);
}

// --- 2. les details technicien (technician_profiles) ---
$stmt = $pdo->prepare('SELECT * FROM technician_profiles WHERE user_id = :id');
$stmt->execute([':id' => $id]);
$p = $stmt->fetch() ?: [];

// helper liste enfant
function fetchList($pdo, $sql, $id) {
    $s = $pdo->prepare($sql);
    $s->execute([':id' => $id]);
    return $s->fetchAll();
}

// petit utilitaire : decoder un JSON en objet/tableau, avec defaut
function jsonOr($value, $default) {
    if ($value === null || $value === '') { return $default; }
    $decoded = json_decode($value, true);
    return $decoded !== null ? $decoded : $default;
}

// --- 3. les listes enfant communes (memes tables que l'acteur) ---
$skills = fetchList($pdo,
    'SELECT id, name, level FROM skills WHERE user_id = :id', $id);

$formations = fetchList($pdo,
    'SELECT id, school, degree, year, description FROM formations WHERE user_id = :id', $id);

$awards_list = fetchList($pdo,
    'SELECT id, title, year, project FROM awards WHERE user_id = :id', $id);

$gallery_items = fetchList($pdo,
    'SELECT id, type, url, title, description, category, visibility
     FROM gallery_items WHERE user_id = :id', $id);

$ratings_reviews_list = fetchList($pdo,
    'SELECT id, author, rating, comment, created_at FROM reviews WHERE user_id = :id', $id);

$filmRows = fetchList($pdo,
    'SELECT id, project_title, project_type, role, production_name, director,
            year, description, project_link, link_type, poster_image, media
     FROM filmography WHERE user_id = :id', $id);
$filmography = [];
foreach ($filmRows as $f) {
    $f['media'] = $f['media'] ? json_decode($f['media'], true) : [];
    $filmography[] = $f;
}

// --- 4. listes specifiques au technicien ---
$software_skills = fetchList($pdo,
    'SELECT id, name, level FROM software_skills WHERE user_id = :id', $id);

$certifications = fetchList($pdo,
    'SELECT id, name, year FROM certifications WHERE user_id = :id', $id);

// secondary_roles + mastered_equipment : listes de strings (colonne `value`)
$secRows = fetchList($pdo,
    'SELECT value FROM secondary_roles WHERE user_id = :id', $id);
$secondary_roles = array_map(fn($r) => $r['value'], $secRows);

$mastRows = fetchList($pdo,
    'SELECT value FROM mastered_equipment WHERE user_id = :id', $id);
$mastered_equipment = array_map(fn($r) => $r['value'], $mastRows);

$availability_calendar = fetchList($pdo,
    'SELECT id, start_date, end_date, status, note
     FROM availability_calendar WHERE user_id = :id', $id);

// --- 5. on reassemble l'objet profil complet ---
$profile = [
    // compte
    'id'             => (string) $u['id'],
    'name'           => $u['name'],
    // email VOLONTAIREMENT non renvoye : endpoint public (anti-scraping / RGPD).
    // L'email du compte connecte vient de me.php (authentifie).
    'user_type'      => $u['user_type'],
    'gender'         => $u['gender'] ?? null,
    'image'          => $u['image'],
    'cover_image'    => $u['cover_image'],
    'isVerified'     => (bool) $u['is_verified'],
    'accountStatus'  => $u['account_status'],
    // calcules
    'score'             => (int) $u['score'],
    'credibility_level' => $u['credibility_level'],
    'rating'            => $u['rating'],
    'reviews'           => (int) $u['reviews_count'],
    // details technicien (scalaires)
    'role'                  => $p['role'] ?? '',
    'about'                 => $p['about'] ?? '',
    'location'              => $p['location'] ?? '',
    'crew_ready'            => isset($p['crew_ready']) ? (bool) $p['crew_ready'] : false,
    'availability_status'   => $p['availability_status'] ?? '',
    'availability_start_date' => $p['availability_start_date'] ?? '',
    'availability_end_date'   => $p['availability_end_date'] ?? '',
    'availability_note'     => $p['availability_note'] ?? '',
    'daily_rate'            => $p['daily_rate'] ?? '',
    'daily_rate_visibility' => isset($p['daily_rate_visibility']) ? (bool) $p['daily_rate_visibility'] : false,
    // objets JSON
    'equipment'  => jsonOr($p['equipment'] ?? null, [
        'cameras' => '', 'lenses' => '', 'micros' => '',
        'lights' => '', 'drone' => '', 'accessories' => '',
    ]),
    'mobility'   => jsonOr($p['mobility'] ?? null, [
        'zone' => '', 'travel' => false, 'international' => false,
    ]),
    'profession' => jsonOr($p['profession'] ?? null, [
        'association' => '', 'validatedShoots' => '', 'shootingsCount' => '',
    ]),
    // listes specifiques tech
    'secondary_roles'       => $secondary_roles,
    'mastered_equipment'    => $mastered_equipment,
    'software_skills'       => $software_skills,
    'certifications'        => $certifications,
    'availability_calendar' => $availability_calendar,
    // listes communes
    'skills'               => $skills,
    'formations'           => $formations,
    'awards_list'          => $awards_list,
    'gallery_items'        => $gallery_items,
    'filmography'          => $filmography,
    'ratings_reviews_list' => $ratings_reviews_list,
];

json_response(['ok' => true, 'profile' => $profile]);
