<?php
require_once 'config.php';
if (!isset($_SESSION['user']['id'])) { sendJson(['count' => 0]); }

try {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM conversation_participants WHERE user_id = ? AND has_read = 0");
    $stmt->execute([$_SESSION['user']['id']]);
    sendJson(['count' => $stmt->fetchColumn()]);
} catch (Exception $e) {
    sendJson(['count' => 0]);
}
?>