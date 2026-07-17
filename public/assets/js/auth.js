import { api, currentUser } from './api.js?v=20260717.9';
import { notify } from './ui.js?v=20260717.9';

const form = document.querySelector('form[data-endpoint]');
if (form) form.addEventListener('submit', async event => {
    event.preventDefault();
    const message = form.querySelector('.form-message');
    const submitButton = form.querySelector('button[type="submit"]');
    message.textContent = '';
    const data = Object.fromEntries(new FormData(form));
    submitButton.disabled = true;
    submitButton.classList.add('is-loading');
    try {
        await api(form.dataset.endpoint, { method: 'POST', body: JSON.stringify(data) });
        if (form.dataset.endpoint === '/auth/forgot-password') { message.style.color = 'var(--green)'; message.textContent = 'Se l’indirizzo esiste, riceverai le istruzioni per reimpostare la password.'; submitButton.disabled = false; submitButton.classList.remove('is-loading'); return; }
        if (form.dataset.endpoint === '/auth/reset-password') { location.href = '/login.html?reset=1'; return; }
        const next = new URLSearchParams(location.search).get('next') || '/catalog.html';
        location.href = next;
    } catch (error) {
        const first = Object.values(error.errors || {}).flat()[0];
        message.textContent = first || error.message;
        submitButton.disabled = false;
        submitButton.classList.remove('is-loading');
    }
});

if (document.body.dataset.guestOnly === 'true') currentUser().then(user => { if (user) location.href = '/catalog.html'; }).catch(() => notify('Impossibile verificare la sessione.'));
