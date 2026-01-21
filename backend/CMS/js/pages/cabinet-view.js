import { initSidebar } from '../modules/sidebar.js';
import { initProfileDropdown, initLogout } from './dashboard.js';

/**
 * Cabinet View page functionality
 * Handles document table, add document modal, and cabinet-specific features
 */

/**
 * Initialize cabinet view page
 */
export function initCabinetView() {
    // Initialize sidebar with collapse functionality
    initSidebar();

    // Initialize profile dropdown and logout
    initProfileDropdown();
    initLogout();

    // Initialize document management
    initDocumentManagement();

    // Initialize all filter dropdowns
    initCabinetNumberSort();
    initCategorySort();
    initOsasServiceSort();
    initStatusSort();

    // Initialize search functionality
    initSearchFunctionality();

    // Load documents from API based on query parameter
    loadDocumentsFromQuery();
}

/**
 * Load documents from API based on URL query parameter
 */
async function loadDocumentsFromQuery() {
    // Get cabinet_id from URL query parameter
    const urlParams = new URLSearchParams(window.location.search);
    const cabinetId = urlParams.get('cabinet_id');

    if (cabinetId) {
        const numericCabinetId = parseInt(cabinetId, 10);
        if (!isNaN(numericCabinetId)) {
            // Fetch cabinet info to display cabinet name
            try {
                const cabinetResponse = await fetch('/OSAS-SIS/backend/CMS/api/cabinets.php');
                const cabinetResult = await cabinetResponse.json();

                if (cabinetResult.success && cabinetResult.data) {
                    const cabinet = cabinetResult.data.find(c => c.id === numericCabinetId);
                    if (cabinet) {
                        // Update page title
                        const pageTitle = document.querySelector('title');
                        if (pageTitle) {
                            pageTitle.textContent = `${cabinet.name || 'Cabinet ' + numericCabinetId} - DSA Project`;
                        }

                        // Update cabinet view title if it exists (for view.php)
                        const cabinetViewTitle = document.getElementById('cabinetViewTitle');
                        if (cabinetViewTitle) {
                            cabinetViewTitle.textContent = cabinet.name || 'Cabinet ' + numericCabinetId;
                        }
                    }
                }
            } catch (error) {
                console.error('Error fetching cabinet info:', error);
            }
            await reloadDocumentsForCabinet(numericCabinetId);

            // If a specific file_id is provided, highlight that file once documents are loaded
            const fileIdParam = urlParams.get('file_id');
            if (fileIdParam) {
                const fileId = parseInt(fileIdParam, 10);
                if (!isNaN(fileId)) {
                    // Slight delay to ensure table rows are rendered
                    setTimeout(() => {
                        highlightFileRow(fileId);
                    }, 300);
                }
            }
        }
    }
}

/**
 * Initialize document management functionality
 */
export function initDocumentManagement() {
    const addDocumentBtn = document.getElementById('addDocumentBtn');

    if (addDocumentBtn) {
        addDocumentBtn.addEventListener('click', () => {
            showAddDocumentModal();
        });
    }

    const exportBtn = document.getElementById('exportTableBtn');
    if (exportBtn) {
        exportBtn.addEventListener('click', exportTableToPDF);
    }

    const bulkAddBtn = document.getElementById('bulkAddBtn');
    if (bulkAddBtn) {
        console.log('Bulk Add button found, attaching listener');
        bulkAddBtn.addEventListener('click', () => {
            console.log('Bulk Add button clicked');
            try {
                if (typeof showBulkAddModal === 'function') {
                    showBulkAddModal();
                } else {
                    console.error('showBulkAddModal is not defined');
                    // Fallback or alert
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Bulk Add feature is currently unavailable (Function not found). Please refresh the page.'
                    });
                }
            } catch (e) {
                console.error('Error opening bulk add modal:', e);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'An error occurred while opening the modal: ' + e.message
                });
            }
        });
    } else {
        console.warn('Bulk Add button NOT found');
    }

    // Initialize document count
    updateDocumentCount();
}

// Store current filter state
let currentFilters = {
    cabinetNumber: 'all',
    category: 'all',
    osasService: 'all',
    status: 'all',
    searchTerm: ''
};

/**
 * Initialize cabinet number sort dropdown functionality
 * This allows sorting/filtering documents by cabinet number in the cabinet view page
 */
function initCabinetNumberSort() {
    const sortBtn = document.getElementById('cabinetNumberSortBtn');
    const sortDropdown = document.getElementById('cabinetNumberSortDropdown');
    const sortText = document.getElementById('cabinetNumberSortText');

    if (!sortBtn || !sortDropdown) return;

    // Toggle dropdown
    sortBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        sortDropdown.classList.toggle('hidden');

        // Close other dropdowns
        document.getElementById('categorySortDropdown')?.classList.add('hidden');
        document.getElementById('statusSortDropdown')?.classList.add('hidden');
    });

    // Handle cabinet number selection with event delegation
    sortDropdown.addEventListener('click', (e) => {
        const option = e.target.closest('button[data-cabinet-number]');
        if (!option) return;

        e.stopPropagation();
        const cabinetNumber = option.getAttribute('data-cabinet-number');
        const optionText = option.textContent.trim();

        // Update button text
        if (sortText) {
            sortText.textContent = cabinetNumber === 'all' ? 'All Numbers' : optionText;
        }

        // Close dropdown
        sortDropdown.classList.add('hidden');

        // Update filter state
        currentFilters.cabinetNumber = cabinetNumber;

        // Apply all filters
        applyAllFilters();
    });

    // Close dropdown when clicking outside
    document.addEventListener('click', (e) => {
        if (sortBtn && !sortBtn.contains(e.target) && sortDropdown && !sortDropdown.contains(e.target)) {
            sortDropdown.classList.add('hidden');
        }
    });
}

/**
 * Initialize category sort dropdown functionality
 */
function initCategorySort() {
    const sortBtn = document.getElementById('categorySortBtn');
    const sortDropdown = document.getElementById('categorySortDropdown');
    const sortText = document.getElementById('categorySortText');

    if (!sortBtn || !sortDropdown) return;

    // Toggle dropdown
    sortBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        sortDropdown.classList.toggle('hidden');

        // Close other dropdowns
        document.getElementById('cabinetNumberSortDropdown')?.classList.add('hidden');
        document.getElementById('statusSortDropdown')?.classList.add('hidden');
    });

    // Handle category selection with event delegation
    sortDropdown.addEventListener('click', (e) => {
        const option = e.target.closest('button[data-category]');
        if (!option) return;

        e.stopPropagation();
        const category = option.getAttribute('data-category');
        const optionText = option.textContent.trim();

        // Update button text
        if (sortText) {
            sortText.textContent = category === 'all' ? 'All Categories' : optionText;
        }

        // Close dropdown
        sortDropdown.classList.add('hidden');

        // Update filter state
        currentFilters.category = category;

        // Apply all filters
        applyAllFilters();
    });

    // Close dropdown when clicking outside
    document.addEventListener('click', (e) => {
        if (sortBtn && !sortBtn.contains(e.target) && sortDropdown && !sortDropdown.contains(e.target)) {
            sortDropdown.classList.add('hidden');
        }
    });
}

/**
 * Initialize OSAS Service sort dropdown functionality
 */
function initOsasServiceSort() {
    const sortBtn = document.getElementById('osasServiceSortBtn');
    const sortDropdown = document.getElementById('osasServiceSortDropdown');
    const sortText = document.getElementById('osasServiceSortText');

    if (!sortBtn || !sortDropdown) return;

    // Toggle dropdown
    sortBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        sortDropdown.classList.toggle('hidden');

        // Close other dropdowns
        document.getElementById('cabinetNumberSortDropdown')?.classList.add('hidden');
        document.getElementById('categorySortDropdown')?.classList.add('hidden');
        document.getElementById('statusSortDropdown')?.classList.add('hidden');
    });

    // Handle service selection with event delegation
    sortDropdown.addEventListener('click', (e) => {
        const option = e.target.closest('button[data-osas-service]');
        if (!option) return;

        e.stopPropagation();
        const service = option.getAttribute('data-osas-service');
        const optionText = option.textContent.trim();

        // Update button text - Truncate if too long for the button
        if (sortText) {
            sortText.textContent = service === 'all' ? 'All Services' : (optionText.length > 20 ? optionText.substring(0, 20) + '...' : optionText);
        }

        // Close dropdown
        sortDropdown.classList.add('hidden');

        // Update filter state
        currentFilters.osasService = service;

        // Apply all filters
        applyAllFilters();
    });

    // Close dropdown when clicking outside
    document.addEventListener('click', (e) => {
        if (sortBtn && !sortBtn.contains(e.target) && sortDropdown && !sortDropdown.contains(e.target)) {
            sortDropdown.classList.add('hidden');
        }
    });
}


/**
 * Initialize status sort dropdown functionality
 */
