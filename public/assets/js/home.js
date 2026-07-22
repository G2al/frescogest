import { api } from './api.js?v=20260720.5';
import { refreshIcons } from './ui.js?v=20260722.5';

const productsRoot = document.querySelector('#featured-products');
const previousButton = document.querySelector('#featured-prev');
const nextButton = document.querySelector('#featured-next');

function featuredProduct(product) {
    const price = Number(product.price_per_kg).toLocaleString('it-IT', {
        style: 'currency',
        currency: 'EUR',
    });
    const image = product.image_url
        ? `<img src="${product.image_url}" alt="${product.name}" loading="lazy">`
        : '<span class="featured-placeholder"><i data-lucide="leaf"></i></span>';

    return `
        <a class="featured-product" href="/product.html?slug=${encodeURIComponent(product.slug)}">
            <span class="featured-product-image">${image}<img class="featured-quality-seal" src="/assets/images/frescogest-quality-seal.png?v=bee6ef63" alt="" aria-hidden="true"></span>
            <span class="featured-product-copy">
                <small>${product.category?.name || 'Catalogo'}</small>
                <strong>${product.name}</strong>
                <span>${price}<small>/kg</small></span>
            </span>
            <i data-lucide="arrow-up-right"></i>
        </a>`;
}

function scrollProducts(direction) {
    productsRoot?.scrollBy({
        left: direction * Math.max(productsRoot.clientWidth * 0.72, 280),
        behavior: 'smooth',
    });
}

async function loadFeaturedProducts() {
    try {
        const response = await api('/catalog/products');
        const products = response.data.slice(0, 10);
        productsRoot.innerHTML = products.length
            ? products.map(featuredProduct).join('')
            : '<p class="featured-empty">I prodotti saranno disponibili a breve.</p>';
        refreshIcons(productsRoot);
    } catch {
        productsRoot.innerHTML = '<p class="featured-empty">Non è stato possibile caricare i prodotti.</p>';
    }
}

previousButton?.addEventListener('click', () => scrollProducts(-1));
nextButton?.addEventListener('click', () => scrollProducts(1));

loadFeaturedProducts();
