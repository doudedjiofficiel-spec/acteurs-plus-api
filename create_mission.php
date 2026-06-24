<?php
// =====================================================================
//  create_mission.php — publie une MISSION technique (RECRUTEUR uniquement)
//  POST + Authorization: Bearer <token>
//  Body JSON : { title, description, departments[], roles[], location,
//    startDate, endDate, deadline, projectType, image, profileWanted,
//    criteriaRequired[], criteriaPreferred[], budget, budgetMode,
//    budgetVisibility, duration, status, company }
// ---------------------------------------------------------------------
//  SECURITE : created_by = l'utilisateur du TOKEN (jamais un id du client).
//  Reserve aux recruteurs (user_type lu en base). Requetes preparees.
//  Les listes (departments/roles/criteres) sont stockees en JSON (longtext).
// =====================================================================

require_once 'config.php';
require_once 'auth_check.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['ok' => false, 'error' => 'Methode non autorisee'], 405);
}

$userId = require_auth($pdo);

// ---- Reserve aux recruteurs : on lit le type EN BASE (jamais le client) ----
$st = $pdo->prepare('SELECT user_type FROM users WHERE id = :id');
$st->execute([':id' => $userId]);
$u = $st->fetch();
if (!$u || $u['user_type'] !== 'recruiter') {
    json_response(['ok' => false, 'error' => 'Publication reservee aux recruteurs'], 403);
}

$in = get_json_input();

// Titre obligatoire (colonne NOT NULL).
$title = trim($in['title'] ?? '');
if ($title === '') {
    json_response(['ok' => false, 'error' => 'Le titre est obligatoire'], 422);
}

// Normalise une liste (tableau ou "a, b, c") -> tableau de strings.
$toList = function ($v) {
    if (is_array($v)) {
        $v = array_map(fn($x) => trim((string) $x), $v);
    } elseif (is_string($v)) {
        $v = array_map('trim', explode(',', $v));
    } else {
        $v = [];
    }
    return array_values(array_filter($v, fn($x) => $x !== ''));
};

// Date '' -> NULL (colonnes date/datetime).
$dateOrNull = fn($v) => (($v ?? '') !== '') ? $v : null;

$status = (($in['status'] ?? '') === 'fermée') ? 'fermée' : 'ouverte';

$stmt = $pdo->prepare(
    'INSERT INTO missions
        (title, description, departments, roles, location, start_date, end_date,
         deadline, project_type, image, profile_wanted, criteria_required,
         criteria_preferred, budget, budget_mode, budget_visibility, duration,
         status, company, created_by)
     VALUES
        (:title, :description, :departments, :roles, :location, :start_date, :end_date,
         :deadline, :project_type, :image, :profile_wanted, :criteria_required,
         :criteria_preferred, :budget, :budget_mode, :budget_visibility, :duration,
         :status, :company, :created_by)'
);

$stmt->execute([
    ':title'              => $title,
    ':description'        => trim($in['description'] ?? ''),
    ':departments'        => json_encode($toList($in['departments'] ?? [])),
    ':roles'              => json_encode($toList($in['roles'] ?? [])),
    ':location'           => trim($in['location'] ?? ''),
    ':start_date'         => $dateOrNull($in['startDate'] ?? ''),
    ':end_date'           => $dateOrNull($in['endDate'] ?? ''),
    ':deadline'           => $dateOrNull($in['deadline'] ?? ''),
    ':project_type'       => trim($in['projectType'] ?? ''),
    ':image'              => trim($in['image'] ?? ''),
    ':profile_wanted'     => trim($in['profileWanted'] ?? ''),
    ':criteria_required'  => json_encode($toList($in['criteriaRequired'] ?? [])),
    ':criteria_preferred' => json_encode($toList($in['criteriaPreferred'] ?? [])),
    ':budget'             => (string) ($in['budget'] ?? '0'),
    ':budget_mode'        => trim($in['budgetMode'] ?? ''),
    ':budget_visibility'  => !empty($in['budgetVisibility']) ? '1' : '0',
    ':duration'           => trim($in['duration'] ?? ''),
    ':status'             => $status,
    ':company'            => trim($in['company'] ?? ''),
    ':created_by'         => $userId,
]);

json_response(['ok' => true, 'id' => (int) $pdo->lastInsertId()]);
