// Gestion de la page Achats
document.addEventListener('DOMContentLoaded', () => {
    const donnerBtns = document.querySelectorAll('.achat-donner-btn');
    
    donnerBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            const articleId = btn.dataset.articleId || '';
            const prix = btn.dataset.prix || btn.closest('.achat-item').querySelector('.achat-price').textContent.trim();
            
            // Extraire le prix numérique
            const prixNumerique = parseFloat(prix.replace(/[^\d,.]/g, '').replace(',', '.'));
            
            if (isNaN(prixNumerique) || prixNumerique <= 0) {
                alert('Erreur: Impossible de déterminer le prix de cet article.');
                return;
            }
            
            // Rediriger vers la page de paiement avec les paramètres
            const params = new URLSearchParams({
                article_id: articleId,
                montant: prixNumerique.toFixed(2),
                from: 'achats'
            });
            
            window.location.href = `paiement.php?${params.toString()}`;
        });
    });
});

