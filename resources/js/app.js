

import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();

function formatMoney(value) {
    const num = parseFloat(String(value).replace(/,/g, ''));

    if (isNaN(num)) {
        return '';
    }

    return num.toLocaleString('en-MY', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-money-input]').forEach((input) => {
        if (input.value !== '') {
            input.value = formatMoney(input.value);
        }

        input.addEventListener('focus', () => {
            input.value = input.value.replace(/,/g, '');
        });

        input.addEventListener('blur', () => {
            if (input.value !== '') {
                input.value = formatMoney(input.value);
            }
        });
    });
});

// Strip thousand separators from money inputs right before any form submits,
// so the server always receives a plain numeric string.
document.addEventListener('submit', (event) => {
    event.target.querySelectorAll?.('[data-money-input]').forEach((input) => {
        input.value = input.value.replace(/,/g, '');
    });
}, true);
