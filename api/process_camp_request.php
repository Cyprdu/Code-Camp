<?php
// Fichier: /api/process_camp_request.php
session_start();
header('Content-Type: application/json');
require_once 'config.php';

if (!isset($_SESSION['user']) || !($_SESSION['user']['is_admin'] ?? false)) {
    http_response_code(403); exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$campId = $input['campId'] ?? null;
$action = $input['action'] ?? null;

if (!$campId || !$action || !in_array($action, ['approve', 'deny'])) {
    http_response_code(400); 
    echo json_encode(['error' => 'Action ou ID de camp invalide.']);
    exit;
}

try {
    if ($action === 'approve') {
        // Approuver : on coche "Validé" et on décoche "En attente"
        $updateData = [
            'fields' => [
                'Validé' => true,
                'En attente' => false,
                'Refusé' => false
            ]
        ];
        callAirtable('PATCH', 'Camps', $updateData, $campId);

        // On lie aussi le camp au profil du directeur pour qu'il puisse le gérer
        $campRecord = callAirtable('GET', 'Camps', null, $campId);
        if (!isset($campRecord['error']) && isset($campRecord['fields']['Organisateur'][0])) {
            $organizerId = $campRecord['fields']['Organisateur'][0];
            $userRecord = callAirtable('GET', 'User', null, $organizerId);
            $existingCamps = $userRecord['fields']['proprio'] ?? [];
            $existingCamps[] = $campId;
            callAirtable('PATCH', 'User', ['fields' => ['proprio' => array_values(array_unique($existingCamps))]], $organizerId);
        }

    } elseif ($action === 'deny') {
        // Refuser : on coche "Refusé" et on décoche "En attente"
        $updateData = [
            'fields' => [
                'Refusé' => true,
                'En attente' => false,
                'Validé' => false
            ]
        ];
        callAirtable('PATCH', 'Camps', $updateData, $campId);
    }
    
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
