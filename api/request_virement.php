<?php
// api/request_virement.php

// 1. CONFIG & SÉCURITÉ
require_once '../api/config.php';

// Le script doit être accessible uniquement aux directeurs via POST
if (!isset($_SESSION['user']) || empty($_SESSION['user']['is_directeur']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(403);
    header('Location: ../index.php');
    exit;
}

$organisateurId = filter_input(INPUT_POST, 'organisateur_id', FILTER_VALIDATE_INT);
$userId = $_SESSION['user']['id'];

// PARAMÈTRES DE LA COMMISSION
$COMISSION_RATE = 1.00; // 1.00%
$MIN_AMOUNT = 10.00; // Montant minimum pour le virement

if (!$organisateurId) {
    header('Location: ../public_infos.php?error=ID organisme manquant.');
    exit;
}

try {
    // Début de la transaction pour garantir l'atomicité
    $pdo->beginTransaction();

    // A. Récupérer les données de l'organisateur (vérifie l'appartenance)
    $stmtOrga = $pdo->prepare("SELECT * FROM organisateurs WHERE id = ? AND user_id = ?");
    $stmtOrga->execute([$organisateurId, $userId]);
    $organisateur = $stmtOrga->fetch(PDO::FETCH_ASSOC);

    // B. Récupérer les données de l'utilisateur (directeur)
    $stmtUser = $pdo->prepare("SELECT nom, prenom, email FROM users WHERE id = ?");
    $stmtUser->execute([$userId]);
    $user = $stmtUser->fetch(PDO::FETCH_ASSOC);

    if (!$organisateur || !$user) {
        $pdo->rollBack();
        header('Location: ../public_infos.php?error=Organisme ou utilisateur introuvable.');
        exit;
    }
    
    $montantTotal = floatval($organisateur['portefeuille']);

    // C. Validation des conditions de virement
    if ($montantTotal < $MIN_AMOUNT) {
        $pdo->rollBack();
        header('Location: ../dashboard_organisme.php?organisateur_id=' . $organisateurId . '&error=Montant insuffisant pour un virement (min ' . $MIN_AMOUNT . '€).');
        exit;
    }
    
    if (empty($organisateur['iban']) || empty($organisateur['bic_swift'])) {
        $pdo->rollBack();
        header('Location: ../dashboard_organisme.php?organisateur_id=' . $organisateurId . '&error=IBAN ou BIC/SWIFT manquant. Veuillez mettre à jour vos informations bancaires.');
        exit;
    }

    // D. Calcul de la commission
    $commission = round($montantTotal * ($COMISSION_RATE / 100), 2);
    $montantApresCommission = $montantTotal - $commission;
    
    // E. Création du Token unique (60 caractères)
    $token = bin2hex(random_bytes(30));

    // F. Insertion dans la table virements
    $insertSql = "INSERT INTO virements (
                    token, organisateur_id, user_id, montant_total, commission_rate, montant_apres_commission, 
                    nom_organisme, iban, bic_swift, email_organisme, tel_organisme, 
                    nom_user, prenom_user, email_user
                  ) VALUES (
                    ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
                  )";
    
    $stmtInsert = $pdo->prepare($insertSql);
    $stmtInsert->execute([
        $token, 
        $organisateurId, 
        $userId, 
        $montantTotal, 
        $COMISSION_RATE, 
        $montantApresCommission,
        $organisateur['nom'], 
        $organisateur['iban'], 
        $organisateur['bic_swift'], 
        $organisateur['email'], 
        $organisateur['tel'],
        $user['nom'], 
        $user['prenom'], 
        $user['email']
    ]);

    // G. Mise à jour du portefeuille à 0.00
    $updatePortefeuilleSql = "UPDATE organisateurs SET portefeuille = 0.00 WHERE id = ?";
    $stmtUpdate = $pdo->prepare($updatePortefeuilleSql);
    $stmtUpdate->execute([$organisateurId]);

    $pdo->commit();
    
    // H. Redirection vers la page de confirmation
    header('Location: ../virement.php?t=' . $token);
    exit;

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Erreur de virement: " . $e->getMessage());
    header('Location: ../dashboard_organisme.php?organisateur_id=' . $organisateurId . '&error=Erreur interne lors de la demande de virement.');
    exit;
}
?>