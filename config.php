<?php
// =====================================================================
//  config.php — connexion a la base + outils communs
//  Tous les endpoints font : require_once 'config.php';
// =====================================================================

// =====================================================================
//  === A MODIFIER EN PRODUCTION ===
//  Une fois en ligne, ne changez QUE ce bloc (aucune autre ligne) :
//   - $DB_HOST / $DB_NAME / $DB_USER / $DB_PASS : identifiants MySQL de
//     l'hebergeur. Creez un utilisateur DEDIE avec mot de passe ; ne gardez
//     JAMAIS root / mot de passe vide en production.
//   - $ALLOWED_ORIGIN : remplacez '*' par l'URL EXACTE du front
//     (ex. 'https://votre-domaine.com') pour ne plus autoriser toutes origines.
//  Les valeurs par defaut ci-dessous sont celles du LOCAL (XAMPP) :
//  ne pas y toucher tant qu'on developpe en local.
// =====================================================================
$DB_HOST = '127.0.0.1';
$DB_NAME = 'acteurs_plus';
$DB_USER = 'root';
$DB_PASS = '';                 // LOCAL (XAMPP) : root sans mot de passe. PROD : mot de passe d'un user dedie.
$ALLOWED_ORIGIN = '*';         // LOCAL : toutes origines. PROD : 'https://votre-domaine.com'
// =====================================================================

// ---- 1. CORS : autorise le front (Vite) a appeler cette API ----
header('Access-Control-Allow-Origin: ' . $ALLOWED_ORIGIN);
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json; charset=utf-8');

// Avant un POST, le navigateur envoie une requete "OPTIONS" (preflight).
// On repond OK immediatement, sans executer le reste.
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// ---- 2. Connexion MySQL (PDO) — utilise les variables du bloc config ci-dessus ----

try {
    $pdo = new PDO(
        "mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4",
        $DB_USER,
        $DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,  // erreurs = exceptions
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,        // resultats en tableaux clairs
            PDO::ATTR_EMULATE_PREPARES   => false,                  // VRAIES requetes preparees (anti-injection)
        ]
    );
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Connexion a la base impossible']);
    exit;
}

// ---- 3. Outils ----

// Renvoie une reponse JSON puis arrete le script.
function json_response($data, $status = 200) {
    http_response_code($status);
    echo json_encode($data);
    exit;
}

// Lit le corps JSON envoye par React (fetch avec un body JSON).
function get_json_input() {
    $raw  = file_get_contents('php://input');
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}
