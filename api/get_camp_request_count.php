<?php
// Fichier: /api/get_camp_request_count.php
// Version corrigée pour compter uniquement les camps "En attente".

session_start();
header('Content-Type: application/json');
require_once 'config.php';

// Sécurité : Seul un administrateur peut récupérer ce compteur.
if (!isset($_SESSION['user']) || !($_SESSION['user']['is_admin'] ?? false)) {
    echo json_encode(['count' => 0]);
    exit;
}

try {
    $tableName = 'Camps';
    
    // CORRIGÉ : La formule filtre maintenant les enregistrements pour ne garder que
    // ceux où la case "En attente" est cochée.
    $formula = "{En attente} = 1";
    
    // Pour optimiser la requête, on ne demande qu'un seul champ, car seul le nombre nous intéresse.
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
    
    // On compte le nombre d'enregistrements retournés qui correspondent au filtre.
    $count = isset($result['records']) ? count($result['records']) : 0;
    
    echo json_encode(['count' => $count]);

} catch (Exception $e) {
    // En cas d'erreur, on renvoie simplement 0 pour ne pas casser l'interface.
    echo json_encode(['count' => 0]);
}
?>
