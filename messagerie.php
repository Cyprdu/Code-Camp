<?php
require_once 'partials/header.php';
// Sécurité : l'utilisateur doit être connecté pour accéder à sa messagerie.
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}
$current_user_id = $_SESSION['user']['id'];
?>
<title>Ma Messagerie - ColoMap</title>

<main class="h-[calc(100vh-64px)] container mx-auto p-0 sm:p-4">
    <div class="h-full border-0 sm:border bg-white sm:rounded-xl shadow-lg flex">
        
        <aside id="conversations-column" class="w-full md:w-1/3 border-r h-full flex flex-col transition-transform duration-300 ease-in-out">
            <div class="p-4 border-b">
                <h1 class="text-2xl sm:text-3xl font-bold text-center mb-2">
                    Mes <span class="bg-gradient-to-r from-blue-500 to-pink-500 text-transparent bg-clip-text">Conversations</span>
                </h1>
                <p class="text-center text-gray-500 text-xs sm:text-sm mb-4">Retrouvez ici toutes vos discussions.</p>
                <div class="relative">
                    <input type="text" id="search-conversations" placeholder="Rechercher..." class="w-full p-2 pl-10 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <svg class="w-5 h-5 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                         <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                    </svg>
                </div>
            </div>
            <div id="conversations-list" class="flex-grow overflow-y-auto">
                <div class="p-4 text-center text-gray-500">Chargement...</div>
            </div>
        </aside>

        <section id="active-conversation" class="hidden md:flex w-full md:w-2/3 h-full flex-col bg-gray-50">
            <div id="conversation-header" class="p-4 border-b flex items-center bg-white">
                <button id="back-to-convos-btn" class="md:hidden mr-4 p-2 rounded-full hover:bg-gray-100">
                    <svg class="w-6 h-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" /></svg>
                </button>
                <p id="conversation-title" class="font-bold text-lg"></p>
            </div>
            
            <div id="messages-container" class="flex-grow p-6 overflow-y-auto space-y-4">
                <div id="welcome-message" class="h-full flex items-center justify-center text-gray-500">
                     <p>Sélectionnez une conversation pour commencer.</p>
                </div>
            </div>

            <div id="message-form-container" class="p-4 border-t bg-white hidden">
                <form id="send-message-form" class="flex items-center gap-4">
                     <input type="text" id="message-input" placeholder="Taper un message..." class="flex-grow p-3 border rounded-full focus:outline-none focus:ring-2 focus:ring-blue-500" autocomplete="off">
                    <button type="submit" class="bg-blue-600 text-white p-3 rounded-full hover:bg-blue-700 active:bg-blue-800 transition-colors duration-200">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-6 h-6">
                             <path d="M3.478 2.405a.75.75 0 00-.926.94l2.432 7.905H13.5a.75.75 0 010 1.5H4.984l-2.432 7.905a.75.75 0 00.926.94 60.519 60.519 0 0018.445-8.986.75.75 0 000-1.218A60.517 60.517 0 003.478 2.405z" />
                        </svg>
                    </button>
                </form>
            </div>
        </section>
    </div>
