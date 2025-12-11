<?php
// Fichier: /api/get_my_camps.php
// Version qui utilise la nouvelle colonne "proprio" pour plus d'efficacité.

session_start();
header('Content-Type: application/json');
require_once 'config.php';

// Sécurité
if (!isset($_SESSION['user']['id']) || !$_SESSION['user']['is_directeur']) {
    http_response_code(403);
    echo json_encode(['error' => 'Accès non autorisé.']);
    exit;
}

try {
    $userId = $_SESSION['user']['id'];
    
    // 1. Récupérer l'enregistrement de l'utilisateur
    $userRecord = callAirtable('GET', 'User', null, $userId);

    // Si l'utilisateur n'a pas de camps ou que la colonne "proprio" est vide
    if (!isset($userRecord['fields']['proprio'])) {
        echo json_encode([]); // On retourne un tableau vide
        exit;
    }

    $myCampsIds = $userRecord['fields']['proprio'];

    if (empty($myCampsIds)) {
        echo json_encode([]);
        exit;
    }

    // 2. Construire une formule pour récupérer tous les camps liés en une seule requête
    $formulaParts = [];
    foreach ($myCampsIds as $campId) {
        $formulaParts[] = "RECORD_ID() = '" . $campId . "'";
    }
    $formula = 'OR(' . implode(', ', $formulaParts) . ')';

    // 3. Récupérer les détails des camps depuis la table 'Camps'
    $url = AIRTABLE_API_URL . AIRTABLE_BASE_ID . '/Camps?filterByFormula=' . urlencode($formula);
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . AIRTABLE_API_KEY]);
    $response = curl_exec($ch);
    curl_close($ch);

    $result = json_decode($response, true);
    
    $camps = [];
    if (isset($result['records'])) {
        foreach ($result['records'] as $record) {
            $camps[] = [
                'id' => $record['id'],
                'nom' => $record['fields']['nom'] ?? 'Camp sans nom'
            ];
        }
    }
    
    echo json_encode($camps);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
