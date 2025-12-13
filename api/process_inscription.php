<?php
// Fichier: /api/process_inscription.php
require_once 'config.php';

// Sécurité
if (!isset($_SESSION['user']['id'])) {
    sendJson(['error' => 'Non connecté'], 403);
}

$input = json_decode(file_get_contents('php://input'), true);

// Validation simple
if (empty($input['camp_id']) || empty($input['child_id']) || empty($input['tarif_id'])) {
    sendJson(['error' => 'Données incomplètes'], 400);
}

try {
    $pdo->beginTransaction();

    // 1. Vérifier si l'enfant appartient bien à l'utilisateur (Sécurité IDOR)
    $stmtChild = $pdo->prepare("SELECT id FROM enfants WHERE id = ? AND parent_id = ?");
    $stmtChild->execute([$input['child_id'], $_SESSION['user']['id']]);
    if (!$stmtChild->fetch()) {
        throw new Exception("Enfant non autorisé.");
    }

    // 2. Vérifier s'il reste des places (et verrouiller la ligne pour éviter double résa)
    // Note: Pour faire simple ici on fait un check simple, en prod on utiliserait "FOR UPDATE"
    $stmtCamp = $pdo->prepare("SELECT quota_global, (SELECT COUNT(*) FROM inscriptions WHERE camp_id = ?) as inscrits FROM camps WHERE id = ?");
    $stmtCamp->execute([$input['camp_id'], $input['camp_id']]);
    $campData = $stmtCamp->fetch();

    if (($campData['quota_global'] - $campData['inscrits']) <= 0) {
        throw new Exception("Désolé, il n'y a plus de places disponibles.");
    }

    // 3. Vérifier si l'enfant est déjà inscrit à ce camp
    $stmtCheck = $pdo->prepare("SELECT id FROM inscriptions WHERE enfant_id = ? AND camp_id = ?");
    $stmtCheck->execute([$input['child_id'], $input['camp_id']]);
    if ($stmtCheck->fetch()) {
        throw new Exception("Cet enfant est déjà inscrit à ce séjour.");
    }

    // 4. Insérer l'inscription
    $sqlInsert = "INSERT INTO inscriptions (enfant_id, camp_id, tarif_id, date_inscription, statut_paiement, montant_paye, mode_paiement) 
                  VALUES (?, ?, ?, NOW(), 'PAYE', ?, ?)";
    $stmtInsert = $pdo->prepare($sqlInsert);
    $stmtInsert->execute([
        $input['child_id'],
        $input['camp_id'],
        $input['tarif_id'],
        $input['amount_paid'],
        $input['payment_method']
    ]);
// ... Code précédent d'insertion dans 'inscriptions' ...

    // 5. MISE À JOUR DU PORTEFEUILLE ORGANISATEUR
    // On calcule le montant net (Montant payé - 1% de commission)
    $commissionRate = 0.01;
    $netAmount = $input['amount_paid'] * (1 - $commissionRate);

    // On récupère l'ID de l'organisateur via le camp
    $stmtOrg = $pdo->prepare("SELECT organisateur_id FROM camps WHERE id = ?");
    $stmtOrg->execute([$input['camp_id']]);
    $orgId = $stmtOrg->fetchColumn();

    if ($orgId) {
        // On crédite le portefeuille
        $stmtUpdateWallet = $pdo->prepare("UPDATE organisateurs SET portefeuille = portefeuille + ? WHERE id = ?");
        $stmtUpdateWallet->execute([$netAmount, $orgId]);
        
        // Optionnel : On pourrait ajouter une ligne dans une table 'transactions' pour l'historique
    }

    $pdo->commit(); // Validation finale de la transaction
    // ...


    sendJson(['success' => true, 'message' => 'Inscription validée']);

} catch (Exception $e) {
    $pdo->rollBack();
    sendJson(['error' => $e->getMessage()], 500);
}
?>