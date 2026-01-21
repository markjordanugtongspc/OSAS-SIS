    <?php
// Start session and initialize variables for navbar
session_start();
$firstname = $_SESSION['firstname'] ?? 'User';
$lastname = $_SESSION['lastname'] ?? '';
$position = $_SESSION['position'] ?? '';

// Include Vite Helper (go up to project root, then into backend)
require_once __DIR__ . '/../../../../backend/vite_helper.php';

// Development mode detection - use built JS bundle when available
$isDev = !file_exists(__DIR__ . '/../../../../dist/backend/js/pages/cabinet-view.js');

// Get cabinet ID from query parameter (numeric ID, e.g., ?cabinet_id=1)
$cabinetId = isset($_GET['cabinet_id']) ? intval($_GET['cabinet_id']) : null;
$cabinetName = $cabinetId ? 'Cabinet ' . $cabinetId : 'Cabinet';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="../../../images/spc.png">
    <title><?php echo $cabinetName; ?> - DSA Project</title>

    <!-- Load Styles -->
    <?= vite(['frontend/css/styles.css']) ?>
    <style>
        /* Limit file name/description width in the Documents table */
        #documentsTableBody td:nth-child(3),
        #documentsTableBody td:nth-child(5) {
            max-width: 18rem !important;
            overflow: hidden !important;
            white-space: nowrap !important;
            text-overflow: ellipsis !important;
        }

        #documentsTableBody td:nth-child(3) > div,
        #documentsTableBody td:nth-child(5) > div {
            max-width: 18rem !important;
            white-space: nowrap !important;
            overflow: hidden !important;
            text-overflow: ellipsis !important;
        }

        /* Fixed-size View Document modal */
        .swal2-popup.swal2-doc-view-modal {
            width: 520px !important;
            max-width: 95vw !important;
            padding: 1.25rem 1.5rem !important;
        }

        .swal2-doc-view-modal .swal2-html-container {
            max-height: 60vh;
            overflow-y: auto;
            text-align: left;
            margin-top: 0.75rem;
        }

        .swal2-doc-view-modal .swal2-title {
            font-size: 1rem;
            line-height: 1.3;
            white-space: normal;
        }
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
    <?php include '../../../pages/navbar.php'; ?>

    <!-- Main Content Area -->
    <div class="ml-64 min-h-screen transition-all duration-300 flex flex-col">
            
            <!-- SPA Marker for SIS Sidebar integration -->
            <div id="spaContentMarker" style="display:none"></div>
            
            <!-- Header -->
            <header id="mainHeader" class="bg-transparent shadow-sm border-b border-gray-200">
                <div class="flex items-center justify-between px-4 sm:px-6 lg:px-8 py-4">
                    <!-- Back Button and Cabinet Title -->
                    <div class="flex items-center gap-4">
                        <a href="/OSAS-SIS/frontend/CMS/pages/papers.php" class="p-2 rounded-lg hover:bg-gray-100 transition-colors cursor-pointer">
                            <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 15L3 9m0 0l6-6M3 9h12a6 6 0 010 12h-3"></path>
                            </svg>
                        </a>
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="iconify text-[#047857]" data-icon="mdi:archive" data-width="22" data-height="22"></span>
                                <h2 id="cabinetViewTitle" class="text-xl sm:text-2xl font-bold text-gray-800"><?php echo $cabinetName; ?></h2>
                            </div>
                            <p class="text-gray-500 text-xs sm:text-sm">View and manage documents in this cabinet</p>
                        </div>
                    </div>
                    
                    <!-- Right Side Actions -->
                    <div class="flex items-center gap-3">
                        <!-- Bulk Add Button -->
                        <button id="bulkAddBtn" class="bg-white border border-[#800000] text-[#800000] px-4 py-2 rounded-lg hover:bg-gray-50 transition-colors flex items-center gap-2 cursor-pointer">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                            </svg>
                            <span>Bulk Add</span>
                        </button>
                        
                        <!-- Add Document Button -->
                        <button id="addDocumentBtn" class="bg-[#800000] text-white px-4 py-2 rounded-lg hover:bg-[#700000] transition-colors flex items-center gap-2 cursor-pointer">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                            </svg>
                            <span>Add Document</span>
                        </button>
                    </div>
                </div>
            </header>
            
            <!-- Main Content -->
            <main class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8 bg-gray-50">
                
                <!-- Search Bar and Filters -->
                <div class="mb-6">
                    <div class="flex flex-col md:flex-row gap-3">
                        <!-- Search Input -->
                        <div class="relative flex-1">
                            <input 
                                type="text" 
                                id="searchDocumentsInput" 
                                placeholder="Search documents by file name or category..." 
                                class="w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#800000] focus:border-[#800000] outline-none text-sm"
                            >
                            <svg class="w-5 h-5 text-gray-400 absolute left-3 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                        </div>
                        
                        <!-- Filter Buttons -->
                        <div class="flex flex-wrap gap-2">
                            <!-- Cabinet Number Dropdown -->
                            <div class="relative">
                                <button id="cabinetNumberSortBtn" class="px-4 py-2.5 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors text-sm cursor-pointer flex items-center gap-2 whitespace-nowrap">
                                    <span id="cabinetNumberSortText">All Numbers</span>
                                    <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                    </svg>
                                </button>
                                
                                <!-- Cabinet Number Sort Dropdown Menu -->
                                <div id="cabinetNumberSortDropdown" class="hidden absolute left-0 mt-2 w-48 bg-white rounded-lg shadow-lg border border-gray-200 py-2 z-50">
                                    <button class="w-full text-left px-4 py-2 hover:bg-gray-100 transition-colors text-sm text-gray-700" data-cabinet-number="all">
                                        All Cabinet Numbers
                                    </button>
                                    <!-- Dynamically populated via JavaScript -->
                                </div>
                            </div>
                            
                            <!-- Category Dropdown -->
                            <div class="relative">
                                <button id="categorySortBtn" class="px-4 py-2.5 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors text-sm cursor-pointer flex items-center gap-2 whitespace-nowrap">
                                    <span id="categorySortText">All Categories</span>
                                    <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                    </svg>
                                </button>
                                
                                <!-- Category Sort Dropdown Menu -->
                                <div id="categorySortDropdown" class="hidden absolute left-0 mt-2 w-40 bg-white rounded-lg shadow-lg border border-gray-200 py-2 z-50">
                                    <button class="w-full text-left px-4 py-2 hover:bg-gray-100 transition-colors text-sm text-gray-700" data-category="all">
                                        All Categories
                                    </button>
                                    <!-- Dynamically populated via JavaScript -->
                                </div>
                            </div>
                            
                            <!-- OSAS Services Dropdown -->
                            <div class="relative">
                                <button id="osasServiceSortBtn" class="px-4 py-2.5 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors text-sm cursor-pointer flex items-center gap-2 whitespace-nowrap">
                                    <span id="osasServiceSortText">All Services</span>
                                    <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                    </svg>
                                </button>
                                
                                <!-- OSAS Services Sort Dropdown Menu -->
                                <div id="osasServiceSortDropdown" class="hidden absolute left-0 mt-2 w-64 bg-white rounded-lg shadow-lg border border-gray-200 py-2 z-50 max-h-60 overflow-y-auto">
                                    <button class="w-full text-left px-4 py-2 hover:bg-gray-100 transition-colors text-sm text-gray-700" data-osas-service="all">
                                        All Services
                                    </button>
                                    <!-- Dynamically populated via JavaScript -->
                                </div>
                            </div>
                            
                            <!-- Status Dropdown -->
                            <div class="relative">
                                <button id="statusSortBtn" class="px-4 py-2.5 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors text-sm cursor-pointer flex items-center gap-2 whitespace-nowrap">
                                    <span id="statusSortText">All Status</span>
                                    <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                    </svg>
                                </button>
                                
                                <!-- Status Sort Dropdown Menu -->
                                <div id="statusSortDropdown" class="hidden absolute right-0 mt-2 w-40 bg-white rounded-lg shadow-lg border border-gray-200 py-2 z-50">
                                    <button class="w-full text-left px-4 py-2 hover:bg-gray-100 transition-colors text-sm text-gray-700" data-status="all">
                                        All Status
                                    </button>
                                    <button class="w-full text-left px-4 py-2 hover:bg-gray-100 transition-colors text-sm text-gray-700" data-status="available">
                                        Available
                                    </button>
                                    <button class="w-full text-left px-4 py-2 hover:bg-gray-100 transition-colors text-sm text-gray-700" data-status="borrowed">
                                        Borrowed
                                    </button>
                                    <button class="w-full text-left px-4 py-2 hover:bg-gray-100 transition-colors text-sm text-gray-700" data-status="archived">
                                        Archived
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Documents Table -->
                <div class="bg-white rounded-lg shadow-md overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                        <h3 class="text-lg font-semibold text-gray-800">Documents</h3>
                        <div class="flex items-center gap-4">
                            <span class="text-sm text-gray-500" id="documentCount">0 documents</span>
                            <button type="button" id="exportTableBtn" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-white bg-[#800020] hover:bg-[#5c0016] rounded-md transition-colors cursor-pointer" title="Export table to PDF">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-3.5 h-3.5">
                                    <path d="M6 9V2h12v7"></path>
                                    <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
                                    <path d="M6 14h12v8H6z"></path>
                                </svg>
                                Export PDF
                            </button>
                        </div>
                    </div>
                    
                <!-- Table -->
                <div class="overflow-x-auto overflow-y-auto max-h-[70vh]">
                    <table class="w-full table-fixed">
                        <thead class="bg-[#800000] sticky top-0 z-10">
                            <tr>
                                <th class="w-[5%] px-6 py-3 text-center text-xs font-medium text-white uppercase tracking-wider whitespace-nowrap">NO.</th>
                                <th class="w-[15%] px-6 py-3 text-center text-xs font-medium text-white uppercase tracking-wider whitespace-nowrap">Cabinet Number</th>
                                <th class="w-[30%] px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider whitespace-nowrap">File Name</th>
                                <th class="w-[10%] px-6 py-3 text-center text-xs font-medium text-white uppercase tracking-wider whitespace-nowrap">Category</th>
                                <th class="w-[15%] px-6 py-3 text-center text-xs font-medium text-white uppercase tracking-wider whitespace-nowrap">OSAS Services</th>
                                <th class="w-[10%] px-6 py-3 text-center text-xs font-medium text-white uppercase tracking-wider whitespace-nowrap">Status</th>
                                <th class="w-[15%] px-6 py-3 text-center text-xs font-medium text-white uppercase tracking-wider whitespace-nowrap">Actions</th>
                            </tr>
                        </thead>
                            <tbody id="documentsTableBody" class="bg-white divide-y divide-gray-200">
                                <!-- Documents will be loaded dynamically via JavaScript API -->
                                
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
            </main>
        </div>
    
    <?php if ($isDev): ?>
        <!-- Load your JavaScript entry point -->
        <script type="module">
            import { initCabinetView } from '/OSAS-SIS/backend/CMS/js/pages/cabinet-view.js?v=<?php echo time(); ?>';
            window.initCMSCabinetView = initCabinetView;
            initCabinetView();
        </script>
    <?php else: ?>
        <!-- Production: Load built assets -->
        <script type="module">
            import { initCabinetView } from '/OSAS-SIS/dist/backend/js/pages/cabinet-view.js';
            window.initCMSCabinetView = initCabinetView;
            initCabinetView();
        </script>
    <?php endif; ?>
</body>
</html>
