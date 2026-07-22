import { api } from './api.js?v=20260720.5';
import { addToCart, normalizeProductQuantity } from './cart.js?v=20260720.10';
import { notify } from './ui.js?v=20260722.3';

const slug = new URLSearchParams(location.search).get('slug');

if (slug) api(`/catalog/products/${encodeURIComponent(slug)}`).then(({ data }) => {
    document.title = `${data.name} · Il Paradiso della Frutta`;
    const unit = data.unit_of_measure?.symbol || 'u.';
    const minimum = Number(data.minimum_quantity || 1);
    const unitPrice = data.price_per_unit ?? data.price_per_kg;
    const price = Number(unitPrice).toLocaleString('it-IT', { style: 'currency', currency: 'EUR' });
    document.querySelector('#product-detail').innerHTML = `<div class="hero-card reveal"><div class="product-image">${data.image_url ? `<img src="${data.image_url}" alt="">` : '🥬'}</div></div><div class="reveal"><span class="eyebrow">${data.category?.name || 'Catalogo'}</span><h1>${data.name}</h1><p class="lead">${data.description || 'Prodotto selezionato da Il Paradiso della Frutta.'}</p><div class="price-row"><strong class="price">${price}<small>/${unit}</small></strong>${data.has_personalized_price ? '<span class="badge">Il tuo prezzo</span>' : ''}</div><div class="hero-actions"><label>Quantità (${unit}) · minimo ${minimum}<input id="quantity" class="quantity" type="number" step="${minimum}" min="${minimum}" data-unit="${unit}" inputmode="decimal" value="${minimum}"></label><button id="add-product" class="btn btn-primary">Aggiungi al carrello</button></div></div>`;
    document.querySelector('#add-product').addEventListener('click', async event => {
        normalizeProductQuantity(document.querySelector('#quantity'), true);
        event.currentTarget.classList.add('is-loading');
        event.currentTarget.disabled = true;
        await addToCart({ id: data.id, name: data.name, slug: data.slug, price_per_kg: unitPrice, image_url: data.image_url, minimum_quantity: minimum, unit }, document.querySelector('#quantity').value);
        event.currentTarget.classList.remove('is-loading');
        event.currentTarget.disabled = false;
    });
}).catch(error => notify(error.message, 'error'));
