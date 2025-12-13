<?php
// Fichier: /api/get_camp_details.php
require_once 'config.php';

$id = $_GET['id'] ?? 0;

try {
    // AJOUT : on sélectionne le "token"
    $sql = "SELECT c.*, o.nom as organisateur_nom, o.email as organisateur_email, o.tel as organisateur_tel 
            FROM camps c 
            LEFT JOIN organisateurs o ON c.organisateur_id = o.id 
            WHERE c.id = ?";
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id]);
    $camp = $stmt->fetch();

    if (!$camp) {
        sendJson(['error' => 'Camp introuvable'], 404);
    }

    // Calcul des places restantes (inchangé)
    $stmtQuota = $pdo->prepare("SELECT COUNT(*) FROM inscriptions WHERE camp_id = ?");
    $stmtQuota->execute([$id]);
    $inscrits = $stmtQuota->fetchColumn();
    $places_restantes = $camp['quota_global'] - $inscrits;

    // Comptage des vues (inchangé)
    $pdo->prepare("UPDATE camps SET vues = vues + 1 WHERE id = ?")->execute([$id]);

    // Likes (inchangé)
    $stmtLikes = $pdo->prepare("SELECT COUNT(*) FROM favoris WHERE camp_id = ?");
    $stmtLikes->execute([$id]);
    $likes = $stmtLikes->fetchColumn();

    $response = [
        'id' => $camp['id'],
        'token' => $camp['token'], // IMPORTANT : On renvoie le token
        'nom' => $camp['nom'],
        'description' => nl2br(htmlspecialchars($camp['description'])),
        'ville' => $camp['ville'],
        'adresse' => $camp['adresse'],
        'prix' => $camp['prix'],
        'image_url' => $camp['image_url'],
        'date_debut' => $camp['date_debut'],
        'date_fin' => $camp['date_fin'],
        'age_min' => $camp['age_min'],
        'age_max' => $camp['age_max'],
        'places_restantes' => max(0, $places_restantes),
        'inscription_en_ligne' => $camp['inscription_en_ligne'],
        'inscription_hors_ligne' => $camp['inscription_hors_ligne'],
        'lien_externe' => $camp['lien_externe'],
        'adresse_retour' => nl2br(htmlspecialchars($camp['adresse_retour_dossier'] ?? '')),
        'vues' => $camp['vues'],
        'likes' => $likes,
        'organisateur_id' => $camp['organisateur_id'],
        'prive' => $camp['prive']
    ];

    sendJson($response);

} catch (Exception $e) {
    sendJson(['error' => $e->getMessage()], 500);
}
?>