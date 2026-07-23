import { api, currentUser } from './api.js?v=20260720.5';
import { storedCartCount } from './cart-storage.js?v=20260723.1';

let iconLibraryPromise;

export function refreshIcons(root = document) {
    const render = () => window.lucide?.createIcons({
        icons: window.lucide.icons,
        root,
        attrs: { 'aria-hidden': 'true' },
    });

    if (window.lucide) {
        render();
        return Promise.resolve();
    }

    if (!iconLibraryPromise) {
        iconLibraryPromise = new Promise((resolve, reject) => {
            const script = document.createElement('script');
            script.src = '/assets/vendor/lucide.min.js';
            script.onload = () => {
                render();
                resolve();
            };
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

    const icons = { error: 'circle-alert', info: 'info', success: 'circle-check', warning: 'triangle-alert' };
    const safeType = Object.hasOwn(icons, type) ? type : 'info';
    const node = document.createElement('div');
    node.className = `notice notice-${safeType}`;
    node.innerHTML = `<i data-lucide="${icons[safeType]}"></i><span class="notice-message"></span><button type="button" class="notice-close" aria-label="Chiudi"><i data-lucide="x"></i></button>`;
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
}

export function cartCount() {
    return storedCartCount();
}

export async function mountLayout() {
    const user = await currentUser();
    const pathname = location.pathname;
    const homeActive = ['/', '/index.html'].includes(pathname);
    const catalogActive = pathname === '/catalog.html';
    const ordersActive = pathname === '/orders.html';
    const header = document.querySelector('#site-header');

    if (header) {
        const usesFashionOverlayHeader = homeActive || catalogActive;

        header.className = `site-header${usesFashionOverlayHeader ? ' fashion-header fashion-header-overlay' : ' catalog-site-header'}`;
        header.innerHTML = `
                <div class="fashion-announcement" aria-label="Informazioni Cerino Store">
                    <div class="fashion-announcement-track">
                        <div class="fashion-announcement-group">
                            <span>Spedizioni disponibili in tutta Italia</span>
                            <i aria-hidden="true">✦</i>
                            <span>Nuovi arrivi ogni settimana</span>
                            <i aria-hidden="true">✦</i>
                            <span>Ordina direttamente su WhatsApp</span>
                            <i aria-hidden="true">✦</i>
                            <span>Men’s clothing · Cerino Store</span>
                            <i aria-hidden="true">✦</i>
                        </div>
                        <div class="fashion-announcement-group" aria-hidden="true">
                            <span>Spedizioni disponibili in tutta Italia</span>
                            <i>✦</i>
                            <span>Nuovi arrivi ogni settimana</span>
                            <i>✦</i>
                            <span>Ordina direttamente su WhatsApp</span>
                            <i>✦</i>
                            <span>Men’s clothing · Cerino Store</span>
                            <i>✦</i>
                        </div>
                    </div>
                </div>
            <div class="container nav">
                <a class="brand" href="/index.html" aria-label="Cerino Store, torna alla home">
                    <img class="brand-logo brand-logo-dark" src="/assets/images/cerino-logo-primary.png?v=20260723.2" alt="Cerino Store">
                    <img class="brand-logo brand-logo-light" src="/assets/images/cerino-logo-white.png?v=20260723.2" alt="Cerino Store">
                </a>
                <nav class="nav-links" aria-label="Navigazione principale">
                    <a class="${homeActive ? 'active' : ''}" href="/index.html" ${homeActive ? 'aria-current="page"' : ''}>Home</a>
                    <a class="${catalogActive ? 'active' : ''}" href="/catalog.html" ${catalogActive ? 'aria-current="page"' : ''}>Catalogo</a>
                    <a class="${ordersActive ? 'active' : ''}" href="/orders.html" ${ordersActive ? 'aria-current="page"' : ''}>I miei ordini</a>
                </nav>
                <div class="nav-actions">
                    <a class="header-cart" href="/cart.html" aria-label="Apri carrello"><i data-lucide="shopping-bag"></i><span class="nav-action-label">Carrello</span><span class="badge">${cartCount()}</span></a>
                    ${user
                        ? `<a class="btn btn-link account-label" href="/profile.html" aria-label="Account di ${user.name}"><i data-lucide="circle-user-round"></i><span class="nav-action-label">${user.name}</span></a><button class="btn btn-link header-logout" id="logout" aria-label="Esci"><i data-lucide="log-out"></i><span class="nav-action-label">Esci</span></button>`
                        : '<a class="btn btn-link header-login" href="/login.html"><i data-lucide="log-in"></i><span class="nav-action-label">Accedi</span></a><a class="btn header-register" href="/register.html">Registrati</a>'}
                </div>
            </div>`;

        header.querySelector('#logout')?.addEventListener('click', async () => {
            await api('/auth/logout', { method: 'POST', body: '{}' });
            location.href = '/index.html';
        });
    }

    const footer = document.querySelector('#site-footer');
    if (footer) {
        footer.innerHTML = `
            <div class="container footer-content">
                <div class="footer-brand-column">
                    <a href="/index.html"><img class="footer-logo" src="/assets/images/cerino-logo-white.png?v=20260723.2" alt="Cerino Store"></a>
                    <p>Abbigliamento uomo contemporaneo, selezionato con carattere a Lusciano.</p>
                </div>
                <div class="footer-column"><h2>Esplora</h2><nav><a href="/index.html">Home</a><a href="/catalog.html">Catalogo</a><a href="/orders.html">I miei ordini</a><a href="${user ? '/profile.html' : '/login.html'}">${user ? 'Il mio account' : 'Accedi'}</a></nav></div>
                <div class="footer-column"><h2>Contatti</h2><a href="mailto:info@cerinostore.it"><i data-lucide="mail"></i>info@cerinostore.it</a><a href="https://wa.me/393240994144" target="_blank" rel="noopener"><i data-lucide="message-circle"></i>+39 324 099 4144</a><span><i data-lucide="map-pin"></i>Viale Colucci 49<br>81030 Lusciano (CE)</span></div>
                <div class="footer-column"><h2>Cerino Store</h2><strong>Men’s Clothing</strong><span>Spedizioni disponibili</span><span>Ordini assistiti su WhatsApp</span></div>
            </div>
            <div class="container footer-bottom"><span>© ${new Date().getFullYear()} Cerino Store</span><span>Wear your attitude.</span></div>`;
    }

    refreshIcons();
    return user;
}

function escapeMarkup(value) {
    const element = document.createElement('span');
    element.textContent = String(value ?? '');

    return element.innerHTML;
}

function variantSwatch(color) {
    const normalized = String(color || '').trim().toLocaleLowerCase('it-IT');
    const colors = {
        arancione: '#dd7434',
        azzurro: '#76aaca',
        beige: '#c9ad87',
        bianco: '#f8f7f3',
        blu: '#233a64',
        bordeaux: '#721f35',
        crema: '#eee2c7',
        giallo: '#e5bd35',
        grigio: '#858585',
        marrone: '#6a4935',
        navy: '#17243d',
        nero: '#161616',
        oliva: '#667244',
        panna: '#eee7d8',
        rosa: '#d9a4ad',
        rosso: '#a92832',
        sabbia: '#c7b28f',
        verde: '#35634a',
        viola: '#704c7c',
    };

    if (colors[normalized]) return colors[normalized];

    let hash = 0;
    for (const character of normalized) hash = ((hash << 5) - hash) + character.charCodeAt(0);

    return `hsl(${Math.abs(hash) % 360} 34% 48%)`;
}

export function variantPickerMarkup(variants, className = '') {
    const available = Array.isArray(variants)
        ? variants.filter(variant => variant?.id && (variant.size || variant.color))
        : [];
    if (!available.length) return '';

    const first = available[0];
    const sizes = [...new Set(available.map(variant => variant.size).filter(Boolean))];
    const colors = [...new Set(available.map(variant => variant.color).filter(Boolean))];
    const firstSize = first.size || sizes[0] || '';
    const firstColor = first.color || colors[0] || '';
    const colorButtons = colors.map(color => {
        const active = color === firstColor;

        return `<button class="variant-color-option${active ? ' active' : ''}" type="button" data-color="${escapeMarkup(color)}" aria-pressed="${active}" title="${escapeMarkup(color)}"><span class="variant-swatch" style="--variant-swatch:${variantSwatch(color)}"></span><span class="sr-only">${escapeMarkup(color)}</span></button>`;
    }).join('');
    const sizeButtons = sizes.map(size => {
        const active = size === firstSize;
        const availableForColor = !firstColor || available.some(variant => variant.color === firstColor && variant.size === size);

        return `<button class="variant-size-option${active ? ' active' : ''}" type="button" data-size="${escapeMarkup(size)}" aria-pressed="${active}" ${availableForColor ? '' : 'disabled'}>${escapeMarkup(size)}</button>`;
    }).join('');
    const options = available.map((variant, index) => `<option value="${variant.id}" data-size="${escapeMarkup(variant.size || '')}" data-color="${escapeMarkup(variant.color || '')}" ${index === 0 ? 'selected' : ''}>${escapeMarkup([variant.size, variant.color].filter(Boolean).join(' · '))}</option>`).join('');

    return `<div class="variant-picker ${className}" data-variant-axis="color">
        ${colors.length ? `<fieldset class="variant-picker-group variant-picker-colors"><legend>Colore <strong class="variant-selected-color">${escapeMarkup(firstColor)}</strong></legend><div class="variant-color-options">${colorButtons}</div></fieldset>` : ''}
        ${sizes.length ? `<fieldset class="variant-picker-group variant-picker-sizes"><legend>Taglia</legend><div class="variant-size-options">${sizeButtons}</div></fieldset>` : ''}
        <select class="card-variant" tabindex="-1" aria-hidden="true" hidden>${options}</select>
    </div>`;
}

export function productCard(product) {
    const image = product.image_url
        ? `<img src="${product.image_url}" alt="${product.name}" loading="lazy">`
        : '<span class="product-placeholder"><i data-lucide="shirt"></i><small>Cerino Store</small></span>';
    const unitPrice = Number(product.price_per_unit ?? product.price_per_kg);
    const price = unitPrice.toLocaleString('it-IT', { style: 'currency', currency: 'EUR' });
    const minimum = Number(product.minimum_quantity || 1);
    const total = (unitPrice * minimum).toLocaleString('it-IT', { style: 'currency', currency: 'EUR' });
    const unit = product.unit_of_measure?.symbol || 'pz';
    const variants = Array.isArray(product.variants) ? product.variants : [];
    const variantPicker = variantPickerMarkup(variants, 'variant-picker-card');

    return `<article class="card product-card reveal">
        <a class="product-media product-modal-trigger" href="/product.html?slug=${encodeURIComponent(product.slug)}" data-product-slug="${product.slug}" aria-haspopup="dialog">${image}${product.is_seasonal ? '<span class="seasonal-badge">Novità</span>' : ''}</a>
        <div class="card-body product-card-body">
            <div class="product-category">${product.brand || product.category?.name || 'Cerino Store'}</div>
            <a class="product-modal-trigger" href="/product.html?slug=${encodeURIComponent(product.slug)}" data-product-slug="${product.slug}" aria-haspopup="dialog"><h3>${product.name}</h3></a>
            <p class="product-description">${product.description || 'Un capo selezionato per completare il tuo stile.'}</p>
            ${variantPicker}
            <div class="price-row"><div class="product-price-summary"><strong class="price">${price}</strong><span class="product-total-preview" aria-live="polite">Totale: ${total}</span></div></div>
            <div class="product-purchase">
                <div class="quantity-stepper"><button class="qty-step" type="button" data-step="-${minimum}" aria-label="Diminuisci quantità"><i data-lucide="minus"></i></button><input class="card-quantity" type="number" min="${minimum}" step="${minimum}" value="${minimum}" inputmode="numeric" aria-label="Quantità"><button class="qty-step" type="button" data-step="${minimum}" aria-label="Aumenta quantità"><i data-lucide="plus"></i></button></div>
                <button class="add-cart catalog-add-button" data-id="${product.id}" data-name="${product.name}" data-slug="${product.slug}" data-price="${unitPrice}" data-minimum="${minimum}" data-unit="${unit}" data-image="${product.image_url || ''}" aria-label="Aggiungi ${product.name} al carrello"><i data-lucide="shopping-bag"></i><span>Aggiungi</span></button>
            </div>
        </div>
    </article>`;
}

export function skeletonCards(count = 6) {
    return Array.from({ length: count }, () => '<article class="card skeleton-card"><div class="skeleton skeleton-image"></div><div class="card-body"><div class="skeleton skeleton-line short"></div><div class="skeleton skeleton-line"></div><div class="skeleton skeleton-line"></div></div></article>').join('');
}

mountLayout().catch(() => {});
