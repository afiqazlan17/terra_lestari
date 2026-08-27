// Direct Bluetooth (Web Bluetooth API) printing for ESC/POS thermal receipt
// printers. This exists because generic Android "print driver" apps often
// don't register as a system Print Service, so window.print() never sees
// the printer as an option. This bypasses that entirely - the browser talks
// straight to the printer over Bluetooth Low Energy (BLE) GATT.
//
// The exact GATT service/characteristic UUID a printer exposes depends on
// its internal Bluetooth chip, which varies by manufacturer and isn't
// published for generic/no-name printers. KNOWN_SERVICES lists the patterns
// most commonly seen on cheap BLE thermal printer modules; connect() tries
// each in turn, then falls back to scanning for any writable characteristic
// among the declared optionalServices.

const KNOWN_SERVICES = [
    // Very common on generic Chinese BLE printer/serial modules
    { service: '0000ff00-0000-1000-8000-00805f9b34fb', write: '0000ff02-0000-1000-8000-00805f9b34fb' },
    // ISSC / Microchip UART service, common in ESC/POS BLE printers
    { service: '49535343-fe7d-4ae5-8fa9-9fafd205e455', write: '49535343-8841-43f4-a8d4-ecbe34729bb3' },
    // Nordic UART Service (NUS)
    { service: '6e400001-b5a3-f393-e0a9-e50e24dcca9e', write: '6e400002-b5a3-f393-e0a9-e50e24dcca9e' },
];

const STORAGE_KEY = 'sb_printer_config';
const CHUNK_SIZE = 20;

const SBPrinter = {
    device: null,
    characteristic: null,
    lastError: null,
    ready: Promise.resolve(false),

    isSupported() {
        return 'bluetooth' in navigator;
    },

    // Callers that print right after a page load (e.g. the receipt page,
    // reached immediately after checkout) can race the background
    // reconnectIfKnown() triggered in app.js. Wait for it (capped) before
    // checking isConnected().
    async waitUntilReady(timeoutMs = 4000) {
        return Promise.race([
            this.ready,
            new Promise((resolve) => setTimeout(resolve, timeoutMs)),
        ]);
    },

    getSavedConfig() {
        try {
            return JSON.parse(localStorage.getItem(STORAGE_KEY) || 'null');
        } catch (e) {
            return null;
        }
    },

    isConnected() {
        return !!(this.characteristic && this.device && this.device.gatt.connected);
    },

    async connect() {
        this.lastError = null;
        const allServiceUuids = KNOWN_SERVICES.map((s) => s.service);

        const device = await navigator.bluetooth.requestDevice({
            acceptAllDevices: true,
            optionalServices: allServiceUuids,
        });

        const server = await device.gatt.connect();
        const found = await this._findWritableCharacteristic(server);

        if (! found) {
            throw new Error('Tiada saluran cetak (writable characteristic) dijumpai pada printer ini.');
        }

        this._bind(device, found.characteristic);

        localStorage.setItem(STORAGE_KEY, JSON.stringify({
            deviceId: device.id,
            deviceName: device.name,
            service: found.service,
            write: found.write,
        }));

        return { name: device.name };
    },

    async reconnectIfKnown() {
        if (! this.isSupported() || ! navigator.bluetooth.getDevices) {
            return false;
        }

        const saved = this.getSavedConfig();
        if (! saved) {
            return false;
        }

        try {
            const devices = await navigator.bluetooth.getDevices();
            const device = devices.find((d) => d.id === saved.deviceId);
            if (! device) {
                return false;
            }

            const server = await device.gatt.connect();
            const service = await server.getPrimaryService(saved.service);
            const characteristic = await service.getCharacteristic(saved.write);

            this._bind(device, characteristic);

            return true;
        } catch (e) {
            this.lastError = e.message;
            return false;
        }
    },

    disconnect() {
        if (this.device && this.device.gatt.connected) {
            this.device.gatt.disconnect();
        }
        this.device = null;
        this.characteristic = null;
        localStorage.removeItem(STORAGE_KEY);
    },

    async write(bytes) {
        if (! this.isConnected()) {
            throw new Error('Printer tidak connect.');
        }

        for (let i = 0; i < bytes.length; i += CHUNK_SIZE) {
            const chunk = bytes.slice(i, i + CHUNK_SIZE);
            await this.characteristic.writeValue(chunk);
        }
    },

    _bind(device, characteristic) {
        this.device = device;
        this.characteristic = characteristic;

        device.addEventListener('gattserverdisconnected', () => {
            this.characteristic = null;
        });
    },

    async _findWritableCharacteristic(server) {
        for (const candidate of KNOWN_SERVICES) {
            try {
                const service = await server.getPrimaryService(candidate.service);
                const characteristic = await service.getCharacteristic(candidate.write);
                return { service: candidate.service, write: candidate.write, characteristic };
            } catch (e) {
                continue;
            }
        }

        try {
            const services = await server.getPrimaryServices();
            for (const service of services) {
                const chars = await service.getCharacteristics();
                for (const ch of chars) {
                    if (ch.properties.write || ch.properties.writeWithoutResponse) {
                        return { service: service.uuid, write: ch.uuid, characteristic: ch };
                    }
                }
            }
        } catch (e) {
            this.lastError = e.message;
        }

        return null;
    },
};

