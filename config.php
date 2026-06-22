<?php
// =====================================================================
//  config.php — connexion a la base + outils communs
//  Tous les endpoints font : require_once 'config.php';
// =====================================================================

// ---- 1. CORS : autorise ton React (Vite) a appeler cette API ----
// En developpement, '*' suffit. En production on mettra ton vrai domaine.
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json; charset=utf-8');

// Avant un POST, le navigateur envoie une requete "OPTIONS" (preflight).
// On repond OK immediatement, sans executer le reste.
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// ---- 2. Connexion MySQL (PDO) ----
$DB_HOST = '127.0.0.1';
$DB_NAME = 'acteurs_plus';
$DB_USER = 'root';
$DB_PASS = '';   // XAMPP : mot de passe root VIDE par defaut. Mets le tien si tu en as un.

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
