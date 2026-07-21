import { api } from './api.js?v=20260720.5';
import { notify, productCard, refreshIcons, skeletonCards } from './ui.js?v=20260721.5';

const categoriesRoot = document.querySelector('#categories');
const previousCategoriesButton = document.querySelector('#categories-previous');
const nextCategoriesButton = document.querySelector('#categories-next');
const productsRoot = document.querySelector('#products');
const searchInput = document.querySelector('#product-search');
const searchButton = document.querySelector('#catalog-search-button');
const seasonalButton = document.querySelector('#seasonal-filter');
const filterForm = document.querySelector('#catalog-filter-form');
const filterPanel = document.querySelector('#catalog-filters');
const filterBackdrop = document.querySelector('#catalog-filter-backdrop');
const openFiltersButton = document.querySelector('#open-catalog-filters');
const closeFiltersButton = document.querySelector('#close-catalog-filters');
const resetFiltersButton = document.querySelector('#reset-catalog-filters');
const filterCategory = document.querySelector('#filter-category');
const filterMinPrice = document.querySelector('#filter-min-price');
const filterMaxPrice = document.querySelector('#filter-max-price');
const unitFiltersRoot = document.querySelector('#unit-filters');
const priceRangeRoot = document.querySelector('#catalog-price-range');
const activeFilterCount = document.querySelector('#active-filter-count');
const sortSelect = document.querySelector('#catalog-sort');
const titleRoot = document.querySelector('#catalog-title');
const countRoot = document.querySelector('#product-count');
const paginationRoot = document.querySelector('#catalog-pagination');
const previousProductsButton = document.querySelector('#products-prev');
const nextProductsButton = document.querySelector('#products-next');
let categories = [];
let requestId = 0;
let modalRequestId = 0;
let modalTrigger;
let categoryDragStartX = 0;
let categoryDragScrollLeft = 0;
let categoryDragged = false;

const state = {
    category: new URLSearchParams(location.search).get('category') || '',
    search: new URLSearchParams(location.search).get('search') || '',
    seasonal: new URLSearchParams(location.search).get('seasonal') === '1',
    unit: new URLSearchParams(location.search).get('unit') || '',
    minPrice: new URLSearchParams(location.search).get('min_price') || '',
    maxPrice: new URLSearchParams(location.search).get('max_price') || '',
    sort: new URLSearchParams(location.search).get('sort') || 'relevant',
    page: Math.max(Number.parseInt(new URLSearchParams(location.search).get('page') || '1', 10) || 1, 1),
};

const categoryIcons = {
    Frutta: 'apple',
    Verdura: 'salad',
    Latticini: 'milk',
    'Prodotti campani': 'map-pinned',
    'Prodotti confezionati': 'package',
    'Frutta secca': 'nut',
    Legumi: 'bean',
    'Spezie ed erbe': 'sprout',
};

function escapeHtml(value) {
    const node = document.createElement('span');
    node.textContent = String(value ?? '');
    return node.innerHTML;
}

function safeColor(value) {
    return /^#[0-9a-f]{6}$/i.test(String(value)) ? value : '#eaf6ee';
}

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
    const totalProducts = categories.reduce((total, category) => total + Number(category.products_count || 0), 0);
    const tabs = [{ name: 'Tutti', slug: '', catalog_color: '#e8f5ec', products_count: totalProducts }, ...categories];
    categoriesRoot.innerHTML = tabs.map(category => {
        const name = escapeHtml(category.name);
        const slug = escapeHtml(category.slug);
        const image = category.image_url
            ? `<img src="${escapeHtml(category.image_url)}" alt="" loading="lazy">`
            : `<span class="category-tab-fallback"><i data-lucide="${category.slug ? (categoryIcons[category.name] || 'leaf') : 'layout-grid'}"></i></span>`;
        const productCount = Number(category.products_count || 0);

        return `<button class="category-tab ${state.category === category.slug ? 'active' : ''}" style="--category-color:${safeColor(category.catalog_color)}" type="button" data-category="${slug}" aria-pressed="${state.category === category.slug}"><span class="category-tab-copy"><i data-lucide="${category.slug ? (categoryIcons[category.name] || 'leaf') : 'layout-grid'}"></i><strong>${name}</strong><small>${productCount} ${productCount === 1 ? 'prodotto' : 'prodotti'}</small></span><span class="category-tab-media">${image}</span></button>`;
    }).join('');
    filterCategory.innerHTML = tabs.map(category => `<option value="${escapeHtml(category.slug)}" ${state.category === category.slug ? 'selected' : ''}>${category.slug ? escapeHtml(category.name) : 'Tutte le categorie'}</option>`).join('');
    refreshIcons(categoriesRoot);
    const selected = categories.find(category => category.slug === state.category);
    titleRoot.textContent = selected ? selected.name : 'Tutti i prodotti';
    syncHeaderCategoryState();
    requestAnimationFrame(() => {
        categoriesRoot.querySelector('.category-tab.active')?.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
        updateCategoryCarouselControls();
    });
}