function initStatusSort() {
    const sortBtn = document.getElementById('statusSortBtn');
    const sortDropdown = document.getElementById('statusSortDropdown');
    const sortText = document.getElementById('statusSortText');

    if (!sortBtn || !sortDropdown) return;

    // Toggle dropdown
    sortBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        sortDropdown.classList.toggle('hidden');

        // Close other dropdowns
        document.getElementById('cabinetNumberSortDropdown')?.classList.add('hidden');
        document.getElementById('categorySortDropdown')?.classList.add('hidden');
        document.getElementById('osasServiceSortDropdown')?.classList.add('hidden');
    });

    // Handle status selection
    const statusOptions = sortDropdown.querySelectorAll('button[data-status]');
    statusOptions.forEach(option => {
        option.addEventListener('click', async (e) => {
            e.stopPropagation();
            const status = option.getAttribute('data-status');
            const optionText = option.textContent.trim();

            // Update button text
            if (sortText) {
                sortText.textContent = status === 'all' ? 'All Status' : optionText;
            }

            // Close dropdown
            sortDropdown.classList.add('hidden');

            // Update filter state
            currentFilters.status = status;

            // Determine current cabinet from URL
            const urlParams = new URLSearchParams(window.location.search);
            const cabinetIdParam = urlParams.get('cabinet_id');

            if (cabinetIdParam) {
                const cabinetId = parseInt(cabinetIdParam, 10);

                if (status === 'all') {
                    // Default: load all non-archived files
                    await reloadDocumentsForCabinet(cabinetId);
                } else if (['available', 'borrowed', 'archived'].includes(status)) {
                    // Load files for specific status from API (including archived)
                    await reloadDocumentsForCabinet(cabinetId, status);
                }
            }

            // Apply remaining client-side filters (cabinet number, category, search)
            applyAllFilters();
        });
    });

    // Close dropdown when clicking outside
    document.addEventListener('click', (e) => {
        if (sortBtn && !sortBtn.contains(e.target) && sortDropdown && !sortDropdown.contains(e.target)) {
            sortDropdown.classList.add('hidden');
        }
    });
}

/**
 * Apply all filters (cabinet number, category, status, search) to the documents table
 * Note: status filter 'archived' relies on server reload via reloadDocumentsForCabinet
 */
function applyAllFilters() {
    const tableBody = document.getElementById('documentsTableBody');
    if (!tableBody) return;

    const rows = tableBody.querySelectorAll('tr:not(#emptyStateRow)');
    const emptyStateRow = document.getElementById('emptyStateRow');

    let visibleCount = 0;

    rows.forEach(row => {
        let isVisible = true;

        // Filter by cabinet number
        if (currentFilters.cabinetNumber !== 'all') {
            const cabinetNumberCell = row.querySelector('td:nth-child(2)');
            if (cabinetNumberCell) {
                const cellText = cabinetNumberCell.textContent.trim();
                if (cellText !== currentFilters.cabinetNumber) {
                    isVisible = false;
                }
            }
        }

        // Filter by category
        if (isVisible && currentFilters.category !== 'all') {
            const categoryCell = row.querySelector('td:nth-child(4)');
            if (categoryCell) {
                const categoryText = categoryCell.textContent.trim().toLowerCase();
                if (categoryText !== currentFilters.category.toLowerCase()) {
                    isVisible = false;
                }
            }
        }

        // Filter by OSAS Service
        if (isVisible && currentFilters.osasService !== 'all') {
            const serviceCell = row.querySelector('td:nth-child(5)');
            if (serviceCell) {
                // Determine text content (handle div truncation wrapper if present)
                let serviceText = serviceCell.textContent.trim();
                const innerDiv = serviceCell.querySelector('div');
                if (innerDiv) {
                    serviceText = innerDiv.textContent.trim();
                }

                if (serviceText !== currentFilters.osasService && serviceText !== 'N/A') {
                    // Check strict equality or if service is actually N/A when we wanted something else
                    if (currentFilters.osasService === 'N/A') {
                        if (serviceText !== 'N/A') isVisible = false;
                    } else {
                        if (serviceText !== currentFilters.osasService) isVisible = false;
                    }
                } else if (serviceText === 'N/A' && currentFilters.osasService !== 'N/A' && currentFilters.osasService !== 'all') {
                    isVisible = false;
                }
            }
        }

        // Filter by status
        if (isVisible && currentFilters.status !== 'all') {
            const statusCell = row.querySelector('td:nth-child(6)');
            if (statusCell) {
                const statusText = statusCell.textContent.trim().toLowerCase();
                if (statusText !== currentFilters.status.toLowerCase()) {
                    isVisible = false;
                }
            }
        }

        // Filter by search term
        if (isVisible && currentFilters.searchTerm) {
            const fileNameCell = row.querySelector('td:nth-child(3)');
            const categoryCell = row.querySelector('td:nth-child(4)');

            if (fileNameCell && categoryCell) {
                const fileName = fileNameCell.textContent.toLowerCase();
                const category = categoryCell.textContent.toLowerCase();

                if (!fileName.includes(currentFilters.searchTerm) && !category.includes(currentFilters.searchTerm)) {
                    isVisible = false;
                }
            }
        }

        // Show or hide row
        if (isVisible) {
            row.classList.remove('hidden');
            visibleCount++;
        } else {
            row.classList.add('hidden');
        }
    });

    // Show/hide empty state
    if (emptyStateRow) {
        if (visibleCount === 0) {
            emptyStateRow.classList.remove('hidden');
        } else {
            emptyStateRow.classList.add('hidden');
        }
    }

    // Update row numbers after filtering
    updateRowNumbersAfterSort();

    // Update document count
    updateDocumentCount();
}

/**
 * Update row numbers after sorting/filtering
 */
function updateRowNumbersAfterSort() {
    const tableBody = document.getElementById('documentsTableBody');
    if (!tableBody) return;

    // Get only visible rows (excluding empty state row)
    const visibleRows = Array.from(tableBody.querySelectorAll('tr:not(#emptyStateRow)'))
        .filter(row => !row.classList.contains('hidden'));

    visibleRows.forEach((row, index) => {
        const firstCell = row.querySelector('td:first-child');
        if (firstCell) {
            firstCell.textContent = index + 1;
        }
    });
}

/**
 * Show Add Document modal using SweetAlert2
 */
