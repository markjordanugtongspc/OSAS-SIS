// Main entry point for Vite
import '../../frontend/css/styles.css';
import { processLogin } from './modules/auth.js';
import { initDashboard } from './pages/dashboard.js';
import { initPapers } from './pages/papers.js';
import { initCabinetView } from './pages/cabinet-view.js';
import { initSidebar, restoreNavigationState } from './modules/sidebar.js';
import { initProfileDropdown, initLogout } from './pages/dashboard.js';

// Mark page as loaded immediately (prevent FOUC)
function markPageLoaded() {
    requestAnimationFrame(() => {
        requestAnimationFrame(() => {
            document.body.classList.add('loaded');
        });
    });
}

// Initialize based on page
document.addEventListener('DOMContentLoaded', () => {
    const currentPath = window.location.pathname;
    
    // Login form handler (for index.php)
    const loginForm = document.getElementById('loginForm');
    if (loginForm) {
        loginForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(loginForm);
            await processLogin(formData);
        });
        // Mark login page as loaded
        markPageLoaded();
        return; // Don't initialize other functionality on login page
    }
    
    // Check if page has sidebar (dashboard, papers, template, or any frontend page)
    const hasSidebar = document.getElementById('sidebar');
    
    if (hasSidebar) {
        // Always initialize sidebar first for any page with a sidebar
        initSidebar();
        
        // Restore navigation state (scroll position, etc.)
        restoreNavigationState();
        
        // Initialize profile dropdown and logout for all frontend pages
        initProfileDropdown();
        initLogout();
        
        // Specific page handlers - these pages have their own init functions
        if (currentPath.includes('/pages/cabinets/view.php')) {
            initCabinetView();
        }
        else if (currentPath.includes('/pages/papers.php')) {
            initPapers();
        }
        else if (currentPath.includes('/dashboard.php')) {
            initDashboard();
        }
    }
});
