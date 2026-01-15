<?php
// Start session and initialize variables for navbar
session_start();
$firstname = $_SESSION['firstname'] ?? 'User';
$lastname = $_SESSION['lastname'] ?? '';
$position = $_SESSION['position'] ?? '';

// Include Vite Helper
require_once __DIR__ . '/../../../backend/vite_helper.php';

// Development mode detection - use built JS bundle when available
$isDev = !file_exists(__DIR__ . '/../../../dist/backend/js/pages/papers.js');
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title>Cabinets - DSA Project (v2.0)</title>

    <!-- Load Styles (keep global styles only; curtain styles scoped below) -->
    <?= vite(['frontend/css/styles.css']) ?>
    
    <!-- Curtain styling scoped to cabinets on this page -->
    <style>
        /* Compact filter spacing */
        #filtersSection { margin-bottom: 0.75rem; }
        #cabinetsGrid { margin-top: 0.25rem; }
        
        .cabinet-curtain {
            position: absolute;
            left: 0;
            right: 0;
            top: -6px; /* lift slightly */
            height: 44px; /* smaller curtain */
            border-top-left-radius: 0.75rem;
            border-top-right-radius: 0.75rem;
            z-index: 10;
            pointer-events: none;
            background:
                radial-gradient(120px 40px at 20% 0%, rgba(255,255,255,0.35), rgba(255,255,255,0) 70%),
                radial-gradient(120px 40px at 80% 0%, rgba(255,255,255,0.25), rgba(255,255,255,0) 70%),
                repeating-linear-gradient(
                    90deg,
                    rgba(255,255,255,0.16) 0px,
                    rgba(255,255,255,0.16) 10px,
                    rgba(0,0,0,0.06) 18px,
                    rgba(255,255,255,0.12) 28px
                ),
                linear-gradient(180deg, var(--curtain, #10b981) 0%, var(--curtain-dark, #047857) 100%);
            box-shadow:
                inset 0 -10px 18px rgba(0, 0, 0, 0.14),
                0 10px 22px rgba(0, 0, 0, 0.10);
        }
        .cabinet-curtain::after {
            content: "";
            position: absolute;
            left: 0;
            right: 0;
            bottom: -10px;
            height: 22px;
            background:
                radial-gradient(circle at 12px 0px, var(--curtain-dark, #047857) 12px, transparent 13px) 0 0 / 24px 22px repeat-x;
            filter: drop-shadow(0 6px 8px rgba(0,0,0,0.12));
            opacity: 0.95;
        }
        .cabinet-curtain::before {
            content: "";
            position: absolute;
            left: 18px;
            right: 18px;
            top: 10px;
            height: 6px;
            border-radius: 999px;
            background: rgba(255,255,255,0.35);
            box-shadow: 0 2px 6px rgba(0,0,0,0.16);
            opacity: 0.9;
        }
        .cabinet-container { box-shadow: 0 12px 30px rgba(0, 0, 0, 0.12); }
        .cabinet-container .cabinet-body { padding-top: 3.6rem; }
        .cabinet-container:hover .cabinet-curtain { transform: translateY(-2px); transition: transform 180ms ease; }
    </style>
    
    <!-- Iconify -->
    <script src="https://code.iconify.design/3/3.1.0/iconify.min.js"></script>
    
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <?php if ($isDev): ?>
        <!-- Vite HMR Client - Must be loaded first for auto-refresh -->
        <script type="module">
            import('/@vite/client').catch(err => console.error('Vite client error:', err));
        </script>
    <?php endif; ?>
 </head>
<body class="bg-gray-50 min-h-screen">
    
    <!-- Sidebar Navigation -->
    <?php include '../../pages/navbar.php'; ?>

    <!-- Main Content Area -->
    <div class="ml-64 min-h-screen transition-all duration-300 flex flex-col">
        
        <!-- SPA Marker for SIS Sidebar integration -->
        <div id="spaContentMarker" style="display:none"></div>
        
        <!-- Header -->
        <header id="mainHeader" class="bg-transparent shadow-sm border-b border-gray-200">
                <div class="flex items-center justify-between px-4 sm:px-6 lg:px-8 py-4">
                    <!-- Page Title -->
                    <h2 class="text-xl font-semibold text-gray-800">Cabinets Management</h2>
                    
                    <!-- Right Side Actions -->
                    <div class="flex items-center gap-4">
                        <!-- Add Cabinet Button -->
                        <button id="addCabinetBtn" class="bg-[#800000] text-white px-4 py-2 rounded-lg hover:bg-[#700000] transition-colors flex items-center gap-2 cursor-pointer">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                            </svg>
                            <span>Add Cabinet</span>
                        </button>
                    </div>
                </div>
        </header>
        
        <!-- Main Content -->
        <main class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8 bg-gray-50">
                <!-- Filters and Search -->
                <div id="filtersSection" class="mb-6">
                    <div class="flex flex-col md:flex-row gap-4 items-center justify-between flex-wrap">
                        <div id="searchBarContainer" class="relative flex-1 w-full md:w-auto">
                            <input type="text" id="searchPapersInput" placeholder="Search papers..." 
                                   class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#800000] focus:border-[#800000] outline-none text-sm">
                            <svg class="w-5 h-5 text-gray-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                        </div>
                        
                        <!-- Filter Buttons -->
                        <div class="flex flex-wrap gap-2 ml-auto">
                            <!-- Cabinet Dropdown -->
                            <div class="relative">
                                <button id="cabinetDropdownBtn" class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors text-sm cursor-pointer flex items-center gap-2">
                                    <span id="cabinetDropdownText">Select Cabinet</span>
                                    <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                    </svg>
                                </button>
                                
                                <!-- Cabinet Dropdown Menu -->
                                <div id="cabinetDropdown" class="hidden absolute left-0 mt-2 w-48 bg-white rounded-lg shadow-lg border border-gray-200 py-2 z-50">
                                    <button class="w-full text-left px-4 py-2 hover:bg-gray-100 transition-colors text-sm text-gray-700" data-cabinet="all">
                                        All Cabinets
                                    </button>
                                    <!-- Dynamically populated via JavaScript -->
                                </div>
                            </div>
                            
                            <!-- Status Dropdown - Updated v2.0 with Archive Support -->
                            <div class="relative">
                                <button id="statusDropdownBtn" class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors text-sm cursor-pointer flex items-center gap-2">
                                    <span id="statusDropdownText">All Cabinets</span>
                                    <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                    </svg>
                                </button>
                                
                                <!-- Status Dropdown Menu - INCLUDES ARCHIVED OPTION -->
                                <div id="statusDropdown" class="hidden absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg border border-gray-200 py-2 z-50 max-h-64 overflow-y-auto">
                                    <button class="w-full text-left px-4 py-2 hover:bg-gray-100 transition-colors text-sm text-gray-700 font-medium" data-status="all">
                                        📋 All Cabinets
                                    </button>
                                    <button class="w-full text-left px-4 py-2 hover:bg-gray-100 transition-colors text-sm text-gray-700" data-status="active">
                                        ✓ Active
                                    </button>
                                    <button class="w-full text-left px-4 py-2 hover:bg-gray-100 transition-colors text-sm text-gray-700" data-status="pending">
                                        ⏳ Pending
                                    </button>
                                    <div class="border-t border-gray-200 my-1"></div>
                                    <button class="w-full text-left px-4 py-2 hover:bg-gray-100 transition-colors text-sm text-gray-700 font-medium" data-status="archived">
                                        📦 Archived
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Documents Table View (Hidden by default) -->
                <div id="documentsView" class="hidden">
                    <div class="mb-6 flex items-center justify-between">
                        <div class="flex items-center gap-4">
                            <button id="backToCabinetsBtn" class="p-2 rounded-lg hover:bg-gray-100 transition-colors cursor-pointer">
                                <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                                </svg>
                            </button>
                            <div class="flex items-center gap-3">
                                <div>
                                    <h2 id="selectedCabinetName" class="text-2xl font-bold text-gray-800"></h2>
                                    <p class="text-gray-600">View and manage documents in this cabinet</p>
                                </div>
                                <button id="editSelectedCabinetBtn" class="p-2 rounded-lg hover:bg-gray-100 transition-colors group" title="Edit Cabinet">
                                    <svg class="w-5 h-5 text-gray-400 group-hover:text-[#800000] cursor-pointer" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                    </svg>
                                </button>
                            </div>
                        </div>
                        <button id="addDocumentBtn" class="bg-[#800000] text-white px-4 py-2 rounded-lg hover:bg-[#700000] transition-colors flex items-center gap-2 cursor-pointer">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                            </svg>
                            <span>Add Document</span>
                        </button>
                    </div>
                    
                    <!-- Documents Table -->
                    <div class="bg-white rounded-lg shadow-md overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                            <h3 class="text-lg font-semibold text-gray-800">Documents</h3>
                            <div class="flex items-center gap-3">
                                <!-- Cabinet Number filter (for testing sort/filter across C1.1/C1.2/C1.3) -->
                                <div class="relative">
                                    <button id="cabinetNumberFilterBtn" class="px-3 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors text-sm cursor-pointer flex items-center gap-2">
                                        <span id="cabinetNumberFilterText">All Cabinet Numbers</span>
                                        <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                        </svg>
                                    </button>
                                    <div id="cabinetNumberFilterDropdown" class="hidden absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg border border-gray-200 py-2 z-50">
                                        <!-- Dynamically populated via JavaScript based on selected cabinet -->
                                        <button class="w-full text-left px-4 py-2 hover:bg-gray-100 transition-colors text-sm text-gray-700" data-cabinet-number="all">
                                            All Cabinet Numbers
                                        </button>
                                    </div>
                                </div>

                                <span class="text-sm text-gray-500" id="documentCount">0 documents</span>
                            </div>
                        </div>
                        
                        <!-- Table -->
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">NO.</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cabinet Number</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">File Name</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Added By</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                                    </tr>
                                </thead>
                                <tbody id="documentsTableBody" class="bg-white divide-y divide-gray-200">
                                    <!-- Documents will be dynamically generated via JavaScript based on selected cabinet -->
                                    <!-- Example Document Row Structure (for reference):
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">1</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">C1.1</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">Document Name</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">Category Name</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">Admin User</td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800">Available</span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                                            <div class="flex items-center gap-2">
                                                <button class="edit-document-btn p-1.5 rounded-lg hover:bg-gray-100 transition-colors group" data-document-id="1" title="Edit Document">
                                                    <svg class="w-4 h-4 text-gray-400 group-hover:text-[#800000] cursor-pointer" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                                    </svg>
                                                </button>
                                                <button class="view-document-btn p-1.5 rounded-lg hover:bg-gray-100 transition-colors group" data-document-id="1" title="View Details">
                                                    <svg class="w-4 h-4 text-gray-400 group-hover:text-[#800000] cursor-pointer" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                                    </svg>
                                                </button>
                                                <button class="delete-document-btn p-1.5 rounded-lg hover:bg-red-50 transition-colors group" data-document-id="1" title="Delete Document">
                                                    <svg class="w-4 h-4 text-gray-400 group-hover:text-red-600 cursor-pointer" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                    </svg>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    -->
                                    
                                    <!-- Empty State (will be shown when no documents) -->
                                    <tr id="emptyStateRow" class="hidden">
                                        <td colspan="7" class="px-6 py-12 text-center">
                                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                            </svg>
                                            <h3 class="mt-2 text-sm font-medium text-gray-900">No documents</h3>
                                            <p class="mt-1 text-sm text-gray-500">Get started by adding a new document.</p>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                <!-- Cabinets Grid -->
                <div id="cabinetsGrid" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 pt-4">
                    <!-- Cabinets will be dynamically loaded via JavaScript -->
                    <!-- Example Cabinet Card Structure (for reference):
                    <div class="bg-white rounded-lg shadow-md hover:shadow-lg transition-shadow overflow-hidden">
                        <div class="p-6">
                            <div class="flex items-start justify-between mb-4">
                                <div class="flex-1">
                                    <h3 class="text-lg font-semibold text-gray-800">Cabinet Name</h3>
                                    <p class="text-sm text-gray-500 mt-1">Description</p>
                                </div>
                                <button class="edit-cabinet-btn p-2 rounded-lg hover:bg-gray-100 transition-colors group" data-cabinet-id="1" title="Edit Cabinet">
                                    <svg class="w-5 h-5 text-gray-400 group-hover:text-[#800000] cursor-pointer" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                    </svg>
                                </button>
                            </div>
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-gray-600">0 documents</span>
                                <button class="view-cabinet-btn text-[#800000] hover:text-[#600000] font-medium cursor-pointer" data-cabinet-id="1">
                                    View Details →
                                </button>
                            </div>
                        </div>
                    </div>
                    -->
                    
                    <!-- Empty State (shown when no cabinets) -->
                    <div id="emptyCabinetsState" class="hidden col-span-full text-center py-12">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">No cabinets</h3>
                        <p class="mt-1 text-sm text-gray-500">Get started by adding a new cabinet.</p>
                    </div>
                </div>
        </main>
    </div>
    
    <?php if ($isDev): ?>
        <!-- Load your JavaScript entry point -->
        <script type="module">
            import { initPapers } from '/OSAS-SIS/backend/CMS/js/pages/papers.js';
            window.initCMSPapers = initPapers;
            initPapers();
        </script>
    <?php else: ?>
        <!-- Production: Load built assets -->
        <script type="module">
            import { initPapers } from '/OSAS-SIS/dist/backend/js/pages/papers.js';
            window.initCMSPapers = initPapers;
            initPapers();
        </script>
    <?php endif; ?>
</body>
</html>
