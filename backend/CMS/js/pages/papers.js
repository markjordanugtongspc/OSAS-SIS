import { initSidebar } from '../modules/sidebar.js';
import { initProfileDropdown, initLogout } from './dashboard.js';
import { initDocumentManagement, showAddDocumentModal, addDocumentToTable, deleteDocument, updateDocumentCount } from './cabinet-view.js';

/**
 * Papers page functionality
 * Handles sidebar, active states, and papers-specific features
 */

// Store current status filter state
let currentStatusFilter = null;

// Store all cabinets for filtering
let allCabinets = [];

/**
 * Initialize papers page
 * Version: 2.0 - With Archive Support
 */
export function initPapers() {
    console.log('📦 Papers page initialized - Version 2.0 with Archive Support');

    // Initialize sidebar with collapse functionality
    initSidebar();

    // Initialize profile dropdown and logout
    initProfileDropdown();
    initLogout();

    // Initialize filter dropdowns and search functionality
    initFilterDropdowns();

    // Initialize search box behavior (client-side filter + global search)
    initSearchFunctionality();

    // Initialize cabinet view functionality
    initCabinetView();

    // Load cabinets from API (don't let errors block UI wiring)
    try {
        loadCabinets();
    } catch (error) {
        console.error('Error loading cabinets on init:', error);
    }

    // Initialize Add Cabinet button
    initAddCabinetButton();
}

/**
 * Initialize filter dropdowns and search bar visibility
 */
function initFilterDropdowns() {
    const cabinetDropdownBtn = document.getElementById('cabinetDropdownBtn');
    const cabinetDropdown = document.getElementById('cabinetDropdown');
    const cabinetDropdownText = document.getElementById('cabinetDropdownText');
    const statusDropdownBtn = document.getElementById('statusDropdownBtn');
    const statusDropdown = document.getElementById('statusDropdown');
    const statusDropdownText = document.getElementById('statusDropdownText');
    const searchBarContainer = document.getElementById('searchBarContainer');

    // Make sure the global search bar is visible on initial load
    if (searchBarContainer) {
        searchBarContainer.classList.remove('invisible');
    }

    // ⚡ FORCE ADD ARCHIVE OPTION TO STATUS DROPDOWN (Dynamic injection to bypass cache)
    if (statusDropdown) {
        console.log('🔧 Checking status dropdown for Archive option...');

        // Check if Archive option already exists
        const existingArchiveOption = statusDropdown.querySelector('button[data-status="archived"]');

        if (!existingArchiveOption) {
            console.log('⚠️ Archive option NOT found in HTML! Adding dynamically...');

            // Create separator
            const separator = document.createElement('div');
            separator.className = 'border-t border-gray-200 my-1';

            // Create Archive button
            const archiveButton = document.createElement('button');
            archiveButton.className = 'w-full text-left px-4 py-2 hover:bg-gray-100 transition-colors text-sm text-gray-700 font-medium';
            archiveButton.setAttribute('data-status', 'archived');
            archiveButton.innerHTML = '📦 Archived';

            // Append to dropdown
            statusDropdown.appendChild(separator);
            statusDropdown.appendChild(archiveButton);

            console.log('✅ Archive option added dynamically!');
        } else {
            console.log('✅ Archive option already exists in HTML');
        }
    }

    // Cabinet Dropdown
    if (cabinetDropdownBtn && cabinetDropdown) {
        cabinetDropdownBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            cabinetDropdown.classList.toggle('hidden');
            // Close status dropdown if open
            if (statusDropdown) {
                statusDropdown.classList.add('hidden');
            }
        });

        // Use event delegation for dynamically added cabinet options
        cabinetDropdown.addEventListener('click', (e) => {
            const option = e.target.closest('button[data-cabinet]');
            if (!option) return;

            e.stopPropagation();
            const cabinetValue = option.getAttribute('data-cabinet');
            const cabinetText = option.textContent.trim();
            const cabinetId = option.getAttribute('data-cabinet-id');

            // Update button text
            if (cabinetDropdownText) {
                cabinetDropdownText.textContent = cabinetText;
            }

            // Close dropdown
            cabinetDropdown.classList.add('hidden');

            // Get main header and filters section
            const mainHeader = document.getElementById('mainHeader');
            const filtersSection = document.getElementById('filtersSection');
            const cabinetsGrid = document.getElementById('cabinetsGrid');
            const documentsView = document.getElementById('documentsView');
            const selectedCabinetName = document.getElementById('selectedCabinetName');

            // If "All Cabinets" is selected, show cabinets grid
            if (cabinetValue === 'all') {
                // Show main header
                if (mainHeader) {
                    mainHeader.classList.remove('hidden');
                }

                // Reset status dropdown to "Select Status" and show all cabinets
                const statusDropdownText = document.getElementById('statusDropdownText');
                if (statusDropdownText) {
                    statusDropdownText.textContent = 'Select Status';
                }

                // Show all cabinets (reset any status filter)
                if (allCabinets && allCabinets.length > 0) {
                    renderCabinets(allCabinets);
                }

                // Keep search bar visible even when viewing all cabinets so
                // users can perform a global filename search across cabinets.
                if (searchBarContainer) {
                    searchBarContainer.classList.remove('invisible');
                }
                if (filtersSection) {
                    filtersSection.classList.remove('p-8', 'mb-8', 'bg-white', 'rounded-lg', 'shadow-md');
                }

                // Show cabinets grid and hide documents view
                if (cabinetsGrid) {
                    cabinetsGrid.classList.remove('hidden');
                }
                if (documentsView) {
                    documentsView.classList.add('hidden');
                }

                // Reset Cabinet Number filter to "All" for next time user opens documents view
                setCabinetNumberFilter('all');

                // Update URL to remove cabinet_id parameter
                const url = new URL(window.location);
                url.searchParams.delete('cabinet_id');
                window.history.pushState({}, '', url);
            } else {
                // Hide main header when specific cabinet is selected
                if (mainHeader) {
                    mainHeader.classList.add('hidden');
                }

                // Show search bar and expand filters section
                if (searchBarContainer) {
                    searchBarContainer.classList.remove('invisible');
                    searchBarContainer.classList.add('visible');
                }
                if (filtersSection) {
                    filtersSection.classList.remove('mb-6');
                    filtersSection.classList.add('p-8', 'mb-8', 'bg-white', 'rounded-lg', 'shadow-md');
                }

                // Show documents view and hide cabinets grid
                if (cabinetsGrid) {
                    cabinetsGrid.classList.add('hidden');
                }
                if (documentsView) {
                    documentsView.classList.remove('hidden');
                }
                if (selectedCabinetName) {
                    selectedCabinetName.textContent = cabinetText;
                }

                // Load documents for this cabinet using numeric ID
                const numericCabinetId = cabinetId || parseInt(cabinetValue);
                loadCabinetDocuments(numericCabinetId);

                // Keep Cabinet Number filter default as "All Cabinet Numbers"
                setCabinetNumberFilter('all');

                // Update URL with cabinet_id parameter
                const url = new URL(window.location);
                url.searchParams.set('cabinet_id', numericCabinetId);
                window.history.pushState({}, '', url);
            }
        });
    }

    // Status Dropdown
    if (statusDropdownBtn && statusDropdown) {
        statusDropdownBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            const isHidden = statusDropdown.classList.contains('hidden');
            statusDropdown.classList.toggle('hidden');

            if (!isHidden) {
                console.log('📊 Status dropdown closed');
            } else {
                console.log('📊 Status dropdown opened - Options available:');
                const options = statusDropdown.querySelectorAll('button[data-status]');
                options.forEach(opt => {
                    console.log('   -', opt.textContent.trim(), '(data-status="' + opt.getAttribute('data-status') + '")');
                });
            }

            // Close cabinet dropdown if open
            if (cabinetDropdown) {
                cabinetDropdown.classList.add('hidden');
            }
        });

        // Handle status selection with event delegation (for dynamically added options)
        statusDropdown.addEventListener('click', async (e) => {
            const option = e.target.closest('button[data-status]');
            if (!option) return;

            e.stopPropagation();
            const statusValue = option.getAttribute('data-status');
            const statusText = option.textContent.trim();

            // Update button text
            if (statusDropdownText) {
                statusDropdownText.textContent = statusText;
            }

            // Close dropdown
            statusDropdown.classList.add('hidden');

            // Check if we're on the cabinet grid view or document view
            const cabinetsGrid = document.getElementById('cabinetsGrid');
            const documentsView = document.getElementById('documentsView');
            const isOnCabinetGrid = cabinetsGrid && !cabinetsGrid.classList.contains('hidden');
            const isOnDocumentView = documentsView && !documentsView.classList.contains('hidden');

            if (isOnCabinetGrid) {
                // Filter cabinets by status
                filterCabinetsByStatus(statusValue);
            } else if (isOnDocumentView) {
                // Store current status filter
                currentStatusFilter = statusValue === 'uses' ? 'uses' : statusValue;

                // Get current cabinet ID from URL parameter (primary source)
                const urlParams = new URLSearchParams(window.location.search);
                let cabinetId = urlParams.get('cabinet_id');

                // If no URL param, check if documents view is visible (meaning a cabinet is selected)
                if (!cabinetId) {
                    const cabinetDropdownText = document.getElementById('cabinetDropdownText');
                    if (cabinetDropdownText &&
                        cabinetDropdownText.textContent !== 'Select Cabinet' &&
                        cabinetDropdownText.textContent !== 'All Cabinets') {
                        // Find matching option in dropdown
                        const options = cabinetDropdown.querySelectorAll('button[data-cabinet-id]');
                        for (const opt of options) {
                            if (opt.textContent.trim() === cabinetDropdownText.textContent.trim()) {
                                cabinetId = opt.getAttribute('data-cabinet-id');
                                break;
                            }
                        }
                    }
                }

                if (cabinetId && statusValue !== 'uses') {
                    // Filter documents by status
                    await loadCabinetDocuments(parseInt(cabinetId, 10), statusValue);
                } else if (cabinetId && statusValue === 'uses') {
                    // Load uses list
                    await loadFileUses(parseInt(cabinetId, 10));
                } else {
                    // Reset status filter if no cabinet selected
                    currentStatusFilter = null;
                    if (!cabinetId && statusDropdownText) {
                        statusDropdownText.textContent = 'Select Status';
                    }
                }
            } else {
                // On cabinet grid view without cabinet selected - filter cabinets
                filterCabinetsByStatus(statusValue);
            }
        });
    }

    // Close dropdowns when clicking outside
    document.addEventListener('click', (e) => {
        if (cabinetDropdownBtn && !cabinetDropdownBtn.contains(e.target) && cabinetDropdown && !cabinetDropdown.contains(e.target)) {
            cabinetDropdown.classList.add('hidden');
        }
        if (statusDropdownBtn && !statusDropdownBtn.contains(e.target) && statusDropdown && !statusDropdown.contains(e.target)) {
            statusDropdown.classList.add('hidden');
        }
    });
}

