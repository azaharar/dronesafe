const CACHE_NAME = 'dronesafe-v1';
const ASSETS_TO_CACHE = [
  '/',
  '/css/styles.css',
  '/js/app.js',
  '/images/icon-192x192.png',
  '/images/icon-512x512.png'
];

// Instalación: Cachea solo los assets estáticos
self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then(cache => cache.addAll(ASSETS_TO_CACHE))
  );
});

// Fetch: Excluye rutas dinámicas (PHP) del cache
self.addEventListener('fetch', (event) => {
  const url = new URL(event.request.url);
  
  // Excluye estas rutas del cache 
  const excludedRoutes = [
    '/tracking.php',
    '/pickup.php',
    '/packages.php'
  ];

  if (excludedRoutes.some(route => url.pathname.includes(route))) {
    return fetch(event.request); // Ignora el cache para estas rutas
  }

  // Comportamiento normal para el resto
  event.respondWith(
    caches.match(event.request)
      .then(response => response || fetch(event.request))
  );
});