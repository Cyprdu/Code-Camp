<?php
session_start();
header('Content-Type: application/json');
require_once 'config.php';

// Sécurité
if (!isset($_SESSION['user']['id'])) { http_response_code(403); exit; }
$userId = $_SESSION['user']['id'];
$campId = $_GET['camp_id'] ?? '';
$childId = $_GET['child_id'] ?? '';
if (empty($campId) || empty($childId)) { http_response_code(400); exit; }

try {
    // Étape 1 : Vérifier que l'enfant appartient bien à l'utilisateur connecté
    $childRecord = callAirtable('GET', 'Enfants', null, $childId);
    if (isset($childRecord['error']) || !in_array($userId, $childRecord['fields']['Parent'] ?? [])) {
        http_response_code(403);
        echo json_encode(['error' => 'Accès non autorisé à cet enfant.']);
        exit;
    }

    // Étape 2 : Récupérer les détails du camp
    $campRecord = callAirtable('GET', 'Camps', null, $campId);
    if (isset($campRecord['error'])) throw new Exception('Camp introuvable.');

    // Étape 3 : Récupérer les détails de l'organisateur
    $organisateurId = $campRecord['fields']['Organisme'][0] ?? null;
    $organisateurDetails = ['nom' => 'Non spécifié', 'mail' => '', 'tel' => ''];
    if ($organisateurId) {
        $orgRecord = callAirtable('GET', 'Organisateur', null, $organisateurId);
        if (!isset($orgRecord['error'])) {
            $organisateurDetails = [
                'nom' => $orgRecord['fields']['Nom de l\'organisme'] ?? 'N/A',
                'mail' => $orgRecord['fields']['Mail'] ?? 'N/A',
                'tel' => $orgRecord['fields']['Tel'] ?? 'N/A'
            ];
        }
    }

    // Étape 4 : Formater et renvoyer la réponse
    $response = [
        'enfant' => [
            'id' => $childRecord['id'],
            'prenom' => $childRecord['fields']['Prénom'] ?? 'N/A'
        ],
        'camp' => [
            'id' => $campRecord['id'],
            'nom' => $campRecord['fields']['nom'] ?? 'N/A',
            'adresse' => $campRecord['fields']['Adresse exacte du camp'] ?? 'N/A',
            'date_debut' => $campRecord['fields']['Date début du camp'] ?? null
        ],
        'organisateur' => $organisateurDetails
    ];

    echo json_encode($response);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
