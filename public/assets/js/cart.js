import { api, currentUser } from './api.js?v=20260720.5';
import { notify, refreshIcons } from './ui.js?v=20260722.5';
import { getStoredCart, saveStoredCart } from './cart-storage.js?v=20260720.7';

let drawerNotes = '';
let commercialTerms;
let appliedPromotion = readPromotion();

export function getCart() { return getStoredCart(); }
export function saveCart(cart) { saveStoredCart(cart); }

function currency(value) {
    return Number(value).toLocaleString('it-IT', { style: 'currency', currency: 'EUR' });
}

function readPromotion() {
    try {
        return JSON.parse(sessionStorage.getItem('appliedPromotion') || 'null');
    } catch {
        return null;
    }
}

function savePromotion(promotion) {
    appliedPromotion = promotion;

    if (promotion) {
        sessionStorage.setItem('appliedPromotion', JSON.stringify(promotion));
    } else {
        sessionStorage.removeItem('appliedPromotion');
    }
}

function discountedTotal(total) {
    const percentage = Number(appliedPromotion?.discount_percentage || 0);
    return total * (1 - percentage / 100);
}

function cartProductsTotal() {
    return getCart().reduce((sum, item) => sum + (Number(item.quantity) * Number(item.price_per_kg)), 0);
}

function promotionMarkup(context) {
    if (appliedPromotion) {
        return `<div class="promotion-applied">
            <span><i data-lucide="badge-check"></i><span><strong>${escapeHtml(appliedPromotion.code)}</strong><small>${escapeHtml(appliedPromotion.name)} · -${Number(appliedPromotion.discount_percentage).toLocaleString('it-IT')}%</small></span></span>
            <button class="promotion-remove" type="button">Rimuovi</button>
        </div>`;
    }

    return `<div class="promotion-entry">
        <label for="promotion-code-${context}"><i data-lucide="ticket-percent"></i>Hai un codice sconto?</label>
        <div><input id="promotion-code-${context}" class="promotion-code-input" type="text" maxlength="64" autocomplete="off" placeholder="Inserisci il codice"><button class="promotion-apply" type="button">Applica</button></div>
    </div>`;
}

function totalMarkup(total) {
    if (!appliedPromotion) return `<strong>${currency(total)}</strong>`;

    return `<span class="promotion-total"><del>${currency(total)}</del><strong>${currency(discountedTotal(total))}</strong></span>`;
}

function quantity(value, unit) {
    const amount = Number(value);

    if (unit === 'kg' && amount > 0 && amount < 1) {
        return `${Number((amount * 1000).toFixed(3)).toLocaleString('it-IT')} g`;
    }

    return `${Number(amount.toFixed(3)).toLocaleString('it-IT')} ${unit || 'u.'}`;
}

function updateProductTotal(input) {
    const container = input.closest('.product-card, .product-modal-panel');
    const button = container?.querySelector('.add-cart');
    const preview = container?.querySelector('.product-total-preview');
    const amount = Number(String(input.value).replace(',', '.'));
    const unitPrice = Number(button?.dataset.price);

    if (!preview || !Number.isFinite(amount) || !Number.isFinite(unitPrice)) return;

    preview.textContent = `Totale: ${currency(amount * unitPrice)}`;
    preview.classList.remove('is-updated');
    requestAnimationFrame(() => preview.classList.add('is-updated'));
}

function minimumQuantityMessage(minimum, unit = 'u.') {
    const formattedMinimum = Number(minimum).toLocaleString('it-IT', { maximumFractionDigits: 3 });
    return `La quantità minima acquistabile è ${formattedMinimum} ${unit}.`;
}

function quantityDetails(input) {
    const container = input.closest('.product-card, .product-modal-panel');
    const button = container?.querySelector('.add-cart');

    return {
        minimum: Number(input.min || button?.dataset.minimum || 1),
        unit: input.dataset.unit || button?.dataset.unit || 'u.',
    };
}

