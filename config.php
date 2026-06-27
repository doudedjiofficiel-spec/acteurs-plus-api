<?php
// =====================================================================
//  config.php — chargeur de configuration (VERSIONNE, SANS secret)
//  Tous les endpoints font : require_once 'config.php';
// ---------------------------------------------------------------------
//  Les VRAIS secrets (DB, origine CORS, environnement) vivent dans
//  config.local.php (NON versionne — voir .gitignore). Modele :
//  config.example.php. A defaut de fichier local, on lit les variables
//  d'environnement, puis des defauts de DEV en dernier recours.
//  -> Aucun identifiant n'est jamais committe sur GitHub.
// =====================================================================

// ---- 0. Charger les secrets locaux (hors depot) s'ils existent ----
$__localConfig = __DIR__ . '/config.local.php';
if (is_file($__localConfig)) {
    require $__localConfig;   // definit $APP_ENV, $DB_*, $ALLOWED_ORIGIN
}

// ---- 0bis. Valeurs effectives : config.local.php > env > defauts DEV ----
// (isset() sur une variable non definie = false, sans warning.)
$APP_ENV        = isset($APP_ENV)        ? $APP_ENV        : (getenv('APP_ENV')        ?: 'dev');
$DB_HOST        = isset($DB_HOST)        ? $DB_HOST        : (getenv('DB_HOST')        ?: '127.0.0.1');
$DB_NAME        = isset($DB_NAME)        ? $DB_NAME        : (getenv('DB_NAME')        ?: 'acteurs_plus');
$DB_USER        = isset($DB_USER)        ? $DB_USER        : (getenv('DB_USER')        ?: 'root');
$DB_PASS        = isset($DB_PASS)        ? $DB_PASS        : (getenv('DB_PASS')        ?: '');
$ALLOWED_ORIGIN = isset($ALLOWED_ORIGIN) ? $ALLOWED_ORIGIN : (getenv('ALLOWED_ORIGIN') ?: '*');

$IS_PROD = ($APP_ENV === 'prod' || $APP_ENV === 'production');

// ---- 1. Affichage des erreurs : MASQUE en prod (jamais de SQL/stack/chemin au client) ----
if ($IS_PROD) {
    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '0');
    error_reporting(0);
} else {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);
}

// ---- 1bis. Filet global : toute erreur NON rattrapee -> JSON generique 500 ----
// (Le flux normal passe par json_response()+exit et n'est pas concerne.)
set_exception_handler(function ($e) use ($IS_PROD) {
    if (!headers_sent()) {
        http_response_code(500);
    }
    error_log('[acteurs-plus] Exception: ' . $e);
    echo json_encode(['ok' => false, 'error' => $IS_PROD
        ? 'Erreur serveur'
        : ('Exception: ' . $e->getMessage())]);
    exit;
});
register_shutdown_function(function () use ($IS_PROD) {
    $err = error_get_last();
    if ($err && in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        if (!headers_sent()) {
            http_response_code(500);
        }
        error_log('[acteurs-plus] Fatal: ' . $err['message'] . ' @ ' . $err['file'] . ':' . $err['line']);
        echo json_encode(['ok' => false, 'error' => $IS_PROD
            ? 'Erreur serveur'
            : ('Fatal: ' . $err['message'])]);
    }
});

// ---- 2. CORS : autorise le front a appeler cette API ----
//  L'origine est pilotee par $ALLOWED_ORIGIN (config.local.php).
//  PROD : domaine exact ; DEV/local : '*'. Un seul reglage a changer.
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

// ---- 3. Connexion MySQL (PDO) — utilise les variables de config ci-dessus ----
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
    error_log('[acteurs-plus] DB: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Connexion a la base impossible']);
    exit;
}

// ---- 4. Outils ----

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
