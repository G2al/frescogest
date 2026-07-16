let csrfToken = null;

async function loadCsrfToken() {
    const response = await fetch('/api/v1/csrf-token', { credentials: 'same-origin' });
    const payload = await response.json();
    csrfToken = payload.data.csrf_token;
    return csrfToken;
}

export async function api(path, options = {}, retried = false) {
    const method = (options.method || 'GET').toUpperCase();
    const mutates = !['GET', 'HEAD'].includes(method);
    if (mutates && !csrfToken) await loadCsrfToken();

    const response = await fetch(`/api/v1${path}`, {
        credentials: 'same-origin',
        ...options,
        headers: {
            Accept: 'application/json',
            ...(mutates ? { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken } : {}),
            ...(options.headers || {}),
        },
    });

    if (response.status === 419 && !retried) {
        await loadCsrfToken();
        return api(path, options, true);
    }

    const payload = response.status === 204 ? {} : await response.json();
    if (!response.ok) {
        const error = new Error(payload.message || 'Si è verificato un errore.');
        error.status = response.status;
        error.errors = payload.errors || {};
        throw error;
    }
    return payload;
}

export async function currentUser() {
    try {
        return (await api('/auth/user')).data;
    } catch (error) {
        if (error.status === 401) return null;
        throw error;
    }
}
