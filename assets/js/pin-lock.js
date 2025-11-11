const PIN_ENDPOINT = 'api.php';

document.addEventListener('DOMContentLoaded', () => {
    const pinBackdrop = document.getElementById('pinLockBackdrop');
    const pinForm = document.getElementById('pinLockForm');
    const pinInput = document.getElementById('pinLockInput');
    const pinError = document.getElementById('pinLockError');
    const cancelBtn = document.getElementById('pinLockCancelBtn');
    const submitBtn = document.getElementById('pinLockSubmitBtn');
    const redirectTarget = document.body.dataset.pinRedirect || window.location.href;

    const openPinModal = () => {
        if (pinBackdrop) {
            pinBackdrop.classList.add('active');
        }
        if (pinError) {
            pinError.textContent = '';
        }
        if (pinInput) {
            pinInput.value = '';
            pinInput.focus();
        }
    };

    const closePinModal = () => {
        if (pinBackdrop) {
            pinBackdrop.classList.remove('active');
        }
    };

    const setLoading = (state) => {
        if (!submitBtn) return;
        submitBtn.disabled = state;
        submitBtn.textContent = state ? 'Vérification...' : 'Valider';
    };

    const showError = (message) => {
        if (pinError) {
            pinError.textContent = message;
        }
    };

    const verifyPin = async (pin) => {
        try {
            setLoading(true);
            const response = await fetch(`${PIN_ENDPOINT}/verify-pin`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                credentials: 'same-origin',
                body: JSON.stringify({ pin })
            });

            const data = await response.json().catch(() => ({}));

            if (response.ok) {
                closePinModal();
                window.location.href = redirectTarget;
            } else {
                showError(data.message || 'PIN incorrect. Veuillez réessayer.');
                if (pinInput) {
                    pinInput.focus();
                }
            }
        } catch (error) {
            showError('Erreur de connexion. Veuillez vérifier votre réseau.');
        } finally {
            setLoading(false);
        }
    };

    if (pinForm) {
        pinForm.addEventListener('submit', (event) => {
            event.preventDefault();
            if (!pinInput) return;
            const value = pinInput.value.trim();
            if (!/^\d{6}$/.test(value)) {
                showError('Le code PIN doit contenir exactement 6 chiffres.');
                pinInput.focus();
                return;
            }
            showError('');
            verifyPin(value);
        });
    }

    if (cancelBtn) {
        cancelBtn.addEventListener('click', () => {
            closePinModal();
            window.location.href = 'index.php';
        });
    }

    openPinModal();
});