/**
 * Initialize cabinet view functionality
 */
function initCabinetView() {
    // Cabinet view initialization is handled by cabinet-view.js
    // This function is a placeholder for any papers-specific cabinet view setup
}

/**
 * Load cabinets from API and render them
 */
async function loadCabinets(includeArchived = false) {
    try {
        const url = includeArchived
            ? '/OSAS-SIS/backend/CMS/api/cabinets.php?include_archived=true'
            : '/OSAS-SIS/backend/CMS/api/cabinets.php';
        
        console.log('📡 Fetching cabinets from:', url);
        const response = await fetch(url);
        const result = await response.json();

        console.log('📥 API Response:', result);

        if (result.success && result.data) {
            console.log(`✅ Loaded ${result.data.length} cabinet(s) from API`);
            allCabinets = result.data;
            renderCabinets(result.data);
            populateCabinetDropdown(result.data);
            populateStatusDropdown(result.data);
        } else {
            console.error('❌ Failed to load cabinets:', result.message);
            showEmptyCabinetsState();
        }
    } catch (error) {
        console.error('❌ Error loading cabinets:', error);
        showEmptyCabinetsState();
    }
}

/**
 * Populate cabinet dropdown
 */
function populateCabinetDropdown(cabinets) {
    const cabinetDropdown = document.getElementById('cabinetDropdown');
    if (!cabinetDropdown) return;

    const allButton = cabinetDropdown.querySelector('button[data-cabinet="all"]');
    cabinetDropdown.innerHTML = '';
    if (allButton) {
        cabinetDropdown.appendChild(allButton);
    }

    cabinets.forEach(cabinet => {
        const button = document.createElement('button');
        button.className = 'w-full text-left px-4 py-2 hover:bg-gray-100 transition-colors text-sm text-gray-700';
        button.setAttribute('data-cabinet', `cabinet_${cabinet.id}`);
        button.setAttribute('data-cabinet-id', cabinet.id.toString());
        button.textContent = cabinet.name || `Cabinet ${cabinet.id}`;
        cabinetDropdown.appendChild(button);
    });
}

/**
 * Populate status dropdown
 */
function populateStatusDropdown(cabinets) {
    const statusDropdown = document.getElementById('statusDropdown');
    if (!statusDropdown) return;
    // Status dropdown is already populated in HTML, this is a placeholder
}

/**
 * Render cabinet cards
 */
function renderCabinets(cabinets) {
    const cabinetsGrid = document.getElementById('cabinetsGrid');
    const emptyState = document.getElementById('emptyCabinetsState');

    if (!cabinetsGrid) {
        console.error('❌ cabinetsGrid element not found!');
        return;
    }

    console.log('🎨 Rendering cabinets:', cabinets);
    console.log('📦 Number of cabinets:', cabinets?.length || 0);

    // Make sure grid is visible
    cabinetsGrid.classList.remove('hidden');
    
    const existingCards = cabinetsGrid.querySelectorAll('.cabinet-card');
    existingCards.forEach(card => card.remove());

    if (!cabinets || cabinets.length === 0) {
        console.log('⚠️ No cabinets to render, showing empty state');
        if (emptyState) {
            emptyState.classList.remove('hidden');
        }
        return;
    }

    if (emptyState) {
        emptyState.classList.add('hidden');
    }

    cabinets.forEach((cabinet, index) => {
        console.log(`📋 Creating card ${index + 1} for cabinet:`, cabinet.name || cabinet.id);
        const card = createCabinetCard(cabinet);
        if (card) {
            cabinetsGrid.appendChild(card);
            console.log(`✅ Card ${index + 1} appended to grid`);
        } else {
            console.error(`❌ Failed to create card for cabinet ${cabinet.id}`);
        }
    });

    console.log(`✅ Rendered ${cabinets.length} cabinet card(s)`);
}

