<?php
// Fichier: /api/get_organisateurs.php
// Nouvelle version qui récupère les organismes via la table User pour plus de fiabilité.

// --- DÉBUT DU BLOC DE DÉBOGAGE ---
error_reporting(E_ALL);
ini_set('display_errors', 1);
// --- FIN DU BLOC DE DÉBOGAGE ---

session_start();
header('Content-Type: application/json');
require_once 'config.php';

// Sécurité : l'utilisateur doit être un directeur connecté.
if (!isset($_SESSION['user']['id']) || !$_SESSION['user']['is_directeur']) {
    http_response_code(403);
    echo json_encode(['error' => 'Accès non autorisé.']);
    exit;
}

try {
    $userId = $_SESSION['user']['id'];
    
    // --- Étape 1: Récupérer l'enregistrement de l'utilisateur pour trouver les organismes liés ---
    $userRecord = callAirtable('GET', 'User', null, $userId);

    if (isset($userRecord['error'])) {
        throw new Exception("Impossible de récupérer les informations de l'utilisateur.");
    }

    // --- Étape 2: Extraire les IDs des organismes liés à cet utilisateur ---
    // Assurez-vous que le nom de la colonne dans votre table "User" est bien "Organisateur".
    $organisateurIds = $userRecord['fields']['Organisateur'] ?? [];

    if (empty($organisateurIds)) {
        // Si l'utilisateur n'est lié à aucun organisme, on renvoie un tableau vide.
        echo json_encode([]);
        exit;
    }

    // --- Étape 3: Construire une formule pour récupérer les détails de chaque organisme lié ---
    $formulaParts = [];
    foreach ($organisateurIds as $id) {
        $formulaParts[] = "RECORD_ID() = '{$id}'";
    }
    $formula = 'OR(' . implode(', ', $formulaParts) . ')';
    
    // --- Étape 4: Récupérer les détails des organismes en construisant l'URL manuellement ---
    $url = AIRTABLE_API_URL . AIRTABLE_BASE_ID . '/Organisateur?filterByFormula=' . urlencode($formula);
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . AIRTABLE_API_KEY]);
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($http_code >= 300) {
        throw new Exception("Impossible de récupérer les détails des organismes.");
    }

    $result = json_decode($response, true);

    // --- Étape 5: Formater la réponse finale ---
    $organisateurs = [];
    if (isset($result['records'])) {
        foreach ($result['records'] as $record) {
            // On vérifie maintenant l'existence de chaque champ avec des '??' pour plus de sécurité.
            $organisateurs[] = [
                'id' => $record['id'],
                'nom' => $record['fields']["Nom de l'organisme"] ?? 'N/A',
                'tel' => $record['fields']['Tel'] ?? '',
                'mail' => $record['fields']['Mail'] ?? '',
                'web' => $record['fields']['Web'] ?? '',
                'portefeuille' => $record['fields']['Portefeuille'] ?? 0,
                // Assurez-vous que le nom du champ "Lookup" est correct ici.
                'camps' => $record['fields']['Nom (from Camp)'] ?? [] 
            ];
        }
    }
    
    echo json_encode($organisateurs);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
