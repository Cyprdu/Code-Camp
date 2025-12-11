<?php
require_once 'partials/header.php';

// SÉCURITÉ : On vérifie que l'utilisateur est connecté ET qu'il est directeur.
if (!isset($_SESSION['user']) || !$_SESSION['user']['is_directeur']) {
    header('Location: index.php');
    exit;
}
?>

<title>Mes Camps - Espace Organisateur</title>

<!-- Popup de confirmation de suppression -->
<div id="delete-confirm-modal" class="fixed inset-0 bg-gray-900 bg-opacity-50 backdrop-blur-xl flex items-center justify-center z-50 hidden">
    <div class="bg-white rounded-2xl shadow-2xl p-8 max-w-md w-full text-center transform transition-all" id="modal-content">
        <!-- Icône d'avertissement -->
        <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-red-100 mb-5">
            <svg class="h-10 w-10 text-red-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
            </svg>
        </div>
        <h2 class="text-2xl font-bold mb-2 text-gray-900">Confirmation Requise</h2>
        <p class="text-gray-600 mb-6">Vous êtes sur le point de supprimer définitivement le camp :</p>
        
        <div class="bg-gray-100 rounded-lg p-3 text-left mb-6">
            <p class="text-sm text-gray-500">Nom du camp</p>
            <p id="modal-camp-name" class="font-bold text-lg text-gray-800"></p>
            <p class="text-sm text-gray-500 mt-2">ID du camp</p>
            <p id="modal-camp-id" class="font-mono text-xs text-gray-800"></p>
        </div>
        
        <p class="text-sm text-red-700 font-semibold mb-6">Cette action est irréversible. Toutes les données associées seront perdues.</p>

        <div class="flex flex-col gap-3">
            <button id="confirm-delete-button" disabled class="w-full bg-red-600 text-white font-bold py-3 px-4 rounded-lg transition-all duration-300 disabled:bg-red-300 disabled:cursor-not-allowed">
                <!-- Le texte du bouton sera mis à jour par JS -->
            </button>
            <button id="cancel-delete-button" class="w-full bg-gray-200 text-gray-800 font-bold py-3 px-4 rounded-lg hover:bg-gray-300">
                Annuler
            </button>
        </div>
    </div>
</div>


