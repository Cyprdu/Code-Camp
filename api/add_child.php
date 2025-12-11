<?php
// Fichier: /api/add_child.php

session_start();
header('Content-Type: application/json');
require_once 'config.php';

// SÉCURITÉ : L'utilisateur doit être connecté.
if (!isset($_SESSION['user']['id'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Accès non autorisé. Vous devez être connecté.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Méthode non autorisée.']);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true);

    // Validation des champs obligatoires
    $required_fields = [
        'prenom_enfant', 'nom_enfant', 'date_naissance', 
        'prenom_parent1', 'nom_parent1', 'mail_parent1', 'num_parent1'
    ];
    foreach ($required_fields as $field) {
        if (empty($input[$field])) {
            http_response_code(400);
            echo json_encode(['error' => "Le champ '$field' est obligatoire."]);
            exit;
        }
    }
    
    // Mapping des champs du formulaire vers les noms des colonnes Airtable
    $data = [
        'fields' => [
            'Prénom' => $input['prenom_enfant'],
            'Nom' => $input['nom_enfant'],
            'Date de naissance' => $input['date_naissance'],
            'Sexe' => $input['sexe'] ?? null,
            'num enfant' => $input['num_enfant'] ?? null,
            'Alèrgie' => $input['alergie'] ?? 'Aucune',
            // CORRIGÉ : Le nom du champ a été changé pour correspondre à la base de données.
            'prenom parent 1' => $input['prenom_parent1'],
            'nom parent 1' => $input['nom_parent1'],
            'mail parent 1' => $input['mail_parent1'],
            'num parent 1' => $input['num_parent1'],
            'prenom parent 2' => $input['prenom_parent2'] ?? null,
            'nom parent 2' => $input['nom_parent2'] ?? null,
            'mail parent 2' => $input['mail_parent2'] ?? null,
            'num parent 2' => $input['num_parent2'] ?? null,
            'Parent' => [$_SESSION['user']['id']] // Lie l'enfant au parent connecté
        ]
    ];
    
    // Note: La gestion de l'upload du fichier "copie carnet de santé" n'est pas incluse.
    // L'API Airtable attend une URL pour les pièces jointes.
    // Cela nécessite une étape supplémentaire :
    // 1. Uploader le fichier sur votre propre serveur (ou un service comme S3).
    // 2. Obtenir l'URL publique de ce fichier.
    // 3. Ajouter cette URL au tableau $data['fields']['copie carnet de santé'].

    $result = callAirtable('POST', 'Enfants', $data);

    if (isset($result['error'])) {
        throw new Exception($result['response']['error']['message'] ?? "Erreur lors de l'ajout de la fiche enfant.");
    }

    http_response_code(201); // Created
    echo json_encode(['success' => 'La fiche de l\'enfant a été enregistrée avec succès !']);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
