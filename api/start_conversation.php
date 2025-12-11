<?php
// Fichier: /api/start_conversation.php
// Version sécurisée qui crée une conversation privée entre un parent et un directeur.

session_start();
header('Content-Type: application/json');
require_once 'config.php';

if (!isset($_SESSION['user']['id'])) { http_response_code(403); exit; }

$input = json_decode(file_get_contents('php://input'), true);
$organisateurId = $input['organisateurId'] ?? null;
if (empty($organisateurId)) { http_response_code(400); exit; }

$parentId = $_SESSION['user']['id'];
$parentName = $_SESSION['user']['prenom'] . ' ' . $_SESSION['user']['nom'];

try {
    // Étape 1: Récupérer les informations de l'organisme pour trouver le directeur.
    $orgRecord = callAirtable('GET', 'Organisateur', null, $organisateurId);
    if (isset($orgRecord['error'])) throw new Exception("Organisateur introuvable.");

    $directorId = $orgRecord['fields']['Liaison'][0] ?? null;
    if (!$directorId) throw new Exception("Le directeur de cet organisme n'est pas défini.");

    // Le parent ne peut pas se contacter lui-même.
    if ($parentId === $directorId) {
        throw new Exception("Vous ne pouvez pas démarrer une conversation avec vous-même.");
    }
    
    // Étape 2: Créer une nouvelle conversation.
    $orgName = $orgRecord['fields']['Nom de l\'organisme'] ?? 'Organisateur';
    
    // Le nom de la conversation est maintenant plus descriptif.
    $conversationName = "{$parentName} / {$orgName}";

    $conversationData = [
        'fields' => [
            'Nom' => $conversationName,
            'Participants' => [$parentId, $directorId], // Lie le parent et le directeur
            'Organisme' => [$organisateurId]
        ]
    ];
    
    $newConversation = callAirtable('POST', 'Conversations', $conversationData);
    if (isset($newConversation['error'])) {
        throw new Exception($newConversation['response']['error']['message'] ?? 'Erreur Airtable lors de la création.');
    }

    echo json_encode(['conversationId' => $newConversation['id']]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