/**
 * Create a cabinet card element
 */
function createCabinetCard(cabinet) {
    const card = document.createElement('div');
    card.className = 'cabinet-card group cursor-pointer';

    // Status badge styling
    let statusBadgeClass = 'bg-emerald-500';
    let statusText = 'Active';
    let cabinetBg = 'bg-emerald-50';
    let cabinetBorder = 'border-emerald-200';
    let drawerColor = 'bg-emerald-100';
    let handleColor = 'bg-emerald-600';
    let curtainColor = '#10b981';
    let curtainColorDark = '#047857';

    if (cabinet.status === 'pending') {
        statusBadgeClass = 'bg-amber-500';
        statusText = 'Pending';
        cabinetBg = 'bg-amber-50';
        cabinetBorder = 'border-amber-200';
        drawerColor = 'bg-amber-100';
        handleColor = 'bg-amber-600';
        curtainColor = '#f59e0b';
        curtainColorDark = '#b45309';
    } else if (cabinet.status === 'archived') {
        statusBadgeClass = 'bg-gray-400';
        statusText = 'Archived';
        cabinetBg = 'bg-gray-50';
        cabinetBorder = 'border-gray-200';
        drawerColor = 'bg-gray-100';
        handleColor = 'bg-gray-500';
        curtainColor = '#9ca3af';
        curtainColorDark = '#4b5563';
    }

    const fileCount = cabinet.file_count || 0;
    const cabinetName = cabinet.name || 'Cabinet ' + cabinet.id;
    const description = cabinet.description || 'Document storage';
    let hoverHintBg = 'bg-emerald-600';
    let hoverHintText = 'text-white';

    if (cabinet.status === 'pending') {
        hoverHintBg = 'bg-amber-600';
        hoverHintText = 'text-white';
    } else if (cabinet.status === 'archived') {
        hoverHintBg = 'bg-gray-600';
        hoverHintText = 'text-white';
    }

    card.innerHTML = `
        <div class="cabinet-container relative bg-white rounded-xl shadow-md hover:shadow-xl transition-all duration-300 overflow-visible border-2 ${cabinetBorder}" style="--curtain:${curtainColor}; --curtain-dark:${curtainColorDark};" data-cabinet-id="${cabinet.id}" data-cabinet-name="${cabinetName}">
            <div class="cabinet-curtain" aria-hidden="true"></div>
            <!-- Status Badge (Top Right Corner, Middle of Border) - Editable -->
            <div class="status-section absolute -top-3 right-4 z-20">
                <!-- View Mode -->
                <button class="status-badge-view px-2.5 py-1 text-xs font-semibold rounded-full ${statusBadgeClass} text-white shadow-sm hover:opacity-90 transition-opacity cursor-pointer flex items-center gap-1" title="Click to change status">
                    ${statusText}
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                </button>
                
                <!-- Edit Mode (Dropdown) -->
                <div class="status-edit-dropdown hidden absolute right-0 mt-1 w-32 bg-white rounded-lg shadow-lg border border-gray-200 py-1 z-50">
                    <button class="status-option w-full text-left px-3 py-2 text-sm hover:bg-emerald-50 transition-colors ${cabinet.status === 'active' ? 'bg-emerald-100 font-semibold' : ''}" data-status="active">
                        <span class="text-emerald-600">● Active</span>
                    </button>
                    <button class="status-option w-full text-left px-3 py-2 text-sm hover:bg-amber-50 transition-colors ${cabinet.status === 'pending' ? 'bg-amber-100 font-semibold' : ''}" data-status="pending">
                        <span class="text-amber-600">● Pending</span>
                    </button>
                    <button class="status-option w-full text-left px-3 py-2 text-sm hover:bg-gray-50 transition-colors ${cabinet.status === 'archived' ? 'bg-gray-100 font-semibold' : ''}" data-status="archived">
                        <span class="text-gray-600">● Archived</span>
                    </button>
                </div>
            </div>
            
            <!-- Cabinet Body -->
            <div class="cabinet-body p-6 ${cabinetBg} transition-all duration-300">
                <!-- Cabinet Header (Editable Area) -->
                <div class="bg-white rounded-lg p-4 mb-4 shadow-sm border ${cabinetBorder}">
                    <!-- Cabinet Name - Editable -->
                    <div class="name-section mb-2">
                        <!-- View Mode -->
                        <div class="name-view-mode group cursor-pointer" title="Click to edit name">
                            <h3 class="cabinet-name text-lg font-bold text-gray-800 truncate hover:text-[#800000] transition-colors">
                                ${cabinetName}
                            </h3>
                        </div>
                        
                        <!-- Edit Mode -->
                        <div class="name-edit-mode hidden">
                            <div class="flex items-center gap-2">
                                <input 
                                    type="text" 
                                    class="cabinet-name-input flex-1 px-2 py-1 text-lg font-bold text-gray-800 border-2 border-[#800000] rounded focus:ring-2 focus:ring-[#800000] focus:border-[#800000] outline-none"
                                    value="${cabinetName}"
                                />
                                <button class="save-name-btn p-1 text-green-600 hover:bg-green-50 rounded transition-colors" title="Save">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                </button>
                                <button class="cancel-name-btn p-1 text-gray-600 hover:bg-gray-100 rounded transition-colors" title="Cancel">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Cabinet Description - Editable -->
                    <div class="description-section">
                        <!-- View Mode -->
                        <div class="description-view-mode group cursor-pointer" title="Click to edit description">
                            <p class="cabinet-description text-sm text-gray-600 truncate hover:text-[#800000] transition-colors">
                                ${description}
                            </p>
                        </div>
                        
                        <!-- Edit Mode -->
                        <div class="description-edit-mode hidden">
                            <div class="flex items-start gap-2">
                                <textarea 
                                    class="cabinet-description-input flex-1 px-2 py-1 text-sm text-gray-600 border-2 border-[#800000] rounded focus:ring-2 focus:ring-[#800000] focus:border-[#800000] outline-none resize-none"
                                    rows="2"
                                >${description}</textarea>
                                <div class="flex flex-col gap-1">
                                    <button class="save-description-btn p-1 text-green-600 hover:bg-green-50 rounded transition-colors" title="Save">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                        </svg>
                                    </button>
                                    <button class="cancel-description-btn p-1 text-gray-600 hover:bg-gray-100 rounded transition-colors" title="Cancel">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Drawer 1 (Animated) -->
                <div class="drawer-1 relative ${drawerColor} rounded-lg p-4 mb-3 border-2 ${cabinetBorder} transition-all duration-700 shadow-inner">
                    <div class="absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2 ${handleColor} w-16 h-2 rounded-full shadow-md"></div>
                </div>
                
                <!-- Drawer 2 (Animated) -->
                <div class="drawer-2 relative ${drawerColor} rounded-lg p-3 border-2 ${cabinetBorder} transition-all duration-700 shadow-inner">
                    <div class="absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2 ${handleColor} w-12 h-1.5 rounded-full shadow-md"></div>
                </div>
                
                <!-- File Count -->
                <div class="flex items-center justify-center gap-2 mt-4 text-gray-700">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    <span class="text-sm font-semibold">${fileCount} Document${fileCount !== 1 ? 's' : ''}</span>
                </div>
            </div>
            
            <!-- Click hint (appears on hover, only when not editing) -->
            <div class="click-hint absolute inset-0 bg-black/0 group-hover:bg-black/5 transition-all duration-300 flex items-center justify-center pointer-events-none rounded-xl">
                <div class="opacity-0 group-hover:opacity-100 transition-all duration-300 ${hoverHintBg} ${hoverHintText} px-3 py-1.5 rounded-full shadow-lg flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5M7.188 2.239l.777 2.897M5.136 7.965l-2.898-.777M13.95 4.05l-2.122 2.122m-5.657 5.656l-2.12 2.122"></path>
                    </svg>
                    <span class="text-xs font-medium">Click to Open</span>
                </div>
            </div>
        </div>
    `;

    const cabinetContainer = card.querySelector('.cabinet-container');
    const clickHint = card.querySelector('.click-hint');

    cabinetContainer.addEventListener('click', (e) => {
        if (e.target.closest('.status-section') || e.target.closest('.name-section') || e.target.closest('.description-section') || e.target.closest('.status-edit-dropdown')) {
            return;
        }
        e.preventDefault();
        openCabinetWithAnimation(cabinet.id, cabinetContainer);
    });

    // Status badge editing
    const statusBadgeView = card.querySelector('.status-badge-view');
    const statusDropdown = card.querySelector('.status-edit-dropdown');
    const statusOptions = card.querySelectorAll('.status-option');

    if (statusBadgeView && statusDropdown) {
        statusBadgeView.addEventListener('click', (e) => {
            e.stopPropagation();
            statusDropdown.classList.toggle('hidden');
        });

        statusOptions.forEach(option => {
            option.addEventListener('click', async (e) => {
                e.stopPropagation();
                const newStatus = option.getAttribute('data-status');
                if (newStatus === cabinet.status) {
                    statusDropdown.classList.add('hidden');
                    return;
                }
                const Swal = window.Swal;
                if (!Swal) return;
                try {
                    const response = await fetch(`/OSAS-SIS/backend/CMS/api/cabinets.php?id=${cabinet.id}`, {
                        method: 'PUT',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ status: newStatus })
                    });
                    const result = await response.json();
                    if (result.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Status Updated!',
                            text: `Cabinet status changed to ${newStatus}`,
                            confirmButtonColor: '#800000',
                            timer: 1500,
                            showConfirmButton: false
                        });

                        // Respect the currently selected status filter when refreshing
                        const statusDropdownTextEl = document.getElementById('statusDropdownText');
                        let currentFilter = 'all';
                        if (statusDropdownTextEl) {
                            const label = statusDropdownTextEl.textContent.trim().toLowerCase();
                            if (label.includes('archived')) {
                                currentFilter = 'archived';
                            } else if (label.includes('pending')) {
                                currentFilter = 'pending';
                            } else if (label.includes('active')) {
                                currentFilter = 'active';
                            } else {
                                currentFilter = 'all';
                            }
                        }

                        await filterCabinetsByStatus(currentFilter);
                    }
                } catch (error) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Failed to update status: ' + error.message,
                        confirmButtonColor: '#800000'
                    });
                }
                statusDropdown.classList.add('hidden');
            });
        });
    }

    // Name editing
    const nameViewMode = card.querySelector('.name-view-mode');
    const nameEditMode = card.querySelector('.name-edit-mode');
    const nameInput = card.querySelector('.cabinet-name-input');
    const saveNameBtn = card.querySelector('.save-name-btn');
    const cancelNameBtn = card.querySelector('.cancel-name-btn');

    if (nameViewMode && nameEditMode) {
        nameViewMode.addEventListener('click', (e) => {
            e.stopPropagation();
            nameViewMode.classList.add('hidden');
            nameEditMode.classList.remove('hidden');
            clickHint.style.display = 'none';
            nameInput.focus();
            nameInput.select();
        });

        saveNameBtn.addEventListener('click', async (e) => {
            e.stopPropagation();
            const newName = nameInput.value.trim();
            const Swal = window.Swal;
            if (!Swal || !newName) {
                if (Swal) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Cabinet name cannot be empty',
                        confirmButtonColor: '#800000'
                    });
                }
                return;
            }
            try {
                const response = await fetch(`/OSAS-SIS/backend/CMS/api/cabinets.php?id=${cabinet.id}`, {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ name: newName })
                });
                const result = await response.json();
                if (result.success) {
                    card.querySelector('.cabinet-name').textContent = newName;
                    cabinet.name = newName;
                    nameViewMode.classList.remove('hidden');
                    nameEditMode.classList.add('hidden');
                    clickHint.style.display = '';
                    Swal.fire({
                        icon: 'success',
                        title: 'Saved!',
                        text: 'Cabinet name updated successfully',
                        confirmButtonColor: '#800000',
                        timer: 1500,
                        showConfirmButton: false
                    });
                }
            } catch (error) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Failed to update name: ' + error.message,
                    confirmButtonColor: '#800000'
                });
            }
        });

        cancelNameBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            nameInput.value = cabinet.name;
            nameViewMode.classList.remove('hidden');
            nameEditMode.classList.add('hidden');
            clickHint.style.display = '';
        });
    }

    // Description editing
    const descViewMode = card.querySelector('.description-view-mode');
    const descEditMode = card.querySelector('.description-edit-mode');
    const cabinetDescEl = card.querySelector('.cabinet-description');
    const descInput = card.querySelector('.cabinet-description-input');
    const saveDescBtn = card.querySelector('.save-description-btn');
    const cancelDescBtn = card.querySelector('.cancel-description-btn');

    if (descViewMode && descEditMode) {
        descViewMode.addEventListener('click', (e) => {
            e.stopPropagation();
            e.preventDefault();
            descViewMode.classList.add('hidden');
            descEditMode.classList.remove('hidden');
            clickHint.style.display = 'none';
            descInput.focus();
            descInput.setSelectionRange(descInput.value.length, descInput.value.length);
        });

        saveDescBtn.addEventListener('click', async (e) => {
            e.stopPropagation();
            e.preventDefault();
            const newDescription = descInput.value.trim();
            const Swal = window.Swal;
            if (!Swal) return;
            try {
                const response = await fetch(`/OSAS-SIS/backend/CMS/api/cabinets.php?id=${cabinet.id}`, {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ description: newDescription })
                });
                const result = await response.json();
                if (result.success) {
                    if (cabinetDescEl) {
                        cabinetDescEl.textContent = newDescription || 'No description';
                    }
                    cabinet.description = newDescription;
                    descViewMode.classList.remove('hidden');
                    descEditMode.classList.add('hidden');
                    clickHint.style.display = '';
                    Swal.fire({
                        icon: 'success',
                        title: 'Saved!',
                        text: 'Description updated successfully',
                        confirmButtonColor: '#800000',
                        timer: 1500,
                        showConfirmButton: false
                    });
                } else {
                    throw new Error(result.message || 'Failed to update description');
                }
            } catch (error) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Failed to update description: ' + error.message,
                    confirmButtonColor: '#800000'
                });
            }
        });

        cancelDescBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            e.preventDefault();
            descInput.value = cabinet.description || '';
            descViewMode.classList.remove('hidden');
            descEditMode.classList.add('hidden');
            clickHint.style.display = '';
        });
    }

    return card;
}

