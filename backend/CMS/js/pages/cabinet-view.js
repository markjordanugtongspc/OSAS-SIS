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

            // Load documents for this cabinet
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

    // Initialize document count
    updateDocumentCount();
}

// Store current filter state
let currentFilters = {
    cabinetNumber: 'all',
    category: 'all',
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
                    <label class="block text-sm font-medium text-gray-700 mb-2">Document Name</label>
                    <input 
                        type="text" 
                        id="documentName" 
                        name="documentName" 
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#800000] focus:border-[#800000] outline-none"
                        placeholder="Enter document name"
                        required
                    />
                </div>
                <div class="mb-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Cabinet Number</label>
                    <input 
                        type="text" 
                        id="cabinetNumberInput" 
                        name="cabinetNumber" 
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#800000] focus:border-[#800000] outline-none"
                        placeholder="e.g., C1.1"
                        required
                    />
                </div>
                <div class="mb-3">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Document Category</label>
                    <select 
                        id="documentCategory" 
                        name="documentCategory" 
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#800000] focus:border-[#800000] outline-none"
                        required
                    >
                        <option value="">Select category</option>
                        <option value="Documents">Documents</option>
                        <option value="Sports">Sports</option>
                        <option value="Objects">Objects</option>
                    </select>
                </div>
                <div class="mb-1">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                    <textarea 
                        id="documentDescription" 
                        name="documentDescription" 
                        rows="2"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#800000] focus:border-[#800000] outline-none"
                        placeholder="Enter document description or notes"
                    ></textarea>
                </div>
                <div class="mb-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                    <select 
                        id="documentStatus" 
                        name="documentStatus" 
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#800000] focus:border-[#800000] outline-none"
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
            const description = document.getElementById('documentDescription')?.value;
            const status = document.getElementById('documentStatus')?.value;

            if (!name || !cabinetNumber || !category || !status) {
                Swal.showValidationMessage('Please fill in all required fields');
                return false;
            }

            return {
                name,
                cabinetNumber: cabinetNumber.trim(),
                category,
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
        <td class="px-6 py-4 whitespace-nowrap">
            <div class="text-sm font-medium text-gray-900">${docData.name}</div>
            <div class="text-sm text-gray-500">${docData.description || 'No description'}</div>
        </td>
        <td class="px-6 py-4 whitespace-nowrap text-center">
            <span class="px-2 py-1 text-xs rounded-full ${categoryBadgeClass}">${category}</span>
        </td>
        <td class="px-6 py-4 whitespace-nowrap text-center">
            <div class="flex items-center">
                <div class="w-8 h-8 rounded-full bg-[#800000] flex items-center justify-center text-white font-semibold text-xs mr-2">
                    A
                </div>
                <span class="text-sm text-gray-900">Admin</span>
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
async function reloadDocumentsForCabinet(cabinetId, status = null) {
    try {
        let apiUrl = `/OSAS-SIS/backend/CMS/api/files.php?cabinet_id=${cabinetId}`;

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
        }
    } catch (error) {
        console.error('Error reloading documents:', error);
    }
}

/**
 * Highlight a specific file row in the documents table and scroll it into view.
 * @param {number} fileId - File ID to highlight
 */
function highlightFileRow(fileId) {
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
        <td class="px-6 py-4 whitespace-nowrap">
            <div class="text-sm font-medium text-gray-900">${doc.filename}</div>
            <div class="text-sm text-gray-500">${doc.description || 'No description'}</div>
        </td>
        <td class="px-6 py-4 whitespace-nowrap text-center">
            <span class="px-2 py-1 text-xs rounded-full ${categoryBadgeClass}">${doc.category || 'Documents'}</span>
        </td>
        <td class="px-6 py-4 whitespace-nowrap text-center">
            <span class="text-sm text-gray-900">${doc.added_by || 'Admin'}</span>
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
    const count = rows.length;

    documentCount.textContent = `${count} document${count !== 1 ? 's' : ''}`;

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
                `,
                icon: 'info',
                confirmButtonColor: '#800000',
                confirmButtonText: 'Close'
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

                    return { filename, cabinet_number: cabinetNumber.trim(), category, description, status, borrow_by: borrowerName || null };
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
