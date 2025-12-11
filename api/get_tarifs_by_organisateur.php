<?php
session_start();
header('Content-Type: application/json');
require_once 'config.php';

// Sécurité : on vérifie juste que l'utilisateur est connecté
if (!isset($_SESSION['user']['id'])) { 
    http_response_code(403); 
    echo json_encode(['error' => 'Accès non autorisé.']);
    exit; 
}

try {
    // On ne filtre plus, on récupère TOUT depuis la table "Tarif"
    $result = callAirtable('GET', 'Tarif');

    if (isset($result['error'])) {
        throw new Exception($result['response']['error']['message'] ?? 'Erreur Airtable');
    }

    // On renvoie tous les enregistrements
    echo json_encode($result['records'] ?? []);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>