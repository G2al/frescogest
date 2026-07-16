import { api } from './api.js';
import { notify, productCard, refreshIcons, skeletonCards } from './ui.js';

const categoriesRoot = document.querySelector('#categories');
const productsRoot = document.querySelector('#products');
const searchInput = document.querySelector('#product-search');
const seasonalButton = document.querySelector('#seasonal-filter');
const titleRoot = document.querySelector('#catalog-title');
const countRoot = document.querySelector('#product-count');
let categories = [];
let requestId = 0;
let modalRequestId = 0;
let modalTrigger;

const state = {
    category: new URLSearchParams(location.search).get('category') || '',
    search: new URLSearchParams(location.search).get('search') || '',
    seasonal: new URLSearchParams(location.search).get('seasonal') === '1',
};

const categoryIcons = {
    Frutta: 'apple',
    Verdura: 'salad',
    Latticini: 'milk',
    'Prodotti campani': 'map-pinned',
    'Prodotti confezionati': 'package',
};

function ensureProductModal() {
    if (document.querySelector('#product-modal')) return;
    document.body.insertAdjacentHTML('beforeend', `
        <div id="product-modal-backdrop" class="product-modal-backdrop" aria-hidden="true"></div>
        <section id="product-modal" class="product-modal" role="dialog" aria-modal="true" aria-labelledby="product-modal-title" aria-hidden="true">
            <div class="product-modal-panel">
                <button class="product-modal-close" type="button" aria-label="Chiudi dettaglio prodotto"><i data-lucide="x"></i></button>
                <div id="product-modal-content" class="product-modal-content"></div>
            </div>
        </section>
    `);
    refreshIcons(document.querySelector('#product-modal'));
}

function openProductModal() {
    ensureProductModal();
    const modal = document.querySelector('#product-modal');
    const backdrop = document.querySelector('#product-modal-backdrop');
    modal.classList.add('open');
    backdrop.classList.add('open');
    modal.setAttribute('aria-hidden', 'false');
    backdrop.setAttribute('aria-hidden', 'false');
    document.body.classList.add('product-modal-open');
    modal.querySelector('.product-modal-close')?.focus();
}

function closeProductModal(restoreFocus = true) {
    modalRequestId++;
    const modal = document.querySelector('#product-modal');
    const backdrop = document.querySelector('#product-modal-backdrop');
    modal?.classList.remove('open');
    backdrop?.classList.remove('open');
    modal?.setAttribute('aria-hidden', 'true');
    backdrop?.setAttribute('aria-hidden', 'true');
    document.body.classList.remove('product-modal-open');
    if (restoreFocus) modalTrigger?.focus();
}

async function showProductModal(slug, trigger) {
    modalTrigger = trigger;
    openProductModal();
    const currentRequest = ++modalRequestId;
    const content = document.querySelector('#product-modal-content');
    content.innerHTML = '<div class="product-modal-skeleton skeleton"></div><div class="product-modal-loading"><div class="skeleton skeleton-line short"></div><div class="skeleton skeleton-line"></div><div class="skeleton skeleton-line"></div><div class="skeleton skeleton-line short"></div></div>';

    try {
        const { data: product } = await api(`/catalog/products/${encodeURIComponent(slug)}`);
        if (currentRequest !== modalRequestId) return;
        const price = Number(product.price_per_kg).toLocaleString('it-IT', { style: 'currency', currency: 'EUR' });
        const image = product.image_url
            ? `<img src="${product.image_url}" alt="${product.name}">`
            : '<span class="product-modal-placeholder"><i data-lucide="image"></i></span>';
        content.innerHTML = `
            <div class="product-modal-media">${image}${product.is_seasonal ? '<span class="seasonal-badge"><i data-lucide="sparkles"></i>Stagionale</span>' : ''}</div>
            <div class="product-modal-copy">
                <span class="eyebrow">${product.category?.name || 'Catalogo'}</span>
                <h2 id="product-modal-title">${product.name}</h2>
                <p>${product.description || 'Prodotto selezionato da Frescogest.'}</p>
                <div class="product-modal-facts"><span><i data-lucide="scale"></i>Venduto al kg</span><span><i data-lucide="badge-check"></i>Qualità selezionata</span></div>
                <div class="product-modal-price"><strong>${price}<small>/kg</small></strong>${product.has_personalized_price ? '<span class="badge">Il tuo prezzo</span>' : ''}</div>
                <div class="product-modal-purchase">
                    <label>Quantità in kg</label>
                    <div class="product-modal-controls">
                        <div class="quantity-stepper" aria-label="Quantità in chilogrammi"><button class="qty-step" type="button" data-step="-1" aria-label="Diminuisci quantità"><i data-lucide="minus"></i></button><input class="card-quantity" type="number" step="1" inputmode="decimal" value="1" aria-label="Quantità in kg"><button class="qty-step" type="button" data-step="1" aria-label="Aumenta quantità"><i data-lucide="plus"></i></button></div>
                        <button class="add-cart catalog-add-button" type="button" data-id="${product.id}" data-name="${product.name}" data-slug="${product.slug}" data-price="${product.price_per_kg}" data-image="${product.image_url || ''}"><i data-lucide="shopping-cart"></i>Aggiungi al carrello</button>
                    </div>
                </div>
            </div>`;
        refreshIcons(content);
    } catch (error) {
        if (currentRequest !== modalRequestId) return;
        content.innerHTML = '<div class="empty product-modal-error">Impossibile caricare il prodotto.</div>';
        notify(error.message);
    }
}

