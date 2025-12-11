<?php
// Fichier: /api/get_favorites.php
// Récupère les détails des camps favoris d'un utilisateur.

session_start();
header('Content-Type: application/json');
require_once 'config.php';

// SÉCURITÉ : L'utilisateur doit être connecté.
if (!isset($_SESSION['user']['id'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Accès non autorisé.']);
    exit;
}

try {
    $favoriteIds = $_SESSION['user']['favorites'] ?? [];

    if (empty($favoriteIds)) {
        echo json_encode([]); // Renvoie un tableau vide si pas de favoris
        exit;
    }

    // Construire une formule Airtable pour récupérer plusieurs enregistrements par leur ID
    $formulaParts = [];
    foreach ($favoriteIds as $id) {
        $formulaParts[] = "RECORD_ID() = '" . addslashes($id) . "'";
    }
    $formula = 'OR(' . implode(', ', $formulaParts) . ')';

    $url = AIRTABLE_API_URL . AIRTABLE_BASE_ID . '/Camps?filterByFormula=' . urlencode($formula);
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . AIRTABLE_API_KEY]);
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code >= 300) { throw new Exception("Erreur API lors de la récupération des favoris."); }
    
    $result = json_decode($response, true);
    
    $camps = [];
    if (isset($result['records'])) {
        foreach ($result['records'] as $record) {
            $camps[] = [
                'id' => $record['id'],
                'nom' => $record['fields']['nom'] ?? 'N/A',
                'ville' => $record['fields']['Ville ou se déroule le camp'] ?? 'N/A',
                'prix' => $record['fields']['Prix conseillé'] ?? 0,
                'age_min' => $record['fields']['Age min'] ?? 0,
                'age_max' => $record['fields']['Age max'] ?? 0,
                'date_debut' => $record['fields']['Date début du camp'] ?? '',
                'image_url' => $record['fields']['illustration'][0]['url'] ?? 'https://placehold.co/600x400/e2e8f0/cbd5e0?text=Image'
            ];
        }
    }
    
    echo json_encode($camps);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
