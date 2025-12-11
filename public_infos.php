<?php
require_once 'partials/header.php';

// Sécurité
if (!isset($_SESSION['user']) || !$_SESSION['user']['is_directeur']) {
    header('Location: index.php');
    exit;
}
?>

<title>Gestion des Organisateurs - ColoMap</title>

<main class="container mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-12">
    <div class="mb-8">
        <a href="organisateurs.php" class="inline-flex items-center gap-2 text-gray-600 hover:text-gray-900 font-medium">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-5 h-5"><path fill-rule="evenodd" d="M17 10a.75.75 0 0 1-.75.75H5.612l4.158 3.96a.75.75 0 1 1-1.04 1.08l-5.5-5.25a.75.75 0 0 1 0-1.08l5.5-5.25a.75.75 0 1 1 1.04 1.08L5.612 9.25H16.25A.75.75 0 0 1 17 10Z" clip-rule="evenodd" /></svg>
            Retour
        </a>
    </div>

    <div class="bg-white p-8 rounded-xl shadow-lg border">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-gray-900">Mes Organismes</h1>
            <a href="create_organisateur.php" class="bg-blue-600 text-white font-bold py-2 px-4 rounded-lg hover:bg-blue-700">
                Créer un organisme
            </a>
        </div>
        
        <div id="organisateurs-list" class="space-y-4">
            <!-- La liste des organismes sera chargée ici -->
        </div>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', async function() {
    const listContainer = document.getElementById('organisateurs-list');
    listContainer.innerHTML = '<p class="text-center text-gray-500 py-4">Chargement...</p>';
    try {
        const response = await fetch('api/get_organisateurs.php');
        if (!response.ok) throw new Error('Erreur réseau');
        const organisateurs = await response.json();
        
        listContainer.innerHTML = '';
        if(organisateurs.length === 0) {
            listContainer.innerHTML = '<p class="text-center text-gray-500 py-4">Vous n\'avez encore créé aucun organisme.</p>';
            return;
        }

        organisateurs.forEach(org => {
            const campsList = org.camps.length > 0 
                ? org.camps.map(camp => `<li class="truncate">${camp}</li>`).join('') 
                : '<li class="text-gray-400">Aucun camp associé.</li>';

            const card = `
                <div class="bg-gray-50 p-4 rounded-lg border">
                    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                        <div>
                            <p class="font-bold text-lg text-gray-800">${org.nom}</p>
                            <a href="mailto:${org.mail}" class="text-sm text-blue-600 hover:underline">${org.mail}</a>
                        </div>
                        <div class="text-left md:text-right">
                             <p class="font-bold text-2xl text-green-600">${org.portefeuille}€</p>
                             <p class="text-xs text-gray-500">Portefeuille</p>
                        </div>
                    </div>
                    <div class="mt-4 border-t pt-4">
                        <h4 class="font-semibold text-sm mb-2">Camps associés :</h4>
                        <ul class="list-disc list-inside text-sm text-gray-700 space-y-1">
                           ${campsList}
                        </ul>
                    </div>
                </div>
            `;
            listContainer.innerHTML += card;
        });
    } catch (error) {
        listContainer.innerHTML = `<p class="text-red-500 font-bold text-center py-4">${error.message}</p>`;
    }
});
</script>
</body>
</html>
