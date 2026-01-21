import { initSidebar } from '../modules/sidebar.js';
import { updateDashboardStats } from '../modules/dashboard_charts.js';

/**
 * Dashboard functionality
 * Handles theme toggle, profile dropdown, mobile menu, and logout
 */

/**
 * Initialize theme toggle functionality (removed - using light mode only)
 */
export function initThemeToggle() {
    // Theme toggle removed - using maroon and white color scheme only
}

/**
 * Initialize profile dropdown functionality
 */
export function initProfileDropdown() {
    const profileBtn = document.getElementById('profileBtn');
    const profileDropdown = document.getElementById('profileDropdown');

    if (!profileBtn || !profileDropdown) return;

    profileBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        profileDropdown.classList.toggle('hidden');
    });

    // Close dropdown when clicking outside
    document.addEventListener('click', (e) => {
        if (!profileBtn.contains(e.target) && !profileDropdown.contains(e.target)) {
            profileDropdown.classList.add('hidden');
        }
    });
}

/**
 * Initialize mobile menu toggle (if needed in future)
 * Currently using Tailwind responsive classes
 */
export function initMobileMenu() {
    // Mobile menu functionality can be added here if needed
    // Currently handled by Tailwind CSS responsive classes
}

/**
 * Initialize logout functionality
 */
export function initLogout() {
    const logoutBtn = document.getElementById('logoutBtn');
    const profileLogoutBtn = document.getElementById('profileLogoutBtn');

    const handleLogout = async () => {
        const result = await Swal.fire({
            icon: 'question',
            title: 'Logout?',
            text: 'Are you sure you want to logout?',
            showCancelButton: true,
            confirmButtonColor: '#800000',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Yes, logout',
            cancelButtonText: 'Cancel'
        });

        if (result.isConfirmed) {
            // Call logout API to destroy session
            try {
                const response = await fetch('/OSAS-SIS/backend/CMS/logout.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    }
                });

                // Redirect to login page
                window.location.href = '../../index.php';
            } catch (error) {
                // Even if API call fails, redirect to login
                window.location.href = '../../index.php';
            }
        }
    };

    if (logoutBtn) logoutBtn.addEventListener('click', handleLogout);
    if (profileLogoutBtn) profileLogoutBtn.addEventListener('click', handleLogout);
}

/**
 * Load dashboard statistics from API
 */
async function loadDashboardStats() {
    try {
        const response = await fetch('/OSAS-SIS/backend/CMS/api/dashboard_stats.php');
        const result = await response.json();

        if (result.success && result.data) {
            updateDashboardStats(result.data);
            // Load recent documents
            if (result.data.recent_documents) {
                renderRecentDocuments(result.data.recent_documents);
            }
        } else {
            console.error('Failed to load dashboard stats:', result.message);
        }
    } catch (error) {
        console.error('Error loading dashboard stats:', error);
    }
}

/**
 * Render recent documents in the table
 * @param {Array} documents - Array of document objects (max 4)
 */
function renderRecentDocuments(documents) {
    const tableBody = document.getElementById('recentDocumentsTableBody');
    if (!tableBody) return;

    // Clear existing rows
    tableBody.innerHTML = '';

    if (!documents || documents.length === 0) {
        tableBody.innerHTML = `
            <tr>
                <td colspan="5" class="px-4 py-8 text-center text-sm text-gray-500">
                    No recent documents found.
                </td>
            </tr>
        `;
        return;
    }

    // Render up to 4 most recent documents (they're already sorted by created_at DESC from backend)
    documents.slice(0, 4).forEach(doc => {
        // Category badge color
        let categoryBadgeClass = 'bg-purple-100 text-purple-800'; // Default for Documents
        if (doc.category === 'Sports') {
            categoryBadgeClass = 'bg-blue-100 text-blue-800';
        } else if (doc.category === 'Objects') {
            categoryBadgeClass = 'bg-orange-100 text-orange-800';
        }

        // Status badge styling
        let statusBadgeClass = 'bg-green-100 text-green-800';
        let statusText = 'Available';
        if (doc.status === 'borrowed') {
            statusBadgeClass = 'bg-yellow-100 text-yellow-800';
            statusText = 'Borrowed';
        } else if (doc.status === 'archived') {
            statusBadgeClass = 'bg-gray-100 text-gray-800';
            statusText = 'Archived';
        }

        // Format date
        const createdDate = new Date(doc.created_at);
        const formattedDate = createdDate.toLocaleDateString('en-US', {
            year: 'numeric',
            month: '2-digit',
            day: '2-digit'
        });

        // Determine type - use category or default to "Document"
        const documentType = doc.category || 'Document';

        const row = document.createElement('tr');
        row.className = 'hover:bg-gray-50';
        row.innerHTML = `
            <td class="px-4 py-3 text-sm text-gray-800 font-medium">${doc.filename || 'Unnamed Document'}</td>
            <td class="px-4 py-3 text-sm text-gray-600">${documentType}</td>
            <td class="px-4 py-3">
                <span class="px-2 py-1 text-xs rounded-full ${categoryBadgeClass}">${doc.category || 'Documents'}</span>
            </td>
            <td class="px-4 py-3 text-sm text-gray-600">${formattedDate}</td>
            <td class="px-4 py-3">
                <span class="px-2 py-1 text-xs rounded-full ${statusBadgeClass}">${statusText}</span>
            </td>
        `;

        tableBody.appendChild(row);
    });
}

