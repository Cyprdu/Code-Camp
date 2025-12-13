<?php
// demande_de_virement_info.php

// 1. CONFIG
require_once 'api/config.php';

// 2. SÉCURITÉ
if (!isset($_SESSION['user']) || empty($_SESSION['user']['is_directeur'])) {
    header('Location: index.php');
    exit;
}

// PARAMÈTRES DE LA COMMISSION
$COMISSION_RATE = 1.00; // 1.00%
$MIN_AMOUNT = 10.00; // Montant minimum pour le virement (pour validation)

// 3. LOGIQUE - Récupération et Calcul
$organisateurId = filter_input(INPUT_GET, 'organisateur_id', FILTER_VALIDATE_INT);
$userId = $_SESSION['user']['id'];
$organisateur = null;
$error = null;
$montantTotal = 0.00;
$montantTotalDemandé = 0.00;
$montantApresCommission = 0.00;
$commission = 0.00;
$dateDemande = new DateTime();
// Calcul de la date estimée (J+3 ouvrés)
$dateVirementEstime = (new DateTime())->modify('+3 weekdays')->format('Y-m-d H:i:s');

if (!$organisateurId) {
    $error = "ID d'organisme manquant.";
} else {
    try {
        // A. Récupérer les données de l'organisateur
        $stmtOrga = $pdo->prepare("SELECT * FROM organisateurs WHERE id = ? AND user_id = ?");
        $stmtOrga->execute([$organisateurId, $userId]);
        $organisateur = $stmtOrga->fetch(PDO::FETCH_ASSOC);

        if (!$organisateur) {
            $error = "Organisme introuvable ou vous n'êtes pas autorisé à y accéder.";
        } else {
            $montantTotal = floatval($organisateur['portefeuille']);
            
            if ($montantTotal < $MIN_AMOUNT) {
                $error = "Montant de portefeuille insuffisant pour un virement (minimum requis : " . number_format($MIN_AMOUNT, 2, ',', ' ') . "€).";
            } else {
                // Par défaut, le montant demandé est le montant total disponible
                $montantTotalDemandé = $montantTotal;
                
                // Calcul de la commission et du montant net sur le montant MAXIMAL disponible
                $commission = round($montantTotalDemandé * ($COMISSION_RATE / 100), 2);
                $montantApresCommission = $montantTotalDemandé - $commission;
            }
        }

    } catch (Exception $e) {
        $error = "Erreur SQL : " . $e->getMessage();
    }
}

// 4. AFFICHAGE HTML
require_once 'partials/header.php';
?>

<title>Demande de Virement - <?= htmlspecialchars($organisateur['nom'] ?? 'Organisme') ?></title>

