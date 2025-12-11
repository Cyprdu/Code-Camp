<?php
// Fichier: /api/get_animator_applications.php (Corrigé)
session_start();
header('Content-Type: application/json');
require_once 'config.php';

if (!isset($_SESSION['user']['id']) || !$_SESSION['user']['is_directeur']) {
    http_response_code(403); 
    echo json_encode(['error' => 'Accès non autorisé']);
    exit;
}

try {
    $directorId = $_SESSION['user']['id'];
    $tableName = 'Candidatures';
    
    // CORRECTION : La formule doit utiliser le champ Lookup correct.
    $formula = "{ID Directeur (from Camp)} = '{$directorId}'";

    $result = callAirtable('GET', $tableName, ['filterByFormula' => $formula]);
    if (isset($result['error'])) {
        $errorMessage = $result['response']['error']['message'] ?? "Erreur de récupération des candidatures.";
        throw new Exception($errorMessage);
    }

    $applications = [];
    foreach ($result['records'] as $record) {
        $fields = $record['fields'];
        $applications[] = [
            'id' => $record['id'],
            'candidat_nom' => ($fields['Prénom (from Candidat)'][0] ?? '') . ' ' . ($fields['Nom (from Candidat)'][0] ?? ''),
            'candidat_mail' => $fields['Mail (from Candidat)'][0] ?? 'N/A',
            'candidat_tel' => $fields['Téléphone (from Candidat)'][0] ?? 'N/A',
            'camp_nom' => $fields['Nom du camp (from Camp)'][0] ?? 'N/A',
            'motivation' => $fields['Motivation'] ?? '',
            'statut' => $fields['Statut'] ?? 'N/A',
            'date' => $record['createdTime']
        ];
    }
    
    echo json_encode($applications);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>