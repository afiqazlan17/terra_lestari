

import Alpine from 'alpinejs';
import { Passkeys } from '@laravel/passkeys';
import './bluetooth-printer';

window.Alpine = Alpine;
window.Passkeys = Passkeys;

document.addEventListener('alpine:init', () => {
    Alpine.store('connectivity', {
        online: navigator.onLine,
    });

    window.addEventListener('online', () => { Alpine.store('connectivity').online = true; });
    window.addEventListener('offline', () => { Alpine.store('connectivity').online = false; });
});

Alpine.start();

if ('serviceWorker' in navigator) {
    navigator.serviceWorker.register('/sw.js').catch(() => {});
}

if (window.SBPrinter) {
    window.SBPrinter.ready = window.SBPrinter.reconnectIfKnown();
}

// Bank-style money entry: the field always shows a fully-formatted "0.00"
// amount, and every digit typed shifts in from the rightmost (cents) place
// - like an ATM/banking-app amount pad - rather than editing at a text
// cursor. We track the amount as an integer cents count on the element and
// re-render the formatted string from that on every change, so the display
// can never drift out of sync with the underlying value.
const MAX_MONEY_CENTS = 999999999999; // RM 9,999,999,999.99

function moneyInputCents(input) {
    return parseInt(input.dataset.cents || '0', 10) || 0;
}

function parseToCents(raw) {
    const num = parseFloat(String(raw).replace(/,/g, ''));

    return isNaN(num) ? 0 : Math.round(num * 100);
}

function renderMoneyInput(input, cents) {
    cents = Math.min(Math.max(0, cents), MAX_MONEY_CENTS);
    input.dataset.cents = String(cents);

    const [intPart, decPart] = (cents / 100).toFixed(2).split('.');
    input.value = intPart.replace(/\B(?=(\d{3})+(?!\d))/g, ',') + '.' + decPart;

    requestAnimationFrame(() => {
        const end = input.value.length;
        input.setSelectionRange?.(end, end);
    });
}

function initMoneyInput(input) {
    // Optional (not `required`) fields stay genuinely empty until the user
    // types, rather than forcing a "0.00" that would submit as a real zero
    // instead of null - e.g. NBK's Harga Jual, which is nullable server-side.
    if (input.value.trim() !== '') {
        renderMoneyInput(input, parseToCents(input.value));
    }

    // beforeinput (not keydown) so this also works with mobile/virtual
    // keyboards, which often don't populate a usable event.key on keydown.
    input.addEventListener('beforeinput', (event) => {
        if (event.inputType === 'insertText' && /^[0-9]$/.test(event.data || '')) {
            event.preventDefault();
            renderMoneyInput(input, moneyInputCents(input) * 10 + Number(event.data));

            return;
        }

        if (event.inputType === 'deleteContentBackward' || event.inputType === 'deleteContentForward') {
            event.preventDefault();
            const next = Math.floor(moneyInputCents(input) / 10);

            if (next === 0 && ! input.required) {
                delete input.dataset.cents;
                input.value = '';
            } else {
                renderMoneyInput(input, next);
            }

            return;
        }

        // Anything else (paste, drag-drop, autofill's own insertion, IME
        // composition) is handled by the listeners below instead.
        event.preventDefault();
    });

    input.addEventListener('paste', (event) => {
        event.preventDefault();
        const text = (event.clipboardData || window.clipboardData)?.getData('text') || '';
        renderMoneyInput(input, parseToCents(text));
    });

    // Catches programmatic `input.value = x` sets (e.g. AI receipt auto-fill)
    // that are followed by a dispatched 'input' event, since those bypass
    // beforeinput entirely.
    input.addEventListener('input', () => {
        if (input.value !== '' && ! /^[\d,]*\.\d{2}$/.test(input.value)) {
            renderMoneyInput(input, parseToCents(input.value));
        }
    });
}

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-money-input]').forEach(initMoneyInput);
});

// Strip thousand separators from money inputs right before any form submits,
// so the server always receives a plain numeric string.
document.addEventListener('submit', (event) => {
    event.target.querySelectorAll?.('[data-money-input]').forEach((input) => {
        input.value = input.value.replace(/,/g, '');
    });
}, true);
