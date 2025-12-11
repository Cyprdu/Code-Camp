<?php
// Fichier: /api/get_camps.php
// Version finale qui n'affiche que les camps validés et futurs.

header('Content-Type: application/json');
require_once 'config.php';

try {
    $formulaParts = [];
    
    // CONDITION DE BASE : Le camp doit être validé ET sa date de début doit être future.
    $today = date('Y-m-d');
    $formulaParts[] = "IS_AFTER({Date début du camp}, '{$today}')";
    $formulaParts[] = "{Validé} = 1";

    // Filtre par nom
    if (!empty($_GET['name'])) {
        $formulaParts[] = "SEARCH(LOWER('" . addslashes($_GET['name']) . "'), LOWER({nom}))";
    }

    // Filtre par département
    if (!empty($_GET['department'])) {
        $departmentCode = addslashes($_GET['department']);
        $formulaParts[] = "LEFT(TRIM({Code Postale} & ''), 2) = '{$departmentCode}'";
    }

    // Filtre par âge
    if (!empty($_GET['age'])) {
        $age = intval($_GET['age']);
        $formulaParts[] = "AND({Age min} <= {$age}, {Age max} >= {$age})";
    }
    
    // Construit la formule finale avec des AND
    $formula = 'AND(' . implode(', ', $formulaParts) . ')';
    $url = AIRTABLE_API_URL . AIRTABLE_BASE_ID . '/Camps?filterByFormula=' . urlencode($formula);

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . AIRTABLE_API_KEY]);
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code >= 300) {
        $errorDetails = json_decode($response, true);
        throw new Exception($errorDetails['error']['message'] ?? 'Erreur API.');
    }
    
    $result = json_decode($response, true);
    $final_records = $result['records'] ?? [];

    $camps_response = [];
    foreach ($final_records as $record) {
        $camps_response[] = [
            'id' => $record['id'],
            'nom' => $record['fields']['nom'] ?? 'N/A',
            'ville' => $record['fields']['Ville ou se déroule le camp'] ?? 'N/A',
            'prix' => $record['fields']['Prix conseillé'] ?? 0,
            'age_min' => $record['fields']['Age min'] ?? 0,
            'age_max' => $record['fields']['Age max'] ?? 0,
            'date_debut' => $record['fields']['Date début du camp'] ?? '',
            'image_url' => $record['fields']['illustration'][0]['url'] ?? 'https://placehold.co/600x400'
        ];
    }
    
    echo json_encode($camps_response);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
