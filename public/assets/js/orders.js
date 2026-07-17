import { api, currentUser } from './api.js';
import { notify, refreshIcons } from './ui.js';

document.body.classList.add('orders-page');

function currency(value) {
    return Number(value).toLocaleString('it-IT', { style: 'currency', currency: 'EUR' });
}

function escapeHtml(value) {
    const element = document.createElement('div');
    element.textContent = value ?? '';
    return element.innerHTML;
}

function orderItem(item) {
    const image = item.image_url
        ? `<img src="${item.image_url}" alt="${escapeHtml(item.product_name)}" loading="lazy">`
        : '<span><i data-lucide="package-open"></i></span>';

    return `<div class="order-product">
        <div class="order-product-image">${image}</div>
        <div class="order-product-copy">
            <strong>${escapeHtml(item.product_name)}</strong>
            <span>${item.quantity} ${item.unit_of_measure_symbol || 'kg'} · ${currency(item.price_per_kg)}/kg</span>
        </div>
        <strong class="order-product-total">${currency(item.line_total)}</strong>
    </div>`;
}

function orderCard(order) {
    const date = new Date(order.requested_at).toLocaleDateString('it-IT', {
        day: '2-digit',
        month: 'long',
        year: 'numeric',
    });

    return `<article class="order-card reveal">
        <header class="order-card-header">
            <div><span class="order-card-label">Richiesta</span><strong>${order.order_number}</strong></div>
            <span class="badge">${order.status_label}</span>
        </header>
        <div class="order-products">${order.items.map(orderItem).join('')}</div>
        ${order.customer_notes ? `<div class="order-note"><i data-lucide="message-square-text"></i><span>${escapeHtml(order.customer_notes)}</span></div>` : ''}
        <footer class="order-card-footer">
            <div><span>Inviato il</span><time>${date}</time></div>
            <div class="order-card-total"><span>Totale indicativo</span><strong>${currency(order.total_amount)}</strong></div>
        </footer>
    </article>`;
}

async function loadOrders() {
    const root = document.querySelector('#orders');
    if (!root) return;
    if (!await currentUser()) { location.href = '/login.html?next=/orders.html'; return; }
    const { data } = await api('/orders');
    root.innerHTML = data.length
        ? data.map(orderCard).join('')
        : '<div class="empty">Non hai ancora inviato richieste d’ordine.</div>';
    refreshIcons(root);
    const created = new URLSearchParams(location.search).get('created');
    if (created) notify(`Richiesta ${created} salvata. WhatsApp non costituisce conferma dell’ordine.`);
}

loadOrders().catch(error => notify(error.message));
