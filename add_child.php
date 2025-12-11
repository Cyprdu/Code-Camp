<?php
require_once 'partials/header.php';

// SÉCURITÉ : On vérifie si l'utilisateur est connecté.
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}
?>

<title>Ajouter un Enfant - Fiche d'Inscription</title>

<main class="container mx-auto max-w-3xl px-4 sm:px-6 lg:px-8 py-12">

    <div class="mb-8">
        <a href="profile.php#children" class="inline-flex items-center gap-2 text-gray-600 hover:text-gray-900 font-medium">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-5 h-5">
                <path fill-rule="evenodd" d="M17 10a.75.75 0 0 1-.75.75H5.612l4.158 3.96a.75.75 0 1 1-1.04 1.08l-5.5-5.25a.75.75 0 0 1 0-1.08l5.5-5.25a.75.75 0 1 1 1.04 1.08L5.612 9.25H16.25A.75.75 0 0 1 17 10Z" clip-rule="evenodd" />
            </svg>
            Retour au profil
        </a>
    </div>

    <div class="bg-white p-8 rounded-xl shadow-lg border">
        <form id="add-child-form" class="space-y-10">
            
            <!-- Section 1: Informations sur l'enfant -->
            <div>
                <h2 class="text-xl font-bold text-gray-900 border-b pb-2 mb-6">Informations sur l'enfant</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                    <div>
                        <label for="prenom_enfant" class="block text-sm font-medium text-gray-700">Prénom</label>
                        <input type="text" id="prenom_enfant" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm p-2">
                    </div>
                    <div>
                        <label for="nom_enfant" class="block text-sm font-medium text-gray-700">Nom</label>
                        <input type="text" id="nom_enfant" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm p-2">
                    </div>
                    <div>
                        <label for="date_naissance" class="block text-sm font-medium text-gray-700">Date de naissance</label>
                        <input type="date" id="date_naissance" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm p-2 text-gray-600">
                    </div>
                    <div>
                        <label for="sexe" class="block text-sm font-medium text-gray-700">Sexe</label>
                        <select id="sexe" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm p-2">
                            <option>Homme</option>
                            <option>Femme</option>
                            <option>Autre</option>
                        </select>
                    </div>
                    <div class="sm:col-span-2">
                        <label for="num_enfant" class="block text-sm font-medium text-gray-700">N° de téléphone de l'enfant (optionnel)</label>
                        <input type="tel" id="num_enfant" placeholder="+33 6 12 34 56 78" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm p-2">
                    </div>
                </div>
            </div>

            <!-- Section 2: Informations de Santé -->
            <div>
                <h2 class="text-xl font-bold text-gray-900 border-b pb-2 mb-6">Santé</h2>
                <div class="space-y-6">
                    <div>
                        <label for="alergie" class="block text-sm font-medium text-gray-700">Allergies ou informations médicales importantes</label>
                        <textarea id="alergie" rows="4" placeholder="Aucune allergie connue, PAI pour asthme, etc." class="mt-1 block w-full rounded-md border-gray-300 shadow-sm p-2"></textarea>
                    </div>
                    <div>
                        <label for="carnet_sante" class="block text-sm font-medium text-gray-700">Copie du carnet de santé (vaccins)</label>
                        <input type="file" id="carnet_sante" class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                        <p class="text-xs text-gray-500 mt-1">Fichier PDF ou image. La prise en charge de l'envoi de fichier est en développement.</p>
                    </div>
                </div>
            </div>

            <!-- Section 3: Responsable Légal 1 -->
            <div>
                <h2 class="text-xl font-bold text-gray-900 border-b pb-2 mb-6">Responsable Légal 1 (principal)</h2>
                 <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                    <div>
                        <label for="prenom_parent1" class="block text-sm font-medium text-gray-700">Prénom</label>
                        <input type="text" id="prenom_parent1" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm p-2">
                    </div>
                     <div>
                        <label for="nom_parent1" class="block text-sm font-medium text-gray-700">Nom</label>
                        <input type="text" id="nom_parent1" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm p-2">
                    </div>
                     <div>
                        <label for="mail_parent1" class="block text-sm font-medium text-gray-700">Email</label>
                        <input type="email" id="mail_parent1" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm p-2">
                    </div>
                     <div>
                        <label for="num_parent1" class="block text-sm font-medium text-gray-700">N° de téléphone</label>
                        <input type="tel" id="num_parent1" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm p-2">
                    </div>
                </div>
            </div>
            
            <!-- Section 4: Responsable Légal 2 -->
            <div>
                <h2 class="text-xl font-bold text-gray-900 border-b pb-2 mb-6">Responsable Légal 2 (optionnel)</h2>
                 <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                    <div>
                        <label for="prenom_parent2" class="block text-sm font-medium text-gray-700">Prénom</label>
                        <input type="text" id="prenom_parent2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm p-2">
                    </div>
                     <div>
                        <label for="nom_parent2" class="block text-sm font-medium text-gray-700">Nom</label>
                        <input type="text" id="nom_parent2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm p-2">
                    </div>
                     <div>
                        <label for="mail_parent2" class="block text-sm font-medium text-gray-700">Email</label>
                        <input type="email" id="mail_parent2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm p-2">
                    </div>
                     <div>
                        <label for="num_parent2" class="block text-sm font-medium text-gray-700">N° de téléphone</label>
                        <input type="tel" id="num_parent2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm p-2">
                    </div>
                </div>
            </div>

            <div id="form-message" class="text-center mt-4 text-sm"></div>

            <div class="pt-6 text-right">
                <button type="submit" class="bg-blue-600 text-white font-bold py-3 px-6 rounded-lg transition-all hover:bg-blue-700 w-full sm:w-auto">
                    Enregistrer la fiche de l'enfant
                </button>
            </div>
        </form>
    </div>