export function normalizeProductQuantity(input, showNotice = false) {
    const { minimum, unit } = quantityDetails(input);
    const value = Number(String(input.value).replace(',', '.'));
    const valid = Number.isFinite(value) && value >= minimum && value <= 99999;

    input.classList.toggle('is-invalid', !valid);
    input.setAttribute('aria-invalid', String(!valid));
    input.setCustomValidity(valid ? '' : minimumQuantityMessage(minimum, unit));
    input.closest('.quantity-stepper')?.classList.toggle('is-invalid', !valid);

    if (valid) return false;

    input.value = String(minimum);
    input.classList.remove('is-invalid');
    input.setAttribute('aria-invalid', 'false');
    input.setCustomValidity('');
    input.closest('.quantity-stepper')?.classList.remove('is-invalid');
    updateProductTotal(input);

    if (showNotice) notify(minimumQuantityMessage(minimum, unit), 'warning');

    return true;
}

function escapeHtml(value) {
    const element = document.createElement('div');
    element.textContent = value;
    return element.innerHTML;
}

function updateCartBadges() {
    document.querySelectorAll('.header-cart .badge').forEach(badge => {
        badge.textContent = getCart().length;
    });
}

function ensureCartDrawer() {
    if (document.querySelector('#cart-drawer')) return;
    document.body.insertAdjacentHTML('beforeend', `
        <div id="cart-drawer-backdrop" class="cart-drawer-backdrop" aria-hidden="true"></div>
        <aside id="cart-drawer" class="cart-drawer" role="dialog" aria-modal="true" aria-labelledby="cart-drawer-title" aria-hidden="true">
            <header class="cart-drawer-header">
                <div><span class="eyebrow">Il tuo ordine</span><h2 id="cart-drawer-title">Carrello</h2></div>
                <button class="cart-drawer-close" type="button" aria-label="Chiudi il carrello"><i data-lucide="x"></i></button>
            </header>
            <div id="cart-drawer-content" class="cart-drawer-content"></div>
            <footer id="cart-drawer-footer" class="cart-drawer-footer"></footer>
        </aside>
    `);
}

function renderCartDrawer() {
    ensureCartDrawer();
    const cart = getCart();
    const content = document.querySelector('#cart-drawer-content');
    const footer = document.querySelector('#cart-drawer-footer');

    content.innerHTML = cart.length
        ? cart.map((item, index) => `<article class="cart-drawer-item">
            <a class="cart-drawer-thumb" href="/product.html?slug=${encodeURIComponent(item.slug)}">${item.image_url ? `<img src="${item.image_url}" alt="${item.name}">` : '<span>🥬</span>'}</a>
            <div class="cart-drawer-item-copy">
                <a href="/product.html?slug=${encodeURIComponent(item.slug)}"><strong>${item.name}</strong></a>
                <span>${currency(item.price_per_kg)}/${item.unit || 'u.'}</span>
                <div class="cart-drawer-item-actions">
                    <div class="drawer-quantity" aria-label="Quantità in chilogrammi">
                        <button class="drawer-qty-step" type="button" data-index="${index}" data-step="-1" aria-label="Diminuisci quantità"><i data-lucide="minus"></i></button>
                        <span>${quantity(item.quantity, item.unit)}</span>
                        <button class="drawer-qty-step" type="button" data-index="${index}" data-step="1" aria-label="Aumenta quantità"><i data-lucide="plus"></i></button>
                    </div>
                    <button class="drawer-remove" type="button" data-index="${index}"><i data-lucide="trash-2"></i>Rimuovi</button>
                </div>
            </div>
            <strong class="cart-drawer-line-total">${currency(item.quantity * item.price_per_kg)}</strong>
        </article>`).join('')
        : '<div class="cart-drawer-empty"><span><i data-lucide="shopping-basket"></i></span><h3>Il carrello è vuoto</h3><p>Scegli i prodotti dal catalogo e aggiungili al tuo ordine.</p></div>';

    const total = cartProductsTotal();
    footer.innerHTML = cart.length
        ? `<div class="commercial-terms-slot">${commercialTermsMarkup(total)}</div>${promotionMarkup('drawer')}<label class="cart-drawer-notes"><span><i data-lucide="message-square-text"></i>Note per Antonio</span><textarea id="cart-drawer-notes" rows="2" placeholder="Preferenze o indicazioni sulla consegna">${escapeHtml(drawerNotes)}</textarea></label><div class="cart-drawer-total"><span>Totale indicativo</span>${totalMarkup(total)}</div><button class="btn cart-drawer-whatsapp" type="button"><i data-lucide="message-circle"></i>Conferma su WhatsApp</button><a class="btn cart-drawer-checkout" href="/cart.html"><i data-lucide="shopping-bag"></i>Apri il carrello completo</a><button class="cart-drawer-continue" type="button">Continua gli acquisti</button>`
        : '<button class="btn btn-primary cart-drawer-continue" type="button"><i data-lucide="arrow-left"></i>Continua nel catalogo</button>';
    updateCartBadges();
    refreshIcons(document.querySelector('#cart-drawer'));
}

