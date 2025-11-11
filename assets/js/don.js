// Gestion de la page Don
document.addEventListener('DOMContentLoaded', () => {
    const regulierBtn = document.getElementById('regulierBtn');
    const ponctuelBtn = document.getElementById('ponctuelBtn');
    const periodSection = document.getElementById('periodSection');
    const periodButtons = document.querySelectorAll('.period-btn');
    const amountBtns = document.querySelectorAll('.amount-btn');
    const customAmountInput = document.getElementById('customAmount');
    const donateBtn = document.getElementById('donateBtn');
    
    let selectedFrequency = null;
    let selectedAmount = null;
    let selectedPeriod = null;

    // Gestion des boutons de fréquence
    regulierBtn.addEventListener('click', () => {
        regulierBtn.classList.add('active');
        ponctuelBtn.classList.remove('active');
        selectedFrequency = 'regulier';
        if (periodSection) {
            periodSection.classList.remove('hidden');
            periodSection.setAttribute('aria-hidden', 'false');
        }
        updateDonateButton();
    });

    ponctuelBtn.addEventListener('click', () => {
        ponctuelBtn.classList.add('active');
        regulierBtn.classList.remove('active');
        selectedFrequency = 'ponctuel';
        if (periodSection) {
            periodSection.classList.add('hidden');
            periodSection.setAttribute('aria-hidden', 'true');
        }
        if (periodButtons.length > 0) {
            periodButtons.forEach(btn => btn.classList.remove('active'));
        }
        selectedPeriod = null;
        updateDonateButton();
    });

    // Gestion des options de périodicité
    periodButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            periodButtons.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            const count = parseInt(btn.dataset.count, 10);
            selectedPeriod = {
                interval: btn.dataset.interval,
                count: Number.isNaN(count) ? null : count,
                label: btn.dataset.label
            };
            updateDonateButton();
        });
    });

    // Gestion des montants prédéfinis
    amountBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            // Retirer l'état actif de tous les boutons
            amountBtns.forEach(b => b.classList.remove('active'));
            // Ajouter l'état actif au bouton cliqué
            btn.classList.add('active');
            selectedAmount = btn.dataset.amount;
            // Remplir le champ personnalisé avec le montant sélectionné
            customAmountInput.value = selectedAmount;
            updateDonateButton();
        });
    });

    // Gestion du champ de montant personnalisé
    customAmountInput.addEventListener('input', (e) => {
        const value = parseFloat(e.target.value);
        if (value && value > 0) {
            // Retirer l'état actif des boutons de montant prédéfinis
            amountBtns.forEach(b => b.classList.remove('active'));
            selectedAmount = value;
        } else {
            selectedAmount = null;
        }
        updateDonateButton();
    });

    // Gestion du bouton Donner
    donateBtn.addEventListener('click', () => {
        if (!selectedFrequency) {
            alert('Veuillez choisir une fréquence (Régulier ou Ponctuel)');
            return;
        }

        if (selectedFrequency === 'regulier' && !selectedPeriod) {
            alert('Veuillez choisir la périodicité de votre don régulier');
            return;
        }

        if (!selectedAmount || selectedAmount <= 0) {
            alert('Veuillez choisir ou saisir un montant');
            return;
        }

        // Rediriger vers la page de paiement avec les paramètres
        const params = new URLSearchParams({
            montant: parseFloat(selectedAmount).toFixed(2),
            frequency: selectedFrequency,
            from: 'don'
        });

        if (selectedFrequency === 'regulier' && selectedPeriod) {
            params.set('frequency_interval', selectedPeriod.interval);
            if (selectedPeriod.count) {
                params.set('frequency_interval_count', String(selectedPeriod.count));
            }
            params.set('frequency_label', selectedPeriod.label);
        }
        
        window.location.href = `paiement.php?${params.toString()}`;
    });

    // Fonction pour mettre à jour l'état du bouton Donner
    function updateDonateButton() {
        const hasValidAmount = selectedAmount && selectedAmount > 0;
        const hasValidPeriod = selectedFrequency !== 'regulier' || selectedPeriod !== null;

        if (selectedFrequency && hasValidAmount && hasValidPeriod) {
            donateBtn.disabled = false;
            donateBtn.classList.add('enabled');
        } else {
            donateBtn.disabled = true;
            donateBtn.classList.remove('enabled');
        }
    }

    // Fonction pour réinitialiser le formulaire
    function resetForm() {
        regulierBtn.classList.remove('active');
        ponctuelBtn.classList.remove('active');
        amountBtns.forEach(b => b.classList.remove('active'));
        periodButtons.forEach(b => b.classList.remove('active'));
        if (periodSection) {
            periodSection.classList.add('hidden');
            periodSection.setAttribute('aria-hidden', 'true');
        }
        customAmountInput.value = '';
        selectedFrequency = null;
        selectedAmount = null;
        selectedPeriod = null;
        updateDonateButton();
    }

    // Initialiser l'état du bouton
    updateDonateButton();
});

