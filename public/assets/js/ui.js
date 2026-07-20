import { api, currentUser } from './api.js?v=20260720.5';
import { storedCartCount } from './cart-storage.js?v=20260720.5';

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

export function notify(message, type = 'info') {
    let stack = document.querySelector('.notice-stack');
    if (!stack) {
        stack = document.createElement('div');
        stack.className = 'notice-stack';
        stack.setAttribute('aria-live', 'polite');
        document.body.append(stack);
    }

    const icons = {
        error: 'circle-alert',
        info: 'info',
        success: 'circle-check',
        warning: 'triangle-alert',
    };
    const safeType = Object.hasOwn(icons, type) ? type : 'info';
    const node = document.createElement('div');
    node.className = `notice notice-${safeType}`;
    node.setAttribute('role', safeType === 'error' ? 'alert' : 'status');
    node.innerHTML = `<i data-lucide="${icons[safeType]}"></i><span class="notice-message"></span><button type="button" class="notice-close" aria-label="Chiudi"><i data-lucide="x"></i></button><span class="notice-progress" aria-hidden="true"></span>`;
    node.querySelector('.notice-message').textContent = message;
    stack.append(node);
    refreshIcons(node);

    const remove = () => {
        node.classList.add('notice-leaving');
        setTimeout(() => node.remove(), 220);
    };
    const timeout = setTimeout(remove, 5000);
    node.querySelector('.notice-close').addEventListener('click', () => {
        clearTimeout(timeout);
        remove();
    });

    while (stack.children.length > 3) {
        stack.firstElementChild.remove();
    }
}

export function cartCount() {
    return storedCartCount();
}

export async function mountLayout() {
    if (!document.querySelector('link[rel="icon"]')) {
        const favicon = document.createElement('link');
        favicon.rel = 'icon';
        favicon.type = 'image/png';
        favicon.href = '/assets/images/favicon.png';
        document.head.append(favicon);
    }
    const user = await currentUser();
    const header = document.querySelector('#site-header');
    if (header) {
        const catalogActive = location.pathname === '/catalog.html';
        const ordersActive = location.pathname === '/orders.html';
        header.className = 'site-header';
        header.innerHTML = `
            <div class="container nav">
                <a class="brand" href="/" aria-label="Il Paradiso della Frutta, torna alla home">
                    <img class="brand-logo" src="/assets/images/ilparadisodellafrutta-logo-primary.png" alt="Il Paradiso della Frutta">
                </a>
                <nav class="nav-links" aria-label="Navigazione principale">
                    <a class="${catalogActive ? 'active' : ''}" href="/catalog.html" ${catalogActive ? 'aria-current="page"' : ''}><i data-lucide="layout-grid"></i><span>Catalogo</span></a>
                    <a class="${ordersActive ? 'active' : ''}" href="/orders.html" ${ordersActive ? 'aria-current="page"' : ''}><i data-lucide="receipt-text"></i><span>I miei ordini</span></a>
                </nav>
                <div class="nav-actions">
                    <a class="header-cart" href="/cart.html" aria-label="Apri carrello" title="Carrello">
                        <svg class="header-cart-icon" viewBox="0 0 24 24" width="23" height="23" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="8" cy="21" r="1"></circle><circle cx="19" cy="21" r="1"></circle><path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57L22 6H5.12"></path></svg><span class="nav-action-label">Carrello</span><span class="badge">${cartCount()}</span>
                    </a>
                    ${user
                        ? `<a class="btn btn-link account-label" href="/profile.html" aria-label="Apri profilo" title="${user.name}"><i data-lucide="circle-user-round"></i><span class="nav-action-label">${user.name}</span></a><button class="btn btn-link header-logout" id="logout" aria-label="Esci" title="Esci"><i data-lucide="log-out"></i><span class="nav-action-label">Esci</span></button>`
                        : `<a class="btn btn-link header-login" href="/login.html" aria-label="Accedi" title="Accedi"><i data-lucide="log-in"></i><span class="nav-action-label">Accedi</span></a><a class="btn btn-primary header-register" href="/register.html" aria-label="Registrati" title="Registrati"><i data-lucide="user-plus"></i><span class="nav-action-label">Registrati</span></a>`}
                </div>
            </div>`;
        refreshIcons(header);
        document.querySelector('#logout')?.addEventListener('click', async () => {
            await api('/auth/logout', { method: 'POST', body: '{}' });
            location.href = '/';
        });
    }
    const footer = document.querySelector('#site-footer');
    if (footer) {
        footer.innerHTML = `
            <div class="container footer-content">
                <div class="footer-brand-column">
                    <a href="/" aria-label="Il Paradiso della Frutta, torna alla home">
                        <img class="footer-logo" src="/assets/images/ilparadisodellafrutta-logo-white.png" alt="Il Paradiso della Frutta">
                    </a>
                    <p>Prodotti freschi selezionati ogni giorno per privati e ristoratori.</p>
                </div>
                <div class="footer-column">
                    <h2>Esplora</h2>
                    <nav aria-label="Navigazione piè di pagina">
                        <a href="/">Home</a>
                        <a href="/catalog.html">Catalogo</a>
                        <a href="/orders.html">I miei ordini</a>
                        <a href="${user ? '/profile.html' : '/login.html'}">${user ? 'Il mio profilo' : 'Accedi'}</a>
                    </nav>
                </div>
                <div class="footer-column">
                    <h2>Contatti</h2>
                    <a href="mailto:admin@ilparadisodellafrutta.it"><i data-lucide="mail"></i>admin@ilparadisodellafrutta.it</a>
                    <a href="https://wa.me/393792688229" target="_blank" rel="noopener"><i data-lucide="message-circle"></i>+39 379 268 8229</a>
                    <span><i data-lucide="map-pin"></i>Via dei Caduti Genovesi, 8<br>Bornasco (PV)</span>
                </div>
                <div class="footer-column footer-company">
                    <h2>Dati aziendali</h2>
                    <strong>Il Paradiso della Frutta</strong>
                    <span>di Castaldo Mariarosaria</span>
                    <span>Partita IVA 02396610186</span>
                </div>
            </div>
            <div class="container footer-bottom">
                <span>© ${new Date().getFullYear()} Il Paradiso della Frutta</span>
                <span>Qualità e freschezza, ogni giorno.</span>
            </div>`;
    }
    refreshIcons();
    return user;
}

