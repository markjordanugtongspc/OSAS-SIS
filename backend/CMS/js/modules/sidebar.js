/**
 * Sidebar functionality
 * Handles active states, collapse/expand, and navigation
 */

/**
 * Initialize sidebar functionality
 */
export function initSidebar() {
    try {
        // Wait for DOM to be fully ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => {
                initActiveState();
                initCollapseToggle();
                initInstantNavigation();
                markPageAsLoaded();
            });
        } else {
            initActiveState();
            initCollapseToggle();
            initInstantNavigation();
            markPageAsLoaded();
        }
    } catch (error) {
        console.error('Error initializing sidebar:', error);
        // Still mark as loaded even if there's an error
        markPageAsLoaded();
    }
}

/**
 * Mark page as loaded to prevent FOUC
 */
function markPageAsLoaded() {
    try {
        // Wait a tiny bit for styles to be applied
        requestAnimationFrame(() => {
            requestAnimationFrame(() => {
                document.body.classList.add('loaded');
            });
        });
    } catch (error) {
        console.error('Error marking page as loaded:', error);
    }
}

/**
 * Initialize active state for navigation links
 */
function initActiveState() {
    try {
        // Get current page path
        const currentPath = window.location.pathname;
        
        // Find all navigation links
        const navLinks = document.querySelectorAll('#sidebar nav a');
        
        if (!navLinks || navLinks.length === 0) {
            return; // No navigation links found
        }
        
        navLinks.forEach(link => {
            if (!link) return;
            
            const href = link.getAttribute('href');
            let isActive = false;
            
            // Check if link matches current page
            if (href && href !== '#') {
                const cleanHref = href.replace(/^#/, '').replace(/^\//, '');
                
                // Direct match
                if (currentPath.includes(cleanHref)) {
                    isActive = true;
                }
                
                // Special case: Papers section includes cabinets
                if (href.includes('papers.php') && currentPath.includes('/cabinets/')) {
                    isActive = true;
                }
            }
            
            if (isActive) {
                // Add active class
                link.classList.remove('hover:bg-white/10');
                link.classList.add('bg-white/10', 'hover:bg-white/20');
            } else {
                // Remove active class
                link.classList.remove('bg-white/10', 'hover:bg-white/20');
                if (!link.classList.contains('bg-white/10')) {
                    link.classList.add('hover:bg-white/10');
                }
            }
        });
    } catch (error) {
        console.error('Error initializing active state:', error);
    }
}

/**
 * Initialize sidebar collapse/expand functionality
 */
function initCollapseToggle() {
    try {
        const sidebar = document.getElementById('sidebar');
        const collapseBtn = document.getElementById('sidebarCollapseBtn');
        const collapseIcon = collapseBtn?.querySelector('svg');
        const mainContent = document.querySelector('.flex-1');
        
        if (!sidebar || !mainContent) return;
        
        // Always prevent horizontal overflow
        sidebar.classList.add('overflow-hidden');
        
        // Check for saved collapse state
        try {
            const isCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
            if (isCollapsed) {
                sidebar.classList.add('w-16');
                sidebar.classList.remove('w-64');
                mainContent.classList.add('transition-all', 'duration-300');
                if (collapseIcon) {
                    collapseIcon.classList.add('rotate-180');
                }
                // Apply collapsed state
                toggleSidebarText(sidebar, true);
            }
        } catch (storageError) {
            console.warn('localStorage not available:', storageError);
        }
        
        // Toggle collapse on button click
        if (collapseBtn) {
            collapseBtn.addEventListener('click', () => {
                const currentlyCollapsed = sidebar.classList.contains('w-16');
                
                if (currentlyCollapsed) {
                    // Expand
                    sidebar.classList.remove('w-16');
                    sidebar.classList.add('w-64');
                    try {
                        localStorage.setItem('sidebarCollapsed', 'false');
                    } catch (e) {
                        console.warn('Could not save to localStorage:', e);
                    }
                    if (collapseIcon) {
                        collapseIcon.classList.remove('rotate-180');
                    }
                    toggleSidebarText(sidebar, false);
                } else {
                    // Collapse
                    sidebar.classList.remove('w-64');
                    sidebar.classList.add('w-16');
                    try {
                        localStorage.setItem('sidebarCollapsed', 'true');
                    } catch (e) {
                        console.warn('Could not save to localStorage:', e);
                    }
                    if (collapseIcon) {
                        collapseIcon.classList.add('rotate-180');
                    }
                    toggleSidebarText(sidebar, true);
                }
            });
        }
    } catch (error) {
        console.error('Error initializing collapse toggle:', error);
    }
}

/**
 * Toggle text visibility in sidebar when collapsed
 */
function toggleSidebarText(sidebar, isCollapsed) {
    try {
        if (!sidebar) return;
        
        const textElements = sidebar.querySelectorAll('span, p, h1, h2, h3, h4, h5, h6');
        const navItems = sidebar.querySelectorAll('nav li');
        const sidebarHeaderContent = sidebar.querySelector('.p-6 > div:first-child');
        const sidebarHeader = sidebar.querySelector('.p-6.border-b');
        const collapseBtn = document.getElementById('sidebarCollapseBtn');
        const logoutBtn = document.getElementById('logoutBtn');
        
        // Hide/show text elements
        textElements.forEach(element => {
            if (!element) return;
            // Skip collapse button SVG
            if (element.closest('#sidebarCollapseBtn')) return;
            
            if (isCollapsed) {
                element.style.display = 'none';
            } else {
                element.style.display = '';
            }
        });
        
        // Center icons when collapsed
        navItems.forEach(item => {
            if (!item) return;
            const link = item.querySelector('a');
            if (link) {
                if (isCollapsed) {
                    link.classList.add('justify-center');
                    link.classList.remove('gap-3');
                } else {
                    link.classList.remove('justify-center');
                    link.classList.add('gap-3');
                }
            }
        });
        
        // Hide/show sidebar header content when collapsed
        if (sidebarHeaderContent) {
            if (isCollapsed) {
                sidebarHeaderContent.style.display = 'none';
            } else {
                sidebarHeaderContent.style.display = '';
            }
        }
        
        // Adjust sidebar header container to center collapse button when collapsed
        if (sidebarHeader) {
            if (isCollapsed) {
                sidebarHeader.classList.remove('justify-between');
                sidebarHeader.classList.add('justify-center');
                if (collapseBtn) {
                    collapseBtn.classList.remove('p-2');
                    collapseBtn.classList.add('mx-auto');
                }
            } else {
                sidebarHeader.classList.remove('justify-center');
                sidebarHeader.classList.add('justify-between');
                if (collapseBtn) {
                    collapseBtn.classList.remove('mx-auto');
                    collapseBtn.classList.add('p-2');
                }
            }
        }
        
        // Adjust logout button to center icon when collapsed
        if (logoutBtn) {
            if (isCollapsed) {
                logoutBtn.classList.remove('gap-3', 'text-left');
                logoutBtn.classList.add('justify-center');
            } else {
                logoutBtn.classList.remove('justify-center');
                logoutBtn.classList.add('gap-3', 'text-left');
            }
        }
    } catch (error) {
        console.error('Error toggling sidebar text:', error);
    }
}

/**
 * Initialize instant navigation
 */
function initInstantNavigation() {
    try {
        const navLinks = document.querySelectorAll('#sidebar nav a');
        
        if (!navLinks || navLinks.length === 0) return;
        
        navLinks.forEach(link => {
            if (!link) return;
            
            const href = link.getAttribute('href');
            
            // Only handle internal navigation links
            if (href && href !== '#' && !href.startsWith('http') && !href.startsWith('mailto:')) {
                link.addEventListener('click', (e) => {
                    try {
                        // Don't prevent default if it's the current page
                        const currentPath = window.location.pathname;
                        if (currentPath.includes(href.replace(/^#/, '').replace(/^\//, ''))) {
                            return; // Already on this page
                        }
                        
                        // Save navigation state to localStorage before navigation
                        saveNavigationState(href);
                        
                        // Let browser handle navigation instantly - no preventDefault, no delays
                        // Navigation happens instantly without any loading overlays or transitions
                    } catch (error) {
                        console.error('Error handling navigation click:', error);
                    }
                });
            }
        });
    } catch (error) {
        console.error('Error initializing instant navigation:', error);
    }
}

/**
 * Save navigation state before navigating
 */
function saveNavigationState(href) {
    try {
        // Save current scroll position
        const scrollPosition = window.scrollY || window.pageYOffset || 0;
        localStorage.setItem('lastScrollPosition', scrollPosition.toString());
        
        // Save current page
        localStorage.setItem('lastPage', window.location.pathname);
        
        // Save target page for future reference
        if (href) {
            localStorage.setItem('lastTargetPage', href);
        }
    } catch (error) {
        console.warn('Could not save navigation state:', error);
    }
}

/**
 * Restore navigation state after page load
 */
export function restoreNavigationState() {
    try {
        const lastScrollPosition = localStorage.getItem('lastScrollPosition');
        const lastPage = localStorage.getItem('lastPage');
        const currentPage = window.location.pathname;
        
        // Only restore scroll if we're on the same page (back/forward navigation)
        if (lastPage === currentPage && lastScrollPosition) {
            // Restore scroll position after page loads
            setTimeout(() => {
                try {
                    window.scrollTo(0, parseInt(lastScrollPosition) || 0);
                } catch (scrollError) {
                    console.warn('Could not restore scroll position:', scrollError);
                }
            }, 100);
        }
    } catch (error) {
        console.warn('Could not restore navigation state:', error);
    }
}
