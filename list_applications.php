<?php
// =====================================================================
//  list_applications.php — LISTE des candidatures de l'utilisateur connecte
//  GET + Authorization: Bearer <token>
//  Filtres optionnels (recruteur) : ?target_id=<int>&kind=casting|mission
// ---------------------------------------------------------------------
//  Le ROLE decide du perimetre (lu en base via user_type) :
//   - candidat (talent / professional) -> SES candidatures (candidate_uid).
//   - recruteur                         -> celles RECUES (owner_uid).
//  Les uid sont renvoyes prefixes (convention front) :
//   - candidateUid : 'talent_<id>' (casting) ou 'tech_<id>' (mission).
//   - ownerUid     : 'recruiter_<id>'.
//  Aucune donnee sensible (pas d'email).
// =====================================================================

require_once 'config.php';
require_once 'auth_check.php';

$userId = require_auth($pdo);

// ---- Role de l'utilisateur connecte ----
$st = $pdo->prepare('SELECT user_type FROM users WHERE id = :id');
$st->execute([':id' => $userId]);
$me = $st->fetch();
if (!$me) {
    json_response(['ok' => false, 'error' => 'Compte introuvable'], 404);
}
$type = $me['user_type'];

$cols = "id, kind, target_id, target_title, candidate_uid, candidate_name,
         candidate_role, candidate_image, owner_uid, status, note, to_audition,
         created_at, updated_at";

if ($type === 'recruiter') {
    // Recruteur : candidatures RECUES (+ filtres optionnels).
    $where  = ['owner_uid = :uid'];
    $params = [':uid' => $userId];
    if (isset($_GET['target_id']) && (int) $_GET['target_id'] > 0) {
        $where[] = 'target_id = :tid';
        $params[':tid'] = (int) $_GET['target_id'];
    }
    if (isset($_GET['kind']) && in_array($_GET['kind'], ['casting', 'mission'], true)) {
        $where[] = 'kind = :kind';
        $params[':kind'] = $_GET['kind'];
    }
    $sql = "SELECT {$cols} FROM applications WHERE " . implode(' AND ', $where) . " ORDER BY created_at DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
} elseif ($type === 'talent' || $type === 'professional') {
    // Candidat : SES propres candidatures.
    $stmt = $pdo->prepare("SELECT {$cols} FROM applications WHERE candidate_uid = :uid ORDER BY created_at DESC");
    $stmt->execute([':uid' => $userId]);
} else {
    // Autre type (ex. admin) : rien.
    json_response(['ok' => true, 'applications' => []]);
}

$rows = $stmt->fetchAll();

$applications = [];
foreach ($rows as $r) {
    // Prefixe du candidat deduit du kind (casting -> acteur, mission -> technicien).
    $candPrefix = ($r['kind'] === 'mission') ? 'tech_' : 'talent_';
    $applications[] = [
        'id'             => (string) $r['id'],
        'kind'           => $r['kind'],
        'targetId'       => (string) $r['target_id'],
        'targetTitle'    => $r['target_title'] ?? '',
        'candidateUid'   => $candPrefix . $r['candidate_uid'],
        'candidateName'  => $r['candidate_name'] ?? '',
        'candidateRole'  => $r['candidate_role'] ?? '',
        'candidateImage' => $r['candidate_image'] ?? '',
        'ownerUid'       => $r['owner_uid'] !== null ? ('recruiter_' . $r['owner_uid']) : '',
        'status'         => $r['status'] ?? 'recue',
        'note'           => $r['note'] ?? '',
        'toAudition'     => (bool) $r['to_audition'],
        'createdAt'      => $r['created_at'],
        'updatedAt'      => $r['updated_at'],
    ];
}

json_response(['ok' => true, 'applications' => $applications]);