export function productCard(product) {
    const image = product.image_url
        ? `<img src="${product.image_url}" alt="${product.name}" loading="lazy">`
        : '<span class="product-placeholder">🥬</span>';
    const price = Number(product.price_per_unit ?? product.price_per_kg).toLocaleString('it-IT', { style: 'currency', currency: 'EUR' });
    const unit = product.unit_of_measure?.symbol || 'u.';
    const minimum = Number(product.minimum_quantity || 1);
    return `<article class="card product-card reveal"><a class="product-media product-modal-trigger" href="/product.html?slug=${encodeURIComponent(product.slug)}" data-product-slug="${product.slug}" aria-haspopup="dialog">${image}<img class="quality-seal" src="/assets/images/frescogest-quality-seal.png" alt="" aria-hidden="true">${product.is_seasonal ? '<span class="seasonal-badge"><i data-lucide="sparkles"></i>Stagionale</span>' : ''}</a><div class="card-body product-card-body"><div class="product-category">${product.category?.name || ''}</div><a class="product-modal-trigger" href="/product.html?slug=${encodeURIComponent(product.slug)}" data-product-slug="${product.slug}" aria-haspopup="dialog"><h3>${product.name}</h3></a><p class="product-description">${product.description || 'Prodotto selezionato da Il Paradiso della Frutta.'}</p><div class="price-row"><strong class="price">${price}<small>/${unit}</small></strong>${product.has_personalized_price ? '<span class="badge">Il tuo prezzo</span>' : ''}</div><div class="product-purchase"><div class="quantity-stepper" aria-label="Quantità in ${unit}"><button class="qty-step" type="button" data-step="-${minimum}" aria-label="Diminuisci quantità"><i data-lucide="minus"></i></button><input class="card-quantity" type="number" min="${minimum}" step="${minimum}" inputmode="decimal" value="${minimum}" aria-label="Quantità in ${unit}"><button class="qty-step" type="button" data-step="${minimum}" aria-label="Aumenta quantità"><i data-lucide="plus"></i></button></div><button class="add-cart catalog-add-button" data-id="${product.id}" data-name="${product.name}" data-slug="${product.slug}" data-price="${product.price_per_unit ?? product.price_per_kg}" data-minimum="${minimum}" data-unit="${unit}" data-image="${product.image_url || ''}" aria-label="Aggiungi ${product.name} al carrello"><i data-lucide="shopping-cart"></i><span>Aggiungi</span></button></div></div></article>`;
}

export function skeletonCards(count = 6) {
    return Array.from({ length: count }, () => '<article class="card skeleton-card"><div class="skeleton skeleton-image"></div><div class="card-body"><div class="skeleton skeleton-line short"></div><div class="skeleton skeleton-line"></div><div class="skeleton skeleton-line"></div></div></article>').join('');
}

mountLayout().catch(() => {});
