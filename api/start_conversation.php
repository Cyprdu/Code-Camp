<?php
// Fichier: /api/start_conversation.php
require_once 'config.php';

if (!isset($_SESSION['user']['id'])) { sendJson(['error' => 'Accès interdit'], 403); }

$input = json_decode(file_get_contents('php://input'), true);
$orgId = $input['organisateurId'] ?? null;
$userId = $_SESSION['user']['id'];

if (!$orgId) sendJson(['error' => 'Organisateur manquant'], 400);

try {
    // 1. Trouver le directeur de l'organisme
    $stmtOrg = $pdo->prepare("SELECT user_id, nom FROM organisateurs WHERE id = ?");
    $stmtOrg->execute([$orgId]);
    $org = $stmtOrg->fetch();

    if (!$org || !$org['user_id']) {
        throw new Exception("Directeur introuvable pour cet organisme.");
    }
    $directorId = $org['user_id'];

    if ($directorId == $userId) {
        throw new Exception("Vous ne pouvez pas discuter avec vous-même.");
    }

    // 2. Vérifier si une conversation existe déjà entre ces deux personnes
    // (Simplification : on cherche une convo commune. Pour une vraie unicité par sujet, il faudrait plus de logique)
    // Ici on crée une nouvelle à chaque fois comme dans votre ancien code, ou on peut vérifier.
    
    // Création de la conversation
    $pdo->beginTransaction();
    
    $convoName = $_SESSION['user']['prenom'] . ' ' . $_SESSION['user']['nom'] . ' / ' . $org['nom'];
    
    $stmtConvo = $pdo->prepare("INSERT INTO conversations (nom) VALUES (?)");
    $stmtConvo->execute([$convoName]);
    $convoId = $pdo->lastInsertId();

    // Ajout des participants
    $stmtPart = $pdo->prepare("INSERT INTO conversation_participants (conversation_id, user_id, has_read) VALUES (?, ?, ?)");
    $stmtPart->execute([$convoId, $userId, 1]); // L'initiateur a lu
    $stmtPart->execute([$convoId, $directorId, 0]); // Le destinataire n'a pas lu

    $pdo->commit();
    
    sendJson(['conversationId' => $convoId]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    sendJson(['error' => $e->getMessage()], 500);
}
?>