<?php
// Fichier: /api/process_request.php
// Traite une demande d'accès (accepter ou refuser).

session_start();
header('Content-Type: application/json');
require_once 'config.php';

// SÉCURITÉ : Seul un admin peut faire cette action.
if (!isset($_SESSION['user']) || !($_SESSION['user']['is_admin'] ?? false)) {
    http_response_code(403);
    echo json_encode(['error' => 'Accès non autorisé.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Méthode non autorisée.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$userId = $input['userId'] ?? null;
$action = $input['action'] ?? null;

if (!$userId || !$action || !in_array($action, ['accept', 'refuse'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Données invalides.']);
    exit;
}

try {
    $updateData = [];
    if ($action === 'accept') {
        $updateData = [
            'fields' => [
                'Directeur' => true,
                'Demande en cours...' => false,
                'Refusé' => false,
            ]
        ];
    } else { // 'refuse'
        $updateData = [
            'fields' => [
                'Directeur' => false,
                'Demande en cours...' => false,
                'Refusé' => true,
            ]
        ];
    }
    
    $result = callAirtable('PATCH', 'User', $updateData, $userId);

    if (isset($result['error'])) {
        throw new Exception($result['response']['error']['message'] ?? "Erreur lors de la mise à jour de l'utilisateur.");
    }
    
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
