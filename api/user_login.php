<?php
// Fichier: /api/user_login.php (Corrigé)
session_start();
header('Content-Type: application/json');
require_once 'config.php';

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['error' => 'Méthode non autorisée.']);
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true);
    $mail = trim($input['mail'] ?? '');
    $password = $input['password'] ?? '';

    if (empty($mail) || empty($password)) {
        http_response_code(400);
        echo json_encode(['error' => 'Email et mot de passe requis.']);
        exit;
    }

    $tableName = 'User';
    $formula = "LOWER(TRIM({mail})) = '" . strtolower(addslashes($mail)) . "'";
    $url = AIRTABLE_API_URL . AIRTABLE_BASE_ID . '/' . rawurlencode($tableName) . '?filterByFormula=' . urlencode($formula);

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . AIRTABLE_API_KEY]);
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code >= 300) { throw new Exception("Erreur de communication avec la base de données."); }
    $result = json_decode($response, true);

    if (empty($result['records'])) {
        http_response_code(401);
        echo json_encode(['error' => 'Email ou mot de passe incorrect.']);
        exit;
    }

    $userRecord = $result['records'][0];
    $hashedPassword = $userRecord['fields']['Mot de passe aché'] ?? '';

    if (password_verify($password, $hashedPassword)) {
        $_SESSION['user'] = [
            'id' => $userRecord['id'],
            'nom' => $userRecord['fields']['nom'] ?? '',
            'prenom' => $userRecord['fields']['prenom'] ?? '',
            'mail' => $userRecord['fields']['mail'] ?? '',
            'tel' => $userRecord['fields']['numero de tel'] ?? '', // CORRECTION : Ajout du téléphone à la session
            'photo_url' => $userRecord['fields']['PDP'][0]['url'] ?? null,
            'is_directeur' => $userRecord['fields']['Directeur'] ?? false,
            'demande_en_cours' => $userRecord['fields']['Demande en cours...'] ?? false,
            'is_admin' => $userRecord['fields']['Admin'] ?? false,
            'is_refused' => $userRecord['fields']['Refusé'] ?? false,
            'favorites' => $userRecord['fields']['Favories'] ?? [],
            'is_animateur' => $userRecord['fields']['Annimateur'] ?? false
        ];
        echo json_encode(['success' => true]);
    } else {
        http_response_code(401);
        echo json_encode(['error' => 'Email ou mot de passe incorrect.']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Une erreur fatale est survenue sur le serveur.', 'message' => $e->getMessage()]);
}
?>