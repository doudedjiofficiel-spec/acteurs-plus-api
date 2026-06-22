<?php
// =====================================================================
//  auth_check.php — OUTIL reutilisable (n'est PAS un endpoint appele seul)
//  Un endpoint protege fait :
//      require_once 'config.php';
//      require_once 'auth_check.php';
//      $userId = require_auth($pdo);
// =====================================================================

require_once 'config.php';

// ---- Lit le jeton "Bearer <token>" depuis le header Authorization ----
// Apache peut renommer le header (HTTP_AUTHORIZATION ou la variante REDIRECT_).
// En dernier secours on passe par getallheaders(). Renvoie le token ou null.
function read_bearer_token() {
    $header = '';

    if (!empty($_SERVER['HTTP_AUTHORIZATION'])) {
        $header = $_SERVER['HTTP_AUTHORIZATION'];
    } elseif (!empty($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
        $header = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
    } elseif (function_exists('getallheaders')) {
        foreach (getallheaders() as $key => $value) {
            if (strtolower($key) === 'authorization') {
                $header = $value;
                break;
            }
        }
    }

    $header = trim($header);
    if ($header === '') {
        return null;
    }

    // Format attendu : "Bearer <token>"
    if (preg_match('/^Bearer\s+(.+)$/i', $header, $m)) {
        return trim($m[1]);
    }

    return null;
}

// ---- Verifie le jeton et renvoie le user_id (int) si tout est bon ----
// Sinon, repond une erreur 401 et ARRETE le script (json_response fait exit).
function require_auth($pdo) {
    $token = read_bearer_token();

    if ($token === null || $token === '') {
        json_response(['ok' => false, 'error' => 'Non authentifié'], 401);
    }

    $stmt = $pdo->prepare(
        'SELECT user_id, expires_at FROM sessions WHERE token = :token'
    );
    $stmt->execute([':token' => $token]);
    $session = $stmt->fetch();

    // Token introuvable OU expire -> session refusee.
    if (!$session || strtotime($session['expires_at']) < time()) {
        json_response(['ok' => false, 'error' => 'Session invalide ou expirée'], 401);
    }

    return (int) $session['user_id'];
}
