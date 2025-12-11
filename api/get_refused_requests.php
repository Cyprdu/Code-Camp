<?php
session_start();
header('Content-Type: application/json');
require_once 'config.php';

// Sécurité : Seul un administrateur peut accéder à cette liste.
if (!isset($_SESSION['user']) || !($_SESSION['user']['is_admin'] ?? false)) {
    http_response_code(403);
    echo json_encode(['error' => 'Accès non autorisé.']);
    exit;
}

try {
    $tableName = 'Camps';
    // Formule pour ne récupérer que les camps où la case "Refusé" est cochée.
    $formula = "{Refusé} = 1";
    
    $url = AIRTABLE_API_URL . AIRTABLE_BASE_ID . '/' . rawurlencode($tableName) . '?filterByFormula=' . urlencode($formula);
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . AIRTABLE_API_KEY]);
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code >= 300) {
        throw new Exception("Erreur API lors de la récupération des camps refusés.");
    }
    
    $result = json_decode($response, true);
    
    $camps = [];
    if (isset($result['records'])) {
        foreach ($result['records'] as $record) {
            $camps[] = [
                'id' => $record['id'],
                'nom' => $record['fields']['nom'] ?? 'N/A',
                'ville' => $record['fields']['Ville ou se déroule le camp'] ?? 'N/A',
                'organisateur_nom' => $record['fields']['Nom Organisateur (from Organisateur)'][0] ?? 'Inconnu'
            ];
        }
    }
    
    echo json_encode($camps);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