// Placeholder functions for missing dependencies
function loadCabinetDocuments(cabinetId, status = null) {
    console.log('loadCabinetDocuments called with:', cabinetId, status);
    // This should be implemented or imported from cabinet-view.js
}

function setCabinetNumberFilter(value) {
    console.log('setCabinetNumberFilter called with:', value);
    // This should be implemented
}

function loadFileUses(cabinetId) {
    console.log('loadFileUses called with:', cabinetId);
    // This should be implemented
}

/**
 * Open cabinet with drawer animation
 * @param {number} cabinetId - Cabinet ID
 * @param {HTMLElement} cabinetElement - The cabinet card element
 */
function openCabinetWithAnimation(cabinetId, cabinetElement) {
    // Disable pointer events to prevent multiple clicks
    cabinetElement.style.pointerEvents = 'none';

    // Ensure smooth scaling / fade on the whole cabinet
    cabinetElement.style.transition = 'transform 0.9s cubic-bezier(0.25, 0.8, 0.25, 1), opacity 0.7s ease';

    // Get main drawer and cabinet body
    const drawer1 = cabinetElement.querySelector('.drawer-1');
    const cabinetBody = cabinetElement.querySelector('.cabinet-body');

    // Slight zoom-in of the whole cabinet (feels like camera moving closer)
    setTimeout(() => {
        cabinetElement.style.transform = 'scale(1.06)';
    }, 40);

    // Open main drawer (pulls forward towards viewer smoothly)
    setTimeout(() => {
        drawer1.style.transform = 'translateZ(80px) scale(1.15)';
        drawer1.style.boxShadow = '0 24px 55px rgba(0, 0, 0, 0.4)';
        drawer1.style.zIndex = '10';
    }, 140);

    // Subtle cabinet body background fade while drawer is open
    setTimeout(() => {
        if (cabinetBody) {
            cabinetBody.style.opacity = '0.85';
        }
    }, 260);

    // After drawer has been open for a moment, shrink and fade the whole cabinet
    setTimeout(() => {
        cabinetElement.style.transform = 'scale(0.84) translateY(10px)';
        cabinetElement.style.opacity = '0';
    }, 1200);

    // Navigate to the page after animation completes
    setTimeout(() => {
        const url = `/OSAS-SIS/frontend/CMS/pages/cabinets/view.php?cabinet_id=${cabinetId}`;
        if (typeof window.navigateTo === 'function') {
            window.navigateTo(url);
        } else {
            window.location.href = url;
        }
    }, 1800);
}

