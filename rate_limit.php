<?php
// =====================================================================
//  rate_limit.php — OUTIL reutilisable (pas un endpoint appele seul)
//  Limitation de debit portable (PHP + MariaDB, zero dependance).
//  S'appuie sur la table `rate_limits` (voir migrate.php / migration SQL).
//  Chaque "tentative" = une ligne (action, rl_key, created_at). On COMPTE
//  les lignes d'une cle dans une fenetre de temps pour decider du blocage.
// =====================================================================

// IP du client. On utilise REMOTE_ADDR (pose par le serveur, NON usurpable).
// On NE fait PAS confiance a X-Forwarded-For (forgeable -> contournement).
function client_ip() {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    return is_string($ip) && $ip !== '' ? substr($ip, 0, 190) : 'unknown';
}

// True si la cle a atteint/depasse $max tentatives dans les $windowSec dernieres secondes.
// IMPORTANT : la fenetre est calculee sur l'horloge MySQL (NOW()), comme l'horodatage
// d'insertion (rl_hit). Melanger time() PHP et NOW() MySQL casse le comptage quand
// les deux horloges/fuseaux different (hebergement mutualise). $windowSec est une
// constante interne -> on l'injecte en (int), pas de risque d'injection.
function rl_blocked($pdo, $action, $key, $max, $windowSec) {
    $win = (int) $windowSec;
    $st = $pdo->prepare(
        "SELECT COUNT(*) FROM rate_limits
         WHERE action = :a AND rl_key = :k
           AND created_at > (NOW() - INTERVAL {$win} SECOND)"
    );
    $st->execute([':a' => $action, ':k' => $key]);
    return ((int) $st->fetchColumn()) >= (int) $max;
}

// Enregistre une tentative (une ligne horodatee). GC probabiliste au passage.
function rl_hit($pdo, $action, $key) {
    $st = $pdo->prepare(
        'INSERT INTO rate_limits (action, rl_key, created_at) VALUES (:a, :k, NOW())'
    );
    $st->execute([':a' => $action, ':k' => $key]);

    // Nettoyage occasionnel (1 fois sur ~50) pour ne pas grossir indefiniment.
    if (mt_rand(1, 50) === 1) {
        rl_gc($pdo);
    }
}

// Efface les tentatives d'une cle (ex. login reussi -> on repart a zero).
function rl_clear($pdo, $action, $key) {
    $st = $pdo->prepare('DELETE FROM rate_limits WHERE action = :a AND rl_key = :k');
    $st->execute([':a' => $action, ':k' => $key]);
}

// Purge les lignes de plus de 24 h (horloge MySQL, coherente avec rl_hit).
function rl_gc($pdo) {
    $pdo->exec('DELETE FROM rate_limits WHERE created_at < (NOW() - INTERVAL 86400 SECOND)');
}
