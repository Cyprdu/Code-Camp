<?php
// Fichier: /api/get_children.php
// VERSION CORRIGÉE ET SÉCURISÉE AVEC LE SEXE

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

    // On utilise la formule fiable basée sur l'ID unique du parent.
    // C'est la garantie que seuls les enfants de l'utilisateur connecté sont retournés.
    $formula = "{Parent_ID_Unique} = '{$userId}'";

    // On s'assure de demander tous les champs nécessaires, y compris le 'Sexe'.
    $fields = ['Prénom', 'Date de naissance', 'Inscrit au camp', 'Sexe'];
    
    // Construction manuelle et directe de l'URL pour être certain que le filtre est appliqué
    $url = AIRTABLE_API_URL . AIRTABLE_BASE_ID . '/' . rawurlencode('Enfants') .
           '?filterByFormula=' . urlencode($formula);
    foreach ($fields as $field) {
        $url .= '&fields%5B%5D=' . rawurlencode($field);
    }

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . AIRTABLE_API_KEY]);
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code >= 300) {
        throw new Exception("Erreur de communication avec Airtable pour récupérer les enfants.");
    }

    $childrenResult = json_decode($response, true);

    $childrenData = [];
    if (isset($childrenResult['records'])) {
        foreach ($childrenResult['records'] as $record) {
            $dob = $record['fields']['Date de naissance'] ?? null;
            $age = $dob ? (new DateTime($dob))->diff(new DateTime('today'))->y : null;
            
            $childrenData[] = [
                'id' => $record['id'],
                'prenom' => $record['fields']['Prénom'] ?? 'Enfant',
                'age' => $age,
                'sexe' => $record['fields']['Sexe'] ?? null, // On retourne le sexe
                'registeredCamps' => $record['fields']['Inscrit au camp'] ?? []
            ];
        }
    }

    echo json_encode($childrenData);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>