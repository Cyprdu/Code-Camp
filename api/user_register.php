<?php
// Fichier: /api/user_register.php (modifié)
header('Content-Type: application/json');
require_once 'config.php';

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['error' => 'Méthode non autorisée.']);
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true);
    $mail = $input['mail'] ?? null;
    $password = $input['password'] ?? null;

    // Vérification de l'existence de l'email
    $tableName = 'User';
    $formula = "LOWER({mail}) = '" . strtolower(addslashes($mail)) . "'";
    $existingUser = callAirtable('GET', $tableName, ['filterByFormula' => $formula]);
    if (!empty($existingUser['records'])) {
        http_response_code(409);
        echo json_encode(['error' => 'Un compte avec cette adresse email existe déjà.']);
        exit;
    }
    
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $isAnimator = ($input['role'] ?? 'parent') === 'animateur';

    $data = [
        'fields' => [
            'nom' => $input['nom'],
            'prenom' => $input['prenom'],
            'mail' => $mail,
            'Naissance' => $input['naissance'],
            'Sexe' => $input['sexe'],
            'Mot de passe aché' => $hashed_password,
            'Annimateur' => $isAnimator,
            'BAFA' => $input['bafa'] ?? false
        ]
    ];
    
    $createResult = callAirtable('POST', $tableName, $data);

    if (isset($createResult['error'])) {
        throw new Exception($createResult['response']['error']['message'] ?? 'Erreur lors de la création du compte.');
    } else {
        http_response_code(201);
        echo json_encode(['success' => 'Compte créé avec succès !']);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>