/**
 * Show empty cabinets state
 */
function showEmptyCabinetsState() {
    const emptyState = document.getElementById('emptyCabinetsState');
    if (emptyState) {
        emptyState.classList.remove('hidden');
    }
}

/**
 * Filter cabinets by status
 * @param {string} status - Status to filter by ('all', 'active', 'pending', 'archived')
 */
async function filterCabinetsByStatus(status) {
    console.log('Filtering by status:', status);

    // If filtering by archived, reload cabinets with archived included
    if (status === 'archived') {
        console.log('Loading archived cabinets...');
        await loadCabinets(true);
        console.log('All cabinets loaded:', allCabinets);

        const cabinetsGrid = document.getElementById('cabinetsGrid');
        const emptyState = document.getElementById('emptyCabinetsState');

        // Clear existing cards
        if (cabinetsGrid) {
            const existingCards = cabinetsGrid.querySelectorAll('.cabinet-card');
            existingCards.forEach(card => card.remove());
        }

        // Filter to show only archived cabinets
        if (allCabinets && allCabinets.length > 0) {
            const archivedCabinets = allCabinets.filter(cabinet => cabinet.status === 'archived');
            console.log('Archived cabinets found:', archivedCabinets);

            if (archivedCabinets.length === 0) {
                if (emptyState) {
                    emptyState.classList.remove('hidden');
                    const emptyTitle = emptyState.querySelector('h3');
                    const emptyText = emptyState.querySelector('p');
                    if (emptyTitle) emptyTitle.textContent = 'No archived cabinets';
                    if (emptyText) emptyText.textContent = 'You have no archived cabinets at the moment.';

                    let backBtn = emptyState.querySelector('.back-to-all-cabinets-btn');
                    if (!backBtn) {
                        backBtn = document.createElement('button');
                        backBtn.type = 'button';
                        backBtn.className = 'back-to-all-cabinets-btn mt-3 inline-flex items-center px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 transition-colors';
                        backBtn.innerHTML = '&#8592; Back to all cabinets';
                        backBtn.addEventListener('click', async () => {
                            const statusDropdownTextEl = document.getElementById('statusDropdownText');
                            if (statusDropdownTextEl) {
                                statusDropdownTextEl.textContent = 'All Cabinets';
                            }
                            await filterCabinetsByStatus('all');
                        });
                        emptyState.appendChild(backBtn);
                    } else {
                        backBtn.classList.remove('hidden');
                    }
                }
            } else {
                if (emptyState) {
                    const backBtn = emptyState.querySelector('.back-to-all-cabinets-btn');
                    if (backBtn) {
                        backBtn.classList.add('hidden');
                    }
                    emptyState.classList.add('hidden');
                }
                renderCabinets(archivedCabinets);
            }
        } else {
            // No cabinets at all - show empty state
            if (emptyState) {
                emptyState.classList.remove('hidden');
                const emptyTitle = emptyState.querySelector('h3');
                const emptyText = emptyState.querySelector('p');
                if (emptyTitle) emptyTitle.textContent = 'No archived cabinets';
                if (emptyText) emptyText.textContent = 'You have no archived cabinets at the moment.';
                let backBtn = emptyState.querySelector('.back-to-all-cabinets-btn');
                if (!backBtn) {
                    backBtn = document.createElement('button');
                    backBtn.type = 'button';
                    backBtn.className = 'back-to-all-cabinets-btn mt-3 inline-flex items-center px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 transition-colors';
                    backBtn.innerHTML = '&#8592; Back to all cabinets';
                    backBtn.addEventListener('click', async () => {
                        const statusDropdownTextEl = document.getElementById('statusDropdownText');
                        if (statusDropdownTextEl) {
                            statusDropdownTextEl.textContent = 'All Cabinets';
                        }
                        await filterCabinetsByStatus('all');
                    });
                    emptyState.appendChild(backBtn);
                } else {
                    backBtn.classList.remove('hidden');
                }
            }
        }
        return;
    }

    // For other statuses, reload without archived
    if (status === 'all' || status === 'active' || status === 'pending') {
        await loadCabinets(false);
    }

    if (!allCabinets || allCabinets.length === 0) {
        console.log('No cabinets available');
        return;
    }

    let filteredCabinets = allCabinets;

    // Filter cabinets if status is not 'all'
    if (status !== 'all') {
        filteredCabinets = allCabinets.filter(cabinet => {
            const cabinetStatus = cabinet.status || 'active';
            return cabinetStatus === status;
        });
    }

    console.log('Filtered cabinets:', filteredCabinets);

    const emptyState = document.getElementById('emptyCabinetsState');
    if (emptyState) {
        const backBtn = emptyState.querySelector('.back-to-all-cabinets-btn');
        if (backBtn) {
            backBtn.classList.add('hidden');
        }
    }

    renderCabinets(filteredCabinets);
}

