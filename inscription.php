<?php
require_once 'partials/header.php';

// Sécurité
if (!isset($_SESSION['user'])) {
    $redirectUrl = 'inscription.php' . (!empty($_GET['t']) ? '?t=' . urlencode($_GET['t']) : '');
    header('Location: login.php?redirect=' . urlencode($redirectUrl));
    exit;
}

$token = $_GET['t'] ?? '';
?>

<title>Inscription - ColoMap</title>

<main class="container mx-auto max-w-4xl px-4 py-12">
    
    <div id="loader" class="text-center py-20">
        <div class="loader inline-block"></div>
        <p class="mt-4 text-gray-600">Chargement des données...</p>
    </div>

    <div id="inscription-content" class="hidden">
        <div class="mb-8 text-center">
            <h1 class="text-3xl font-extrabold text-gray-900 mb-2">Inscription au séjour</h1>
            <p class="text-xl text-blue-600 font-bold" id="camp-title"></p>
            <p class="text-sm text-gray-500" id="camp-dates"></p>
            <div id="fratrie-badge" class="hidden mt-2 inline-block bg-green-100 text-green-800 text-xs font-bold px-3 py-1 rounded-full border border-green-200">
                Famille nombreuse ? Profitez de la remise fratrie !
            </div>
        </div>

        <div id="step-1" class="bg-white p-6 rounded-xl shadow-lg border mb-6 transition-all">
            <h2 class="text-xl font-bold mb-4 flex items-center text-gray-800">
                <span class="bg-blue-600 text-white w-8 h-8 flex items-center justify-center rounded-full mr-3 text-sm">1</span>
                Qui participe ?
            </h2>
            
            <div id="children-list" class="space-y-3 mb-4"></div>
            
            <div class="text-center pt-4 border-t">
                <a href="add_child.php?redirect=inscription.php?t=<?= htmlspecialchars($token) ?>" class="text-sm text-blue-600 hover:underline font-bold">+ Ajouter un autre enfant</a>
            </div>
            
            <div class="mt-6 text-right">
                <button id="btn-goto-2" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-6 rounded-lg shadow disabled:bg-gray-300 disabled:cursor-not-allowed" disabled>
                    Continuer &rarr;
                </button>
            </div>
        </div>

        <div id="step-2" class="hidden bg-white p-6 rounded-xl shadow-lg border mb-6 transition-all">
            <h2 class="text-xl font-bold mb-4 flex items-center text-gray-800">
                <span class="bg-blue-600 text-white w-8 h-8 flex items-center justify-center rounded-full mr-3 text-sm">2</span>
                Vérification des informations
            </h2>
            
            <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6">
                <div class="flex">
                    <div class="ml-3">
                        <p class="text-sm text-yellow-700 font-bold">
                            Veuillez relire attentivement les fiches de chaque enfant.
                        </p>
                        <p class="text-xs text-yellow-600 mt-1">
                            Vous devez attendre <span id="timer-display" class="font-bold text-red-600">15</span> secondes avant de pouvoir valider.
                        </p>
                    </div>
                </div>
            </div>

            <div id="verification-list" class="space-y-6">
                </div>

            <div class="mt-8 flex justify-between items-center">
                <button id="btn-back-1" class="text-gray-500 hover:text-gray-800 underline">Retour</button>
                <button id="btn-goto-3" class="bg-gray-400 text-white font-bold py-3 px-6 rounded-lg shadow cursor-not-allowed" disabled>
                    Valider et Choisir Tarif (15s)
                </button>
            </div>
        </div>

        <div id="step-3" class="hidden bg-white p-6 rounded-xl shadow-lg border mb-6 transition-all">
            <h2 class="text-xl font-bold mb-4 flex items-center text-gray-800">
                <span class="bg-blue-600 text-white w-8 h-8 flex items-center justify-center rounded-full mr-3 text-sm">3</span>
                Tarifs & Paiement
            </h2>
            
            <div id="pricing-list" class="space-y-4 mb-8">
                </div>

            <div class="bg-gray-50 p-6 rounded-xl border border-gray-200 text-center">
                <h3 class="text-2xl font-bold text-gray-800 mb-2">Total à régler : <span id="grand-total" class="text-blue-600">0.00 €</span></h3>
                <p class="text-xs text-gray-500 mb-6">Paiement sécurisé (Simulation)</p>
                <button id="btn-pay" class="w-full md:w-1/2 bg-green-600 hover:bg-green-700 text-white font-bold py-4 px-6 rounded-lg shadow-lg transform transition hover:scale-[1.02]">
                    Confirmer et Payer
                </button>
            </div>
             <div class="mt-4 text-left">
                <button id="btn-back-2" class="text-gray-500 hover:text-gray-800 underline">Retour</button>
            </div>
        </div>

    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', async function() {
    const token = '<?= $token ?>';
    let globalData = null;
    let selectedChildrenIds = []; // Array of IDs
    let selectedTarifs = {}; // { childId: {tarifId, prix} }
    
    // Elements DOM
    const loader = document.getElementById('loader');
    const content = document.getElementById('inscription-content');
    const step1 = document.getElementById('step-1');
    const step2 = document.getElementById('step-2');
    const step3 = document.getElementById('step-3');
    
    // 1. CHARGEMENT
    try {
        const res = await fetch(`api/get_inscription_data.php?t=${token}`);
        globalData = await res.json();
        
        if(globalData.error) throw new Error(globalData.error);
        
        // Setup Header
        document.getElementById('camp-title').textContent = globalData.camp.nom;
        document.getElementById('camp-dates').textContent = globalData.camp.dates;
        if(globalData.camp.remise_fratrie > 0) {
            document.getElementById('fratrie-badge').classList.remove('hidden');
            document.getElementById('fratrie-badge').textContent = `Remise Fratrie : -${globalData.camp.remise_fratrie}% dès le 2ème enfant inscrit !`;
        }

        // Render Step 1 (Choix)
        renderChildSelection();
        
        loader.classList.add('hidden');
        content.classList.remove('hidden');

    } catch(e) {
        loader.innerHTML = `<p class="text-red-600 font-bold">${e.message}</p>`;
        return;
    }

    // --- LOGIQUE ETAPE 1 ---
    function renderChildSelection() {
        const list = document.getElementById('children-list');
        list.innerHTML = '';
        
        if(globalData.enfants.length === 0) {
            list.innerHTML = '<p class="text-gray-500 italic">Aucun enfant enregistré.</p>';
            return;
        }

        globalData.enfants.forEach(child => {
            const div = document.createElement('div');
            // Style grisé si non éligible
            const disabledClass = !child.is_eligible ? 'bg-gray-100 opacity-60 cursor-not-allowed' : 'bg-white hover:bg-blue-50 cursor-pointer';
            const checkboxDisabled = !child.is_eligible ? 'disabled' : '';
            
            div.className = `flex items-center p-4 border rounded-lg transition ${disabledClass}`;
            
            div.innerHTML = `
                <div class="flex items-center h-5">
                    <input type="checkbox" value="${child.id}" ${checkboxDisabled} class="child-checkbox w-5 h-5 text-blue-600 rounded border-gray-300 focus:ring-blue-500">
                </div>
                <div class="ml-4 flex-1">
                    <div class="flex justify-between">
                        <label class="font-bold text-gray-900 block">${child.prenom} ${child.nom}</label>
                        ${!child.is_eligible ? `<span class="text-xs font-bold text-red-500 px-2 py-1 bg-red-100 rounded">${child.reason}</span>` : ''}
                    </div>
                    <p class="text-xs text-gray-500">${child.age} ans</p>
                </div>
            `;
            
            if(child.is_eligible) {
                div.addEventListener('click', (e) => {
                    if(e.target.type !== 'checkbox') {
                        const cb = div.querySelector('input');
                        cb.checked = !cb.checked;
                        updateSelection();
                    }
                });
                div.querySelector('input').addEventListener('change', updateSelection);
            }

            list.appendChild(div);
        });
    }

    function updateSelection() {
        const cbs = document.querySelectorAll('.child-checkbox:checked');
        selectedChildrenIds = Array.from(cbs).map(cb => parseInt(cb.value));
        
        const btn = document.getElementById('btn-goto-2');
        btn.disabled = selectedChildrenIds.length === 0;
        if(selectedChildrenIds.length > 0) {
            btn.classList.remove('bg-gray-300', 'cursor-not-allowed');
            btn.classList.add('bg-blue-600', 'hover:bg-blue-700');
        } else {
            btn.classList.add('bg-gray-300', 'cursor-not-allowed');
            btn.classList.remove('bg-blue-600', 'hover:bg-blue-700');
        }
    }

    document.getElementById('btn-goto-2').addEventListener('click', () => {
        renderVerification();
        step1.classList.add('hidden');
        step2.classList.remove('hidden');
        startTimer();
    });

    // --- LOGIQUE ETAPE 2 (VERIF & TIMER) ---
    function renderVerification() {
        const list = document.getElementById('verification-list');
        list.innerHTML = '';
        
        selectedChildrenIds.forEach(id => {
            const child = globalData.enfants.find(c => c.id === id);
            if(!child) return;

            // Liens fichiers sécurisés
            const linkCarnet = child.carnet_token ? `api/secure_file.php?file=${child.carnet_token}` : '#';
            const linkFiche = child.fiche_token ? `api/secure_file.php?file=${child.fiche_token}` : '#';
            const carnetClass = child.carnet_token ? "text-blue-600 hover:underline" : "text-gray-400 italic cursor-not-allowed";
            const ficheClass = child.fiche_token ? "text-blue-600 hover:underline" : "text-gray-400 italic cursor-not-allowed";

            const card = document.createElement('div');
            card.className = "border border-gray-300 rounded-lg overflow-hidden";
            card.innerHTML = `
                <div class="bg-gray-100 p-3 border-b flex justify-between items-center">
                    <h3 class="font-bold text-lg text-gray-800">${child.prenom} ${child.nom}</h3>
                    <a href="modif_child.php?id=${child.id}&redirect=inscription.php?t=${token}" class="text-xs bg-white border border-gray-300 px-3 py-1 rounded hover:bg-gray-50 text-gray-700 font-bold">
                        ✏️ Modifier les infos
                    </a>
                </div>
                <div class="p-4 grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                    <div>
                        <p class="text-gray-500 uppercase text-xs font-bold">Santé</p>
                        <p class="mb-1"><span class="font-semibold">Allergies:</span> ${child.infos_sante || 'RAS'}</p>
                        <p><span class="font-semibold">Régime:</span> ${child.regime_alimentaire || 'Standard'}</p>
                        <p class="mt-1"><span class="font-semibold">Médecin:</span> ${child.medecin_nom || 'NC'}</p>
                    </div>
                    <div>
                        <p class="text-gray-500 uppercase text-xs font-bold">Documents</p>
                        <p class="mb-1">
                            <a href="${linkCarnet}" target="_blank" class="${carnetClass}">📄 Carnet de Santé</a>
                        </p>
                        <p>
                            <a href="${linkFiche}" target="_blank" class="${ficheClass}">📄 Fiche Sanitaire Liaison</a>
                        </p>
                    </div>
                    <div class="md:col-span-2 border-t pt-2 mt-2">
                        <p class="text-gray-500 uppercase text-xs font-bold">Responsable Principal</p>
                        <p>${child.resp1_nom || ''} - ${child.resp1_tel || ''}</p>
                    </div>
                </div>
            `;
            list.appendChild(card);
        });
    }

    let timerInterval;
    function startTimer() {
        let timeLeft = 15;
        const btn = document.getElementById('btn-goto-3');
        const display = document.getElementById('timer-display');
        
        btn.disabled = true;
        btn.classList.add('bg-gray-400', 'cursor-not-allowed');
        btn.classList.remove('bg-green-600', 'hover:bg-green-700');
        
        clearInterval(timerInterval);
        
        timerInterval = setInterval(() => {
            timeLeft--;
            display.textContent = timeLeft;
            btn.textContent = `Valider et Choisir Tarif (${timeLeft}s)`;
            
            if(timeLeft <= 0) {
                clearInterval(timerInterval);
                btn.disabled = false;
                btn.textContent = "Je valide ces informations";
                btn.classList.remove('bg-gray-400', 'cursor-not-allowed');
                btn.classList.add('bg-green-600', 'hover:bg-green-700');
                display.textContent = "0";
            }
        }, 1000);
    }

    document.getElementById('btn-back-1').addEventListener('click', () => {
        step2.classList.add('hidden');
        step1.classList.remove('hidden');
        clearInterval(timerInterval);
    });

    document.getElementById('btn-goto-3').addEventListener('click', () => {
        renderPricing();
        step2.classList.add('hidden');
        step3.classList.remove('hidden');
        window.scrollTo(0,0);
    });

    // --- LOGIQUE ETAPE 3 (PRIX & REMISE) ---
    function renderPricing() {
        const list = document.getElementById('pricing-list');
        list.innerHTML = '';
        
        // Calcul du rang de l'enfant pour la remise
        // On commence par le nombre d'inscrits existants
        let currentRankCounter = globalData.camp.nb_deja_inscrits_famille; 
        const remisePercent = parseFloat(globalData.camp.remise_fratrie);

        selectedChildrenIds.forEach((id, index) => {
            const child = globalData.enfants.find(c => c.id === id);
            
            // On incrémente le rang pour cet enfant
            currentRankCounter++; 
            
            // Logique Remise : Appliquée si c'est le 2ème enfant ou plus (Total historique + courant)
            // SI Camp dit "Remise fratrie", on l'applique dès le 2e enfant.
            const isDiscounted = (remisePercent > 0 && currentRankCounter > 1);
            
            // Création Select Tarif
            const selectId = `tarif-select-${id}`;
            const container = document.createElement('div');
            container.className = "bg-gray-50 p-4 rounded border flex flex-col md:flex-row justify-between items-center gap-4";
            
            let options = `<option value="">-- Choisir tarif --</option>`;
            globalData.tarifs.forEach(t => {
                let prix = parseFloat(t.prix);
                let labelPrix = `${prix} €`;
                
                // Calcul affichage option
                if(t.montant_libre == 1) {
                    options += `<option value="${t.id}" data-prix="0" data-libre="1">${t.nom} (Montant Libre)</option>`;
                } else {
                    options += `<option value="${t.id}" data-prix="${prix}" data-libre="0">${t.nom} - ${labelPrix}</option>`;
                }
            });

            // Affichage Info Remise
            let discountBadge = '';
            if(isDiscounted) {
                discountBadge = `<div class="text-xs text-green-700 font-bold bg-green-100 px-2 py-1 rounded mt-1">
                    🎉 Remise Fratrie (-${remisePercent}%) appliquée
                </div>`;
            } else if (currentRankCounter === 1 && remisePercent > 0) {
                 discountBadge = `<div class="text-xs text-gray-500 italic mt-1">Plein tarif (1er enfant)</div>`;
            }

            container.innerHTML = `
                <div>
                    <span class="font-bold text-lg">${child.prenom}</span>
                    ${discountBadge}
                </div>
                <div class="w-full md:w-1/2">
                    <select id="${selectId}" class="w-full border p-2 rounded tarif-selector" data-child="${id}" data-discount="${isDiscounted ? remisePercent : 0}">
                        ${options}
                    </select>
                    <div id="libre-container-${id}" class="hidden mt-2">
                        <input type="number" placeholder="Montant (€)" class="border p-2 rounded w-full libre-input" data-child="${id}">
                    </div>
                </div>
                <div class="text-right w-32">
                    <div id="price-display-${id}" class="font-bold text-xl text-gray-800">0.00 €</div>
                </div>
            `;
            list.appendChild(container);

            // Listener Change
            const select = container.querySelector('select');
            select.addEventListener('change', (e) => handleTarifChange(e, id));
            
            // Listener Input Libre
            const inputLibre = container.querySelector('.libre-input');
            inputLibre.addEventListener('input', () => calculateGrandTotal());
        });
    }

    function handleTarifChange(e, childId) {
        const select = e.target;
        const option = select.options[select.selectedIndex];
        const libreDiv = document.getElementById(`libre-container-${childId}`);
        
        if(!select.value) {
            selectedTarifs[childId] = null;
            libreDiv.classList.add('hidden');
            updateChildPriceDisplay(childId, 0, 0);
            return;
        }

        const isLibre = option.dataset.libre == "1";
        const basePrice = parseFloat(option.dataset.prix);
        const discountPercent = parseFloat(select.dataset.discount);

        if(isLibre) {
            libreDiv.classList.remove('hidden');
            selectedTarifs[childId] = { id: select.value, price: 0, isLibre: true }; // Prix défini par input
        } else {
            libreDiv.classList.add('hidden');
            
            // Calcul Prix Remisé
            let finalPrice = basePrice;
            if(discountPercent > 0) {
                finalPrice = basePrice * (1 - (discountPercent / 100));
            }
            
            selectedTarifs[childId] = { id: select.value, price: finalPrice, basePrice: basePrice, discount: discountPercent };
            updateChildPriceDisplay(childId, finalPrice, basePrice, discountPercent);
        }
        calculateGrandTotal();
    }

    function updateChildPriceDisplay(childId, final, base=0, discount=0) {
        const el = document.getElementById(`price-display-${childId}`);
        if(discount > 0 && base > 0) {
            el.innerHTML = `
                <span class="text-xs text-gray-400 line-through block">${base.toFixed(2)}€</span>
                <span class="text-green-600">${final.toFixed(2)}€</span>
            `;
        } else {
            el.innerHTML = `<span class="text-gray-800">${final.toFixed(2)}€</span>`;
        }
    }

    function calculateGrandTotal() {
        let total = 0;
        let allValid = true;

        selectedChildrenIds.forEach(id => {
            const select = document.getElementById(`tarif-select-${id}`);
            if(!select.value) { allValid = false; return; }
            
            const option = select.options[select.selectedIndex];
            const isLibre = option.dataset.libre == "1";

            if(isLibre) {
                const input = document.querySelector(`.libre-input[data-child="${id}"]`);
                const val = parseFloat(input.value);
                if(!val || val <= 0) { allValid = false; return; }
                total += val;
                // Update object for submission
                selectedTarifs[id] = { id: select.value, price: val, isLibre: true };
                updateChildPriceDisplay(id, val);
            } else {
                // Prix déjà calculé dans selectedTarifs lors du change
                if(selectedTarifs[id]) total += selectedTarifs[id].price;
            }
        });

        document.getElementById('grand-total').textContent = total.toFixed(2) + ' €';
        document.getElementById('btn-pay').disabled = !allValid || total <= 0;
        if(!allValid) document.getElementById('btn-pay').classList.add('opacity-50', 'cursor-not-allowed');
        else document.getElementById('btn-pay').classList.remove('opacity-50', 'cursor-not-allowed');
    }

    document.getElementById('btn-back-2').addEventListener('click', () => {
        step3.classList.add('hidden');
        step2.classList.remove('hidden');
    });

    // PAIEMENT (Boucle d'envoi API)
    document.getElementById('btn-pay').addEventListener('click', async () => {
        if(!confirm("Confirmer le paiement et l'inscription ?")) return;
        
        const btn = document.getElementById('btn-pay');
        btn.disabled = true;
        btn.textContent = "Traitement en cours...";

        let errors = [];
        
        // On envoie une requête PAR enfant (pour utiliser l'API existante process_inscription)
        // Note: L'idéal serait de refaire process_inscription pour accepter un array, 
        // mais pour respecter la structure existante, on boucle.
        
        for (const childId of selectedChildrenIds) {
            const tarifData = selectedTarifs[childId];
            
            try {
                const res = await fetch('api/process_inscription.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({
                        camp_id: globalData.camp.id,
                        child_id: childId,
                        tarif_id: tarifData.id,
                        amount_paid: tarifData.price, // Prix remisé envoyé
                        payment_method: 'CB_STRIPE_TEST'
                    })
                });
                const json = await res.json();
                if(json.error) throw new Error(json.error);
                
            } catch (e) {
                errors.push(`Enfant ID ${childId}: ${e.message}`);
            }
        }

        if(errors.length > 0) {
            alert("Certaines inscriptions ont échoué :\n" + errors.join("\n"));
            btn.disabled = false;
            btn.textContent = "Réessayer";
        } else {
            alert("Toutes les inscriptions sont validées !");
            window.location.href = 'mes_camps.php'; // Ou page de succès globale
        }
    });

});
</script>

<?php require_once 'partials/footer.php'; ?>