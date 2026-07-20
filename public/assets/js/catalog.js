import { api } from './api.js?v=20260720.5';
import { notify, productCard, refreshIcons, skeletonCards } from './ui.js?v=20260720.7';

const categoriesRoot = document.querySelector('#categories');
const productsRoot = document.querySelector('#products');
const searchInput = document.querySelector('#product-search');
const seasonalButton = document.querySelector('#seasonal-filter');
const titleRoot = document.querySelector('#catalog-title');
const countRoot = document.querySelector('#product-count');
const paginationRoot = document.querySelector('#catalog-pagination');
const previousProductsButton = document.querySelector('#products-prev');
const nextProductsButton = document.querySelector('#products-next');
let categories = [];
let requestId = 0;
let modalRequestId = 0;
let modalTrigger;

const state = {
    category: new URLSearchParams(location.search).get('category') || '',
    search: new URLSearchParams(location.search).get('search') || '',
    seasonal: new URLSearchParams(location.search).get('seasonal') === '1',
    page: Math.max(Number.parseInt(new URLSearchParams(location.search).get('page') || '1', 10) || 1, 1),
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
        const unitPrice = Number(product.price_per_unit ?? product.price_per_kg);
        const price = unitPrice.toLocaleString('it-IT', { style: 'currency', currency: 'EUR' });
        const unit = product.unit_of_measure?.symbol || 'u.';
        const minimum = Number(product.minimum_quantity || 1);
        const total = (unitPrice * minimum).toLocaleString('it-IT', { style: 'currency', currency: 'EUR' });
        const image = product.image_url
            ? `<img src="${product.image_url}" alt="${product.name}">`
            : '<span class="product-modal-placeholder"><i data-lucide="image"></i></span>';
        content.innerHTML = `
            <div class="product-modal-media">${image}${product.is_seasonal ? '<span class="seasonal-badge"><i data-lucide="sparkles"></i>Stagionale</span>' : ''}</div>
            <div class="product-modal-copy">
                <span class="eyebrow">${product.category?.name || 'Catalogo'}</span>
                <h2 id="product-modal-title">${product.name}</h2>
                <p>${product.description || 'Prodotto selezionato da Il Paradiso della Frutta.'}</p>
                <div class="product-modal-facts"><span><i data-lucide="scale"></i>Venduto al kg</span><span><i data-lucide="badge-check"></i>Qualità selezionata</span></div>
                <div class="product-modal-price"><div class="product-price-summary"><strong>${price}<small>/kg</small></strong><span class="product-total-preview" aria-live="polite">Totale: ${total}</span></div>${product.has_personalized_price ? '<span class="badge">Il tuo prezzo</span>' : ''}</div>
                <div class="product-modal-purchase">
                    <label>Quantità in kg</label>
                    <div class="product-modal-controls">
                        <div class="quantity-stepper" aria-label="Quantità in chilogrammi"><button class="qty-step" type="button" data-step="-1" aria-label="Diminuisci quantità"><i data-lucide="minus"></i></button><input class="card-quantity" type="number" step="1" inputmode="decimal" value="1" aria-label="Quantità in kg"><button class="qty-step" type="button" data-step="1" aria-label="Aumenta quantità"><i data-lucide="plus"></i></button></div>
                        <button class="add-cart catalog-add-button" type="button" data-id="${product.id}" data-name="${product.name}" data-slug="${product.slug}" data-price="${product.price_per_kg}" data-image="${product.image_url || ''}"><i data-lucide="shopping-cart"></i>Aggiungi al carrello</button>
                    </div>
                </div>
            </div>`;
        const addButton = content.querySelector('.add-cart');
        const quantityInput = content.querySelector('.card-quantity');
        if (addButton) {
            addButton.dataset.price = product.price_per_unit ?? product.price_per_kg;
            addButton.dataset.minimum = String(minimum);
            addButton.dataset.unit = unit;
        }
        if (quantityInput) {
            quantityInput.min = String(minimum);
            quantityInput.step = String(minimum);
            quantityInput.value = String(minimum);
            quantityInput.closest('.quantity-stepper')?.querySelectorAll('.qty-step').forEach(step => {
                step.dataset.step = String((Number(step.dataset.step) < 0 ? -1 : 1) * minimum);
            });
        }
        const quantityLabel = content.querySelector('.product-modal-purchase > label');
        if (quantityLabel) quantityLabel.textContent = `Quantità in ${unit} · minimo ${minimum}`;
        const priceSuffix = content.querySelector('.product-modal-price small');
        if (priceSuffix) priceSuffix.textContent = `/${unit}`;
        const saleFact = content.querySelector('.product-modal-facts span');
        if (saleFact) saleFact.lastChild.textContent = `Venduto a ${unit}`;
        refreshIcons(content);
    } catch (error) {
        if (currentRequest !== modalRequestId) return;
        content.innerHTML = '<div class="empty product-modal-error">Impossibile caricare il prodotto.</div>';
        notify(error.message, 'error');
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
    if (state.page > 1) params.set('page', String(state.page));
    history.replaceState({}, '', `${location.pathname}${params.size ? `?${params}` : ''}`);
}

function pageNumbers(currentPage, lastPage) {
    const windowSize = 7;
    let start = Math.max(1, currentPage - Math.floor(windowSize / 2));
    let end = Math.min(lastPage, start + windowSize - 1);
    start = Math.max(1, end - windowSize + 1);
    const visible = Array.from({ length: end - start + 1 }, (_, index) => start + index);

    if (start > 1) {
        visible.unshift(...(start > 2 ? [1, 'ellipsis'] : [1]));
    }

    if (end < lastPage) {
        visible.push(...(end < lastPage - 1 ? ['ellipsis', lastPage] : [lastPage]));
    }

    return visible;
}

