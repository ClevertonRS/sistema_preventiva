const CACHE_NAME = "vivo-preventivas-v4";
const ASSETS_TO_CACHE = [
  "./",
  "./login.php",
  "./dashboard.php",
  "./assets/icons/manifest.json",
];

// Instalação do Service Worker e gravação do App Shell
self.addEventListener("install", (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) =>
      Promise.all(
        ASSETS_TO_CACHE.map((url) =>
          cache.add(url).catch(() => {}),
        ),
      ),
    ),
  );
  self.skipWaiting();
});

// Limpeza de caches antigos
self.addEventListener("activate", (event) => {
  event.waitUntil(
    caches.keys().then((keys) => {
      return Promise.all(
        keys.map((key) => {
          if (key !== CACHE_NAME) {
            return caches.delete(key);
          }
        }),
      );
    }),
  );
  self.clients.claim();
});

// Interceptador de requisições
self.addEventListener("fetch", (event) => {
  event.respondWith(
    caches.match(event.request).then((cachedResponse) => {
      return cachedResponse || fetch(event.request);
    }),
  );
});