<div class="min-h-screen bg-gray-50 py-10 font-sans">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <div class="flex items-center gap-4 mb-8">
            <a href="dashboard_organisme.php?organisateur_id=<?= $organisateurId ?>" class="text-gray-500 hover:text-[#0A112F] transition">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            </a>
            <h1 class="text-3xl font-extrabold text-[#0A112F]">Demande de Virement</h1>
        </div>

        <?php if ($error || !$organisateur): ?>
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded shadow-sm">
                <p class="font-bold">Erreur</p>
                <p><?= $error ?? "Erreur de chargement de l'organisme." ?></p>
            </div>
            <a href="public_infos.php" class="text-[#0A112F] font-bold hover:underline block mt-4">Retour à la sélection d'organisme</a>
        <?php else: ?>

            <div class="bg-white rounded-xl shadow-lg p-8 border border-gray-100">
                
                <div class="flex items-center space-x-3 text-gray-700 mb-6 border-b pb-4">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <p class="text-lg font-medium">
                        Vérification finale pour **<?= htmlspecialchars($organisateur['nom']) ?>**
                    </p>
                </div>

                <form action="api/process_virement.php" method="POST">
                    <input type="hidden" name="organisateur_id" value="<?= $organisateurId ?>">
                    <input type="hidden" name="date_virement_estime" value="<?= $dateVirementEstime ?>">

                    <div class="mb-8 p-6 bg-gray-50 rounded-lg border border-gray-200">
                        <h2 class="flex items-center gap-2 text-xl font-bold text-[#0A112F] mb-4">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2v4c0 1.105 1.343 2 3 2s3-.895 3-2v-4c0-1.105-1.343-2-3-2z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 17V5m0 16a9 9 0 100-18 9 9 0 000 18z"/></svg>
                            Montant et Calendrier
                        </h2>
                        
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                            
                            <div>
                                <label for="montant_total_demande" class="block text-sm font-medium text-gray-700">Montant BRUT à virer</label>
                                <div class="relative mt-1 rounded-md shadow-sm">
                                    <input type="number" step="0.01" min="0.01" max="<?= number_format($montantTotal, 2, '.', '') ?>" 
                                        name="montant_total_demande" id="montant_total_demande" 
                                        value="<?= number_format($montantTotalDemandé, 2, '.', '') ?>" required 
                                        oninput="updateVirementAmounts(<?= $COMISSION_RATE / 100 ?>)"
                                        class="block w-full rounded-md border-gray-400 border-2 pr-12 focus:border-[#0A112F] focus:ring-[#0A112F] p-2 text-lg font-bold">
                                    <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                                        <span class="text-gray-500 text-sm">€</span>
                                    </div>
                                </div>
                                <p class="mt-1 text-xs text-gray-500">Max disponible : <?= number_format($montantTotal, 2, ',', ' ') ?>€</p>
                            </div>
                            
                            <div class="p-3 bg-white border border-gray-100 rounded-lg flex flex-col justify-center">
                                <p class="flex items-center gap-2 font-semibold text-sm text-gray-700">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                    Date de Virement Estimée :
                                </p>
                                <p class="text-lg text-blue-600 font-extrabold mt-1">
                                    <?= date('d/m/Y', strtotime($dateVirementEstime)) ?>
                                </p>
                                <p class="text-xs text-gray-500 mt-1">Basé sur J+3 ouvrés après validation.</p>
                            </div>
                        </div>
                        
                        <fieldset class="mt-6 pt-4 border-t border-gray-300">
                            <legend class="text-base font-semibold text-gray-700 mb-3">Calcul de la Transaction</legend>
                            <div class="space-y-2">
                                <div class="flex justify-between items-center text-gray-700">
                                    <span class="font-medium">Montant Brut Retiré :</span>
                                    <span id="brut_amount_display" class="font-bold"><?= number_format($montantTotalDemandé, 2, ',', ' ') ?>€</span>
                                </div>
                                <div class="flex justify-between items-center text-red-600">
                                    <span class="font-medium flex items-center gap-1">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        Commission ColoMap (<?= number_format($COMISSION_RATE, 2, ',', ' ') ?>%) :
                                    </span>
                                    <span id="commission_value" class="font-bold">- <?= number_format($commission, 2, ',', ' ') ?>€</span>
                                </div>
                                <div class="flex justify-between items-center text-green-700 border-t pt-3 border-gray-300 text-xl">
                                    <span class="font-extrabold">Montant NET Final :</span>
                                    <span id="net_amount_value" class="font-extrabold"><?= number_format($montantApresCommission, 2, ',', ' ') ?>€</span>
                                </div>
                            </div>
                        </fieldset>
                    </div>


                    <div class="mb-8 p-6 bg-white rounded-xl border border-gray-200 shadow-sm">
                        <h2 class="flex items-center gap-2 text-xl font-bold text-[#0A112F] mb-4">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3v-6a3 3 0 00-3-3H7a3 3 0 00-3 3v6a3 3 0 003 3z"/></svg>
                            Informations Bancaires
                        </h2>
                        <p class="text-sm text-gray-600 mb-4">Ces coordonnées seront utilisées pour le virement. Elles sont mises à jour dans le profil de l'organisme si vous les modifiez ici.</p>

                        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                            <div>
                                <label for="iban" class="block text-sm font-medium text-gray-700">IBAN <span class="text-red-500">*</span></label>
                                <input type="text" name="iban" id="iban" value="<?= htmlspecialchars($organisateur['iban'] ?? '') ?>" required class="mt-1 block w-full rounded-md border-gray-400 border-2 shadow-sm focus:border-green-500 focus:ring-green-500 p-2" maxlength="34" placeholder="FRxx xxxx xxxx xxxx xxxx xxxx xxxx x">
                            </div>

                            <div>
                                <label for="bic_swift" class="block text-sm font-medium text-gray-700">BIC / SWIFT <span class="text-red-500">*</span></label>
                                <input type="text" name="bic_swift" id="bic_swift" value="<?= htmlspecialchars($organisateur['bic_swift'] ?? '') ?>" required class="mt-1 block w-full rounded-md border-gray-400 border-2 shadow-sm focus:border-green-500 focus:ring-green-500 p-2" maxlength="11" placeholder="XXXXXXXXXXX">
                            </div>

                            <div>
                                <label for="email_organisme" class="block text-sm font-medium text-gray-700">Email de l'Organisme</label>
                                <input type="email" name="email_organisme" id="email_organisme" value="<?= htmlspecialchars($organisateur['email'] ?? '') ?>" class="mt-1 block w-full rounded-md border-gray-400 border-2 shadow-sm focus:border-green-500 focus:ring-green-500 p-2">
                            </div>
                            
                            <div>
                                <label for="tel_organisme" class="block text-sm font-medium text-gray-700">Téléphone de l'Organisme</label>
                                <input type="tel" name="tel_organisme" id="tel_organisme" value="<?= htmlspecialchars($organisateur['tel'] ?? '') ?>" class="mt-1 block w-full rounded-md border-gray-400 border-2 shadow-sm focus:border-green-500 focus:ring-green-500 p-2">
                            </div>
                        </div>
                    </div>

                    <div class="mt-8 pt-6 border-t border-gray-200 flex justify-end">
                        <button type="submit" class="inline-flex justify-center rounded-xl border border-transparent bg-green-600 py-3 px-6 text-sm font-medium text-white shadow-lg hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition transform hover:scale-105">
                            Confirmer et créer la demande de virement
                        </button>
                    </div>
                </form>

            </div>

        <?php endif; ?>
    </div>
