<?php
// =====================================================================
//  get_mission.php — LIT une mission technique par id
//  Appel : GET http://localhost/acteurs-plus-api/get_mission.php?id=1
//  (pas de token : detail public ; pas de filtre status pour les liens directs)
// ---------------------------------------------------------------------
//  Listes relues du JSON + created_by en 'recruiter_<id>'. Aucun email.
// =====================================================================

require_once 'config.php';

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($id <= 0) {
    json_response(['ok' => false, 'error' => 'id manquant ou invalide'], 422);
}

$stmt = $pdo->prepare(
    "SELECT id, title, description, departments, roles, location, start_date, end_date,
            deadline, project_type, image, profile_wanted, criteria_required,
            criteria_preferred, budget, budget_mode, budget_visibility, duration,
            status, company, created_by, created_at
     FROM missions WHERE id = :id"
);
$stmt->execute([':id' => $id]);
$r = $stmt->fetch();

if (!$r) {
    json_response(['ok' => false, 'error' => 'Mission introuvable'], 404);
}

$mission = [
    'id'                => (string) $r['id'],
    'title'             => $r['title'],
    'description'       => $r['description'] ?? '',
    'departments'       => $r['departments'] ? (json_decode($r['departments'], true) ?: []) : [],
    'roles'             => $r['roles'] ? (json_decode($r['roles'], true) ?: []) : [],
    'location'          => $r['location'] ?? '',
    'startDate'         => $r['start_date'] ?? '',
    'endDate'           => $r['end_date'] ?? '',
    'deadline'          => $r['deadline'] ? substr($r['deadline'], 0, 10) : '',
    'projectType'       => $r['project_type'] ?? '',
    'image'             => $r['image'] ?? '',
    'profileWanted'     => $r['profile_wanted'] ?? '',
    'criteriaRequired'  => $r['criteria_required'] ? (json_decode($r['criteria_required'], true) ?: []) : [],
    'criteriaPreferred' => $r['criteria_preferred'] ? (json_decode($r['criteria_preferred'], true) ?: []) : [],
    'budget'            => (int) ($r['budget'] ?? 0),
    'budgetMode'        => $r['budget_mode'] ?? '',
    'budgetVisibility'  => (bool) ($r['budget_visibility'] ?? 0),
    'duration'          => $r['duration'] ?? '',
    'status'            => $r['status'] ?? 'ouverte',
    'company'           => $r['company'] ?? '',
    'createdBy'         => $r['created_by'] ? ('recruiter_' . $r['created_by']) : '',
    'createdAt'         => $r['created_at'],
];

json_response(['ok' => true, 'mission' => $mission]);
