<?php
require_once 'api/config.php'; // Inclure config AVANT le header pour avoir $pdo
require_once 'partials/header.php';

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

// --- SÉCURITÉ RENFORCÉE (IDOR FIX) ---
// On vérifie immédiatement en base de données que l'enfant appartient bien à l'utilisateur connecté.
try {
    $stmtVerify = $pdo->prepare("SELECT id FROM enfants WHERE id = ? AND parent_id = ?");
    $stmtVerify->execute([$child_id, $_SESSION['user']['id']]);
    if (!$stmtVerify->fetch()) {
        // Stop net si l'enfant n'est pas à lui
        die('<div class="container mx-auto py-20 text-center"><h1 class="text-3xl font-bold text-red-600">Accès Refusé</h1><p class="mt-4">Vous n\'avez pas les droits pour consulter les informations de cet enfant.</p><a href="reservations.php" class="inline-block mt-6 bg-blue-600 text-white px-4 py-2 rounded">Retour</a></div>');
    }
} catch (Exception $e) {
    die("Erreur de sécurité.");
}
// -------------------------------------
?>

<title>Détails Inscription</title>

<main class="container mx-auto max-w-4xl px-4 sm:px-6 lg:px-8 py-12">
    <div id="loader" class="text-center py-20"><div class="loader inline-block"></div><p>Chargement...</p></div>
    <div id="content" class="hidden bg-white p-8 rounded-xl shadow-lg border">
        <h1 class="text-2xl font-bold mb-4 text-center border-b pb-4">Inscription Confirmée</h1>
        <div class="grid md:grid-cols-2 gap-8">
            <div>
                <h2 class="font-bold text-lg mb-2">Camp</h2>
                <p id="camp-name" class="text-gray-800"></p>
                <p id="camp-dates" class="text-sm text-gray-600"></p>
                <p id="camp-address" class="text-sm text-gray-600 mt-1"></p>
            </div>
            <div>
                <h2 class="font-bold text-lg mb-2">Enfant</h2>
                <p id="child-name" class="text-gray-800"></p>
                <h2 class="font-bold text-lg mt-4 mb-2">Contact Orga</h2>
                <p id="org-name" class="text-gray-800"></p>
                <p id="org-contact" class="text-sm text-blue-600"></p>
            </div>
        </div>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', async function() {
    const campId = '<?= $camp_id ?>';
    const childId = '<?= $child_id ?>';
    
    try {
        const res = await fetch(`api/get_inscription_details.php?camp_id=${campId}&child_id=${childId}`);
        if (!res.ok) throw new Error('Erreur chargement');
        const data = await res.json();

        document.getElementById('camp-name').textContent = data.camp.nom;
        document.getElementById('child-name').textContent = data.enfant.prenom;
        document.getElementById('camp-address').textContent = data.camp.adresse;
        document.getElementById('org-name').textContent = data.organisateur.nom;
        document.getElementById('org-contact').textContent = `${data.organisateur.mail} / ${data.organisateur.tel}`;
        
        const date = new Date(data.camp.date_debut).toLocaleDateString('fr-FR');
        document.getElementById('camp-dates').textContent = `Début le : ${date}`;

        document.getElementById('loader').classList.add('hidden');
        document.getElementById('content').classList.remove('hidden');
    } catch (e) {
        document.getElementById('loader').innerHTML = `<p class="text-red-500">${e.message}</p>`;
    }
});
</script>
</body>
</html>