function commercialTermsMarkup(total = cartProductsTotal()) {
    if (!commercialTerms) return '';

    const minimum = Number(commercialTerms.minimum_order_gross || 0);
    const freeShipping = Number(commercialTerms.free_shipping_threshold_gross || 0);
    const shippingTax = Number(commercialTerms.shipping_tax_percentage || 0);
    const shippingGross = Number(commercialTerms.shipping_fee_net) * (1 + shippingTax / 100);
    const finalTarget = freeShipping > 0 ? freeShipping : minimum;
    const progress = finalTarget > 0 ? Math.min(100, Math.max(0, total / finalTarget * 100)) : 100;
    const minimumMarker = minimum > 0 && freeShipping > minimum
        ? Math.min(100, Math.max(0, minimum / freeShipping * 100))
        : 100;
    let state = 'minimum';
    let icon = 'shopping-basket';
    let title = 'Spesa minima raggiunta';
    let detail = `Consegna ${currency(shippingGross)}`;

    if (minimum > 0 && total < minimum) {
        title = `Ci sei quasi: mancano ${currency(minimum - total)} al minimo`;
        detail = `Ordine minimo ${currency(minimum)} · consegna ${currency(shippingGross)}`;
    } else if (freeShipping > 0 && total < freeShipping) {
        state = 'shipping';
        icon = 'truck';
        title = `Mancano ${currency(freeShipping - total)} alla consegna gratuita`;
        detail = `Minimo raggiunto · consegna ${currency(shippingGross)}`;
    } else if (freeShipping > 0) {
        state = 'complete';
        icon = 'badge-check';
        title = 'Consegna gratuita raggiunta';
        detail = `Hai superato la soglia di ${currency(freeShipping)}`;
    }

    return `<div class="commercial-terms is-${state}">
        <span class="commercial-terms-icon"><i data-lucide="${icon}"></i></span>
        <div class="commercial-terms-copy">
            <small>Condizioni ordine</small>
            <strong>${title}</strong>
            <span>${detail}</span>
        </div>
        <div class="commercial-progress" role="progressbar" aria-label="${escapeHtml(title)}" aria-valuemin="0" aria-valuemax="100" aria-valuenow="${Math.round(progress)}">
            <span class="commercial-progress-fill" style="width:${progress.toFixed(2)}%"></span>
            ${minimumMarker < 100 ? `<span class="commercial-progress-marker" style="left:${minimumMarker.toFixed(2)}%" aria-hidden="true"></span>` : ''}
        </div>
    </div>`;
}

async function loadCommercialTerms() {
    if (!await currentUser()) return;

    try {
        commercialTerms = (await api('/orders/commercial-terms')).data;
        document.querySelectorAll('.commercial-terms-slot').forEach(node => { node.innerHTML = commercialTermsMarkup(cartProductsTotal()); });
        const form = document.querySelector('#order-form');
        if (form && !form.querySelector('.commercial-terms-slot')) {
            form.insertAdjacentHTML('afterbegin', `<div class="commercial-terms-slot">${commercialTermsMarkup(cartProductsTotal())}</div>`);
        }
        refreshIcons();
    } catch {}
}

