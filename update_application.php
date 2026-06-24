<?php
// =====================================================================
//  update_application.php — change le statut / la note / l'etoile d'une candidature
//  POST + Authorization: Bearer <token>
//  Body JSON : { id:<int>, status?, note?, toAudition? }
// ---------------------------------------------------------------------
//  SECURITE (non negociable) :
//   - RECRUTEUR PROPRIETAIRE (owner_uid = token) : peut changer
//     status / note / to_audition (kanban).
//   - CANDIDAT (candidate_uid = token) : peut UNIQUEMENT retirer sa
//     candidature (status='retiree').
//   - Sinon 403. updated_at est gere par la base (ON UPDATE).
//   - On ne fait jamais confiance a un owner_uid / candidate_uid du client :
//     les WHERE imposent l'identite du token.
// =====================================================================

require_once 'config.php';
require_once 'auth_check.php';
require_once 'notif_helper.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['ok' => false, 'error' => 'Methode non autorisee'], 405);
}

$userId = require_auth($pdo);

// Libelles lisibles + couleur d'accent pour la notif de statut.
$STATUS_LABELS = [
    'recue' => 'Reçue', 'preselection' => 'Présélection', 'shortlist' => 'Shortlist',
    'convoque' => 'Convoqué', 'retenu' => 'Retenu', 'refuse' => 'Non retenu', 'retiree' => 'Retirée',
];
$STATUS_COLOR = function ($s) {
    if ($s === 'retenu') return '#2ecc71';
    if ($s === 'refuse') return '#e74c3c';
    return '#C97A0A';
};

$in = get_json_input();
$id = (int) ($in['id'] ?? 0);
if ($id <= 0) {
    json_response(['ok' => false, 'error' => 'Candidature manquante ou invalide'], 422);
}

// Statuts autorises (pipeline + clotures).
$VALID_STATUS = ['recue', 'preselection', 'shortlist', 'convoque', 'retenu', 'refuse', 'retiree'];

// ---- On lit la candidature pour savoir qui est qui (+ titre + statut actuel) ----
$st = $pdo->prepare('SELECT candidate_uid, owner_uid, target_title, status FROM applications WHERE id = :id');
$st->execute([':id' => $id]);
$app = $st->fetch();
if (!$app) {
    json_response(['ok' => false, 'error' => 'Candidature introuvable'], 404);
}
$oldStatus = $app['status'];

$isOwner     = ($app['owner_uid'] !== null) && ((int) $app['owner_uid'] === $userId);
$isCandidate = ((int) $app['candidate_uid'] === $userId);

// ─────────────────────────────────────────────────────────────────────
//  CAS 1 — RECRUTEUR PROPRIETAIRE : status / note / to_audition
// ─────────────────────────────────────────────────────────────────────
if ($isOwner) {
    $sets   = [];
    $params = [':id' => $id, ':uid' => $userId];

    if (array_key_exists('status', $in)) {
        $status = (string) $in['status'];
        if (!in_array($status, $VALID_STATUS, true)) {
            json_response(['ok' => false, 'error' => 'Statut invalide'], 422);
        }
        $sets[] = 'status = :status';
        $params[':status'] = $status;
    }
    if (array_key_exists('note', $in)) {
        $sets[] = 'note = :note';
        $params[':note'] = trim((string) $in['note']);
    }
    if (array_key_exists('toAudition', $in)) {
        $sets[] = 'to_audition = :ta';
        $params[':ta'] = $in['toAudition'] ? 1 : 0;
    }

    if (!$sets) {
        json_response(['ok' => false, 'error' => 'Rien a mettre a jour'], 422);
    }

    // WHERE owner_uid = token : double garde (l'identite ne peut pas etre usurpee).
    $sql = 'UPDATE applications SET ' . implode(', ', $sets) . ' WHERE id = :id AND owner_uid = :uid';
    $pdo->prepare($sql)->execute($params);

    // ---- Notif CIBLEE au CANDIDAT : UNIQUEMENT si le STATUT change ----
    // (pas de notif pour une simple note ou un marque-page "a auditionner".)
    if (array_key_exists('status', $in) && $status !== $oldStatus) {
        $candidateId = (int) $app['candidate_uid'];
        if ($candidateId !== $userId) {
            $label = $STATUS_LABELS[$status] ?? $status;
            create_notification(
                $pdo,
                $candidateId,
                'Votre candidature a « ' . ($app['target_title'] ?? '') . ' » : ' . $label,
                $STATUS_COLOR($status),
                '/dashboard'   // espace candidat : suivi de ses candidatures
            );
        }
    }

    json_response(['ok' => true]);
}

// ─────────────────────────────────────────────────────────────────────
//  CAS 2 — CANDIDAT : uniquement retirer SA candidature
// ─────────────────────────────────────────────────────────────────────
if ($isCandidate) {
    $status = (string) ($in['status'] ?? '');
    if ($status !== 'retiree') {
        json_response(['ok' => false, 'error' => 'Action non autorisee'], 403);
    }
    $upd = $pdo->prepare(
        "UPDATE applications SET status = 'retiree' WHERE id = :id AND candidate_uid = :uid"
    );
    $upd->execute([':id' => $id, ':uid' => $userId]);
    json_response(['ok' => true]);
}

// ─────────────────────────────────────────────────────────────────────
//  Ni proprietaire ni candidat -> interdit
// ─────────────────────────────────────────────────────────────────────
json_response(['ok' => false, 'error' => 'Action reservee au recruteur proprietaire'], 403);