function renderCategories() {
    const tabs = [{ name: 'Tutti', slug: '' }, ...categories];
    categoriesRoot.innerHTML = tabs.map(category => `<button class="category-tab ${state.category === category.slug ? 'active' : ''}" type="button" data-category="${category.slug}"><i data-lucide="${category.slug ? (categoryIcons[category.name] || 'circle-dot') : 'layout-grid'}"></i>${category.name}</button>`).join('');
    refreshIcons(categoriesRoot);
    const selected = categories.find(category => category.slug === state.category);
    titleRoot.textContent = selected ? selected.name : 'Tutti i prodotti';
}

function updateUrl() {
    const params = new URLSearchParams();
    if (state.category) params.set('category', state.category);
    if (state.search) params.set('search', state.search);
    if (state.seasonal) params.set('seasonal', '1');
    history.replaceState({}, '', `${location.pathname}${params.size ? `?${params}` : ''}`);
}

async function loadProducts() {
    const currentRequest = ++requestId;
    productsRoot.innerHTML = skeletonCards(8);
    countRoot.textContent = 'Caricamento…';
    const params = new URLSearchParams();
    if (state.category) params.set('category', state.category);
    if (state.search) params.set('search', state.search);
    if (state.seasonal) params.set('seasonal', '1');

    try {
        const payload = await api(`/catalog/products${params.size ? `?${params}` : ''}`);
        if (currentRequest !== requestId) return;
        productsRoot.innerHTML = payload.data.length
            ? payload.data.map(productCard).join('')
            : '<div class="empty catalog-empty">Nessun prodotto corrisponde alla ricerca.</div>';
        refreshIcons(productsRoot);
        const total = payload.meta?.total ?? payload.data.length;
        countRoot.textContent = `${total} ${total === 1 ? 'prodotto' : 'prodotti'}`;
    } catch (error) {
        if (currentRequest !== requestId) return;
        productsRoot.innerHTML = '<div class="empty catalog-empty">Impossibile caricare il catalogo.</div>';
        countRoot.textContent = '';
        notify(error.message);
    }
}

async function initialize() {
    if (!productsRoot) return;
    refreshIcons();
    productsRoot.innerHTML = skeletonCards(8);
    searchInput.value = state.search;
    seasonalButton.classList.toggle('active', state.seasonal);
    seasonalButton.setAttribute('aria-pressed', String(state.seasonal));

    try {
        categories = (await api('/catalog/categories')).data;
        renderCategories();
        await loadProducts();
    } catch (error) {
        notify(error.message);
    }
}

categoriesRoot?.addEventListener('click', event => {
    const tab = event.target.closest('.category-tab');
    if (!tab) return;
    state.category = tab.dataset.category;
    renderCategories();
    updateUrl();
    loadProducts();
});

let searchTimer;
searchInput?.addEventListener('input', event => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => {
        state.search = event.target.value.trim();
        updateUrl();
        loadProducts();
    }, 300);
});

seasonalButton?.addEventListener('click', () => {
    state.seasonal = !state.seasonal;
    seasonalButton.classList.toggle('active', state.seasonal);
    seasonalButton.setAttribute('aria-pressed', String(state.seasonal));
    updateUrl();
    loadProducts();
});

productsRoot?.addEventListener('click', event => {
    const trigger = event.target.closest('.product-modal-trigger');
    if (!trigger || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;
    event.preventDefault();
    showProductModal(trigger.dataset.productSlug, trigger);
});

document.addEventListener('click', event => {
    if (event.target.closest('.product-modal-close, #product-modal-backdrop')) closeProductModal();
});

document.addEventListener('keydown', event => {
    if (event.key === 'Escape' && document.querySelector('#product-modal.open')) closeProductModal();
});

document.addEventListener('cart:added', () => closeProductModal(false));

initialize();
