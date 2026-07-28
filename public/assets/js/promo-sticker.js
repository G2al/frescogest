import { notify, refreshIcons } from './ui.js?v=20260722.5';

const section = document.querySelector('#promo-sticker-section');
const title = document.querySelector('#promo-sticker-title');
const stage = document.querySelector('#promo-sticker-stage');
const sticker = document.querySelector('#promo-sticker');
const reward = document.querySelector('#promo-sticker-reward');
const rewardLabel = document.querySelector('#promo-reward-label');
const description = document.querySelector('#promo-description');
const status = document.querySelector('#promo-sticker-status');
const copyButton = document.querySelector('#copy-promo-code');
const promoCode = document.querySelector('#promo-code');

const promotionDescription = (promotion) => {
    const discount = Number(promotion.discount_percentage).toLocaleString('it-IT', {
        maximumFractionDigits: 2,
    });
    const rule = promotion.rule === 'first_order'
        ? 'Valido sul tuo primo ordine'
        : 'Valido sui tuoi ordini';
    const audience = promotion.audience === 'all'
        ? ''
        : ` per ${promotion.audience_label.toLowerCase()}`;

    return `${rule}${audience}: ${discount}% di sconto. Comunica il codice ad Antonio su WhatsApp.`;
};

const loadPromotion = async () => {
    const response = await fetch('/api/v1/promotions/sticker', {
        credentials: 'same-origin',
        headers: {
            Accept: 'application/json',
            'Cache-Control': 'no-cache',
        },
    });

    if (! response.ok) {
        throw new Error('Promozione non disponibile.');
    }

    const payload = await response.json();

    return payload.data;
};

const initializeSticker = async () => {
    if (! section || ! title || ! stage || ! sticker || ! reward || ! rewardLabel || ! description || ! status || ! copyButton || ! promoCode) {
        return;
    }

    let promotion;

    try {
        promotion = await loadPromotion();
    } catch {
        section.remove();

        return;
    }

    if (! promotion) {
        section.remove();

        return;
    }

    title.textContent = promotion.name;
    rewardLabel.textContent = promotion.name;
    promoCode.textContent = promotion.code;
    description.textContent = promotionDescription(promotion);
    section.classList.remove('hidden');

    let revealed = false;
    let resetTimer;

    const setRevealProgress = (progress) => {
        const normalizedProgress = Math.min(Math.max(Number(progress) || 0, 0), 1);
        reward.style.clipPath = `inset(0 ${100 - (normalizedProgress * 100)}% 0 0 round 28px)`;
    };

    const revealReward = () => {
        if (revealed) {
            return;
        }

        revealed = true;
        clearTimeout(resetTimer);
        setRevealProgress(1);
        stage.classList.add('is-revealed');
        reward.setAttribute('aria-hidden', 'false');
        copyButton.disabled = false;
        status.textContent = 'Codice promo sbloccato';
    };

    const resetReward = () => {
        if (revealed) {
            return;
        }

        setRevealProgress(0);
        status.textContent = 'Afferra un bordo e tira';
    };

    copyButton.addEventListener('click', async () => {
        try {
            await navigator.clipboard.writeText(promotion.code);
            copyButton.classList.add('is-copied');
            copyButton.querySelector('span').textContent = 'Codice copiato';
            notify('Codice promo copiato.', 'success');
        } catch {
            notify(`Codice promo: ${promotion.code}`, 'success');
        }
    });

    sticker.addEventListener('peelstart', () => {
        clearTimeout(resetTimer);
        status.textContent = 'Continua a tirare';
    });

    sticker.addEventListener('peelchange', (event) => {
        if (revealed) {
            return;
        }

        const progress = Number(event.detail?.progress ?? event.detail?.amount ?? 0);
        setRevealProgress(progress);

        if (progress >= 0.82) {
            status.textContent = 'Ci sei quasi';
        }
    });

    sticker.addEventListener('peelend', (event) => {
        const progress = Number(event.detail?.progress ?? event.detail?.amount ?? 0);

        if (progress >= 0.96 || event.detail?.willReset === false) {
            revealReward();

            return;
        }

        status.textContent = 'Tira ancora un po’';
        resetTimer = window.setTimeout(resetReward, 550);
    });

    sticker.addEventListener('error', () => {
        sticker.classList.add('hidden');
        stage.classList.add('has-sticker-error');
        status.textContent = 'La sorpresa è pronta';
        revealReward();
    });

    try {
        await import('https://sticker.oooo.so/embed/sticker-forge.es.js');
        await customElements.whenDefined('sticker-forge');

        await sticker.setSource({
            type: 'text',
            text: 'TIRA QUI\nscopri il tuo sconto',
            fontFamily: 'Arial Rounded MT Bold, Arial Black, sans-serif',
            fontWeight: 900,
            color: '#97604e',
            richText: {
                blocks: [
                    {
                        align: 'center',
                        lineHeight: 1.2,
                        runs: [
                            {
                                text: 'TIRA ',
                                color: 'rgb(25, 25, 29)',
                                fontSize: 28,
                                fontWeight: 900,
                                underline: false,
                            },
                            {
                                text: 'QUI',
                                color: 'rgb(36, 126, 245)',
                                fontSize: 28,
                                fontWeight: 900,
                                underline: false,
                            },
                        ],
                    },
                    {
                        align: 'center',
                        lineHeight: 0.8,
                        runs: [
                            {
                                text: 'scopri il tuo sconto',
                                color: 'rgb(25, 25, 29)',
                                fontSize: 10,
                                fontWeight: 500,
                                underline: false,
                            },
                        ],
                    },
                ],
            },
        });

        sticker.setOptions({
            outline: {
                width: 18,
                color: '#ffffff',
            },
            shadow: {
                opacity: 0.22,
                blur: 22,
                distance: 16,
                angle: 42,
                color: '#191823',
            },
            peel: {
                radius: 0.12,
                stiffness: 0.72,
                grabWidth: 22,
                maxAngle: 3.55,
                release: 'snap',
            },
            sound: {
                enabled: true,
                volume: 0.68,
            },
            back: {
                color: '#f7f5f2',
                gloss: 0.7,
                roughness: 0.3,
            },
            tilt: -4.5,
            wind: 0.25,
            quality: 'high',
        });
    } catch {
        sticker.classList.add('hidden');
        stage.classList.add('has-sticker-error');
        status.textContent = 'La sorpresa è pronta';
        revealReward();
    }

    refreshIcons(stage);
};

initializeSticker();
