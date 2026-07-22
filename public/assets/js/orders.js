import { api, currentUser } from './api.js?v=20260720.5';
import { notify, refreshIcons } from './ui.js?v=20260722.4';

document.body.classList.add('orders-page');

function currency(value) {
    return Number(value).toLocaleString('it-IT', { style: 'currency', currency: 'EUR' });
}

function quantity(value) {
    return Number(value).toLocaleString('it-IT', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 3,
    });
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
            <span>${quantity(item.quantity)} ${item.unit_of_measure_symbol || 'kg'} · ${currency(item.price_per_kg)}/kg</span>
        </div>
        <strong class="order-product-total">${currency(item.line_total)}</strong>
    </div>`;
}

function orderCard(order) {
    const detailsId = `order-details-${order.id}`;
    const date = new Date(order.requested_at).toLocaleDateString('it-IT', {
        day: '2-digit',
        month: 'long',
        year: 'numeric',
    });

    return `<article class="order-card reveal">
        <button class="order-card-header order-card-toggle" type="button" aria-expanded="false" aria-controls="${detailsId}">
            <div><span class="order-card-label">Richiesta</span><strong>${order.order_number}</strong></div>
            <span class="order-card-toggle-meta"><span class="badge">${order.status_label}</span><i data-lucide="chevron-down"></i></span>
        </button>
        <div id="${detailsId}" class="order-card-details" hidden>
            <div class="order-products">${order.items.map(orderItem).join('')}</div>
            ${order.customer_notes ? `<div class="order-note"><i data-lucide="message-square-text"></i><span>${escapeHtml(order.customer_notes)}</span></div>` : ''}
        </div>
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
    root.addEventListener('click', event => {
        const toggle = event.target.closest('.order-card-toggle');
        if (!toggle) return;
        const details = document.getElementById(toggle.getAttribute('aria-controls'));
        const expanded = toggle.getAttribute('aria-expanded') === 'true';
        toggle.setAttribute('aria-expanded', String(!expanded));
        toggle.closest('.order-card').classList.toggle('expanded', !expanded);
        details.hidden = expanded;
    });
    const created = new URLSearchParams(location.search).get('created');
    if (created) notify(`Richiesta ${created} salvata. WhatsApp non costituisce conferma dell’ordine.`, 'success');
}

loadOrders().catch(error => notify(error.message, 'error'));
