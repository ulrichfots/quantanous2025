// Gestion du modal de confirmation de paiement
document.addEventListener('DOMContentLoaded', () => {
    const urlParams = new URLSearchParams(window.location.search);
    const status = urlParams.get('status');
    
    if (status === 'success') {
        const modal = document.getElementById('paymentSuccessModal');
        if (modal) {
            // Afficher le modal
            modal.classList.add('active');
            
            // Masquer le modal après 5 secondes
            setTimeout(() => {
                modal.classList.remove('active');
                // Retirer le paramètre status de l'URL sans recharger la page
                const newUrl = window.location.pathname;
                window.history.replaceState({}, document.title, newUrl);
            }, 5000);
        }
    }
});

