// Enregistrement du Service Worker
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('./service-worker.php')
            .then((registration) => {
                registration.addEventListener('updatefound', () => {
                    const newWorker = registration.installing;
                    newWorker.addEventListener('statechange', () => {
                    });
                });
            })
            .catch(() => {});
    });
}

// Gestion du menu kebab (menu contextuel)
document.addEventListener('DOMContentLoaded', () => {
    const menuBtn = document.getElementById('menuBtn');
    const contextMenu = document.getElementById('contextMenu');
    const menuOverlay = document.getElementById('menuOverlay');
    const menuItems = document.querySelectorAll('.context-menu-item');

    const openMenu = () => {
        if (contextMenu) {
            contextMenu.classList.add('active');
        }
        if (menuOverlay) {
            menuOverlay.classList.add('active');
        }
    };

    const closeMenu = () => {
        if (contextMenu) {
            contextMenu.classList.remove('active');
        }
        if (menuOverlay) {
            menuOverlay.classList.remove('active');
        }
    };

    if (menuBtn && contextMenu) {
        menuBtn.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            const isActive = contextMenu.classList.contains('active');
            if (isActive) {
                closeMenu();
            } else {
                openMenu();
            }
        });
    }

    if (menuOverlay) {
        menuOverlay.addEventListener('click', (e) => {
            e.stopPropagation();
            closeMenu();
        });
    }

    menuItems.forEach(item => {
        item.addEventListener('click', (e) => {
            e.stopPropagation();
            const page = item.dataset.page;
            switch(page) {
                case 'presentation':
                    window.location.href = 'admin-presentation.php';
                    break;
                case 'achats':
                    window.location.href = 'admin-achats.php';
                    break;
                case 'explications':
                    window.location.href = 'admin-explications.php';
                    break;
                case 'pin':
                    window.location.href = 'admin-pin.php';
                    break;
                case 'logout':
                    window.location.href = 'logout.php?redirect=' + encodeURIComponent(window.location.href);
                    break;
            }
            closeMenu();
        });
    });

    document.addEventListener('click', (e) => {
        if (contextMenu && contextMenu.classList.contains('active')) {
            if (!contextMenu.contains(e.target) && !menuBtn.contains(e.target) && (!menuOverlay || !menuOverlay.contains(e.target))) {
                closeMenu();
            }
        }
    });

});

window.addEventListener('online', () => {
    const resultBox = document.getElementById('result');
    if (resultBox) {
        resultBox.textContent = 'Connexion rétablie !';
        resultBox.className = 'result-box show success';
        setTimeout(() => {
            resultBox.className = 'result-box';
        }, 3000);
    }
});

window.addEventListener('offline', () => {
    const resultBox = document.getElementById('result');
    if (resultBox) {
        resultBox.textContent = 'Mode hors ligne activé. Les données mises en cache seront utilisées.';
        resultBox.className = 'result-box show';
    }
});

