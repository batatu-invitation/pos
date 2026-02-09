<div x-data="deviceManager" id="navbar-extras" class="flex items-center space-x-1 md:space-x-2 mr-2 md:mr-4 border-r border-gray-200 pr-2 md:pr-4">
    <button @click="toggleFullscreen()"
        class="p-2 text-gray-400 hover:text-indigo-600 transition-colors rounded-full hover:bg-gray-100"
        title="{{ __('Toggle Fullscreen') }}">
        <i class="fas fa-expand text-lg md:text-xl"></i>
    </button>

    <!-- Printer Button -->
    <button @click="connectPrinter" id="btn-printer"
        class="relative p-2 text-gray-400 hover:text-indigo-600 transition-colors rounded-full hover:bg-gray-100 group"
        title="{{ __('Connect Printer') }}">
        <i class="fas fa-print text-lg md:text-xl" :class="printerStatus.startsWith('Connected') ? 'text-green-500' : 'fa-print'"></i>
        <span id="status-printer"
            class="absolute top-1.5 right-1.5 h-2 w-2 rounded-full border border-white"
            :class="printerStatus.startsWith('Connected') ? 'bg-green-500' : 'bg-red-500'"></span>
    </button>

    <!-- Scanner Button -->
    <button @click="connectScanner" id="btn-scanner"
        class="relative p-2 text-gray-400 hover:text-indigo-600 transition-colors rounded-full hover:bg-gray-100 group"
        title="{{ __('Connect Scanner') }}">
        <i class="fas fa-barcode text-lg md:text-xl" :class="scannerStatus.startsWith('Connected') ? 'text-green-500' : 'fa-barcode'"></i>
        <span id="status-scanner"
            class="absolute top-1.5 right-1.5 h-2 w-2 rounded-full border border-white"
            :class="scannerStatus.startsWith('Connected') ? 'bg-green-500' : 'bg-red-500'"></span>
    </button>

    <!-- Dongle Button (Placeholder as pos-devices.js doesn't have dongle yet, keeping static for now or hiding) -->
    <!-- <button @click="connectDevice('dongle')" ...> -->
</div>
