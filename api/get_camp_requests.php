<?php
// Fichier: /api/get_camp_requests.php
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

    // FORMULE DE FILTRE : C'est la ligne la plus importante.
    // Elle récupère uniquement les enregistrements où la case "En attente" est cochée.
    // Assurez-vous que le nom "En attente" correspond EXACTEMENT à votre nom de colonne dans Airtable.
    $formula = "{En attente} = 1"; 
    
    // On construit l'URL avec la formule de filtre.
    $url = AIRTABLE_API_URL . AIRTABLE_BASE_ID . '/' . rawurlencode($tableName) . '?filterByFormula=' . urlencode($formula);

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . AIRTABLE_API_KEY]);
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code >= 300) {
        $errorDetails = json_decode($response, true);
        throw new Exception($errorDetails['error']['message'] ?? "Erreur lors de la récupération des demandes de camp.");
    }
    
    $result = json_decode($response, true);
    $requests = [];

    if (isset($result['records'])) {
        foreach ($result['records'] as $record) {
            $requests[] = [
                'id' => $record['id'],
                'nom' => $record['fields']['nom'] ?? 'N/A',
                'ville' => $record['fields']['Ville ou se déroule le camp'] ?? 'N/A',
                'code_postal' => $record['fields']['Code Postale'] ?? 'N/A',
                'organisateur_nom' => $record['fields']['Nom Organisateur (from Organisateur)'][0] ?? 'Inconnu'
            ];
        }
    }
    
    echo json_encode($requests);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
