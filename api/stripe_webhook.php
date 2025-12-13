<?php
// api/stripe_webhook.php

require_once 'config.php';

// IMPORTANT: Le fichier config.php doit contenir la constante STRIPE_WEBHOOK_SECRET

$payload = @file_get_contents('php://input');
$event = null;

try {
    // 1. Valider la signature de l'événement (Sécurité critique)
    $event = \Stripe\Webhook::constructEvent(
        $payload, 
        $_SERVER['HTTP_STRIPE_SIGNATURE'],
        STRIPE_WEBHOOK_SECRET
    );
} catch(\UnexpectedValueException $e) {
    http_response_code(400); // Signature invalide
    exit();
} catch(\Stripe\Exception\SignatureVerificationException $e) {
    http_response_code(400); // Signature invalide
    exit();
}

// 2. Traiter l'événement 'checkout.session.completed'
if ($event->type == 'checkout.session.completed') {
    $session = $event->data->object;
    
    // Récupérer les métadonnées pour identifier les inscriptions
    $reservationToken = $session->metadata->reservation_token;
    $totalAmountEur = $session->metadata->total_amount_eur;
    $organisateurId = $session->metadata->organisateur_id_local;
    $paymentIntentId = $session->payment_intent;

    if (empty($reservationToken) || empty($organisateurId) || empty($paymentIntentId)) {
        error_log("WEBHOOK ERROR: Métadonnées manquantes pour la session " . $session->id);
        http_response_code(400); 
        exit();
    }

    try {
        $pdo->beginTransaction();

        // 3. Valider TOUTES les inscriptions liées au token
        $sqlUpdateInscription = "
            UPDATE inscriptions 
            SET statut_paiement = 'PAYE', 
                stripe_payment_intent_id = ?
            WHERE reservation_token = ? AND statut_paiement = 'EN_ATTENTE'
        ";
        $stmtUpdateInscr = $pdo->prepare($sqlUpdateInscription);
        $stmtUpdateInscr->execute([$paymentIntentId, $reservationToken]);

        if ($stmtUpdateInscr->rowCount() > 0) {
            // 4. Créditer le portefeuille de l'organisateur (Montant total brut)
            $sqlCreditPortefeuille = "
                UPDATE organisateurs 
                SET portefeuille = portefeuille + ? 
                WHERE id = ?
            ";
            $stmtCreditPortefeuille = $pdo->prepare($sqlCreditPortefeuille);
            
            // Le montant total des inscriptions est crédité au portefeuille de l'organisme
            $stmtCreditPortefeuille->execute([$totalAmountEur, $organisateurId]);
        }
        
        $pdo->commit();

    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("Erreur Webhook BDD: " . $e->getMessage());
        http_response_code(500); // Demande à Stripe de réessayer
        exit();
    }
}

// Renvoyer une réponse HTTP 200 à Stripe
http_response_code(200);
?>