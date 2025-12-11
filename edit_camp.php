<?php
require_once 'partials/header.php';

// Sécurité
if (!isset($_SESSION['user']) || !$_SESSION['user']['is_directeur']) {
    header('Location: index.php');
    exit;
}
if (!isset($_GET['id'])) {
    header('Location: mes_camps.php');
    exit;
}
?>

<title>Modifier le Camp - Espace Organisateur</title>

<main class="container mx-auto max-w-4xl px-4 sm:px-6 lg:px-8 py-12">

    <div id="loader-edit" class="text-center py-20">
        <div class="loader inline-block"></div>
        <p class="mt-4 text-gray-600">Chargement des informations du camp...</p>
    </div>

    <div id="form-container" class="hidden">
        <div class="mb-8">
            <a href="mes_camps.php" class="inline-flex items-center gap-2 text-gray-600 hover:text-gray-900 font-medium">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-5 h-5"><path fill-rule="evenodd" d="M17 10a.75.75 0 0 1-.75.75H5.612l4.158 3.96a.75.75 0 1 1-1.04 1.08l-5.5-5.25a.75.75 0 0 1 0-1.08l5.5-5.25a.75.75 0 1 1 1.04 1.08L5.612 9.25H16.25A.75.75 0 0 1 17 10Z" clip-rule="evenodd" /></svg>
                Retour à la liste de mes camps
            </a>
        </div>

        <div class="bg-white p-8 rounded-xl shadow-lg border">
            <h1 class="text-2xl font-bold mb-6 text-gray-900">Modifier le camp</h1>
            <form id="edit-camp-form" class="space-y-6">
                <!-- Les champs du formulaire seront ici, identiques à create_camp.php -->
                <div><label for="nom" class="block text-sm font-medium text-gray-700">Nom du camp</label><input type="text" id="nom" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm p-2"></div>
                <div><label for="description" class="block text-sm font-medium text-gray-700">Description</label><textarea id="description" rows="4" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm p-2"></textarea></div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div><label for="ville" class="block text-sm font-medium text-gray-700">Ville</label><input type="text" id="ville" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm p-2"></div>
                    <div><label for="code_postal" class="block text-sm font-medium text-gray-700">Code Postal</label><input type="text" id="code_postal" required pattern="[0-9]{5}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm p-2"></div>
                </div>
                <div><label for="adresse" class="block text-sm font-medium text-gray-700">Adresse exacte</label><input type="text" id="adresse" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm p-2"></div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div><label for="prix" class="block text-sm font-medium text-gray-700">Prix (€)</label><input type="number" id="prix" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm p-2"></div>
                    <div><label for="age_min" class="block text-sm font-medium text-gray-700">Âge minimum</label><input type="number" id="age_min" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm p-2"></div>
                    <div><label for="age_max" class="block text-sm font-medium text-gray-700">Âge maximum</label><input type="number" id="age_max" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm p-2"></div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div><label for="date_debut" class="block text-sm font-medium text-gray-700">Date de début</label><input type="date" id="date_debut" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm p-2"></div>
                    <div><label for="date_fin" class="block text-sm font-medium text-gray-700">Date de fin</label><input type="date" id="date_fin" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm p-2"></div>
                </div>
                <div><label for="image_url" class="block text-sm font-medium text-gray-700">URL de l'image d'illustration</label><input type="url" id="image_url" placeholder="https://..." required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm p-2"></div>
                
                <div id="form-message" class="text-center mt-4"></div>
                <div class="pt-4 text-right"><button type="submit" class="bg-yellow-500 text-white font-bold py-3 px-6 rounded-lg transition hover:bg-yellow-600">Enregistrer les modifications</button></div>
            </form>
        </div>
    </div>
</main>
<script>
document.addEventListener('DOMContentLoaded', async function() {
    const params = new URLSearchParams(window.location.search);
    const campId = params.get('id');
    const loader = document.getElementById('loader-edit');
    const formContainer = document.getElementById('form-container');
    const form = document.getElementById('edit-camp-form');
    const formMessage = document.getElementById('form-message');

    if (!campId) {
        window.location.href = 'mes_camps.php';
        return;
    }

    // 1. Charger les données du camp et pré-remplir le formulaire
    try {
        const response = await fetch(`api/get_camp_details.php?id=${campId}`);
        if (!response.ok) throw new Error('Camp introuvable.');
        const camp = await response.json();

        // Remplissage du formulaire
        document.getElementById('nom').value = camp.nom;
        document.getElementById('description').value = camp.description.replace(/<br\s*\/?>/gi, ""); // Retire les <br>
        document.getElementById('ville').value = camp.ville;
        document.getElementById('code_postal').value = camp.code_postal || '';
        document.getElementById('adresse').value = camp.adresse;
        document.getElementById('prix').value = camp.prix;
        document.getElementById('age_min').value = camp.age_min;
        document.getElementById('age_max').value = camp.age_max;
        document.getElementById('date_debut').value = camp.date_debut;
        document.getElementById('date_fin').value = camp.date_fin;
        document.getElementById('image_url').value = camp.image_url;
        
        loader.classList.add('hidden');
        formContainer.classList.remove('hidden');

    } catch(error) {
        loader.innerHTML = `<p class="text-red-500 font-bold">${error.message}</p>`;
    }

    // 2. Gérer la soumission du formulaire
    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        formMessage.innerHTML = '<p class="text-blue-500">Enregistrement...</p>';
        const updatedData = {
            nom: document.getElementById('nom').value,
            description: document.getElementById('description').value,
            ville: document.getElementById('ville').value,
            code_postal: document.getElementById('code_postal').value,
            adresse: document.getElementById('adresse').value,
            prix: parseInt(document.getElementById('prix').value),
            age_min: parseInt(document.getElementById('age_min').value),
            age_max: parseInt(document.getElementById('age_max').value),
            date_debut: document.getElementById('date_debut').value,
            date_fin: document.getElementById('date_fin').value,
            image_url: document.getElementById('image_url').value
        };

        try {
            const response = await fetch(`api/update_camp.php?id=${campId}`, {
                method: 'POST', // Les serveurs gèrent souvent mieux POST que PATCH
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(updatedData)
            });
            const result = await response.json();
            if (!response.ok) throw new Error(result.error);
            formMessage.innerHTML = `<p class="text-green-600 font-bold">${result.success}</p>`;
        } catch(error) {
            formMessage.innerHTML = `<p class="text-red-500 font-bold">${error.message}</p>`;
        }
    });
});
</script>
</body>
</html>