</div>

<script>
    // Taux de commission est passé du PHP au JS pour le calcul en temps réel
    const COMMISSION_RATE_JS = <?= $COMISSION_RATE / 100 ?>;

    /**
     * Met à jour les montants de commission et net en temps réel
     * basé sur le montant brut entré par l'utilisateur.
     */
    function updateVirementAmounts() {
        const inputElement = document.getElementById('montant_total_demande');
        const maxAmount = parseFloat(inputElement.getAttribute('max'));
        // Remplacer la virgule par le point pour le parsing float si nécessaire
        let brutAmount = parseFloat(inputElement.value.replace(',', '.')) || 0;

        // Limiter le montant à la valeur maximale et minimale
        if (brutAmount > maxAmount) {
            brutAmount = maxAmount;
            inputElement.value = brutAmount.toFixed(2);
        }
        if (brutAmount < 0) {
            brutAmount = 0;
            inputElement.value = brutAmount.toFixed(2);
        }
        
        // S'assurer que le champ d'entrée a toujours la valeur mise à jour
        inputElement.value = brutAmount.toFixed(2);


        // --- Calcul ---
        const commission = brutAmount * COMMISSION_RATE_JS;
        const netAmount = brutAmount - commission;

        // --- Mise à jour de l'affichage ---
        
        // 1. Montant Brut Affiché (pour le récap)
        document.getElementById('brut_amount_display').textContent = 
            brutAmount.toFixed(2).replace('.', ',') + '€';

        // 2. Commission
        document.getElementById('commission_value').textContent = 
            '- ' + commission.toFixed(2).replace('.', ',') + '€';
            
        // 3. Montant Net Final
        document.getElementById('net_amount_value').textContent = 
            netAmount.toFixed(2).replace('.', ',') + '€';
    }

    // Exécuter le calcul une fois au chargement pour s'assurer que les valeurs initiales sont affichées
    document.addEventListener('DOMContentLoaded', updateVirementAmounts);
</script>

<?php require_once 'partials/footer.php'; ?>