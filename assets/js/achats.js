// Gestion de la page Achats
document.addEventListener('DOMContentLoaded', () => {
    const donnerBtns = document.querySelectorAll('.achat-donner-btn');
    
    donnerBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            // Vérifier si le bouton est désactivé
            if (btn.disabled || btn.classList.contains('disabled')) {
                return;
            }
            
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

    // Gestion de la lightbox pour les images
    const lightbox = document.getElementById('imageLightbox');
    const lightboxImage = document.getElementById('lightboxImage');
    const lightboxClose = document.getElementById('lightboxClose');
    const lightboxPrev = document.getElementById('lightboxPrev');
    const lightboxNext = document.getElementById('lightboxNext');
    const lightboxCounter = document.getElementById('lightboxCounter');
    
    // Attendre que le DOM soit complètement chargé avant de sélectionner les wrappers
    const imageWrappers = document.querySelectorAll('.achat-image-wrapper');

    let currentImages = [];
    let currentIndex = 0;

    function openLightbox(images, index = 0) {
        if (!images || images.length === 0) return;
        
        currentImages = images;
        currentIndex = index;
        updateLightbox();
        lightbox.classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    function closeLightbox() {
        lightbox.classList.remove('active');
        document.body.style.overflow = '';
        currentImages = [];
        currentIndex = 0;
    }

    function updateLightbox() {
        if (currentImages.length === 0) return;
        
        lightboxImage.src = currentImages[currentIndex];
        lightboxCounter.textContent = `${currentIndex + 1} / ${currentImages.length}`;
        
        lightboxPrev.style.display = currentImages.length > 1 ? 'flex' : 'none';
        lightboxNext.style.display = currentImages.length > 1 ? 'flex' : 'none';
        lightboxCounter.style.display = currentImages.length > 1 ? 'block' : 'none';
    }

    function nextImage() {
        if (currentImages.length === 0) return;
        currentIndex = (currentIndex + 1) % currentImages.length;
        updateLightbox();
    }

    function prevImage() {
        if (currentImages.length === 0) return;
        currentIndex = (currentIndex - 1 + currentImages.length) % currentImages.length;
        updateLightbox();
    }

    // Ouvrir la lightbox au clic sur une image
    // Utiliser la délégation d'événements pour gérer les images dynamiques
    document.addEventListener('click', (e) => {
        const clickedImage = e.target.closest('.achat-image-wrapper .achat-image');
        if (!clickedImage) return;
        
        const wrapper = clickedImage.closest('.achat-image-wrapper');
        if (!wrapper) return;
        
        e.stopPropagation();
        e.preventDefault();
        
        const imagesJson = wrapper.dataset.images;
        if (imagesJson) {
            try {
                const images = JSON.parse(imagesJson);
                if (images && Array.isArray(images) && images.length > 0) {
                    // Filtrer les images vides
                    const validImages = images.filter(img => img && typeof img === 'string' && img.trim() !== '');
                    if (validImages.length > 0) {
                        openLightbox(validImages, 0);
                    }
                }
            } catch (error) {
                // Erreur de parsing JSON - ignorer silencieusement
            }
        }
    });
    
    // Ajouter le style cursor pointer à toutes les images cliquables
    imageWrappers.forEach(wrapper => {
        const img = wrapper.querySelector('.achat-image.clickable-image');
        if (img) {
            img.style.cursor = 'pointer';
        }
    });

    // Fermer la lightbox
    if (lightboxClose) {
        lightboxClose.addEventListener('click', closeLightbox);
    }

    // Navigation
    if (lightboxNext) {
        lightboxNext.addEventListener('click', (e) => {
            e.stopPropagation();
            nextImage();
        });
    }

    if (lightboxPrev) {
        lightboxPrev.addEventListener('click', (e) => {
            e.stopPropagation();
            prevImage();
        });
    }

    // Fermer en cliquant sur le fond
    if (lightbox) {
        lightbox.addEventListener('click', (e) => {
            if (e.target === lightbox) {
                closeLightbox();
            }
        });
    }

    // Navigation au clavier
    document.addEventListener('keydown', (e) => {
        if (!lightbox.classList.contains('active')) return;
        
        if (e.key === 'Escape') {
            closeLightbox();
        } else if (e.key === 'ArrowRight') {
            nextImage();
        } else if (e.key === 'ArrowLeft') {
            prevImage();
        }
    });
});

