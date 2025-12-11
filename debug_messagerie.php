<?php
// On active toutes les erreurs pour ne rien manquer
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once 'api/config.php'; 
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Debug Messagerie</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-8">
<div class="bg-white p-8 rounded-xl shadow-lg border max-w-5xl mx-auto space-y-6">
    <h1 class="text-2xl font-bold text-center">Script de Débogage de la Messagerie</h1>
    
    <?php
    // L'ID de la conversation que vous voulez tester est maintenant directement dans le code.
    $testConversationId = 'reck7cqipKVYHtspb'; // VOTRE ID EST ICI

    echo '<div><h2 class="text-xl font-semibold text-blue-600 border-b pb-2 mb-2">Étape 1 : Vérification de la Configuration</h2>';
    if (!isset($_SESSION['user']['id'])) {
        echo "<p class='text-red-500'>ERREUR : Utilisateur non connecté.</p></div>";
        exit;
    }
    $userId = $_SESSION['user']['id'];
    echo "<p><strong>ID de l'Utilisateur connecté :</strong> <span class='font-mono text-sm'>" . htmlspecialchars($userId) . "</span></p>";
    echo "<p><strong>ID de la Conversation en test :</strong> <span class='font-mono text-sm'>" . htmlspecialchars($testConversationId) . "</span></p>";
    echo '</div>';

    try {
        // Étape 2 : Vérifier que l'utilisateur est bien participant
        echo '<div><h2 class="text-xl font-semibold text-blue-600 border-b pb-2 mb-2">Étape 2 : Vérification de Sécurité</h2>';
        $convoRecord = callAirtable('GET', 'Conversations', null, $testConversationId);

        // On vérifie d'abord que la conversation a bien été trouvée
        if(isset($convoRecord['error'])) {
             echo "<p class='text-red-500'><strong>ERREUR :</strong> Impossible de trouver la conversation avec l'ID `{$testConversationId}`. Vérifiez que l'ID est correct.</p></div>";
             exit;
        }

        if(!in_array($userId, $convoRecord['fields']['Participants'] ?? [])) {
            echo "<p class='text-red-500'><strong>ERREUR :</strong> L'utilisateur connecté (ID: {$userId}) ne fait PAS partie des participants de cette conversation. Participants trouvés : ";
            print_r($convoRecord['fields']['Participants'] ?? ['Aucun']);
            echo "</p></div>";
            exit;
        }
        echo "<p class='text-green-600'><strong>SUCCÈS :</strong> L'utilisateur est bien un participant de la conversation.</p></div>";


        // ÉTAPE 3: Recherche des messages avec le champ unique
        echo '<div><h2 class="text-xl font-semibold text-blue-600 border-b pb-2 mb-2">Étape 3 : Recherche des Messages</h2>';
        $formula = "{Conversation_ID_Unique} = '{$testConversationId}'";
        echo "<p><strong>Formule de filtre envoyée à Airtable :</strong></p>";
        echo "<pre class='bg-yellow-100 text-yellow-800 p-3 my-2 rounded-md font-mono text-sm'>" . htmlspecialchars($formula) . "</pre>";
        
        $params = ['filterByFormula' => $formula];
        $messagesResult = callAirtable('GET', 'Messages', $params);
        
        echo "<p><strong>Réponse brute de l'API Airtable (JSON) :</strong></p>";
        echo "<pre class='bg-gray-100 p-4 rounded-lg border text-sm'>" . htmlspecialchars(json_encode($messagesResult, JSON_PRETTY_PRINT)) . "</pre>";
        echo '</div>';


        // ÉTAPE 4 : Interprétation
        echo '<div><h2 class="text-xl font-semibold text-blue-600 border-b pb-2 mb-2">Étape 4 : Interprétation</h2>';
        if (isset($messagesResult['error'])) {
            echo "<p class='text-red-500'><strong>DIAGNOSTIC : ERREUR.</strong> Airtable a retourné une erreur. Le problème vient probablement du nom du champ `Conversation_ID_Unique` ou de la formule.</p>";
        } else if (empty($messagesResult['records'])) {
            echo "<p class='text-orange-500'><strong>DIAGNOSTIC : ANOMALIE.</strong> La requête a réussi mais Airtable n'a retourné **aucun message**. Cela signifie que le champ `Conversation_ID_Unique` dans votre table `Messages` est vide ou ne contient pas l'ID de conversation attendu. Veuillez vérifier sa configuration.</p>";
        } else {
            echo "<p class='text-green-600 font-bold'>DIAGNOSTIC : SUCCÈS. La requête a parfaitement fonctionné et a retourné " . count($messagesResult['records']) . " message(s).</p>";
        }
        echo '</div>';

    } catch (Exception $e) {
        echo "<div><h2 class='text-xl font-semibold text-red-600'>Erreur Fatale</h2><p>Le script a rencontré une erreur : " . $e->getMessage() . "</p></div>";
    }
    ?>
</div>
</body>
</html>