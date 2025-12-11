<?php
// Fichier: /api/toggle_favorite.php
// Ajoute ou supprime un camp des favoris d'un utilisateur.

session_start();
header('Content-Type: application/json');
require_once 'config.php';

// SÉCURITÉ : L'utilisateur doit être connecté.
if (!isset($_SESSION['user']['id'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Vous devez être connecté pour gérer vos favoris.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Méthode non autorisée.']);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    $campId = $input['campId'] ?? null;

    if (empty($campId)) {
        http_response_code(400);
        echo json_encode(['error' => 'ID de camp manquant.']);
        exit;
    }

    $userId = $_SESSION['user']['id'];
    
    // Étape 1 : Récupérer les favoris actuels de l'utilisateur
    $userRecord = callAirtable('GET', 'User', null, $userId);
    if (isset($userRecord['error'])) {
        throw new Exception("Impossible de récupérer les informations de l'utilisateur.");
    }
    $currentFavorites = $userRecord['fields']['Favories'] ?? [];
    
    $isFavorited = in_array($campId, $currentFavorites);
    $newFavorites = [];

    // Étape 2 : Ajouter ou supprimer le camp de la liste
    if ($isFavorited) {
        // Le camp est déjà en favori, on le supprime (unfavorite)
        $newFavorites = array_diff($currentFavorites, [$campId]);
    } else {
        // Le camp n'est pas en favori, on l'ajoute
        $newFavorites = $currentFavorites;
        $newFavorites[] = $campId;
    }
    
    // Étape 3 : Mettre à jour l'enregistrement de l'utilisateur avec la nouvelle liste
    $updateData = [
        'fields' => [
            'Favories' => array_values($newFavorites) // Re-indexe le tableau
        ]
    ];
    $updateResult = callAirtable('PATCH', 'User', $updateData, $userId);

    if (isset($updateResult['error'])) {
        throw new Exception("Erreur lors de la mise à jour des favoris.");
    }

    // Étape 4 : Mettre à jour la session et renvoyer le nouveau statut
    $_SESSION['user']['favorites'] = array_values($newFavorites);
    
    echo json_encode(['success' => true, 'isFavorited' => !$isFavorited]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
