function updateSidebarIcon(isOpen) {
    const toggleBtn = document.querySelector('button[onclick="toggleSidebar()"]');
    if (!toggleBtn) return;
    
    const icon = toggleBtn.querySelector('i');
    if (!icon) return;
    
    if (isOpen) {
        icon.classList.remove('fa-bars');
        icon.classList.add('fa-times');
    } else {
        icon.classList.remove('fa-times');
        icon.classList.add('fa-bars');
    }
}

function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    if (!sidebar) return; // Safety check
    
    const overlay = document.getElementById('sidebar-overlay');
    
    // Check if we are on mobile or desktop
    const isMobile = window.innerWidth < 768; // Tailwind md breakpoint
    
    if (isMobile) {
        // Mobile logic
        if (sidebar.classList.contains('-translate-x-full')) {
            // Open
            sidebar.classList.remove('-translate-x-full');
            if (overlay) overlay.classList.remove('hidden');
            updateSidebarIcon(true);
        } else {
            // Close
            sidebar.classList.add('-translate-x-full');
            if (overlay) overlay.classList.add('hidden');
            updateSidebarIcon(false);
        }
    } else {
        // Desktop logic - Toggle width
        if (sidebar.classList.contains('w-64')) {
            // Close
            sidebar.classList.remove('w-64');
            sidebar.classList.add('w-0', 'overflow-hidden', 'border-none');
            updateSidebarIcon(false);
        } else {
            // Open
            sidebar.classList.remove('w-0', 'overflow-hidden', 'border-none');
            sidebar.classList.add('w-64');
            updateSidebarIcon(true);
        }
    }
}

// Initialize logic when DOM is ready
function initApp() {
    initNavbarExtras();

    const overlay = document.getElementById('sidebar-overlay');
    if (overlay) {
        // Remove existing listener to prevent duplicates if the element wasn't replaced
        overlay.removeEventListener('click', toggleSidebar);
        overlay.addEventListener('click', toggleSidebar);
    }
    
    // Initialize icon state
    if (window.innerWidth >= 768) {
        const sidebar = document.getElementById('sidebar');
        if (sidebar && !sidebar.classList.contains('w-0')) {
            updateSidebarIcon(true);
        } else {
            updateSidebarIcon(false);
        }
    } else {
        updateSidebarIcon(false);
    }
}

// Handle window resize
function handleResize() {
    const sidebar = document.getElementById('sidebar');
    if (!sidebar) return; // Safety check for pages without sidebar
    
    const overlay = document.getElementById('sidebar-overlay');
    
    if (window.innerWidth >= 768) {
        // Reset mobile specific classes when switching to desktop
        sidebar.classList.remove('-translate-x-full');
        if (overlay) overlay.classList.add('hidden');
        
        // Ensure desktop sidebar is visible by default if it wasn't toggled closed
        if (sidebar.classList.contains('w-0')) {
            // Keep it closed if it was closed
            updateSidebarIcon(false);
        } else {
            sidebar.classList.add('w-64');
            updateSidebarIcon(true);
        }
    } else {
        // Reset desktop specific classes when switching to mobile
        sidebar.classList.remove('w-0', 'overflow-hidden', 'border-none');
        sidebar.classList.add('w-64');
        
        // Ensure mobile sidebar is hidden by default
        sidebar.classList.add('-translate-x-full');
        updateSidebarIcon(false);
    }
}

// Event Listeners
document.addEventListener('DOMContentLoaded', initApp);
document.addEventListener('livewire:navigated', initApp);

// Add resize listener only once
if (!window.hasResizeListener) {
    window.addEventListener('resize', handleResize);
    window.hasResizeListener = true;
}

function initNavbarExtras() {
    // If buttons already exist (e.g. manually added in POS pages), don't inject
    if (document.getElementById('btn-printer')) return;
    if (document.getElementById('navbar-extras')) return;

    // Try to find a suitable container in the header
    let container = document.querySelector('header .flex.items-center.space-x-4');
    
    if (!container) return;

    // Create container
    const extrasContainer = document.createElement('div');
    extrasContainer.id = 'navbar-extras';
    extrasContainer.className = 'flex items-center space-x-1 md:space-x-2 mr-2 md:mr-4 border-r border-gray-200 pr-2 md:pr-4';

    // Extras HTML (Fullscreen, Printer, Scanner)
    const extrasHtml = `
        <button onclick="toggleFullscreen()" class="p-2 text-gray-400 hover:text-indigo-600 transition-colors rounded-full hover:bg-gray-100" title="Toggle Fullscreen">
            <i class="fas fa-expand text-lg md:text-xl"></i>
        </button>
        <button onclick="connectDevice('printer')" id="btn-printer" class="relative p-2 text-gray-400 hover:text-indigo-600 transition-colors rounded-full hover:bg-gray-100 group" title="Connect Printer">
            <i class="fas fa-print text-lg md:text-xl"></i>
            <span id="status-printer" class="absolute top-1.5 right-1.5 h-2 w-2 bg-red-500 rounded-full border border-white"></span>
        </button>
        <button onclick="connectDevice('scanner')" id="btn-scanner" class="relative p-2 text-gray-400 hover:text-indigo-600 transition-colors rounded-full hover:bg-gray-100 group" title="Connect Scanner">
            <i class="fas fa-barcode text-lg md:text-xl"></i>
            <span id="status-scanner" class="absolute top-1.5 right-1.5 h-2 w-2 bg-red-500 rounded-full border border-white"></span>
        </button>
    `;

    extrasContainer.innerHTML = extrasHtml;

    // Insert before the notification bell if possible, otherwise prepend
    const buttons = container.querySelectorAll('button');
    let bellBtn = null;
    buttons.forEach(btn => {
        if (btn.querySelector('.fa-bell')) {
            bellBtn = btn;
        }
    });

    if (bellBtn) {
        container.insertBefore(extrasContainer, bellBtn);
    } else {
        container.prepend(extrasContainer);
    }
}

function connectDevice(type) {
    const btn = document.getElementById(`btn-${type}`);
    if (!btn) return;
    
    const statusDot = document.getElementById(`status-${type}`);
    const icon = btn.querySelector('i');
    
    // Mock connection process
    if (statusDot && statusDot.classList.contains('bg-green-500')) {
        // Disconnect
        statusDot.classList.remove('bg-green-500');
        statusDot.classList.add('bg-red-500');
    } else {
        // Connect
        if (icon) {
            const originalIconClass = icon.className;
            // Use a simpler spinner class
            icon.className = 'fas fa-circle-notch fa-spin text-lg md:text-xl text-indigo-600';
            
            setTimeout(() => {
                icon.className = originalIconClass;
                if (statusDot) {
                    statusDot.classList.remove('bg-red-500');
                    statusDot.classList.add('bg-green-500');
                }
            }, 1500);
        }
    }
}

function toggleFullscreen() {
    if (!document.fullscreenElement) {
        document.documentElement.requestFullscreen().catch(err => {
            console.error(`Error attempting to enable fullscreen: ${err.message}`);
        });
    } else {
        if (document.exitFullscreen) {
            document.exitFullscreen();
        }
    }
}