import { api } from './api.js?v=20260720.5';
import { refreshIcons, variantPickerMarkup } from './ui.js?v=20260723.5';
import './cart.js?v=20260723.3';

const productsRoot = document.querySelector('#featured-products');
const progressTrack = document.querySelector('.fashion-capsule-progress');
const progressValue = document.querySelector('#fashion-capsule-progress-value');
const fallbackImages = [
    '/assets/images/cerino-editorial.jpg',
    '/assets/images/cerino-sport.png',
    '/assets/images/cerino-hero.png',
];
let modalTrigger;

function escapeHtml(value) {
    const node = document.createElement('span');
    node.textContent = String(value ?? '');

    return node.innerHTML;
}

function productImage(product) {
    return product.image_url || fallbackImages[(Number(product.id) - 1) % fallbackImages.length];
}

function featuredProduct(product) {
    const unitPrice = Number(product.price_per_unit ?? product.price_per_kg);
    const price = unitPrice.toLocaleString('it-IT', {
        style: 'currency',
        currency: 'EUR',
    });
    const image = `<img src="${productImage(product)}" alt="${escapeHtml(product.name)}" loading="lazy">`;
    const variants = Array.isArray(product.variants) ? product.variants : [];
    const variantPicker = variantPickerMarkup(variants, 'variant-picker-featured');
    const minimum = Number(product.minimum_quantity || 1);
    const unit = product.unit_of_measure?.symbol || 'pz';

    return `<article class="featured-product">
        <button class="featured-product-image home-product-trigger" type="button" data-product-slug="${escapeHtml(product.slug)}" aria-haspopup="dialog"><span class="capsule-new">New</span>${image}</button>
        <div class="featured-product-copy">
            <small>${escapeHtml(product.brand || product.category?.name || 'Cerino Store')}</small>
            <button class="featured-product-name home-product-trigger" type="button" data-product-slug="${escapeHtml(product.slug)}" aria-haspopup="dialog">${escapeHtml(product.name)}</button>
            <span class="featured-product-price">${price}<small class="product-total-preview" aria-live="polite">Totale: ${price}</small></span>
            ${variantPicker || '<small class="home-product-availability">Varianti su richiesta</small>'}
            <span class="featured-product-purchase">
                <span class="quantity-stepper">
                    <button class="qty-step" type="button" data-step="-${minimum}" aria-label="Diminuisci quantità"><i data-lucide="minus"></i></button>
                    <input class="card-quantity" type="number" min="${minimum}" step="${minimum}" value="${minimum}" inputmode="numeric" aria-label="Quantità">
                    <button class="qty-step" type="button" data-step="${minimum}" aria-label="Aumenta quantità"><i data-lucide="plus"></i></button>
                </span>
                <button class="add-cart catalog-add-button" type="button" data-id="${product.id}" data-name="${escapeHtml(product.name)}" data-slug="${escapeHtml(product.slug)}" data-price="${unitPrice}" data-minimum="${minimum}" data-unit="${escapeHtml(unit)}" data-image="${escapeHtml(product.image_url || '')}" aria-label="Aggiungi ${escapeHtml(product.name)} al carrello"><i data-lucide="shopping-bag"></i><span>Aggiungi</span></button>
            </span>
        </div>
    </article>`;
}

function updateCapsuleProgress() {
    if (!productsRoot || !progressValue) return;

    const availableScroll = productsRoot.scrollWidth - productsRoot.clientWidth;
    const progress = availableScroll > 0 ? productsRoot.scrollLeft / availableScroll : 0;
    const thumbTravel = Math.max((progressTrack?.clientWidth || 0) - progressValue.clientWidth, 0);
    progressValue.style.transform = `translateX(${progress * thumbTravel}px)`;
}

function ensureProductModal() {
    if (document.querySelector('#home-product-modal')) return;

    document.body.insertAdjacentHTML('beforeend', `
        <div id="home-product-modal-backdrop" class="product-modal-backdrop" aria-hidden="true"></div>
        <section id="home-product-modal" class="product-modal" role="dialog" aria-modal="true" aria-labelledby="home-product-modal-title" aria-hidden="true">
            <div class="product-modal-panel">
                <button class="product-modal-close" type="button" aria-label="Chiudi dettaglio prodotto"><i data-lucide="x"></i></button>
                <div class="product-modal-content"></div>
            </div>
        </section>
    `);
    refreshIcons(document.querySelector('#home-product-modal'));
}

function closeProductModal() {
    const modal = document.querySelector('#home-product-modal');
    const backdrop = document.querySelector('#home-product-modal-backdrop');
    modal?.classList.remove('open');
    backdrop?.classList.remove('open');
    modal?.setAttribute('aria-hidden', 'true');
    backdrop?.setAttribute('aria-hidden', 'true');
    document.body.classList.remove('product-modal-open');
    modalTrigger?.focus();
}

