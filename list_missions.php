<?php
// =====================================================================
//  list_missions.php — LISTE des missions techniques publiques NON archivees
//  Appel : GET http://localhost/acteurs-plus-api/list_missions.php
//  (pas de token : annuaire public)
// ---------------------------------------------------------------------
//  Non archive = status 'ouverte' ET deadline non passee (ou nulle).
//  Listes (departments/roles/criteres) relues du JSON ; created_by renvoye
//  en 'recruiter_<id>'. Aucune donnee sensible.
// =====================================================================

require_once 'config.php';

$rows = $pdo->query(
    "SELECT id, title, description, departments, roles, location, start_date, end_date,
            deadline, project_type, image, profile_wanted, criteria_required,
            criteria_preferred, budget, budget_mode, budget_visibility, duration,
            status, company, created_by, created_at
     FROM missions
     WHERE status = 'ouverte'
       AND (deadline IS NULL OR deadline >= CURDATE())
     ORDER BY created_at DESC"
)->fetchAll();

$missions = [];
foreach ($rows as $r) {
    $missions[] = [
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
}

json_response(['ok' => true, 'missions' => $missions]);
