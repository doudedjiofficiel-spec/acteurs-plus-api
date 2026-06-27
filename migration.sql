-- =====================================================================
--  migration.sql — equivalent phpMyAdmin de migrate.php
--  A importer UNE fois (onglet "SQL" de phpMyAdmin, base acteurs_plus).
--  Idempotent : re-executable sans casse.
-- =====================================================================

-- 1. Table anti-brute-force / anti-spam ------------------------------
CREATE TABLE IF NOT EXISTS rate_limits (
    id         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    action     VARCHAR(32)  NOT NULL,
    rl_key     VARCHAR(190) NOT NULL,
    created_at DATETIME     NOT NULL,
    PRIMARY KEY (id),
    KEY idx_rl (action, rl_key, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Unicite des avis : 1 avis par (cible, auteur) -------------------
-- 2a. Dedoublonnage : garder l'avis le plus recent (id max) par couple.
DELETE r1 FROM reviews r1
JOIN reviews r2
  ON r1.user_id    = r2.user_id
 AND r1.author_uid = r2.author_uid
 AND r1.author_uid IS NOT NULL
 AND r1.id < r2.id;

-- 2b. Index UNIQUE. Si vous le relancez et qu'il existe deja, MariaDB
--     renverra "Duplicate key name 'uniq_review_author'" : ignorez cette
--     erreur (l'index est deja en place).
ALTER TABLE reviews
  ADD UNIQUE KEY uniq_review_author (user_id, author_uid);
