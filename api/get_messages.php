<?php
// Fichier: /api/get_messages.php
// Version finale utilisant le champ de recherche d'ID unique

session_start();
header('Content-Type: application/json');
require_once 'config.php';

// Sécurité : Vérifie que l'utilisateur est connecté.
if (!isset($_SESSION['user']['id'])) { 
    http_response_code(403);
    echo json_encode(['error' => 'Accès non autorisé.']);
    exit; 
}
$userId = $_SESSION['user']['id'];

$conversationId = $_GET['id'] ?? '';
if (empty($conversationId)) { 
    http_response_code(400); 
    echo json_encode(['error' => 'ID de conversation manquant.']);
    exit;
}

try {
    // Étape de sécurité : Vérifier que l'utilisateur fait bien partie de la conversation.
    $convoRecord = callAirtable('GET', 'Conversations', null, $conversationId);
    if (isset($convoRecord['error']) || !in_array($userId, $convoRecord['fields']['Participants'] ?? [])) {
        http_response_code(403);
        echo json_encode(['error' => "Vous n'êtes pas autorisé à voir cette conversation."]);
        exit;
    }

    // On utilise notre champ fiable pour une recherche simple et parfaite.
    $formula = "{Conversation_ID_Unique} = '{$conversationId}'";
    
    // Paramètres de la requête : on trie par "Date d'envoi" en ordre ascendant.
    $params = [
        'filterByFormula' => $formula,
        'sort' => [['field' => "Date d'envoi", 'direction' => 'asc']]
    ];
    
    // Appel à l'API pour récupérer les messages triés.
    $result = callAirtable('GET', 'Messages', $params);

    if (isset($result['error'])) {
        throw new Exception($result['response']['error']['message'] ?? "Erreur lors de la récupération des messages.");
    }

    echo json_encode($result['records'] ?? []);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>