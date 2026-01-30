<div x-data="deviceToolbar()" x-init="init()" id="navbar-extras" class="flex items-center space-x-1 md:space-x-2 mr-2 md:mr-4 border-r border-gray-200 pr-2 md:pr-4">
    <button @click="toggleFullscreen()"
        class="p-2 text-gray-400 hover:text-indigo-600 transition-colors rounded-full hover:bg-gray-100"
        title="{{ __('Toggle Fullscreen') }}">
        <i class="fas fa-expand text-lg md:text-xl"></i>
    </button>

    <!-- Printer Button -->
    <button @click="connectDevice('printer')" id="btn-printer"
        class="relative p-2 text-gray-400 hover:text-indigo-600 transition-colors rounded-full hover:bg-gray-100 group"
        title="{{ __('Connect Printer') }}">
        <i class="fas fa-print text-lg md:text-xl" :class="loading.printer ? 'fa-circle-notch fa-spin text-indigo-600' : 'fa-print'"></i>
        <span id="status-printer"
            class="absolute top-1.5 right-1.5 h-2 w-2 rounded-full border border-white"
            :class="connected.printer ? 'bg-green-500' : 'bg-red-500'"></span>
    </button>

    <!-- Scanner Button -->
    <button @click="connectDevice('scanner')" id="btn-scanner"
        class="relative p-2 text-gray-400 hover:text-indigo-600 transition-colors rounded-full hover:bg-gray-100 group"
        title="{{ __('Connect Scanner') }}">
        <i class="fas fa-barcode text-lg md:text-xl" :class="loading.scanner ? 'fa-circle-notch fa-spin text-indigo-600' : 'fa-barcode'"></i>
        <span id="status-scanner"
            class="absolute top-1.5 right-1.5 h-2 w-2 rounded-full border border-white"
            :class="connected.scanner ? 'bg-green-500' : 'bg-red-500'"></span>
    </button>

    <!-- Dongle Button -->
    <button @click="connectDevice('dongle')" id="btn-dongle"
        class="relative p-2 text-gray-400 hover:text-indigo-600 transition-colors rounded-full hover:bg-gray-100 group"
        title="{{ __('Connect Dongle') }}">
        <i class="fas fa-microchip text-lg md:text-xl" :class="loading.dongle ? 'fa-circle-notch fa-spin text-indigo-600' : 'fa-microchip'"></i>
        <span id="status-dongle"
            class="absolute top-1.5 right-1.5 h-2 w-2 rounded-full border border-white"
            :class="connected.dongle ? 'bg-green-500' : 'bg-red-500'"></span>
    </button>
