<?php if (!defined('PIN_MODAL_INCLUDED')) {
    define('PIN_MODAL_INCLUDED', true);
?>
<div class="pin-lock-backdrop" id="pinLockBackdrop" aria-hidden="true">
    <div class="pin-lock-modal" role="dialog" aria-labelledby="pinLockTitle" aria-describedby="pinLockDescription">
        <h2 class="pin-lock-title" id="pinLockTitle">Code PIN requis</h2>
        <p class="pin-lock-description" id="pinLockDescription">
            Saisissez le code PIN à 6 chiffres pour accéder aux fonctions d'administration.
        </p>
        <form id="pinLockForm" class="pin-lock-form" autocomplete="off">
            <label for="pinLockInput" class="pin-lock-label">Code PIN</label>
            <input type="password"
                   id="pinLockInput"
                   name="pin"
                   inputmode="numeric"
                   pattern="\d{6}"
                   maxlength="6"
                   minlength="6"
                   class="pin-lock-input"
                   placeholder="••••••"
                   required>
            <p class="pin-lock-error" id="pinLockError" aria-live="polite"></p>
            <div class="pin-lock-actions">
                <button type="submit" class="pin-lock-submit" id="pinLockSubmitBtn">Valider</button>
                <button type="button" class="pin-lock-cancel" id="pinLockCancelBtn">Annuler</button>
            </div>
        </form>
    </div>
</div>
<?php } ?>
