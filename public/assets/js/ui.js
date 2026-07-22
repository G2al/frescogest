import { api, currentUser } from './api.js?v=20260720.5';
import { storedCartCount } from './cart-storage.js?v=20260720.5';

let iconLibraryPromise;
let storeStatusTimer;

async function watchStoreStatus() {
    window.clearTimeout(storeStatusTimer);

    try {
        const response = await fetch('/api/v1/store/status', {
            credentials: 'same-origin',
            cache: 'no-store',
            headers: { Accept: 'application/json' },
        });
        const status = (await response.json()).data;

        if (status.is_closed) {
            window.location.reload();
            return;
        }

        const closureTime = status.closes_at ? new Date(status.closes_at).getTime() : Number.NaN;
        const millisecondsUntilClosure = closureTime - new Date(status.server_time).getTime();
        const nextCheck = Number.isFinite(millisecondsUntilClosure)
            ? Math.min(60000, Math.max(1000, millisecondsUntilClosure + 250))
            : 60000;
        storeStatusTimer = window.setTimeout(watchStoreStatus, nextCheck);
    } catch {
        storeStatusTimer = window.setTimeout(watchStoreStatus, 30000);
    }
}

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
        const catalogActive = ['/', '/index.html', '/catalog.html'].includes(location.pathname);
        const activeCategory = catalogActive ? new URLSearchParams(location.search).get('category') || '' : '';
        const ordersActive = location.pathname === '/orders.html';
        const catalogStyleHeader = catalogActive || ordersActive;
        header.className = `site-header${catalogStyleHeader ? ' catalog-site-header' : ''}`;
        header.innerHTML = `
            <div class="container nav">
                <a class="brand" href="/" aria-label="Il Paradiso della Frutta, torna alla home">
                    <img class="brand-logo" src="/assets/images/ilparadisodellafrutta-logo-primary.png" alt="Il Paradiso della Frutta">
                </a>
                <nav class="nav-links" aria-label="Navigazione principale">
                    ${catalogStyleHeader
                        ? `<a class="${catalogActive && !activeCategory ? 'active' : ''}" data-catalog-root href="/" ${catalogActive && !activeCategory ? 'aria-current="page"' : ''}><span>Catalogo</span></a><a class="catalog-category-link ${activeCategory === 'frutta' ? 'active' : ''}" data-category="frutta" href="/?category=frutta" ${activeCategory === 'frutta' ? 'aria-current="page"' : ''}><span>Frutta</span></a><a class="catalog-category-link ${activeCategory === 'verdura' ? 'active' : ''}" data-category="verdura" href="/?category=verdura" ${activeCategory === 'verdura' ? 'aria-current="page"' : ''}><span>Verdura</span></a><a class="catalog-category-link ${activeCategory === 'latticini' ? 'active' : ''}" data-category="latticini" href="/?category=latticini" ${activeCategory === 'latticini' ? 'aria-current="page"' : ''}><span>Latticini</span></a><a class="${ordersActive ? 'active' : ''}" href="/orders.html" ${ordersActive ? 'aria-current="page"' : ''}><span>I miei ordini</span></a>`
                        : `<a href="/"><i data-lucide="layout-grid"></i><span>Catalogo</span></a><a href="/orders.html"><i data-lucide="receipt-text"></i><span>I miei ordini</span></a>`}
                </nav>
                <div class="nav-actions">
                    ${catalogActive ? '<label class="catalog-header-search"><span class="sr-only">Cerca prodotti</span><input type="search" placeholder="Cerca prodotti…" autocomplete="off"><i data-lucide="search"></i></label>' : ''}
                    <a class="header-cart" href="/cart.html" aria-label="Apri carrello" title="Carrello">
                        <svg class="header-cart-icon" viewBox="0 0 24 24" width="23" height="23" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="8" cy="21" r="1"></circle><circle cx="19" cy="21" r="1"></circle><path d="M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57L22 6H5.12"></path></svg><span class="nav-action-label">Carrello</span><span class="badge">${cartCount()}</span>
                    </a>
                    ${user
                        ? `<a class="btn btn-link account-label" href="/profile.html" aria-label="Apri profilo" title="${user.name}"><i data-lucide="circle-user-round"></i><span class="nav-action-label">${user.name}</span></a><button class="btn btn-link header-logout" id="logout" aria-label="Esci" title="Esci"><i data-lucide="log-out"></i><span class="nav-action-label">Esci</span></button>`
                        : `<a class="btn btn-link header-login" href="/login.html" aria-label="Accedi" title="Accedi"><i data-lucide="log-in"></i><span class="nav-action-label">Accedi</span></a>`}
                </div>
            </div>`;
        refreshIcons(header);
        const catalogHeaderSearch = header.querySelector('.catalog-header-search input');
        if (catalogHeaderSearch) {
            const catalogSearch = document.querySelector('#product-search');
            catalogHeaderSearch.value = catalogSearch?.value || new URLSearchParams(location.search).get('search') || '';
            catalogHeaderSearch.addEventListener('input', () => {
                if (!catalogSearch) return;
                catalogSearch.value = catalogHeaderSearch.value;
                catalogSearch.dispatchEvent(new Event('input', { bubbles: true }));
            });
            catalogHeaderSearch.addEventListener('keydown', event => {
                if (event.key !== 'Enter') return;
                event.preventDefault();
                document.querySelector('#catalog-search-button')?.click();
                document.querySelector('#catalog-results')?.scrollIntoView({ behavior: 'smooth' });
            });
        }
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
                        <a href="/">Catalogo</a>
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
    const unitPrice = Number(product.price_per_unit ?? product.price_per_kg);
    const price = unitPrice.toLocaleString('it-IT', { style: 'currency', currency: 'EUR' });
    const unit = product.unit_of_measure?.symbol || 'u.';
    const minimum = Number(product.minimum_quantity || 1);
    const total = (unitPrice * minimum).toLocaleString('it-IT', { style: 'currency', currency: 'EUR' });
    return `<article class="card product-card reveal"><a class="product-media product-modal-trigger" href="/product.html?slug=${encodeURIComponent(product.slug)}" data-product-slug="${product.slug}" aria-haspopup="dialog">${image}<img class="quality-seal" src="/assets/images/frescogest-quality-seal.png?v=bee6ef63" alt="" aria-hidden="true">${product.is_seasonal ? '<span class="seasonal-badge"><i data-lucide="sparkles"></i>Stagionale</span>' : ''}</a><div class="card-body product-card-body"><div class="product-category">${product.category?.name || ''}</div><a class="product-modal-trigger" href="/product.html?slug=${encodeURIComponent(product.slug)}" data-product-slug="${product.slug}" aria-haspopup="dialog"><h3>${product.name}</h3></a><p class="product-description">${product.description || 'Prodotto selezionato da Il Paradiso della Frutta.'}</p><div class="price-row"><div class="product-price-summary"><strong class="price">${price}<small>/${unit}</small></strong><span class="product-total-preview" aria-live="polite">Totale: ${total}</span></div>${product.has_personalized_price ? '<span class="badge">Il tuo prezzo</span>' : ''}</div><div class="product-purchase"><div class="quantity-stepper" aria-label="Quantità in ${unit}"><button class="qty-step" type="button" data-step="-${minimum}" aria-label="Diminuisci quantità"><i data-lucide="minus"></i></button><input class="card-quantity" type="number" min="${minimum}" step="${minimum}" inputmode="decimal" value="${minimum}" aria-label="Quantità in ${unit}"><button class="qty-step" type="button" data-step="${minimum}" aria-label="Aumenta quantità"><i data-lucide="plus"></i></button></div><button class="add-cart catalog-add-button" data-id="${product.id}" data-name="${product.name}" data-slug="${product.slug}" data-price="${product.price_per_unit ?? product.price_per_kg}" data-minimum="${minimum}" data-unit="${unit}" data-image="${product.image_url || ''}" aria-label="Aggiungi ${product.name} al carrello"><i data-lucide="shopping-cart"></i><span>Aggiungi</span></button></div></div></article>`;
}

export function skeletonCards(count = 6) {
    return Array.from({ length: count }, () => '<article class="card skeleton-card"><div class="skeleton skeleton-image"></div><div class="card-body"><div class="skeleton skeleton-line short"></div><div class="skeleton skeleton-line"></div><div class="skeleton skeleton-line"></div></div></article>').join('');
}

watchStoreStatus();
mountLayout().catch(() => {});
