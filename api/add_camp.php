<?php
session_start();
header('Content-Type: application/json');
require_once 'config.php';

if (!isset($_SESSION['user']['id']) || !$_SESSION['user']['is_directeur']) {
    http_response_code(403);
    echo json_encode(['error' => 'Accès non autorisé.']);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true);

    $fields = [
        'nom' => $input['nom'],
        'Déscription' => $input['description'],
        'Ville ou se déroule le camp' => $input['ville'],
        'Code Postale' => $input['code_postal'],
        'Adresse exacte du camp' => $input['adresse'],
        'Prix conseillé' => (int)$input['prix'],
        'Age min' => (int)$input['age_min'],
        'Age max' => (int)$input['age_max'],
        'Date début du camp' => $input['date_debut'],
        'Date fin du camp' => $input['date_fin'],
        'illustration' => [['url' => $input['image_url']]],
        'En attente' => true,
        'Validé' => false,
        'Refusé' => false,
        'Vues' => 0,
        'Organisateur' => [$_SESSION['user']['id']]
    ];

    // --- AJOUT DES NOUVEAUX CHAMPS ---

    // Inscription en ligne
    if ($input['inscription_en_ligne']) {
        $fields['Inscription en ligne'] = true;
        $fields['Inscription hors ligne'] = false;
        if (!empty($input['date_limite_inscription'])) {
            $fields["Date limite d'inscription"] = $input['date_limite_inscription'];
        }
        $fields['quota'] = (int)($input['quota_max'] ?? 0);
        $fields['Remise si plusieurs enfants'] = (int)($input['remise'] ?? 0);
        
        // Nouveaux champs
        $fields['Montant libre'] = $input['montant_libre'] ?? false;
        if(!empty($input['quota_fille'])) $fields['MAX FILLE'] = (int)$input['quota_fille'];
        if(!empty($input['quota_garcon'])) $fields['MAX GARCON'] = (int)$input['quota_garcon'];
    } else {
        $fields['Inscription en ligne'] = false;
        $fields['Inscription hors ligne'] = true;
        if (!empty($input['dossier_pdf'])) $fields["dossier d'inscription"] = $input['dossier_pdf'];
        if (!empty($input['adresse_retour'])) $fields['adresse retour dossier'] = $input['adresse_retour'];
    }

    // Tarifs
    if (!empty($input['tarifs'])) {
        $fields['Lien Tarifs'] = $input['tarifs']; // On attend un tableau d'IDs de tarifs
    }

    // Gestion animateur
    $fields['Gestion animateur'] = $input['gestion_animateur'] ?? false;
    if ($fields['Gestion animateur']) {
        if(!empty($input['quota_max_anim'])) $fields['quota max anim'] = (int)$input['quota_max_anim'];
        if(!empty($input['quota_max_anim_fille'])) $fields['quota max anim FILLE'] = (int)$input['quota_max_anim_fille'];
        if(!empty($input['quota_max_anim_garcon'])) $fields['quota max anim GARCON'] = (int)$input['quota_max_anim_garcon'];
        $fields['anim +18'] = $input['anim_majeur'] ?? false;
        if(!empty($input['quota_fille_mineur'])) $fields['quota max anim FILLE -18'] = (int)$input['quota_fille_mineur'];
        if(!empty($input['quota_fille_majeur'])) $fields['quota max anim FILLE +18'] = (int)$input['quota_fille_majeur'];
        if(!empty($input['quota_garcon_mineur'])) $fields['quota max anim GARCON -18'] = (int)$input['quota_garcon_mineur'];
        if(!empty($input['quota_garcon_majeur'])) $fields['quota max anim GARCON +18'] = (int)$input['quota_garcon_majeur'];
        $fields['BAFA ANIM'] = $input['bafa_obligatoire'] ?? false;
        $fields['paiement anim'] = $input['paiement_anim'] ?? false;
        if(!empty($input['prix_anim'])) $fields['prix anim'] = (float)$input['prix_anim'];
        $fields['montant libre anim'] = $input['montant_libre_anim'] ?? false;
        $fields['rémunération anim'] = $input['remuneration_anim'] ?? false;
    }

    $campData = ['fields' => $fields];
    $result = callAirtable('POST', 'Camps', $campData);

    if (isset($result['error'])) {
        throw new Exception($result['response']['error']['message'] ?? "Erreur inconnue.");
    }
    
    echo json_encode(['success' => 'Votre camp a été soumis pour approbation !']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>