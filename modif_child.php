<?php
require_once 'partials/header.php';

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$childId = $_GET['id'] ?? 0;
$redirect = $_GET['redirect'] ?? 'children.php';

// Récupération infos enfant
$stmt = $pdo->prepare("SELECT * FROM enfants WHERE id = ? AND parent_id = ?");
$stmt->execute([$childId, $_SESSION['user']['id']]);
$child = $stmt->fetch();

if (!$child) die("Enfant introuvable.");

$inputClass = "w-full bg-gray-50 border border-gray-400 text-gray-900 text-sm rounded-lg p-2.5";
$labelClass = "block mb-2 text-sm font-bold text-gray-800 uppercase";
?>

<div class="max-w-4xl mx-auto py-12 px-4">
    <h1 class="text-3xl font-bold mb-8">Modifier : <?= htmlspecialchars($child['prenom']) ?></h1>

    <form action="api/update_child.php" method="POST" enctype="multipart/form-data" class="space-y-6 bg-white p-8 rounded shadow">
        <input type="hidden" name="child_id" value="<?= $child['id'] ?>">
        <input type="hidden" name="redirect_url" value="<?= htmlspecialchars($redirect) ?>">

        <div class="grid grid-cols-2 gap-4">
            <div><label class="<?= $labelClass ?>">Nom</label><input type="text" name="nom" value="<?= $child['nom'] ?>" class="<?= $inputClass ?>"></div>
            <div><label class="<?= $labelClass ?>">Prénom</label><input type="text" name="prenom" value="<?= $child['prenom'] ?>" class="<?= $inputClass ?>"></div>
            
            <div class="col-span-2 border-t pt-4 mt-4">
                <h3 class="font-bold text-red-600 mb-2">Santé</h3>
                <label class="<?= $labelClass ?>">Allergies / Infos Médicales</label>
                <textarea name="infos_sante" class="<?= $inputClass ?>"><?= $child['infos_sante'] ?></textarea>
            </div>
            
             <div class="col-span-2">
                <label class="<?= $labelClass ?>">Régime Alimentaire</label>
                <textarea name="regime_alimentaire" class="<?= $inputClass ?>"><?= $child['regime_alimentaire'] ?></textarea>
            </div>
        </div>

        <div class="flex justify-end pt-4">
            <a href="<?= htmlspecialchars($redirect) ?>" class="text-gray-600 mr-4 py-2">Annuler</a>
            <button type="submit" class="bg-blue-600 text-white font-bold py-2 px-6 rounded">Enregistrer</button>
        </div>
    </form>
</div>

<?php require_once 'partials/footer.php'; ?>