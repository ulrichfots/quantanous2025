// Gestion de la page Modifier le code PIN
document.addEventListener('DOMContentLoaded', () => {
    const oldPinInput = document.getElementById('oldPin');
    const newPinInput = document.getElementById('newPin');
    const confirmPinInput = document.getElementById('confirmPin');
    const oldPinDisplay = document.getElementById('oldPinDisplay');
    const newPinDisplay = document.getElementById('newPinDisplay');
    const confirmPinDisplay = document.getElementById('confirmPinDisplay');
    const keyButtons = document.querySelectorAll('.key-btn:not(.key-delete)');
    const deleteBtn = document.getElementById('deleteBtn');
    const saveBtn = document.getElementById('saveBtn');
    const pinError = document.getElementById('pinError');

    let currentField = 'oldPin'; // oldPin, newPin, confirmPin
    const maxLength = 6;

    // Fonction pour mettre à jour l'affichage des points
    function updateDisplay(field, value) {
        const display = field === 'oldPin' ? oldPinDisplay : 
                       field === 'newPin' ? newPinDisplay : confirmPinDisplay;
        const dots = display.querySelectorAll('.pin-dot');
        
        dots.forEach((dot, index) => {
            if (index < value.length) {
                dot.classList.add('filled');
            } else {
                dot.classList.remove('filled');
            }
        });
    }

    // Fonction pour obtenir la valeur actuelle
    function getCurrentValue() {
        if (currentField === 'oldPin') return oldPinInput.value;
        if (currentField === 'newPin') return newPinInput.value;
        return confirmPinInput.value;
    }

    // Fonction pour définir la valeur actuelle
    function setCurrentValue(value) {
        if (currentField === 'oldPin') {
            oldPinInput.value = value;
            updateDisplay('oldPin', value);
        } else if (currentField === 'newPin') {
            newPinInput.value = value;
            updateDisplay('newPin', value);
        } else {
            confirmPinInput.value = value;
            updateDisplay('confirmPin', value);
        }
    }

    // Fonction pour passer au champ suivant
    function moveToNextField() {
        if (currentField === 'oldPin' && oldPinInput.value.length === maxLength) {
            currentField = 'newPin';
            // Focus visuel sur le nouveau champ
            newPinDisplay.scrollIntoView({ behavior: 'smooth', block: 'center' });
        } else if (currentField === 'newPin' && newPinInput.value.length === maxLength) {
            currentField = 'confirmPin';
            // Focus visuel sur le champ de confirmation
            confirmPinDisplay.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    }

    // Gestion des boutons numériques
    keyButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            const key = btn.dataset.key;
            const currentValue = getCurrentValue();
            
            if (currentValue.length < maxLength) {
                setCurrentValue(currentValue + key);
                moveToNextField();
                hideError();
            }
        });
    });

    // Gestion du bouton supprimer
    if (deleteBtn) {
        deleteBtn.addEventListener('click', () => {
            const currentValue = getCurrentValue();
            if (currentValue.length > 0) {
                setCurrentValue(currentValue.slice(0, -1));
                hideError();
            } else {
                // Revenir au champ précédent si le champ actuel est vide
                if (currentField === 'confirmPin') {
                    currentField = 'newPin';
                    newPinDisplay.scrollIntoView({ behavior: 'smooth', block: 'center' });
                } else if (currentField === 'newPin') {
                    currentField = 'oldPin';
                    oldPinDisplay.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            }
        });
    }

    // Fonction pour afficher une erreur
    function showError(message) {
        pinError.textContent = message;
        pinError.classList.add('show');
    }

    // Fonction pour masquer l'erreur
    function hideError() {
        pinError.classList.remove('show');
    }

    // Validation avant sauvegarde
    function validatePins() {
        const oldPin = oldPinInput.value;
        const newPin = newPinInput.value;
        const confirmPin = confirmPinInput.value;

        if (oldPin.length !== maxLength) {
            showError('Veuillez saisir l\'ancien code PIN');
            oldPinDisplay.scrollIntoView({ behavior: 'smooth', block: 'center' });
            currentField = 'oldPin';
            return false;
        }

        if (newPin.length !== maxLength) {
            showError('Veuillez saisir le nouveau code PIN');
            newPinDisplay.scrollIntoView({ behavior: 'smooth', block: 'center' });
            currentField = 'newPin';
            return false;
        }

        if (confirmPin.length !== maxLength) {
            showError('Veuillez confirmer le nouveau code PIN');
            confirmPinDisplay.scrollIntoView({ behavior: 'smooth', block: 'center' });
            currentField = 'confirmPin';
            return false;
        }

        if (newPin !== confirmPin) {
            showError('Les nouveaux codes PIN ne correspondent pas');
            confirmPinInput.value = '';
            updateDisplay('confirmPin', '');
            currentField = 'confirmPin';
            return false;
        }

        if (oldPin === newPin) {
            showError('Le nouveau code PIN doit être différent de l\'ancien');
            newPinInput.value = '';
            confirmPinInput.value = '';
            updateDisplay('newPin', '');
            updateDisplay('confirmPin', '');
            currentField = 'newPin';
            return false;
        }

        return true;
    }

    // Gestion de la sauvegarde
    if (saveBtn) {
        saveBtn.addEventListener('click', async () => {
            hideError();

            if (!validatePins()) {
                return;
            }

            try {
                saveBtn.disabled = true;
                saveBtn.style.opacity = '0.6';

                const response = await fetch('api.php/save-pin', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        oldPin: oldPinInput.value,
                        newPin: newPinInput.value
                    })
                });

                const result = await response.json();

                if (response.ok) {
                    // Afficher un message de confirmation
                    pinError.textContent = 'Code PIN modifié avec succès !';
                    pinError.classList.add('show', 'success');
                    
                    // Rediriger vers la page d'accueil après 1.5 secondes
                    setTimeout(() => {
                        window.location.href = 'index.php';
                    }, 1500);
                } else {
                    pinError.classList.remove('success');
                    showError(result.message || 'Erreur lors de la modification du code PIN');
                }
            } catch (error) {
                pinError.classList.remove('success');
                showError('Erreur de connexion');
            } finally {
                saveBtn.disabled = false;
                saveBtn.style.opacity = '1';
            }
        });
    }

    // Permettre de cliquer sur un champ pour le sélectionner
    [oldPinDisplay, newPinDisplay, confirmPinDisplay].forEach((display, index) => {
        display.addEventListener('click', () => {
            if (index === 0) currentField = 'oldPin';
            else if (index === 1) currentField = 'newPin';
            else currentField = 'confirmPin';
            hideError();
        });
    });

    // Permettre la saisie au clavier (pour les tests)
    document.addEventListener('keydown', (e) => {
        if (e.key >= '0' && e.key <= '9') {
            const currentValue = getCurrentValue();
            if (currentValue.length < maxLength) {
                setCurrentValue(currentValue + e.key);
                moveToNextField();
                hideError();
            }
        } else if (e.key === 'Backspace') {
            const currentValue = getCurrentValue();
            if (currentValue.length > 0) {
                setCurrentValue(currentValue.slice(0, -1));
                hideError();
            }
        }
    });
});

