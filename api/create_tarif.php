<?php
session_start();
header('Content-Type: application/json');
require_once 'config.php';

if (!isset($_SESSION['user']['id']) || !$_SESSION['user']['is_directeur']) {
    http_response_code(403);
    echo json_encode(['error' => 'Accès non autorisé.']);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Méthode non autorisée.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$nom = $input['nom'] ?? null;
$prix = $input['prix'] ?? null;
$organisateurId = $input['organisateur_id'] ?? null;
$montantLibre = $input['montant_libre'] ?? false;

if (!$nom || !is_numeric($prix) || !$organisateurId) {
    http_response_code(400);
    echo json_encode(['error' => 'Les champs nom, prix et organisateur sont obligatoires.']);
    exit;
}

try {
    $data = [
        'fields' => [
            'Nom du tarif' => $nom,
            'Prix' => (float)$prix,
            'Lien' => [$organisateurId],
            'Montant Libre' => $montantLibre
        ]
    ];
    $result = callAirtable('POST', 'Tarif', $data);

    if (isset($result['error'])) {
        $errorMessage = $result['response']['error']['message'] ?? 'Erreur inconnue lors de la communication avec Airtable.';
        throw new Exception($errorMessage);
    }
    
    http_response_code(201);
    echo json_encode($result);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>