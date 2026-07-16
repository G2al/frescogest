import { api, currentUser } from './api.js';
import { notify } from './ui.js';

async function loadOrders() {
    const root = document.querySelector('#orders');
    if (!root) return;
    if (!await currentUser()) { location.href = '/login.html?next=/orders.html'; return; }
    const { data } = await api('/orders');
    root.innerHTML = data.length ? data.map(order => `<article class="order-row reveal"><div><div class="card-meta"><strong>${order.order_number}</strong><span class="badge">${order.status_label}</span></div><p>${order.items.map(item => `${item.quantity} kg · ${item.product_name} · ${Number(item.line_total).toLocaleString('it-IT', { style: 'currency', currency: 'EUR' })}`).join('<br>')}</p></div><div><strong>${Number(order.total_amount).toLocaleString('it-IT', { style: 'currency', currency: 'EUR' })}</strong><br><time>${new Date(order.requested_at).toLocaleDateString('it-IT')}</time></div></article>`).join('') : '<div class="empty">Non hai ancora inviato richieste d’ordine.</div>';
    const created = new URLSearchParams(location.search).get('created');
    if (created) notify(`Richiesta ${created} salvata. WhatsApp non costituisce conferma dell’ordine.`);
}
loadOrders().catch(error => notify(error.message));
