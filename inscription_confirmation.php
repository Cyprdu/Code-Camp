<?php
// inscription_confirmation.php

require_once 'api/config.php';

// Sécurité : L'utilisateur doit être connecté pour voir ses inscriptions
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$token = filter_input(INPUT_GET, 'token', FILTER_SANITIZE_STRING);
$status = filter_input(INPUT_GET, 'status', FILTER_SANITIZE_STRING); // 'success' ou 'cancel' (donné par Stripe)

$userId = $_SESSION['user']['id'];
$inscriptions = [];
$statut_global = 'EN_ATTENTE'; // Statut initial
$message = "Veuillez patienter, nous vérifions le statut de votre paiement...";
$animation_file = '';

if (!$token) {
    // Si pas de token, on ne peut pas afficher les détails
    $statut_global = 'ERREUR';
    $message = "Token de réservation manquant. Impossible de vérifier le statut.";
} else {
    try {
        // 1. Récupérer toutes les inscriptions liées à ce token et à cet utilisateur
        $sql = "
            SELECT i.*, c.nom as camp_nom, e.prenom as enfant_prenom 
            FROM inscriptions i
            JOIN camps c ON i.camp_id = c.id
            JOIN enfants e ON i.enfant_id = e.id
            WHERE i.reservation_token = ? AND e.user_id = ?
            ORDER BY i.id ASC
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$token, $userId]);
        $inscriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($inscriptions)) {
            $statut_global = 'ERREUR';
            $message = "Réservation introuvable ou non autorisée.";
        } else {
            // 2. Déterminer le statut réel de la réservation
            
            // Le statut réel est donné par la BDD suite au Webhook (PAYE, EN_ATTENTE)
            $db_statut = $inscriptions[0]['statut_paiement']; 

            if ($db_statut === 'PAYE') {
                // Paiement confirmé par le webhook
                $statut_global = 'SUCCES';
                $message = "Félicitations ! Votre paiement a été confirmé. Les inscriptions sont validées.";
                $animation_file = 'Animation - 1720788531370.json';

            } elseif ($db_statut === 'EN_ATTENTE') {
                // Le paiement est en cours de vérification par Stripe (Webhook pas encore reçu)
                $statut_global = 'PENDING';
                
                if ($status === 'success') {
                    $message = "Paiement en cours de confirmation. Stripe vérifie la transaction, ce qui peut prendre quelques secondes. Veuillez ne pas quitter cette page.";
                } else {
                    $message = "Le statut est en attente. Si vous pensez avoir payé, veuillez ne pas vous réinscrire; le système mettra à jour le statut sous peu. Si vous n'avez pas payé, la réservation sera annulée automatiquement.";
                }
                $animation_file = 'loading_check.json'; // Animation d'attente

            } else {
                // Statut inconnu ou ANNUlÉ (par défaut)
                $statut_global = 'ECHEC';
                $message = "Le paiement a échoué ou a été annulé (Code: {$db_statut}). Veuillez réessayer l'inscription.";
                $animation_file = 'fail.json';
            }
        }

    } catch (Exception $e) {
        $statut_global = 'ERREUR';
        $message = "Erreur interne lors de la vérification : " . $e->getMessage();
    }
}

// Définition des couleurs/icônes pour l'affichage
$colors = [
    'SUCCES' => ['bg' => 'bg-green-50', 'text' => 'text-green-800', 'border' => 'border-green-600'],
    'ECHEC' => ['bg' => 'bg-red-50', 'text' => 'text-red-800', 'border' => 'border-red-600'],
    'PENDING' => ['bg' => 'bg-yellow-50', 'text' => 'text-yellow-800', 'border' => 'border-yellow-600'],
    'ERREUR' => ['bg' => 'bg-red-50', 'text' => 'text-red-800', 'border' => 'border-red-600'],
];

$current_color = $colors[$statut_global];

require_once 'partials/header.php';
?>

<title>Statut de la Réservation</title>

<script src="https://unpkg.com/@lottiefiles/lottie-player@latest/dist/lottie-player.js"></script>

<div class="min-h-screen bg-gray-50 py-12 font-sans">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <div class="<?= $current_color['bg'] ?> <?= $current_color['border'] ?> p-8 rounded-xl shadow-lg border-t-4 mb-8 text-center">
            <h1 class="text-3xl font-extrabold <?= $current_color['text'] ?> mb-4">
                <?php 
                    if ($statut_global === 'SUCCES') echo "Paiement et Inscription Validés !";
                    elseif ($statut_global === 'ECHEC') echo "Paiement Échoué ou Annulé.";
                    elseif ($statut_global === 'PENDING') echo "Statut en Cours de Vérification.";
                    else echo "Problème Technique.";
                ?>
            </h1>
            <p class="text-lg <?= $current_color['text'] ?>"><?= $message ?></p>
        </div>

        <div class="mb-8 flex justify-center">
            <lottie-player 
                src="assets/lotties/<?= htmlspecialchars($animation_file) ?>" 
                background="transparent" 
                speed="1" 
                style="width: 200px; height: 200px;" 
                loop 
                autoplay>
            </lottie-player>
            <?php  ?> 
        </div>

        <?php if (!empty($inscriptions) && $statut_global !== 'ERREUR'): ?>
            <div class="bg-white rounded-xl shadow-md p-6 border border-gray-200">
                <h2 class="text-xl font-bold text-gray-800 mb-4 border-b pb-2">Résumé des Réservations</h2>
                
                <p class="text-sm text-gray-500 mb-4">
                    Toutes les inscriptions liées à cette transaction ont été traitées.
                </p>

                <div class="space-y-4">
                    <?php foreach ($inscriptions as $insc): ?>
                        <div class="flex justify-between items-center p-3 border rounded-lg <?= ($insc['statut_paiement'] === 'PAYE') ? 'bg-green-50' : 'bg-gray-50' ?>">
                            <div class="font-semibold text-gray-900">
                                <?= htmlspecialchars($insc['enfant_prenom']) ?> - <?= htmlspecialchars($insc['camp_nom']) ?>
                            </div>
                            <div class="text-right">
                                <span class="text-lg font-bold <?= ($insc['statut_paiement'] === 'PAYE') ? 'text-green-700' : 'text-gray-700' ?>">
                                    <?= number_format($insc['prix_final'], 2, ',', ' ') ?>€
                                </span>
                                <p class="text-xs text-gray-500">
                                    Statut: 
                                    <span class="font-bold">
                                    <?= ($insc['statut_paiement'] === 'PAYE') ? 'PAYÉ' : $insc['statut_paiement'] ?>
                                    </span>
                                </p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <div class="mt-8 text-center">
            <?php if ($statut_global === 'SUCCES'): ?>
                <a href="mes_camps.php" class="inline-block bg-[#0A112F] text-white font-bold py-3 px-8 rounded-xl hover:bg-blue-900 transition shadow-lg">
                    Accéder à Mes Camps
                </a>
            <?php elseif ($statut_global === 'ECHEC'): ?>
                <a href="inscription.php?t=<?= urlencode($inscriptions[0]['camp_token'] ?? $token) ?>" class="inline-block bg-red-600 text-white font-bold py-3 px-8 rounded-xl hover:bg-red-700 transition shadow-lg">
                    Réessayer l'Inscription
                </a>
            <?php endif; ?>
        </div>

    </div>
</div>

<?php require_once 'partials/footer.php'; ?>