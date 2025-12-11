<?php
// Fichier: /api/get_my_reservations.php
// Version finale sécurisée, basée sur la logique de get_children.php qui fonctionne.

session_start();
header('Content-Type: application/json');
require_once 'config.php';

// --- SÉCURITÉ ---
// On s'assure qu'un utilisateur est bien connecté.
if (!isset($_SESSION['user']['id'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Accès non autorisé.']);
    exit;
}

try {
    // === PARTIE 1 : RÉCUPÉRER LES ENFANTS DE L'UTILISATEUR (La méthode qui fonctionne) ===
    $userId = $_SESSION['user']['id'];
    $formula_children = "{Parent_ID_Unique} = '{$userId}'";
    $fields_children = ['Prénom', 'Inscrit au camp'];

    // On construit l'URL pour la requête des enfants
    $url_children = AIRTABLE_API_URL . AIRTABLE_BASE_ID . '/' . rawurlencode('Enfants') . '?filterByFormula=' . urlencode($formula_children);
    foreach ($fields_children as $field) {
        $url_children .= '&fields%5B%5D=' . rawurlencode($field);
    }

    // On exécute la requête pour obtenir la liste des enfants de l'utilisateur
    $ch_children = curl_init($url_children);
    curl_setopt($ch_children, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch_children, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . AIRTABLE_API_KEY]);
    $response_children = curl_exec($ch_children);
    $http_code_children = curl_getinfo($ch_children, CURLINFO_HTTP_CODE);
    curl_close($ch_children);

    if ($http_code_children >= 300) {
        throw new Exception("Erreur Airtable en récupérant la liste des enfants.");
    }

    $childrenResult = json_decode($response_children, true);
    $childrenRecords = $childrenResult['records'] ?? [];

    // Si l'utilisateur n'a pas d'enfant, on s'arrête là.
    if (empty($childrenRecords)) {
        echo json_encode([]);
        exit;
    }

    // === PARTIE 2 : COLLECTER LES IDs DE CAMPS ET RÉCUPÉRER LEURS DÉTAILS ===
    $campIdsToFetch = [];
    foreach ($childrenRecords as $child) {
        if (!empty($child['fields']['Inscrit au camp'])) {
            foreach ($child['fields']['Inscrit au camp'] as $campId) {
                $campIdsToFetch[$campId] = true;
            }
        }
    }
    $uniqueCampIds = array_keys($campIdsToFetch);

    // Si les enfants ne sont inscrits à aucun camp, on s'arrête.
    if (empty($uniqueCampIds)) {
        echo json_encode([]);
        exit;
    }

    // On prépare la requête pour récupérer les détails de tous les camps nécessaires en un seul appel.
    $formula_camps_parts = [];
    foreach ($uniqueCampIds as $id) {
        $formula_camps_parts[] = "RECORD_ID() = '{$id}'";
    }
    $formula_camps = 'OR(' . implode(', ', $formula_camps_parts) . ')';

    $url_camps = AIRTABLE_API_URL . AIRTABLE_BASE_ID . '/' . rawurlencode('Camps') . '?filterByFormula=' . urlencode($formula_camps);

    $ch_camps = curl_init($url_camps);
    curl_setopt($ch_camps, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch_camps, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . AIRTABLE_API_KEY]);
    $response_camps = curl_exec($ch_camps);
    $http_code_camps = curl_getinfo($ch_camps, CURLINFO_HTTP_CODE);
    curl_close($ch_camps);

    if ($http_code_camps >= 300) {
        throw new Exception("Erreur Airtable en récupérant les détails des camps.");
    }
    
    $campsResult = json_decode($response_camps, true);
    $campsDetails = [];
    foreach ($campsResult['records'] as $camp) {
        $campsDetails[$camp['id']] = [
            'camp_nom' => $camp['fields']['nom'] ?? 'N/A',
            'camp_image_url' => $camp['fields']['illustration'][0]['url'] ?? 'https://placehold.co/600x400',
            'date_debut' => $camp['fields']['Date début du camp'] ?? null
        ];
    }
    
    // === PARTIE 3 : ASSEMBLER LA LISTE FINALE DES RÉSERVATIONS ===
    $finalReservations = [];
    foreach ($childrenRecords as $child) {
        $childName = $child['fields']['Prénom'] ?? 'Enfant';
        $registeredCamps = $child['fields']['Inscrit au camp'] ?? [];
        foreach ($registeredCamps as $campId) {
            if (isset($campsDetails[$campId])) {
                $finalReservations[] = [
                    'camp_id' => $campId, 'enfant_id' => $child['id'], 'enfant_nom' => $childName,
                    'camp_nom' => $campsDetails[$campId]['camp_nom'], 'camp_image_url' => $campsDetails[$campId]['camp_image_url'],
                    'date_debut' => $campsDetails[$campId]['date_debut']
                ];
            }
        }
    }
    
    usort($finalReservations, function($a, $b) {
        $timeA = isset($a['date_debut']) ? strtotime($a['date_debut']) : 0;
        $timeB = isset($b['date_debut']) ? strtotime($b['date_debut']) : 0;
        return $timeA - $timeB;
    });

    echo json_encode($finalReservations);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>