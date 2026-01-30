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
