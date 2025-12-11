<?php
// Fichier: /api/send_message.php
// Version mise à jour pour gérer le statut "non lu"

session_start();
header('Content-Type: application/json');
require_once 'config.php';

if (!isset($_SESSION['user']['id'])) { http_response_code(403); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit; }

$input = json_decode(file_get_contents('php://input'), true);
$conversationId = $input['conversationId'] ?? null;
$content = $input['content'] ?? '';
$userId = $_SESSION['user']['id'];

if (empty($conversationId) || empty(trim($content))) { http_response_code(400); exit; }

try {
    // 1. On envoie le message (logique existante)
    $messageData = [
        'fields' => [
            'Contenu' => $content,
            'Auteur' => [$userId],
            'Conversation' => [$conversationId]
        ]
    ];
    $result = callAirtable('POST', 'Messages', $messageData);
    if(isset($result['error'])) throw new Exception('Erreur Airtable lors de l\'envoi du message');

    // --- NOUVELLE LOGIQUE ---
    // 2. On met à jour la conversation pour notifier les autres participants.
    
    // On récupère les détails de la conversation pour connaître les participants.
    $convoRecord = callAirtable('GET', 'Conversations', null, $conversationId);
    if (isset($convoRecord['error'])) throw new Exception('Impossible de trouver la conversation à mettre à jour.');

    $participants = $convoRecord['fields']['Participants'] ?? [];

    // On crée la liste des personnes qui n'ont pas encore lu le message (tous sauf l'expéditeur).
    $unreadBy = array_filter($participants, function($participantId) use ($userId) {
        return $participantId !== $userId;
    });

    // On met à jour la conversation avec la liste "Non lus par" et la date de dernière activité.
    $updateConvoData = [
        'fields' => [
            'Non lus par' => array_values($unreadBy), // La liste des autres participants
            'Dernière Activité' => date('c') // Met à jour le champ de tri
        ]
    ];
    callAirtable('PATCH', 'Conversations', $updateConvoData, $conversationId);
    
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>