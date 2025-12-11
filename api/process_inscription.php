<?php
// Fichier: /api/process_inscription.php
// Gère la validation finale ET l'incrémentation des compteurs de genre.

session_start();
header('Content-Type: application/json');
require_once 'config.php';

// --- Sécurité et validation des entrées ---
if (!isset($_SESSION['user']['id'])) { http_response_code(403); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit; }

$input = json_decode(file_get_contents('php://input'), true);
$campId = $input['campId'] ?? null;
$childIds = $input['childIds'] ?? [];
$finalPrice = (float)($input['finalPrice'] ?? 0);

if (empty($campId) || empty($childIds)) {
    http_response_code(400);
    echo json_encode(['error' => 'Données de réservation invalides.']);
    exit;
}

try {
    // --- NOUVELLE LOGIQUE : COMPTER LES GENRES DES NOUVEAUX INSCRITS ---
    $newly_registered_girls = 0;
    $newly_registered_boys = 0;

    // On crée une formule pour récupérer le sexe de tous les enfants à inscrire
    $childFormulaParts = [];
    foreach ($childIds as $childId) {
        $childFormulaParts[] = "RECORD_ID() = '{$childId}'";
    }
    $childFormula = 'OR(' . implode(', ', $childFormulaParts) . ')';

    // On appelle l'API pour avoir les détails des enfants
    $childrenRecords = callAirtable('GET', 'Enfants', ['filterByFormula' => $childFormula, 'fields' => ['Sexe']]);
    
    if (isset($childrenRecords['records'])) {
        foreach ($childrenRecords['records'] as $child) {
            if (isset($child['fields']['Sexe'])) {
                if ($child['fields']['Sexe'] === 'Femme') {
                    $newly_registered_girls++;
                } elseif ($child['fields']['Sexe'] === 'Homme') {
                    $newly_registered_boys++;
                }
            }
        }
    }

    // --- MISE À JOUR DES DONNÉES ---
    // 1. On récupère les infos actuelles du camp (compteurs et liste d'inscrits)
    $campRecord = callAirtable('GET', 'Camps', null, $campId);
    if (isset($campRecord['error'])) throw new Exception("Camp introuvable.");

    $currentInscrits = $campRecord['fields']['Inscrit'] ?? [];
    $currentGirls = (int)($campRecord['fields']['Fille inscrit'] ?? 0);
    $currentBoys = (int)($campRecord['fields']['Garçon inscrit'] ?? 0);

    // 2. On prépare le paquet de données à mettre à jour
    $updateCampData = [
        'fields' => [
            // Ajoute les nouveaux IDs à la liste existante
            'Inscrit' => array_unique(array_merge($currentInscrits, $childIds)),
            // Met à jour les compteurs
            'Fille inscrit' => $currentGirls + $newly_registered_girls,
            'Garçon inscrit' => $currentBoys + $newly_registered_boys
        ]
    ];
    
    // 3. On met à jour le camp avec la nouvelle liste d'inscrits ET les nouveaux compteurs
    callAirtable('PATCH', 'Camps', $updateCampData, $campId);

    // 4. Mettre à jour la fiche de chaque enfant (ne change pas)
    foreach ($childIds as $childId) {
        $childRecord = callAirtable('GET', 'Enfants', null, $childId);
        if (isset($childRecord['error'])) continue;
        $currentCamps = $childRecord['fields']['Inscrit au camp'] ?? [];
        $currentCamps[] = $campId;
        callAirtable('PATCH', 'Enfants', ['fields' => ['Inscrit au camp' => array_unique($currentCamps)]], $childId);
    }
    
    // Le reste de la logique (portefeuille, commission) reste identique
    $organisateurId = $campRecord['fields']['Organisme'][0] ?? null;
    if ($organisateurId) {
        $organisateurRecord = callAirtable('GET', 'Organisateur', null, $organisateurId);
        if (!isset($organisateurRecord['error'])) {
            $commissionRate = 0.03;
            $commissionAmount = $finalPrice * $commissionRate;
            $organizerEarnings = $finalPrice - $commissionAmount;
            $currentWallet = (float)($organisateurRecord['fields']['Portefeuille'] ?? 0);
            $newWallet = $currentWallet + $organizerEarnings;
            callAirtable('PATCH', 'Organisateur', ['fields' => ['Portefeuille' => $newWallet]], $organisateurId);
        }
    }

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>