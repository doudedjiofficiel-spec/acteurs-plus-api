<?php
// =====================================================================
//  uid_helpers.php — OUTIL reutilisable (pas un endpoint appele seul)
//  Pont entre les uid PREFIXES du front (talent_3, tech_2, recruiter_1)
//  et les id BIGINT de la base.
//   - uid_to_id('talent_3')         -> 3   (id numerique apres le dernier '_')
//   - prefix_for_type('talent')     -> 'talent_'
//   - id_to_uid('professional', 2)  -> 'tech_2'
// =====================================================================

// Extrait l'id numerique d'un uid prefixe (ou d'une chaine deja numerique).
function uid_to_id($uid) {
    if (is_int($uid)) return $uid;
    if (!is_string($uid)) return 0;
    $pos = strrpos($uid, '_');
    $num = ($pos === false) ? $uid : substr($uid, $pos + 1);
    return (int) $num;
}

// Prefixe front correspondant au user_type de la base.
function prefix_for_type($type) {
    switch ($type) {
        case 'talent':       return 'talent_';
        case 'professional': return 'tech_';
        case 'recruiter':    return 'recruiter_';
        case 'admin':        return 'admin_';
        default:             return 'talent_';
    }
}

// Reconstruit un uid prefixe (front) a partir d'un user_type + id DB.
function id_to_uid($type, $id) {
    return prefix_for_type($type) . $id;
}
