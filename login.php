<?php require_once 'partials/header.php'; ?>
<title>Connexion - TrouveTonCamp</title>

<main class="container mx-auto px-4 py-16 flex justify-center">
    <div class="w-full max-w-md">
        <form id="login-form" class="bg-white shadow-lg rounded-xl px-8 pt-6 pb-8 mb-4">
            <h1 class="text-3xl font-bold text-center text-gray-800 mb-6">Connexion</h1>

            <!-- Zone de message d'erreur/succès -->
            <div id="message-area" class="mb-4 text-center"></div>

            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="mail">
                    Adresse Email
                </label>
                <input class="shadow-sm appearance-none border rounded-lg w-full py-3 px-4 text-gray-700 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500" id="mail" type="email" placeholder="votre.email@exemple.com" required>
            </div>
            <div class="mb-6">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="password">
                    Mot de passe
                </label>
                <input class="shadow-sm appearance-none border rounded-lg w-full py-3 px-4 text-gray-700 mb-3 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500" id="password" type="password" placeholder="******************" required>
            </div>
            <div class="flex items-center justify-between">
                <button class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-6 rounded-lg focus:outline-none focus:shadow-outline w-full transition duration-300" type="submit">
                    Se connecter
                </button>
            </div>
             <p class="text-center text-gray-500 text-sm mt-6">
                Pas encore de compte ? <a class="font-bold text-blue-600 hover:text-blue-800" href="register.php">Inscrivez-vous</a>
            </p>
        </form>
    </div>
</main>

<script>
document.getElementById('login-form').addEventListener('submit', async function(event) {
    event.preventDefault();

    const mail = document.getElementById('mail').value;
    const password = document.getElementById('password').value;
    const messageArea = document.getElementById('message-area');
    messageArea.innerHTML = '<p class="text-blue-500">Connexion en cours...</p>';

    try {
        const response = await fetch('api/user_login.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ mail, password })
        });

        const result = await response.json();

        if (response.ok) {
            messageArea.innerHTML = `<p class="text-green-500 font-bold">Connexion réussie ! Redirection...</p>`;
            window.location.href = 'index.php';
        } else {
            // --- CORRECTION IMPORTANTE ---
            // Ce bloc affiche maintenant le message de débogage s'il existe.
            let errorMessage = result.error;
            if (result.debug) {
                // On ajoute le message de débogage en plus petit en dessous.
                errorMessage += `<br><small class="text-gray-500 mt-2 block">${result.debug}</small>`;
            }
            messageArea.innerHTML = `<p class="text-red-500 font-bold">${errorMessage}</p>`;
        }
    } catch (error) {
        messageArea.innerHTML = `<p class="text-red-500 font-bold">Une erreur de communication est survenue.</p>`;
    }
});
</script>

</body>
</html>