export function openCartDrawer() {
    renderCartDrawer();
    const drawer = document.querySelector('#cart-drawer');
    const backdrop = document.querySelector('#cart-drawer-backdrop');
    requestAnimationFrame(() => {
        drawer.classList.add('open');
        backdrop.classList.add('open');
        drawer.setAttribute('aria-hidden', 'false');
        backdrop.setAttribute('aria-hidden', 'false');
        document.body.classList.add('cart-drawer-open');
        drawer.querySelector('.cart-drawer-close')?.focus();
    });
}

export function closeCartDrawer() {
    const drawer = document.querySelector('#cart-drawer');
    const backdrop = document.querySelector('#cart-drawer-backdrop');
    drawer?.classList.remove('open');
    backdrop?.classList.remove('open');
    drawer?.setAttribute('aria-hidden', 'true');
    backdrop?.setAttribute('aria-hidden', 'true');
    document.body.classList.remove('cart-drawer-open');
}
export async function addToCart(product, quantity = 1) {
    const parsedQuantity = Number(String(quantity).replace(',', '.'));
    const minimum = Number(product.minimum_quantity || product.minimum || 1);
    if (!Number.isFinite(parsedQuantity) || parsedQuantity < minimum || parsedQuantity > 99999) {
        notify(minimumQuantityMessage(minimum, product.unit), 'warning');
        return false;
    }
    if (!await currentUser()) {
        location.href = `/login.html?next=${encodeURIComponent(location.pathname + location.search)}`;
        return false;
    }
    const cart = getCart();
    const existing = cart.find(item => item.product_id === Number(product.id));
    if (existing) {
        existing.quantity = Number((existing.quantity + parsedQuantity).toFixed(3));
        existing.price_per_kg = Number(product.price_per_kg);
        existing.image_url = product.image_url || existing.image_url;
        existing.slug = product.slug || existing.slug;
    }
    else cart.push({ product_id: Number(product.id), name: product.name, slug: product.slug, image_url: product.image_url || null, price_per_kg: Number(product.price_per_kg), minimum_quantity: minimum, unit: product.unit || 'u.', quantity: parsedQuantity });
    saveCart(cart);
    notify('Prodotto aggiunto al carrello.', 'success');
    document.dispatchEvent(new CustomEvent('cart:added', { detail: { productId: Number(product.id) } }));
    openCartDrawer();
    return true;
}

document.addEventListener('click', async event => {
    const button = event.target.closest('.add-cart');
    if (!button) return;
    const input = button.closest('.product-card, .product-modal-panel')?.querySelector('.card-quantity');
    if (input) normalizeProductQuantity(input, true);
    const quantity = input?.value || 1;
    button.classList.add('is-loading');
    button.disabled = true;
    await addToCart({ id: button.dataset.id, name: button.dataset.name, slug: button.dataset.slug, price_per_kg: button.dataset.price, image_url: button.dataset.image, minimum_quantity: button.dataset.minimum, unit: button.dataset.unit }, quantity);
    button.classList.remove('is-loading');
    button.disabled = false;
});

document.addEventListener('click', async event => {
    const stepButton = event.target.closest('.qty-step');
    if (!stepButton) return;
    const input = stepButton.closest('.quantity-stepper')?.querySelector('.card-quantity');
    if (!input) return;
    const minimum = Number(input.min || 1);
    const current = Number(String(input.value).replace(',', '.')) || minimum;
    const step = Number(stepButton.dataset.step);
    if (step < 0 && current <= minimum) return;
    input.value = String(Math.max(minimum, Number((current + step).toFixed(3))));
    updateProductTotal(input);
});

