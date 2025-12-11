<?php
require_once 'partials/header.php';
if (!isset($_SESSION['user']) || !$_SESSION['user']['is_directeur']) {
    header('Location: index.php');
    exit;
}
?>
<title>Créer un Nouveau Camp - ColoMap</title>

<main class="container mx-auto max-w-4xl px-4 sm:px-6 lg:px-8 py-12">
    <div class="mb-8"><a href="organisateurs.php" class="inline-flex items-center gap-2 text-gray-600 hover:text-gray-900 font-medium"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-5 h-5"><path fill-rule="evenodd" d="M17 10a.75.75 0 0 1-.75.75H5.612l4.158 3.96a.75.75 0 1 1-1.04 1.08l-5.5-5.25a.75.75 0 0 1 0-1.08l5.5-5.25a.75.75 0 1 1 1.04 1.08L5.612 9.25H16.25A.75.75 0 0 1 17 10Z" clip-rule="evenodd" /></svg>Retour</a></div>
    
    <div class="bg-white p-8 rounded-xl shadow-lg border">
        <form id="single-page-form" class="space-y-10">
            
            <fieldset class="space-y-6">
                <legend class="text-2xl font-bold mb-4 border-b pb-2 w-full">Informations Générales</legend>
                <div><label for="nom" class="block text-sm font-medium text-gray-700">Nom du camp</label><input type="text" id="nom" required class="bg-gray-50 mt-1 block w-full rounded-md border-gray-400 p-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"></div>
                <div><label for="description" class="block text-sm font-medium text-gray-700">Description</label><textarea id="description" rows="4" required class="bg-gray-50 mt-1 block w-full rounded-md border-gray-400 p-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"></textarea></div>
                <div class="grid md:grid-cols-2 gap-6">
                    <div><label for="date_debut" class="block text-sm font-medium text-gray-700">Date de début</label><input type="date" id="date_debut" required class="bg-gray-50 mt-1 block w-full rounded-md border-gray-400 p-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"></div>
                    <div><label for="date_fin" class="block text-sm font-medium text-gray-700">Date de fin</label><input type="date" id="date_fin" required class="bg-gray-50 mt-1 block w-full rounded-md border-gray-400 p-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"></div>
                </div>
            </fieldset>

            <fieldset class="space-y-6 pt-6 border-t">
                 <legend class="text-2xl font-bold mb-4 border-b pb-2 w-full">Détails du Camp</legend>
                <div class="grid md:grid-cols-2 gap-6">
                    <div><label for="adresse" class="block text-sm font-medium text-gray-700">Adresse</label><input type="text" id="adresse" required class="bg-gray-50 mt-1 block w-full rounded-md border-gray-400 p-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"></div>
                    <div><label for="ville" class="block text-sm font-medium text-gray-700">Ville</label><input type="text" id="ville" required class="bg-gray-50 mt-1 block w-full rounded-md border-gray-400 p-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"></div>
                    <div><label for="code_postal" class="block text-sm font-medium text-gray-700">Code Postal</label><input type="text" id="code_postal" required pattern="[0-9]{5}" class="bg-gray-50 mt-1 block w-full rounded-md border-gray-400 p-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"></div>
                </div>
                <div class="grid md:grid-cols-3 gap-6 pt-4">
                    <div class="hidden"><label for="prix">Prix de base (€)</label><input type="number" id="prix" value="0" required></div>
                    <div><label for="age_min" class="block text-sm font-medium text-gray-700">Âge min.</label><input type="number" id="age_min" required class="bg-gray-50 mt-1 block w-full rounded-md border-gray-400 p-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"></div>
                    <div><label for="age_max" class="block text-sm font-medium text-gray-700">Âge max.</label><input type="number" id="age_max" required class="bg-gray-50 mt-1 block w-full rounded-md border-gray-400 p-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"></div>
                </div>
                <div><label for="image_url" class="block text-sm font-medium text-gray-700">URL de l'image d'illustration</label><input type="url" id="image_url" placeholder="https://..." required class="bg-gray-50 mt-1 block w-full rounded-md border-gray-400 p-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"></div>
            </fieldset>
            
            <fieldset class="space-y-6 pt-6 border-t">
                <legend class="text-2xl font-bold mb-2 border-b pb-2 w-full">Options d'Inscription</legend>
                <div class="pt-2"><label class="flex items-center cursor-pointer"><input type="checkbox" id="online-inscription" class="h-4 w-4 rounded text-blue-600 focus:ring-blue-500" checked><span class="ml-3 text-sm font-medium">Activer les inscriptions en ligne sur ColoMap</span></label></div>
                
                <div id="online-fields" class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <div class="flex justify-between items-center mb-1">
                                <label for="organisateur-select" class="block text-sm font-medium text-gray-700">Organisme responsable</label>
                                <button type="button" id="toggle-new-org-btn" class="text-sm text-blue-600 hover:underline font-medium">+ Créer</button>
                            </div>
                            <select id="organisateur-select" required class="bg-gray-50 block w-full p-2 border-gray-400 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500"></select>
                            <div id="new-org-form" class="hidden mt-2 space-y-4 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                                <h3 class="font-semibold text-gray-800">Création rapide d'un organisme</h3>
                                <div><input type="text" id="new-org-name" placeholder="Nom de l'organisme" class="w-full p-2 border-gray-300 rounded-md"></div>
                                <div class="grid md:grid-cols-2 gap-4">
                                    <div><input type="email" id="new-org-mail" placeholder="Email de contact" class="w-full p-2 border-gray-300 rounded-md"></div>
                                    <div><input type="tel" id="new-org-tel" placeholder="Téléphone" class="w-full p-2 border-gray-300 rounded-md"></div>
                                </div>
                                <div><input type="url" id="new-org-web" placeholder="Site web (optionnel)" class="w-full p-2 border-gray-300 rounded-md"></div>
                                <div id="new-org-message" class="text-xs"></div>
                                <div class="text-right">
                                    <button type="button" id="cancel-new-org-btn" class="text-sm text-gray-600 mr-4">Annuler</button>
                                    <button type="button" id="save-new-org-btn" class="bg-blue-600 text-white font-bold py-2 px-4 rounded-lg text-sm">Enregistrer</button>
                                </div>
                            </div>
                        </div>
                        <div><label for="date-limite" class="block text-sm font-medium">Date limite d'inscription</label><input type="date" id="date-limite" class="bg-gray-50 mt-1 block w-full p-2 border-gray-400 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500"></div>
                        <div><label for="remise" class="block text-sm font-medium">Remise / enfant supp. (%)</label><input type="number" id="remise" value="0" min="0" max="100" class="bg-gray-50 mt-1 block w-full p-2 border-gray-400 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500"></div>
                    </div>
                    <div class="grid md:grid-cols-2 gap-6 items-end">
                         <div><label for="quota-max" class="block text-sm font-medium">Quota d'enfants total</label><input type="number" id="quota-max" required min="1" class="bg-gray-50 mt-1 block w-full p-2 border-gray-400 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500"></div>
                         <div><button type="button" id="toggle-genre-quota" class="text-sm text-blue-600 hover:underline">+ quota par genre</button></div>
                    </div>
                    <div id="genre-quota-fields" class="hidden grid md:grid-cols-2 gap-6 bg-gray-100 p-4 rounded-lg">
                        <div><label for="quota-fille" class="block text-sm font-medium">Quota max filles</label><input type="number" id="quota-fille" min="0" class="bg-gray-50 mt-1 block w-full p-2 border-gray-400 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500"></div>
                        <div><label for="quota-garcon" class="block text-sm font-medium">Quota max garçons</label><input type="number" id="quota-garcon" min="0" class="bg-gray-50 mt-1 block w-full p-2 border-gray-400 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500"></div>
                    </div>
                </div>
                <div id="offline-fields" class="hidden space-y-4">
                    <div><label for="offline-prix" class="block text-sm font-medium text-gray-700">Prix du camp (€)</label><input type="number" id="offline-prix" class="bg-gray-50 mt-1 block w-full rounded-md border-gray-400 p-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"></div>
                    <div class="bg-gray-100 p-4 rounded-lg space-y-4">
                        <p class="text-sm text-gray-600">Infos pour le dossier papier :</p>
                        <div><label for="pdf-link" class="block text-sm font-medium">Lien vers le dossier PDF</label><input type="url" id="pdf-link" class="bg-gray-50 mt-1 block w-full rounded-md border-gray-400 p-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"></div>
                        <div><label for="adresse_retour" class="block text-sm font-medium">Adresse de retour du dossier</label><input type="text" id="adresse_retour" class="bg-gray-50 mt-1 block w-full rounded-md border-gray-400 p-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"></div>
                    </div>
                </div>
            </fieldset>
            
            <fieldset id="advanced-options-container" class="space-y-6 pt-10 mt-10 border-t">
                <legend class="text-2xl font-bold mb-4 border-b pb-2 w-full">Options Avancées</legend>
                <div class="space-y-4">
                    <label class="flex items-center cursor-pointer"><input type="checkbox" id="toggle-tarifs" class="h-4 w-4 focus:ring-blue-500"><span class="ml-3 text-sm font-medium">Gérer les tarifs multiples</span></label>
                    <div id="tarifs-section" class="hidden space-y-6 pl-6">
                        <div>
                            <h3 class="text-lg font-medium mb-2">Sélectionner des tarifs existants</h3>
                            <div id="existing-tarifs-list" class="space-y-2 max-h-40 overflow-y-auto bg-gray-100 p-3 rounded">Sélectionnez un organisme pour voir les tarifs.</div>
                        </div>
                        <div class="border-t pt-4">
                             <h3 class="text-lg font-medium mb-2">Ou créer un nouveau tarif pour ce camp</h3>
                             <div class="grid md:grid-cols-2 gap-4 items-center">
                                <div><input type="text" id="new-tarif-name" placeholder="Nom du nouveau tarif" class="bg-gray-50 block w-full p-2 border-gray-400 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500"></div>
                                <div><input type="number" id="new-tarif-price" placeholder="Prix (€)" class="bg-gray-50 block w-full p-2 border-gray-400 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500"></div>
                             </div>
                             <div class="mt-2"><label class="flex items-center cursor-pointer"><input type="checkbox" id="new-tarif-montant-libre" class="h-4 w-4 focus:ring-blue-500"><span class="ml-2 text-sm text-gray-600">Montant libre (le prix devient un prix conseillé)</span></label></div>
                             <button type="button" id="add-new-tarif-btn" class="mt-4 bg-green-100 text-green-800 font-semibold py-2 px-4 rounded-lg text-sm">Ajouter ce tarif</button>
                        </div>
                    </div>
                </div>
                <div class="space-y-4 pt-4 border-t">
                    <label class="flex items-center cursor-pointer"><input type="checkbox" id="toggle-animateurs" class="h-4 w-4 focus:ring-blue-500"><span class="ml-3 text-sm font-medium">Gérer les animateurs</span></label>
                    <div id="animateurs-section" class="hidden space-y-6 pl-6">
                         <div class="grid md:grid-cols-3 gap-6">
                            <div><label for="quota-max-anim" class="text-sm">Quota animateurs</label><input type="number" id="quota-max-anim" class="bg-gray-50 mt-1 w-full p-2 border-gray-400 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500"></div>
                            <div><label for="quota-max-anim-fille" class="text-sm">... dont filles</label><input type="number" id="quota-max-anim-fille" class="bg-gray-50 mt-1 w-full p-2 border-gray-400 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500"></div>
                            <div><label for="quota-max-anim-garcon" class="text-sm">... dont garçons</label><input type="number" id="quota-max-anim-garcon" class="bg-gray-50 mt-1 w-full p-2 border-gray-400 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500"></div>
                         </div>
                         <div class="pt-4"><label class="flex items-center"><input type="checkbox" id="anim-majeur" class="h-4 w-4 focus:ring-blue-500"><span class="ml-3 text-sm">Animateurs majeurs uniquement</span></label></div>
                         <div id="anim-age-details" class="hidden grid md:grid-cols-2 lg:grid-cols-4 gap-4 bg-gray-100 p-4 rounded-lg">
                            <div><label class="text-sm" for="quota-fille-mineur">Filles -18</label><input type="number" id="quota-fille-mineur" class="bg-gray-50 mt-1 w-full p-2 border-gray-400 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500"></div>
                            <div><label class="text-sm" for="quota-fille-majeur">Filles +18</label><input type="number" id="quota-fille-majeur" class="bg-gray-50 mt-1 w-full p-2 border-gray-400 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500"></div>
                            <div><label class="text-sm" for="quota-garcon-mineur">Garçons -18</label><input type="number" id="quota-garcon-mineur" class="bg-gray-50 mt-1 w-full p-2 border-gray-400 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500"></div>
                            <div><label class="text-sm" for="quota-garcon-majeur">Garçons +18</label><input type="number" id="quota-garcon-majeur" class="bg-gray-50 mt-1 w-full p-2 border-gray-400 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500"></div>
                         </div>
                         <div class="pt-4"><label class="flex items-center"><input type="checkbox" id="bafa-obligatoire" class="h-4 w-4 focus:ring-blue-500"><span class="ml-3 text-sm">BAFA obligatoire</span></label></div>
                         <div class="border-t pt-6 space-y-4">
                             <label class="flex items-center"><input type="checkbox" id="paiement-anim" class="h-4 w-4 focus:ring-blue-500"><span class="ml-3 text-sm">Les animateurs doivent payer une part</span></label>
                             <div id="anim-payment-details" class="hidden grid md:grid-cols-2 gap-6">
                                <div><label for="prix-anim" class="text-sm">Montant à payer (€)</label><input type="number" id="prix-anim" class="bg-gray-50 mt-1 w-full p-2 border-gray-400 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500"></div>
                                <div class="flex items-end pb-2"><label class="flex items-center"><input type="checkbox" id="montant-libre-anim" class="h-4 w-4 focus:ring-blue-500"><span class="ml-3 text-sm">Montant libre</span></label></div>
                             </div>
                             <label class="flex items-center"><input type="checkbox" id="remuneration-anim" class="h-4 w-4 focus:ring-blue-500"><span class="ml-3 text-sm">Rémunération prévue pour les animateurs</span></label>
                         </div>
                    </div>
                </div>
            </fieldset>

            <div class="flex justify-end mt-10 border-t pt-6">
                 <button type="submit" class="bg-green-600 text-white font-bold py-3 px-6 rounded-lg hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">Soumettre le camp</button>
            </div>
            
            <div id="form-message" class="text-center mt-4 text-sm font-medium"></div>
        </form>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // --- DOM Elements ---
    const form = document.getElementById('single-page-form');
    const orgSelect = document.getElementById('organisateur-select');
    const onlineCheckbox = document.getElementById('online-inscription');
    const advancedOptionsContainer = document.getElementById('advanced-options-container');
    const toggleTarifsBtn = document.getElementById('toggle-tarifs');
    const basePriceInput = document.getElementById('prix');
    const offlinePriceInput = document.getElementById('offline-prix');
    const newTarifMontantLibre = document.getElementById('new-tarif-montant-libre');
    const newTarifPriceInput = document.getElementById('new-tarif-price');
    const toggleNewOrgBtn = document.getElementById('toggle-new-org-btn');
    const newOrgForm = document.getElementById('new-org-form');
    const saveNewOrgBtn = document.getElementById('save-new-org-btn');
    const cancelNewOrgBtn = document.getElementById('cancel-new-org-btn');
    let selectedTarifs = new Set();
    let allTarifs = [];

    // --- Fonctions de visibilité ---
    function toggleOnlineSections() {
        const isOnline = onlineCheckbox.checked;
        document.getElementById('online-fields').classList.toggle('hidden', !isOnline);
        document.getElementById('offline-fields').classList.toggle('hidden', isOnline);
        advancedOptionsContainer.classList.toggle('hidden', !isOnline);
        
        // CORRECTION : S'assurer que les champs requis sont gérés correctement
        document.getElementById('quota-max').required = isOnline;
        document.getElementById('organisateur-select').required = isOnline;
        document.getElementById('offline-prix').required = !isOnline;

        if (isOnline) {
            updateBasePrice();
        } else {
            basePriceInput.value = offlinePriceInput.value || 0;
            toggleTarifsBtn.checked = false;
            document.getElementById('toggle-animateurs').checked = false;
            document.getElementById('tarifs-section').classList.add('hidden');
            document.getElementById('animateurs-section').classList.add('hidden');
        }
    }
    
    // --- Logique du Prix de Base ---
    function updateBasePrice() {
        if (!toggleTarifsBtn.checked) {
            basePriceInput.value = 0;
            return;
        }
        const tarifCheckboxes = document.querySelectorAll('.existing-tarif-checkbox:checked');
        let prices = [];
        tarifCheckboxes.forEach(checkbox => {
            prices.push(parseFloat(checkbox.dataset.price));
        });
        if (prices.length > 0) {
            basePriceInput.value = Math.min(...prices);
        } else {
            basePriceInput.value = 0;
        }
    }

    // --- Logique des Tarifs (Filtrage Côté Client) ---
    function renderFilteredTarifs() {
        const listContainer = document.getElementById('existing-tarifs-list');
        const orgId = orgSelect.value;
        listContainer.innerHTML = '';
        if (!orgId) { listContainer.innerHTML = '<p class="text-sm text-gray-500">Veuillez sélectionner un organisme.</p>'; return; }

        const filteredTarifs = allTarifs.filter(tarif => tarif.fields['Lien'] && tarif.fields['Lien'][0] === orgId);
        if (filteredTarifs.length > 0) {
            filteredTarifs.forEach(tarif => {
                const label = document.createElement('label');
                label.className = 'flex items-center p-2 rounded hover:bg-gray-100 cursor-pointer';
                label.innerHTML = `<input type="checkbox" value="${tarif.id}" data-price="${tarif.fields['Prix']}" class="h-4 w-4 existing-tarif-checkbox focus:ring-blue-500" ${selectedTarifs.has(tarif.id) ? 'checked' : ''}><span class="ml-3 text-sm">${tarif.fields['Nom du tarif']} (${tarif.fields['Prix']}€)</span>`;
                listContainer.appendChild(label);
            });
        } else { listContainer.innerHTML = '<p class="text-sm text-gray-500">Aucun tarif existant pour cet organisme.</p>'; }
    }
    
    // --- Logique des Quotas ---
    function handleQuotaChange() {
        const total = parseInt(document.getElementById('quota-max').value, 10) || 0;
        const filles = document.getElementById('quota-fille');
        const garcons = document.getElementById('quota-garcon');
        if (document.activeElement === filles && filles.value !== '') {
            const valFilles = Math.max(0, parseInt(filles.value, 10) || 0);
            filles.value = Math.min(valFilles, total);
            garcons.value = total - filles.value;
        } else if (document.activeElement === garcons && garcons.value !== '') {
            const valGarcons = Math.max(0, parseInt(garcons.value, 10) || 0);
            garcons.value = Math.min(valGarcons, total);
            filles.value = total - garcons.value;
        }
    }

    // --- Attachement des Événements ---
    onlineCheckbox.addEventListener('change', toggleOnlineSections);
    toggleTarifsBtn.addEventListener('change', (e) => {
        document.getElementById('tarifs-section').classList.toggle('hidden', !e.target.checked);
        if(e.target.checked) renderFilteredTarifs();
        updateBasePrice();
    });
    offlinePriceInput.addEventListener('input', () => { if (!onlineCheckbox.checked) { basePriceInput.value = offlinePriceInput.value; } });
    newTarifMontantLibre.addEventListener('change', (e) => { newTarifPriceInput.placeholder = e.target.checked ? "Prix conseillé" : "Prix (€)"; });
    document.getElementById('toggle-animateurs').addEventListener('change', (e) => document.getElementById('animateurs-section').classList.toggle('hidden', !e.target.checked));
    document.getElementById('toggle-genre-quota').addEventListener('click', () => document.getElementById('genre-quota-fields').classList.toggle('hidden'));
    document.getElementById('anim-majeur').addEventListener('change', (e) => document.getElementById('anim-age-details').classList.toggle('hidden', e.target.checked));
    document.getElementById('paiement-anim').addEventListener('change', (e) => document.getElementById('anim-payment-details').classList.toggle('hidden', !e.target.checked));
    orgSelect.addEventListener('change', renderFilteredTarifs);
    document.getElementById('existing-tarifs-list').addEventListener('change', (e) => {
        if (e.target.classList.contains('existing-tarif-checkbox')) {
            if (e.target.checked) { selectedTarifs.add(e.target.value); } 
            else { selectedTarifs.delete(e.target.value); }
            updateBasePrice();
        }
    });
    saveNewOrgBtn.addEventListener('click', async function() {
        // ... (logique de sauvegarde du nouvel organisme, inchangée)
    });
    toggleNewOrgBtn.addEventListener('click', () => newOrgForm.classList.toggle('hidden'));
    cancelNewOrgBtn.addEventListener('click', () => newOrgForm.classList.add('hidden'));
    document.getElementById('add-new-tarif-btn').addEventListener('click', async function() {
        // ... (logique d'ajout de tarif, inchangée)
    });
    ['quota-max', 'quota-fille', 'quota-garcon'].forEach(id => document.getElementById(id).addEventListener('input', handleQuotaChange));

    // --- Soumission du formulaire ---
    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        const submitButton = form.querySelector('button[type="submit"]');
        const formMessage = document.getElementById('form-message');
        formMessage.innerHTML = '<p class="text-blue-500">Soumission en cours...</p>';
        submitButton.disabled = true;

        const isOnline = document.getElementById('online-inscription').checked;
        
        // CORRECTION : On s'assure que le prix est correctement défini
        let finalPrice = 0;
        if (isOnline) {
             finalPrice = document.getElementById('prix').value;
        } else {
             finalPrice = document.getElementById('offline-prix').value;
             if (!finalPrice) {
                 alert("Veuillez définir un prix pour l'inscription hors ligne.");
                 submitButton.disabled = false;
                 return;
             }
        }
        
        const formData = {
            nom: document.getElementById('nom').value,
            description: document.getElementById('description').value,
            ville: document.getElementById('ville').value,
            code_postal: document.getElementById('code_postal').value,
            adresse: document.getElementById('adresse').value,
            prix: finalPrice,
            age_min: document.getElementById('age_min').value,
            age_max: document.getElementById('age_max').value,
            date_debut: document.getElementById('date_debut').value,
            date_fin: document.getElementById('date_fin').value,
            image_url: document.getElementById('image_url').value,
            inscription_en_ligne: isOnline,
            organisateur_id: document.getElementById('organisateur-select').value,
            date_limite_inscription: document.getElementById('date-limite').value,
            remise: document.getElementById('remise').value,
            quota_max: document.getElementById('quota-max').value,
            quota_fille: document.getElementById('quota-fille').value,
            quota_garcon: document.getElementById('quota-garcon').value,
            tarifs: document.getElementById('toggle-tarifs').checked ? Array.from(selectedTarifs) : [],
            dossier_pdf: document.getElementById('pdf-link').value,
            adresse_retour: document.getElementById('adresse_retour').value,
            gestion_animateur: document.getElementById('toggle-animateurs').checked,
            quota_max_anim: document.getElementById('quota-max-anim').value,
            quota_max_anim_fille: document.getElementById('quota-max-anim-fille').value,
            quota_max_anim_garcon: document.getElementById('quota-max-anim-garcon').value,
            anim_majeur: document.getElementById('anim-majeur').checked,
            quota_fille_mineur: document.getElementById('quota-fille-mineur').value,
            quota_fille_majeur: document.getElementById('quota-fille-majeur').value,
            quota_garcon_mineur: document.getElementById('quota-garcon-mineur').value,
            quota_garcon_majeur: document.getElementById('quota-garcon-majeur').value,
            bafa_obligatoire: document.getElementById('bafa-obligatoire').checked,
            paiement_anim: document.getElementById('paiement-anim').checked,
            prix_anim: document.getElementById('prix-anim').value,
            montant_libre_anim: document.getElementById('montant-libre-anim').checked,
            remuneration_anim: document.getElementById('remuneration-anim').checked,
        };
        try {
            const response = await fetch('api/add_camp.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(formData) });
            const result = await response.json();
            if (!response.ok) throw new Error(result.error || 'Une erreur est survenue.');
            formMessage.innerHTML = `<p class="text-green-600 font-bold">${result.success}</p>`;
            setTimeout(() => window.location.href = 'organisateurs.php', 2000);
        } catch (error) {
            formMessage.innerHTML = `<p class="text-red-600 font-bold">${error.message}</p>`;
            submitButton.disabled = false;
        }
    });

    // --- Initialisation ---
    (async () => {
        try {
            const [orgsResponse, tarifsResponse] = await Promise.all([
                fetch('api/get_organisateurs.php'),
                fetch('api/get_tarifs_by_organisateur.php')
            ]);
            if (!orgsResponse.ok || !tarifsResponse.ok) throw new Error('Erreur de chargement initial.');
            
            const dataOrgs = await orgsResponse.json();
            allTarifs = await tarifsResponse.json();

            orgSelect.innerHTML = '<option value="">Sélectionnez un organisme</option>';
            dataOrgs.forEach(org => {
                orgSelect.innerHTML += `<option value="${org.id}">${org.nom}</option>`;
            });
        } catch (e) { console.error("Erreur d'initialisation:", e); }
        toggleOnlineSections();
    })();
});
</script>
</body>
</html>