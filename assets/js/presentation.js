// Gestion de la pagination pour la page Présentation
document.addEventListener('DOMContentLoaded', () => {
    const contentContainer = document.querySelector('.content-text');
    const contentSection = document.querySelector('.content-section');
    
    if (!contentContainer || !contentSection) {
        return;
    }

    // Créer le conteneur de pagination
    const paginationContainer = document.createElement('div');
    paginationContainer.className = 'content-pagination';
    paginationContainer.id = 'contentPagination';
    paginationContainer.innerHTML = `
        <button class="pagination-btn pagination-prev" id="contentPaginationPrev" style="display: none;">
            ‹ Précédent
        </button>
        <span class="pagination-info" id="contentPaginationInfo"></span>
        <button class="pagination-btn pagination-next" id="contentPaginationNext" style="display: none;">
            Suivant ›
        </button>
    `;
    
    // Insérer la pagination après le contenu
    contentContainer.parentNode.insertBefore(paginationContainer, contentContainer.nextSibling);
    
    const prevBtn = document.getElementById('contentPaginationPrev');
    const nextBtn = document.getElementById('contentPaginationNext');
    const infoSpan = document.getElementById('contentPaginationInfo');

    // Hauteur maximale par page (en pixels) - ajustable selon les besoins
    const MAX_HEIGHT_PER_PAGE = window.innerHeight * 0.5; // 50% de la hauteur de l'écran
    let currentPage = 0;
    let pages = [];

    function createPagination() {
        // Cloner le contenu original
        const originalContent = contentContainer.cloneNode(true);
        const originalHTML = contentContainer.innerHTML;
        
        // Récupérer tous les éléments enfants (paragraphes, images, divs, listes, etc.)
        const allElements = Array.from(originalContent.childNodes).filter(node => {
            // Filtrer les nœuds texte vides et garder les éléments HTML
            if (node.nodeType === Node.TEXT_NODE) {
                return node.textContent.trim().length > 0;
            }
            return node.nodeType === Node.ELEMENT_NODE;
        });
        
        if (allElements.length === 0) {
            // Si pas d'éléments, vérifier le contenu texte
            const textContent = contentContainer.textContent || contentContainer.innerText;
            if (textContent.trim().length === 0) {
                paginationContainer.style.display = 'none';
                return; // Pas de contenu
            }
        }

        // Vider le conteneur temporairement pour mesurer
        contentContainer.innerHTML = '';

        // Créer les pages
        let currentPageContent = [];
        let currentHeight = 0;
        let tempDiv = document.createElement('div');
        tempDiv.style.visibility = 'hidden';
        tempDiv.style.position = 'absolute';
        tempDiv.style.width = contentContainer.offsetWidth + 'px';
        tempDiv.className = contentContainer.className;
        const computedStyle = window.getComputedStyle(contentContainer);
        tempDiv.style.padding = computedStyle.padding;
        tempDiv.style.margin = computedStyle.margin;
        tempDiv.style.fontSize = computedStyle.fontSize;
        tempDiv.style.lineHeight = computedStyle.lineHeight;
        document.body.appendChild(tempDiv);

        // Traiter chaque élément (paragraphes, images, divs, listes, etc.)
        allElements.forEach((element, index) => {
            let elementClone;
            let elementHeight = 0;
            
            if (element.nodeType === Node.TEXT_NODE) {
                // Pour les nœuds texte, créer un paragraphe
                const p = document.createElement('p');
                p.textContent = element.textContent;
                elementClone = p;
            } else {
                elementClone = element.cloneNode(true);
            }
            
            // Mesurer la hauteur de l'élément
            const previousHeight = tempDiv.offsetHeight;
            tempDiv.appendChild(elementClone);
            
            // Si c'est une image, attendre qu'elle soit chargée
            const images = elementClone.querySelectorAll('img');
            if (images.length > 0) {
                // Pour les images, on attend qu'elles soient chargées
                // Mais pour la pagination, on utilise une hauteur approximative
                images.forEach(img => {
                    if (!img.complete) {
                        // Si l'image n'est pas chargée, utiliser une hauteur par défaut
                        img.style.height = 'auto';
                    }
                });
            }
            
            const newHeight = tempDiv.offsetHeight;
            elementHeight = Math.max(newHeight - previousHeight, 50); // Minimum 50px pour éviter les erreurs
            tempDiv.removeChild(elementClone);

            // Si ajouter cet élément dépasse la hauteur max, créer une nouvelle page
            if (currentHeight + elementHeight > MAX_HEIGHT_PER_PAGE && currentPageContent.length > 0) {
                // Sauvegarder la page actuelle
                pages.push([...currentPageContent]);
                currentPageContent = [];
                currentHeight = 0;
            }

            currentPageContent.push(element);
            currentHeight += elementHeight;
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
            contentContainer.innerHTML = originalHTML;
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
        pageDiv.className = 'content-page active';
        pages[currentPage].forEach(element => {
            if (element.nodeType === Node.TEXT_NODE) {
                // Pour les nœuds texte, créer un paragraphe
                const p = document.createElement('p');
                p.textContent = element.textContent;
                pageDiv.appendChild(p);
            } else {
                pageDiv.appendChild(element.cloneNode(true));
            }
        });
        contentContainer.appendChild(pageDiv);

        // Mettre à jour les boutons
        if (prevBtn) {
            prevBtn.style.display = currentPage > 0 ? 'block' : 'none';
        }
        if (nextBtn) {
            nextBtn.style.display = currentPage < pages.length - 1 ? 'block' : 'none';
        }
        if (infoSpan) {
            infoSpan.textContent = `Page ${currentPage + 1} / ${pages.length}`;
        }

        // Scroll vers le haut du contenu
        const title = document.querySelector('.content-title');
        if (title) {
            title.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
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

