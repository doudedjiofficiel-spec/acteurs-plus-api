<?php
// =====================================================================
//  get_casting.php — LIT un casting par id
//  Appel : GET http://localhost/acteurs-plus-api/get_casting.php?id=1
//  (pas de token : detail public)
// ---------------------------------------------------------------------
//  Renvoie tous les champs (criteres relus du JSON) + created_by en
//  'recruiter_<id>' (pour savoir qui possede / afficher l'editeur).
//  Aucune donnee sensible (pas d'email).
// =====================================================================

require_once 'config.php';

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($id <= 0) {
    json_response(['ok' => false, 'error' => 'id manquant ou invalide'], 422);
}

$stmt = $pdo->prepare(
    "SELECT id, title, role, description, location, deadline, close_date_legacy,
            project_type, status, image, company, role_age, role_physique,
            role_psychologie, role_arc, criteria_required, criteria_preferred,
            budget_mode, budget_amount, shoot_dates, duration, created_by, created_at
     FROM castings WHERE id = :id"
);
$stmt->execute([':id' => $id]);
$r = $stmt->fetch();

if (!$r) {
    json_response(['ok' => false, 'error' => 'Casting introuvable'], 404);
}

$casting = [
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

json_response(['ok' => true, 'casting' => $casting]);