</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('deviceToolbar', () => ({
        connected: {
            printer: false,
            scanner: false,
            dongle: false
        },
        loading: {
            printer: false,
            scanner: false,
            dongle: false
        },

        init() {
            // Restore connection state from session
            ['printer', 'scanner', 'dongle'].forEach(type => {
                if (localStorage.getItem('device_connected_' + type) === 'true') {
                    this.connected[type] = true;
                }
            });

            // Initialize global store if not exists
            if (!window.connectedDevices) {
                window.connectedDevices = {
                    printer: null,
                    scanner: null,
                    dongle: null
                };
            }

            // Auto-detect USB devices
            if (navigator.usb) {
                navigator.usb.addEventListener('connect', (event) => {
                    this.autoConnectDevice(event.device);
                });

                navigator.usb.addEventListener('disconnect', (event) => {
                    this.autoDisconnectDevice(event.device);
                });

                // Check for existing devices
                this.checkExistingUsbDevices();
            }

            this.startDeviceHeartbeat();
        },

        async checkExistingUsbDevices() {
             if (!navigator.usb) return;
             try {
                 const devices = await navigator.usb.getDevices();
                 for (const device of devices) {
                     await this.autoConnectDevice(device, false);
                 }
             } catch (e) {
                 console.error('Error checking existing USB devices:', e);
             }
        },

        async autoConnectDevice(device, showToast = true) {
             // Priority: Printer -> Dongle
             // If printer is expected (was connected or usb method selected) and slot is empty
             const printerMethod = localStorage.getItem('printer_method');
             const expectPrinter = this.connected.printer || printerMethod === 'usb' || !printerMethod;

             if (expectPrinter && !window.connectedDevices.printer) {
                 try {
                     await device.open();
                     window.connectedDevices.printer = device;
                     this.connected.printer = true;
                     localStorage.setItem('device_connected_printer', 'true');
                     localStorage.setItem('printer_method', 'usb');

                     if (showToast) {
                        const name = device.productName || device.name || 'Printer';
                        const Toast = Swal.mixin({
                            toast: true,
                            position: 'top-end',
                            showConfirmButton: false,
                            timer: 3000,
                            timerProgressBar: true
                        });
                        Toast.fire({
                            icon: 'success',
                            title: `Connected to ${name}`
                        });
                     }
                     return;
                 } catch (e) {
                     console.error('Auto-connect printer failed', e);
                 }
             }

             // Dongle Logic (if needed in future)
             if (this.connected.dongle && !window.connectedDevices.dongle) {
                 try {
                     await device.open();
                     window.connectedDevices.dongle = device;
                     this.connected.dongle = true;
                 } catch (e) {
                     console.error('Auto-connect dongle failed', e);
                 }
             }
        },

        autoDisconnectDevice(device) {
             if (window.connectedDevices.printer === device) {
                 this.handleDisconnect('printer', false);
                 const Toast = Swal.mixin({
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 3000,
                    timerProgressBar: true
                 });
                 Toast.fire({
                    icon: 'warning',
                    title: 'Printer Disconnected'
                 });
             }
             // Add dongle check if needed
        },

        toggleFullscreen() {
            if (!document.fullscreenElement) {
                document.documentElement.requestFullscreen().catch(err => {
                    console.error(`Error attempting to enable fullscreen: ${err.message}`);
                });
            } else {
                if (document.exitFullscreen) {
                    document.exitFullscreen();
                }
            }
        },

        async connectDevice(type) {
            // Disconnect Logic
            if (this.connected[type]) {
                const result = await Swal.fire({
                    title: `Disconnect ${type.charAt(0).toUpperCase() + type.slice(1)}?`,
                    text: "Are you sure you want to disconnect?",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Yes, disconnect'
                });

                if (result.isConfirmed) {
                    await this.handleDisconnect(type, true);
                }
                return;
            }

            // Connect Logic
            this.loading[type] = true;
            try {
                let device = null;
                let connectionMethod = 'mock';

                if (type === 'printer') {
                    const { value: selectedMethod } = await Swal.fire({
                        title: 'Connect Printer',
                        text: 'Select connection method',
                        icon: 'question',
                        input: 'radio',
                        inputOptions: {
                            'bluetooth': 'Bluetooth',
                            'usb': 'USB Cable'
                        },
                        inputValidator: (value) => {
                            if (!value) return 'You need to select a connection method!'
                        },
                        showCancelButton: true
                    });

                    if (!selectedMethod) {
                        this.loading[type] = false;
                        return;
                    }
                    connectionMethod = selectedMethod;
                    localStorage.setItem('printer_method', connectionMethod);
                }

                if (type === 'printer') {
                    if (connectionMethod === 'bluetooth') {
                        if (!navigator.bluetooth) throw new Error('Bluetooth is not supported in this browser.');
                        device = await navigator.bluetooth.requestDevice({
                            acceptAllDevices: true,
                            optionalServices: ['000018f0-0000-1000-8000-00805f9b34fb'] // Standard Printer Service UUID
                        });
                        if (device.gatt) await device.gatt.connect();
                    } else { // USB
                        if (!navigator.usb) throw new Error('WebUSB is not supported in this browser.');
                        device = await navigator.usb.requestDevice({ filters: [] });
                        await device.open();
                    }
                } else if (type === 'dongle') {
                    // Assume Dongle is a USB device
                    if (navigator.usb) {
                         device = await navigator.usb.requestDevice({ filters: [] });
                         await device.open();
                    } else {
                         // Fallback simulation for Dongle
                         await new Promise(r => setTimeout(r, 1000));
                         device = { productName: 'Dongle Device', simulation: true };
                    }
                } else {
                    // Scanner (Mock/Simulated)
                    await new Promise(r => setTimeout(r, 1000));
                    device = { productName: 'Scanner' };
                }

                // Store reference globally
                window.connectedDevices[type] = device;

                // Success
                this.connected[type] = true;

                // Save state
                localStorage.setItem('device_connected_' + type, 'true');

                const name = device.productName || device.name || type;
                Swal.fire('Connected!', `Successfully connected to ${name}`, 'success');

            } catch (error) {
                console.error(error);
                // Ignore user cancellation errors
                if (error.name !== 'NotFoundError' && error.message !== 'User cancelled the requestDevice() chooser.') {
                    Swal.fire('Connection Failed', error.message, 'error');
                }
            } finally {
                this.loading[type] = false;
            }
        },

        async handleDisconnect(type, showAlert = true) {
            this.connected[type] = false;

            localStorage.removeItem('device_connected_' + type);
            if (type === 'printer') localStorage.removeItem('printer_method');

            if (window.connectedDevices && window.connectedDevices[type]) {
                if (window.connectedDevices[type].gatt) {
                     // Check if connected before disconnecting to avoid errors
                     if (window.connectedDevices[type].gatt.connected) {
                         window.connectedDevices[type].gatt.disconnect();
                     }
                } else if (window.connectedDevices[type].close) {
                     await window.connectedDevices[type].close();
                }
                window.connectedDevices[type] = null;
            }

            if (showAlert) {
                Swal.fire('Disconnected', `${type.charAt(0).toUpperCase() + type.slice(1)} disconnected successfully`, 'success');
            }
        },

        startDeviceHeartbeat() {
            if (window.deviceHeartbeatInterval) clearInterval(window.deviceHeartbeatInterval);

            window.deviceHeartbeatInterval = setInterval(async () => {
                // Check USB Devices (Dongle, USB Printer)
                if (navigator.usb) {
                    const usbDevices = await navigator.usb.getDevices();

                    // Check Dongle
                    if (this.connected.dongle) {
                        const isConnected = window.connectedDevices.dongle
                            ? usbDevices.some(d => d === window.connectedDevices.dongle)
                            : usbDevices.length > 0; // Fallback

                        if (!isConnected && !window.connectedDevices.dongle?.simulation) {
                             this.handleDisconnect('dongle', false);
                        }
                    }

                    // Check USB Printer
                    if (this.connected.printer && localStorage.getItem('printer_method') === 'usb') {
                        const isConnected = window.connectedDevices.printer
                            ? usbDevices.some(d => d === window.connectedDevices.printer)
                            : usbDevices.length > 0;

                        if (!isConnected) this.handleDisconnect('printer', false);
                    }
                }

                // Check Bluetooth Printer
                if (this.connected.printer && localStorage.getItem('printer_method') === 'bluetooth') {
                    const device = window.connectedDevices.printer;
                    if (device && device.gatt && !device.gatt.connected) {
                        this.handleDisconnect('printer', false);
                    }
                    if (!device) {
                         this.handleDisconnect('printer', false);
                    }
                }

            }, 5000);
        }
    }))
});
</script>
