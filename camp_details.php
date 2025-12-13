<?php
// On inclut la config EN PREMIER pour avoir accès à la base de données ($pdo)
require_once 'api/config.php';
require_once 'partials/header.php';

// Informations utilisateur
$is_logged_in = isset($_SESSION['user']);
$user_favorites = $_SESSION['user']['favorites'] ?? [];

// === LOGIQUE DE RÉCUPÉRATION (Token ou ID) ===
$token = $_GET['t'] ?? null;
$id_param = $_GET['id'] ?? null;
$camp_id = null;

try {
    if ($token) {
        // Cas 1 : Accès via le lien sécurisé (Prioritaire)
        $stmt = $pdo->prepare("SELECT id FROM camps WHERE token = ?");
        $stmt->execute([$token]);
        $res = $stmt->fetch();
        if ($res) {
            $camp_id = $res['id'];
        }
    } elseif ($id_param) {
        // Cas 2 : Accès via l'ID (Ancienne méthode ou Interne)
        $stmt = $pdo->prepare("SELECT id, prive FROM camps WHERE id = ?");
        $stmt->execute([$id_param]);
        $res = $stmt->fetch();
        
        if ($res) {
            // SÉCURITÉ : Si le camp est privé, on interdit l'accès par ID direct
            if ($res['prive'] == 1 && (!isset($_SESSION['user']['is_admin']) || !$_SESSION['user']['is_admin'])) {
                // Redirection immédiate si camp privé accédé par ID (sauf admin)
                echo "<script>window.location.href='index.php';</script>";
                exit;
            }
            $camp_id = $res['id'];
        }
    }
} catch (Exception $e) {
    // En cas d'erreur SQL, on ne fait rien, camp_id restera null
}
?>

<title>Détails du Camp - ColoMap</title>

<div id="auth-modal" class="fixed inset-0 bg-gray-900 bg-opacity-50 backdrop-blur-sm flex items-center justify-center z-50 hidden">
    <div class="bg-white rounded-lg shadow-xl p-8 max-w-sm w-full text-center">
        <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-blue-100 mb-4">
            <svg class="h-6 w-6 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" /></svg>
        </div>
        <h2 class="text-2xl font-bold mb-2">Accès réservé</h2>
        <p class="text-gray-600 mb-6">Vous devez être connecté pour voir les détails de ce camp.</p>
        <a href="login.php" class="w-full bg-blue-600 text-white font-bold py-3 px-4 rounded-lg inline-block hover:bg-blue-700">Se connecter</a>
        <a href="index.php" class="mt-4 text-sm text-gray-500 hover:text-gray-700">Retour à l'accueil</a>
    </div>
</div>