export function showAddDocumentModal() {
    Swal.fire({
        title: 'Add New Document',
        html: `
            <form id="addDocumentForm" class="text-left">
                <div class="mb-2">
                    <label class="block text-xs font-semibold text-gray-700 mb-1">Document Name</label>
                    <input 
                        type="text" 
                        id="documentName" 
                        name="documentName" 
                        class="w-full px-3 py-1.5 text-xs border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#800000] focus:border-[#800000] outline-none"
                        placeholder="Enter name"
                        required
                    />
                </div>
                <div class="mb-2">
                    <label class="block text-xs font-semibold text-gray-700 mb-1">Cabinet Number</label>
                    <input 
                        type="text" 
                        id="cabinetNumberInput" 
                        name="cabinetNumber" 
                        class="w-full px-3 py-1.5 text-xs border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#800000] focus:border-[#800000] outline-none"
                        placeholder="e.g. C1.1"
                        required
                    />
                </div>
                <div class="mb-2">
                    <label class="block text-xs font-semibold text-gray-700 mb-1">Category</label>
                    <select 
                        id="documentCategory" 
                        name="documentCategory" 
                        class="w-full px-3 py-1.5 text-xs border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#800000] focus:border-[#800000] outline-none"
                        required
                    >
                        <option value="">Select category</option>
                        <option value="Documents">Documents</option>
                        <option value="Sports">Sports</option>
                        <option value="Objects">Objects</option>
                    </select>
                </div>
                <div class="mb-2">
                    <label class="block text-xs font-semibold text-gray-700 mb-1">OSAS Service</label>
                    <select 
                        id="osasService" 
                        name="osasService" 
                        class="w-full px-3 py-1.5 text-xs border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#800000] focus:border-[#800000] outline-none"
                    >
                        <option value="">Select Service</option>
                        <option value="Guidance and service counseling services">Guidance and service counseling services</option>
                        <option value="Student Organization">Student Organization</option>
                        <option value="Scholarship and Financial Assistance">Scholarship and Financial Assistance</option>
                        <option value="Health Service">Health Service</option>
                        <option value="Culture and Art Program">Culture and Art Program</option>
                        <option value="Sports Development Program">Sports Development Program</option>
                        <option value="Safety and Security Services">Safety and Security Services</option>
                        <option value="Student Housing and Residential Services">Student Housing and Residential Services</option>
                        <option value="Social and Community Involvement Programs">Social and Community Involvement Programs</option>
                    </select>
                </div>
                <div class="mb-2">
                    <label class="block text-xs font-semibold text-gray-700 mb-1">Status</label>
                    <select 
                        id="documentStatus" 
                        name="documentStatus" 
                        class="w-full px-3 py-1.5 text-xs border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#800000] focus:border-[#800000] outline-none"
                        required
                    >
                        <option value="available">Available</option>
                        <option value="borrowed">Borrowed</option>
                    </select>
                </div>
            </form>
        `,
        showCancelButton: true,
        confirmButtonText: 'Add Document',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#800000',
        cancelButtonColor: '#6b7280',
        width: '450px',
        didOpen: () => {
            // Focus on first input
            const firstInput = document.getElementById('documentName');
            if (firstInput) {
                firstInput.focus();
            }
        },
        preConfirm: () => {
            const name = document.getElementById('documentName')?.value;
            const cabinetNumber = document.getElementById('cabinetNumberInput')?.value;
            const category = document.getElementById('documentCategory')?.value;
            const osasService = document.getElementById('osasService')?.value;
            const description = document.getElementById('documentDescription')?.value;
            const status = document.getElementById('documentStatus')?.value;

            if (!name || !cabinetNumber || !category || !status) {
                Swal.showValidationMessage('Please fill in all required fields');
                return false;
            }

            return {
                name,
                cabinetNumber: cabinetNumber.trim(),
                name,
                cabinetNumber: cabinetNumber.trim(),
                category,
                osas_service: osasService || '',
                description: description || '',
                status
            };
        }
    }).then(async (result) => {
        if (result.isConfirmed && result.value) {
            // Get current cabinet ID using multiple methods for reliability
            let cabinetId = null;

            // Method 1: Check URL parameter (most reliable - set when cabinet is selected)
            const urlParams = new URLSearchParams(window.location.search);
            const cabinetIdFromUrl = urlParams.get('cabinet_id');
            if (cabinetIdFromUrl) {
                cabinetId = parseInt(cabinetIdFromUrl, 10);
            }

            // Method 2: Get from cabinet dropdown option's data-cabinet-id attribute
            if (!cabinetId) {
                const cabinetDropdownText = document.getElementById('cabinetDropdownText');
                const cabinetDropdown = document.getElementById('cabinetDropdown');

                if (cabinetDropdownText && cabinetDropdown &&
                    cabinetDropdownText.textContent.trim() !== 'Select Cabinet' &&
                    cabinetDropdownText.textContent.trim() !== 'All Cabinets') {

                    const selectedText = cabinetDropdownText.textContent.trim();

                    // Find the matching option in the dropdown
                    const options = cabinetDropdown.querySelectorAll('button[data-cabinet-id]');
                    for (const option of options) {
                        if (option.textContent.trim() === selectedText) {
                            cabinetId = parseInt(option.getAttribute('data-cabinet-id'), 10);
                            break;
                        }
                    }
                }
            }

            // Method 3: Try to get from selectedCabinetName and match with dropdown
            if (!cabinetId) {
                const selectedCabinetName = document.getElementById('selectedCabinetName');
                const cabinetDropdown = document.getElementById('cabinetDropdown');

                if (selectedCabinetName && cabinetDropdown) {
                    const selectedText = selectedCabinetName.textContent.trim();

                    // Find the matching option in the dropdown
                    const options = cabinetDropdown.querySelectorAll('button[data-cabinet-id]');
                    for (const option of options) {
                        if (option.textContent.trim() === selectedText) {
                            cabinetId = parseInt(option.getAttribute('data-cabinet-id'), 10);
                            break;
                        }
                    }
                }
            }

            if (!cabinetId) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Unable to determine cabinet. Please select a cabinet first.',
                    confirmButtonColor: '#800000'
                });
                return;
            }

            try {
                // POST to CMS Files API
                const response = await fetch('/OSAS-SIS/backend/CMS/api/files.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        cabinet_id: parseInt(cabinetId, 10),
                        cabinet_number: result.value.cabinetNumber,
                        filename: result.value.name,
                        description: result.value.description || null,
                        category: result.value.category,
                        osas_service: result.value.osas_service,
                        status: result.value.status.toLowerCase()
                    })
                });

                const apiResult = await response.json();

                if (apiResult.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Document Added!',
                        text: `"${result.value.name}" has been added successfully.`,
                        confirmButtonColor: '#800000',
                        timer: 2000,
                        showConfirmButton: false
                    });

                    // Reload documents for current cabinet
                    // Trigger custom event that papers.js listens to, or reload directly
                    const event = new CustomEvent('documentAdded', { detail: { cabinetId: parseInt(cabinetId, 10) } });
                    window.dispatchEvent(event);

                    // Also reload documents directly
                    await reloadDocumentsForCabinet(parseInt(cabinetId, 10));
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: apiResult.message || 'Failed to add document',
                        confirmButtonColor: '#800000'
                    });
                }
            } catch (error) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Failed to add document: ' + error.message,
                    confirmButtonColor: '#800000'
                });
            }
        }
    });
}

/**
 * Add document to the table
 * @param {Object} docData - Document data object containing name, cabinetNumber, description, status
 */
export function addDocumentToTable(docData) {
    const tableBody = document.getElementById('documentsTableBody');
    const emptyStateRow = document.getElementById('emptyStateRow');

    if (!tableBody) return;

    // Hide empty state if it exists
    if (emptyStateRow) {
        emptyStateRow.classList.add('hidden');
    }

    // Create new row
    const row = document.createElement('tr');
    row.className = 'hover:bg-gray-50';

    // Format date
    const today = new Date();
    const dateStr = today.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });

    // Status badge color
    let statusBadgeClass = 'bg-green-100 text-green-800';
    if (docData.status === 'Borrowed') {
        statusBadgeClass = 'bg-yellow-100 text-yellow-800';
    } else if (docData.status === 'Archived') {
        statusBadgeClass = 'bg-gray-100 text-gray-800';
    }

    // Get current row count for numbering
    const existingRows = tableBody.querySelectorAll('tr:not(#emptyStateRow)');
    const rowNumber = existingRows.length + 1;

    // Map cabinet name to cabinet prefix (C1, C2, C3)
    // TODO: Backend implementation - Replace with backend API call to fetch cabinet prefix
    // Backend should return the cabinet prefix based on the selected cabinet
    // Example backend table: cabinets(id, display_name, cabinet_prefix)
    // Format: Cabinet 1 → C1, Cabinet 2 → C2, Cabinet 3 → C3
    const cabinetNameToPrefix = {
        'Cabinet 1': 'C1',
        'Cabinet 2': 'C2',
        'Cabinet 3': 'C3'
    };

    // Get cabinet prefix from URL parameter (for cabinets/view.php) or selected cabinet name (for papers.php)
    // This determines which cabinet the document belongs to (for the Cabinet Number column)
    // The prefix (C1, C2, C3) is the unique ID, and the number after the dot (1, 2, 3, ...) auto-increments
    let cabinetPrefix = 'C1'; // Default

    // First, try to get from URL parameter (for cabinet view page)
    const urlParams = new URLSearchParams(window.location.search);
    const cabinetIdFromUrl = urlParams.get('id');
    if (cabinetIdFromUrl) {
        // Extract prefix from URL (e.g., C1.1 → C1, C2.1 → C2, C3.1 → C3)
        const prefixMatch = cabinetIdFromUrl.match(/^(C\d+)\./);
        if (prefixMatch) {
            cabinetPrefix = prefixMatch[1];
        }
    } else {
        // Otherwise, try to get from selected cabinet name (for papers page)
        // Check multiple sources to ensure we get the correct cabinet
        const selectedCabinetNameEl = document.getElementById('selectedCabinetName');
        const cabinetDropdownTextEl = document.getElementById('cabinetDropdownText');

        let cabinetText = '';

        // Priority 1: Check selectedCabinetName (set when viewing a specific cabinet)
        if (selectedCabinetNameEl && selectedCabinetNameEl.textContent.trim()) {
            cabinetText = selectedCabinetNameEl.textContent.trim();
        }
        // Priority 2: Check cabinetDropdownText (set when selecting from dropdown)
        else if (cabinetDropdownTextEl && cabinetDropdownTextEl.textContent.trim() &&
            cabinetDropdownTextEl.textContent.trim() !== 'Select Cabinet' &&
            cabinetDropdownTextEl.textContent.trim() !== 'All Cabinets') {
            cabinetText = cabinetDropdownTextEl.textContent.trim();
        }

        if (cabinetText) {
            // Check if it's a cabinet name (Cabinet 1, Cabinet 2, etc.)
            if (cabinetNameToPrefix[cabinetText]) {
                cabinetPrefix = cabinetNameToPrefix[cabinetText];
            } else {
                // Try to extract cabinet prefix directly from cabinet number (C1.1 → C1)
                const match = cabinetText.match(/^(C\d+)\./);
                if (match) {
                    cabinetPrefix = match[1];
                }
            }
        }
    }

    // Count existing documents with the same cabinet prefix to determine the next number
    // TODO: Backend implementation - Count documents by cabinet prefix
    // Example SQL: SELECT MAX(CAST(SUBSTRING(cabinet_number, 4) AS UNSIGNED)) FROM documents WHERE cabinet_number LIKE 'C1.%'
    // This will determine the next document number (e.g., if C1.1, C1.2 exist, next is C1.3)
    // IMPORTANT: Only count documents that match the current cabinet prefix (C1, C2, or C3)
    let maxNumber = 0;
    existingRows.forEach(row => {
        // Skip hidden rows (they might be filtered out)
        if (row.classList.contains('hidden')) {
            return;
        }

        const cabinetNumberCell = row.querySelector('td:nth-child(2)');
        if (cabinetNumberCell) {
            const cellText = cabinetNumberCell.textContent.trim();
            // Check if this document belongs to the same cabinet prefix
            // Example: If cabinetPrefix is "C1", match "C1.1", "C1.2", "C1.3", etc.
            if (cellText.startsWith(cabinetPrefix + '.')) {
                // Extract the number after the prefix (e.g., C1.3 → 3)
                const numberMatch = cellText.match(new RegExp('^' + cabinetPrefix.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + '\\.(\\d+)'));
                if (numberMatch) {
                    const docNumber = parseInt(numberMatch[1], 10);
                    if (!isNaN(docNumber) && docNumber > maxNumber) {
                        maxNumber = docNumber;
                    }
                }
            }
        }
    });

    // Assign the next cabinet number (e.g., C1.1, C1.2, C1.3, etc.)
    // The prefix (C1, C2, C3) stays the same for the selected cabinet
    // Only the number after the dot increments: C1.1 → C1.2 → C1.3 → C1.4, etc.
    const nextNumber = maxNumber + 1;
    const cabinetNumber = `${cabinetPrefix}.${nextNumber}`;

    // Debug log (remove in production)
    console.log('Adding document:', {
        cabinetPrefix: cabinetPrefix,
        maxNumber: maxNumber,
        nextNumber: nextNumber,
        cabinetNumber: cabinetNumber
    });

    // Get category from document data (from form submission)
    const category = docData.category || 'Documents';

    // Category badge color
    let categoryBadgeClass = 'bg-purple-100 text-purple-800'; // Default for Documents
    if (category === 'Sports') {
        categoryBadgeClass = 'bg-blue-100 text-blue-800';
    } else if (category === 'Objects') {
        categoryBadgeClass = 'bg-orange-100 text-orange-800';
    }

    // TODO: Backend implementation - Sort documents by specific cabinet number
    // When a cabinet is selected from the dropdown, the backend should filter/sort documents
    // based on the cabinet number (C1.1, C1.2, C1.3) stored in the database
    // Example SQL: SELECT * FROM documents WHERE cabinet_number = 'C1.1' ORDER BY created_at DESC

    row.innerHTML = `
        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 text-center">${rowNumber}</td>
        <td class="px-6 py-4 whitespace-nowrap text-center">
            <span class="text-sm text-gray-900 font-medium">${cabinetNumber}</span>
        </td>
        <td class="px-6 py-4">
            <div class="text-sm font-medium text-gray-900" title="${docData.name}" style="max-width: 22rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                ${docData.name.length > 45 ? docData.name.substring(0, 45) + '...' : docData.name}
            </div>
            <div class="text-sm text-gray-500" title="${docData.description || ''}" style="max-width: 22rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                ${(docData.description || 'No description').length > 45 ? (docData.description || 'No description').substring(0, 45) + '...' : (docData.description || 'No description')}
            </div>
        </td>
        <td class="px-6 py-4 whitespace-nowrap text-center">
            <span class="px-2 py-1 text-xs rounded-full ${categoryBadgeClass}">${category}</span>
        </td>
        <td class="px-6 py-4 whitespace-nowrap text-center">
            <div class="text-sm text-gray-900 mx-auto" title="${docData.osas_service || 'N/A'}" style="max-width: 14rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                ${(docData.osas_service || 'N/A')}
            </div>
        </td>
        <td class="px-6 py-4 whitespace-nowrap text-center">
            <span class="px-2 py-1 text-xs rounded-full ${statusBadgeClass}">${docData.status}</span>
        </td>
        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-center">
            <div class="flex items-center justify-center gap-2">
                <button class="text-[#800000] hover:text-[#700000] hover:underline cursor-pointer view-doc-btn" data-doc-name="${docData.name}">View</button>
                <span class="text-gray-300">|</span>
                <button class="text-blue-600 hover:text-blue-800 hover:underline cursor-pointer edit-doc-btn" data-doc-name="${docData.name}">Edit</button>
                <span class="text-gray-300">|</span>
                <button class="text-red-600 hover:text-red-800 hover:underline cursor-pointer delete-doc-btn" data-doc-name="${docData.name}">Delete</button>
            </div>
        </td>
    `;

    // Insert at the beginning of the table
    tableBody.insertBefore(row, tableBody.firstChild);

    // Add event listeners for action buttons
    const deleteBtn = row.querySelector('.delete-doc-btn');
    if (deleteBtn) {
        deleteBtn.addEventListener('click', () => {
            deleteDocument(row, docData.name);
        });
    }

    // Update document count
    updateDocumentCount();

    // Update Cabinet Number filter dropdown to include the new document
    // This ensures the dropdown shows all existing cabinet numbers
    if (typeof populateCabinetNumberFilter === 'function') {
        populateCabinetNumberFilter();
    }
}

