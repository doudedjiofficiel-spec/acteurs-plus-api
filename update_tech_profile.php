<?php
// =====================================================================
//  update_tech_profile.php — sauvegarde les champs simples + objets
//  du profil TECHNICIEN.
//  POST + Authorization: Bearer <token>
//  Body JSON : { name, role, about, location, crew_ready,
//    availability_status, availability_start_date, availability_end_date,
//    availability_note, daily_rate, daily_rate_visibility,
//    software, hardware,
//    equipment:{...}, mobility:{...}, profession:{...} }
// ---------------------------------------------------------------------
//  SECURITE : token decide QUI ; liste blanche (tout champ non liste est
//  ignore) ; requetes preparees. Les objets sont stockes en JSON.
// =====================================================================

require_once 'config.php';
require_once 'auth_check.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['ok' => false, 'error' => 'Methode non autorisee'], 405);
}

$userId = require_auth($pdo);
$input  = get_json_input();

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

// ---- 2. Liste blanche : cle envoyee => [colonne, type] ----
//  type : scalar (texte) | bool (0/1) | date ('' -> NULL) | json (objet)
$fields = [
    'role'                    => ['role', 'scalar'],
    'about'                   => ['about', 'scalar'],
    'location'                => ['location', 'scalar'],
    'crew_ready'              => ['crew_ready', 'bool'],
    'availability_status'     => ['availability_status', 'scalar'],
    'availability_start_date' => ['availability_start_date', 'date'],
    'availability_end_date'   => ['availability_end_date', 'date'],
    'availability_note'       => ['availability_note', 'scalar'],
    'daily_rate'              => ['daily_rate', 'scalar'],
    'daily_rate_visibility'   => ['daily_rate_visibility', 'bool'],
    'software'                => ['software_legacy', 'scalar'],  // legacy
    'hardware'                => ['hardware_legacy', 'scalar'],  // legacy
    'equipment'               => ['equipment', 'json'],
    'mobility'                => ['mobility', 'json'],
    'profession'              => ['profession', 'json'],
];

$cols = [];
$placeholders = [];
$updates = [];
$params = [':user_id' => $userId];

foreach ($fields as $inKey => $def) {
    if (!array_key_exists($inKey, $input)) {
        continue; // champ non envoye -> on n'y touche pas
    }
    [$col, $kind] = $def;
    $val = $input[$inKey];

    if ($kind === 'bool') {
        $val = $val ? 1 : 0;
    } elseif ($kind === 'date') {
        if ($val === '' || $val === null) { $val = null; }
    } elseif ($kind === 'json') {
        // on n'enregistre que si c'est bien un objet/tableau
        $val = is_array($val) ? json_encode($val) : null;
    } else { // scalar
        $val = is_string($val) ? $val : (string) $val;
    }

    $cols[]         = $col;
    $placeholders[] = ':' . $col;
    $updates[]      = "$col = VALUES($col)";
    $params[':' . $col] = $val;
}

if (count($cols) > 0) {
    $sql = 'INSERT INTO technician_profiles (user_id, ' . implode(', ', $cols) . ')
            VALUES (:user_id, ' . implode(', ', $placeholders) . ')
            ON DUPLICATE KEY UPDATE ' . implode(', ', $updates);
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
}

json_response(['ok' => true]);
