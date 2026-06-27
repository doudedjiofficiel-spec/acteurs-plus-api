<?php
// =====================================================================
//  update_profile.php — sauvegarde les champs SIMPLES du profil ACTEUR
//  Appel : POST http://localhost/acteurs-plus-api/update_profile.php
//  Header : Authorization: Bearer <token>
//  Body JSON : { "name":"...", "about":"...", "city":"...", ... }
// ---------------------------------------------------------------------
//  SECURITE :
//   - Le token decide QUI on modifie (jamais un id venant du client)
//     -> impossible de modifier le profil d'un autre.
//   - Liste blanche de champs : tout champ non liste est IGNORE
//     -> impossible d'injecter score, is_verified, roster, etc.
//   - Requetes preparees -> pas d'injection SQL.
// =====================================================================

require_once 'config.php';
require_once 'auth_check.php';
require_once 'validators.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['ok' => false, 'error' => 'Methode non autorisee'], 405);
}

// Qui est connecte ? (verifie le token, sinon stoppe avec 401)
$userId = require_auth($pdo);

$input = get_json_input();

// ---- Garde-fou taille/format des images (anti-DoS / bloat) ----
foreach (['image', 'cover_image'] as $imgKey) {
    if (array_key_exists($imgKey, $input)) {
        $err = image_value_error($input[$imgKey]);
        if ($err !== null) {
            json_response(['ok' => false, 'error' => $err], 422);
        }
    }
}

// ---- Garde-fou longueur des champs texte (bornes larges, anti-bloat) ----
$TEXT_LIMITS = [
    'name' => MAX_NAME, 'about' => MAX_BIO,
    'showreelLink' => MAX_URL, 'cvUrl' => MAX_URL,
    'stageName' => MAX_SHORT, 'role' => MAX_SHORT, 'nationality' => MAX_SHORT,
    'height' => MAX_SHORT, 'weight' => MAX_SHORT, 'eyesColor' => MAX_SHORT,
    'hairColor' => MAX_SHORT, 'morphology' => MAX_SHORT, 'accents' => MAX_SHORT,
    'dialects' => MAX_SHORT, 'location' => MAX_SHORT, 'country' => MAX_SHORT,
    'city' => MAX_SHORT, 'mobility_type' => MAX_SHORT, 'intervention_zone' => MAX_SHORT,
    'showreelType' => MAX_SHORT, 'professional_organization' => MAX_SHORT,
    'currency' => MAX_SHORT,
];
foreach ($TEXT_LIMITS as $k => $max) {
    if (array_key_exists($k, $input) && text_too_long($input[$k], $max)) {
        json_response(['ok' => false, 'error' => "Champ « {$k} » trop long (max {$max} caracteres)."], 422);
    }
}

// ---- LISTE BLANCHE : cle envoyee par React  =>  colonne en base ----
// (seuls ces champs simples de l'acteur sont acceptes pour ce commit)
$map = [
    'stageName'                 => 'stage_name',
    'role'                      => 'role',
    'about'                     => 'about',
    'birthdate'                 => 'birthdate',
    'nationality'               => 'nationality',
    'height'                    => 'height',
    'weight'                    => 'weight',
    'eyesColor'                 => 'eyes_color',
    'hairColor'                 => 'hair_color',
    'morphology'                => 'morphology',
    'accents'                   => 'accents',
    'dialects'                  => 'dialects',
    'location'                  => 'location',
    'country'                   => 'country',
    'city'                      => 'city',
    'mobility_type'             => 'mobility_type',
    'intervention_zone'         => 'intervention_zone',
    'showreelType'              => 'showreel_type',
    'showreelLink'              => 'showreel_link',
    'cvUrl'                     => 'cv_url',
    'professional_organization' => 'professional_organization',
    'currency'                  => 'currency',
];

// ---- 1. Champs de la table users : name + image + cover_image ----
// On ne met a jour QUE les colonnes reellement envoyees (cle presente dans
// $input). Si 'image' n'est pas dans le payload, on n'y touche pas -> on
// n'ecrase pas une photo existante quand on enregistre un autre champ.
// (image / cover_image sont en MEDIUMTEXT : un dataURL base64 tient sans
//  limite de 500 ; valeurs liees en parametre PDO, jamais concatenees.)
$userCols   = [];
$userParams = [':id' => $userId];

if (array_key_exists('name', $input) && trim($input['name']) !== '') {
    $userCols[]          = 'name = :name';
    $userParams[':name'] = trim($input['name']);
}
if (array_key_exists('image', $input)) {
    $userCols[]           = 'image = :image';
    $userParams[':image'] = $input['image'];
}
if (array_key_exists('cover_image', $input)) {
    $userCols[]                 = 'cover_image = :cover_image';
    $userParams[':cover_image'] = $input['cover_image'];
}
if (array_key_exists('gender', $input)) {
    $userCols[]            = 'gender = :gender';
    $userParams[':gender'] = ($input['gender'] === 'H' || $input['gender'] === 'F') ? $input['gender'] : null;
}

if (count($userCols) > 0) {
    $stmt = $pdo->prepare('UPDATE users SET ' . implode(', ', $userCols) . ' WHERE id = :id');
    $stmt->execute($userParams);
}

// ---- 2. Les autres champs vont dans actor_profiles ----
// On ne prend QUE les champs reellement envoyes (presents dans $input).
$cols = [];
$placeholders = [];
$updates = [];
$params = [':user_id' => $userId];

foreach ($map as $inKey => $col) {
    if (!array_key_exists($inKey, $input)) {
        continue; // champ non envoye -> on n'y touche pas
    }
    $val = $input[$inKey];

    // birthdate vide -> NULL (une colonne DATE ne peut pas valoir '')
    if ($col === 'birthdate' && ($val === '' || $val === null)) {
        $val = null;
    }

    $cols[]         = $col;
    $placeholders[] = ':' . $col;
    $updates[]      = "$col = VALUES($col)";
    $params[':' . $col] = $val;
}

// S'il y a au moins un champ a ecrire : INSERT, ou UPDATE si la ligne existe deja.
if (count($cols) > 0) {
    $sql = 'INSERT INTO actor_profiles (user_id, ' . implode(', ', $cols) . ')
            VALUES (:user_id, ' . implode(', ', $placeholders) . ')
            ON DUPLICATE KEY UPDATE ' . implode(', ', $updates);
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
}

json_response(['ok' => true]);
