document.addEventListener('alpine:init', () => {
    Alpine.data('deviceManager', () => ({
        printerCount: 0,
        printerStatus: 'Disconnected',
        scannerStatus: 'Ready (Keyboard)',
        scannerDevice: null,
        printerDevice: null,
        printerMethod: localStorage.getItem('printer_method') || null, // 'serial', 'usb', 'bluetooth'
        
        init() {
            // Restore connections
            this.restoreConnections();

            // Keyboard Wedge Listener (Scanner)
            let buffer = '';
            let lastKeyTime = Date.now();
            
            window.addEventListener('keydown', (e) => {
                // Ignore if user is typing in an input field
                if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') return;

                const currentTime = Date.now();
                if (currentTime - lastKeyTime > 100) buffer = '';
                lastKeyTime = currentTime;
                
                if (e.key === 'Enter') {
                    if (buffer.length > 2) {
                        this.handleBarcodeScan(buffer);
                        buffer = '';
                    }
                } else if (e.key.length === 1) {
                    buffer += e.key;
                }
            });

            // Web Serial Events
            if ("serial" in navigator) {
                navigator.serial.addEventListener("connect", () => {
                    this.printerCount++;
                    this.notify('Serial Device Connected');
                });
                navigator.serial.addEventListener("disconnect", () => {
                    this.printerCount = Math.max(0, this.printerCount - 1);
                    this.notify('Serial Device Disconnected', 'warning');
                });
            }
        },

        async restoreConnections() {
            // Check Serial Ports
            if ("serial" in navigator) {
                try {
                    const ports = await navigator.serial.getPorts();
                    this.printerCount = ports.length;
                    if (ports.length > 0) {
                        this.printerStatus = 'Connected (Serial)';
                        this.printerDevice = ports[0];
                        // Auto-open first port if possible? usually requires user gesture or persistent permissions
                        // await ports[0].open({ baudRate: 9600 });
                    }
                } catch (e) { console.error(e); }
            }

            // Check HID Devices
            if ("hid" in navigator) {
                try {
                    const devices = await navigator.hid.getDevices();
                    if (devices.length > 0) {
                        this.scannerDevice = devices[0];
                        this.setupScannerListeners(this.scannerDevice);
                        this.scannerStatus = 'Connected (HID)';
                    }
                } catch (e) { console.error(e); }
            }
        },

        // --- PRINTER LOGIC ---
        
        async connectPrinter() {
            // If already connected, confirm disconnect
            if (this.printerStatus.startsWith('Connected')) {
                const result = await Swal.fire({
                    title: 'Disconnect Printer?',
                    text: 'Are you sure you want to disconnect?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    confirmButtonText: 'Yes, disconnect'
                });
                if (result.isConfirmed) this.disconnectPrinter();
                return;
            }

            // Ask for method
            const { value: method } = await Swal.fire({
                title: 'Connect Printer',
                input: 'select',
                inputOptions: {
                    'serial': 'Serial / USB-COM (Most Common)',
                    'usb': 'WebUSB (Native USB)',
                    'bluetooth': 'Bluetooth'
                },
                inputPlaceholder: 'Select Connection Method',
                showCancelButton: true
            });

            if (!method) return;

            try {
                if (method === 'serial') {
                    if (!("serial" in navigator)) throw new Error('Web Serial not supported');
                    const port = await navigator.serial.requestPort();
                    await port.open({ baudRate: 9600 });
                    this.printerDevice = port;
                    this.printerCount++;
                    this.printerStatus = 'Connected (Serial)';
                    this.printerMethod = 'serial';
                } 
                else if (method === 'usb') {
                    if (!("usb" in navigator)) throw new Error('WebUSB not supported');
                    const device = await navigator.usb.requestDevice({ filters: [] });
                    await device.open();
                    await device.selectConfiguration(1);
                    await device.claimInterface(0);
                    this.printerDevice = device;
                    this.printerStatus = 'Connected (USB)';
                    this.printerMethod = 'usb';
                }
                else if (method === 'bluetooth') {
                    if (!("bluetooth" in navigator)) throw new Error('Web Bluetooth not supported');
                    const device = await navigator.bluetooth.requestDevice({
                        filters: [{ services: ['000018f0-0000-1000-8000-00805f9b34fb'] }] // Standard Printer Service
                    });
                    const server = await device.gatt.connect();
                    this.printerDevice = device;
                    this.printerStatus = 'Connected (BT)';
                    this.printerMethod = 'bluetooth';
                }

                localStorage.setItem('printer_method', method);
                this.notify('Printer Connected Successfully');

            } catch (err) {
                if (err.name !== 'NotFoundError') {
                    this.notify(err.message || 'Connection Failed', 'error');
                }
            }
        },

        async disconnectPrinter() {
            try {
                if (this.printerDevice) {
                    if (this.printerMethod === 'serial') await this.printerDevice.close();
                    else if (this.printerMethod === 'usb') await this.printerDevice.close();
                    else if (this.printerMethod === 'bluetooth') this.printerDevice.gatt.disconnect();
                }
            } catch (e) { console.error(e); }
            
            this.printerDevice = null;
            this.printerStatus = 'Disconnected';
            this.printerCount = 0;
            localStorage.removeItem('printer_method');
            this.notify('Printer Disconnected');
        },

        // --- SCANNER LOGIC ---

        async connectScanner() {
            if ("hid" in navigator) {
                try {
                    const devices = await navigator.hid.requestDevice({ filters: [] });
                    if (devices.length > 0) {
                        this.scannerDevice = devices[0];
                        await this.scannerDevice.open();
                        this.setupScannerListeners(this.scannerDevice);
                        this.scannerStatus = 'Connected (HID)';
                        this.notify('Scanner Connected via HID');
                    }
                } catch (err) {
                    if (err.name !== 'NotFoundError') console.error(err);
                }
            } else {
                this.notify('WebHID not supported. Using Keyboard Mode.', 'info');
            }
        },

        setupScannerListeners(device) {
            device.addEventListener("inputreport", (event) => {
                // Simplified HID report handling - assumes standard keyboard-like HID
                // In production, parsing HID reports is complex and device-specific
                this.flashScannerStatus();
            });
        },

        handleBarcodeScan(code) {
            this.flashScannerStatus();
            // Dispatch to Livewire or global event
            window.dispatchEvent(new CustomEvent('barcode-scanned', { detail: code }));
            
            // If inside Livewire component with 'barcode' property
            const input = document.getElementById('barcode-input');
            if (input) {
                input.value = code;
                input.dispatchEvent(new Event('input'));
                // Trigger Livewire update if needed
                // Livewire.dispatch('scan', { code: code });
            }
        },

        flashScannerStatus() {
            const original = this.scannerStatus;
            this.scannerStatus = 'Scanning...';
            setTimeout(() => this.scannerStatus = original, 500);
        },

        notify(msg, type = 'success') {
            window.dispatchEvent(new CustomEvent(type === 'error' ? 'notify-error' : 'notify', { detail: msg }));
        }
    }));
});
