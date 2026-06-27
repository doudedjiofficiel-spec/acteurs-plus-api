<?php
// =====================================================================
//  validators.php — validations reutilisables des entrees client.
//  (Pas un endpoint : inclus par require_once dans les endpoints.)
//  But : garde-fous EN AMONT (anti-DoS / bloat / contenu malveillant),
//  sans rien changer a la logique metier.
// =====================================================================

// Taille max d'une valeur "image" (longueur de la CHAINE recue).
//  ~3 Mo de chaine : large pour une image compressee cote client (400-800px
//  -> quelques dizaines de Ko), mais bloque un dataURL abusif (DB bloat /
//  depassement de max_allowed_packet).
if (!defined('MAX_IMAGE_CHARS'))   { define('MAX_IMAGE_CHARS', 3 * 1024 * 1024); }
// Nombre max d'elements acceptes en un seul envoi de galerie (anti POST geant).
if (!defined('MAX_GALLERY_ITEMS')) { define('MAX_GALLERY_ITEMS', 20); }
// Longueur max d'un message texte (anti-spam / bloat ; large pour un vrai msg).
if (!defined('MAX_MESSAGE_CHARS')) { define('MAX_MESSAGE_CHARS', 5000); }

// ---- Bornes de longueur des champs texte (garde-fous LARGES, calibres sur les
//      colonnes reelles : usage normal tres en-dessous, on bloque l'abus/bloat) ----
if (!defined('MAX_NAME'))    { define('MAX_NAME', 120); }    // users.name varchar(150)
if (!defined('MAX_SHORT'))   { define('MAX_SHORT', 255); }   // champs courts (ville, role...)
if (!defined('MAX_URL'))     { define('MAX_URL', 500); }     // showreel_link / cv_url varchar(500)
if (!defined('MAX_BIO'))     { define('MAX_BIO', 3000); }    // about (colonne TEXT)
if (!defined('MAX_TITLE'))   { define('MAX_TITLE', 200); }   // castings/missions.title varchar(200)
if (!defined('MAX_COMPANY')) { define('MAX_COMPANY', 150); } // company varchar(150)
if (!defined('MAX_DESC'))    { define('MAX_DESC', 5000); }   // description (colonne TEXT)

// True si $value est une chaine plus longue que $max caracteres (mb_strlen).
// (Les non-chaines renvoient false : la validation de type se fait ailleurs.)
function text_too_long($value, $max) {
    return is_string($value) && mb_strlen($value) > $max;
}

// Valide une valeur "image" : photo de profil/couverture, media de galerie,
// visuel d'annonce. Accepte :
//   - chaine VIDE          -> pas d'image (OK, l'appelant stocke '' / null) ;
//   - URL http(s)          -> lien colle (image OU video externe) ;
//   - dataURL image        -> jpeg/jpg/png/webp/gif uniquement.
// Refuse : tout autre data: (text/html, image/svg+xml, video...) et toute
// valeur depassant MAX_IMAGE_CHARS.
// Retour : null si OK, sinon un message d'erreur (l'appelant repond 422).
function image_value_error($value) {
    if ($value === null) { return null; }
    if (!is_string($value)) { return "Format d'image invalide"; }

    $v = trim($value);
    if ($v === '') { return null; } // pas d'image -> OK

    if (strlen($v) > MAX_IMAGE_CHARS) {
        $mo = (int) (MAX_IMAGE_CHARS / (1024 * 1024));
        return "Image trop volumineuse (max {$mo} Mo). Compressez-la ou collez un lien.";
    }

    // Lien externe http(s) : OK (image ou video collee).
    if (preg_match('#^https?://#i', $v)) { return null; }

    // dataURL : UNIQUEMENT image bitmap sure (pas de svg/text/html/video).
    if (preg_match('#^data:image/(jpeg|jpg|png|webp|gif);base64,#i', $v)) { return null; }

    return "Image invalide (formats acceptes : lien http(s) ou image jpeg/png/webp/gif).";
}
