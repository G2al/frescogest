const cartStorageKey = 'ilparadisodellafrutta_cart_v3';

function migrateLegacyCart() {
    if (localStorage.getItem(cartStorageKey) !== null) {
        return;
    }

    Object.keys(localStorage)
        .filter(key => key.endsWith('_cart_v2'))
        .forEach(key => localStorage.removeItem(key));

    localStorage.setItem(cartStorageKey, '[]');
}

export function getStoredCart() {
    migrateLegacyCart();

    return JSON.parse(localStorage.getItem(cartStorageKey) || '[]');
}

export function saveStoredCart(cart) {
    localStorage.setItem(cartStorageKey, JSON.stringify(cart));
}

export function storedCartCount() {
    return getStoredCart().length;
}
