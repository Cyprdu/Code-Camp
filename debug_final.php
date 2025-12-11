<?php
// On active toutes les erreurs pour ne rien manquer.
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Démarrage manuel de la session pour isoler le test.
session_start();

// Inclusion manuelle de la config pour la fonction callAirtable et les clés.
// Si ce fichier n'est pas trouvé, une erreur s'affichera immédiatement.
require_once 'api/config.php'; 
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Debug Final des Réservations</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-4 md:p-8">
<div class="bg-white p-6 md:p-8 rounded-xl shadow-lg border max-w-5xl mx-auto space-y-6">
    <h1 class="text-2xl font-bold text-center">Script de Débogage Ultime</h1>
    
    <?php
    // ÉTAPE 1 : VÉRIFICATION DE LA CONFIGURATION ET DE LA SESSION
    echo '<div><h2 class="text-xl font-semibold text-blue-600 border-b pb-2 mb-2">Étape 1 : Vérification de la Configuration</h2>';
    if (!isset($_SESSION['user']['id'])) {
        echo "<p class='text-red-500 font-bold'>ERREUR : Utilisateur non connecté. Veuillez vous connecter avant de lancer ce script.</p></div>";
        exit;
    }
    $userId = $_SESSION['user']['id'];
    echo "<p><strong>ID de la Base Airtable utilisée :</strong> <span class='font-mono text-sm'>" . htmlspecialchars(AIRTABLE_BASE_ID) . "</span></p>";
    echo "<p><strong>ID de l'Utilisateur connecté :</strong> <span class='font-mono text-sm'>" . htmlspecialchars($userId) . "</span></p>";
    echo '</div>';

    // ÉTAPE 2 : CONSTRUCTION DE LA REQUÊTE
    echo '<div><h2 class="text-xl font-semibold text-blue-600 border-b pb-2 mb-2">Étape 2 : Construction de la Requête</h2>';
    $formula = "{Parent_ID_Unique} = '{$userId}'";
    echo "<p><strong>Formule de filtre qui sera envoyée à Airtable :</strong></p>";
    echo "<pre class='bg-yellow-100 text-yellow-800 p-3 my-2 rounded-md font-mono text-sm'>" . htmlspecialchars($formula) . "</pre>";
    
    // Construction manuelle de l'URL pour l'afficher
    $url = AIRTABLE_API_URL . AIRTABLE_BASE_ID . '/Enfants' . '?filterByFormula=' . urlencode($formula);
    echo "<p><strong>URL exacte de l'API qui va être appelée (pour trouver les enfants) :</strong></p>";
    echo "<pre class='bg-gray-100 p-2 rounded-md text-xs break-all'>" . htmlspecialchars($url) . "</pre>";
    echo '</div>';
    
    // ÉTAPE 3 : EXÉCUTION DE L'APPEL ET AFFICHAGE DE LA RÉPONSE BRUTE
    echo '<div><h2 class="text-xl font-semibold text-blue-600 border-b pb-2 mb-2">Étape 3 : Exécution et Réponse Brute de l\'API</h2>';
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . AIRTABLE_API_KEY, 'Content-Type: application/json']);
    $response_body = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "<p><strong>Code de statut HTTP retourné par Airtable :</strong> <span class='font-bold " . ($http_code >= 200 && $http_code < 300 ? "text-green-600" : "text-red-600") . "'>" . htmlspecialchars($http_code) . "</span></p>";
    echo "<p><strong>Réponse brute de l'API Airtable (texte JSON) :</strong></p>";
    echo "<pre class='bg-gray-100 p-4 rounded-lg border text-sm'>" . htmlspecialchars($response_body) . "</pre>";
    echo '</div>';

    // ÉTAPE 4 : INTERPRÉTATION DU RÉSULTAT
    echo '<div><h2 class="text-xl font-semibold text-blue-600 border-b pb-2 mb-2">Étape 4 : Interprétation du Résultat</h2>';
    $resultData = json_decode($response_body, true);
    if ($http_code >= 300 || isset($resultData['error'])) {
        echo "<p class='text-red-500 font-bold'>DIAGNOSTIC : ERREUR. Airtable a retourné une erreur. Le problème vient de la requête elle-même (URL, formule, clé API) ou d'un nom de champ incorrect dans la formule.</p>";
    } else {
        $recordsFound = $resultData['records'] ?? [];
        if (empty($recordsFound)) {
            echo "<p class='text-red-500 font-bold'>DIAGNOSTIC : ANOMALIE. La requête a réussi (code " . $http_code . ") mais Airtable n'a retourné **aucun enfant**. Cela confirme que la liaison via le champ `Parent_ID_Unique` est le problème dans votre base de données. Il faut vérifier sa configuration.</p>";
        } else {
            echo "<p class='text-green-600 font-bold'>DIAGNOSTIC : SUCCÈS. La requête a parfaitement fonctionné. Airtable a retourné " . count($recordsFound) . " enfant(s) appartenant à l'utilisateur connecté.</p>";
            $names = [];
            foreach($recordsFound as $record) {
                $names[] = $record['fields']['Prénom'] ?? '[Prénom non trouvé]';
            }
            echo "<p><strong>Liste des prénoms trouvés :</strong> " . htmlspecialchars(implode(', ', $names)) . "</p>";
            echo "<hr class='my-4'>";
            echo "<p class='font-bold'>CONCLUSION FINALE : Si la liste ci-dessus contient UNIQUEMENT vos enfants, alors la logique de filtrage est **100% CORRECTE**. Si vous voyez toujours toutes les réservations sur la page principale, cela signifie que le fichier `api/get_my_reservations.php` sur votre serveur n'est pas la bonne version ou qu'un cache agressif est toujours actif malgré nos efforts.</p>";
        }
    }
    echo '</div>';
    ?>
</div>
</body>
</html>