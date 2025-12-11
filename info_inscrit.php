<?php
require_once 'partials/header.php';

// Sécurité : l'utilisateur doit être connecté.
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}
$camp_id = $_GET['camp_id'] ?? '';
$child_id = $_GET['child_id'] ?? '';

if (empty($camp_id) || empty($child_id)) {
    header('Location: reservations.php');
    exit;
}
?>

<title>Informations sur l'Inscription - ColoMap</title>

<main class="container mx-auto max-w-4xl px-4 sm:px-6 lg:px-8 py-12">
    <div id="loader" class="text-center py-20">
        <div class="loader inline-block"></div>
        <p class="mt-4 text-gray-600">Chargement des informations...</p>
    </div>

    <div id="content" class="hidden">
        <div class="mb-8">
            <a href="reservations.php" class="inline-flex items-center gap-2 text-gray-600 hover:text-gray-900 font-medium">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-5 h-5"><path fill-rule="evenodd" d="M17 10a.75.75 0 0 1-.75.75H5.612l4.158 3.96a.75.75 0 1 1-1.04 1.08l-5.5-5.25a.75.75 0 0 1 0-1.08l5.5-5.25a.75.75 0 1 1 1.04 1.08L5.612 9.25H16.25A.75.75 0 0 1 17 10Z" clip-rule="evenodd" /></svg>
                Retour à mes réservations
            </a>
        </div>

        <div class="bg-white p-8 rounded-xl shadow-lg border">
            <div class="text-center border-b pb-6 mb-6">
                <p class="text-blue-600 font-semibold">INSCRIPTION CONFIRMÉE</p>
                <h1 id="camp-name" class="text-3xl font-bold mt-2 text-gray-900"></h1>
                <p class="text-lg text-gray-600">pour <span id="child-name" class="font-bold"></span></p>
            </div>
            
            <div class="grid md:grid-cols-2 gap-8">
                <!-- Section Informations Pratiques -->
                <div class="space-y-4">
                    <h2 class="text-xl font-bold">Informations Pratiques</h2>
                    <div id="camp-dates"></div>
                    <div id="camp-address"></div>
                </div>

                <!-- Section Contact -->
                <div class="space-y-4">
                    <h2 class="text-xl font-bold">Contact Organisateur</h2>
                    <div id="org-name"></div>
                    <div id="org-contact"></div>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', async function() {
    const loader = document.getElementById('loader');
    const content = document.getElementById('content');
    const campId = '<?php echo $camp_id; ?>';
    const childId = '<?php echo $child_id; ?>';

    try {
        const response = await fetch(`api/get_inscription_details.php?camp_id=${campId}&child_id=${childId}`);
        if (!response.ok) {
            const errorData = await response.json();
            throw new Error(errorData.error || 'Impossible de charger les détails.');
        }
        
        const data = await response.json();

        // Remplissage des champs
        document.getElementById('camp-name').textContent = data.camp.nom;
        document.getElementById('child-name').textContent = data.enfant.prenom;
        
        const startDate = new Date(data.camp.date_debut).toLocaleDateString('fr-FR', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
        document.getElementById('camp-dates').innerHTML = `<p class="font-semibold">Date d'arrivée</p><p>${startDate}</p>`;
        
        document.getElementById('camp-address').innerHTML = `<p class="font-semibold">Lieu du camp</p><p>${data.camp.adresse}</p>`;
        
        document.getElementById('org-name').innerHTML = `<p class="font-semibold">Organisme</p><p>${data.organisateur.nom}</p>`;
        
        document.getElementById('org-contact').innerHTML = `<p class="font-semibold">Contact</p><p>${data.organisateur.mail} / ${data.organisateur.tel}</p>`;

        loader.classList.add('hidden');
        content.classList.remove('hidden');

    } catch (error) {
        loader.innerHTML = `<p class="text-red-500 font-bold">${error.message}</p>`;
    }
});
</script>

</body>
</html>
