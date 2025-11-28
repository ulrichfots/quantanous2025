// Gestion de la page Admin Achats
document.addEventListener('DOMContentLoaded', () => {
    const MAX_IMAGES = 6;

    const form = document.getElementById('newProjectForm');
    const titleInput = document.getElementById('projectTitle');
    const descriptionInput = document.getElementById('projectDescription');
    const priceInput = document.getElementById('projectCost');
    const tvaCheckbox = document.getElementById('tvaIncluse');
    const quantityInput = document.getElementById('projectQuantity');
    const emailInput = document.getElementById('projectEmail');
    const imagesInput = document.getElementById('projectImages');
    const imagesPreviewList = document.getElementById('imagesPreviewList');
    const imagesPreviewEmpty = document.getElementById('imagesPreviewEmpty');
    const saveBtn = document.getElementById('saveBtn');
    const cancelEditBtn = document.getElementById('cancelEditBtn');
    const submitBtn = form ? form.querySelector('.add-project-btn') : null;

    const projectItems = document.querySelectorAll('.project-item');
    const existingTitles = new Map();

    let currentImages = [];
    let editingProjectId = null;
    let currentEditedItem = null;

    const generateId = () => {
        if (window.crypto && typeof window.crypto.randomUUID === 'function') {
            return window.crypto.randomUUID();
        }
        return `img_${Date.now()}_${Math.random().toString(16).slice(2)}`;
    };

    const normalizeTitle = (value = '') => {
        if (!value) {
            return '';
        }
        const transliterated = value
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '');
        const lower = transliterated.toLowerCase();
        const slug = lower.replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');
        if (slug) {
            return slug;
        }
        return lower.trim().replace(/\s+/g, '-');
    };

    const renderImagesPreview = () => {
        if (!imagesPreviewList) {
            return;
        }

        imagesPreviewList.innerHTML = '';

        if (!currentImages.length) {
            if (imagesPreviewEmpty) {
                imagesPreviewEmpty.classList.remove('hidden');
            }
            return;
        }

        if (imagesPreviewEmpty) {
            imagesPreviewEmpty.classList.add('hidden');
        }

        currentImages.forEach((image) => {
            const wrapper = document.createElement('div');
            wrapper.className = 'image-thumb';
            wrapper.dataset.id = image.id;

            const img = document.createElement('img');
            img.src = image.data;
            img.alt = 'Prévisualisation';

            const removeBtn = document.createElement('button');
            removeBtn.type = 'button';
            removeBtn.className = 'image-thumb-remove';
            removeBtn.dataset.id = image.id;
            removeBtn.setAttribute('aria-label', 'Supprimer cette image');
            removeBtn.textContent = '×';

            wrapper.appendChild(img);
            wrapper.appendChild(removeBtn);
            imagesPreviewList.appendChild(wrapper);
        });
    };

    const resetForm = () => {
        if (form) {
            form.reset();
        }
        currentImages = [];
        editingProjectId = null;
        if (submitBtn) {
            submitBtn.textContent = 'AJOUTER UN PROJET';
        }
        if (cancelEditBtn) {
            cancelEditBtn.classList.add('hidden');
        }
        if (currentEditedItem) {
            currentEditedItem.classList.remove('editing');
            currentEditedItem = null;
        }
        renderImagesPreview();
    };

    const convertImageToBase64 = (file) => new Promise((resolve, reject) => {
        const reader = new FileReader();
        reader.onload = () => resolve(reader.result);
        reader.onerror = reject;
        reader.readAsDataURL(file);
    });

    const fetchProjectDetails = async (projectId) => {
        const response = await fetch(`api.php/get-project?id=${encodeURIComponent(projectId)}`);
        const result = await response.json();
        if (!response.ok || result.status !== 'success') {
            throw new Error(result.message || 'Impossible de récupérer les détails du projet.');
        }
        return result.project;
    };

    const startEdit = async (projectSummary, projectElement) => {
        if (!projectSummary?.id) {
            return;
        }

        try {
            const project = await fetchProjectDetails(projectSummary.id);

            titleInput.value = project.titre || '';
            descriptionInput.value = project.description || '';
            priceInput.value = project.prix ?? '';
            tvaCheckbox.checked = Boolean(project.tva_incluse);
            quantityInput.value = project.quantite ?? 0;
            emailInput.value = project.email_alerte || '';

            currentImages = (project.images || []).map((data) => ({
                id: generateId(),
                data,
                source: 'existing'
            }));
            renderImagesPreview();

            editingProjectId = projectSummary.id;
            if (submitBtn) {
                submitBtn.textContent = 'METTRE À JOUR LE PROJET';
            }
            if (cancelEditBtn) {
                cancelEditBtn.classList.remove('hidden');
            }

            document.querySelectorAll('.project-item').forEach((item) => item.classList.remove('editing'));
            if (projectElement) {
                projectElement.classList.add('editing');
                currentEditedItem = projectElement;
            }

            if (form) {
                form.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        } catch (error) {
            alert(error.message || 'Une erreur est survenue lors de la récupération du projet.');
        }
    };

    if (imagesInput) {
        imagesInput.addEventListener('change', async (event) => {
            const files = Array.from(event.target.files || []);
            if (!files.length) {
                return;
            }

            if (currentImages.length + files.length > MAX_IMAGES) {
                alert(`Vous pouvez ajouter au maximum ${MAX_IMAGES} images par projet.`);
                imagesInput.value = '';
                return;
            }

            try {
                const converted = await Promise.all(files.map(convertImageToBase64));
                converted.forEach((data) => {
                    currentImages.push({
                        id: generateId(),
                        data,
                        source: 'new'
                    });
                });
                renderImagesPreview();
            } catch (error) {
                alert('Erreur lors du chargement des images.');
            } finally {
                imagesInput.value = '';
            }
        });
    }

    if (imagesPreviewList) {
        imagesPreviewList.addEventListener('click', (event) => {
            const target = event.target;
            if (target.classList.contains('image-thumb-remove')) {
                const id = target.dataset.id;
                currentImages = currentImages.filter((image) => image.id !== id);
                renderImagesPreview();
            }
        });
    }

    if (cancelEditBtn) {
        cancelEditBtn.addEventListener('click', () => {
            resetForm();
        });
    }

    if (form) {
        form.addEventListener('submit', async (event) => {
            event.preventDefault();

            const titre = titleInput.value.trim();
            const description = descriptionInput.value.trim();
            const prix = parseFloat(priceInput.value);
            const tvaIncluse = tvaCheckbox.checked;
            const quantite = parseInt(quantityInput.value, 10);
            const emailAlerte = emailInput.value.trim();

            // Validation des champs obligatoires
            if (!titre || !description || Number.isNaN(prix) || prix <= 0) {
                alert('Veuillez remplir tous les champs correctement.');
                return;
            }

            // Validation du stock (obligatoire et >= 0)
            if (Number.isNaN(quantite) || quantite < 0) {
                alert('Veuillez saisir une quantité valide (nombre entier >= 0).');
                quantityInput.focus();
                return;
            }

            // Validation de l'email (obligatoire et format valide)
            if (!emailAlerte) {
                alert('Veuillez saisir un email pour les alertes de stock.');
                emailInput.focus();
                return;
            }

            const emailPattern = /^[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}$/i;
            if (!emailPattern.test(emailAlerte)) {
                alert('Veuillez saisir un email valide.');
                emailInput.focus();
                return;
            }

            const normalized = normalizeTitle(titre);
            const existingId = existingTitles.get(normalized);
            if (existingId && existingId !== editingProjectId) {
                alert('Un projet avec ce titre existe déjà. Veuillez choisir un autre titre.');
                return;
            }

            const imagesPayload = currentImages.map((image) => image.data);

            const projectData = {
                titre,
                description,
                prix,
                tva_incluse: tvaIncluse,
                quantite: quantite,
                email_alerte: emailAlerte || null,
                images: imagesPayload,
                image: imagesPayload[0] || '',
                timestamp: new Date().toISOString()
            };

            if (editingProjectId) {
                projectData.id = editingProjectId;
            }

            try {
                const response = await fetch('api.php/add-project', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(projectData)
                });

                const result = await response.json();

                if (response.ok) {
                    window.location.reload();
                } else {
                    alert(result.message || 'Erreur lors de la sauvegarde du projet.');
                }
            } catch (error) {
                alert('Erreur de connexion. Veuillez vérifier votre connexion internet.');
            }
        });
    }

    projectItems.forEach((item) => {
        const dataAttr = item.dataset.project || '';
        let summary = null;
        if (dataAttr) {
            try {
                summary = JSON.parse(dataAttr);
                if (summary?.titre) {
                    const key = summary.slug || normalizeTitle(summary.titre);
                    existingTitles.set(key, summary.id);
                }
            } catch (error) {
            }
        }

        const editBtn = item.querySelector('.edit-project-btn');
        if (editBtn && summary) {
            editBtn.addEventListener('click', () => startEdit(summary, item));
        }
    });

    const deleteBtns = document.querySelectorAll('.delete-project-btn');
    deleteBtns.forEach((btn) => {
        btn.addEventListener('click', async () => {
            const projectId = btn.dataset.id;
            const projectItem = btn.closest('.project-item');
            const projectTitle = projectItem?.querySelector('.project-title')?.textContent || '';

            if (!confirm(`Êtes-vous sûr de vouloir supprimer le projet "${projectTitle}" ?`)) {
                return;
            }

            try {
                const response = await fetch('api.php/delete-project', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ id: projectId })
                });

                const result = await response.json();

                if (response.ok) {
                    projectItem?.remove();
                    if (projectItem?.dataset?.project) {
                        try {
                            const parsed = JSON.parse(projectItem.dataset.project);
                            const key = parsed.slug || normalizeTitle(parsed.titre || projectTitle);
                            if (key) {
                                existingTitles.delete(key);
                            }
                        } catch (error) {
                            const key = normalizeTitle(projectTitle);
                            if (key) {
                                existingTitles.delete(key);
                            }
                        }
                    }
                    if (editingProjectId === projectId) {
                        resetForm();
                    }
                } else {
                    alert(result.message || 'Erreur lors de la suppression.');
                }
            } catch (error) {
                alert('Erreur de connexion. Veuillez vérifier votre connexion internet.');
            }
        });
    });

    // Gestion du bouton sauvegarder (redirection vers l'accueil)
    if (saveBtn) {
        saveBtn.addEventListener('click', () => {
            const messageEl = document.createElement('div');
            messageEl.className = 'save-message show';
            messageEl.textContent = 'Modifications sauvegardées !';
            document.body.appendChild(messageEl);

            setTimeout(() => {
                window.location.href = 'index.php';
            }, 1500);
        });
    }
});


