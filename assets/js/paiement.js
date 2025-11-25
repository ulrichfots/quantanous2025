// Gestion de la page Paiement avec Stripe Elements (sans redirection)
document.addEventListener('DOMContentLoaded', async () => {
    const form = document.getElementById('paiementForm');
    const montantInput = document.getElementById('montantInput');
    const montantDisplay = document.getElementById('montantDisplay');
    const articleIdInput = document.getElementById('articleIdInput');
    const payerBtn = document.getElementById('payerBtn');
    const deliveryInfo = document.querySelector('.paiement-delivery-info');
    const stripeInfo = document.querySelector('.paiement-stripe-info');
    const paymentElement = document.getElementById('payment-element');
    const paymentMessage = document.getElementById('payment-message');

    // Récupérer les paramètres de l'URL
    const urlParams = new URLSearchParams(window.location.search);
    const montant = urlParams.get('montant') || '10';
    const articleId = urlParams.get('article_id') || '';
    const fromPage = urlParams.get('from') || '';
    const frequency = urlParams.get('frequency') || '';
    const frequencyInterval = urlParams.get('frequency_interval') || '';
    const frequencyIntervalCountParam = urlParams.get('frequency_interval_count');
    const frequencyIntervalCount = frequencyIntervalCountParam ? parseInt(frequencyIntervalCountParam, 10) : '';
    const frequencyLabel = urlParams.get('frequency_label') || '';
    const isRecurring = (fromPage === 'don' && frequency === 'regulier');

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
    
    // Adapter les messages selon l'origine et le montant
    if (fromPage === 'don') {
        if (deliveryInfo) {
            deliveryInfo.style.display = 'none';
        }
        if (stripeInfo) {
            if (isRecurring) {
                const frequencyText = frequencyLabel
                    ? frequencyLabel.charAt(0).toUpperCase() + frequencyLabel.slice(1)
                    : 'Mensuel';
                stripeInfo.textContent = `Vous allez mettre en place un don ${frequencyText.toLowerCase()} récurrent. Le montant sera prélevé automatiquement chaque période.`;
            } else {
                stripeInfo.textContent = 'Le paiement est sécurisé via Stripe. Remplissez vos informations ci-dessous.';
            }
        }
    } else if (fromPage === 'achats') {
        const montantValue = parseFloat(montant);
        if (deliveryInfo && montantValue > 60) {
            deliveryInfo.textContent = 'Livraison gratuite pour les commandes supérieures à 60 €';
            deliveryInfo.style.color = '#2E7D32';
            deliveryInfo.style.fontWeight = '600';
        } else if (deliveryInfo) {
            deliveryInfo.textContent = 'S\'ajouteront des frais de livraison à hauteur de 5 €';
        }
        if (stripeInfo) {
            stripeInfo.textContent = 'Le paiement est sécurisé via Stripe. Remplissez vos informations ci-dessous.';
        }
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

    // Initialiser Stripe
    let stripe = null;
    let elements = null;
    let paymentElementStripe = null;
    let clientSecret = null;

    // Récupérer la clé publique Stripe
    try {
        const configResponse = await fetch('api.php/get-stripe-config');
        
        if (!configResponse.ok) {
            throw new Error(`Erreur HTTP: ${configResponse.status}`);
        }
        
        const configData = await configResponse.json();
        
        if (configData.status === 'error') {
            throw new Error(configData.message || 'Erreur de configuration Stripe');
        }
        
        if (!configData.publishable_key) {
            throw new Error('Clé publique Stripe non disponible dans la réponse');
        }
        
        if (typeof Stripe === 'undefined') {
            throw new Error('Stripe.js n\'est pas chargé. Vérifiez votre connexion internet.');
        }
        
        stripe = Stripe(configData.publishable_key);
    } catch (error) {
        const errorMessage = error.message || 'Erreur: Impossible de charger Stripe. Veuillez rafraîchir la page.';
        if (paymentMessage) {
            paymentMessage.textContent = errorMessage;
            paymentMessage.style.display = 'block';
            paymentMessage.style.color = '#D32F2F';
            paymentMessage.style.backgroundColor = '#FFEBEE';
            paymentMessage.style.padding = '12px';
            paymentMessage.style.borderRadius = '4px';
            paymentMessage.style.marginTop = '16px';
        }
        if (payerBtn) {
            payerBtn.disabled = true;
        }
        return;
    }

    // Fonction pour créer le Payment Intent ou Setup Intent
    async function createPaymentIntent(formData) {
        const paiementData = {
            nom: formData.get('nom'),
            prenom: formData.get('prenom'),
            adresse: formData.get('adresse'),
            code_postal: formData.get('code_postal'),
            ville: formData.get('ville'),
            email: formData.get('email'),
            montant: parseFloat(formData.get('montant')),
            article_id: formData.get('article_id'),
            from: fromPage,
            frequency: frequency,
            frequency_interval: frequencyInterval,
            frequency_interval_count: frequencyIntervalCount,
            frequency_label: frequencyLabel
        };

        const response = await fetch('api.php/create-payment-intent', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(paiementData)
        });

        const result = await response.json();
        
        if (!response.ok || !result.client_secret) {
            throw new Error(result.message || 'Erreur lors de la création du paiement');
        }

        return result;
    }

    // Fonction pour afficher les erreurs
    function showMessage(message, isError = false) {
        paymentMessage.textContent = message;
        paymentMessage.style.display = 'block';
        paymentMessage.style.color = isError ? '#D32F2F' : '#2E7D32';
        paymentMessage.style.backgroundColor = isError ? '#FFEBEE' : '#E8F5E9';
        paymentMessage.style.padding = '12px';
        paymentMessage.style.borderRadius = '4px';
        paymentMessage.style.marginTop = '16px';
    }

    // Gestion de la soumission du formulaire
    if (form) {
        form.addEventListener('submit', async (e) => {
            e.preventDefault();

            if (!form.checkValidity()) {
                form.reportValidity();
                return;
            }

            if (!stripe) {
                showMessage('Stripe n\'est pas initialisé. Veuillez rafraîchir la page.', true);
                return;
            }
            
            // Si le Payment Element n'est pas encore monté, on va le créer maintenant
            if (!paymentElementStripe) {
                // On va créer le Payment Intent d'abord, puis monter le Payment Element
                // Cette partie sera gérée dans le try/catch ci-dessous
            }

            if (payerBtn) {
                payerBtn.disabled = true;
                payerBtn.textContent = 'Traitement en cours...';
            }

            paymentMessage.style.display = 'none';

            const formData = new FormData(form);

            try {
                // Créer le Payment Intent ou Setup Intent
                const intentResult = await createPaymentIntent(formData);
                clientSecret = intentResult.client_secret;
                const isSubscription = intentResult.is_subscription || false;
                
                // Si le Payment Element n'est pas encore monté, l'initialiser
                if (!paymentElementStripe) {
                    elements = stripe.elements({ clientSecret });
                    paymentElementStripe = elements.create('payment');
                    paymentElementStripe.mount('#payment-element');
                    
                    // Attendre que l'utilisateur remplisse le formulaire de carte
                    showMessage('Veuillez remplir vos informations de carte ci-dessus, puis cliquez à nouveau sur "PAYER".', false);
                    if (payerBtn) {
                        payerBtn.disabled = false;
                        payerBtn.innerHTML = `PAYER <span id="montantDisplay">${montantDisplay.textContent}</span>`;
                    }
                    return;
                } else {
                    // Mettre à jour le client secret si nécessaire
                    elements.update({ clientSecret });
                }

                if (isSubscription) {
                    // Pour les abonnements : confirmer le Setup Intent puis créer la Subscription
                    const { error: setupError, setupIntent } = await stripe.confirmSetup({
                        elements,
                        confirmParams: {
                            return_url: window.location.origin + '/index.php?status=success',
                        },
                        redirect: 'if_required'
                    });

                    if (setupError) {
                        showMessage(setupError.message || 'Une erreur est survenue lors de la configuration du paiement.', true);
                        if (payerBtn) {
                            payerBtn.disabled = false;
                            payerBtn.innerHTML = `PAYER <span id="montantDisplay">${montantDisplay.textContent}</span>`;
                        }
                        return;
                    }

                    if (setupIntent && setupIntent.status === 'succeeded') {
                        // Créer la subscription
                        const subscriptionData = {
                            payment_method_id: setupIntent.payment_method,
                            amount: intentResult.amount,
                            interval: intentResult.interval,
                            interval_count: intentResult.interval_count,
                            frequency_label: intentResult.frequency_label,
                            nom: formData.get('nom'),
                            prenom: formData.get('prenom'),
                            adresse: formData.get('adresse'),
                            code_postal: formData.get('code_postal'),
                            ville: formData.get('ville'),
                            email: formData.get('email')
                        };

                        const subscriptionResponse = await fetch('api.php/create-subscription', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify(subscriptionData)
                        });

                        const subscriptionResult = await subscriptionResponse.json();

                        if (!subscriptionResponse.ok) {
                            showMessage(subscriptionResult.message || 'Erreur lors de la création de l\'abonnement.', true);
                            if (payerBtn) {
                                payerBtn.disabled = false;
                                payerBtn.innerHTML = `PAYER <span id="montantDisplay">${montantDisplay.textContent}</span>`;
                            }
                        } else {
                            // Abonnement créé avec succès
                            showMessage('Abonnement créé avec succès ! Redirection...', false);
                            setTimeout(() => {
                                window.location.href = 'index.php?status=success';
                            }, 1500);
                        }
                    } else {
                        showMessage('La configuration du paiement nécessite une action supplémentaire.', true);
                        if (payerBtn) {
                            payerBtn.disabled = false;
                            payerBtn.innerHTML = `PAYER <span id="montantDisplay">${montantDisplay.textContent}</span>`;
                        }
                    }
                } else {
                    // Pour les paiements uniques : confirmer le Payment Intent
                    const { error, paymentIntent } = await stripe.confirmPayment({
                        elements,
                        confirmParams: {
                            return_url: window.location.origin + '/index.php?status=success',
                        },
                        redirect: 'if_required'
                    });

                    if (error) {
                        showMessage(error.message || 'Une erreur est survenue lors du paiement.', true);
                        if (payerBtn) {
                            payerBtn.disabled = false;
                            payerBtn.innerHTML = `PAYER <span id="montantDisplay">${montantDisplay.textContent}</span>`;
                        }
                    } else if (paymentIntent && paymentIntent.status === 'succeeded') {
                        // Paiement réussi
                        showMessage('Paiement réussi ! Redirection...', false);
                        setTimeout(() => {
                            window.location.href = 'index.php?status=success';
                        }, 1500);
                    } else {
                        showMessage('Le paiement nécessite une action supplémentaire.', true);
                        if (payerBtn) {
                            payerBtn.disabled = false;
                            payerBtn.innerHTML = `PAYER <span id="montantDisplay">${montantDisplay.textContent}</span>`;
                        }
                    }
                }
            } catch (error) {
                showMessage(error.message || 'Erreur de connexion. Veuillez vérifier votre connexion internet.', true);
                if (payerBtn) {
                    payerBtn.disabled = false;
                    payerBtn.innerHTML = `PAYER <span id="montantDisplay">${montantDisplay.textContent}</span>`;
                }
            }
        });
    }
});