</main>

<script>
document.getElementById('add-child-form').addEventListener('submit', async function(event) {
    event.preventDefault();

    const formData = {
        prenom_enfant: document.getElementById('prenom_enfant').value,
        nom_enfant: document.getElementById('nom_enfant').value,
        date_naissance: document.getElementById('date_naissance').value,
        sexe: document.getElementById('sexe').value,
        num_enfant: document.getElementById('num_enfant').value,
        alergie: document.getElementById('alergie').value,
        prenom_parent1: document.getElementById('prenom_parent1').value,
        nom_parent1: document.getElementById('nom_parent1').value,
        mail_parent1: document.getElementById('mail_parent1').value,
        num_parent1: document.getElementById('num_parent1').value,
        prenom_parent2: document.getElementById('prenom_parent2').value,
        nom_parent2: document.getElementById('nom_parent2').value,
        mail_parent2: document.getElementById('mail_parent2').value,
        num_parent2: document.getElementById('num_parent2').value,
    };
    
    // Note : la logique d'envoi de fichier (carnet_sante) n'est pas gérée ici.
    // Elle nécessite un traitement plus complexe (ex: envoi vers un serveur de stockage).

    const messageArea = document.getElementById('form-message');
    const submitButton = this.querySelector('button[type="submit"]');

    messageArea.innerHTML = '<p class="text-blue-500">Enregistrement en cours...</p>';
    submitButton.disabled = true;

    try {
        const response = await fetch('api/add_child.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(formData)
        });

        const result = await response.json();

        if (response.ok) {
            messageArea.innerHTML = `<p class="text-green-600 font-bold">${result.success}</p>`;
            setTimeout(() => { window.location.href = 'profile.php#children'; }, 1500);
        } else {
            throw new Error(result.error || 'Une erreur est survenue.');
        }
    } catch (error) {
        messageArea.innerHTML = `<p class="text-red-500 font-bold">${error.message}</p>`;
        submitButton.disabled = false;
    }
});
</script>

</body>
</html>