function padLine(label, value, width) {
    const space = width - label.length - value.length;
    return label + ' '.repeat(Math.max(space, 1)) + value;
}

function wrapText(text, width) {
    const lines = [];
    let remaining = text;
    while (remaining.length > width) {
        lines.push(remaining.slice(0, width));
        remaining = remaining.slice(width);
    }
    lines.push(remaining);
    return lines;
}

// ESC/POS "generate pulse" command that kicks a cash drawer wired into
// the printer's RJ11/RJ12 drawer port. pin=0 vs pin=1 selects which of
// the two connector pins carries the pulse - drawers/printers disagree
// on which one they're wired to, so both are worth trying. t1/t2 are
// the pulse on/off durations in 2ms units (25/250 is a common default).
function buildOpenDrawerCommand(pin = 0, t1 = 25, t2 = 250) {
    return new Uint8Array([0x1B, 0x70, pin, t1, t2]);
}

// Some generic/OEM printer boards (common on unbranded Chinese thermal
// printers) implement cash drawer kick via this alternate DLE DC4
// sequence instead of, or in addition to, the standard ESC p command.
function buildOpenDrawerCommandAlt() {
    return new Uint8Array([0x10, 0x14, 0x01, 0x00, 0x01]);
}

function buildReceiptEscPos(data, is58mm, options = {}) {
    const width = is58mm ? 32 : 42;
    const enc = new TextEncoder();
    const bytes = [];
    const push = (arr) => { for (const b of arr) bytes.push(b); };
    const text = (s) => push(Array.from(enc.encode(s)));

    const ESC = 0x1B;
    const GS = 0x1D;

    push([ESC, 0x40]); // initialize

    if (options.openDrawer) {
        push(Array.from(buildOpenDrawerCommand()));
    }
    push([ESC, 0x61, 0x01]); // center align
    push([ESC, 0x45, 0x01]); // bold on
    text('SAJIAN BAGINDA\n');
    push([ESC, 0x45, 0x00]); // bold off
    text('Warisan Rasa Pantai Timur\n');
    text('-'.repeat(width) + '\n');
    push([ESC, 0x61, 0x00]); // left align
    text(`No. Resit: ${data.orderNumber}\n`);
    text(`Tarikh: ${data.dateStr}\n`);
    text(`Jenis: ${data.typeLabel}\n`);
    text('-'.repeat(width) + '\n');

    data.items.forEach((item) => {
        wrapText(item.name, width).forEach((line) => text(line + '\n'));
        const left = `${item.qty} x RM ${item.price}`;
        const right = `RM ${item.lineTotal}`;
        text(padLine(left, right, width) + '\n');
    });

    text('-'.repeat(width) + '\n');
    text(padLine('Subtotal', `RM ${data.subtotal}`, width) + '\n');
    if (data.discount && parseFloat(data.discount) > 0) {
        text(padLine('Diskaun', `RM ${data.discount}`, width) + '\n');
    }
    push([ESC, 0x45, 0x01]);
    text(padLine('Jumlah', `RM ${data.total}`, width) + '\n');
    push([ESC, 0x45, 0x00]);
    text(padLine('Bayaran', data.paymentLabel, width) + '\n');
    text('-'.repeat(width) + '\n');
    push([ESC, 0x61, 0x01]);
    text('Terima kasih!\n\n\n');

    push([GS, 0x56, 0x42, 0x00]); // partial cut

    return new Uint8Array(bytes);
}

window.SBPrinter = SBPrinter;
window.buildReceiptEscPos = buildReceiptEscPos;
window.buildOpenDrawerCommand = buildOpenDrawerCommand;
window.buildOpenDrawerCommandAlt = buildOpenDrawerCommandAlt;
