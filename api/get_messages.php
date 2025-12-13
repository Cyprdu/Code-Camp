<?php
// Fichier: /api/get_messages.php
require_once 'config.php';

if (!isset($_SESSION['user']['id'])) { sendJson(['error' => 'Non connecté'], 403); }
$userId = $_SESSION['user']['id'];
$convoId = $_GET['id'] ?? null;

if (!$convoId) sendJson(['error' => 'ID manquant'], 400);

try {
    // Sécurité : vérifier participation
    $stmtCheck = $pdo->prepare("SELECT 1 FROM conversation_participants WHERE conversation_id = ? AND user_id = ?");
    $stmtCheck->execute([$convoId, $userId]);
    if (!$stmtCheck->fetch()) {
        sendJson(['error' => 'Accès interdit à cette conversation'], 403);
    }

    // Récupérer les messages
    $stmt = $pdo->prepare("SELECT * FROM messages WHERE conversation_id = ? ORDER BY date_envoi ASC");
    $stmt->execute([$convoId]);
    $messages = $stmt->fetchAll();

    // Marquer comme lu
    $stmtRead = $pdo->prepare("UPDATE conversation_participants SET has_read = 1 WHERE conversation_id = ? AND user_id = ?");
    $stmtRead->execute([$convoId, $userId]);

    // Formatage Airtable-like pour ne pas casser le JS existant (important !)
    // Votre JS attend : record.fields.Contenu, record.fields.Auteur (array), record.fields["Date d'envoi"]
    $formatted = array_map(function($msg) {
        return [
            'fields' => [
                'Contenu' => $msg['contenu'],
                'Auteur' => [$msg['user_id']], // Tableau car Airtable renvoyait un tableau
                "Date d'envoi" => $msg['date_envoi']
            ]
        ];
    }, $messages);

    sendJson($formatted);

} catch (Exception $e) {
    sendJson(['error' => $e->getMessage()], 500);
}
?>