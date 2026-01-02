self.addEventListener("install", (event) => {
    console.log("Service Worker installing...");
    // Add caching logic here if needed
});

self.addEventListener("activate", (event) => {
    console.log("Service Worker activating...");
});

self.addEventListener("fetch", (event) => {
    // Basic pass-through
    // event.respondWith(fetch(event.request));
});
