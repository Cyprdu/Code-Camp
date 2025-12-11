<?php
// Fichier: /api/get_unread_messages_count.php
// Compte le nombre de conversations non lues pour l'utilisateur connecté.

session_start();
header('Content-Type: application/json');
require_once 'config.php';

// On vérifie que l'utilisateur est connecté.
if (!isset($_SESSION['user']['id'])) {
    http_response_code(403);
    echo json_encode(['count' => 0]);
    exit;
}
$userId = $_SESSION['user']['id'];

try {
    // La formule recherche l'ID de l'utilisateur dans le champ "Non lus par".
    $formula = "FIND('{$userId}', ARRAYJOIN({Non lus par}))";
    
    // On optimise la requête en ne demandant qu'un seul champ, car seul le nombre nous intéresse.
    $params = ['filterByFormula' => $formula, 'fields' => ['Nom']];
    
    $result = callAirtable('GET', 'Conversations', $params);
    
    if (isset($result['error'])) {
        throw new Exception('Erreur API lors de la récupération du compteur.');
    }

    $count = isset($result['records']) ? count($result['records']) : 0;
    echo json_encode(['count' => $count]);

} catch (Exception $e) {
    // En cas d'erreur, on renvoie 0 pour ne pas afficher de badge d'erreur.
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage(), 'count' => 0]);
}
?>