</main>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const currentUserId = '<?php echo $current_user_id; ?>';
    const conversationsColumn = document.getElementById('conversations-column');
    const activeConversationColumn = document.getElementById('active-conversation');
    const conversationsList = document.getElementById('conversations-list');
    const conversationHeader = document.getElementById('conversation-header');
    const conversationTitle = document.getElementById('conversation-title');
    const messagesContainer = document.getElementById('messages-container');
    const welcomeMessage = document.getElementById('welcome-message');
    const messageFormContainer = document.getElementById('message-form-container');
    const sendMessageForm = document.getElementById('send-message-form');
    const messageInput = document.getElementById('message-input');
    const backToConvosBtn = document.getElementById('back-to-convos-btn');
    const searchConversationsInput = document.getElementById('search-conversations');
    
    let activeConversationId = null;
    
    function filterConversations() {
        const searchTerm = searchConversationsInput.value.toLowerCase();
        const conversationItems = conversationsList.querySelectorAll('.conversation-item');
        conversationItems.forEach(item => {
            const name = item.dataset.name.toLowerCase();
            if (name.includes(searchTerm)) {
                item.style.display = 'flex';
            } else {
                item.style.display = 'none';
            }
        });
    }

    searchConversationsInput.addEventListener('input', filterConversations);

    async function loadConversations() {
        try {
            const response = await fetch('api/get_conversations.php');
            const conversations = await response.json();
            conversationsList.innerHTML = '';
            if (conversations.length === 0) {
                 conversationsList.innerHTML = `<div class="flex flex-col items-center justify-center h-full p-4 text-center text-gray-500"><svg class="w-16 h-16 mb-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H8.25m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H12m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 0 1-2.555-.337A5.972 5.972 0 0 1 5.41 20.97a5.969 5.969 0 0 1-.474-.065 4.48 4.48 0 0 0 .978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25Z" /></svg><p class="font-semibold text-lg text-gray-700">Vous n'avez pas de conversation</p><p class="text-sm">Commencez une discussion pour la voir ici.</p></div>`;
                return;
            }
            
            for (const convo of conversations) {
                let lastMessageContent = 'Aucun message';
                try {
                    const messagesResponse = await fetch(`api/get_messages.php?id=${convo.id}`);
                    if (messagesResponse.ok) {
                        const messages = await messagesResponse.json();
                        if (messages.length > 0) {
                            messages.sort((a, b) => new Date(a.fields["Date d'envoi"]) - new Date(b.fields["Date d'envoi"]));
                            lastMessageContent = messages[messages.length - 1].fields.Contenu;
                        }
                    }
                } catch (e) { /* On ignore les erreurs ici pour ne pas bloquer l'affichage de la liste */ }

                const convoElement = document.createElement('div');
                convoElement.className = 'flex items-center p-4 border-b cursor-pointer hover:bg-gray-100 transition-colors conversation-item';
                convoElement.dataset.id = convo.id;
                convoElement.dataset.name = convo.displayName;
                const initial = convo.displayName ? convo.displayName.charAt(0).toUpperCase() : '?';
                const avatarUrl = `https://placehold.co/40x40/e2e8f0/2563eb?text=${initial}`;
                convoElement.innerHTML = `
                    <img src="${avatarUrl}" alt="Avatar for ${convo.displayName}" class="w-10 h-10 rounded-full mr-4 flex-shrink-0">
                    <div class="flex-grow overflow-hidden">
                        <p class="font-bold truncate">${convo.displayName}</p>
                         <p class="text-sm text-gray-500 truncate">${lastMessageContent}</p>
                    </div>
                `;
                convoElement.addEventListener('click', () => loadMessages(convo.id, convo.displayName));
                conversationsList.appendChild(convoElement);
            }
        } catch (error) {
            conversationsList.innerHTML = '<p class="text-center text-red-500 p-4">Erreur de chargement des conversations.</p>';
        }
    }

    async function loadMessages(conversationId, name) {
        if (window.innerWidth < 768) { 
            conversationsColumn.classList.add('hidden');
            activeConversationColumn.classList.remove('hidden');
            activeConversationColumn.classList.add('w-full');
        }

        activeConversationId = conversationId;
        welcomeMessage.classList.add('hidden');
        conversationHeader.classList.remove('hidden');
        messageFormContainer.classList.remove('hidden');
        conversationTitle.textContent = name;
        messagesContainer.innerHTML = '<p class="text-center text-gray-400">Chargement...</p>';

        try {
            const response = await fetch(`api/get_messages.php?id=${conversationId}`);
            if (!response.ok) { throw new Error('La réponse du serveur n\'est pas OK'); }

            const messages = await response.json();
            
            // --- AJOUT DE LA LIGNE DE TRI ---
            // On trie les messages par date, du plus ancien au plus récent, juste après les avoir reçus.
            messages.sort((a, b) => new Date(a.fields["Date d'envoi"]) - new Date(b.fields["Date d'envoi"]));

            messagesContainer.innerHTML = '';
            messages.forEach(msg => {
                const isSent = msg.fields.Auteur && msg.fields.Auteur[0] === currentUserId;
                const sentDate = new Date(msg.fields["Date d'envoi"]);
                const timeString = sentDate.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });

                const messageWrapper = document.createElement('div');
                messageWrapper.className = `flex w-full ${isSent ? 'justify-end' : 'justify-start'}`;
                
                const messageContent = document.createElement('div');
                messageContent.className = 'flex flex-col max-w-[80%] md:max-w-md';

                const messageBubble = document.createElement('div');
                messageBubble.className = `px-4 py-2 rounded-2xl break-words ${isSent ? 'bg-blue-600 text-white rounded-br-none' : 'bg-gray-200 text-gray-800 rounded-bl-none'}`;
                messageBubble.textContent = msg.fields.Contenu;
                
                const timeElement = document.createElement('div');
                timeElement.className = `text-xs text-gray-400 mt-1 px-1 ${isSent ? 'text-right' : 'text-left'}`;
                timeElement.textContent = timeString;

                messageContent.appendChild(messageBubble);
                messageContent.appendChild(timeElement);
                messageWrapper.appendChild(messageContent);
                messagesContainer.appendChild(messageWrapper);
            });

            setTimeout(() => {
                messagesContainer.scrollTop = messagesContainer.scrollHeight;
            }, 0);
        } catch (error) {
            console.error("Erreur détaillée:", error);
            messagesContainer.innerHTML = '<p class="text-center text-red-500 p-4">Impossible de charger les messages.</p>';
        }
    }

    sendMessageForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        const content = messageInput.value.trim();
        if (!content || !activeConversationId) return;

        const originalMessage = content;
        messageInput.value = '';
        messageInput.disabled = true;

        try {
            const response = await fetch('api/send_message.php', { 
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ conversationId: activeConversationId, content: originalMessage })
            });
            if (!response.ok) throw new Error("Erreur d'envoi");
            
            await loadMessages(activeConversationId, conversationTitle.textContent);
            await loadConversations();

        } catch (error) {
            console.error('Erreur d\'envoi:', error);
            messageInput.value = originalMessage; 
            alert("Le message n'a pas pu être envoyé.");
        } finally {
            messageInput.disabled = false;
            messageInput.focus();
        }
    });

    backToConvosBtn.addEventListener('click', () => {
        conversationsColumn.classList.remove('hidden');
        activeConversationColumn.classList.add('hidden');
    });

    loadConversations();
    
    const urlParams = new URLSearchParams(window.location.search);
    const convIdFromUrl = urlParams.get('conv_id');
    if (convIdFromUrl) {
        setTimeout(async () => {
            await loadConversations(); 
            const targetConvo = conversationsList.querySelector(`[data-id="${convIdFromUrl}"]`);
            if (targetConvo) {
                loadMessages(convIdFromUrl, targetConvo.dataset.name);
            }
        }, 300);
    }
});
</script>
</body>
</html>