/**
 * Initialize Add Document button - shows info modal with timeout
 */
function initAddDocumentButton() {
    const addDocumentBtn = document.getElementById('addDocumentBtn');
    if (addDocumentBtn) {
        addDocumentBtn.addEventListener('click', async () => {
            const result = await Swal.fire({
                icon: 'info',
                title: 'Select a Cabinet First',
                text: 'Please navigate to the Supplies/Equipment page and select a cabinet where you want to add the document.',
                confirmButtonText: 'Go to Supplies/Equipment',
                confirmButtonColor: '#800000',
                showCancelButton: true,
                cancelButtonText: 'Cancel',
                cancelButtonColor: '#6b7280',
                timer: 3000,
                timerProgressBar: true,
                allowOutsideClick: false
            });

            // If user clicks "Go to Papers" or timer expires, navigate
            if (result.isConfirmed || result.dismiss === Swal.DismissReason.timer) {
                window.location.href = '/OSAS-SIS/frontend/CMS/pages/papers.php';
            }
        });
    }
}

/**
 * Initialize search functionality for documents table
 */
function initSearchFunctionality() {
    const searchInput = document.getElementById('dashboardSearchInput');
    if (!searchInput) return;

    let searchTimeout;

    searchInput.addEventListener('input', (e) => {
        const searchTerm = e.target.value.trim().toLowerCase();

        // Clear previous timeout
        clearTimeout(searchTimeout);

        // Debounce search (wait 300ms after user stops typing)
        searchTimeout = setTimeout(() => {
            performSearch(searchTerm);
        }, 300);
    });
}

/**
 * Perform search on documents table
 * @param {string} searchTerm - Search term (lowercase)
 */
function performSearch(searchTerm) {
    const tableBody = document.getElementById('recentDocumentsTableBody');
    if (!tableBody) return;

    const rows = tableBody.querySelectorAll('tr');
    let visibleCount = 0;

    // Remove any existing empty search state
    const existingEmptyRow = tableBody.querySelector('tr.empty-search-state');
    if (existingEmptyRow) {
        existingEmptyRow.remove();
    }

    rows.forEach(row => {
        // Skip empty state rows (both the default one and search empty state)
        if (row.querySelector('td[colspan]') || row.classList.contains('empty-search-state')) {
            return;
        }

        // Get all text content from the row
        const cells = row.querySelectorAll('td');
        let rowText = '';

        cells.forEach(cell => {
            rowText += cell.textContent.trim().toLowerCase() + ' ';
        });

        // Show row if search term is empty or matches any cell content
        if (!searchTerm || rowText.includes(searchTerm)) {
            row.classList.remove('hidden');
            visibleCount++;
        } else {
            row.classList.add('hidden');
        }
    });

    // Show empty state if no results and search term is provided
    if (visibleCount === 0 && searchTerm) {
        const emptyRow = document.createElement('tr');
        emptyRow.className = 'empty-search-state';
        emptyRow.innerHTML = `
                <td colspan="5" class="px-4 py-8 text-center text-sm text-gray-500">
                    No documents found matching "${searchTerm}".
                </td>
            `;
        tableBody.appendChild(emptyRow);
    }
}


/**
 * Initialize all dashboard functionality
 */
export function initDashboard() {
    initThemeToggle();
    initProfileDropdown();
    initMobileMenu();
    initLogout();
    initSidebar();
    initAddDocumentButton();
    initSearchFunctionality();

    // Load dashboard statistics
    loadDashboardStats();
}
