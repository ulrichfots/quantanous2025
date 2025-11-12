// Gestion de la page Paiement
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('paiementForm');
    const montantInput = document.getElementById('montantInput');
    const montantDisplay = document.getElementById('montantDisplay');
    const articleIdInput = document.getElementById('articleIdInput');
    const payerBtn = document.getElementById('payerBtn');
    const deliveryInfo = document.querySelector('.paiement-delivery-info');
    const stripeInfo = document.querySelector('.paiement-stripe-info');

    // Récupérer les paramètres de l'URL
    const urlParams = new URLSearchParams(window.location.search);
    const montant = urlParams.get('montant') || '10';
    const articleId = urlParams.get('article_id') || '';
    const frequency = urlParams.get('frequency') || '';
    const fromPage = urlParams.get('from') || '';
    const frequencyInterval = urlParams.get('frequency_interval') || '';
    const frequencyIntervalCountParam = urlParams.get('frequency_interval_count');
    const frequencyIntervalCount = frequencyIntervalCountParam ? parseInt(frequencyIntervalCountParam, 10) : '';
    const frequencyLabel = urlParams.get('frequency_label') || '';

    // Mettre à jour les champs cachés et l'affichage
    if (montantInput) {
        montantInput.value = montant;
    }
    if (articleIdInput) {
        articleIdInput.value = articleId;
    }
    if (montantDisplay) {
        montantDisplay.textContent = `${parseFloat(montant).toFixed(2)} €`;
    }
    
    // Adapter les messages selon l'origine
    if (fromPage === 'don') {
        if (deliveryInfo) {
            deliveryInfo.style.display = 'none';
        }
        if (stripeInfo) {
            if (frequency === 'regulier') {
                const frequencyText = frequencyLabel
                    ? frequencyLabel.charAt(0).toUpperCase() + frequencyLabel.slice(1)
                    : 'mensuel';
                const cadenceMap = {
                    mensuel: 'mois',
                    trimestriel: 'trimestre',
                    semestriel: 'semestre',
                    annuel: 'année'
                };
                const cadenceKey = frequencyLabel.toLowerCase();
                const cadence = cadenceMap[cadenceKey] || 'mois';
                stripeInfo.textContent = `Vous serez redirigé vers Stripe pour mettre en place un don ${frequencyText.toLowerCase()}. Le montant sera prélevé automatiquement chaque ${cadence}.`;
            } else {
                stripeInfo.textContent = 'Vous serez redirigé vers Stripe pour finaliser votre don ponctuel en toute sécurité.';
            }
        }
    } else {
        if (stripeInfo) {
            stripeInfo.textContent = 'Le paiement est sécurisé via Stripe. Vous serez redirigé vers une page de paiement sécurisée.';
        }
    }
    
    // Ajouter un champ caché pour la fréquence si présente
    if (frequency) {
        const frequencyInput = document.createElement('input');
        frequencyInput.type = 'hidden';
        frequencyInput.name = 'frequency';
        frequencyInput.value = frequency;
        form.appendChild(frequencyInput);
    }
    if (frequencyInterval) {
        const intervalInput = document.createElement('input');
        intervalInput.type = 'hidden';
        intervalInput.name = 'frequency_interval';
        intervalInput.value = frequencyInterval;
        form.appendChild(intervalInput);
    }
    if (frequencyIntervalCount) {
        const intervalCountInput = document.createElement('input');
        intervalCountInput.type = 'hidden';
        intervalCountInput.name = 'frequency_interval_count';
        intervalCountInput.value = frequencyIntervalCount;
        form.appendChild(intervalCountInput);
    }
    if (frequencyLabel) {
        const frequencyLabelInput = document.createElement('input');
        frequencyLabelInput.type = 'hidden';
        frequencyLabelInput.name = 'frequency_label';
        frequencyLabelInput.value = frequencyLabel;
        form.appendChild(frequencyLabelInput);
    }

    // Validation de l'email
    const emailInput = document.querySelector('input[name="email"]');
    const emailConfirmInput = document.querySelector('input[name="email_confirm"]');
    
    function validateEmails() {
        if (emailInput && emailConfirmInput) {
            if (emailInput.value && emailConfirmInput.value && 
                emailInput.value !== emailConfirmInput.value) {
                emailConfirmInput.setCustomValidity('Les adresses email ne correspondent pas');
            } else {
                emailConfirmInput.setCustomValidity('');
            }
        }
    }

    if (emailInput) {
        emailInput.addEventListener('input', validateEmails);
    }
    if (emailConfirmInput) {
        emailConfirmInput.addEventListener('input', validateEmails);
    }

    // Gestion de la soumission du formulaire
    if (form) {
        form.addEventListener('submit', async (e) => {
            e.preventDefault();

            if (!form.checkValidity()) {
                form.reportValidity();
                return;
            }

            if (payerBtn) {
                payerBtn.disabled = true;
                payerBtn.dataset.originalLabel = payerBtn.innerHTML;
                payerBtn.textContent = 'Redirection en cours...';
            }

            const formData = new FormData(form);
            const frequencyIntervalValue = formData.get('frequency_interval') || '';
            const frequencyIntervalCountValue = formData.get('frequency_interval_count');
            const frequencyIntervalCountParsed = frequencyIntervalCountValue ? parseInt(frequencyIntervalCountValue, 10) : '';
            const paiementData = {
                nom: formData.get('nom'),
                prenom: formData.get('prenom'),
                adresse: formData.get('adresse'),
                code_postal: formData.get('code_postal'),
                ville: formData.get('ville'),
                email: formData.get('email'),
                montant: parseFloat(formData.get('montant')),
                article_id: formData.get('article_id'),
                frequency: formData.get('frequency') || '',
                frequency_interval: frequencyIntervalValue,
                frequency_interval_count: frequencyIntervalCountParsed,
                frequency_label: formData.get('frequency_label') || '',
                from: fromPage
            };

            try {
                const response = await fetch('api.php/create-checkout-session', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(paiementData)
                });

                const result = await response.json();

                if (response.ok && result.checkout_url) {
                    // Vérifier que l'URL est valide et qu'il s'agit bien d'une URL Stripe Checkout
                    const checkoutUrl = result.checkout_url;
                    if (typeof checkoutUrl === 'string' && checkoutUrl.startsWith('https://checkout.stripe.com/')) {
                        // Redirection directe vers Stripe Checkout (pas d'iframe)
                        // Utiliser window.location.replace pour éviter que l'utilisateur puisse revenir en arrière
                        window.location.replace(checkoutUrl);
                    } else {
                        throw new Error('URL de checkout invalide');
                    }
                } else {
                    const errorMessage = result.message || 'Une erreur est survenue lors de la préparation du paiement.';
                    alert(errorMessage);
                    if (payerBtn) {
                        payerBtn.disabled = false;
                        payerBtn.innerHTML = payerBtn.dataset.originalLabel || `PAYER <span id="montantDisplay">${montantDisplay.textContent}</span>`;
                    }
                }
            } catch (error) {
                alert('Erreur de connexion. Veuillez vérifier votre connexion internet.');
                if (payerBtn) {
                    payerBtn.disabled = false;
                    payerBtn.innerHTML = payerBtn.dataset.originalLabel || `PAYER <span id="montantDisplay">${montantDisplay.textContent}</span>`;
                }
            }
        });
    }
});

