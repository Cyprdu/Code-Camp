<?php
// Fichier: /api/get_refused_camps.php
// Version fiabilisée pour récupérer uniquement les camps refusés.

// Active l'affichage des erreurs pour le débogage.
error_reporting(E_ALL);
ini_set('display_errors', 1);

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
    // Assurez-vous que le nom du champ "Refusé" est exact dans votre base Airtable.
    $formula = "{Refusé} = 1";
    
    // On construit l'URL avec la formule de filtre.
    $url = AIRTABLE_API_URL . AIRTABLE_BASE_ID . '/' . rawurlencode($tableName) . '?filterByFormula=' . urlencode($formula);
    
    // Appel direct à l'API pour un meilleur contrôle.
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . AIRTABLE_API_KEY]);
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code >= 300) {
        $errorDetails = json_decode($response, true);
        throw new Exception($errorDetails['error']['message'] ?? "Erreur lors de la récupération des camps refusés.");
    }
    
    $result = json_decode($response, true);
    
    $camps = [];
    if (isset($result['records'])) {
        foreach ($result['records'] as $record) {
            // On s'assure que chaque champ a une valeur par défaut pour éviter les erreurs "undefined".
            $camps[] = [
                'id' => $record['id'],
                'nom' => $record['fields']['nom'] ?? 'Camp sans nom',
                'ville' => $record['fields']['Ville ou se déroule le camp'] ?? 'Ville non précisée'
            ];
        }
    }
    
    echo json_encode($camps);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
