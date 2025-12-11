<?php
// Fichier: /api/get_eligible_camps_for_animator.php (Version finale avec filtrage PHP robuste)
session_start();
header('Content-Type: application/json');
require_once 'config.php';

// Sécurité : Vérifie si l'utilisateur est un animateur connecté
if (!isset($_SESSION['user']['id']) || !($_SESSION['user']['is_animateur'] ?? false)) {
    echo json_encode([]);
    exit;
}

try {
    // 1. Récupérer les données de l'animateur connecté
    $userId = $_SESSION['user']['id'];
    $userRecord = callAirtable('GET', 'User', null, $userId);
    if (isset($userRecord['error'])) {
        throw new Exception("Utilisateur non trouvé.");
    }
    $user = $userRecord['fields'];
    $user['id'] = $userRecord['id'];

    // 2. Récupérer TOUS les camps sans filtre Airtable
    $campsResult = callAirtable('GET', 'Camps');
    $allCamps = $campsResult['records'] ?? [];
    
    // 3. Récupérer les données des autres animateurs pour les quotas
    $allAnimatorsResult = callAirtable('GET', 'User', ['filterByFormula' => "{Annimateur} = 1", 'fields' => ['Sexe']]);
    $allAnimatorsData = [];
    if (!isset($allAnimatorsResult['error'])) {
        foreach($allAnimatorsResult['records'] as $anim) {
            $allAnimatorsData[$anim['id']] = $anim['fields'];
        }
    }

    // --- DÉBUT DU FILTRAGE EXCLUSIVEMENT EN PHP ---
    $eligibleCamps = [];
    $today = new DateTime();
    $today->setTime(0, 0, 0);
    $searchTerm = isset($_GET['name']) ? strtolower(trim($_GET['name'])) : '';

    foreach ($allCamps as $campRecord) {
        $camp = $campRecord['fields'];
        $camp['id'] = $campRecord['id'];

        // FILTRE 1 : "Gestion animateur" doit être cochée
        if (!($camp['Gestion animateur'] ?? false)) {
            continue; // Si la case n'est pas cochée, on ignore ce camp.
        }

        // FILTRE 2 : Recherche par nom
        if (!empty($searchTerm) && stripos($camp['nom'] ?? '', $searchTerm) === false) {
            continue;
        }

        // FILTRE 3 : Date du camp (doit être dans le futur)
        if (empty($camp['Date début du camp'])) continue;
        $campStartDate = new DateTime($camp['Date début du camp']);
        if ($campStartDate < $today) {
            continue;
        }

        // FILTRE 4 : Âge de l'animateur
        if ($camp['anim +18'] ?? false) {
            if (empty($user['Naissance'])) continue;
            $userBirthdate = new DateTime($user['Naissance']);
            $ageAtCampStart = $userBirthdate->diff($campStartDate)->y;
            if ($ageAtCampStart < 18) {
                continue; 
            }
        }
        
        // FILTRE 5 : BAFA
        if (($camp['BAFA ANIM'] ?? false) && !($user['BAFA'] ?? false)) {
            continue;
        }

        // FILTRE 6 : Quotas
        $linkedAnimatorsIds = $camp['Annimateur'] ?? [];
        $totalQuota = $camp['quota max anim'] ?? 999;
        if (count($linkedAnimatorsIds) >= $totalQuota) continue;

        $maleAnimCount = 0;
        $femaleAnimCount = 0;
        foreach($linkedAnimatorsIds as $animId) {
            if(isset($allAnimatorsData[$animId])) {
                if(($allAnimatorsData[$animId]['Sexe'] ?? '') === 'Homme') $maleAnimCount++;
                if(($allAnimatorsData[$animId]['Sexe'] ?? '') === 'Femme') $femaleAnimCount++;
            }
        }
        $maleQuota = $camp['quota max anim GARCON'] ?? 0;
        $femaleQuota = $camp['quota max anim FILLE'] ?? 0;

        if (($user['Sexe'] ?? '') === 'Homme' && $maleQuota > 0 && $maleAnimCount >= $maleQuota) continue;
        if (($user['Sexe'] ?? '') === 'Femme' && $femaleQuota > 0 && $femaleAnimCount >= $femaleQuota) continue;

        // Si le camp a passé tous les filtres, on l'ajoute
        $eligibleCamps[] = [
            'id' => $camp['id'], 'nom' => $camp['nom'] ?? 'N/A', 'ville' => $camp['Ville ou se déroule le camp'] ?? 'N/A',
            'prix' => $camp['prix anim'] ?? 0, 'age_min' => $camp['Age min'] ?? 0, 'age_max' => $camp['Age max'] ?? 0,
            'date_debut' => $camp['Date début du camp'] ?? '', 'image_url' => $camp['illustration'][0]['url'] ?? 'https://placehold.co/600x400'
        ];
    }
    
    echo json_encode($eligibleCamps);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>