const viewport = document.querySelector('meta[name="viewport"]');
const isStandalone = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;

if (isStandalone) {
    document.documentElement.classList.add('fi-admin-pwa-standalone');
}

if (viewport) {
    viewport.setAttribute('content', 'width=device-width,initial-scale=1,maximum-scale=1,user-scalable=no,viewport-fit=cover');
}

document.addEventListener('gesturestart', event => event.preventDefault(), { passive: false });
document.addEventListener('gesturechange', event => event.preventDefault(), { passive: false });
document.addEventListener('gestureend', event => event.preventDefault(), { passive: false });
document.addEventListener('touchmove', event => {
    if (event.touches.length > 1) {
        event.preventDefault();
    }
}, { passive: false });

if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/admin-service-worker.js', {
            scope: '/admin',
            updateViaCache: 'none'
        }).then(registration => registration.update()).catch(() => {});
    });
}
