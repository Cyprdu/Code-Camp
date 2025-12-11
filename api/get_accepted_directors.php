<?php
// Fichier: /api/get_accepted_directors.php
// Récupère la liste de tous les utilisateurs ayant le statut "Directeur".

session_start();
header('Content-Type: application/json');
require_once 'config.php';

// SÉCURITÉ : Seul un admin peut voir cette liste.
if (!isset($_SESSION['user']) || !($_SESSION['user']['is_admin'] ?? false)) {
    http_response_code(403);
    echo json_encode(['error' => 'Accès non autorisé.']);
    exit;
}

try {
    $tableName = 'User';
    // On filtre pour ne prendre que les enregistrements où "Directeur" est coché.
    $formula = "{Directeur} = 1";
    
    $url = AIRTABLE_API_URL . AIRTABLE_BASE_ID . '/' . rawurlencode($tableName) . '?filterByFormula=' . urlencode($formula);
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . AIRTABLE_API_KEY]);
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code >= 300) { throw new Exception("Erreur API Airtable."); }

    $result = json_decode($response, true);
    
    $directors = [];
    if (isset($result['records'])) {
        foreach ($result['records'] as $record) {
            $directors[] = [
                'id' => $record['id'],
                'nom' => $record['fields']['nom'] ?? '',
                'prenom' => $record['fields']['prenom'] ?? '',
                'mail' => $record['fields']['mail'] ?? ''
            ];
        }
    }
    
    echo json_encode($directors);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
