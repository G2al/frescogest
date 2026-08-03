const lockTouchZoom = () => {
    document.addEventListener('gesturestart', event => event.preventDefault(), { passive: false });
    document.addEventListener('gesturechange', event => event.preventDefault(), { passive: false });
    document.addEventListener('gestureend', event => event.preventDefault(), { passive: false });
    document.addEventListener('touchmove', event => {
        if (event.touches.length > 1) {
            event.preventDefault();
        }
    }, { passive: false });
};

lockTouchZoom();

if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/service-worker.js', {
            scope: '/',
            updateViaCache: 'none'
        }).then(registration => registration.update()).catch(() => {});
    });
}
