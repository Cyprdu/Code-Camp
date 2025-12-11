<?php
// Fichier: /api/mark_conversation_as_read.php
// Retire l'utilisateur courant de la liste "Non lus par" pour une conversation donnée.

session_start();
header('Content-Type: application/json');
require_once 'config.php';

if (!isset($_SESSION['user']['id'])) { http_response_code(403); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit; }

$input = json_decode(file_get_contents('php://input'), true);
$conversationId = $input['conversationId'] ?? null;
$userId = $_SESSION['user']['id'];

if (empty($conversationId)) { http_response_code(400); exit; }

try {
    // 1. Récupérer la conversation pour connaître la liste "Non lus par" actuelle.
    $convoRecord = callAirtable('GET', 'Conversations', null, $conversationId);
    if (isset($convoRecord['error'])) throw new Exception('Conversation introuvable.');

    $unreadBy = $convoRecord['fields']['Non lus par'] ?? [];

    // 2. Si l'utilisateur est dans la liste, on le retire.
    if (in_array($userId, $unreadBy)) {
        $newUnreadBy = array_filter($unreadBy, function($participantId) use ($userId) {
            return $participantId !== $userId;
        });

        // 3. On met à jour la conversation avec la nouvelle liste.
        $updateData = ['fields' => ['Non lus par' => array_values($newUnreadBy)]];
        callAirtable('PATCH', 'Conversations', $updateData, $conversationId);
    }
    
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>