/**
 * Reload documents for a specific cabinet
 * @param {number} cabinetId - Cabinet ID
 * @param {string|null} status - Optional status filter (available, borrowed, archived)
 */
export async function reloadDocumentsForCabinet(cabinetId, status = null) {
    try {
        let apiUrl;
        if (cabinetId) {
            apiUrl = `/OSAS-SIS/backend/CMS/api/files.php?cabinet_id=${cabinetId}`;
        } else {
            apiUrl = `/OSAS-SIS/backend/CMS/api/files.php?mode=all`;
        }

        if (status && ['available', 'borrowed', 'archived'].includes(status)) {
            apiUrl += `&status=${encodeURIComponent(status)}`;
        }

        const response = await fetch(apiUrl);
        const result = await response.json();

        if (result.success && result.data) {
            const tableBody = document.getElementById('documentsTableBody');
            const emptyStateRow = document.getElementById('emptyStateRow');

            if (!tableBody) return;

            // Clear existing document rows
            const existingRows = tableBody.querySelectorAll('tr:not(#emptyStateRow)');
            existingRows.forEach(row => row.remove());

            if (result.data.length === 0) {
                if (emptyStateRow) {
                    emptyStateRow.classList.remove('hidden');
                }
            } else {
                if (emptyStateRow) {
                    emptyStateRow.classList.add('hidden');
                }

                // Render documents
                result.data.forEach((doc, index) => {
                    const row = createDocumentRowFromAPI(doc, index + 1);
                    tableBody.appendChild(row);
                });

                // Initialize action buttons using event delegation
                initDocumentActionButtonsDelegated();
            }

            // Update document count
            updateDocumentCount();

            // Extract unique cabinet numbers from API response
            const uniqueCabinetNumbers = [...new Set(result.data.map(doc => doc.cabinet_number).filter(Boolean))].sort((a, b) => {
                const numA = parseInt(a.split('.')[1], 10);
                const numB = parseInt(b.split('.')[1], 10);
                return numA - numB;
            });

            // Update Cabinet Number filter dropdown with numbers from API
            populateCabinetNumberDropdown(uniqueCabinetNumbers);

            // Extract unique categories from API response
            const uniqueCategories = [...new Set(result.data.map(doc => doc.category).filter(Boolean))].sort();

            // Update Category filter dropdown
            populateCategoryDropdown(uniqueCategories);

            // Extract unique OSAS Services from API response
            const uniqueServices = [...new Set(result.data.map(doc => doc.osas_service).filter(Boolean))].sort();

            // Update OSAS Service filter dropdown
            populateOsasServiceDropdown(uniqueServices);
        }
    } catch (error) {
        console.error('Error reloading documents:', error);
    }
}

/**
 * Highlight a specific file row in the documents table and scroll it into view.
 * @param {number} fileId - File ID to highlight
 */
export function highlightFileRow(fileId) {
    const tableBody = document.getElementById('documentsTableBody');
    if (!tableBody) return;

    const row = tableBody.querySelector(`tr[data-file-id="${fileId}"]`);
    if (!row) return;

    row.scrollIntoView({ behavior: 'smooth', block: 'center' });

    // Apply a temporary, more visible highlight using Tailwind-style classes
    row.classList.add(
        'ring-2',
        'ring-[#800000]',
        'ring-offset-2',
        'bg-amber-50',
        'transition-all',
        'duration-500'
    );

    setTimeout(() => {
        row.classList.remove('ring-2', 'ring-[#800000]', 'ring-offset-2', 'bg-amber-50');
    }, 3000);
}

/**
 * Populate cabinet number dropdown dynamically
 * @param {Array} cabinetNumbers - Array of unique cabinet numbers
 */
function populateCabinetNumberDropdown(cabinetNumbers) {
    const dropdown = document.getElementById('cabinetNumberSortDropdown');
    if (!dropdown) return;

    // Clear existing options
    dropdown.innerHTML = '';

    // Add "All Cabinet Numbers" option
    const allOption = document.createElement('button');
    allOption.className = 'w-full text-left px-4 py-2 hover:bg-gray-100 transition-colors text-sm text-gray-700';
    allOption.setAttribute('data-cabinet-number', 'all');
    allOption.textContent = 'All Cabinet Numbers';
    dropdown.appendChild(allOption);

    // Add each unique cabinet number
    cabinetNumbers.forEach(number => {
        if (number) {
            const button = document.createElement('button');
            button.className = 'w-full text-left px-4 py-2 hover:bg-gray-100 transition-colors text-sm text-gray-700';
            button.setAttribute('data-cabinet-number', number);
            button.textContent = number;
            dropdown.appendChild(button);
        }
    });
}

/**
 * Create a document table row from API data (helper function for cabinet-view.js)
 * @param {Object} doc - Document object from API
 * @param {number} rowNumber - Row number (NO. column)
 * @returns {HTMLElement} Table row element
 */
