<?php
// Fichier: /api/get_inscription_data.php
// Rassemble toutes les données nécessaires pour la page d'inscription en une seule fois.
session_start();
header('Content-Type: application/json');
require_once 'config.php';

// Sécurité de base
if (!isset($_SESSION['user']['id'])) { http_response_code(403); exit; }
$userId = $_SESSION['user']['id'];

$campId = $_GET['camp_id'] ?? '';
if (empty($campId)) { http_response_code(400); exit; }

try {
    // --- PARTIE 1 : RÉCUPÉRER LES DONNÉES DU CAMP ---
    $campRecord = callAirtable('GET', 'Camps', null, $campId);
    if (isset($campRecord['error'])) { throw new Exception('Camp introuvable.'); }
    
    $campFields = $campRecord['fields'];
    $campData = [
        'id' => $campRecord['id'],
        'nom' => $campFields['nom'] ?? 'N/A',
        'prix' => $campFields['Prix conseillé'] ?? 0,
        'remise' => $campFields['Remise si plusieurs enfants'] ?? 0,
        'age_min' => $campFields['Age min'] ?? 0,
        'age_max' => $campFields['Age max'] ?? 99,
        'inscrits' => $campFields['Inscrit'] ?? [],
        
        // --- MODIFICATION PRINCIPALE ---
        // On utilise directement les champs que vous avez créés dans Airtable.
        'quota_max_filles' => (int)($campFields['MAX FILLE'] ?? 0),
        'quota_max_garcons' => (int)($campFields['MAX GARCON'] ?? 0),
        'filles_inscrites' => (int)($campFields['Fille inscrit'] ?? 0), // Lecture directe
        'garcons_inscrits' => (int)($campFields['Garçon inscrit'] ?? 0)  // Lecture directe
    ];

    // --- PARTIE 2 : RÉCUPÉRER LES ENFANTS DE L'UTILISATEUR CONNECTÉ ---
    $userChildrenFormula = "{Parent_ID_Unique} = '{$userId}'";
    $userChildrenResult = callAirtable('GET', 'Enfants', ['filterByFormula' => $userChildrenFormula, 'fields' => ['Prénom', 'Date de naissance', 'Sexe']]);
    $userChildrenRaw = $userChildrenResult['records'] ?? [];
    $childrenData = [];
    foreach($userChildrenRaw as $child) {
        $childFields = $child['fields'];
        $dob = $childFields['Date de naissance'] ?? null;
        $age = $dob ? (new DateTime($dob))->diff(new DateTime('today'))->y : null;
        $childrenData[] = [
            'id' => $child['id'],
            'prenom' => $childFields['Prénom'] ?? 'N/A',
            'age' => $age,
            'sexe' => $childFields['Sexe'] ?? 'N/A'
        ];
    }

    // --- PARTIE 3 : CONSTRUIRE LA RÉPONSE FINALE ---
    $finalResponse = [
        'campData' => $campData,
        'childrenData' => $childrenData
    ];
    echo json_encode($finalResponse);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>