<main class="container mx-auto max-w-4xl px-4 sm:px-6 lg:px-8 py-12">
    
    <div class="mb-8">
        <a href="organisateurs.php" class="inline-flex items-center gap-2 text-gray-600 hover:text-gray-900 font-medium">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-5 h-5"><path fill-rule="evenodd" d="M17 10a.75.75 0 0 1-.75.75H5.612l4.158 3.96a.75.75 0 1 1-1.04 1.08l-5.5-5.25a.75.75 0 0 1 0-1.08l5.5-5.25a.75.75 0 1 1 1.04 1.08L5.612 9.25H16.25A.75.75 0 0 1 17 10Z" clip-rule="evenodd" /></svg>
            Retour à l'espace organisateur
        </a>
    </div>

    <div class="bg-white p-8 rounded-xl shadow-lg border">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-gray-900">Gérer mes camps</h1>
            <a href="create_camp.php" class="bg-blue-600 text-white font-bold py-2 px-4 rounded-lg transition-all duration-300 hover:text-white hover:bg-gradient-to-r hover:from-blue-500 hover:via-purple-500 hover:to-pink-500">
                Ajouter un camp
            </a>
        </div>
        
        <div id="my-camps-list" class="space-y-4">
            <!-- La liste des camps sera chargée ici par JavaScript -->
        </div>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const myCampsList = document.getElementById('my-camps-list');
    const modal = document.getElementById('delete-confirm-modal');
    const modalCampName = document.getElementById('modal-camp-name');
    const modalCampId = document.getElementById('modal-camp-id');
    const confirmDeleteButton = document.getElementById('confirm-delete-button');
    const cancelDeleteButton = document.getElementById('cancel-delete-button');

    let campToDeleteId = null;
    let countdownInterval = null;

    async function fetchMyCamps() {
        myCampsList.innerHTML = '<p class="text-gray-500 text-center py-4">Chargement de vos camps...</p>';
        try {
            const response = await fetch('api/get_my_camps.php');
            if (!response.ok) throw new Error('Erreur réseau');
            const camps = await response.json();
            
            myCampsList.innerHTML = ''; 
            if (camps.length === 0) {
                myCampsList.innerHTML = '<p class="text-gray-500 text-center py-4">Vous n\'avez encore ajouté aucun camp.</p>';
                return;
            }

            camps.forEach(camp => {
                const campElement = document.createElement('div');
                campElement.className = "camp-item flex items-center justify-between p-4 bg-gray-50 rounded-lg border hover:bg-gray-100 transition-colors";
                campElement.id = `camp-${camp.id}`;
                campElement.innerHTML = `
                    <div>
                        <p class="font-bold text-gray-800">${camp.nom}</p>
                        <span class="text-xs text-gray-500">ID: ${camp.id}</span>
                    </div>
                    <div class="flex items-center gap-4">
                        <a href="camp_details.php?id=${camp.id}" class="text-blue-600 hover:underline text-sm font-medium">Voir</a>
                        <a href="edit_camp.php?id=${camp.id}" class="text-yellow-600 hover:underline text-sm font-medium">Modifier</a>
                        <button class="delete-button text-red-600 hover:underline text-sm font-medium" data-id="${camp.id}" data-name="${camp.nom}">Supprimer</button>
                    </div>`;
                myCampsList.appendChild(campElement);
            });
            
            document.querySelectorAll('.delete-button').forEach(button => {
                button.addEventListener('click', handleDeleteClick);
            });

        } catch (error) {
            myCampsList.innerHTML = `<p class="text-red-500 text-center font-bold py-4">Erreur lors du chargement de vos camps.</p>`;
        }
    }

    function handleDeleteClick(event) {
        const button = event.currentTarget;
        campToDeleteId = button.dataset.id;
        const campName = button.dataset.name;

        modalCampName.textContent = campName;
        modalCampId.textContent = campToDeleteId;
        
        modal.classList.remove('hidden');
        
        let countdown = 10;
        confirmDeleteButton.disabled = true;
        confirmDeleteButton.innerHTML = `Valider la suppression (${countdown})`;

        countdownInterval = setInterval(() => {
            countdown--;
            confirmDeleteButton.innerHTML = `Valider la suppression (${countdown})`;
            if (countdown <= 0) {
                clearInterval(countdownInterval);
                confirmDeleteButton.disabled = false;
                confirmDeleteButton.innerHTML = 'Valider la suppression';
            }
        }, 1000);
    }

    function closeModal() {
        modal.classList.add('hidden');
        clearInterval(countdownInterval);
        campToDeleteId = null;
    }

    async function confirmDelete() {
        if (!campToDeleteId) return;

        confirmDeleteButton.disabled = true;
        confirmDeleteButton.textContent = 'Suppression en cours...';

        try {
            const response = await fetch('api/delete_camp.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: campToDeleteId })
            });
            
            const result = await response.json();

            if (!response.ok) {
                throw new Error(result.error || 'Erreur inconnue.');
            }
            
            const campElementToRemove = document.getElementById(`camp-${campToDeleteId}`);
            if(campElementToRemove) {
                campElementToRemove.remove();
            }
            
            closeModal();

        } catch (error) {
            alert('Erreur: ' + error.message);
            confirmDeleteButton.disabled = false;
            confirmDeleteButton.textContent = 'Valider la suppression';
        }
    }

    cancelDeleteButton.addEventListener('click', closeModal);
    confirmDeleteButton.addEventListener('click', confirmDelete);

    fetchMyCamps();
});
</script>

</body>
</html>
