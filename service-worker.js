const CACHE_NAME = 'mon-app-pwa-v3';
const urlsToCache = [
  './',
  './index.php',
  './api.php',
  './assets/css/style.css',
  './assets/js/app.js',
  './assets/js/install.js',
  './assets/icons/icon-192x192.png',
  './manifest.json'
];

// Installation du service worker
self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then((cache) => cache.addAll(urlsToCache))
      .catch(() => {})
  );
  self.skipWaiting();
});

// Activation du service worker
self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((cacheNames) => {
      return Promise.all(
        cacheNames.map((cacheName) => {
          if (cacheName !== CACHE_NAME) {
            return caches.delete(cacheName);
          }
        })
      );
    })
  );
  return self.clients.claim();
});

// Stratégie: Cache First, puis Network
self.addEventListener('fetch', (event) => {
  const { request } = event;
  const url = new URL(request.url);

  // Ignorer les requêtes non-GET (POST, PUT, DELETE, etc.)
  if (request.method !== 'GET') {
    return;
  }

  // Ignorer les requêtes vers des schémas non-HTTP(S) (chrome-extension, file, etc.)
  if (!url.protocol.startsWith('http')) {
    return;
  }

  // Ignorer les requêtes vers d'autres origines (sauf si nécessaire)
  // On garde uniquement les requêtes vers notre origine
  if (url.origin !== self.location.origin) {
    return;
  }

  // Stratégie réseau d'abord pour les pages dynamiques PHP afin d'éviter le contenu obsolète.
  const isDynamicPhp =
    request.method === 'GET' &&
    url.origin === self.location.origin &&
    url.pathname.endsWith('.php');

  if (isDynamicPhp) {
    event.respondWith(
      fetch(request)
        .then((networkResponse) => {
          if (networkResponse && networkResponse.status === 200 && networkResponse.type === 'basic') {
            // Vérifier que la requête peut être mise en cache
            if (request.method === 'GET' && url.protocol.startsWith('http') && url.origin === self.location.origin) {
              const responseClone = networkResponse.clone();
              caches.open(CACHE_NAME).then((cache) => {
                try {
                  cache.put(request, responseClone);
                } catch (error) {
                  // Ignorer les erreurs de cache (requêtes non cacheables)
                }
              });
            }
          }
          return networkResponse;
        })
        .catch(() => caches.match(request))
    );
    return;
  }

  // Stratégie cache d'abord pour les ressources statiques.
  event.respondWith(
    caches.match(request).then((cachedResponse) => {
      if (cachedResponse) {
        return cachedResponse;
      }

      return fetch(request)
        .then((networkResponse) => {
          if (!networkResponse || networkResponse.status !== 200 || networkResponse.type !== 'basic') {
            return networkResponse;
          }

          // Vérifier que la requête peut être mise en cache
          if (request.method === 'GET' && url.protocol.startsWith('http') && url.origin === self.location.origin) {
            const responseToCache = networkResponse.clone();
            caches.open(CACHE_NAME).then((cache) => {
              try {
                cache.put(request, responseToCache);
              } catch (error) {
                // Ignorer les erreurs de cache (requêtes non cacheables)
              }
            });
          }

          return networkResponse;
        })
        .catch(() => {
          if (request.destination === 'document') {
            return caches.match('/index.php');
          }
        });
    })
  );
});

// Gestion des messages depuis l'application
self.addEventListener('message', (event) => {
  if (event.data && event.data.type === 'SKIP_WAITING') {
    self.skipWaiting();
  }
});

