<?php
// Fichier: /api/send_message.php
require_once 'config.php';

if (!isset($_SESSION['user']['id'])) { sendJson(['error' => 'Non connecté'], 403); }

$input = json_decode(file_get_contents('php://input'), true);
$convoId = $input['conversationId'] ?? null;
$content = $input['content'] ?? '';
$userId = $_SESSION['user']['id'];

if (!$convoId || empty(trim($content))) sendJson(['error' => 'Données invalides'], 400);

try {
    $pdo->beginTransaction();

    // Insérer message
    $stmt = $pdo->prepare("INSERT INTO messages (conversation_id, user_id, contenu) VALUES (?, ?, ?)");
    $stmt->execute([$convoId, $userId, $content]);

    // Mettre à jour date activité conversation
    $stmtUpd = $pdo->prepare("UPDATE conversations SET derniere_activite = NOW() WHERE id = ?");
    $stmtUpd->execute([$convoId]);

    // Mettre les autres participants en "non lu" (has_read = 0)
    $stmtUnread = $pdo->prepare("UPDATE conversation_participants SET has_read = 0 WHERE conversation_id = ? AND user_id != ?");
    $stmtUnread->execute([$convoId, $userId]);

    $pdo->commit();
    sendJson(['success' => true]);

} catch (Exception $e) {
    $pdo->rollBack();
    sendJson(['error' => $e->getMessage()], 500);
}
?>