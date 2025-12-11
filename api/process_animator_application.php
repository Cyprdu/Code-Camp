<?php
// Fichier: /api/process_animator_application.php (Nouveau)
session_start();
header('Content-Type: application/json');
require_once 'config.php';

// Sécurité : l'utilisateur doit être connecté et être un animateur
if (!isset($_SESSION['user']['id']) || !($_SESSION['user']['is_animateur'] ?? false)) {
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
$campId = $input['campId'] ?? null;
$motivation = $input['motivation'] ?? '';

if (empty($campId) || empty($motivation)) {
    http_response_code(400);
    echo json_encode(['error' => 'Toutes les informations sont requises.']);
    exit;
}

try {
    $tableName = 'Candidatures';

    $data = [
        'fields' => [
            'Candidat' => [$_SESSION['user']['id']],
            'Camp' => [$campId],
            'Motivation' => $motivation,
            'Statut' => 'En attente'
        ]
    ];

    $result = callAirtable('POST', $tableName, $data);

    if (isset($result['error'])) {
        $errorMessage = $result['response']['error']['message'] ?? "Erreur lors de l'envoi de la candidature.";
        throw new Exception($errorMessage);
    }

    http_response_code(201);
    echo json_encode(['success' => 'Candidature envoyée avec succès !']);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>