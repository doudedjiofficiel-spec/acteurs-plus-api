<?php
// =====================================================================
//  create_casting.php — publie un casting (RECRUTEUR uniquement)
//  POST + Authorization: Bearer <token>
//  Body JSON : { title, role, description, location, closeDate, deadline,
//    projectType, status, image, company, roleAge, rolePhysique,
//    rolePsychologie, roleArc, criteriaRequired[], criteriaPreferred[],
//    budgetMode, budgetAmount, shootDates, duration }
// ---------------------------------------------------------------------
//  SECURITE : created_by = l'utilisateur du TOKEN (jamais un id du client).
//  Reserve aux recruteurs (user_type lu en base). Requetes preparees.
//  Les listes de criteres sont stockees en JSON (colonnes longtext).
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

// Normalise une liste de criteres (tableau ou "a, b, c") -> tableau de strings.
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

$status = (($in['status'] ?? '') === 'fermé') ? 'fermé' : 'ouvert';

$stmt = $pdo->prepare(
    'INSERT INTO castings
        (title, role, description, location, deadline, close_date_legacy, project_type,
         status, image, company, role_age, role_physique, role_psychologie, role_arc,
         criteria_required, criteria_preferred, budget_mode, budget_amount, shoot_dates,
         duration, created_by)
     VALUES
        (:title, :role, :description, :location, :deadline, :close_date_legacy, :project_type,
         :status, :image, :company, :role_age, :role_physique, :role_psychologie, :role_arc,
         :criteria_required, :criteria_preferred, :budget_mode, :budget_amount, :shoot_dates,
         :duration, :created_by)'
);

$stmt->execute([
    ':title'             => $title,
    ':role'              => trim($in['role'] ?? ''),
    ':description'       => trim($in['description'] ?? ''),
    ':location'          => trim($in['location'] ?? ''),
    ':deadline'          => (($in['deadline'] ?? '') !== '') ? $in['deadline'] : null,
    ':close_date_legacy' => trim($in['closeDate'] ?? ''),
    ':project_type'      => trim($in['projectType'] ?? ''),
    ':status'            => $status,
    ':image'             => trim($in['image'] ?? ''),
    ':company'           => trim($in['company'] ?? ''),
    ':role_age'          => trim($in['roleAge'] ?? ''),
    ':role_physique'     => trim($in['rolePhysique'] ?? ''),
    ':role_psychologie'  => trim($in['rolePsychologie'] ?? ''),
    ':role_arc'          => trim($in['roleArc'] ?? ''),
    ':criteria_required' => json_encode($toList($in['criteriaRequired'] ?? [])),
    ':criteria_preferred'=> json_encode($toList($in['criteriaPreferred'] ?? [])),
    ':budget_mode'       => trim($in['budgetMode'] ?? ''),
    ':budget_amount'     => (string) ($in['budgetAmount'] ?? '0'),
    ':shoot_dates'       => trim($in['shootDates'] ?? ''),
    ':duration'          => trim($in['duration'] ?? ''),
    ':created_by'        => $userId,
]);

json_response(['ok' => true, 'id' => (int) $pdo->lastInsertId()]);
