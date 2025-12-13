<?php
require_once 'partials/header.php';

// SÉCURITÉ : Accès réservé aux directeurs
if (!isset($_SESSION['user']) || !$_SESSION['user']['is_directeur']) {
    header('Location: index.php');
    exit;
}
?>

<title>Mes Camps - Espace Organisateur</title>

<div id="delete-confirm-modal" class="fixed inset-0 bg-gray-900 bg-opacity-50 backdrop-blur-xl flex items-center justify-center z-50 hidden">
    <div class="bg-white rounded-2xl shadow-2xl p-8 max-w-md w-full text-center transform transition-all">
        <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-red-100 mb-5">
            <svg class="h-10 w-10 text-red-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
            </svg>
        </div>
        
        <h2 class="text-2xl font-bold mb-2 text-gray-900">Confirmation Requise</h2>
        <p class="text-gray-600 mb-6">Êtes-vous sûr de vouloir supprimer ce camp ?</p>
        
        <div class="bg-gray-100 rounded-lg p-4 text-left mb-6 border border-gray-200">
            <p class="text-xs text-gray-500 uppercase font-bold">Séjour sélectionné</p>
            <p id="modal-camp-name" class="font-bold text-lg text-gray-800 mt-1"></p>
            <p class="text-xs text-gray-500 mt-2">ID: <span id="modal-camp-id" class="font-mono"></span></p>
        </div>
        
        <p class="text-sm text-red-600 font-semibold mb-6">⚠️ Cette action est irréversible. Toutes les inscriptions associées seront perdues.</p>

        <div class="flex flex-col gap-3">
            <button id="confirm-delete-button" disabled class="w-full bg-red-600 hover:bg-red-700 text-white font-bold py-3 px-4 rounded-lg transition-all duration-300 disabled:bg-red-300 disabled:cursor-not-allowed shadow-md">
                Patientez...
            </button>
            <button id="cancel-delete-button" class="w-full bg-white border border-gray-300 text-gray-700 font-bold py-3 px-4 rounded-lg hover:bg-gray-50 transition-colors">
                Annuler
            </button>
        </div>
    </div>
</div>

