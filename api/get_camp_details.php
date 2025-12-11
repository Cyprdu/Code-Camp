<?php
// Fichier: /api/get_camp_details.php (mis à jour)
session_start();
header('Content-Type: application/json');
require_once 'config.php';

if (!isset($_GET['id']) || empty($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'ID de camp manquant.']);
    exit;
}
$campId = $_GET['id'];

try {
    $campRecord = callAirtable('GET', 'Camps', null, $campId);
    if (isset($campRecord['error'])) {
        http_response_code(404);
        echo json_encode(['error' => 'Camp introuvable.']);
        exit;
    }
    $fields = $campRecord['fields'];

    $currentViews = $fields['Vues'] ?? 0;
    try {
        callAirtable('PATCH', 'Camps', ['fields' => ['Vues' => ($currentViews + 1)]], $campId);
    } catch (Exception $e) { /* On ignore */ }

    $campDetails = [
        'id' => $campRecord['id'],
        'nom' => $fields['nom'] ?? 'N/A',
        'description' => !empty($fields['Déscription']) ? nl2br(htmlspecialchars($fields['Déscription'])) : '',
        'ville' => $fields['Ville ou se déroule le camp'] ?? 'N/A',
        'prix' => $fields['Prix conseillé'] ?? 0,
        'age_min' => $fields['Age min'] ?? 0,
        'age_max' => $fields['Age max'] ?? 0,
        'date_debut' => $fields['Date début du camp'] ?? null,
        'date_fin' => $fields['Date fin du camp'] ?? null,
        'image_url' => !empty($fields['illustration']) ? $fields['illustration'][0]['url'] : 'https://placehold.co/1200x600',
        'organisateur_id' => !empty($fields['Organisme']) ? $fields['Organisme'][0] : null,
        
        // --- NOUVELLES DONNÉES POUR LES ANIMATEURS ---
        'quota_max_anim' => $fields['quota max anim'] ?? null,
        'bafa_obligatoire' => $fields['BAFA ANIM'] ?? false,
        'anim_majeur' => $fields['anim +18'] ?? false,
        'remuneration_anim' => $fields['rémunération anim'] ?? false,
        'paiement_anim' => $fields['paiement anim'] ?? false,
        'prix_anim' => $fields['prix anim'] ?? 0
    ];

    echo json_encode($campDetails);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>