document.addEventListener('click', async event => {
    const headerCart = event.target.closest('.header-cart');
    if (headerCart && location.pathname !== '/cart.html') {
        event.preventDefault();
        openCartDrawer();
        return;
    }

    if (event.target.closest('.cart-drawer-close, .cart-drawer-continue, #cart-drawer-backdrop')) {
        closeCartDrawer();
        return;
    }

    const quantityButton = event.target.closest('.drawer-qty-step');
    if (quantityButton) {
        const cart = getCart();
        const item = cart[Number(quantityButton.dataset.index)];
        const direction = Number(quantityButton.dataset.step) < 0 ? -1 : 1;
        const step = direction * Number(item?.minimum_quantity || 1);
        if (!item || (step < 0 && item.quantity + step <= 0)) return;
        item.quantity = Math.max(Number(item.minimum_quantity || 1), Number((item.quantity + step).toFixed(3)));
        saveCart(cart);
        renderCartDrawer();
        renderCart();
        return;
    }

    const removeButton = event.target.closest('.drawer-remove');
    if (removeButton) {
        const cart = getCart();
        cart.splice(Number(removeButton.dataset.index), 1);
        saveCart(cart);
        renderCartDrawer();
        renderCart();
        return;
    }

    const quickOrderButton = event.target.closest('.cart-drawer-whatsapp');
    if (quickOrderButton) await submitOrder(drawerNotes, quickOrderButton);

    const promotionButton = event.target.closest('.promotion-apply');
    if (promotionButton) {
        const input = promotionButton.closest('.promotion-entry')?.querySelector('.promotion-code-input');
        await applyPromotion(input, promotionButton);
    }

    if (event.target.closest('.promotion-remove')) {
        savePromotion(null);
        renderCartDrawer();
        renderCart();
        notify('Codice sconto rimosso.', 'success');
    }
});

document.addEventListener('input', event => {
    if (event.target.matches('#cart-drawer-notes')) drawerNotes = event.target.value;
    if (event.target.matches('.card-quantity, #quantity')) {
        const input = event.target;
        const { minimum } = quantityDetails(input);
        const value = Number(String(input.value).replace(',', '.'));
        const invalid = input.value !== '' && (!Number.isFinite(value) || value < minimum || value > 99999);
        input.classList.toggle('is-invalid', invalid);
        input.setAttribute('aria-invalid', String(invalid));
        input.setCustomValidity(invalid ? minimumQuantityMessage(minimum, quantityDetails(input).unit) : '');
        input.closest('.quantity-stepper')?.classList.toggle('is-invalid', invalid);
        updateProductTotal(input);
    }
});

document.addEventListener('change', event => {
    if (event.target.matches('.card-quantity, #quantity')) normalizeProductQuantity(event.target, true);
});

document.addEventListener('keydown', event => {
    if (event.key === 'Escape') closeCartDrawer();
});

async function renderCart() {
    const root = document.querySelector('#cart');
    if (!root) return;
    const cart = getCart();
    root.innerHTML = cart.length ? cart.map((item, index) => `<div class="cart-row reveal"><a class="cart-thumb" href="/product.html?slug=${encodeURIComponent(item.slug)}">${item.image_url ? `<img src="${item.image_url}" alt="">` : '<span>🥬</span>'}</a><div class="cart-product"><h3>${item.name}</h3><span>${Number(item.price_per_kg).toLocaleString('it-IT', { style: 'currency', currency: 'EUR' })}/kg</span></div><div class="cart-controls"><label>Quantità (kg)<input class="quantity cart-quantity" type="number" step="1" inputmode="decimal" value="${item.quantity}" data-index="${index}" title="Usa le frecce per variare di 1 kg oppure scrivi una quantità decimale"></label><strong class="line-total">${(item.quantity * item.price_per_kg).toLocaleString('it-IT', { style: 'currency', currency: 'EUR' })}</strong><button class="btn btn-link remove-cart" data-index="${index}">Rimuovi</button></div></div>`).join('') : '<div class="empty">Il carrello è vuoto. Esplora il catalogo per aggiungere prodotti.</div>';
    root.querySelectorAll('.cart-row').forEach((row, index) => {
        const item = cart[index];
        const unit = item.unit || 'u.';
        const minimum = Number(item.minimum_quantity || 1);
        const price = row.querySelector('.cart-product span');
        const input = row.querySelector('.cart-quantity');
        if (price) price.textContent = `${currency(item.price_per_kg)}/${unit}`;
        if (input) {
            input.min = String(minimum);
            input.step = String(minimum);
            input.title = `Quantità minima: ${minimum} ${unit}`;
            input.parentElement.childNodes[0].textContent = `Quantità (${unit})`;
        }
    });
    document.querySelector('#order-form')?.classList.toggle('hidden', !cart.length);
    const total = cartProductsTotal();
    const totalRoot = document.querySelector('#cart-total');
    if (totalRoot) totalRoot.innerHTML = totalMarkup(total);
    const commercialTermsRoot = document.querySelector('#order-form .commercial-terms-slot');
    if (commercialTermsRoot) commercialTermsRoot.innerHTML = commercialTermsMarkup(total);
    const promotionRoot = document.querySelector('#cart-promotion');
    if (promotionRoot) {
        promotionRoot.innerHTML = promotionMarkup('page');
        refreshIcons(promotionRoot);
    }
}