/**
 * Initialize Add Cabinet button
 */
function initAddCabinetButton() {
    const addCabinetBtn = document.getElementById('addCabinetBtn');
    if (addCabinetBtn) {
        addCabinetBtn.addEventListener('click', () => {
            showAddCabinetModal();
        });
    }
}

/**
 * Show Add Cabinet modal using SweetAlert2
 * TODO: Import SweetAlert2 if not already imported
 */
async function showAddCabinetModal() {
    const Swal = window.Swal;
    if (!Swal) {
        console.error('SweetAlert2 (Swal) not available');
        return;
    }

    Swal.fire({
        title: 'Add New Cabinet',
        html: `
            <form id="addCabinetForm" class="text-left">
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Cabinet Name</label>
                    <input 
                        type="text" 
                        id="cabinetName" 
                        name="cabinetName" 
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#800000] focus:border-[#800000] outline-none"
                        placeholder="Cabinet 1"
                        value="Cabinet"
                        required
                    />
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Short Description</label>
                    <textarea 
                        id="cabinetDescription" 
                        name="cabinetDescription" 
                        rows="3"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#800000] focus:border-[#800000] outline-none"
                        placeholder="Enter cabinet description"
                    ></textarea>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                    <select 
                        id="cabinetStatus" 
                        name="cabinetStatus" 
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#800000] focus:border-[#800000] outline-none"
                        required
                    >
                        <option value="active">Active</option>
                        <option value="pending">Pending</option>
                        <option value="archived">Archived</option>
                    </select>
                </div>
            </form>
        `,
        showCancelButton: true,
        confirmButtonText: 'Add Cabinet',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#800000',
        cancelButtonColor: '#6b7280',
        width: '500px',
        didOpen: () => {
            const firstInput = document.getElementById('cabinetName');
            if (firstInput) {
                firstInput.focus();
            }
        },
        preConfirm: () => {
            const name = document.getElementById('cabinetName')?.value;
            const description = document.getElementById('cabinetDescription')?.value;
            const status = document.getElementById('cabinetStatus')?.value;

            if (!name || !status) {
                Swal.showValidationMessage('Please fill in all required fields');
                return false;
            }

            return {
                name: name.trim(),
                description: description ? description.trim() : null,
                status
            };
        }
    }).then(async (result) => {
        if (result.isConfirmed && result.value) {
            try {
                const response = await fetch('/OSAS-SIS/backend/CMS/api/cabinets.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(result.value)
                });

                const apiResult = await response.json();

                if (apiResult.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Cabinet Added!',
                        text: `"${result.value.name}" has been added successfully.`,
                        confirmButtonColor: '#800000',
                        timer: 2000,
                        showConfirmButton: false
                    });

                    // Reload cabinets
                    loadCabinets();
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: apiResult.message || 'Failed to add cabinet',
                        confirmButtonColor: '#800000'
                    });
                }
            } catch (error) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Failed to add cabinet: ' + error.message,
                    confirmButtonColor: '#800000'
                });
            }
        }
    });
}

/**
 * Initialize search functionality
 * TODO: Backend implementation - Search will filter by Cabinet Number or File Name
 */
function initSearchFunctionality() {
    const searchInput = document.getElementById('searchPapersInput');
    if (!searchInput) return;

    let searchTimeout;

    searchInput.addEventListener('input', (e) => {
        const searchTerm = e.target.value.trim();

        // Clear previous timeout
        clearTimeout(searchTimeout);

        // Debounce search (wait 300ms after user stops typing)
        searchTimeout = setTimeout(() => {
            if (searchTerm) {
                // Perform client-side search for now
                // TODO: Backend implementation - Call API with search parameter
                // Example: GET /backend/CMS/api/files.php?cabinet_id={id}&search={term}
                performSearch(searchTerm);
            } else {
                // Show all documents if search is cleared
                const rows = document.querySelectorAll('#documentsTableBody tr:not(#emptyStateRow)');
                rows.forEach(row => {
                    row.classList.remove('hidden');
                });
                updateRowNumbersAfterFilter();
                updateDocumentCount();
            }
        }, 300);
    });

    // Global search across all cabinets when user presses Enter
    searchInput.addEventListener('keydown', async (e) => {
        if (e.key !== 'Enter') return;

        e.preventDefault();
        const searchTerm = searchInput.value.trim();
        if (!searchTerm) return;

        await searchFileAcrossCabinets(searchTerm);
    });
}

/**
 * Perform search on documents table
 * @param {string} searchTerm - Search term
 */
function performSearch(searchTerm) {
    const tableBody = document.getElementById('documentsTableBody');
    if (!tableBody) return;

    const rows = tableBody.querySelectorAll('tr:not(#emptyStateRow)');
    let visibleCount = 0;

    rows.forEach(row => {
        // Get cabinet number (column 2) and filename (column 3)
        const cabinetNumberCell = row.querySelector('td:nth-child(2)');
        const fileNameCell = row.querySelector('td:nth-child(3)');

        const cabinetNumber = cabinetNumberCell ? cabinetNumberCell.textContent.trim().toLowerCase() : '';
        const fileName = fileNameCell ? fileNameCell.querySelector('.text-sm.font-medium')?.textContent.trim().toLowerCase() || '' : '';

        const searchLower = searchTerm.toLowerCase();

        // Show row if search term matches cabinet number or filename
        if (cabinetNumber.includes(searchLower) || fileName.includes(searchLower)) {
            row.classList.remove('hidden');
            visibleCount++;
        } else {
            row.classList.add('hidden');
        }
    });

    // Update row numbers and count
    updateRowNumbersAfterFilter();
    updateDocumentCount();

    // Show/hide empty state
    const emptyStateRow = document.getElementById('emptyStateRow');
    if (emptyStateRow) {
        if (visibleCount === 0) {
            emptyStateRow.classList.remove('hidden');
        } else {
            emptyStateRow.classList.add('hidden');
        }
    }
}

/**
 * Perform a global search for a file name across all cabinets using the CMS Files API.
 * Shows a SweetAlert2 modal with cabinet and cabinet number and offers navigation
 * to the matching cabinet view, highlighting the chosen file.
 * @param {string} searchTerm
 */
