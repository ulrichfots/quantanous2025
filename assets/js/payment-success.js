// Gestion du modal de confirmation de paiement
document.addEventListener('DOMContentLoaded', () => {
    const urlParams = new URLSearchParams(window.location.search);
    const status = urlParams.get('status');
    
    if (status === 'success') {
        const modal = document.getElementById('paymentSuccessModal');
        if (modal) {
            // Forcer le reflow pour que l'animation fonctionne
            void modal.offsetHeight;
            
            // Afficher le modal avec un léger délai pour l'animation
            setTimeout(() => {
                modal.classList.add('active');
            }, 10);
            
            // Masquer le modal après 5 secondes avec animation de fermeture
            setTimeout(() => {
                modal.classList.remove('active');
                
                // Retirer le paramètre status de l'URL après la fermeture
                setTimeout(() => {
                    const newUrl = window.location.pathname;
                    window.history.replaceState({}, document.title, newUrl);
                }, 300); // Attendre la fin de l'animation
            }, 5000);
        }
    }
});