function createDocumentRowFromAPI(doc, rowNumber) {
    const row = document.createElement('tr');
    row.className = 'hover:bg-gray-50';
    row.setAttribute('data-file-id', doc.id);
    row.setAttribute('data-cabinet-number', doc.cabinet_number);
    row.setAttribute('data-filename', doc.filename);

    // Category badge color
    let categoryBadgeClass = 'bg-purple-100 text-purple-800';
    if (doc.category === 'Sports') {
        categoryBadgeClass = 'bg-blue-100 text-blue-800';
    } else if (doc.category === 'Objects') {
        categoryBadgeClass = 'bg-orange-100 text-orange-800';
    }

    // Status badge color
    let statusBadgeClass = 'bg-green-100 text-green-800';
    if (doc.status === 'borrowed') {
        statusBadgeClass = 'bg-yellow-100 text-yellow-800';
    } else if (doc.status === 'archived') {
        statusBadgeClass = 'bg-gray-100 text-gray-800';
    }

    const statusText = doc.status === 'borrowed' ? 'Borrowed' : (doc.status === 'archived' ? 'Archived' : 'Available');

    const isArchived = doc.status === 'archived';

    row.innerHTML = `
        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 text-center">${rowNumber}</td>
        <td class="px-6 py-4 whitespace-nowrap text-center">
            <span class="text-sm text-gray-900 font-medium">${doc.cabinet_number}</span>
        </td>
        <td class="px-6 py-4">
            <div class="text-sm font-medium text-gray-900" title="${doc.filename}" style="max-width: 22rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                ${doc.filename.length > 45 ? doc.filename.substring(0, 45) + '...' : doc.filename}
            </div>
            <div class="text-sm text-gray-500" title="${doc.description || ''}" style="max-width: 22rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                ${(doc.description || 'No description').length > 45 ? (doc.description || 'No description').substring(0, 45) + '...' : (doc.description || 'No description')}
            </div>
        </td>
        <td class="px-6 py-4 whitespace-nowrap text-center">
            <span class="px-2 py-1 text-xs rounded-full ${categoryBadgeClass}">${doc.category || 'Documents'}</span>
        </td>
        <td class="px-6 py-4 whitespace-nowrap text-center">
            <div class="text-sm text-gray-900 mx-auto" title="${doc.osas_service || 'N/A'}" style="max-width: 14rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                ${(doc.osas_service || 'N/A')}
            </div>
        </td>
        <td class="px-6 py-4 whitespace-nowrap text-center">
            <span class="px-2 py-1 text-xs rounded-full ${statusBadgeClass}">${statusText}</span>
        </td>
        <td class="px-6 py-4 whitespace-nowrap text-center">
            <div class="flex items-center justify-center gap-0.5">
                <button class="view-file-btn p-1.5 text-blue-500 hover:bg-blue-50 hover:text-blue-700 rounded-lg transition-all duration-200 cursor-pointer" data-file-id="${doc.id}" title="View Details">
                    <span class="iconify w-4.5 h-4.5" data-icon="solar:eye-bold" data-inline="false"></span>
                </button>
                <button class="edit-file-btn p-1.5 text-amber-500 hover:bg-amber-50 hover:text-amber-700 rounded-lg transition-all duration-200 cursor-pointer" data-file-id="${doc.id}" title="Edit Document">
                    <span class="iconify w-4.5 h-4.5" data-icon="solar:pen-bold" data-inline="false"></span>
                </button>
                <button class="${isArchived ? 'unarchive-file-btn p-1.5 text-green-500 hover:bg-green-50 hover:text-green-700' : 'archive-file-btn p-1.5 text-red-500 hover:bg-red-50 hover:text-red-700'} rounded-lg transition-all duration-200 cursor-pointer" data-file-id="${doc.id}" data-file-name="${doc.filename}" title="${isArchived ? 'Unarchive Document' : 'Archive Document'}">
                    <span class="iconify w-4.5 h-4.5" data-icon="${isArchived ? 'solar:restart-bold' : 'solar:trash-bin-trash-bold'}" data-inline="false"></span>
                </button>
            </div>
        </td>
    `;

    return row;
}

/**
 * Delete document from table
 */
export function deleteDocument(row, documentName) {
    Swal.fire({
        icon: 'warning',
        title: 'Delete Document?',
        text: `Are you sure you want to delete "${documentName}"?`,
        showCancelButton: true,
        confirmButtonText: 'Yes, delete it',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#6b7280'
    }).then((result) => {
        if (result.isConfirmed) {
            row.remove();
            updateDocumentCount();

            // Check if table is empty and show empty state
            const tableBody = document.getElementById('documentsTableBody');
            const emptyStateRow = document.getElementById('emptyStateRow');
            const existingRows = tableBody.querySelectorAll('tr:not(#emptyStateRow)');

            if (tableBody && emptyStateRow && existingRows.length === 0) {
                emptyStateRow.classList.remove('hidden');
            }

            // Update row numbers after deletion
            updateRowNumbers();

            Swal.fire({
                icon: 'success',
                title: 'Deleted!',
                text: 'Document has been deleted.',
                confirmButtonColor: '#800000',
                timer: 1500,
                showConfirmButton: false
            });
        }
    });
}

/**
 * Update document count display
 */
export function updateDocumentCount() {
    const tableBody = document.getElementById('documentsTableBody');
    const documentCount = document.getElementById('documentCount');

    if (!tableBody || !documentCount) return;

    // Count visible document rows only (exclude empty state row and filtered/hidden rows)
    const rows = tableBody.querySelectorAll('tr:not(#emptyStateRow):not(.hidden)');

    // Aggregate counts by category
    const categoryCounts = {};
    let totalCount = 0;

    rows.forEach(row => {
        const categoryCell = row.querySelector('td:nth-child(4)');
        if (categoryCell) {
            const categoryText = categoryCell.textContent.trim();
            // Basic singular/plural handling if needed, but categories are usually nouns
            categoryCounts[categoryText] = (categoryCounts[categoryText] || 0) + 1;
            totalCount++;
        }
    });

    if (totalCount === 0) {
        documentCount.innerHTML = '<span class="text-sm">0 documents</span>';
    } else {
        // Create formatted HTML with icons
        const countHtml = Object.entries(categoryCounts)
            .map(([cat, num]) => {
                let icon = '';
                let badgeClass = 'bg-purple-50 px-2.5 py-1 rounded-md border border-purple-100 text-purple-700'; // Default / Documents

                if (cat.toLowerCase() === 'sports') {
                    // Trophy icon / Ball icon
                    icon = '<svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>';
                    badgeClass = 'bg-blue-50 px-2.5 py-1 rounded-md border border-blue-100 text-blue-700';
                } else if (cat.toLowerCase() === 'objects') {
                    // Cube icon
                    icon = '<svg class="w-4 h-4 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>';
                    badgeClass = 'bg-orange-50 px-2.5 py-1 rounded-md border border-orange-100 text-orange-700';
                } else {
                    // Document icon
                    icon = '<svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>';
                }

                return `
                    <div class="flex items-center gap-1.5 ${badgeClass}">
                        ${icon}
                        <span class="font-medium">${num} ${cat}</span>
                    </div>
                `;
            })
            .join(''); // Remove separator since we use flex gap on container

        // Wrap in flex container
        documentCount.innerHTML = `<div class="flex items-center gap-2">${countHtml}</div>`;
    }

    // Update row numbers
    updateRowNumbers();
}

/**
 * Initialize document action buttons using event delegation
 */
function initDocumentActionButtonsDelegated() {
    const tableBody = document.getElementById('documentsTableBody');
    if (!tableBody) return;

    // Remove existing event listeners if any
    const clone = tableBody.cloneNode(true);
    tableBody.parentNode.replaceChild(clone, tableBody);
    const newTableBody = document.getElementById('documentsTableBody');

    // Use event delegation for action buttons
    newTableBody.addEventListener('click', async (e) => {
        const viewBtn = e.target.closest('.view-file-btn');
        const editBtn = e.target.closest('.edit-file-btn');
        const archiveBtn = e.target.closest('.archive-file-btn');
        const unarchiveBtn = e.target.closest('.unarchive-file-btn');

        if (viewBtn) {
            const fileId = viewBtn.getAttribute('data-file-id');
            viewDocument(fileId);
        } else if (editBtn) {
            const fileId = editBtn.getAttribute('data-file-id');
            editDocument(fileId);
        } else if (archiveBtn) {
            const fileId = archiveBtn.getAttribute('data-file-id');
            const fileName = archiveBtn.getAttribute('data-file-name');
            await archiveDocument(fileId, fileName);
        } else if (unarchiveBtn) {
            const fileId = unarchiveBtn.getAttribute('data-file-id');
            const fileName = unarchiveBtn.getAttribute('data-file-name');
            await unarchiveDocument(fileId, fileName);
        }
    });
}

/**
 * View document details
 */
