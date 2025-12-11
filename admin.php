<?php
require_once 'partials/header.php';

// SÉCURITÉ : On vérifie que l'utilisateur est connecté ET qu'il est admin.
if (!isset($_SESSION['user']) || !($_SESSION['user']['is_admin'] ?? false)) {
    header('Location: index.php');
    exit;
}
?>

<title>Panneau d'Administration - ColoMap</title>

<main class="container mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-12">
    <h1 class="text-3xl font-bold text-gray-900 mb-8">Panneau d'Administration</h1>

    <!-- Grille de navigation des rubriques admin -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">

        <!-- Carte : Demandes d'accès Directeur -->
        <a href="admin_requests.php" class="relative bg-white p-6 rounded-xl shadow-lg border hover:border-blue-500 hover:ring-2 hover:ring-blue-200 transition-all cursor-pointer">
            <div class="flex items-center gap-4">
                <div class="flex-shrink-0 w-12 h-12 rounded-lg bg-blue-100 text-blue-600 flex items-center justify-center">
                    <svg class="w-6 h-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7.5v3m0 0v3m0-3h3m-3 0h-3m-2.25-4.125a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0ZM3 19.235v-.11a6.375 6.375 0 0 1 12.75 0v.109A12.318 12.318 0 0 1 9.374 21c-2.331 0-4.512-.645-6.374-1.766Z" /></svg>
                </div>
                <div>
                    <h3 class="font-bold text-lg">Demandes d'accès</h3>
                    <p class="text-sm text-gray-500">Traiter les demandes de directeurs.</p>
                </div>
                <span id="request-count-badge" class="absolute top-3 right-3 w-6 h-6 flex items-center justify-center bg-red-600 text-white text-xs font-bold rounded-full hidden"></span>
            </div>
        </a>

        <!-- Carte pour les demandes d'ajout de camp -->
        <a href="admin_camp_requests.php" class="relative bg-white p-6 rounded-xl shadow-lg border hover:border-yellow-500 hover:ring-2 hover:ring-yellow-200 transition-all cursor-pointer">
            <div class="flex items-center gap-4">
                <div class="flex-shrink-0 w-12 h-12 rounded-lg bg-yellow-100 text-yellow-600 flex items-center justify-center">
                    <svg class="w-6 h-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 8.25H7.5a2.25 2.25 0 0 0-2.25 2.25v9a2.25 2.25 0 0 0 2.25 2.25h9a2.25 2.25 0 0 0 2.25-2.25v-9a2.25 2.25 0 0 0-2.25-2.25H15m0-3-3-3m0 0-3 3m3-3V15" /></svg>
                </div>
                <div>
                    <h3 class="font-bold text-lg">Demandes de Camps</h3>
                    <p class="text-sm text-gray-500">Approuver les nouveaux camps.</p>
                </div>
                <span id="camp-request-count-badge" class="absolute top-3 right-3 w-6 h-6 flex items-center justify-center bg-red-600 text-white text-xs font-bold rounded-full hidden"></span>
            </div>
        </a>

        <!-- Carte : Ajouter un camp -->
        <a href="admin_add_camp.php" class="bg-white p-6 rounded-xl shadow-lg border hover:border-green-500 hover:ring-2 hover:ring-green-200 transition-all cursor-pointer">
            <div class="flex items-center gap-4">
                <div class="flex-shrink-0 w-12 h-12 rounded-lg bg-green-100 text-green-600 flex items-center justify-center">
                     <svg class="w-6 h-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 10.5v6m3-3H9m4.06-7.19-2.12-2.12a1.5 1.5 0 0 0-1.061-.44H4.5A2.25 2.25 0 0 0 2.25 6v12a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9a2.25 2.25 0 0 0-2.25-2.25h-5.379a1.5 1.5 0 0 1-1.06-.44Z" /></svg>
                </div>
                <div>
                    <h3 class="font-bold text-lg">Ajouter un camp</h3>
                    <p class="text-sm text-gray-500">Créer une fiche manuellement.</p>
                </div>
            </div>
        </a>
        
        <!-- Carte : Historique des directeurs acceptés -->
        <a href="admin_history_accepted.php" class="bg-white p-6 rounded-xl shadow-lg border hover:border-purple-500 hover:ring-2 hover:ring-purple-200 transition-all cursor-pointer">
            <div class="flex items-center gap-4">
                <div class="flex-shrink-0 w-12 h-12 rounded-lg bg-purple-100 text-purple-600 flex items-center justify-center">
                    <svg class="w-6 h-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                </div>
                <div>
                    <h3 class="font-bold text-lg">Directeurs Acceptés</h3>
                    <p class="text-sm text-gray-500">Voir l'historique des acceptations.</p>
                </div>
            </div>
        </a>
        
        <!-- Carte : Historique des demandes refusées -->
        <a href="admin_history_refused.php" class="bg-white p-6 rounded-xl shadow-lg border hover:border-red-500 hover:ring-2 hover:ring-red-200 transition-all cursor-pointer">
            <div class="flex items-center gap-4">
                <div class="flex-shrink-0 w-12 h-12 rounded-lg bg-red-100 text-red-600 flex items-center justify-center">
                     <svg class="w-6 h-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m9.75 9.75 4.5 4.5m0-4.5-4.5 4.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                </div>
                <div>
                    <h3 class="font-bold text-lg">Demandes Refusées</h3>
                    <p class="text-sm text-gray-500">Voir l'historique des refus.</p>
                </div>
            </div>
        </a>

    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Fonction réutilisable pour mettre à jour les compteurs
    async function updateRequestCount(apiUrl, badgeId) {
        try {
            const response = await fetch(apiUrl);
            if (!response.ok) return;
            const data = await response.json();
            const badge = document.getElementById(badgeId);
            if (badge && data.count > 0) {
                badge.textContent = data.count;
                badge.classList.remove('hidden');
            } else if (badge) {
                badge.classList.add('hidden');
            }
        } catch (error) {
            console.error('Erreur de mise à jour du compteur pour ' + badgeId, error);
        }
    }
    
    // Met à jour les deux compteurs au chargement de la page
    updateRequestCount('api/get_request_count.php', 'request-count-badge');
    updateRequestCount('api/get_camp_request_count.php', 'camp-request-count-badge');
});
</script>

</body>
</html>
