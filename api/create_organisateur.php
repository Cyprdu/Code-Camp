<?php
session_start();
header('Content-Type: application/json');
require_once 'config.php';

if (!isset($_SESSION['user']['id']) || !$_SESSION['user']['is_directeur']) {
    http_response_code(403);
    echo json_encode(['error' => 'Accès non autorisé.']);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Méthode non autorisée.']);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $required_fields = ['nom', 'tel', 'mail'];
    foreach($required_fields as $field) {
        if(empty($input[$field])) {
            throw new Exception("Le champ '$field' est obligatoire.");
        }
    }

    $data = [
        'fields' => [
            "Nom de l'organisme" => $input['nom'],
            'Tel' => $input['tel'],
            'Mail' => $input['mail'],
            'Web' => $input['web'] ?? '',
            'Portefeuille' => 0,
            'Liaison' => [$_SESSION['user']['id']]
        ]
    ];

    $result = callAirtable('POST', 'Organisateur', $data);

    if (isset($result['error'])) {
        throw new Exception($result['response']['error']['message'] ?? "Erreur lors de la création de l'organisme.");
    }

    http_response_code(201);
    echo json_encode($result);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>