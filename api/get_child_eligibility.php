<?php
// Fichier: /api/get_child_eligibility.php
// Ce script compare les enfants d'un utilisateur à un camp spécifique
// et retourne leur statut d'éligibilité ainsi que les détails du camp.

session_start();
header('Content-Type: application/json');
require_once 'config.php';

// Sécurité de base
if (!isset($_SESSION['user']['id'])) { http_response_code(403); exit; }
$userId = $_SESSION['user']['id'];

$campId = $_GET['camp_id'] ?? '';
if (empty($campId)) { http_response_code(400); exit; }

try {
    // --- PARTIE 1 : RÉCUPÉRER TOUTES LES DONNÉES NÉCESSAIRES ---

    // 1a. Détails du camp (quotas, inscrits, âges, prix, etc.)
    $campRecord = callAirtable('GET', 'Camps', null, $campId);
    if (isset($campRecord['error'])) { throw new Exception('Camp introuvable.'); }
    $campFields = $campRecord['fields'];
    
    // 1b. Les enfants de l'utilisateur connecté (avec leur sexe)
    $userChildrenFormula = "{Parent_ID_Unique} = '{$userId}'";
    $userChildrenResult = callAirtable('GET', 'Enfants', ['filterByFormula' => $userChildrenFormula, 'fields' => ['Prénom', 'Date de naissance', 'Sexe']]);
    $userChildren = $userChildrenResult['records'] ?? [];

    // On utilise les comptes déjà faits par Airtable (Fille inscrit, Garçon inscrit)
    $filles_inscrites = (int)($campFields['Fille inscrit'] ?? 0);
    $garcons_inscrits = (int)($campFields['Garçon inscrit'] ?? 0);
    $registeredChildIds = $campFields['Inscrit'] ?? [];

    // --- PARTIE 2 : COMPARER CHAQUE ENFANT DE L'UTILISATEUR ET CONSTRUIRE LA RÉPONSE ---

    $eligibilityData = [];
    foreach($userChildren as $child) {
        $childFields = $child['fields'];
        $childAge = isset($childFields['Date de naissance']) ? (new DateTime($childFields['Date de naissance']))->diff(new DateTime('today'))->y : null;
        
        $isSelectable = true;
        $reason = '';

        if (in_array($child['id'], $registeredChildIds)) {
            $isSelectable = false;
            $reason = 'Déjà inscrit';
        } elseif ($childAge < ($campFields['Age min'] ?? 0) || $childAge > ($campFields['Age max'] ?? 99)) {
            $isSelectable = false;
            $reason = 'Âge non compatible';
        } else {
            $quotaFilles = (int)($campFields['MAX FILLE'] ?? 0);
            $quotaGarcons = (int)($campFields['MAX GARCON'] ?? 0);
            
            if (($childFields['Sexe'] ?? '') === 'Femme' && $quotaFilles > 0 && $filles_inscrites >= $quotaFilles) {
                $isSelectable = false;
                $reason = 'Plus de place pour les filles';
            } elseif (($childFields['Sexe'] ?? '') === 'Homme' && $quotaGarcons > 0 && $garcons_inscrits >= $quotaGarcons) {
                $isSelectable = false;
                $reason = 'Plus de place pour les garçons';
            }
        }
        
        $eligibilityData[] = [
            'id' => $child['id'],
            'prenom' => $childFields['Prénom'] ?? 'N/A',
            'age' => $childAge,
            'sexe' => $childFields['Sexe'] ?? 'N/A',
            'isSelectable' => $isSelectable,
            'reason' => $reason
        ];
    }
    
    // On construit la réponse finale qui contient tout ce dont la page a besoin
    $finalResponse = [
        'campData' => [
            'nom' => $campFields['nom'] ?? 'Camp sans nom',
            'prix' => $campFields['Prix conseillé'] ?? 0,
            'remise' => $campFields['Remise si plusieurs enfants'] ?? 0
        ],
        'childrenEligibility' => $eligibilityData
    ];

    echo json_encode($finalResponse);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>