let deferredPrompt;

// Écouter l'événement beforeinstallprompt
window.addEventListener('beforeinstallprompt', (e) => {
    // Empêcher l'affichage automatique du prompt
    e.preventDefault();
    // Sauvegarder l'événement pour l'utiliser plus tard
    deferredPrompt = e;
    
    // Afficher le bouton d'installation
    const installBtn = document.getElementById('installBtn');
    if (installBtn) {
        installBtn.style.display = 'block';
    }
});

// Gérer le clic sur le bouton d'installation
document.addEventListener('DOMContentLoaded', () => {
    const installBtn = document.getElementById('installBtn');
    const resultBox = document.getElementById('result');
    
    if (installBtn) {
        installBtn.addEventListener('click', async () => {
            if (!deferredPrompt) {
                // Si le prompt n'est pas disponible, donner des instructions
                resultBox.textContent = 'Pour installer cette application:\n\n' +
                    'Sur Android: Menu du navigateur → "Ajouter à l\'écran d\'accueil"\n\n' +
                    'Sur iOS: Safari → Partager → "Sur l\'écran d\'accueil"';
                resultBox.className = 'result-box show';
                return;
            }
            
            // Afficher le prompt d'installation
            deferredPrompt.prompt();
            
            // Attendre la réponse de l'utilisateur
            const { outcome } = await deferredPrompt.userChoice;
            
            if (outcome === 'accepted') {
                resultBox.textContent = 'Application installée avec succès !';
                resultBox.className = 'result-box show success';
            } else {
                resultBox.textContent = 'Installation annulée par l\'utilisateur.';
                resultBox.className = 'result-box show';
            }
            
            // Réinitialiser le prompt
            deferredPrompt = null;
            installBtn.style.display = 'none';
        });
        
        // Vérifier si l'app est déjà installée
        if (window.matchMedia('(display-mode: standalone)').matches) {
            installBtn.style.display = 'none';
            if (resultBox) {
                resultBox.textContent = 'Application déjà installée !';
                resultBox.className = 'result-box show success';
            }
        } else {
            // Vérifier si le bouton doit être affiché
            installBtn.style.display = deferredPrompt ? 'block' : 'none';
        }
    }
});

// Détecter si l'app a été installée depuis le navigateur
window.addEventListener('appinstalled', () => {
    const installBtn = document.getElementById('installBtn');
    if (installBtn) {
        installBtn.style.display = 'none';
    }
    
    const resultBox = document.getElementById('result');
    if (resultBox) {
        resultBox.textContent = 'Application installée avec succès !';
        resultBox.className = 'result-box show success';
    }
    
    deferredPrompt = null;
});