async function viewDocument(fileId) {
    try {
        const response = await fetch(`/OSAS-SIS/backend/CMS/api/files.php?id=${fileId}`);
        const result = await response.json();

        if (result.success && result.data) {
            const doc = result.data;
            const borrowerDisplay = (doc.status === 'borrowed' && doc.borrow_by) ? doc.borrow_by : 'None';
            const statusText = doc.status === 'borrowed' ? 'Borrowed' : (doc.status === 'archived' ? 'Archived' : 'Available');
            let statusBadgeClass = 'bg-green-100 text-green-800';
            if (doc.status === 'borrowed') {
                statusBadgeClass = 'bg-yellow-100 text-yellow-800';
            } else if (doc.status === 'archived') {
                statusBadgeClass = 'bg-gray-100 text-gray-800';
            }
            Swal.fire({
                title: doc.filename,
                icon: 'info',
                width: '520px',
                customClass: 'swal2-doc-view-modal',
                confirmButtonColor: '#800000',
                confirmButtonText: 'Close',
                html: `
                    <div class="text-left space-y-3">
                        <div>
                            <p class="text-sm font-semibold text-gray-700">Cabinet Number:</p>
                            <p class="text-sm text-gray-900">${doc.cabinet_number}</p>
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-gray-700">Category:</p>
                            <p class="text-sm text-gray-900">${doc.category || 'Documents'}</p>
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-gray-700">OSAS Service:</p>
                            <p class="text-sm text-gray-900">${doc.osas_service || 'N/A'}</p>
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-gray-700">Description:</p>
                            <p class="text-sm text-gray-900">${doc.description || 'No description'}</p>
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-gray-700">Status:</p>
                            <p class="text-sm text-gray-900">
                                <span class="px-2 py-1 text-xs rounded-full ${statusBadgeClass}">${statusText}</span>
                            </p>
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-gray-700">Borrower Name:</p>
                            <p class="text-sm text-gray-900">${borrowerDisplay}</p>
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-gray-700">Added By:</p>
                            <p class="text-sm text-gray-900">${doc.added_by || 'Admin'}</p>
                        </div>
                    </div>
                `
            });
        }
    } catch (error) {
        console.error('Error viewing document:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Failed to load document details',
            confirmButtonColor: '#800000'
        });
    }
}

/**
 * Edit document
 */
async function editDocument(fileId) {
    try {
        const response = await fetch(`/OSAS-SIS/backend/CMS/api/files.php?id=${fileId}`);
        const result = await response.json();

        if (result.success && result.data) {
            const doc = result.data;

            Swal.fire({
                title: 'Edit Document',
                html: `
                    <form id="editDocumentForm" class="text-left">
                        <div class="mb-2">
                            <label class="block text-xs font-medium text-gray-700 mb-1">Document Name</label>
                            <input 
                                type="text" 
                                id="editDocumentName" 
                                value="${doc.filename}"
                                class="w-full px-3 py-1.5 text-xs border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#800000] focus:border-[#800000] outline-none"
                                required
                            />
                        </div>
                        <div class="mb-2">
                            <label class="block text-xs font-medium text-gray-700 mb-1">Cabinet Number</label>
                            <input 
                                type="text" 
                                id="editCabinetNumber" 
                                value="${doc.cabinet_number || ''}"
                                class="w-full px-3 py-1.5 text-xs border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#800000] focus:border-[#800000] outline-none"
                                required
                            />
                        </div>
                        <div class="mb-2">
                            <label class="block text-xs font-medium text-gray-700 mb-1">Category</label>
                            <select 
                                id="editDocumentCategory" 
                                class="w-full px-3 py-1.5 text-xs border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#800000] focus:border-[#800000] outline-none"
                                required
                            >
                                <option value="Documents" ${doc.category === 'Documents' ? 'selected' : ''}>Documents</option>
                                <option value="Sports" ${doc.category === 'Sports' ? 'selected' : ''}>Sports</option>
                                <option value="Objects" ${doc.category === 'Objects' ? 'selected' : ''}>Objects</option>
                            </select>
                        </div>
                        <div class="mb-2">
                            <label class="block text-xs font-medium text-gray-700 mb-1">OSAS Service</label>
                            <select 
                                id="editOsasService" 
                                class="w-full px-3 py-1.5 text-xs border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#800000] focus:border-[#800000] outline-none"
                            >
                                <option value="" ${!doc.osas_service ? 'selected' : ''}>Select Service</option>
                                <option value="Guidance and service counseling services" ${doc.osas_service === 'Guidance and service counseling services' ? 'selected' : ''}>Guidance and service counseling services</option>
                                <option value="Student Organization" ${doc.osas_service === 'Student Organization' ? 'selected' : ''}>Student Organization</option>
                                <option value="Scholarship and Financial Assistance" ${doc.osas_service === 'Scholarship and Financial Assistance' ? 'selected' : ''}>Scholarship and Financial Assistance</option>
                                <option value="Health Service" ${doc.osas_service === 'Health Service' ? 'selected' : ''}>Health Service</option>
                                <option value="Culture and Art Program" ${doc.osas_service === 'Culture and Art Program' ? 'selected' : ''}>Culture and Art Program</option>
                                <option value="Sports Development Program" ${doc.osas_service === 'Sports Development Program' ? 'selected' : ''}>Sports Development Program</option>
                                <option value="Safety and Security Services" ${doc.osas_service === 'Safety and Security Services' ? 'selected' : ''}>Safety and Security Services</option>
                                <option value="Student Housing and Residential Services" ${doc.osas_service === 'Student Housing and Residential Services' ? 'selected' : ''}>Student Housing and Residential Services</option>
                                <option value="Social and Community Involvement Programs" ${doc.osas_service === 'Social and Community Involvement Programs' ? 'selected' : ''}>Social and Community Involvement Programs</option>
                            </select>
                        </div>
                        <div class="mb-2">
                            <label class="block text-xs font-medium text-gray-700 mb-1">Description</label>
                            <textarea 
                                id="editDocumentDescription" 
                                rows="2"
                                class="w-full px-3 py-1.5 text-xs border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#800000] focus:border-[#800000] outline-none"
                            >${doc.description || ''}</textarea>
                        </div>
                        <div class="mb-2">
                            <label class="block text-xs font-medium text-gray-700 mb-1">Status</label>
                            <select 
                                id="editDocumentStatus" 
                                class="w-full px-3 py-1.5 text-xs border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#800000] focus:border-[#800000] outline-none"
                                required
                            >
                                <option value="available" ${doc.status === 'available' ? 'selected' : ''}>Available</option>
                                <option value="borrowed" ${doc.status === 'borrowed' ? 'selected' : ''}>Borrowed</option>
                            </select>
                        </div>
                        <div class="mb-2" id="borrowerNameGroup" style="display: none;">
                            <label class="block text-xs font-medium text-gray-700 mb-1">Borrower Name</label>
                            <input 
                                type="text" 
                                id="editBorrowerName" 
                                value="${doc.borrow_by || ''}"
                                class="w-full px-3 py-1.5 text-xs border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#800000] focus:border-[#800000] outline-none"
                                placeholder="Enter borrower name when status is Borrowed"
                            />
                        </div>
                    </form>
                `,
                showCancelButton: true,
                confirmButtonText: 'Save Changes',
                cancelButtonText: 'Cancel',
                confirmButtonColor: '#800000',
                cancelButtonColor: '#6b7280',
                didOpen: () => {
                    const statusSelect = document.getElementById('editDocumentStatus');
                    const borrowerGroup = document.getElementById('borrowerNameGroup');

                    const toggleBorrowerField = () => {
                        if (!statusSelect || !borrowerGroup) return;
                        const value = statusSelect.value;
                        borrowerGroup.style.display = value === 'borrowed' ? 'block' : 'none';
                    };

                    if (statusSelect) {
                        statusSelect.addEventListener('change', toggleBorrowerField);
                    }

                    // Initial state based on current status
                    toggleBorrowerField();
                },
                preConfirm: async () => {
                    const filename = document.getElementById('editDocumentName').value;
                    const cabinetNumber = document.getElementById('editCabinetNumber').value;
                    const category = document.getElementById('editDocumentCategory').value;
                    const osasService = document.getElementById('editOsasService').value;
                    const description = document.getElementById('editDocumentDescription').value;
                    const status = document.getElementById('editDocumentStatus').value;
                    const borrowerInput = document.getElementById('editBorrowerName');
                    const borrowerName = borrowerInput ? borrowerInput.value.trim() : '';

                    if (!filename || !cabinetNumber) {
                        Swal.showValidationMessage('Please enter document name and cabinet number');
                        return false;
                    }

                    if (status === 'borrowed' && !borrowerName) {
                        Swal.showValidationMessage('Please enter borrower name when status is Borrowed');
                        return false;
                    }

                    return { filename, cabinet_number: cabinetNumber.trim(), category, osas_service: osasService.trim(), description, status, borrow_by: borrowerName || null };
                }
            }).then(async (result) => {
                if (result.isConfirmed) {
                    const updateData = result.value;

                    try {
                        const updateResponse = await fetch(`/OSAS-SIS/backend/CMS/api/files.php?id=${fileId}`, {
                            method: 'PUT',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify(updateData)
                        });

                        const updateResult = await updateResponse.json();

                        if (updateResult.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Success!',
                                text: 'Document updated successfully',
                                confirmButtonColor: '#800000'
                            }).then(() => {
                                // Reload documents
                                const urlParams = new URLSearchParams(window.location.search);
                                const cabinetId = urlParams.get('cabinet_id');
                                if (cabinetId) {
                                    reloadDocumentsForCabinet(parseInt(cabinetId, 10));
                                }
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: updateResult.message || 'Failed to update document',
                                confirmButtonColor: '#800000'
                            });
                        }
                    } catch (error) {
                        console.error('Error updating document:', error);
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Failed to update document',
                            confirmButtonColor: '#800000'
                        });
                    }
                }
            });
        }
    } catch (error) {
        console.error('Error loading document:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Failed to load document details',
            confirmButtonColor: '#800000'
        });
    }
}

