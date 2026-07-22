import { api, currentUser } from './api.js?v=20260720.5';
import { notify } from './ui.js?v=20260722.3';

async function loadProfile() {
    if (!document.querySelector('#profile-form')) return;
    if (!await currentUser()) { location.href = '/login.html?next=/profile.html'; return; }
    const { data } = await api('/profile');
    const form = document.querySelector('#profile-form');
    Object.entries(data.customer || {}).forEach(([key, value]) => { if (form.elements[key]) form.elements[key].value = value ?? ''; });
}
document.querySelector('#profile-form')?.addEventListener('submit', async event => {
    event.preventDefault();
    try {
        await api('/profile', { method: 'PATCH', body: JSON.stringify(Object.fromEntries(new FormData(event.target))) });
        notify('Profilo aggiornato.', 'success');
    } catch (error) { notify(Object.values(error.errors || {}).flat()[0] || error.message, 'error'); }
});
loadProfile().catch(error => notify(error.message, 'error'));
