<?php
// Fichier: /api/get_my_reservations2.php
// Version de test dans un nouveau fichier pour éviter le cache.

session_start();
header('Content-Type: application/json');
require_once 'config.php';

if (!isset($_SESSION['user']['id'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Accès non autorisé.']);
    exit;
}

try {
    $userId = $_SESSION['user']['id'];
    
    // On utilise la formule qui a été prouvée correcte par le débogage.
    $formula = "{Parent_ID_Unique} = '{$userId}'";

    $params = [
        'filterByFormula' => $formula,
        'fields' => ['Prénom', 'Inscrit au camp']
    ];
    $childrenResult = callAirtable('GET', 'Enfants', $params);

    if (isset($childrenResult['error'])) {
        throw new Exception("Erreur Airtable.");
    }
    $childrenRecords = $childrenResult['records'] ?? [];

    if (empty($childrenRecords)) {
        echo json_encode([]); 
        exit;
    }

    $campIdsToFetch = [];
    foreach ($childrenRecords as $child) {
        if (!empty($child['fields']['Inscrit au camp'])) {
            foreach ($child['fields']['Inscrit au camp'] as $campId) {
                $campIdsToFetch[$campId] = true;
            }
        }
    }
    $uniqueCampIds = array_keys($campIdsToFetch);

    if (empty($uniqueCampIds)) {
        echo json_encode([]); 
        exit;
    }

    $campFormulaParts = [];
    foreach ($uniqueCampIds as $id) { $campFormulaParts[] = "RECORD_ID() = '{$id}'"; }
    $campFormula = 'OR(' . implode(', ', $campFormulaParts) . ')';
    $campsResult = callAirtable('GET', 'Camps', ['filterByFormula' => $campFormula]);

    $campsDetails = [];
    foreach ($campsResult['records'] as $camp) {
        $campsDetails[$camp['id']] = [
            'camp_nom' => $camp['fields']['nom'] ?? 'N/A',
            'camp_image_url' => $camp['fields']['illustration'][0]['url'] ?? 'https://placehold.co/600x400',
            'date_debut' => $camp['fields']['Date début du camp'] ?? null
        ];
    }
    
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