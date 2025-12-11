<?php
// Fichier: /api/delete_camp.php

session_start();
header('Content-Type: application/json');
require_once 'config.php';

// --- SÉCURITÉ ---
// 1. On vérifie la méthode de la requête
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['error' => 'Méthode non autorisée.']);
    exit;
}

// 2. On vérifie que l'utilisateur est connecté et est un directeur
if (!isset($_SESSION['user']['id']) || !$_SESSION['user']['is_directeur']) {
    http_response_code(403); // Forbidden
    echo json_encode(['error' => 'Accès non autorisé.']);
    exit;
}

// 3. On récupère et valide les données d'entrée
$input = json_decode(file_get_contents('php://input'), true);
$campId = $input['id'] ?? null;

if (empty($campId)) {
    http_response_code(400); // Bad Request
    echo json_encode(['error' => 'ID de camp manquant.']);
    exit;
}

try {
    // --- Logique de suppression ---
    $userId = $_SESSION['user']['id'];

    // Étape 1 : Supprimer l'enregistrement du camp de la table "Camps"
    $deleteResult = callAirtable('DELETE', 'Camps', null, $campId);

    if (isset($deleteResult['error'])) {
        // Si la suppression échoue, on arrête tout
        throw new Exception($deleteResult['response']['error']['message'] ?? 'Erreur lors de la suppression du camp.');
    }

    // Étape 2 : Mettre à jour l'enregistrement de l'utilisateur pour enlever le camp supprimé
    // On récupère d'abord l'utilisateur pour avoir sa liste actuelle de camps
    $userRecord = callAirtable('GET', 'User', null, $userId);
    if (isset($userRecord['error'])) {
        // C'est un problème si on ne trouve pas l'utilisateur, mais le camp est déjà supprimé.
        // On log l'erreur mais on peut renvoyer un succès partiel.
        error_log("Camp $campId supprimé mais impossible de trouver l'utilisateur $userId pour le délier.");
        echo json_encode(['success' => true, 'message' => 'Camp supprimé, mais une erreur est survenue lors de la mise à jour de votre profil.']);
        exit;
    }
    
    // On filtre la liste des camps de l'utilisateur pour enlever celui qui a été supprimé
    $existingCamps = $userRecord['fields']['proprio'] ?? [];
    $updatedCamps = array_filter($existingCamps, function($id) use ($campId) {
        return $id !== $campId;
    });

    // On prépare les données pour la mise à jour (PATCH)
    $updateUserData = [
        'fields' => [
            // Il faut s'assurer de redonner un tableau simple (pas associatif) à Airtable
            'proprio' => array_values($updatedCamps) 
        ]
    ];

    // On met à jour l'enregistrement de l'utilisateur
    callAirtable('PATCH', 'User', $updateUserData, $userId);

    // Si tout s'est bien passé
    echo json_encode(['success' => true, 'message' => 'Le camp a été supprimé avec succès.']);

} catch (Exception $e) {
    http_response_code(500); // Internal Server Error
    echo json_encode(['error' => $e->getMessage()]);
}
?>
