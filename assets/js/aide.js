// Gestion spécifique de la page Aide avec pagination automatique
document.addEventListener('DOMContentLoaded', () => {
    const contentContainer = document.getElementById('aideContent');
    const paginationContainer = document.getElementById('aidePagination');
    const prevBtn = document.getElementById('paginationPrev');
    const nextBtn = document.getElementById('paginationNext');
    const infoSpan = document.getElementById('paginationInfo');

    if (!contentContainer || !paginationContainer) {
        return;
    }

    // Hauteur maximale par page (en pixels) - ajustable selon les besoins
    const MAX_HEIGHT_PER_PAGE = window.innerHeight * 0.6; // 60% de la hauteur de l'écran
    let currentPage = 0;
    let pages = [];

    function createPagination() {
        // Cloner le contenu original
        const originalContent = contentContainer.cloneNode(true);
        const paragraphs = Array.from(originalContent.querySelectorAll('p'));
        
        if (paragraphs.length === 0) {
            // Si pas de paragraphes, essayer de diviser le texte
            const textContent = contentContainer.textContent || contentContainer.innerText;
            if (textContent.trim().length === 0) {
                return; // Pas de contenu
            }
        }

        // Vider le conteneur
        contentContainer.innerHTML = '';

        // Créer les pages
        let currentPageContent = [];
        let currentHeight = 0;
        let tempDiv = document.createElement('div');
        tempDiv.style.visibility = 'hidden';
        tempDiv.style.position = 'absolute';
        tempDiv.style.width = contentContainer.offsetWidth + 'px';
        document.body.appendChild(tempDiv);

        paragraphs.forEach((para, index) => {
            const paraClone = para.cloneNode(true);
            tempDiv.appendChild(paraClone);
            const paraHeight = tempDiv.offsetHeight;
            tempDiv.removeChild(paraClone);

            // Si ajouter ce paragraphe dépasse la hauteur max, créer une nouvelle page
            if (currentHeight + paraHeight > MAX_HEIGHT_PER_PAGE && currentPageContent.length > 0) {
                // Sauvegarder la page actuelle
                pages.push([...currentPageContent]);
                currentPageContent = [];
                currentHeight = 0;
            }

            currentPageContent.push(para);
            currentHeight += paraHeight;
        });

        // Ajouter la dernière page
        if (currentPageContent.length > 0) {
            pages.push(currentPageContent);
        }

        // Nettoyer
        document.body.removeChild(tempDiv);

        // Si une seule page ou moins, pas besoin de pagination
        if (pages.length <= 1) {
            paginationContainer.style.display = 'none';
            // Remettre le contenu original
            contentContainer.innerHTML = originalContent.innerHTML;
            return;
        }

        // Afficher la pagination
        paginationContainer.style.display = 'flex';
        displayPage(0);
    }

    function displayPage(pageIndex) {
        if (pageIndex < 0 || pageIndex >= pages.length) {
            return;
        }

        currentPage = pageIndex;
        contentContainer.innerHTML = '';

        // Créer un conteneur pour la page
        const pageDiv = document.createElement('div');
        pageDiv.className = 'aide-content-page active';
        pages[currentPage].forEach(para => {
            pageDiv.appendChild(para.cloneNode(true));
        });
        contentContainer.appendChild(pageDiv);

        // Mettre à jour les boutons
        prevBtn.style.display = currentPage > 0 ? 'block' : 'none';
        nextBtn.style.display = currentPage < pages.length - 1 ? 'block' : 'none';
        infoSpan.textContent = `Page ${currentPage + 1} / ${pages.length}`;

        // Scroll vers le haut du contenu
        contentContainer.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    // Event listeners
    if (prevBtn) {
        prevBtn.addEventListener('click', () => {
            if (currentPage > 0) {
                displayPage(currentPage - 1);
            }
        });
    }

    if (nextBtn) {
        nextBtn.addEventListener('click', () => {
            if (currentPage < pages.length - 1) {
                displayPage(currentPage + 1);
            }
        });
    }

    // Créer la pagination après un court délai pour s'assurer que le contenu est rendu
    setTimeout(() => {
        createPagination();
    }, 100);

    // Recréer la pagination si la fenêtre est redimensionnée
    let resizeTimeout;
    window.addEventListener('resize', () => {
        clearTimeout(resizeTimeout);
        resizeTimeout = setTimeout(() => {
            createPagination();
        }, 250);
    });
});

