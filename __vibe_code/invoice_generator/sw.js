self.addEventListener('install', e => {
    e.waitUntil(
      caches.open('v1').then(cache => {
        return cache.addAll([
          '/',
          '/index.html',
          '/manifest.json',
          '/icons/icon-192.png',
          '/icons/icon-512.png'
          // Dodaj pozostałe potrzebne pliki CSS/JS tutaj
        ]);
      })
    );
  });
  
  self.addEventListener('fetch', event => {
    event.respondWith(
      caches.match(event.request).then(cachedRes => {
        return cachedRes || fetch(event.request);
      })
    );
  });
  