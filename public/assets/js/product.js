import { api } from './api.js?v=20260720.5';
import { addToCart, normalizeProductQuantity } from './cart.js?v=20260723.3';
import { notify, refreshIcons, variantPickerMarkup } from './ui.js?v=20260723.5';

const slug = new URLSearchParams(location.search).get('slug');

if (slug) api(`/catalog/products/${encodeURIComponent(slug)}`).then(({ data }) => {
    document.title = `${data.name} · Cerino Store`;
    const minimum = Number(data.minimum_quantity || 1);
    const unitPrice = data.price_per_unit ?? data.price_per_kg;
    const price = Number(unitPrice).toLocaleString('it-IT', { style: 'currency', currency: 'EUR' });
    const variants = Array.isArray(data.variants) ? data.variants : [];
    const variantPicker = variantPickerMarkup(variants, 'variant-picker-detail');
    document.querySelector('#product-detail').innerHTML = `
        <div class="hero-card reveal"><div class="product-image">${data.image_url ? `<img src="${data.image_url}" alt="${data.name}">` : '<span class="product-placeholder"><i data-lucide="shirt"></i><small>Cerino Store</small></span>'}</div></div>
        <div class="reveal">
            <span class="eyebrow">${data.brand || data.category?.name || 'Cerino Store'}</span>
            <h1>${data.name}</h1>
            <p class="lead">${data.description || 'Un capo selezionato per completare il tuo stile.'}</p>
            <div class="price-row"><strong class="price">${price}</strong></div>
            <div class="hero-actions">
                ${variantPicker}
                <label>Quantità<input id="quantity" class="quantity" type="number" step="${minimum}" min="${minimum}" inputmode="numeric" value="${minimum}"></label>
                <button id="add-product" class="btn btn-primary">Aggiungi al carrello</button>
            </div>
        </div>`;
    refreshIcons(document.querySelector('#product-detail'));

    document.querySelector('#add-product').addEventListener('click', async event => {
        const quantity = document.querySelector('#quantity');
        const variant = document.querySelector('.card-variant');
        normalizeProductQuantity(quantity, true);
        event.currentTarget.classList.add('is-loading');
        event.currentTarget.disabled = true;
        await addToCart({
            id: data.id,
            name: data.name,
            slug: data.slug,
            price_per_kg: unitPrice,
            image_url: data.image_url,
            minimum_quantity: minimum,
            unit: 'pz',
            product_variant_id: variant?.value,
            variant_label: variant?.selectedOptions[0]?.textContent || '',
        }, quantity.value);
        event.currentTarget.classList.remove('is-loading');
        event.currentTarget.disabled = false;
    });
}).catch(error => notify(error.message, 'error'));
