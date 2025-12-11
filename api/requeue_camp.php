<?php
// Fichier: /api/requeue_camp.php
// Version de débogage pour identifier les erreurs serveur.

// --- DÉBUT DU BLOC DE DÉBOGAGE ---
// Ces lignes forcent l'affichage de toutes les erreurs PHP.
// Elles sont très utiles pour comprendre pourquoi un script ne fonctionne pas.
error_reporting(E_ALL);
ini_set('display_errors', 1);
// --- FIN DU BLOC DE DÉBOGAGE ---

session_start();
header('Content-Type: application/json');

// On s'assure que le fichier config.php est bien inclus.
// Si le chemin est incorrect, le bloc de débogage ci-dessus affichera une erreur.
require_once 'config.php';

// Sécurité : Seul un administrateur peut effectuer cette action.
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
$campId = $input['campId'] ?? null;

if (empty($campId)) {
    http_response_code(400);
    echo json_encode(['error' => 'ID de camp manquant.']);
    exit;
}

try {
    // Préparer les données pour la mise à jour
    $updateData = [
        'fields' => [
            'Refusé' => false,     // On décoche la case "Refusé"
            'En attente' => true  // On coche la case "En attente"
        ]
    ];

    // Mettre à jour l'enregistrement du camp dans Airtable
    $result = callAirtable('PATCH', 'Camps', $updateData, $campId);

    if (isset($result['error'])) {
        // Si Airtable renvoie une erreur, on la transmet au client pour le débogage.
        $errorMessage = $result['response']['error']['message'] ?? "Erreur lors de la mise à jour du statut du camp.";
        throw new Exception($errorMessage);
    }
    
    echo json_encode(['success' => true, 'message' => 'Le camp a été remis en attente.']);

} catch (Exception $e) {
    http_response_code(500);
    // On renvoie le message d'erreur exact pour aider au débogage.
    echo json_encode(['error' => $e->getMessage()]);
}
?>
