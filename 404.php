<?php
// On inclut le header pour garder une navigation cohérente sur le site.
require_once 'partials/header.php';
// On indique au navigateur que c'est bien une page d'erreur 404.
http_response_code(404);
?>

<title>Page Introuvable - ColoMap</title>

<main class="flex items-center justify-center h-[calc(100vh-150px)]">
    <div class="text-center">
        <!-- Image remplaçant l'icône et le texte -->
        <img src="https://cdn-icons-png.flaticon.com/512/2034/2034479.png" alt="Page non trouvée" class="mx-auto w-40 h-40 mb-6">

        <p class="text-lg text-gray-500 mt-4 max-w-md mx-auto">
            Désolé, la page que vous cherchez semble introuvable ou n'existe plus.
        </p>

        <!-- Bouton de retour à l'accueil -->
        <div class="mt-8">
            <a href="index.php" class="bg-blue-600 text-white font-bold py-3 px-6 rounded-lg transition-all duration-300 hover:bg-gradient-to-r hover:from-blue-500 hover:via-purple-500 hover:to-pink-500">
                Retour à l'accueil
            </a>
        </div>
    </div>
</main>

</body>
</html>
