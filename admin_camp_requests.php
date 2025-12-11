<?php
require_once 'partials/header.php';

// SÉCURITÉ
if (!isset($_SESSION['user']) || !($_SESSION['user']['is_admin'] ?? false)) {
    header('Location: index.php');
    exit;
}
?>

<title>Demandes d'Ajout de Camp - Admin</title>

<main class="container mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-12">
    <div class="mb-8">
        <a href="admin.php" class="inline-flex items-center gap-2 text-gray-600 hover:text-gray-900 font-medium">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-5 h-5"><path fill-rule="evenodd" d="M17 10a.75.75 0 0 1-.75.75H5.612l4.158 3.96a.75.75 0 1 1-1.04 1.08l-5.5-5.25a.75.75 0 0 1 0-1.08l5.5-5.25a.75.75 0 1 1 1.04 1.08L5.612 9.25H16.25A.75.75 0 0 1 17 10Z" clip-rule="evenodd" /></svg>
            Retour au panneau d'administration
        </a>
    </div>
    
    <div class="bg-white p-6 rounded-xl shadow-lg border">
        <h1 class="text-2xl font-bold mb-4">Camps en attente d'approbation</h1>
        <div id="camp-requests-list" class="space-y-4"></div>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const listContainer = document.getElementById('camp-requests-list');

    async function fetchCampRequests() {
        listContainer.innerHTML = '<p class="text-gray-500 text-center py-4">Chargement...</p>';
        try {
            const response = await fetch('api/get_camp_requests.php');
            if (!response.ok) throw new Error('Erreur réseau.');
            const requests = await response.json();

            listContainer.innerHTML = '';
            if (requests.length === 0) {
                listContainer.innerHTML = '<p class="text-gray-500 text-center py-4">Aucune demande de camp en attente.</p>';
                return;
            }

            requests.forEach(camp => {
                const card = `
                    <div class="request-card bg-gray-50 p-4 rounded-lg border" id="camp-card-${camp.id}">
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="font-bold text-lg">${camp.nom}</p>
                                <p class="text-sm text-gray-600">${camp.ville}, ${camp.code_postal}</p>
                                <p class="text-xs text-gray-400 mt-1">Soumis par: <span class="font-medium">${camp.organisateur_nom || 'N/A'}</span></p>
                            </div>
                            <div class="flex items-center gap-3">
                                <a href="camp_details.php?id=${camp.id}" target="_blank" class="text-xs bg-gray-200 hover:bg-gray-300 px-3 py-2 rounded-lg font-semibold">Prévisualiser</a>
                                <button class="process-camp-button bg-green-500 hover:bg-green-600 text-white font-bold py-2 px-3 rounded-lg" data-action="approve" data-campid="${camp.id}">Approuver</button>
                                <button class="process-camp-button bg-red-500 hover:bg-red-600 text-white font-bold py-2 px-3 rounded-lg" data-action="deny" data-campid="${camp.id}">Refuser</button>
                            </div>
                        </div>
                    </div>`;
                listContainer.innerHTML += card;
            });

            document.querySelectorAll('.process-camp-button').forEach(button => {
                button.addEventListener('click', handleProcessCampClick);
            });

        } catch (error) {
            listContainer.innerHTML = `<p class="text-red-500 font-bold text-center py-4">${error.message}</p>`;
        }
    }

    async function handleProcessCampClick(event) {
        const button = event.currentTarget;
        const action = button.dataset.action;
        const campId = button.dataset.campid;
        
        if (action === 'deny' && !confirm('Êtes-vous sûr de vouloir refuser et supprimer ce camp ?')) {
            return;
        }

        button.parentElement.querySelectorAll('button').forEach(btn => btn.disabled = true);
        button.textContent = '...';

        try {
            const response = await fetch('api/process_camp_request.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ campId, action })
            });

            if (!response.ok) throw new Error((await response.json()).error || 'Erreur.');
            
            const cardToRemove = document.getElementById(`camp-card-${campId}`);
            if (cardToRemove) cardToRemove.remove();

        } catch (error) {
            alert('Erreur: ' + error.message);
            button.parentElement.querySelectorAll('button').forEach(btn => btn.disabled = false);
            button.textContent = action === 'approve' ? 'Approuver' : 'Refuser';
        }
    }

    fetchCampRequests();
});
</script>

</body>
</html>
