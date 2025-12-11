<?php
require_once 'partials/header.php';

// SÉCURITÉ : On vérifie que l'utilisateur est connecté ET qu'il est admin.
if (!isset($_SESSION['user']) || !($_SESSION['user']['is_admin'] ?? false)) {
    header('Location: index.php');
    exit;
}
?>

<title>Historique des Camps Refusés - Admin</title>

<main class="container mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-12">
    <div class="mb-8">
        <a href="admin.php" class="inline-flex items-center gap-2 text-gray-600 hover:text-gray-900 font-medium">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-5 h-5"><path fill-rule="evenodd" d="M17 10a.75.75 0 0 1-.75.75H5.612l4.158 3.96a.75.75 0 1 1-1.04 1.08l-5.5-5.25a.75.75 0 0 1 0-1.08l5.5-5.25a.75.75 0 1 1 1.04 1.08L5.612 9.25H16.25A.75.75 0 0 1 17 10Z" clip-rule="evenodd" /></svg>
            Retour au panneau d'administration
        </a>
    </div>
    
    <div class="bg-white p-6 rounded-xl shadow-lg border">
        <h1 class="text-2xl font-bold mb-4">Historique des Camps Refusés</h1>
        <div id="refused-camps-list" class="space-y-3">
            <!-- La liste sera chargée ici par JavaScript -->
        </div>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', async function() {
    const listContainer = document.getElementById('refused-camps-list');
    listContainer.innerHTML = '<p class="text-center text-gray-500 py-4">Chargement de l\'historique...</p>';
    try {
        const response = await fetch('api/get_refused_camps.php');
        if (!response.ok) throw new Error('Erreur réseau lors de la récupération des camps refusés.');
        const camps = await response.json();
        
        listContainer.innerHTML = '';
        if(camps.length === 0) {
            listContainer.innerHTML = '<p class="text-center text-gray-500 py-4">Aucun camp n\'a encore été refusé.</p>';
            return;
        }

        camps.forEach(camp => {
            const campCard = `
                <div class="bg-red-50 p-3 rounded-lg border border-red-200 flex justify-between items-center">
                    <div>
                        <p class="font-semibold text-gray-800">${camp.nom}</p>
                        <p class="text-sm text-red-800">${camp.ville}</p>
                        <p class="text-xs text-gray-500 mt-1">Soumis par : ${camp.organisateur_nom}</p>
                    </div>
                    <a href="camp_details.php?id=${camp.id}" target="_blank" class="text-xs bg-gray-200 hover:bg-gray-300 text-gray-700 font-semibold px-2 py-1 rounded">Voir la fiche</a>
                </div>
            `;
            listContainer.innerHTML += campCard;
        });
    } catch (error) {
        listContainer.innerHTML = `<p class="text-red-500 font-bold text-center py-4">${error.message}</p>`;
    }
});
</script>

</body>
</html>