<main class="container mx-auto max-w-5xl px-4 sm:px-6 lg:px-8 py-12">
    
    <div class="mb-8">
        <a href="organisateurs.php" class="inline-flex items-center gap-2 text-gray-600 hover:text-blue-600 font-medium transition-colors">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-5 h-5"><path fill-rule="evenodd" d="M17 10a.75.75 0 0 1-.75.75H5.612l4.158 3.96a.75.75 0 1 1-1.04 1.08l-5.5-5.25a.75.75 0 0 1 0-1.08l5.5-5.25a.75.75 0 1 1 1.04 1.08L5.612 9.25H16.25A.75.75 0 0 1 17 10Z" clip-rule="evenodd" /></svg>
            Retour à l'espace organisateur
        </a>
    </div>

    <div class="bg-white p-8 rounded-2xl shadow-xl border border-gray-100">
        <div class="flex flex-col md:flex-row justify-between items-center mb-8 gap-4 border-b border-gray-100 pb-6">
            <div>
                <h1 class="text-3xl font-extrabold text-gray-900">Mes Séjours</h1>
                <p class="text-gray-500 mt-1">Gérez vos offres, suivez les inscriptions et analysez vos performances.</p>
            </div>
            <a href="create_camp.php" class="bg-blue-600 text-white font-bold py-3 px-6 rounded-xl shadow-lg hover:bg-blue-700 hover:shadow-xl transform hover:-translate-y-0.5 transition-all duration-200 flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-5 h-5"><path d="M10.75 4.75a.75.75 0 0 0-1.5 0v4.5h-4.5a.75.75 0 0 0 0 1.5h4.5v4.5a.75.75 0 0 0 1.5 0v-4.5h4.5a.75.75 0 0 0 0-1.5h-4.5v-4.5Z" /></svg>
                Créer un séjour
            </a>
        </div>
        
        <div id="my-camps-list" class="space-y-4">
            </div>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const myCampsList = document.getElementById('my-camps-list');
    
    // Éléments du modal
    const modal = document.getElementById('delete-confirm-modal');
    const modalCampName = document.getElementById('modal-camp-name');
    const modalCampId = document.getElementById('modal-camp-id');
    const confirmDeleteButton = document.getElementById('confirm-delete-button');
    const cancelDeleteButton = document.getElementById('cancel-delete-button');

    let campToDeleteId = null;
    let countdownInterval = null;

    // --- CHARGEMENT DES CAMPS ---
    async function fetchMyCamps() {
        myCampsList.innerHTML = `
            <div class="text-center py-12">
                <div class="loader inline-block mb-3"></div>
                <p class="text-gray-500 font-medium">Chargement de vos séjours...</p>
            </div>`;
            
        try {
            const response = await fetch('api/get_my_camps.php');
            if (!response.ok) throw new Error('Erreur réseau');
            const camps = await response.json();
            
            myCampsList.innerHTML = ''; 
            
            if (camps.length === 0) {
                myCampsList.innerHTML = `
                    <div class="text-center py-16 bg-gray-50 rounded-xl border-2 border-dashed border-gray-200">
                        <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" /></svg>
                        <h3 class="mt-2 text-sm font-semibold text-gray-900">Aucun séjour</h3>
                        <p class="mt-1 text-sm text-gray-500">Commencez par créer votre premier camp.</p>
                    </div>`;
                return;
            }

            camps.forEach(camp => {
                // Construction des liens SECURISÉS par TOKEN
                const hasToken = (camp.token && camp.token !== "undefined" && camp.token !== null);
                
                // 1. Lien "Voir" (Public)
                const viewLink = hasToken ? `camp_details.php?t=${camp.token}` : '#';
                const viewClass = hasToken ? "text-blue-600 hover:text-blue-800" : "text-gray-400 cursor-not-allowed";
                const viewAttr = hasToken ? 'target="_blank"' : 'onclick="alert(\'Ce camp n\\\'a pas de token valide.\'); return false;"';

                // 2. Lien "Gérer" (Dashboard)
                const dashLink = hasToken ? `gestion_camp.php?t=${camp.token}` : '#';
                const dashClass = hasToken 
                    ? "bg-purple-100 text-purple-700 hover:bg-purple-200 hover:shadow-md" 
                    : "bg-gray-100 text-gray-400 cursor-not-allowed";
                
                // 3. Lien "Modifier" (CORRECTIF ICI : utilise le token)
                const editLink = hasToken ? `edit_camp.php?t=${camp.token}` : '#';
                const editClass = hasToken ? "text-gray-600 hover:text-blue-600 hover:bg-blue-50" : "text-gray-400 cursor-not-allowed";

                // Badge de statut
                let statusBadge = '';
                if(camp.valide == 1) statusBadge = '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">✅ En ligne</span>';
                else if(camp.refuse == 1) statusBadge = '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">❌ Refusé</span>';
                else statusBadge = '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">⏳ En attente</span>';

                const card = document.createElement('div');
                card.className = "group bg-white rounded-xl border border-gray-200 p-5 hover:shadow-lg transition-all duration-300 flex flex-col md:flex-row items-start md:items-center justify-between gap-4";
                card.id = `camp-${camp.id}`;
                
                card.innerHTML = `
                    <div class="flex items-center gap-4">
                        <div class="h-16 w-16 flex-shrink-0 bg-gray-100 rounded-lg overflow-hidden">
                            <img src="${camp.image_url || 'https://placehold.co/100'}" class="h-full w-full object-cover">
                        </div>
                        <div>
                            <div class="flex items-center gap-2 flex-wrap">
                                <h3 class="text-lg font-bold text-gray-900 group-hover:text-blue-600 transition-colors">${camp.nom}</h3>
                                ${statusBadge}
                            </div>
                            <div class="text-xs text-gray-500 mt-1 flex flex-wrap gap-x-4 gap-y-1">
                                <span>ID: ${camp.id}</span>
                                <span class="hidden sm:inline">|</span>
                                <span class="font-mono bg-gray-100 px-1 rounded text-gray-600">Token: ${camp.token || 'N/A'}</span>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center gap-3 w-full md:w-auto mt-2 md:mt-0 border-t md:border-t-0 pt-3 md:pt-0">
                        <a href="${viewLink}" ${viewAttr} class="${viewClass} text-sm font-semibold px-3 py-2 rounded-lg transition-colors flex items-center gap-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                            Voir
                        </a>

                        <a href="${editLink}" class="${editClass} text-sm font-semibold px-3 py-2 rounded-lg transition-colors">
                            Modifier
                        </a>

                        <a href="${dashLink}" class="${dashClass} px-4 py-2 rounded-lg text-sm font-bold transition-all shadow-sm flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" /></svg>
                            Gérer (Dash)
                        </a>

                        <button class="delete-button text-red-500 hover:text-red-700 hover:bg-red-50 p-2 rounded-lg transition-colors ml-1" 
                                data-id="${camp.id}" 
                                data-name="${camp.nom}" 
                                title="Supprimer">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                        </button>
                    </div>`;
                
                myCampsList.appendChild(card);
            });
            
            // Attacher les events
            document.querySelectorAll('.delete-button').forEach(btn => btn.addEventListener('click', handleDeleteClick));

        } catch (error) {
            myCampsList.innerHTML = `<p class="text-red-500 text-center font-bold py-10 bg-red-50 rounded-xl">Erreur : Impossible de charger vos camps.</p>`;
        }
    }

    // --- GESTION SUPPRESSION ---
    function handleDeleteClick(e) {
        const btn = e.currentTarget;
        campToDeleteId = btn.dataset.id;
        
        modalCampName.textContent = btn.dataset.name;
        modalCampId.textContent = campToDeleteId;
        
        modal.classList.remove('hidden');
        
        // Sécurité : Délai avant activation du bouton
        let countdown = 3;
        confirmDeleteButton.disabled = true;
        confirmDeleteButton.textContent = `Confirmer la suppression (${countdown})`;
        confirmDeleteButton.classList.add('bg-red-300');
        confirmDeleteButton.classList.remove('bg-red-600');

        clearInterval(countdownInterval);
        countdownInterval = setInterval(() => {
            countdown--;
            if (countdown <= 0) {
                clearInterval(countdownInterval);
                confirmDeleteButton.disabled = false;
                confirmDeleteButton.textContent = 'Confirmer la suppression';
                confirmDeleteButton.classList.remove('bg-red-300');
                confirmDeleteButton.classList.add('bg-red-600');
            } else {
                confirmDeleteButton.textContent = `Confirmer la suppression (${countdown})`;
            }
        }, 1000);
    }

    function closeModal() {
        modal.classList.add('hidden');
        clearInterval(countdownInterval);
        campToDeleteId = null;
    }

    confirmDeleteButton.addEventListener('click', async function() {
        if (!campToDeleteId) return;
        
        const originalText = this.textContent;
        this.disabled = true;
        this.textContent = 'Suppression en cours...';

        try {
            const response = await fetch('api/delete_camp.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: campToDeleteId })
            });
            
            const res = await response.json();
            if (res.error) throw new Error(res.error);
            
            // Animation de suppression
            const el = document.getElementById(`camp-${campToDeleteId}`);
            el.style.transition = "all 0.5s ease";
            el.style.opacity = "0";
            el.style.transform = "translateX(50px)";
            setTimeout(() => {
                el.remove();
                if(myCampsList.children.length === 0) fetchMyCamps(); // Recharger si vide
            }, 500);
            
            closeModal();
        } catch (error) {
            alert('Erreur : ' + error.message);
            this.disabled = false;
            this.textContent = originalText;
        }
    });

    cancelDeleteButton.addEventListener('click', closeModal);
    
    // Fermer si on clique en dehors du modal
    modal.addEventListener('click', (e) => {
        if (e.target === modal) closeModal();
    });

    fetchMyCamps();
});
</script>

<?php 
if (file_exists('partials/footer.php')) {
    include 'partials/footer.php';
} else {
    echo "</body></html>";
}
?>