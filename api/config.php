<?php
// Fichier: /api/config.php
// Ce fichier contient vos informations secrètes de connexion à Airtable.
// NE PARTAGEZ JAMAIS CE FICHIER OU SON CONTENU.

// Votre "Personal access token" que vous avez généré sur Airtable.
define('AIRTABLE_API_KEY', 'patirCyGLZzXOZQla.dd78066f77fd3b02324df287029066dea37afabe29ce6a261b501bf739b41e53');

// L'ID de votre base de données Airtable (commence par "app...").
define('AIRTABLE_BASE_ID', 'app0AetG6XFed8k2B');

// L'URL de base de l'API Airtable.
define('AIRTABLE_API_URL', 'https://api.airtable.com/v0/');

/**
 * Une fonction simple pour communiquer avec l'API Airtable en utilisant cURL.
 *
 * @param string $method La méthode HTTP (GET, POST, PATCH, DELETE).
 * @param string $table Le nom de la table à interroger.
 * @param array|null $data Les données à envoyer pour les requêtes POST ou PATCH.
 * @param string|null $recordId L'ID de l'enregistrement pour les requêtes sur un seul enregistrement.
 * @return array Le résultat de la requête décodé depuis JSON.
 */
function callAirtable($method, $table, $data = null, $recordId = null) {
    $url = AIRTABLE_API_URL . AIRTABLE_BASE_ID . '/' . rawurlencode($table);
    if ($recordId) {
        $url .= '/' . $recordId;
    }

    $ch = curl_init();
    $headers = [
        'Authorization: Bearer ' . AIRTABLE_API_KEY,
        'Content-Type: application/json'
    ];

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

    if (($method === 'POST' || $method === 'PATCH') && $data !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code >= 200 && $http_code < 300) {
        return json_decode($response, true);
    } else {
        // En cas d'erreur, on peut la logger ou la retourner
        // Pour le débogage, on peut retourner le corps de la réponse d'erreur
        return ['error' => true, 'http_code' => $http_code, 'response' => json_decode($response, true)];
    }
}
?>