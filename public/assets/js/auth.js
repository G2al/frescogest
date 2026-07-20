import { api, currentUser } from './api.js?v=20260720.5';
import { notify, refreshIcons } from './ui.js?v=20260720.5';

const form = document.querySelector('form[data-endpoint]');
const customerTypes = [...(form?.querySelectorAll('[name="type"]') ?? [])];

function showFormMessage(message, type = 'error') {
    const container = form?.querySelector('.form-message');
    if (!container) return;
    const icon = type === 'success' ? 'circle-check' : 'circle-alert';
    container.className = `form-message form-message-${type}`;
    container.innerHTML = `<i data-lucide="${icon}"></i><span></span>`;
    container.querySelector('span').textContent = message;
    refreshIcons(container);
}

function clearFormMessage() {
    const container = form?.querySelector('.form-message');
    if (!container) return;
    container.className = 'form-message';
    container.replaceChildren();
    form.querySelectorAll('.is-invalid').forEach(field => field.classList.remove('is-invalid'));
}

function updateRegistrationFields() {
    if (!customerTypes.length) return;
    const restaurant = form.querySelector('[name="type"]:checked')?.value === 'restaurant';
    form.querySelector('.restaurant-registration-field')?.classList.toggle('hidden', !restaurant);
    form.querySelector('[name="company_name"]')?.toggleAttribute('required', restaurant);
    form.querySelector('[name="first_name"]')?.toggleAttribute('required', !restaurant);
    form.querySelector('[name="last_name"]')?.toggleAttribute('required', !restaurant);
}

function passwordScore(value) {
    if (!value) return 0;
    let score = value.length >= 4 ? 45 : Math.min(value.length * 10, 35);
    if (value.length >= 8) score += 20;
    if (/[a-z]/.test(value) && /[A-Z]/.test(value)) score += 15;
    if (/\d/.test(value)) score += 10;
    if (/[^A-Za-z0-9]/.test(value)) score += 10;
    return Math.min(score, 100);
}

function updatePasswordMeter(input) {
    const meter = input.closest('.field')?.querySelector('.password-meter');
    if (!meter) return;
    const score = passwordScore(input.value);
    meter.style.setProperty('--password-score', `${score}%`);
    meter.dataset.level = score < 45 ? 'weak' : score < 75 ? 'good' : 'strong';
    const label = meter.nextElementSibling;
    if (label?.classList.contains('password-hint')) {
        label.textContent = input.value.length < 4 ? `Ancora ${4 - input.value.length} caratteri` : score >= 75 ? 'Password sicura' : 'Requisito minimo rispettato';
    }
}

function generatedPassword(length = 12) {
    const alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789!@#$%';
    const values = new Uint32Array(length);
    crypto.getRandomValues(values);
    return [...values].map(value => alphabet[value % alphabet.length]).join('');
}

document.querySelectorAll('.password-toggle').forEach(button => {
    button.addEventListener('click', () => {
        const input = document.querySelector(`#${button.dataset.target}`);
        if (!input) return;
        const visible = input.type === 'text';
        input.type = visible ? 'password' : 'text';
        button.innerHTML = `<i data-lucide="${visible ? 'eye' : 'eye-off'}"></i>`;
        button.setAttribute('aria-label', visible ? 'Mostra password' : 'Nascondi password');
        refreshIcons(button);
    });
});

document.querySelectorAll('[data-generate-password]').forEach(button => {
    button.addEventListener('click', () => {
        const password = generatedPassword();
        const passwordInput = form.querySelector('[name="password"]');
        const confirmationInput = form.querySelector('[name="password_confirmation"]');
        passwordInput.value = password;
        confirmationInput.value = password;
        passwordInput.type = 'text';
        confirmationInput.type = 'text';
        passwordInput.dispatchEvent(new Event('input'));
        confirmationInput.dispatchEvent(new Event('input'));
        notify('Password sicura generata e inserita.', 'success');
    });
});

form?.querySelectorAll('[name="password"]').forEach(input => {
    input.addEventListener('input', () => updatePasswordMeter(input));
    updatePasswordMeter(input);
});

customerTypes.forEach(type => type.addEventListener('change', updateRegistrationFields));
updateRegistrationFields();

if (form) {
    form.addEventListener('submit', async event => {
        event.preventDefault();
        clearFormMessage();
        const submitButton = form.querySelector('button[type="submit"]');
        const data = Object.fromEntries(new FormData(form));
        submitButton.disabled = true;
        submitButton.classList.add('is-loading');

        try {
            await api(form.dataset.endpoint, { method: 'POST', body: JSON.stringify(data) });

            if (form.dataset.endpoint === '/auth/forgot-password') {
                const success = 'Se l’indirizzo esiste, riceverai le istruzioni per reimpostare la password.';
                showFormMessage(success, 'success');
                notify(success, 'success');
                submitButton.disabled = false;
                submitButton.classList.remove('is-loading');
                return;
            }

            if (form.dataset.endpoint === '/auth/reset-password') {
                notify('Password aggiornata correttamente.', 'success');
                setTimeout(() => { location.href = '/login.html?reset=1'; }, 650);
                return;
            }

            const success = form.dataset.endpoint === '/auth/register'
                ? 'Registrazione completata. Benvenuto!'
                : 'Accesso effettuato. Bentornato!';
            showFormMessage(success, 'success');
            notify(success, 'success');
            const next = new URLSearchParams(location.search).get('next') || '/catalog.html';
            setTimeout(() => { location.href = next; }, 650);
        } catch (error) {
            const entries = Object.entries(error.errors || {});
            const first = entries.flatMap(([, errors]) => errors)[0];
            const message = first || error.message || 'Controlla i dati inseriti e riprova.';
            const invalidField = entries[0]?.[0];
            form.querySelector(`[name="${invalidField}"]`)?.classList.add('is-invalid');
            showFormMessage(message);
            notify(message, 'error');
            submitButton.disabled = false;
            submitButton.classList.remove('is-loading');
        }
    });
}

if (new URLSearchParams(location.search).has('reset')) {
    showFormMessage('Password aggiornata. Ora puoi accedere.', 'success');
}

if (document.body.dataset.guestOnly === 'true') {
    currentUser()
        .then(user => { if (user) location.href = '/catalog.html'; })
        .catch(() => notify('Impossibile verificare la sessione.', 'error'));
}
