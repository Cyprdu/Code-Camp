<?php
// Fichier: /api/get_conversations.php
// Version sécurisée qui personnalise le nom de l'interlocuteur.
session_start();
header('Content-Type: application/json');
require_once 'config.php';

if (!isset($_SESSION['user']['id'])) { http_response_code(403); exit; }
$currentUserId = $_SESSION['user']['id'];

try {
    // 1. Récupérer les conversations de l'utilisateur
    $formula = "FIND('{$currentUserId}', ARRAYJOIN({Participants}))";
    $params = ['filterByFormula' => $formula, 'sort' => [['field' => 'Dernière Activité', 'direction' => 'desc']]];
    $conversationsResult = callAirtable('GET', 'Conversations', $params);
    $conversations = $conversationsResult['records'] ?? [];

    if (empty($conversations)) {
        echo json_encode([]);
        exit;
    }

    // 2. Extraire les IDs de tous les autres participants
    $otherUserIds = [];
    foreach ($conversations as $convo) {
        $participants = $convo['fields']['Participants'] ?? [];
        foreach ($participants as $pId) {
            if ($pId !== $currentUserId) {
                $otherUserIds[$pId] = $pId; // Utilise l'ID comme clé pour dédupliquer
            }
        }
    }

    // 3. Récupérer les noms de tous ces autres participants en une seule requête
    $userNames = [];
    if (!empty($otherUserIds)) {
        $userFormulaParts = [];
        foreach ($otherUserIds as $id) {
            $userFormulaParts[] = "RECORD_ID() = '{$id}'";
        }
        $userFormula = 'OR(' . implode(', ', $userFormulaParts) . ')';
        $usersResult = callAirtable('GET', 'User', ['filterByFormula' => $userFormula, 'fields' => ['nom', 'prenom']]);
        
        foreach ($usersResult['records'] as $userRecord) {
            $userNames[$userRecord['id']] = ($userRecord['fields']['prenom'] ?? '') . ' ' . ($userRecord['fields']['nom'] ?? '');
        }
    }
    
    // 4. Formater la réponse finale
    $formattedConversations = [];
    foreach ($conversations as $convo) {
        $participants = $convo['fields']['Participants'] ?? [];
        $otherUserId = null;
        foreach($participants as $pId) { if($pId !== $currentUserId) $otherUserId = $pId; }
        
        // Détermine le nom à afficher
        $displayName = $convo['fields']['Nom']; // Nom par défaut
        if ($otherUserId && isset($userNames[$otherUserId])) {
            $displayName = trim($userNames[$otherUserId]);
        }
        
        $convo['displayName'] = $displayName;
        $formattedConversations[] = $convo;
    }

    echo json_encode($formattedConversations);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