async function showProductModal(slug, trigger) {
    ensureProductModal();
    modalTrigger = trigger;

    const modal = document.querySelector('#home-product-modal');
    const backdrop = document.querySelector('#home-product-modal-backdrop');
    const content = modal.querySelector('.product-modal-content');
    content.innerHTML = '<div class="product-modal-skeleton skeleton"></div><div class="product-modal-loading"><div class="skeleton skeleton-line short"></div><div class="skeleton skeleton-line"></div><div class="skeleton skeleton-line"></div></div>';
    modal.classList.add('open');
    backdrop.classList.add('open');
    modal.setAttribute('aria-hidden', 'false');
    backdrop.setAttribute('aria-hidden', 'false');
    document.body.classList.add('product-modal-open');

    try {
        const { data: product } = await api(`/catalog/products/${encodeURIComponent(slug)}`);
        const price = Number(product.price_per_unit ?? product.price_per_kg).toLocaleString('it-IT', {
            style: 'currency',
            currency: 'EUR',
        });
        const variants = Array.isArray(product.variants) ? product.variants : [];
        const variantPicker = variantPickerMarkup(variants, 'variant-picker-modal');
        const unitPrice = Number(product.price_per_unit ?? product.price_per_kg);
        const minimum = Number(product.minimum_quantity || 1);
        const unit = product.unit_of_measure?.symbol || 'pz';
        const total = (unitPrice * minimum).toLocaleString('it-IT', {
            style: 'currency',
            currency: 'EUR',
        });

        content.innerHTML = `
            <div class="product-modal-media"><img src="${productImage(product)}" alt="${escapeHtml(product.name)}"></div>
            <div class="product-modal-copy">
                <span class="eyebrow">${escapeHtml(product.brand || product.category?.name || 'Cerino Store')}</span>
                <h2 id="home-product-modal-title">${escapeHtml(product.name)}</h2>
                <p>${escapeHtml(product.description || 'Un capo selezionato per completare il tuo stile.')}</p>
                <div class="product-modal-facts"><span><i data-lucide="shirt"></i>Moda uomo</span><span><i data-lucide="badge-check"></i>Selezione Cerino</span></div>
                <div class="product-modal-price"><div class="product-price-summary"><strong>${price}</strong><span class="product-total-preview" aria-live="polite">Totale: ${total}</span></div></div>
                <div class="product-modal-purchase">
                    ${variantPicker || '<p class="home-product-availability">Contattaci per conoscere taglie e colori disponibili.</p>'}
                    <label>Quantità</label>
                    <div class="product-modal-controls">
                        <div class="quantity-stepper" aria-label="Quantità">
                            <button class="qty-step" type="button" data-step="-${minimum}" aria-label="Diminuisci quantità"><i data-lucide="minus"></i></button>
                            <input class="card-quantity" type="number" min="${minimum}" step="${minimum}" value="${minimum}" inputmode="numeric" aria-label="Quantità">
                            <button class="qty-step" type="button" data-step="${minimum}" aria-label="Aumenta quantità"><i data-lucide="plus"></i></button>
                        </div>
                        <button class="add-cart catalog-add-button" type="button" data-id="${product.id}" data-name="${escapeHtml(product.name)}" data-slug="${escapeHtml(product.slug)}" data-price="${unitPrice}" data-minimum="${minimum}" data-unit="${escapeHtml(unit)}" data-image="${escapeHtml(product.image_url || '')}"><i data-lucide="shopping-bag"></i><span>Aggiungi al carrello</span></button>
                    </div>
                </div>
            </div>`;
        refreshIcons(content);
    } catch {
        content.innerHTML = '<div class="empty product-modal-error">Non è stato possibile caricare il prodotto.</div>';
    }
}

async function loadFeaturedProducts() {
    if (!productsRoot) return;

    try {
        const response = await api('/catalog/products');
        const products = response.data.slice(0, 8);
        productsRoot.innerHTML = products.length
            ? products.map(featuredProduct).join('')
            : '<p class="featured-empty">La nuova collezione sarà disponibile a breve.</p>';
        refreshIcons(productsRoot);
        requestAnimationFrame(updateCapsuleProgress);
    } catch {
        productsRoot.innerHTML = '<p class="featured-empty">Non è stato possibile caricare i prodotti.</p>';
    }
}

productsRoot?.addEventListener('scroll', updateCapsuleProgress, { passive: true });
window.addEventListener('resize', updateCapsuleProgress);
productsRoot?.addEventListener('click', event => {
    const trigger = event.target.closest('.home-product-trigger');
    if (trigger) showProductModal(trigger.dataset.productSlug, trigger);
});
document.addEventListener('cart:added', closeProductModal);
document.addEventListener('click', event => {
    if (event.target.closest('#home-product-modal .product-modal-close, #home-product-modal-backdrop')) {
        closeProductModal();
    }
});
document.addEventListener('keydown', event => {
    if (event.key === 'Escape' && document.querySelector('#home-product-modal.open')) {
        closeProductModal();
    }
});
refreshIcons();
loadFeaturedProducts();
