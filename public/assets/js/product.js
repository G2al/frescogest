import { api } from './api.js?v=20260717.8';
import { addToCart } from './cart.js?v=20260717.8';
import { notify } from './ui.js?v=20260717.8';

const slug = new URLSearchParams(location.search).get('slug');
if (slug) api(`/catalog/products/${encodeURIComponent(slug)}`).then(({ data }) => {
    document.title = `${data.name} · FrescoGest`;
    const price = Number(data.price_per_kg).toLocaleString('it-IT', { style: 'currency', currency: 'EUR' });
    document.querySelector('#product-detail').innerHTML = `<div class="hero-card reveal"><div class="product-image">${data.image_url ? `<img src="${data.image_url}" alt="">` : '🥬'}</div></div><div class="reveal"><span class="eyebrow">${data.category?.name || 'Catalogo'}</span><h1>${data.name}</h1><p class="lead">${data.description || 'Prodotto selezionato da FrescoGest.'}</p><div class="price-row"><strong class="price">${price}<small>/kg</small></strong>${data.has_personalized_price ? '<span class="badge">Il tuo prezzo</span>' : ''}</div><div class="hero-actions"><label>Quantità (kg)<input id="quantity" class="quantity" type="number" step="1" inputmode="decimal" value="1" title="Usa le frecce per variare di 1 kg oppure scrivi una quantità decimale"></label><button id="add-product" class="btn btn-primary">Aggiungi al carrello</button></div></div>`;
    document.querySelector('#add-product').addEventListener('click', async event => {
        event.currentTarget.classList.add('is-loading');
        event.currentTarget.disabled = true;
        await addToCart({ id: data.id, name: data.name, slug: data.slug, price_per_kg: data.price_per_kg, image_url: data.image_url }, document.querySelector('#quantity').value);
        event.currentTarget.classList.remove('is-loading');
        event.currentTarget.disabled = false;
    });
}).catch(error => notify(error.message));