<main class="container mx-auto px-4 sm:px-6 lg:px-8 max-w-6xl py-8">
    <div id="loader" class="text-center py-20"><div class="loader inline-block"></div><p class="mt-4 text-gray-600">Chargement...</p></div>
    
    <div id="camp-content" class="hidden">
        <div class="mb-6"><a href="index.php" class="inline-flex items-center gap-2 text-gray-600 hover:text-gray-900 font-medium"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-5 h-5"><path fill-rule="evenodd" d="M17 10a.75.75 0 0 1-.75.75H5.612l4.158 3.96a.75.75 0 1 1-1.04 1.08l-5.5-5.25a.75.75 0 0 1 0-1.08l5.5-5.25a.75.75 0 1 1 1.04 1.08L5.612 9.25H16.25A.75.75 0 0 1 17 10Z" clip-rule="evenodd" /></svg>Retour</a></div>
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 lg:gap-12">
            <div class="lg:col-span-2">
                <img id="camp-image" src="" alt="Image du camp" class="w-full h-auto object-cover rounded-xl shadow-lg mb-4">
                <div class="flex justify-between items-start mb-6">
                    <div>
                        <h1 id="camp-name" class="text-4xl font-extrabold text-gray-900 mb-2"></h1>
                        <p id="camp-location" class="text-lg text-gray-500"></p>
                    </div>
                    <div class="flex items-center gap-2 mt-2">
                        <?php if ($is_logged_in): ?>
                        <button id="favorite-button" class="bg-white p-3 rounded-full shadow-md hover:bg-gray-100 transition-all" title="Ajouter aux favoris">
                            <?php $is_favorited = in_array($camp_id, $user_favorites); ?>
                            <svg class="w-6 h-6 <?php echo $is_favorited ? 'text-red-500 fill-current' : 'text-gray-500'; ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 016.364 0L12 7.5l1.318-1.182a4.5 4.5 0 116.364 6.364L12 21l-7.682-7.682a4.5 4.5 0 010-6.364z"></path></svg>
                        </button>
                        <?php endif; ?>
                        <button id="share-button" class="bg-white p-3 rounded-full shadow-md hover:bg-gray-100 transition-all" title="Partager"><svg class="w-6 h-6 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 12v8a2 2 0 002 2h12a2 2 0 002-2v-8m-4-6l-4-4m0 0L8 6m4-4v12"></path></svg></button>
                    </div>
                </div>
                <div class="flex items-center gap-6 text-sm text-gray-500 mb-6 border-y py-3">
                    <div class="flex items-center gap-2"><svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639l4.433-7.467a1.012 1.012 0 0 1 1.732 0l4.433 7.467a1.012 1.012 0 0 1 0 .639l-4.433 7.467a1.012 1.012 0 0 1-1.732 0l-4.433-7.467Z" /></svg><span id="camp-views" class="font-medium"></span> vues</div>
                    <div class="flex items-center gap-2"><svg class="w-5 h-5 text-red-500" fill="currentColor" viewBox="0 0 24 24"><path d="M11.645 20.91a.75.75 0 0 1-1.29 0A18.131 18.131 0 0 1 3.75 10.5a8.25 8.25 0 0 1 16.5 0c0 4.86-3.06 9.25-7.605 10.41Z" /></svg><span id="camp-likes" class="font-medium"></span> favoris</div>
                </div>
                <div class="prose max-w-none"><h2 class="text-2xl font-bold mb-4">Description du camp</h2><div id="camp-description" class="text-gray-700"></div></div>
            </div>
            
            <div class="lg:col-span-1">
                <div class="sticky top-24 bg-white p-6 rounded-xl shadow-lg border">
                    <div id="places-container" class="hidden text-center font-bold p-3 rounded-lg mb-4"><span id="camp-places"></span></div>
                    <h2 class="text-2xl font-bold mb-4">Votre séjour</h2>
                    <div class="space-y-4 mb-6">
                        <div class="flex items-start gap-3"><div class="flex-shrink-0 w-8 h-8 rounded-lg bg-blue-100 text-blue-600 flex items-center justify-center"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-5 h-5"><path d="M10 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6ZM3.465 14.493a1.23 1.23 0 0 0 .41 1.412A9.957 9.957 0 0 0 10 18c2.31 0 4.438-.784 6.131-2.095a1.23 1.23 0 0 0 .41-1.412A9.99 9.99 0 0 0 10 12.001c-2.31 0-4.438.784-6.131 2.094Z" /></svg></div><div><p class="font-semibold">Âge</p><p id="camp-age" class="text-gray-600"></p></div></div>
                        <div class="flex items-start gap-3"><div class="flex-shrink-0 w-8 h-8 rounded-lg bg-green-100 text-green-600 flex items-center justify-center"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-5 h-5"><path fill-rule="evenodd" d="M5.75 2a.75.75 0 0 1 .75.75V4h7V2.75a.75.75 0 0 1 1.5 0V4h.25A2.75 2.75 0 0 1 18 6.75v8.5A2.75 2.75 0 0 1 15.25 18H4.75A2.75 2.75 0 0 1 2 15.25v-8.5A2.75 2.75 0 0 1 4.75 4H5V2.75A.75.75 0 0 1 5.75 2Zm-1 5.5a.75.75 0 0 0 0 1.5h10.5a.75.75 0 0 0 0-1.5H4.75Z" clip-rule="evenodd" /></svg></div><div><p class="font-semibold">Dates</p><p id="camp-dates" class="text-gray-600"></p></div></div>
                    </div>
                    <div id="action-buttons" class="text-center border-t pt-6 space-y-3">
                        <p class="text-gray-600 -mb-2">À partir de</p>
                        <p id="camp-price" class="text-4xl font-bold text-gray-900"></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', async function() {
    const isLoggedIn = <?php echo json_encode($is_logged_in); ?>;
    const loader = document.getElementById('loader');

    // On utilise l'ID résolu par PHP (depuis le token ou l'id url)
    const campId = <?php echo json_encode($camp_id); ?>;

    if (!isLoggedIn) {
        loader.style.display = 'none';
        document.getElementById("auth-modal").classList.remove("hidden");
        document.body.classList.add("overflow-hidden");
        return;
    }

    if (!campId) {
        loader.innerHTML = '<p class="text-red-500 font-bold text-xl">Aucun camp sélectionné ou lien invalide.</p>';
        return;
    }

    const campContent = document.getElementById('camp-content');
    const favoriteButton = document.getElementById('favorite-button');
    const shareButton = document.getElementById('share-button');

    try {
        const response = await fetch(`api/get_camp_details.php?id=${campId}`);
        if (!response.ok) throw new Error('Camp introuvable.');
        const camp = await response.json();

        // Fonctions utilitaires d'affichage
        const safeSetText = (id, text) => {
            const el = document.getElementById(id);
            if (el) el.textContent = text;
        };
        const safeSetHTML = (id, html) => {
            const el = document.getElementById(id);
            if (el) el.innerHTML = html;
        };

        // Remplissage des données
        document.title = `${camp.nom} - ColoMap`;
        if (document.getElementById('camp-image')) document.getElementById('camp-image').src = camp.image_url;
        safeSetText('camp-name', camp.nom);
        safeSetText('camp-location', camp.ville);
        safeSetHTML('camp-description', camp.description);
        safeSetText('camp-views', camp.vues);
        safeSetText('camp-likes', camp.likes);
        safeSetText('camp-price', `${camp.prix}€`);
        safeSetText('camp-age', `${camp.age_min} - ${camp.age_max} ans`);
        
        const startDate = new Date(camp.date_debut).toLocaleDateString('fr-FR', { day: 'numeric', month: 'long' });
        const endDate = new Date(camp.date_fin).toLocaleDateString('fr-FR', { day: 'numeric', month: 'long', year: 'numeric' });
        safeSetText('camp-dates', `Du ${startDate} au ${endDate}`);

        const actionContainer = document.getElementById('action-buttons');
        const placesContainer = document.getElementById('places-container');

        // Gestion Boutons Inscription
        if (camp.inscription_en_ligne) {
            placesContainer.classList.remove('hidden');
            if (camp.places_restantes > 0) {
                if (camp.places_restantes < 10) {
                    placesContainer.innerHTML = `<span>Plus que ${camp.places_restantes} places !</span>`;
                    placesContainer.className = 'text-center font-bold p-3 rounded-lg mb-4 bg-red-100 text-red-800';
                } else {
                    placesContainer.innerHTML = `<span>${camp.places_restantes} places restantes</span>`;
                    placesContainer.className = 'text-center font-bold p-3 rounded-lg mb-4 bg-blue-50 text-blue-800';
                }
                
                // MODIFICATION : Utilisation du TOKEN dans le lien
                actionContainer.innerHTML += `<a href="inscription.php?t=${camp.token}" class="w-full block text-center bg-blue-600 text-white font-bold py-3 px-6 rounded-lg hover:bg-blue-700">S'inscrire en ligne</a>`;
            } else {
                placesContainer.innerHTML = 'Camp complet';
                placesContainer.className = 'text-center font-bold p-3 rounded-lg mb-4 bg-red-100 text-red-800';
            }
        } else if (camp.inscription_hors_ligne) {
            if (camp.lien_externe) {
                actionContainer.innerHTML += `<a href="${camp.lien_externe}" target="_blank" class="w-full block text-center bg-blue-600 text-white font-bold py-3 px-6 rounded-lg hover:bg-blue-700">Lien d'inscription</a>`;
            }
            if (camp.adresse_retour) {
                actionContainer.innerHTML += `<p class="text-xs text-gray-500 pt-2 text-center mt-2 border-t pt-2">* Dossier à retourner à :<br>${camp.adresse_retour}</p>`;
            }
        }

        // Bouton Contact Organisateur
        const contactBtnHtml = `<button id="contact-organizer-btn" data-organizer-id="${camp.organisateur_id}" class="w-full block text-center bg-gray-100 text-gray-800 font-bold py-3 px-6 rounded-lg hover:bg-gray-200 mt-2">Contacter l'organisateur</button>`;
        if(camp.organisateur_id) {
            actionContainer.innerHTML += contactBtnHtml;
            document.getElementById('contact-organizer-btn').addEventListener('click', handleContactClick);
        }

        loader.classList.add('hidden');
        campContent.classList.remove('hidden');

    } catch (error) {
        loader.innerHTML = `<p class="text-red-500 font-bold">${error.message}</p>`;
    }

    async function handleContactClick(event) {
        const organizerId = event.currentTarget.dataset.organizerId;
        event.currentTarget.textContent = 'Ouverture...';
        try {
            const response = await fetch('api/start_conversation.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ organisateurId: organizerId })
            });
            const result = await response.json();
            if(result.conversationId) {
                window.location.href = `messagerie.php?conv_id=${result.conversationId}`;
            } else {
                throw new Error(result.error || "Impossible de démarrer la conversation.");
            }
        } catch(e) {
            alert(e.message);
            event.currentTarget.textContent = "Contacter l'organisateur";
        }
    }

    if (favoriteButton) {
        favoriteButton.addEventListener('click', async function() {
            try {
                const response = await fetch('api/toggle_favorite.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ campId: campId })
                });
                const result = await response.json();
                if (response.ok) {
                    const svg = favoriteButton.querySelector('svg');
                    if (result.isFavorited) {
                        svg.classList.add('text-red-500', 'fill-current');
                        svg.classList.remove('text-gray-500');
                    } else {
                        svg.classList.remove('text-red-500', 'fill-current');
                        svg.classList.add('text-gray-500');
                    }
                } else {
                    throw new Error(result.error || "Erreur lors de la mise à jour des favoris.");
                }
            } catch (error) {
                alert(error.message);
            }
        });
    }

    if (shareButton) {
        shareButton.addEventListener('click', function() {
            if (navigator.share) {
                navigator.share({
                    title: document.title,
                    text: `Regardez ce camp sur ColoMap: ${camp.nom}`,
                    url: window.location.href
                }).catch(error => {
                    console.log('Erreur de partage:', error);
                });
            } else {
                // Fallback: copie dans le presse-papier
                navigator.clipboard.writeText(window.location.href).then(() => {
                    alert("Lien copié dans le presse-papier !");
                });
            }
        });
    }
});
</script>
</body>
</html>