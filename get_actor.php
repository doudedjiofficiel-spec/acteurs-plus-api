<?php
// =====================================================================
//  get_actor.php — LIT un profil ACTEUR complet depuis la base
//  Appel : GET http://localhost/acteurs-plus-api/get_actor.php?id=1
//  (pas besoin de token : profil public)
// ---------------------------------------------------------------------
//  Recompose le "puzzle" : users + actor_profiles + toutes les tables
//  enfant (skills, languages, filmography, gallery, formations, awards,
//  reviews) -> un seul objet, tel que le React l'attend.
//  SECURITE : password JAMAIS renvoye. Requetes preparees.
// =====================================================================

require_once 'config.php';

// --- recuperer l'id demande ---
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($id <= 0) {
    json_response(['ok' => false, 'error' => 'id manquant ou invalide'], 422);
}

// --- 1. le compte (users) ---
// Garde-fou : on ne renvoie QUE des acteurs (user_type = 'talent').
// Empeche d'afficher un technicien ou un recruteur sur une fiche acteur.
$stmt = $pdo->prepare(
    "SELECT id, name, user_type, gender, image, cover_image, is_verified,
            account_status, score, credibility_level, rating, reviews_count
     FROM users WHERE id = :id AND user_type = 'talent'"
);
$stmt->execute([':id' => $id]);
$u = $stmt->fetch();

if (!$u) {
    json_response(['ok' => false, 'error' => 'Profil introuvable'], 404);
}

// --- 2. les details acteur (actor_profiles) ---
$stmt = $pdo->prepare('SELECT * FROM actor_profiles WHERE user_id = :id');
$stmt->execute([':id' => $id]);
$p = $stmt->fetch() ?: [];

// --- petit utilitaire : recuperer une liste enfant ---
function fetchList($pdo, $sql, $id) {
    $s = $pdo->prepare($sql);
    $s->execute([':id' => $id]);
    return $s->fetchAll();
}

// --- 3. les listes enfant ---
$skills = fetchList($pdo,
    'SELECT id, name, level FROM skills WHERE user_id = :id', $id);

$languages_list = fetchList($pdo,
    'SELECT id, name, level FROM profile_languages WHERE user_id = :id', $id);

$formations = fetchList($pdo,
    'SELECT id, school, degree, year, description FROM formations WHERE user_id = :id', $id);

$awards_list = fetchList($pdo,
    'SELECT id, title, year, project FROM awards WHERE user_id = :id', $id);

$gallery_items = fetchList($pdo,
    'SELECT id, type, url, title, description, category, visibility
     FROM gallery_items WHERE user_id = :id', $id);

$ratings_reviews_list = fetchList($pdo,
    'SELECT id, author, author_uid, rating, comment, recommend, created_at FROM reviews WHERE user_id = :id', $id);

// metiers secondaires : liste de strings (colonne `value`), table partagee avec le technicien
$secRows = fetchList($pdo,
    'SELECT value FROM secondary_roles WHERE user_id = :id', $id);
$secondary_roles = array_map(fn($r) => $r['value'], $secRows);

// filmographie : media est du JSON -> on le redonne en tableau
$filmRows = fetchList($pdo,
    'SELECT id, project_title, project_type, role, production_name, director,
            year, description, project_link, link_type, poster_image, media
     FROM filmography WHERE user_id = :id', $id);
$filmography = [];
foreach ($filmRows as $f) {
    $f['media'] = $f['media'] ? json_decode($f['media'], true) : [];
    $filmography[] = $f;
}

// --- 4. on reassemble l'objet profil complet ---
$profile = [
    // compte
    'id'             => (string) $u['id'],   // le React compare les id en string
    'name'           => $u['name'],
    // email VOLONTAIREMENT non renvoye : endpoint public (anti-scraping / RGPD).
    // L'email du compte connecte vient de me.php (authentifie).
    'user_type'      => $u['user_type'],
    'gender'         => $u['gender'] ?? null,
    'image'          => $u['image'],
    'cover_image'    => $u['cover_image'],
    'isVerified'     => (bool) $u['is_verified'],
    'accountStatus'  => $u['account_status'],
    // calcules (pre-remplis)
    'score'             => (int) $u['score'],
    'credibility_level' => $u['credibility_level'],
    'rating'            => $u['rating'],
    'reviews'           => (int) $u['reviews_count'],
    // details acteur (avec valeurs par defaut si la ligne n'existe pas encore)
    'stageName'                 => $p['stage_name'] ?? '',
    'role'                      => $p['role'] ?? '',
    'about'                     => $p['about'] ?? '',
    'birthdate'                 => $p['birthdate'] ?? '',
    'nationality'               => $p['nationality'] ?? '',
    'height'                    => $p['height'] ?? '',
    'weight'                    => $p['weight'] ?? '',
    'eyesColor'                 => $p['eyes_color'] ?? '',
    'hairColor'                 => $p['hair_color'] ?? '',
    'morphology'                => $p['morphology'] ?? '',
    'accents'                   => $p['accents'] ?? '',
    'dialects'                  => $p['dialects'] ?? '',
    'location'                  => $p['location'] ?? '',
    'country'                   => $p['country'] ?? '',
    'city'                      => $p['city'] ?? '',
    'mobility_type'             => $p['mobility_type'] ?? '',
    'intervention_zone'         => $p['intervention_zone'] ?? '',
    'showreelType'              => $p['showreel_type'] ?? '',
    'showreelLink'              => $p['showreel_link'] ?? '',
    'cvUrl'                     => $p['cv_url'] ?? '',
    'professional_organization' => $p['professional_organization'] ?? '',
    'currency'                  => $p['currency'] ?? 'FCFA',
    'represented_by_agency'     => isset($p['represented_by_agency']) ? (bool) $p['represented_by_agency'] : false,
    // langue en texte (derivee de la liste) pour compat affichage
    'languages'                 => implode(', ', array_map(fn($l) => $l['name'], $languages_list)),
    // listes
    'skills'               => $skills,
    'languages_list'       => $languages_list,
    'formations'           => $formations,
    'awards_list'          => $awards_list,
    'gallery_items'        => $gallery_items,
    'filmography'          => $filmography,
    'ratings_reviews_list' => $ratings_reviews_list,
    'secondary_roles'      => $secondary_roles,
];

json_response(['ok' => true, 'profile' => $profile]);
