<?php
require_once 'partials/header.php';

// On définit tous les statuts possibles pour simplifier le code HTML
$is_logged_in = isset($_SESSION['user']);
$is_director =  $is_logged_in && ($_SESSION['user']['is_directeur'] ?? false);
$request_pending = $is_logged_in && ($_SESSION['user']['demande_en_cours'] ?? false);
$is_refused = $is_logged_in && ($_SESSION['user']['is_refused'] ?? false);
?>

<title>Aide et Documentation - ColoMap</title>

<main class="container mx-auto max-w-4xl px-4 sm:px-6 lg:px-8 py-12">
    <div class="bg-white p-8 rounded-xl shadow-lg border">

        <h1 class="text-3xl font-extrabold text-center text-gray-900 mb-4">Centre d'Aide de ColoMap</h1>
        <p class="text-center text-gray-600 mb-12">Trouvez ici les réponses à vos questions, que vous soyez un parent à la recherche du camp parfait ou un organisateur souhaitant rejoindre notre plateforme.</p>

        <!-- Section Pour les Parents -->
        <div class="mb-10">
            <h2 class="text-2xl font-bold text-blue-600 mb-4">Pour les Parents</h2>
            <div class="space-y-4">
                <div>
                    <h3 class="font-semibold text-lg">Qu'est-ce que ColoMap ?</h3>
                    <p class="text-gray-700">ColoMap est une plateforme conçue pour simplifier la recherche de colonies de vacances et de camps pour vos enfants. Nous centralisons des centaines d'offres partout en France pour vous permettre de trouver, comparer et choisir l'aventure idéale en quelques clics.</p>
                </div>
                <div>
                    <h3 class="font-semibold text-lg">Comment rechercher un camp ?</h3>
                    <p class="text-gray-700">Utilisez la barre de recherche sur la page d'accueil pour filtrer les camps par nom, ou cliquez sur "Filtres avancés" pour affiner votre recherche par ville, date ou âge de votre enfant. Si vous êtes connecté, vous pouvez même filtrer directement en fonction de l'âge de vos enfants enregistrés !</p>
                </div>
                <div>
                    <h3 class="font-semibold text-lg">La création d'un compte est-elle obligatoire ?</h3>
                    <p class="text-gray-700">Vous pouvez explorer tous les camps sans compte. Cependant, un compte gratuit est nécessaire pour accéder aux détails complets d'un camp, comme l'adresse exacte ou la brochure PDF. Cela nous permet d'assurer un environnement sécurisé pour nos organisateurs partenaires.</p>
                </div>
            </div>
        </div>

        <!-- Section Pour les Organisateurs -->
        <div class="mb-12">
            <h2 class="text-2xl font-bold text-green-600 mb-4">Pour les Organisateurs</h2>
            <div class="space-y-4">
                <div>
                    <h3 class="font-semibold text-lg">Pourquoi inscrire mes camps sur ColoMap ?</h3>
                    <p class="text-gray-700">En rejoignant ColoMap, vous bénéficiez d'une visibilité exceptionnelle auprès de milliers de parents activement à la recherche d'une colonie. Notre plateforme vous offre un "Espace Organisateur" complet pour gérer vos fiches de camp, ajouter de nouvelles offres et mettre en avant vos points forts.</p>
                </div>
                <div>
                    <h3 class="font-semibold text-lg">Comment puis-je ajouter mes camps ?</h3>
                    <p class="text-gray-700">Pour ajouter et gérer vos camps, vous devez disposer d'un "Espace Directeur". Ce statut est accordé après une simple demande de votre part et une vérification de notre équipe. Cette étape garantit la qualité et la fiabilité des offres présentées sur notre site.</p>
                </div>
            </div>
        </div>
        
        <!-- Section "Devenir Directeur" -->
        <div class="bg-gray-100 rounded-lg p-6 text-center">
            <h2 class="text-xl font-bold text-gray-800 mb-3">Prêt à rejoindre l'aventure ?</h2>
            
            <?php if (!$is_logged_in): ?>
                <p class="text-gray-600 mb-4">Créez un compte ou connectez-vous pour pouvoir demander un accès directeur.</p>
                <a href="login.php" class="bg-blue-600 text-white font-bold py-2 px-6 rounded-lg transition-all duration-300 hover:bg-gradient-to-r hover:from-blue-500 hover:via-purple-500 hover:to-pink-500">
                    Connexion / Inscription
                </a>

            <?php elseif ($is_director): ?>
                <p class="text-green-700 font-semibold mb-4">Vous disposez déjà d'un accès directeur.</p>
                <a href="organisateurs.php" class="bg-green-600 text-white font-bold py-2 px-6 rounded-lg hover:bg-green-700">
                    Accéder à mon espace
                </a>

            <?php elseif ($is_refused): ?>
                <!-- NOUVEAU : Cas où la demande a été refusée -->
                <p class="text-red-700 font-semibold mb-4">Votre précédente demande d'accès n'a pas pu être validée. Pour plus d'informations, veuillez contacter le support.</p>
                <button class="bg-gray-400 text-white font-bold py-2 px-6 rounded-lg cursor-not-allowed">
                    Demande non autorisée
                </button>

            <?php elseif ($request_pending): ?>
                <p class="text-blue-700 font-semibold mb-4">Votre demande d'accès a bien été reçue et est en cours de traitement.</p>
                <button class="bg-gray-400 text-white font-bold py-2 px-6 rounded-lg cursor-not-allowed">
                    Demande en cours...
                </button>

            <?php else: ?>
                <p class="text-gray-600 mb-4">Cliquez sur le bouton ci-dessous pour nous envoyer votre demande.</p>
                <button id="request-access-button" class="bg-purple-600 text-white font-bold py-2 px-6 rounded-lg hover:bg-purple-700">
                    Demander un espace directeur
                </button>
                <div id="request-message" class="mt-4 text-sm"></div>
            <?php endif; ?>
        </div>
    </div>
</main>

<script>
// Le script JS de cette page n'a pas besoin d'être modifié
document.addEventListener('DOMContentLoaded', function() {
    const requestButton = document.getElementById('request-access-button');
    const requestMessage = document.getElementById('request-message');
    if (requestButton) {
        requestButton.addEventListener('click', async function() {
            this.disabled = true; this.textContent = 'Envoi en cours...';
            try {
                const response = await fetch('api/request_director_access.php', { method: 'POST' });
                const result = await response.json();
                if (!response.ok) throw new Error(result.error || 'Erreur.');
                requestMessage.innerHTML = `<p class="text-green-600 font-bold">${result.success}</p>`;
                // On cache le bouton après un succès pour éviter les demandes multiples
                setTimeout(() => { requestButton.style.display = 'none'; }, 2000);
            } catch (error) {
                requestMessage.innerHTML = `<p class="text-red-600 font-bold">${error.message}</p>`;
                this.disabled = false; this.textContent = 'Demander un espace directeur';
            }
        });
    }
});
</script>

</body>
</html>