/**
 * Archive document
 */
async function archiveDocument(fileId, fileName) {
    Swal.fire({
        icon: 'warning',
        title: 'Archive Document?',
        text: `Are you sure you want to archive "${fileName}"?`,
        showCancelButton: true,
        confirmButtonText: 'Yes, archive it',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#6b7280'
    }).then(async (result) => {
        if (result.isConfirmed) {
            try {
                const response = await fetch(`/OSAS-SIS/backend/CMS/api/files.php?id=${fileId}`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ status: 'archived' })
                });

                const apiResult = await response.json();

                if (apiResult.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Archived!',
                        text: 'Document has been archived',
                        confirmButtonColor: '#800000'
                    }).then(() => {
                        // Reload documents
                        const urlParams = new URLSearchParams(window.location.search);
                        const cabinetId = urlParams.get('cabinet_id');
                        if (cabinetId) {
                            reloadDocumentsForCabinet(parseInt(cabinetId, 10));
                        }
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: apiResult.message || 'Failed to archive document',
                        confirmButtonColor: '#800000'
                    });
                }
            } catch (error) {
                console.error('Error archiving document:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Failed to archive document',
                    confirmButtonColor: '#800000'
                });
            }
        }
    });
}

/**
 * Unarchive document
 */
async function unarchiveDocument(fileId, fileName) {
    Swal.fire({
        icon: 'question',
        title: 'Unarchive Document?',
        text: `Do you want to restore "${fileName}"?`,
        showCancelButton: true,
        confirmButtonText: 'Yes, unarchive it',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#16a34a',
        cancelButtonColor: '#6b7280'
    }).then(async (result) => {
        if (result.isConfirmed) {
            try {
                const response = await fetch(`/OSAS-SIS/backend/CMS/api/files.php?id=${fileId}`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ status: 'available' })
                });

                const apiResult = await response.json();

                if (apiResult.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Unarchived!',
                        text: 'Document has been restored.',
                        confirmButtonColor: '#800000'
                    }).then(() => {
                        // Reset status filter back to "All Status" after unarchiving
                        const statusSortText = document.getElementById('statusSortText');
                        if (statusSortText) {
                            statusSortText.textContent = 'All Status';
                        }

                        // Clear any status filter in currentFilters so all rows are shown
                        if (typeof currentFilters !== 'undefined') {
                            currentFilters.status = 'all';
                        }

                        const urlParams = new URLSearchParams(window.location.search);
                        const cabinetId = urlParams.get('cabinet_id');
                        if (cabinetId) {
                            reloadDocumentsForCabinet(parseInt(cabinetId, 10));
                        }
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: apiResult.message || 'Failed to unarchive document',
                        confirmButtonColor: '#800000'
                    });
                }
            } catch (error) {
                console.error('Error unarchiving document:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Failed to unarchive document',
                    confirmButtonColor: '#800000'
                });
            }
        }
    });
}

/**
 * Initialize search functionality for documents
 */
function initSearchFunctionality() {
    const searchInput = document.getElementById('searchDocumentsInput');
    if (!searchInput) return;

    searchInput.addEventListener('input', (e) => {
        const searchTerm = e.target.value.toLowerCase().trim();

        // Update filter state
        currentFilters.searchTerm = searchTerm;

        // Apply all filters
        applyAllFilters();
    });
}

/**
 * Populate category dropdown dynamically based on documents
 * @param {Array} categories - Array of unique categories
 */
function populateCategoryDropdown(categories) {
    const categoryDropdown = document.getElementById('categorySortDropdown');
    if (!categoryDropdown) return;

    // Clear existing options except "All Categories"
    categoryDropdown.innerHTML = '';

    // Add "All Categories" option
    const allOption = document.createElement('button');
    allOption.className = 'w-full text-left px-4 py-2 hover:bg-gray-100 transition-colors text-sm text-gray-700';
    allOption.setAttribute('data-category', 'all');
    allOption.textContent = 'All Categories';
    categoryDropdown.appendChild(allOption);

    // Add each unique category
    categories.forEach(category => {
        if (category) {
            const button = document.createElement('button');
            button.className = 'w-full text-left px-4 py-2 hover:bg-gray-100 transition-colors text-sm text-gray-700';
            button.setAttribute('data-category', category);
            button.textContent = category;
            categoryDropdown.appendChild(button);
        }
    });
}

/**
 * Populate OSAS Service dropdown dynamically based on documents
 * @param {Array} services - Array of unique services
 */
function populateOsasServiceDropdown(services) {
    const dropdown = document.getElementById('osasServiceSortDropdown');
    if (!dropdown) return;

    // Clear existing options except "All Services"
    dropdown.innerHTML = '';

    // Add "All Services" option
    const allOption = document.createElement('button');
    allOption.className = 'w-full text-left px-4 py-2 hover:bg-gray-100 transition-colors text-sm text-gray-700';
    allOption.setAttribute('data-osas-service', 'all');
    allOption.textContent = 'All Services';
    dropdown.appendChild(allOption);

    // Add each unique service
    services.forEach(service => {
        if (service) {
            const button = document.createElement('button');
            button.className = 'w-full text-left px-4 py-2 hover:bg-gray-100 transition-colors text-sm text-gray-700';
            button.setAttribute('data-osas-service', service);
            button.textContent = service;
            dropdown.appendChild(button);
        }
    });
}

/**
 * Update row numbers in the table
 */
function updateRowNumbers() {
    const tableBody = document.getElementById('documentsTableBody');
    if (!tableBody) return;

    // Only renumber visible rows (so filtering doesn't create confusing numbering)
    const rows = tableBody.querySelectorAll('tr:not(#emptyStateRow):not(.hidden)');
    rows.forEach((row, index) => {
        const firstCell = row.querySelector('td:first-child');
        if (firstCell) {
            firstCell.textContent = index + 1;
        }
    });
}

/**
 * Show Bulk Add Documents modal
 */
