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
        
        // Créer un div temporaire pour mesurer (doit être créé avant splitParagraphIntoChunks)
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
        
        // Fonction pour diviser un paragraphe en plusieurs paragraphes plus petits
        function splitParagraphIntoChunks(para) {
            const chunks = [];
            const text = para.textContent || para.innerText;
            
            // Si le paragraphe contient des <br>, diviser par <br>
            if (para.innerHTML && para.innerHTML.includes('<br')) {
                // Diviser en préservant le HTML
                const htmlContent = para.innerHTML;
                const parts = htmlContent.split(/<br\s*\/?>/i);
                parts.forEach((part, index) => {
                    const trimmed = part.trim();
                    if (trimmed) {
                        const p = document.createElement('p');
                        // Préserver le HTML original
                        p.innerHTML = trimmed;
                        chunks.push(p);
                    } else if (index === 0 && parts.length > 1) {
                        // Si la première partie est vide mais qu'il y a d'autres parties, créer un paragraphe vide
                        const p = document.createElement('p');
                        p.innerHTML = '&nbsp;';
                        chunks.push(p);
                    }
                });
            } else {
                // Sinon, diviser par phrases (point suivi d'un espace ou virgule)
                const sentences = text.split(/(?<=[.!?,])\s+/);
                let currentChunk = '';
                
                sentences.forEach(sentence => {
                    const testChunk = currentChunk + (currentChunk ? ' ' : '') + sentence;
                    const testP = document.createElement('p');
                    testP.textContent = testChunk;
                    tempDiv.appendChild(testP);
                    const testHeight = tempDiv.offsetHeight;
                    tempDiv.removeChild(testP);
                    
                    // Si le chunk dépasse la limite, sauvegarder le précédent et commencer un nouveau
                    if (testHeight > MAX_HEIGHT_PER_PAGE * 0.8 && currentChunk) {
                        const p = document.createElement('p');
                        p.textContent = currentChunk;
                        chunks.push(p);
                        currentChunk = sentence;
                    } else {
                        currentChunk = testChunk;
                    }
                });
                
                if (currentChunk) {
                    const p = document.createElement('p');
                    p.textContent = currentChunk;
                    chunks.push(p);
                }
            }
            
            return chunks.length > 0 ? chunks : [para];
        }
        
        // Récupérer tous les éléments enfants
        const allElements = Array.from(originalContent.childNodes).filter(node => {
            if (node.nodeType === Node.TEXT_NODE) {
                return node.textContent.trim().length > 0;
            }
            return node.nodeType === Node.ELEMENT_NODE;
        });
        
        if (allElements.length === 0) {
            const textContent = contentContainer.textContent || contentContainer.innerText;
            if (textContent.trim().length === 0) {
                paginationContainer.style.display = 'none';
                return;
            }
        }

        // Vider le conteneur temporairement pour mesurer
        contentContainer.innerHTML = '';

        // Traiter chaque élément et diviser les gros paragraphes
        let processedElements = [];
        allElements.forEach((element) => {
            if (element.nodeType === Node.TEXT_NODE) {
                const p = document.createElement('p');
                p.textContent = element.textContent;
                processedElements.push(...splitParagraphIntoChunks(p));
            } else if (element.tagName === 'P') {
                // Si c'est un paragraphe, vérifier s'il est trop long
                tempDiv.appendChild(element.cloneNode(true));
                const paraHeight = tempDiv.offsetHeight;
                tempDiv.removeChild(tempDiv.firstChild);
                
                if (paraHeight > MAX_HEIGHT_PER_PAGE * 0.8) {
                    // Diviser le paragraphe
                    processedElements.push(...splitParagraphIntoChunks(element));
                } else {
                    processedElements.push(element);
                }
            } else {
                // Pour les autres éléments (images, divs, etc.), les garder tels quels
                processedElements.push(element);
            }
        });

        // Créer les pages à partir des éléments traités
        let currentPageContent = [];
        let currentHeight = 0;

        processedElements.forEach((element) => {
            const elementClone = element.cloneNode(true);
            
            // Mesurer la hauteur de l'élément
            const previousHeight = tempDiv.offsetHeight;
            tempDiv.appendChild(elementClone);
            
            // Gérer les images
            const images = elementClone.querySelectorAll('img');
            if (images.length > 0) {
                images.forEach(img => {
                    if (!img.complete) {
                        img.style.height = 'auto';
                    }
                });
            }
            
            const newHeight = tempDiv.offsetHeight;
            const elementHeight = Math.max(newHeight - previousHeight, 50);
            tempDiv.removeChild(elementClone);

            // Si ajouter cet élément dépasse la hauteur max, créer une nouvelle page
            if (currentHeight + elementHeight > MAX_HEIGHT_PER_PAGE && currentPageContent.length > 0) {
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

