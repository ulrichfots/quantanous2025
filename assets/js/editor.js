// Gestion de l'éditeur de texte
document.addEventListener('DOMContentLoaded', () => {
    const editor = document.getElementById('editor');
    const saveBtn = document.getElementById('saveBtn');
    const formatBtns = document.querySelectorAll('.format-btn');

    if (!editor) return;

    // Fonction pour exécuter une commande de formatage
    function execCommand(command, value = null) {
        document.execCommand(command, false, value);
        editor.focus();
    }

    // Gestion des boutons de formatage
    formatBtns.forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            const command = btn.dataset.command;
            const value = btn.dataset.value || null;

            if (command === 'createLink') {
                const url = prompt('Entrez l\'URL du lien:', 'http://');
                if (url) {
                    execCommand(command, url);
                }
            } else {
                execCommand(command, value);
            }

            // Mettre à jour l'état visuel des boutons
            updateButtonStates();
        });
    });

    // Mettre à jour l'état visuel des boutons de formatage
    function updateButtonStates() {
        formatBtns.forEach(btn => {
            const command = btn.dataset.command;
            let isActive = false;

            try {
                switch(command) {
                    case 'bold':
                        isActive = document.queryCommandState('bold');
                        break;
                    case 'italic':
                        isActive = document.queryCommandState('italic');
                        break;
                    case 'underline':
                        isActive = document.queryCommandState('underline');
                        break;
                    case 'strikeThrough':
                        isActive = document.queryCommandState('strikeThrough');
                        break;
                }
                
                btn.classList.toggle('active', isActive);
            } catch (e) {
                // Ignorer les erreurs
            }
        });
    }

    // Écouter les changements de sélection
    editor.addEventListener('selectionchange', updateButtonStates);
    editor.addEventListener('keyup', updateButtonStates);
    editor.addEventListener('mouseup', updateButtonStates);

    // Gestion de la sauvegarde
    if (saveBtn) {
        saveBtn.addEventListener('click', async () => {
            const content = editor.innerHTML;
            
            // Déterminer le type de contenu en fonction de la page
            const currentPage = window.location.pathname.split('/').pop();
            let contentType = 'presentation';
            
            if (currentPage === 'admin-explications.php') {
                contentType = 'explications';
            } else if (currentPage === 'admin-presentation.php') {
                contentType = 'presentation';
            }
            
            // Sauvegarder via API
            try {
                saveBtn.disabled = true;
                saveBtn.style.opacity = '0.6';

                const response = await fetch('api.php/save-presentation', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        content: content,
                        type: contentType
                    })
                });

                const result = await response.json();

                if (response.ok) {
                    // Afficher un message de confirmation
                    showSaveMessage('Sauvegardé avec succès !');
                    // Rediriger vers la page d'accueil après 1.5 secondes
                    setTimeout(() => {
                        window.location.href = 'index.php';
                    }, 1500);
                } else {
                    showSaveMessage('Erreur lors de la sauvegarde', 'error');
                }
            } catch (error) {
                showSaveMessage('Erreur de connexion', 'error');
            } finally {
                saveBtn.disabled = false;
                saveBtn.style.opacity = '1';
            }
        });
    }

    // Fonction pour afficher un message de sauvegarde
    function showSaveMessage(message, type = 'success') {
        // Créer un élément de message temporaire
        const messageEl = document.createElement('div');
        messageEl.className = `save-message ${type}`;
        messageEl.textContent = message;
        document.body.appendChild(messageEl);

        // Afficher le message
        setTimeout(() => {
            messageEl.classList.add('show');
        }, 10);

        // Masquer après 3 secondes
        setTimeout(() => {
            messageEl.classList.remove('show');
            setTimeout(() => {
                document.body.removeChild(messageEl);
            }, 300);
        }, 3000);
    }

    // Prévenir la perte de données lors de la navigation
    let hasUnsavedChanges = false;

    editor.addEventListener('input', () => {
        hasUnsavedChanges = true;
    });

    window.addEventListener('beforeunload', (e) => {
        if (hasUnsavedChanges) {
            e.preventDefault();
            e.returnValue = 'Vous avez des modifications non sauvegardées. Êtes-vous sûr de vouloir quitter ?';
        }
    });

    // Marquer comme sauvegardé après une sauvegarde réussie
    if (saveBtn) {
        const originalClick = saveBtn.onclick;
        saveBtn.addEventListener('click', () => {
            setTimeout(() => {
                hasUnsavedChanges = false;
            }, 1000);
        });
    }
});