function renderPagination(meta) {
    const currentPage = meta?.current_page ?? 1;
    const lastPage = meta?.last_page ?? 1;
    const hasPrevious = currentPage > 1;
    const hasNext = currentPage < lastPage;

    previousProductsButton.disabled = !hasPrevious;
    nextProductsButton.disabled = !hasNext;

    if (lastPage <= 1) {
        paginationRoot.classList.add('hidden');
        paginationRoot.innerHTML = '';
        return;
    }

    paginationRoot.classList.remove('hidden');
    paginationRoot.innerHTML = `
        <button class="pagination-direction" type="button" data-page="${currentPage - 1}" ${hasPrevious ? '' : 'disabled'}>
            <i data-lucide="chevron-left"></i><span>Precedente</span>
        </button>
        <div class="pagination-pages">
            ${pageNumbers(currentPage, lastPage).map(page => page === 'ellipsis'
                ? '<span class="pagination-ellipsis" aria-hidden="true">…</span>'
                : `<button class="pagination-page ${page === currentPage ? 'active' : ''}" type="button" data-page="${page}" ${page === currentPage ? 'aria-current="page"' : ''}>${page}</button>`).join('')}
        </div>
        <button class="pagination-direction" type="button" data-page="${currentPage + 1}" ${hasNext ? '' : 'disabled'}>
            <span>Successivo</span><i data-lucide="chevron-right"></i>
        </button>`;
    refreshIcons(paginationRoot);
}

async function loadProducts() {
    const currentRequest = ++requestId;
    productsRoot.innerHTML = skeletonCards(8);
    countRoot.textContent = 'Caricamento…';
    const params = new URLSearchParams();
    if (state.category) params.set('category', state.category);
    if (state.search) params.set('search', state.search);
    if (state.seasonal) params.set('seasonal', '1');
    params.set('page', String(state.page));

    try {
        const payload = await api(`/catalog/products${params.size ? `?${params}` : ''}`);
        if (currentRequest !== requestId) return;
        productsRoot.innerHTML = payload.data.length
            ? payload.data.map(productCard).join('')
            : '<div class="empty catalog-empty">Nessun prodotto corrisponde alla ricerca.</div>';
        refreshIcons(productsRoot);
        payload.data.forEach(product => {
            const button = productsRoot.querySelector(`.add-cart[data-id="${product.id}"]`);
            const input = button?.closest('.product-card')?.querySelector('.card-quantity');
            const minimum = Number(product.minimum_quantity || 1);
            const unit = product.unit_of_measure?.symbol || 'u.';
            if (button) {
                button.dataset.price = product.price_per_unit ?? product.price_per_kg;
                button.dataset.minimum = String(minimum);
                button.dataset.unit = unit;
                const suffix = button.closest('.product-card')?.querySelector('.price small');
                if (suffix) suffix.textContent = `/${unit}`;
            }
            if (input) {
                input.min = String(minimum);
                input.step = String(minimum);
                input.value = String(minimum);
                input.closest('.quantity-stepper')?.querySelectorAll('.qty-step').forEach(step => {
                    step.dataset.step = String((Number(step.dataset.step) < 0 ? -1 : 1) * minimum);
                });
            }
        });
        const total = payload.meta?.total ?? payload.data.length;
        const from = payload.meta?.from ?? (total ? 1 : 0);
        const to = payload.meta?.to ?? payload.data.length;
        countRoot.textContent = total > payload.data.length
            ? `${from}–${to} di ${total} prodotti`
            : `${total} ${total === 1 ? 'prodotto' : 'prodotti'}`;
        renderPagination(payload.meta);
    } catch (error) {
        if (currentRequest !== requestId) return;
        productsRoot.innerHTML = '<div class="empty catalog-empty">Impossibile caricare il catalogo.</div>';
        countRoot.textContent = '';
        paginationRoot.classList.add('hidden');
        notify(error.message, 'error');
    }
}

function changePage(page) {
    if (!Number.isInteger(page) || page < 1 || page === state.page) return;
    state.page = page;
    updateUrl();
    loadProducts().then(() => document.querySelector('#catalog-results')?.scrollIntoView({ behavior: 'smooth', block: 'start' }));
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
        notify(error.message, 'error');
    }
}

categoriesRoot?.addEventListener('click', event => {
    const tab = event.target.closest('.category-tab');
    if (!tab) return;
    state.category = tab.dataset.category;
    state.page = 1;
    renderCategories();
    updateUrl();
    loadProducts();
});

let searchTimer;
searchInput?.addEventListener('input', event => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => {
        state.search = event.target.value.trim();
        state.page = 1;
        updateUrl();
        loadProducts();
    }, 300);
});

seasonalButton?.addEventListener('click', () => {
    state.seasonal = !state.seasonal;
    state.page = 1;
    seasonalButton.classList.toggle('active', state.seasonal);
    seasonalButton.setAttribute('aria-pressed', String(state.seasonal));
    updateUrl();
    loadProducts();
});

previousProductsButton?.addEventListener('click', () => changePage(state.page - 1));
nextProductsButton?.addEventListener('click', () => changePage(state.page + 1));
paginationRoot?.addEventListener('click', event => {
    const button = event.target.closest('button[data-page]');
    if (!button || button.disabled) return;
    changePage(Number.parseInt(button.dataset.page, 10));
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
