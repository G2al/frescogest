import { api, currentUser } from './api.js';

let iconLibraryPromise;

export function refreshIcons(root = document) {
    const render = () => window.lucide?.createIcons({ icons: window.lucide.icons, root, attrs: { 'aria-hidden': 'true' } });
    if (window.lucide) {
        render();
        return Promise.resolve();
    }
    if (!iconLibraryPromise) {
        iconLibraryPromise = new Promise((resolve, reject) => {
            const script = document.createElement('script');
            script.src = '/assets/vendor/lucide.min.js';
            script.onload = () => { render(); resolve(); };
            script.onerror = reject;
            document.head.append(script);
        });
    } else {
        iconLibraryPromise.then(render);
    }
    return iconLibraryPromise;
}

export function notify(message) {
    document.querySelector('.notice')?.remove();
    const node = document.createElement('div');
    node.className = 'notice';
    node.textContent = message;
    document.body.append(node);
    setTimeout(() => node.remove(), 4000);
}

export function cartCount() {
    return JSON.parse(localStorage.getItem('frescogest_cart_v2') || '[]').length;
}

export async function mountLayout() {
    if (!document.querySelector('link[rel="icon"]')) {
        const favicon = document.createElement('link');
        favicon.rel = 'icon';
        favicon.type = 'image/png';
        favicon.href = '/assets/images/frescogest-mark.png';
        document.head.append(favicon);
    }
    const user = await currentUser();
    const header = document.querySelector('#site-header');
    if (header) {
        header.className = 'site-header';
        header.innerHTML = `<div class="container nav"><a class="brand" href="/" aria-label="Frescogest"><img class="brand-logo" src="/assets/images/frescogest-logo.png" alt="Frescogest"></a><nav class="nav-links"><a href="/catalog.html">Catalogo</a><a href="/orders.html">I miei ordini</a></nav><div class="nav-actions"><a class="btn header-cart" href="/cart.html"><i data-lucide="shopping-cart"></i><span>Carrello</span><span class="badge">${cartCount()}</span></a>${user ? `<a class="btn btn-link account-label" href="/profile.html"><i data-lucide="circle-user-round"></i>${user.name}</a><button class="btn btn-link header-logout" id="logout"><i data-lucide="log-out"></i>Esci</button>` : '<a class="btn btn-link header-login" href="/login.html"><i data-lucide="log-in"></i>Accedi</a><a class="btn btn-primary header-register" href="/register.html"><i data-lucide="user-plus"></i>Registrati</a>'}</div></div>`;
        refreshIcons(header);
        document.querySelector('#logout')?.addEventListener('click', async () => {
            await api('/auth/logout', { method: 'POST', body: '{}' });
            location.href = '/';
        });
    }
    const footer = document.querySelector('#site-footer');
    if (footer) footer.innerHTML = '<div class="container footer-content"><img class="footer-logo" src="/assets/images/frescogest-logo.png" alt="Frescogest"><span>Prodotti freschi, richieste semplici.</span></div>';
    return user;
}

export function productCard(product) {
    const image = product.image_url
        ? `<img src="${product.image_url}" alt="${product.name}" loading="lazy">`
        : '<span class="product-placeholder">🥬</span>';
    const price = Number(product.price_per_kg).toLocaleString('it-IT', { style: 'currency', currency: 'EUR' });
    return `<article class="card product-card reveal"><a class="product-media product-modal-trigger" href="/product.html?slug=${encodeURIComponent(product.slug)}" data-product-slug="${product.slug}" aria-haspopup="dialog">${image}${product.is_seasonal ? '<span class="seasonal-badge"><i data-lucide="sparkles"></i>Stagionale</span>' : ''}</a><div class="card-body product-card-body"><div class="product-category">${product.category?.name || ''}</div><a class="product-modal-trigger" href="/product.html?slug=${encodeURIComponent(product.slug)}" data-product-slug="${product.slug}" aria-haspopup="dialog"><h3>${product.name}</h3></a><p class="product-description">${product.description || 'Prodotto selezionato da Frescogest.'}</p><div class="price-row"><strong class="price">${price}<small>/kg</small></strong>${product.has_personalized_price ? '<span class="badge">Il tuo prezzo</span>' : ''}</div><div class="product-purchase"><div class="quantity-stepper" aria-label="Quantità in chilogrammi"><button class="qty-step" type="button" data-step="-1" aria-label="Diminuisci quantità"><i data-lucide="minus"></i></button><input class="card-quantity" type="number" step="1" inputmode="decimal" value="1" aria-label="Quantità in kg"><button class="qty-step" type="button" data-step="1" aria-label="Aumenta quantità"><i data-lucide="plus"></i></button></div><button class="add-cart catalog-add-button" data-id="${product.id}" data-name="${product.name}" data-slug="${product.slug}" data-price="${product.price_per_kg}" data-image="${product.image_url || ''}" aria-label="Aggiungi ${product.name} al carrello"><i data-lucide="shopping-cart"></i><span>Aggiungi</span></button></div></div></article>`;
}

export function skeletonCards(count = 6) {
    return Array.from({ length: count }, () => '<article class="card skeleton-card"><div class="skeleton skeleton-image"></div><div class="card-body"><div class="skeleton skeleton-line short"></div><div class="skeleton skeleton-line"></div><div class="skeleton skeleton-line"></div></div></article>').join('');
}

mountLayout().catch(() => {});
