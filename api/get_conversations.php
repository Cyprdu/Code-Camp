<?php
// Fichier: /api/get_conversations.php
require_once 'config.php';

if (!isset($_SESSION['user']['id'])) { sendJson(['error' => 'Non connecté'], 403); }
$userId = $_SESSION['user']['id'];

try {
    // On récupère les conversations où l'utilisateur est participant
    // Et on joint pour avoir le nom de l'AUTRE participant
    $sql = "
        SELECT c.id, c.derniere_activite, 
               u.prenom, u.nom
        FROM conversations c
        JOIN conversation_participants cp_me ON c.id = cp_me.conversation_id
        JOIN conversation_participants cp_other ON c.id = cp_other.conversation_id
        JOIN users u ON cp_other.user_id = u.id
        WHERE cp_me.user_id = ? 
        AND cp_other.user_id != ? -- On prend l'autre participant
        ORDER BY c.derniere_activite DESC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$userId, $userId]);
    $convos = $stmt->fetchAll();

    $output = array_map(function($c) {
        return [
            'id' => $c['id'],
            'displayName' => $c['prenom'] . ' ' . $c['nom'],
            'date' => $c['derniere_activite']
        ];
    }, $convos);

    sendJson($output);

} catch (Exception $e) {
    sendJson(['error' => $e->getMessage()], 500);
}
?>