async function searchFileAcrossCabinets(searchTerm) {
    const Swal = window.Swal;
    if (!Swal) {
        console.error('SweetAlert2 (Swal) not available for global search');
        return;
    }

    try {
        const response = await fetch(`/OSAS-SIS/backend/CMS/api/files.php?search=${encodeURIComponent(searchTerm)}`);
        const result = await response.json();

        if (!result.success || !Array.isArray(result.data) || result.data.length === 0) {
            await Swal.fire({
                icon: 'info',
                title: 'No matching files',
                text: `No file found matching "${searchTerm}" across all cabinets.`,
                confirmButtonColor: '#800000'
            });
            return;
        }

        const files = result.data;

        // Single match: show direct confirmation modal
        if (files.length === 1) {
            const file = files[0];
            const cabinetName = file.cabinet_name || `Cabinet ${file.cabinet_id}`;
            const cabinetNumber = file.cabinet_number || 'N/A';

            const { isConfirmed } = await Swal.fire({
                icon: 'success',
                title: 'File found',
                html: `
                    <div class="text-left space-y-2">
                        <div class="text-sm text-gray-700"><span class="font-semibold">File Name:</span> ${file.filename}</div>
                        <div class="text-sm text-gray-700"><span class="font-semibold">Cabinet:</span> ${cabinetName}</div>
                        <div class="text-sm text-gray-700"><span class="font-semibold">Cabinet Number:</span> ${cabinetNumber}</div>
                    </div>
                `,
                showCancelButton: true,
                confirmButtonText: 'Go to Cabinet →',
                cancelButtonText: 'Close',
                confirmButtonColor: '#800000',
                cancelButtonColor: '#6b7280',
                width: '480px'
            });

            if (isConfirmed) {
                navigateToFileInCabinet(file);
            }
            return;
        }

        // Multiple matches: let the user pick which file to open
        const inputOptions = {};
        files.forEach((file) => {
            const cabinetName = file.cabinet_name || `Cabinet ${file.cabinet_id}`;
            const cabinetNumber = file.cabinet_number || 'N/A';
            inputOptions[file.id] = `${file.filename} — ${cabinetName} (${cabinetNumber})`;
        });

        const { isConfirmed, value: selectedId } = await Swal.fire({
            title: `Select file (${files.length} matches)`,
            input: 'select',
            inputOptions,
            inputPlaceholder: 'Choose a file to open',
            showCancelButton: true,
            confirmButtonText: 'Go to Cabinet →',
            cancelButtonText: 'Cancel',
            confirmButtonColor: '#800000',
            cancelButtonColor: '#6b7280',
            width: '520px',
            inputValidator: (value) => {
                if (!value) {
                    return 'Please select a file to open';
                }
                return null;
            }
        });

        if (isConfirmed && selectedId) {
            const selectedFile = files.find((file) => String(file.id) === String(selectedId));
            if (selectedFile) {
                navigateToFileInCabinet(selectedFile);
            }
        }
    } catch (error) {
        console.error('Error performing global file search:', error);
        await Swal.fire({
            icon: 'error',
            title: 'Search failed',
            text: 'An unexpected error occurred while searching. Please try again.',
            confirmButtonColor: '#800000'
        });
    }
}

/**
 * Navigate to the cabinet view page for the given file and highlight the file row.
 * @param {Object} file
 */
function navigateToFileInCabinet(file) {
    if (!file || !file.cabinet_id || !file.id) {
        console.error('navigateToFileInCabinet: missing cabinet_id or file id', file);
        return;
    }

    const url = `/OSAS-SIS/frontend/CMS/pages/cabinets/view.php?cabinet_id=${file.cabinet_id}&file_id=${file.id}`;

    if (typeof window.navigateTo === 'function') {
        window.navigateTo(url);
    } else {
        window.location.href = url;
    }
}

/**
 * Initialize document action buttons (View, Edit, Archive)
 */
function initDocumentActionButtons() {
    // View buttons
    const viewButtons = document.querySelectorAll('.view-file-btn');
    viewButtons.forEach(btn => {
        btn.addEventListener('click', async () => {
            const fileId = btn.getAttribute('data-file-id');
            await showViewFileModal(fileId);
        });
    });

    // Edit buttons
    const editButtons = document.querySelectorAll('.edit-file-btn');
    editButtons.forEach(btn => {
        btn.addEventListener('click', async () => {
            const fileId = btn.getAttribute('data-file-id');
            await showEditFileModal(fileId);
        });
    });

    // Archive buttons
    const archiveButtons = document.querySelectorAll('.archive-file-btn');
    archiveButtons.forEach(btn => {
        btn.addEventListener('click', async () => {
            const fileId = btn.getAttribute('data-file-id');
            const fileName = btn.getAttribute('data-file-name');
            await archiveFile(fileId, fileName);
        });
    });

    // Unarchive buttons
    const unarchiveButtons = document.querySelectorAll('.unarchive-file-btn');
    unarchiveButtons.forEach(btn => {
        btn.addEventListener('click', async () => {
            const fileId = btn.getAttribute('data-file-id');
            const fileName = btn.getAttribute('data-file-name');
            await unarchiveFile(fileId, fileName);
        });
    });
}

/**
 * Show View File modal using SweetAlert2
 * @param {number} fileId - File ID
 */
async function showViewFileModal(fileId) {
    const Swal = window.Swal;
    if (!Swal) {
        console.error('SweetAlert2 (Swal) not available');
        return;
    }
    try {
        // Fetch file details from CMS Files API
        const response = await fetch(`/OSAS-SIS/backend/CMS/api/files.php?id=${fileId}`);
        const result = await response.json();

        if (!result.success || !result.data) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'File not found',
                confirmButtonColor: '#800000'
            });
            return;
        }

        const file = result.data;

        // Category badge color
        let categoryBadgeClass = 'bg-purple-100 text-purple-800';
        if (file.category === 'Sports') {
            categoryBadgeClass = 'bg-blue-100 text-blue-800';
        } else if (file.category === 'Objects') {
            categoryBadgeClass = 'bg-orange-100 text-orange-800';
        }

        // Status badge color
        let statusBadgeClass = 'bg-green-100 text-green-800';
        if (file.status === 'borrowed') {
            statusBadgeClass = 'bg-yellow-100 text-yellow-800';
        } else if (file.status === 'archived') {
            statusBadgeClass = 'bg-gray-100 text-gray-800';
        }

        const statusText = file.status === 'borrowed' ? 'Borrowed' : (file.status === 'archived' ? 'Archived' : 'Available');

        Swal.fire({
            title: 'File Details',
            html: `
                <div class="text-left space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Cabinet Number</label>
                        <p class="text-sm text-gray-900">${file.cabinet_number}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">File Name</label>
                        <p class="text-sm text-gray-900">${file.filename}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                        <p class="text-sm text-gray-600">${file.description || 'No description'}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Category</label>
                        <span class="px-2 py-1 text-xs rounded-full ${categoryBadgeClass}">${file.category || 'Documents'}</span>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                        <span class="px-2 py-1 text-xs rounded-full ${statusBadgeClass}">${statusText}</span>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Added By</label>
                        <p class="text-sm text-gray-900">${file.added_by || 'Admin'}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Created At</label>
                        <p class="text-sm text-gray-600">${new Date(file.created_at).toLocaleString()}</p>
                    </div>
                </div>
            `,
            confirmButtonText: 'Close',
            confirmButtonColor: '#800000',
            width: '500px'
        });
    } catch (error) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Failed to load file details: ' + error.message,
            confirmButtonColor: '#800000'
        });
    }
}

/**
 * Show Edit File modal using SweetAlert2
 * @param {number} fileId - File ID
 */