document.addEventListener('change', event => {
    if (!event.target.matches('.cart-quantity')) return;
    const cart = getCart();
    const quantity = Number(String(event.target.value).replace(',', '.'));
    const item = cart[Number(event.target.dataset.index)];
    if (!Number.isFinite(quantity) || quantity < Number(item?.minimum_quantity || 1) || quantity > 99999) {
        notify(minimumQuantityMessage(Number(item?.minimum_quantity || 1), item?.unit), 'warning');
        renderCart();
        return;
    }
    cart[Number(event.target.dataset.index)].quantity = quantity;
    saveCart(cart);
    renderCart();
});
document.addEventListener('click', event => {
    const button = event.target.closest('.remove-cart');
    if (!button) return;
    const cart = getCart();
    cart.splice(Number(button.dataset.index), 1);
    saveCart(cart);
    renderCart();
    updateCartBadges();
});
async function submitOrder(customerNotes, button) {
    if (button.disabled || !getCart().length) return;
    button.disabled = true;
    button.classList.add('is-loading');
    const whatsappWindow = window.open('/whatsapp.html', '_blank');

    if (!whatsappWindow) {
        notify('Consenti l’apertura delle finestre per proseguire su WhatsApp.', 'warning');
        button.disabled = false;
        button.classList.remove('is-loading');
        return;
    }

    whatsappWindow.opener = null;

    if (!await currentUser()) {
        whatsappWindow.close();
        location.href = '/login.html?next=/cart.html';
        return;
    }
    try {
        const payload = await api('/orders', { method: 'POST', body: JSON.stringify({ customer_notes: customerNotes || null, promotion_code: appliedPromotion?.code || null, items: getCart().map(({ product_id, quantity }) => ({ product_id, quantity })) }) });
        saveCart([]);
        savePromotion(null);
        drawerNotes = '';
        updateCartBadges();
        whatsappWindow.location.replace(payload.data.whatsapp_url);
    } catch (error) {
        whatsappWindow.close();
        notify(error.message, 'error');
        button.disabled = false;
        button.classList.remove('is-loading');
    }
}

async function applyPromotion(input, button) {
    const code = input?.value.trim();

    if (!code) {
        notify('Inserisci un codice sconto.', 'warning');
        input?.focus();
        return;
    }

    button.disabled = true;
    button.classList.add('is-loading');

    try {
        const response = await api('/promotions/validate', {
            method: 'POST',
            body: JSON.stringify({ code }),
        });
        savePromotion(response.data);
        renderCartDrawer();
        renderCart();
        notify(response.message, 'success');
    } catch (error) {
        notify(error.message, 'error');
        input?.focus();
    } finally {
        button.disabled = false;
        button.classList.remove('is-loading');
    }
}

document.querySelector('#order-form')?.addEventListener('submit', async event => {
    event.preventDefault();
    await submitOrder(event.target.customer_notes.value, event.target.querySelector('button[type=submit]'));
});
renderCart();
loadCommercialTerms();