export function showBulkAddModal() {
    Swal.fire({
        title: 'Bulk Add Documents',
        html: `
            <form id="bulkAddForm" class="text-left">
                <div class="mb-3 p-3 bg-gray-50 rounded-lg border border-gray-200">
                    <h4 class="text-xs font-bold text-[#800000] mb-2 border-b border-gray-200 pb-1">Common Settings</h4>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">Category <span class="text-red-500">*</span></label>
                            <select id="bulkCategory" class="w-full px-3 py-1.5 text-xs border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#800000] focus:border-[#800000] outline-none">
                                <option value="Documents">Documents</option>
                                <option value="Sports">Sports</option>
                                <option value="Objects">Objects</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">OSAS Service</label>
                            <select id="bulkOsasService" class="w-full px-3 py-1.5 text-xs border border-gray-300 rounded-lg focus:ring-1 focus:ring-[#800000] focus:border-[#800000] outline-none">
                                <option value="">Select Service</option>
                                <option value="Guidance and service counseling services">Guidance and service counseling services</option>
                                <option value="Student Organization">Student Organization</option>
                                <option value="Scholarship and Financial Assistance">Scholarship and Financial Assistance</option>
                                <option value="Health Service">Health Service</option>
                                <option value="Culture and Art Program">Culture and Art Program</option>
                                <option value="Sports Development Program">Sports Development Program</option>
                                <option value="Safety and Security Services">Safety and Security Services</option>
                                <option value="Student Housing and Residential Services">Student Housing and Residential Services</option>
                                <option value="Social and Community Involvement Programs">Social and Community Involvement Programs</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">Status <span class="text-red-500">*</span></label>
                            <select id="bulkStatus" class="w-full px-3 py-1.5 text-xs border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#800000] focus:border-[#800000] outline-none">
                                <option value="available">Available</option>
                                <option value="borrowed">Borrowed</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="mb-2 flex justify-between items-center">
                    <h4 class="text-xs font-bold text-gray-800">Files to Add</h4>
                    <button type="button" id="addBulkRowBtn" class="text-xs bg-[#800000] hover:bg-[#700000] text-white px-2 py-1 rounded-md transition-colors flex items-center gap-1">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                        Add Another File
                    </button>
                </div>

                <div id="bulkRowsContainer" class="h-[180px] overflow-y-auto pr-2 space-y-2 pb-2 border-t border-b border-gray-100 py-2">
                    <!-- Initial Row -->
                    <div class="bulk-row p-2 border border-gray-200 rounded-lg relative group bg-white shadow-sm hover:shadow-md transition-shadow">
                        <button type="button" class="remove-bulk-row hidden absolute top-1 right-1 text-gray-400 hover:text-red-500 p-1 rounded-full hover:bg-red-50 transition-colors">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        </button>
                        <div class="grid grid-cols-12 gap-2 mb-2">
                            <div class="col-span-8">
                                <label class="block text-xs font-medium text-gray-500 mb-1">File Name <span class="text-red-500">*</span></label>
                                <input type="text" class="bulk-name w-full px-3 py-1.5 text-xs border border-gray-300 rounded-lg focus:ring-1 focus:ring-[#800000] focus:border-[#800000] outline-none" placeholder="Enter file name" required>
                            </div>
                            <div class="col-span-4">
                                <label class="block text-xs font-medium text-gray-500 mb-1">Cabinet No.</label>
                                <input type="text" class="bulk-cab-num w-full px-3 py-1.5 text-xs border border-gray-300 rounded-lg focus:ring-1 focus:ring-[#800000] focus:border-[#800000] outline-none" placeholder="e.g. C1.X">
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">Description (Optional)</label>
                            <input type="text" class="bulk-desc w-full px-3 py-1.5 text-xs border border-gray-300 rounded-lg focus:ring-1 focus:ring-[#800000] focus:border-[#800000] outline-none" placeholder="Enter description">
                        </div>
                    </div>
                </div>
                <div class="text-xs text-gray-500 mt-2 italic">* Minimum 1 file required.</div>
            </form>
        `,
        showCancelButton: true,
        confirmButtonText: 'Add All Documents',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#800000',
        cancelButtonColor: '#6b7280',
        width: '700px',
        didOpen: () => {
            const container = document.getElementById('bulkRowsContainer');
            const addBtn = document.getElementById('addBulkRowBtn');

            // Template for new row
            const createRow = () => {
                const div = document.createElement('div');
                div.className = 'bulk-row p-2 border border-gray-200 rounded-lg relative group bg-white shadow-sm hover:shadow-md transition-shadow';
                div.innerHTML = `
                    <button type="button" class="remove-bulk-row absolute top-1 right-1 text-gray-400 hover:text-red-500 p-1 rounded-full hover:bg-red-50 transition-colors">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                    <div class="grid grid-cols-12 gap-2 mb-2">
                        <div class="col-span-8">
                            <label class="block text-xs font-medium text-gray-500 mb-1">File Name <span class="text-red-500">*</span></label>
                            <input type="text" class="bulk-name w-full px-3 py-1.5 text-xs border border-gray-300 rounded-lg focus:ring-1 focus:ring-[#800000] focus:border-[#800000] outline-none" placeholder="Enter file name" required>
                        </div>
                        <div class="col-span-4">
                            <label class="block text-xs font-medium text-gray-500 mb-1">Cabinet No.</label>
                            <input type="text" class="bulk-cab-num w-full px-3 py-1.5 text-xs border border-gray-300 rounded-lg focus:ring-1 focus:ring-[#800000] focus:border-[#800000] outline-none" placeholder="e.g. C1.X">
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Description (Optional)</label>
                        <input type="text" class="bulk-desc w-full px-3 py-1.5 text-xs border border-gray-300 rounded-lg focus:ring-1 focus:ring-[#800000] focus:border-[#800000] outline-none" placeholder="Enter description">
                    </div>
                `;
                return div;
            };

            // Add Row Listener
            addBtn.addEventListener('click', () => {
                // Prepend the new row (add to top)
                container.insertBefore(createRow(), container.firstChild);
                // Scroll to top to see the new row
                container.scrollTop = 0;
            });

            // Remove Row Delegation
            container.addEventListener('click', (e) => {
                const removeBtn = e.target.closest('.remove-bulk-row');
                if (removeBtn) {
                    if (container.querySelectorAll('.bulk-row').length > 1) {
                        removeBtn.closest('.bulk-row').remove();
                    } else {
                        // If it's the last row, just clear values
                        const row = removeBtn.closest('.bulk-row');
                        row.querySelectorAll('input').forEach(input => input.value = '');
                        Swal.showValidationMessage('At least one row is required');
                    }
                }
            });
        },
        preConfirm: () => {
            const category = document.getElementById('bulkCategory').value;
            const osasService = document.getElementById('bulkOsasService').value;
            const status = document.getElementById('bulkStatus').value;

            const rows = document.querySelectorAll('.bulk-row');
            const files = [];
            let isValid = true;

            rows.forEach((row, index) => {
                const name = row.querySelector('.bulk-name').value.trim();
                const cabinetNum = row.querySelector('.bulk-cab-num').value.trim();
                const desc = row.querySelector('.bulk-desc').value.trim();

                if (!name) {
                    // Highlight error?
                    row.querySelector('.bulk-name').classList.add('border-red-500');
                    isValid = false;
                } else {
                    row.querySelector('.bulk-name').classList.remove('border-red-500');
                    files.push({
                        filename: name,
                        cabinet_number: cabinetNum,
                        description: desc
                    });
                }
            });

            if (!isValid) {
                Swal.showValidationMessage('Please fill in all required file names');
                return false;
            }

            if (files.length === 0) {
                Swal.showValidationMessage('Please add at least one file');
                return false;
            }

            return {
                bulk: true,
                category,
                osas_service: osasService || '',
                status,
                files
            };
        }
    }).then(async (result) => {
        if (result.isConfirmed && result.value) {
            // Logic to send to API (Similar to addDocument)
            // Identify Cabinet ID first
            // Method 1: Check URL parameter
            let cabinetId = null;
            const urlParams = new URLSearchParams(window.location.search);
            const cabinetIdFromUrl = urlParams.get('cabinet_id');
            if (cabinetIdFromUrl) {
                cabinetId = parseInt(cabinetIdFromUrl, 10);
            }

            // Fallback methods (Dropdowns)
            if (!cabinetId) {
                const cabinetDropdownText = document.getElementById('cabinetDropdownText');
                const cabinetDropdown = document.getElementById('cabinetDropdown');
                if (cabinetDropdownText && cabinetDropdown &&
                    cabinetDropdownText.textContent.trim() !== 'Select Cabinet' &&
                    cabinetDropdownText.textContent.trim() !== 'All Cabinets') {
                    const selectedText = cabinetDropdownText.textContent.trim();
                    const options = cabinetDropdown.querySelectorAll('button[data-cabinet-id]');
                    for (const option of options) {
                        if (option.textContent.trim() === selectedText) {
                            cabinetId = parseInt(option.getAttribute('data-cabinet-id'), 10);
                            break;
                        }
                    }
                }
            }

            if (!cabinetId) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Unable to determine cabinet. Please select a cabinet first.',
                    confirmButtonColor: '#800000'
                });
                return;
            }

            // Prepare Payload
            const payload = {
                bulk: true,
                cabinet_id: cabinetId,
                category: result.value.category,
                osas_service: result.value.osas_service,
                status: result.value.status,
                files: result.value.files
            };

            try {
                const response = await fetch('/OSAS-SIS/backend/CMS/api/files.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });

                const apiResult = await response.json();

                if (apiResult.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: apiResult.message || 'Documents added successfully.',
                        confirmButtonColor: '#800000',
                        timer: 2000,
                        showConfirmButton: false
                    });

                    // Reload
                    const event = new CustomEvent('documentAdded', { detail: { cabinetId: parseInt(cabinetId, 10) } });
                    window.dispatchEvent(event);
                    await reloadDocumentsForCabinet(parseInt(cabinetId, 10));

                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: apiResult.message || 'Failed to add documents',
                        confirmButtonColor: '#800000'
                    });
                }
            } catch (error) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Failed to add documents: ' + error.message,
                    confirmButtonColor: '#800000'
                });
            }
        }
    });
}

/**
 * Export table to PDF (Print)
 */
function exportTableToPDF() {
    const itemsTable = document.querySelector('table');
    if (!itemsTable) return;

    // Get cabinet name
    const cabinetName = document.getElementById('cabinetViewTitle')?.textContent || 'Cabinet Documents';

    // Create a new window for printing
    const printWindow = window.open('', '', 'height=600,width=800');

    printWindow.document.write('<html><head><title>' + cabinetName + ' - Export</title>');
    printWindow.document.write('<style>');
    printWindow.document.write('body { font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif; padding: 20px; color: #333; }');
    printWindow.document.write('table { width: 100%; border-collapse: collapse; margin-top: 20px; }');
    printWindow.document.write('th, td { border: 1px solid #e2e8f0; padding: 12px 8px; text-align: left; font-size: 12px; }');
    printWindow.document.write('th { background-color: #f8fafc; color: #64748b; font-weight: 600; text-transform: uppercase; font-size: 11px; }');
    printWindow.document.write('h1 { color: #800000; font-size: 24px; margin-bottom: 5px; }');
    printWindow.document.write('p.subtitle { color: #64748b; font-size: 14px; margin-top: 0; }');
    printWindow.document.write('</style>');
    printWindow.document.write('</head><body>');

    printWindow.document.write('<h1>' + cabinetName + '</h1>');
    printWindow.document.write('<p class="subtitle">Generated on ' + new Date().toLocaleString() + '</p>');

    // Clone the table
    const tableClone = itemsTable.cloneNode(true);

    // Remove Actions column (last column)
    const rows = tableClone.querySelectorAll('tr');
    rows.forEach(row => {
        if (row.cells.length > 0) {
            row.deleteCell(-1);
        }
    });

    printWindow.document.write(tableClone.outerHTML);
    printWindow.document.write('</body></html>');

    printWindow.document.close();
    printWindow.focus();

    // Wait for content to load then print
    setTimeout(() => {
        printWindow.print();
        printWindow.close();
    }, 500);
}
