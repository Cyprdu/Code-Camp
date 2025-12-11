<?php
require_once 'partials/header.php';
// Sécurité : si l'utilisateur n'est pas connecté, il est redirigé.
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}
// On récupère l'ID du camp depuis l'URL. S'il est manquant, on redirige.
$camp_id = $_GET['id'] ?? '';
if (empty($camp_id)) {
    header('Location: index.php');
    exit;
}
?>
<title>Inscription au Camp - ColoMap</title>

<main class="container mx-auto max-w-4xl px-4 sm:px-6 lg:px-8 py-12">
    <div id="loader" class="text-center py-20">
        <div class="loader inline-block"></div>
        <p class="mt-4 text-gray-600">Préparation de l'inscription...</p>
    </div>

    <div id="inscription-process" class="hidden">
        <div class="text-center mb-8">
            <h1 id="camp-title" class="text-3xl font-bold text-gray-900"></h1>
        </div>

        <div id="step-1-children" class="step-content bg-white p-8 rounded-xl shadow-lg border">
           <h2 class="text-2xl font-bold mb-4">Étape 1: Qui participe ?</h2>
            <p class="text-gray-600 mb-6">Sélectionnez le ou les enfants que vous souhaitez inscrire à ce camp.</p>
            <div id="children-selection-list" class="space-y-3">
                </div>
            <div class="text-right mt-8">
                <button id="btn-to-summary" class="bg-blue-600 text-white font-bold py-3 px-6 rounded-lg hover:bg-blue-700 disabled:bg-gray-400" disabled>Continuer</button>
            </div>
        </div>

        <div id="step-2-summary" class="step-content hidden bg-white p-8 rounded-xl shadow-lg border">
             <h2 class="text-2xl font-bold mb-6">Étape 2: Résumé et Paiement</h2>
            <div id="summary-details" class="space-y-4 border-b pb-4 mb-6">
                </div>
            <div class="font-bold text-xl flex justify-between">
                <span>Total à régler :</span>
                <span id="total-price"></span>
            </div>
             <div class="mt-8">
                 <h3 class="text-lg font-semibold mb-4">Informations de paiement (simulation)</h3>
                 <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <input type="text" placeholder="Numéro de carte (ex: 4242 ...)" class="p-2 border rounded-md">
                    <input type="text" placeholder="Nom sur la carte" class="p-2 border rounded-md">
                    <input type="text" placeholder="MM/AA" class="p-2 border rounded-md">
                    <input type="text" placeholder="CVC" class="p-2 border rounded-md">
                 </div>
            </div>
            <div id="payment-message" class="text-center my-4 text-sm font-medium"></div>
            <div class="flex justify-between mt-8">
                <button id="btn-back-to-children" class="bg-gray-200 text-gray-800 font-bold py-3 px-6 rounded-lg">Retour</button>
                <button id="btn-confirm-payment" class="bg-green-600 text-white font-bold py-3 px-6 rounded-lg hover:bg-green-700">Valider l'inscription</button>
            </div>
        </div>

        <div id="step-3-confirmation" class="step-content hidden bg-white p-8 rounded-xl shadow-lg border text-center">
             <h2 class="text-2xl font-bold mb-2">Inscription Réussie !</h2>
            <p class="text-gray-600 mb-6">Félicitations ! L'inscription a bien été prise en compte.</p>
            <a href="reservations.php" class="bg-blue-600 text-white font-bold py-3 px-6 rounded-lg">Voir mes réservations</a>
        </div>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', async function() {
    // Références aux éléments HTML
    const loader = document.getElementById('loader');
    const inscriptionProcess = document.getElementById('inscription-process');
    const campTitle = document.getElementById('camp-title');
    const childrenListContainer = document.getElementById('children-selection-list');
    const summaryContainer = document.getElementById('summary-details');
    const totalPriceEl = document.getElementById('total-price');
    const campId = '<?php echo $camp_id; ?>';
    
    // Objet pour gérer les différentes étapes de l'interface
    const steps = {
        children: document.getElementById('step-1-children'),
        summary: document.getElementById('step-2-summary'),
        confirmation: document.getElementById('step-3-confirmation')
    };

    // Variables pour stocker les données de l'API
    let campData = {};
    let childrenData = [];

    // Fonction pour afficher la bonne étape
    function showStep(stepName) {
        Object.values(steps).forEach(step => step.classList.add('hidden'));
        if (steps[stepName]) steps[stepName].classList.remove('hidden');
    }

    // Chargement des données au démarrage de la page
    try {
        // On lance les deux appels à l'API en parallèle pour gagner du temps
        const [campRes, childrenRes] = await Promise.all([
            fetch(`api/get_camp_details.php?id=${campId}`),
            fetch('api/get_children.php')
        ]);
        if (!campRes.ok || !childrenRes.ok) throw new Error('Erreur de chargement des données initiales.');
        
        campData = await campRes.json();
        childrenData = await childrenRes.json(); // Reçoit uniquement les enfants de l'utilisateur connecté

        if (campData.error || childrenData.error) throw new Error('Données invalides reçues du serveur.');
        
        // Affichage des informations
        campTitle.textContent = `Inscription pour : ${campData.nom}`;
        renderChildrenList();
        loader.classList.add('hidden');
        inscriptionProcess.classList.remove('hidden');
        showStep('children');

    } catch (error) {
        loader.innerHTML = `<p class="text-red-500 font-bold">${error.message}</p>`;
    }

    // Fonction pour afficher la liste des enfants et gérer leur éligibilité
    function renderChildrenList() {
        childrenListContainer.innerHTML = '';
        if (childrenData.length === 0) {
            childrenListContainer.innerHTML = '<p>Vous devez d\'abord <a href="add_child.php" class="text-blue-600 underline">enregistrer un enfant</a> sur votre profil.</p>';
            return;
        }
        
        const alreadyRegisteredIds = campData.inscrits || [];
        
        childrenData.forEach(child => {
            const isEligibleByAge = child.age >= campData.age_min && child.age <= campData.age_max;
            const isAlreadyRegistered = alreadyRegisteredIds.includes(child.id);
            
            let isEligibleByGender = true;
            let statusHtml = '';

            // SÉQUENCE DE VÉRIFICATION DE L'ÉLIGIBILITÉ
            if (isAlreadyRegistered) {
                statusHtml = '<span class="text-xs font-bold text-green-600 ml-2">Déjà inscrit</span>';
            } else if (!isEligibleByAge) {
                statusHtml = '<span class="text-xs text-red-500 ml-2">Âge non compatible</span>';
            } else {
                // Vérification du quota par genre
                if (child.sexe === 'Femme' && campData.quota_max_filles > 0 && campData.filles_inscrites >= campData.quota_max_filles) {
                    isEligibleByGender = false;
                    statusHtml = '<span class="text-xs font-bold text-orange-600 ml-2">Plus de place pour les filles</span>';
                } else if (child.sexe === 'Homme' && campData.quota_max_garcons > 0 && campData.garcons_inscrits >= campData.quota_max_garcons) {
                    isEligibleByGender = false;
                    statusHtml = '<span class="text-xs font-bold text-orange-600 ml-2">Plus de place pour les garçons</span>';
                }
            }
            
            // Un enfant n'est sélectionnable que si toutes les conditions sont remplies
            const isSelectable = isEligibleByAge && !isAlreadyRegistered && isEligibleByGender;

            // Création de l'élément HTML pour l'enfant
            const label = document.createElement('label');
            label.className = `flex items-center p-4 border rounded-lg ${isSelectable ? 'cursor-pointer hover:bg-blue-50' : 'opacity-60 bg-gray-100 cursor-not-allowed'}`;
            label.innerHTML = `
                <input type="checkbox" class="h-5 w-5 rounded border-gray-300 text-blue-600 focus:ring-blue-500" data-child-id="${child.id}" data-child-name="${child.prenom}" ${!isSelectable ? 'disabled' : ''}>
                <div class="ml-4">
                    <span class="font-semibold">${child.prenom}</span>
                    <span class="text-sm text-gray-500">(${child.age} ans - ${child.sexe})</span>
                    ${statusHtml}
                 </div>
            `;
            childrenListContainer.appendChild(label);
        });
        childrenListContainer.addEventListener('change', updateSummaryButtonState);
    }
    
    // Le reste du script gère la navigation entre les étapes et la soumission finale
    
    function getSelectedChildren() {
        return Array.from(childrenListContainer.querySelectorAll('input:checked')).map(input => ({ id: input.dataset.childId, name: input.dataset.childName }));
    }
    
    function updateSummaryButtonState() {
        document.getElementById('btn-to-summary').disabled = getSelectedChildren().length === 0;
    }
    
    document.getElementById('btn-to-summary').addEventListener('click', function() {
        const selectedChildren = getSelectedChildren();
        let summaryHtml = '';
        let total = 0;
        const price = parseFloat(campData.prix);
        const discount = campData.remise ? parseFloat(campData.remise) / 100 : 0;
        selectedChildren.forEach((child, index) => {
            let itemPrice = price;
            let discountText = '';
            if (index > 0 && discount > 0) {
                itemPrice = price * (1 - discount);
                discountText = ` (Remise de ${campData.remise}%)`;
            }
            total += itemPrice;
            summaryHtml += `<div class="flex justify-between"><p>Inscription pour <strong>${child.name}</strong>${discountText}</p><p>${itemPrice.toFixed(2)}€</p></div>`;
        });
        summaryContainer.innerHTML = summaryHtml;
        totalPriceEl.textContent = `${total.toFixed(2)}€`;
        showStep('summary');
    });

    document.getElementById('btn-back-to-children').addEventListener('click', () => showStep('children'));

    document.getElementById('btn-confirm-payment').addEventListener('click', async function() {
        this.disabled = true; this.textContent = 'Traitement en cours...';
        const messageEl = document.getElementById('payment-message');
        messageEl.textContent = '';
        const payload = { campId: campId, childIds: getSelectedChildren().map(c => c.id), finalPrice: parseFloat(totalPriceEl.textContent) };
        try {
            const response = await fetch('api/process_inscription.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload) });
            const result = await response.json();
            if(!response.ok) throw new Error(result.error || "Une erreur est survenue lors du paiement.");
            showStep('confirmation');
        } catch (error) {
            messageEl.innerHTML = `<p class="text-red-600">${error.message}</p>`;
            this.disabled = false; this.textContent = "Valider l'inscription";
        }
    });
});
</script>
</body>
</html>