<?php
require_once 'partials/header.php';

// Sécurité : si l'utilisateur n'est pas connecté, on le redirige.
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

// On récupère le statut d'administrateur depuis la session pour l'affichage conditionnel.
$is_admin = $_SESSION['user']['is_admin'] ?? false;
?>

<title>Mon Espace Personnel - ColoMap</title>

<main class="container mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-12">

    <div class="mb-8">
        <h1 class="text-3xl font-bold tracking-tight text-gray-900">Mon Espace Personnel</h1>
        <p class="mt-1 text-lg text-gray-600">Gérez vos informations, vos enfants et vos camps favoris.</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">

        <!-- Carte : Mes enfants avec nouvelle icône -->
        <a href="children.php" class="bg-white p-6 rounded-xl shadow-lg border hover:border-blue-500 hover:ring-2 hover:ring-blue-200 transition-all cursor-pointer">
            <div class="flex items-center gap-4">
                <div class="flex-shrink-0 w-12 h-12 rounded-lg bg-blue-100 text-blue-600 flex items-center justify-center">
                    <svg class="w-7 h-7" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" /></svg>
                </div>
                <div>
                    <h3 class="font-bold text-lg">Mes Enfants</h3>
                    <p class="text-sm text-gray-500">Gérer les fiches de vos enfants.</p>
                </div>
            </div>
        </a>

        <!-- Carte : Paramètres du compte -->
        <a href="settings.php" class="bg-white p-6 rounded-xl shadow-lg border hover:border-gray-500 hover:ring-2 hover:ring-gray-200 transition-all cursor-pointer">
            <div class="flex items-center gap-4">
                <div class="flex-shrink-0 w-12 h-12 rounded-lg bg-gray-100 text-gray-600 flex items-center justify-center">
                    <svg class="w-6 h-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6h9.75M10.5 6a1.5 1.5 0 1 1-3 0m3 0a1.5 1.5 0 1 0-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-3.75 0H7.5m9-6h3.75m-3.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-9.75 0h9.75" /></svg>
                </div>
                <div>
                    <h3 class="font-bold text-lg">Mes Paramètres</h3>
                    <p class="text-sm text-gray-500">Modifier vos informations personnelles.</p>
                </div>
            </div>
        </a>
        
        <!-- Carte Admin avec nouvelle icône et logique de notif corrigée -->
        <?php if ($is_admin): ?>
        <a href="admin.php" class="relative bg-red-50 p-6 rounded-xl shadow-lg border border-red-200 hover:border-red-500 hover:ring-2 hover:ring-red-200 transition-all cursor-pointer md:col-span-2 lg:col-span-3">
            <div class="flex items-center gap-4">
                <div class="flex-shrink-0 w-12 h-12 rounded-lg bg-red-100 text-red-600 flex items-center justify-center">
                    <svg class="w-7 h-7" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.286Zm-1.5 6.135a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /></svg>
                </div>
                <div>
                    <h3 class="font-bold text-lg text-red-800">Panneau d'Administration</h3>
                    <p class="text-sm text-red-700">Gérer les demandes et le site.</p>
                </div>
                <span id="admin-notif-badge-profile" class="absolute top-3 right-3 w-6 h-6 flex items-center justify-center bg-red-600 text-white text-xs font-bold rounded-full hidden"></span>
            </div>
        </a>
        <?php endif; ?>

    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function () {
    <?php if ($is_admin): ?>
    async function getAdminNotifCount() {
        try {
            // On lance les deux appels en parallèle pour plus d'efficacité
            const [requestResponse, campResponse] = await Promise.all([
                fetch('api/get_request_count.php'),
                fetch('api/get_camp_request_count.php')
            ]);

            if (!requestResponse.ok || !campResponse.ok) return;

            const requestData = await requestResponse.json();
            const campData = await campResponse.json();
            const totalCount = (requestData.count || 0) + (campData.count || 0);
            
            const badge = document.getElementById('admin-notif-badge-profile');
            if (badge && totalCount > 0) {
                badge.textContent = totalCount;
                badge.classList.remove('hidden');
            } else if (badge) {
                badge.classList.add('hidden');
            }
        } catch (error) {
            console.error('Impossible de récupérer le nombre de notifications admin:', error);
        }
    }
    getAdminNotifCount();
    <?php endif; ?>
});
</script>

</body>
</html>
