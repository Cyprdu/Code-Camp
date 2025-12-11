<?php
// Fichier: /api/get_my_applications.php
session_start();
header('Content-Type: application/json');
require_once 'config.php';

// Sécurité : l'utilisateur doit être un animateur connecté.
if (!isset($_SESSION['user']['id']) || !($_SESSION['user']['is_animateur'] ?? false)) {
    http_response_code(403);
    echo json_encode(['error' => 'Accès non autorisé.']);
    exit;
}

try {
    $userId = $_SESSION['user']['id'];
    
    // 1. Récupérer l'enregistrement de l'utilisateur pour obtenir les IDs de ses candidatures
    $userRecord = callAirtable('GET', 'User', null, $userId);
    if (isset($userRecord['error'])) {
        throw new Exception("Erreur lors de la récupération de l'utilisateur.");
    }

    $applicationIds = $userRecord['fields']['Candidatures'] ?? [];

    if (empty($applicationIds)) {
        echo json_encode(['pending' => [], 'accepted' => []]);
        exit;
    }
    
    // 2. Récupérer les détails de toutes les candidatures de l'utilisateur en une seule fois
    $formulaParts = [];
    foreach ($applicationIds as $appId) {
        $formulaParts[] = "RECORD_ID() = '{$appId}'";
    }
    $formula = 'OR(' . implode(', ', $formulaParts) . ')';
    
    $applicationsResult = callAirtable('GET', 'Candidatures', ['filterByFormula' => $formula]);
    if (isset($applicationsResult['error'])) {
        throw new Exception("Erreur lors de la récupération de vos candidatures.");
    }

    $pending = [];
    $accepted = [];

    foreach ($applicationsResult['records'] as $application) {
        $appFields = $application['fields'];
        $campId = $appFields['Camp'][0] ?? null;

        if (!$campId) continue;

        // 3. Pour chaque candidature, récupérer les détails du camp associé
        $campRecord = callAirtable('GET', 'Camps', null, $campId);
        if (isset($campRecord['error'])) continue;
        $campFields = $campRecord['fields'];

        // 4. Récupérer les détails de l'organisateur du camp
        $organisateurId = $campFields['Organisme'][0] ?? null;
        $organisateurDetails = ['nom' => 'N/A', 'mail' => 'N/A'];
        if ($organisateurId) {
            $orgRecord = callAirtable('GET', 'Organisateur', null, $organisateurId);
            if (!isset($orgRecord['error'])) {
                $organisateurDetails['nom'] = $orgRecord['fields']["Nom de l'organisme"] ?? 'N/A';
                $organisateurDetails['mail'] = $orgRecord['fields']['Mail'] ?? 'N/A';
            }
        }
        
        $data = [
            'camp_nom' => $campFields['nom'] ?? 'N/A',
            'camp_ville' => $campFields['Ville ou se déroule le camp'] ?? 'N/A',
            'camp_image_url' => $campFields['illustration'][0]['url'] ?? 'https://placehold.co/600x400',
            'inscrits_enfants' => count($campFields['Inscrit'] ?? []),
            'inscrits_animateurs' => count($campFields['Annimateur'] ?? []),
            'organisateur_nom' => $organisateurDetails['nom'],
            'organisateur_mail' => $organisateurDetails['mail'],
            'statut' => $appFields['Statut'] ?? 'En attente'
        ];
        
        // 5. Trier la candidature dans la bonne catégorie
        if ($data['statut'] === 'Accepté') {
            $accepted[] = $data;
        } else {
            $pending[] = $data;
        }
    }

    echo json_encode(['pending' => $pending, 'accepted' => $accepted]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>