<?php
// =====================================================================
//  list_castings.php — LISTE des castings publics NON archives
//  Appel : GET http://localhost/acteurs-plus-api/list_castings.php
//  (pas de token : annuaire public)
// ---------------------------------------------------------------------
//  Non archive = status 'ouvert' ET deadline non passee (ou nulle).
//  Critreres relus depuis le JSON ; created_by renvoye en 'recruiter_<id>'
//  (convention front). Aucune donnee sensible.
// =====================================================================

require_once 'config.php';

$rows = $pdo->query(
    "SELECT id, title, role, description, location, deadline, close_date_legacy,
            project_type, status, image, company, role_age, role_physique,
            role_psychologie, role_arc, criteria_required, criteria_preferred,
            budget_mode, budget_amount, shoot_dates, duration, created_by, created_at
     FROM castings
     WHERE status = 'ouvert'
       AND (deadline IS NULL OR deadline >= CURDATE())
     ORDER BY created_at DESC"
)->fetchAll();

$castings = [];
foreach ($rows as $r) {
    $castings[] = [
        'id'                => (string) $r['id'],
        'title'             => $r['title'],
        'role'              => $r['role'] ?? '',
        'description'       => $r['description'] ?? '',
        'location'          => $r['location'] ?? '',
        'deadline'          => $r['deadline'] ? substr($r['deadline'], 0, 10) : '',
        'closeDate'         => $r['close_date_legacy'] ?? '',
        'projectType'       => $r['project_type'] ?? '',
        'status'            => $r['status'] ?? 'ouvert',
        'image'             => $r['image'] ?? '',
        'company'           => $r['company'] ?? '',
        'roleAge'           => $r['role_age'] ?? '',
        'rolePhysique'      => $r['role_physique'] ?? '',
        'rolePsychologie'   => $r['role_psychologie'] ?? '',
        'roleArc'           => $r['role_arc'] ?? '',
        'criteriaRequired'  => $r['criteria_required'] ? (json_decode($r['criteria_required'], true) ?: []) : [],
        'criteriaPreferred' => $r['criteria_preferred'] ? (json_decode($r['criteria_preferred'], true) ?: []) : [],
        'budgetMode'        => $r['budget_mode'] ?? '',
        'budgetAmount'      => (int) ($r['budget_amount'] ?? 0),
        'shootDates'        => $r['shoot_dates'] ?? '',
        'duration'          => $r['duration'] ?? '',
        'createdBy'         => $r['created_by'] ? ('recruiter_' . $r['created_by']) : '',
        'createdAt'         => $r['created_at'],
    ];
}

json_response(['ok' => true, 'castings' => $castings]);
