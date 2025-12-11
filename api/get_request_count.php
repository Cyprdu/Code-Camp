<?php
// Fichier: /api/get_request_count.php
// Récupère le nombre de demandes d'accès directeur en attente.

session_start();
header('Content-Type: application/json');
require_once 'config.php';

// Sécurité : Seul un administrateur peut récupérer ce compteur.
if (!isset($_SESSION['user']) || !($_SESSION['user']['is_admin'] ?? false)) {
    // Si l'utilisateur n'est pas un admin, on renvoie 0 pour ne pas afficher de notification.
    echo json_encode(['count' => 0]);
    exit;
}

try {
    $tableName = 'User';
    // On filtre les enregistrements où la case "Demande en cours..." est cochée.
    $formula = "{Demande en cours...} = 1";
    
    // Pour optimiser, on ne demande qu'un seul champ, car on n'a besoin que du nombre d'enregistrements.
    $fields = ['nom']; 
    $url = AIRTABLE_API_URL . AIRTABLE_BASE_ID . '/' . rawurlencode($tableName) . '?filterByFormula=' . urlencode($formula) . '&fields%5B%5D=' . rawurlencode($fields[0]);

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . AIRTABLE_API_KEY]);
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code >= 300) { throw new Exception("API Error"); }
    
    $result = json_decode($response, true);
    
    // On compte le nombre d'enregistrements retournés par la requête.
    $count = isset($result['records']) ? count($result['records']) : 0;
    
    echo json_encode(['count' => $count]);

} catch (Exception $e) {
    // En cas d'erreur (ex: API Airtable inaccessible), on renvoie 0.
    echo json_encode(['count' => 0]);
}
?>
