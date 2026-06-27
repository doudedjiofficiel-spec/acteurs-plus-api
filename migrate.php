<?php
// =====================================================================
//  migrate.php — MIGRATION idempotente (a lancer UNE fois au deploiement)
//  Usage : en ligne de commande UNIQUEMENT (Terminal cPanel ou local) :
//      php migrate.php
//  Refuse l'execution via HTTP (pas un endpoint public).
//  Cree :
//   1. la table `rate_limits` (anti-brute-force / anti-spam) ;
//   2. l'unicite des avis : 1 avis par (cible, auteur) -> dedoublonne
//      l'existant puis ajoute un index UNIQUE.
//  Re-executable sans risque (verifie l'existant avant d'agir).
//  Equivalent SQL pour phpMyAdmin : voir migration.sql.
// =====================================================================

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    header('Content-Type: text/plain; charset=utf-8');
    echo "Interdit : a lancer en ligne de commande (php migrate.php).\n";
    exit;
}

require __DIR__ . '/config.php';   // fournit $pdo (et masque les erreurs selon l'env)

function step($msg) { echo "  - $msg\n"; }

echo "== Migration acteurs-plus-api ==\n";

try {
    // ---- 1. Table rate_limits ----
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS rate_limits (
            id         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            action     VARCHAR(32)  NOT NULL,
            rl_key     VARCHAR(190) NOT NULL,
            created_at DATETIME     NOT NULL,
            PRIMARY KEY (id),
            KEY idx_rl (action, rl_key, created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    );
    step("Table 'rate_limits' prete.");

    // ---- 2. Unicite des avis (user_id, author_uid) ----
    // 2a. Dedoublonnage : on garde l'avis le PLUS RECENT (id max) par couple.
    $deleted = $pdo->exec(
        "DELETE r1 FROM reviews r1
         JOIN reviews r2
           ON r1.user_id    = r2.user_id
          AND r1.author_uid = r2.author_uid
          AND r1.author_uid IS NOT NULL
          AND r1.id < r2.id"
    );
    step("Doublons d'avis supprimes : " . (int) $deleted . ".");

    // 2b. Ajout de l'index UNIQUE seulement s'il n'existe pas deja.
    $idxName = 'uniq_review_author';
    $chk = $pdo->prepare(
        "SELECT COUNT(*) FROM information_schema.statistics
         WHERE table_schema = DATABASE() AND table_name = 'reviews' AND index_name = :idx"
    );
    $chk->execute([':idx' => $idxName]);
    if ((int) $chk->fetchColumn() === 0) {
        $pdo->exec("ALTER TABLE reviews ADD UNIQUE KEY {$idxName} (user_id, author_uid)");
        step("Index UNIQUE '{$idxName}' ajoute (1 avis par cible/auteur).");
    } else {
        step("Index UNIQUE '{$idxName}' deja present.");
    }

    echo "== Migration terminee avec succes. ==\n";
} catch (Throwable $e) {
    echo "ERREUR de migration : " . $e->getMessage() . "\n";
    exit(1);
}
