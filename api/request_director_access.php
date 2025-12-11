<?php
// Fichier: /api/request_director_access.php

session_start();
header('Content-Type: application/json');
require_once 'config.php';

// --- SÉCURITÉ ---
// 1. L'utilisateur doit être connecté
if (!isset($_SESSION['user']['id'])) {
    http_response_code(403); // Forbidden
    echo json_encode(['error' => 'Vous devez être connecté pour faire cette demande.']);
    exit;
}

// 2. La méthode doit être POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['error' => 'Méthode non autorisée.']);
    exit;
}

try {
    $userId = $_SESSION['user']['id'];

    // Préparer les données pour mettre à jour la case à cocher dans Airtable
    $updateData = [
        'fields' => [
            // Assurez-vous que le nom du champ "Demande en cours..." est exact
            'Demande en cours...' => true
        ]
    ];

    // Appeler Airtable pour mettre à jour l'enregistrement de l'utilisateur
    $result = callAirtable('PATCH', 'User', $updateData, $userId);

    if (isset($result['error'])) {
        throw new Exception($result['response']['error']['message'] ?? "Erreur lors de la communication avec la base de données.");
    }

    // Mettre à jour la session pour que l'utilisateur n'ait plus à refaire la demande
    $_SESSION['user']['demande_en_cours'] = true;

    // Renvoyer une réponse de succès
    echo json_encode(['success' => 'Votre demande a été envoyée avec succès ! Notre équipe vous répondra bientôt.']);

} catch (Exception $e) {
    http_response_code(500); // Internal Server Error
    echo json_encode(['error' => $e->getMessage()]);
}
?>
