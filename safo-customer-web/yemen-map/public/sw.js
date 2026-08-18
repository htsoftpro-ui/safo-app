const CACHE = 'yemen-map-v2';
const SHELL = ['./', './index.html', './manifest.webmanifest'];

const isPmtiles = (url) => url.pathname.endsWith('.pmtiles');
const isOptionalDem = (url) => url.pathname.includes('/data/dem/');

self.addEventListener('install', (event) => {
  event.waitUntil(caches.open(CACHE).then((cache) => cache.addAll(SHELL)).then(() => self.skipWaiting()));
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((keys) => Promise.all(keys.filter((key) => key !== CACHE).map((key) => caches.delete(key))))
      .then(() => self.clients.claim()),
  );
});

self.addEventListener('fetch', (event) => {
  const request = event.request;
  if (request.method !== 'GET') return;
  const url = new URL(request.url);
  if (url.origin !== self.location.origin) return;

  // Cache API matches PMTiles range requests by URL and can return the wrong byte range.
  // Let the PMTiles protocol own range fetching; this avoids corrupt archives and decode errors.
  if (isPmtiles(url)) return;

  event.respondWith((async () => {
    const cache = await caches.open(CACHE);
    const cached = await cache.match(request);
    if (cached) return cached;
    try {
      const response = await fetch(request);
      // Never cache missing optional DEM resources or other error responses.
      if (response.ok && !isOptionalDem(url)) {
        cache.put(request, response.clone()).catch(() => undefined);
      }
      return response;
    } catch {
      return cached || Response.error();
    }
  })());
});