function syncHeaderCategoryState() {
    const catalogRootLink = document.querySelector('[data-catalog-root]');
    const categoryLinks = document.querySelectorAll('.catalog-category-link[data-category]');
    catalogRootLink?.classList.toggle('active', !state.category);
    if (catalogRootLink) {
        if (state.category) catalogRootLink.removeAttribute('aria-current');
        else catalogRootLink.setAttribute('aria-current', 'page');
    }
    categoryLinks.forEach(link => {
        const active = link.dataset.category === state.category;
        link.classList.toggle('active', active);
        if (active) link.setAttribute('aria-current', 'page');
        else link.removeAttribute('aria-current');
    });
}

function updateCategoryCarouselControls() {
    if (!categoriesRoot) return;
    const maximum = Math.max(0, categoriesRoot.scrollWidth - categoriesRoot.clientWidth);
    previousCategoriesButton?.toggleAttribute('disabled', categoriesRoot.scrollLeft <= 2);
    nextCategoriesButton?.toggleAttribute('disabled', categoriesRoot.scrollLeft >= maximum - 2);
}

function scrollCategories(direction) {
    categoriesRoot?.scrollBy({
        left: direction * Math.max(220, categoriesRoot.clientWidth * .72),
        behavior: 'smooth',
    });
}

function renderFilterOptions(filters) {
    const units = filters.units || [];
    unitFiltersRoot.innerHTML = [
        { id: '', name: 'Tutti', symbol: '', products_count: null },
        ...units,
    ].map(unit => `<label class="unit-filter-option"><input type="radio" name="unit" value="${unit.id}" ${String(unit.id) === state.unit ? 'checked' : ''}><span>${unit.symbol ? `<strong>${unit.symbol}</strong><small>${unit.name}</small>` : '<strong>Tutti</strong><small>Ogni formato</small>'}</span></label>`).join('');

    const minimum = Number(filters.price?.min || 0);
    const maximum = Number(filters.price?.max || 0);
    priceRangeRoot.textContent = maximum > 0
        ? `Prezzi disponibili da ${minimum.toLocaleString('it-IT', { style: 'currency', currency: 'EUR' })} a ${maximum.toLocaleString('it-IT', { style: 'currency', currency: 'EUR' })}`
        : '';
    filterMinPrice.placeholder = minimum ? String(minimum) : '0';
    filterMaxPrice.placeholder = maximum ? String(maximum) : 'Qualsiasi';
    refreshIcons(filterPanel);
}

function syncFilterControls() {
    filterCategory.value = state.category;
    filterMinPrice.value = state.minPrice;
    filterMaxPrice.value = state.maxPrice;
    seasonalButton.checked = state.seasonal;
    sortSelect.value = state.sort;
    const selectedUnit = unitFiltersRoot.querySelector(`[name="unit"][value="${CSS.escape(state.unit)}"]`);
    if (selectedUnit) selectedUnit.checked = true;
    updateActiveFilterCount();
}

function updateActiveFilterCount() {
    const count = [state.category, state.unit, state.minPrice, state.maxPrice, state.seasonal ? '1' : ''].filter(Boolean).length;
    activeFilterCount.textContent = String(count);
    activeFilterCount.classList.toggle('hidden', count === 0);
    openFiltersButton.classList.toggle('active', count > 0);
}

function openFilters() {
    filterPanel.classList.add('open');
    filterBackdrop.classList.add('open');
    filterBackdrop.setAttribute('aria-hidden', 'false');
    openFiltersButton.setAttribute('aria-expanded', 'true');
    document.body.classList.add('catalog-filters-open');
    filterPanel.querySelector('select, input, button')?.focus();
}

function closeFilters() {
    filterPanel.classList.remove('open');
    filterBackdrop.classList.remove('open');
    filterBackdrop.setAttribute('aria-hidden', 'true');
    openFiltersButton.setAttribute('aria-expanded', 'false');
    document.body.classList.remove('catalog-filters-open');
}