async function showEditFileModal(fileId) {
    const Swal = window.Swal;
    if (!Swal) {
        console.error('SweetAlert2 (Swal) not available');
        return;
    }
    try {
        // Fetch file details from CMS Files API
        const response = await fetch(`/OSAS-SIS/backend/CMS/api/files.php?id=${fileId}`);
        const result = await response.json();

        if (!result.success || !result.data) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'File not found',
                confirmButtonColor: '#800000'
            });
            return;
        }

        const file = result.data;

        Swal.fire({
            title: 'Edit Document',
            html: `
                <form id="editFileForm" class="text-left">
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">File Name</label>
                        <input 
                            type="text" 
                            id="editFileName" 
                            name="filename" 
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#800000] focus:border-[#800000] outline-none"
                            value="${file.filename}"
                            required
                        />
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                        <textarea 
                            id="editFileDescription" 
                            name="description" 
                            rows="3"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#800000] focus:border-[#800000] outline-none"
                        >${file.description || ''}</textarea>
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Category</label>
                        <select 
                            id="editFileCategory" 
                            name="category" 
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#800000] focus:border-[#800000] outline-none"
                            required
                        >
                            <option value="Documents" ${file.category === 'Documents' ? 'selected' : ''}>Documents</option>
                            <option value="Sports" ${file.category === 'Sports' ? 'selected' : ''}>Sports</option>
                            <option value="Objects" ${file.category === 'Objects' ? 'selected' : ''}>Objects</option>
                        </select>
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                        <select 
                            id="editFileStatus" 
                            name="status" 
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#800000] focus:border-[#800000] outline-none"
                            required
                        >
                            <option value="available" ${file.status === 'available' ? 'selected' : ''}>Available</option>
                            <option value="borrowed" ${file.status === 'borrowed' ? 'selected' : ''}>Borrowed</option>
                        </select>
                    </div>
                </form>
            `,
            showCancelButton: true,
            confirmButtonText: 'Update Document',
            cancelButtonText: 'Cancel',
            confirmButtonColor: '#800000',
            cancelButtonColor: '#6b7280',
            width: '500px',
            didOpen: () => {
                const firstInput = document.getElementById('editFileName');
                if (firstInput) {
                    firstInput.focus();
                }
            },
            preConfirm: () => {
                const filename = document.getElementById('editFileName')?.value;
                const description = document.getElementById('editFileDescription')?.value;
                const category = document.getElementById('editFileCategory')?.value;
                const status = document.getElementById('editFileStatus')?.value;

                if (!filename || !category || !status) {
                    Swal.showValidationMessage('Please fill in all required fields');
                    return false;
                }

                return {
                    filename: filename.trim(),
                    description: description ? description.trim() : null,
                    category,
                    status
                };
            }
        }).then(async (result) => {
            if (result.isConfirmed && result.value) {
                try {
                    const response = await fetch(`/OSAS-SIS/backend/CMS/api/files.php?id=${fileId}`, {
                        method: 'PATCH',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify(result.value)
                    });

                    const apiResult = await response.json();

                    if (apiResult.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Document Updated!',
                            text: `"${result.value.filename}" has been updated successfully.`,
                            confirmButtonColor: '#800000',
                            timer: 2000,
                            showConfirmButton: false
                        });

                        // Reload documents for current cabinet
                        const selectedCabinetName = document.getElementById('selectedCabinetName');
                        if (selectedCabinetName) {
                            // Get cabinet ID from selected cabinet name or current view
                            const cabinetDropdownText = document.getElementById('cabinetDropdownText');
                            let cabinetId = null;

                            if (cabinetDropdownText && cabinetDropdownText.textContent.trim() !== 'Select Cabinet' && cabinetDropdownText.textContent.trim() !== 'All Cabinets') {
                                // Try to extract from cabinet name or get from data attribute
                                const cabinetCard = document.querySelector(`.cabinet-container[data-cabinet-name="${cabinetDropdownText.textContent.trim()}"]`);
                                if (cabinetCard) {
                                    cabinetId = cabinetCard.getAttribute('data-cabinet-id');
                                }
                            }

                            if (cabinetId) {
                                loadCabinetDocuments(cabinetId);
                            }
                        }
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: apiResult.message || 'Failed to update document',
                            confirmButtonColor: '#800000'
                        });
                    }
                } catch (error) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Failed to update document: ' + error.message,
                        confirmButtonColor: '#800000'
                    });
                }
            }
        });
    } catch (error) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Failed to load file details: ' + error.message,
            confirmButtonColor: '#800000'
        });
    }
}

/**
 * Archive file (soft delete)
 * @param {number} fileId - File ID
 * @param {string} fileName - File name for confirmation message
 */
async function archiveFile(fileId, fileName) {
    const Swal = window.Swal;
    if (!Swal) {
        console.error('SweetAlert2 (Swal) not available');
        return;
    }
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
                    method: 'DELETE'
                });

                const apiResult = await response.json();

                if (apiResult.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Archived!',
                        text: 'Document has been archived.',
                        confirmButtonColor: '#800000',
                        timer: 1500,
                        showConfirmButton: false
                    });

                    // Reload documents for current cabinet
                    const cabinetDropdownText = document.getElementById('cabinetDropdownText');
                    if (cabinetDropdownText && cabinetDropdownText.textContent.trim() !== 'Select Cabinet' && cabinetDropdownText.textContent.trim() !== 'All Cabinets') {
                        const cabinetCard = document.querySelector(`.cabinet-container[data-cabinet-name="${cabinetDropdownText.textContent.trim()}"]`);
                        if (cabinetCard) {
                            const cabinetId = cabinetCard.getAttribute('data-cabinet-id');
                            loadCabinetDocuments(cabinetId);
                        }
                    }
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: apiResult.message || 'Failed to archive document',
                        confirmButtonColor: '#800000'
                    });
                }
            } catch (error) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Failed to archive document: ' + error.message,
                    confirmButtonColor: '#800000'
                });
            }
        }
    });
}

/**
 * Unarchive file (restore from archived to available)
 * @param {number} fileId - File ID
 * @param {string} fileName - File name for confirmation message
 */
async function unarchiveFile(fileId, fileName) {
    const Swal = window.Swal;
    if (!Swal) {
        console.error('SweetAlert2 (Swal) not available');
        return;
    }
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
                    method: 'PATCH',
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
                        confirmButtonColor: '#800000',
                        timer: 1500,
                        showConfirmButton: false
                    });

                    // Reset status filter back to All after unarchiving
                    const statusDropdownText = document.getElementById('statusDropdownText');
                    if (statusDropdownText) {
                        statusDropdownText.textContent = '📋 All Cabinets';
                    }

                    // Clear current status filter so all non-archived docs are shown
                    currentStatusFilter = null;

                    // Reload documents for current cabinet
                    const cabinetDropdownText = document.getElementById('cabinetDropdownText');
                    if (cabinetDropdownText && cabinetDropdownText.textContent.trim() !== 'Select Cabinet' && cabinetDropdownText.textContent.trim() !== 'All Cabinets') {
                        const cabinetCard = document.querySelector(`.cabinet-container[data-cabinet-name="${cabinetDropdownText.textContent.trim()}"]`);
                        if (cabinetCard) {
                            const cabinetId = cabinetCard.getAttribute('data-cabinet-id');
                            loadCabinetDocuments(cabinetId);
                        }
                    }
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: apiResult.message || 'Failed to unarchive document',
                        confirmButtonColor: '#800000'
                    });
                }
            } catch (error) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Failed to unarchive document: ' + error.message,
                    confirmButtonColor: '#800000'
                });
            }
        }
    });
}