function updateUrl() {
    const params = new URLSearchParams();
    if (state.category) params.set('category', state.category);
    if (state.search) params.set('search', state.search);
    if (state.seasonal) params.set('seasonal', '1');
    if (state.unit) params.set('unit', state.unit);
    if (state.minPrice) params.set('min_price', state.minPrice);
    if (state.maxPrice) params.set('max_price', state.maxPrice);
    if (state.sort !== 'relevant') params.set('sort', state.sort);
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
    if (state.unit) params.set('unit', state.unit);
    if (state.minPrice) params.set('min_price', state.minPrice);
    if (state.maxPrice) params.set('max_price', state.maxPrice);
    if (state.sort !== 'relevant') params.set('sort', state.sort);
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
    sortSelect.value = state.sort;

    try {
        const [categoryPayload, filterPayload] = await Promise.all([
            api('/catalog/categories'),
            api('/catalog/filters'),
        ]);
        categories = categoryPayload.data;
        renderCategories();
        renderFilterOptions(filterPayload.data);
        syncFilterControls();
        await loadProducts();
    } catch (error) {
        notify(error.message, 'error');
    }
}

categoriesRoot?.addEventListener('click', event => {
    if (categoryDragged) {
        event.preventDefault();
        event.stopPropagation();
        categoryDragged = false;
        return;
    }
    const tab = event.target.closest('.category-tab');
    if (!tab) return;
    state.category = tab.dataset.category;
    state.page = 1;
    renderCategories();
    syncFilterControls();
    updateUrl();
    loadProducts();
});

previousCategoriesButton?.addEventListener('click', () => scrollCategories(-1));
nextCategoriesButton?.addEventListener('click', () => scrollCategories(1));
categoriesRoot?.addEventListener('scroll', updateCategoryCarouselControls, { passive: true });
window.addEventListener('resize', updateCategoryCarouselControls);

categoriesRoot?.addEventListener('pointerdown', event => {
    if (event.pointerType !== 'mouse' || event.button !== 0) return;
    categoryDragStartX = event.clientX;
    categoryDragScrollLeft = categoriesRoot.scrollLeft;
    categoryDragged = false;
    categoriesRoot.setPointerCapture(event.pointerId);
    categoriesRoot.classList.add('is-grabbing');
});

categoriesRoot?.addEventListener('pointermove', event => {
    if (!categoriesRoot.hasPointerCapture(event.pointerId)) return;
    const distance = event.clientX - categoryDragStartX;
    if (Math.abs(distance) > 5) categoryDragged = true;
    if (!categoryDragged) return;
    categoriesRoot.scrollLeft = categoryDragScrollLeft - distance;
});

const finishCategoryDrag = event => {
    if (!categoriesRoot?.hasPointerCapture(event.pointerId)) return;
    categoriesRoot.releasePointerCapture(event.pointerId);
    categoriesRoot.classList.remove('is-grabbing');
    updateCategoryCarouselControls();
};

categoriesRoot?.addEventListener('pointerup', finishCategoryDrag);
categoriesRoot?.addEventListener('pointercancel', finishCategoryDrag);

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

searchButton?.addEventListener('click', () => {
    state.search = searchInput.value.trim();
    state.page = 1;
    updateUrl();
    loadProducts();
});

searchInput?.addEventListener('keydown', event => {
    if (event.key === 'Enter') searchButton.click();
});

filterForm?.addEventListener('submit', event => {
    event.preventDefault();
    const minimum = filterMinPrice.value.trim();
    const maximum = filterMaxPrice.value.trim();

    if (minimum && maximum && Number(maximum) < Number(minimum)) {
        notify('Il prezzo massimo deve essere maggiore o uguale al prezzo minimo.', 'warning');
        filterMaxPrice.focus();
        return;
    }

    state.category = filterCategory.value;
    state.unit = filterForm.querySelector('[name="unit"]:checked')?.value || '';
    state.minPrice = minimum;
    state.maxPrice = maximum;
    state.seasonal = seasonalButton.checked;
    state.page = 1;
    renderCategories();
    syncFilterControls();
    updateUrl();
    closeFilters();
    loadProducts();
});

resetFiltersButton?.addEventListener('click', () => {
    Object.assign(state, {
        category: '',
        search: '',
        seasonal: false,
        unit: '',
        minPrice: '',
        maxPrice: '',
        sort: 'relevant',
        page: 1,
    });
    searchInput.value = '';
    renderCategories();
    syncFilterControls();
    updateUrl();
    loadProducts();
});

sortSelect?.addEventListener('change', event => {
    state.sort = event.target.value;
    state.page = 1;
    updateUrl();
    loadProducts();
});

openFiltersButton?.addEventListener('click', openFilters);
closeFiltersButton?.addEventListener('click', closeFilters);
filterBackdrop?.addEventListener('click', closeFilters);

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
    if (event.key === 'Escape' && filterPanel.classList.contains('open')) closeFilters();
});

document.addEventListener('cart:added', () => closeProductModal(false));

initialize();
