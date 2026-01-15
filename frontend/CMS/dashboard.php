<?php
// Start session and initialize variables for navbar
session_start();
$firstname = $_SESSION['firstname'] ?? 'User';
$lastname = $_SESSION['lastname'] ?? '';
$position = $_SESSION['position'] ?? '';

// Include Vite Helper
require_once __DIR__ . '/../../backend/vite_helper.php';

// Development mode detection - use built JS bundle when available
$isDev = !file_exists(__DIR__ . '/../../dist/backend/js/pages/dashboard.js');
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document Management - DSA Project</title>
    
    <!-- Load Styles -->
    <?= vite(['frontend/css/styles.css']) ?>
    
    <?php if ($isDev): ?>
        <!-- Vite HMR Client - Must be loaded first for auto-refresh -->
        <script type="module">
            import('/@vite/client').catch(err => console.error('Vite client error:', err));
        </script>
    <?php endif; ?>
 </head>
<body class="bg-gray-50 min-h-screen">
    
    <!-- Sidebar Navigation -->
    <?php include '../pages/navbar.php'; ?>

    <!-- Main Content Area -->
    <div class="ml-64 min-h-screen transition-all duration-300 flex flex-col">
        
        <!-- SPA Marker for SIS Sidebar integration -->
        <div id="spaContentMarker" style="display:none"></div>
        
        <!-- Header -->
        <header class="bg-white shadow-sm border-b border-gray-200">
                <div class="flex items-center justify-between px-4 sm:px-6 lg:px-8 py-4">
                    <!-- Page Title -->
                    <h2 class="text-xl font-semibold text-gray-800">Document Management Dashboard</h2>
                    
                    <!-- Right Side Actions -->
                    <div class="flex items-center gap-4">
                        <!-- Add Document Button -->
                        <button id="addDocumentBtn" class="bg-[#800020] text-white px-4 py-2 rounded-lg hover:bg-[#5c0016] transition-colors flex items-center gap-2 cursor-pointer">
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
                <!-- Stats Cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                    <!-- Total Documents Card -->
                    <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-[#800020]">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-600">Total Documents</p>
                                <p class="text-2xl font-bold text-gray-900 mt-1" id="totalDocuments">0</p>
                            </div>
                            <div class="w-12 h-12 bg-[#800020]/10 rounded-lg flex items-center justify-center">
                                <svg class="w-6 h-6 text-[#800020]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                            </div>
                        </div>
                        <p class="text-xs text-emerald-600 mt-2">+12% from last month</p>
                    </div>
                    
                    <!-- Total Cabinets Card -->
                    <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-amber-500/80">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-600">Total Cabinets</p>
                                <p class="text-2xl font-bold text-gray-900 mt-1" id="totalCabinets">0</p>
                            </div>
                            <div class="w-12 h-12 bg-amber-500/10 rounded-lg flex items-center justify-center">
                                <svg class="w-6 h-6 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                            </div>
                        </div>
                        <p class="text-xs text-emerald-600 mt-2">+8% from last month</p>
                    </div>
                    
                    <!-- Pending Documents Card -->
                    <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-amber-500/80">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-600">Pending Review</p>
                                <p class="text-2xl font-bold text-gray-900 mt-1" id="pendingCabinets">0</p>
                            </div>
                            <div class="w-12 h-12 bg-amber-500/10 rounded-lg flex items-center justify-center">
                                <svg class="w-6 h-6 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                        </div>
                        <p class="text-xs text-orange-600 mt-2">Requires attention</p>
                    </div>
                    
                    <!-- Archived Documents Card -->
                    <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-slate-400/80">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-600">Archived</p>
                                <p class="text-2xl font-bold text-gray-900 mt-1" id="archivedFiles">0</p>
                            </div>
                            <div class="w-12 h-12 bg-slate-500/5 rounded-lg flex items-center justify-center">
                                <svg class="w-6 h-6 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"></path>
                                </svg>
                            </div>
                        </div>
                        <p class="text-xs text-gray-500 mt-2">Stored documents</p>
                    </div>
                </div>
                
                <!-- Charts Section -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                    <!-- Documents vs Papers Pie Chart -->
                    <div class="bg-white rounded-lg shadow-md p-6">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4">Documents vs Papers Distribution</h3>
                        <div class="h-64 flex items-center justify-center">
                            <!-- Pie Chart Placeholder -->
                            <div class="relative w-48 h-48">
                                <!-- Pie Chart SVG -->
                                <svg class="transform -rotate-90 w-full h-full" viewBox="0 0 100 100" id="pieChartSvg">
                                    <!-- Documents -->
                                    <circle id="pieChartDocumentsCircle" cx="50" cy="50" r="40" fill="none" stroke="#800020" stroke-width="20" 
                                            stroke-dasharray="0 0" stroke-dashoffset="0" />
                                    <!-- Others -->
                                    <circle id="pieChartOthersCircle" cx="50" cy="50" r="40" fill="none" stroke="#fbbf24" stroke-width="20" 
                                            stroke-dasharray="0 0" stroke-dashoffset="0" />
                                </svg>
                                <!-- Center Text -->
                                <div class="absolute inset-0 flex items-center justify-center">
                                    <div class="text-center">
                                        <p class="text-2xl font-bold text-gray-800" id="pieChartTotal">0</p>
                                        <p class="text-xs text-gray-600">Total Items</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- Legend -->
                        <div class="flex justify-center gap-6 mt-4 flex-wrap">
                            <div class="flex items-center gap-2">
                                <div class="w-4 h-4 rounded-full bg-[#800020]"></div>
                                <span class="text-sm text-gray-700"><span id="pieChartDocumentsLabel">Documents</span> (<span id="pieChartDocumentsPercent">0</span>%)</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <div class="w-4 h-4 rounded-full bg-[#fbbf24]"></div>
                                <span class="text-sm text-gray-700"><span id="pieChartOthersLabel">Others</span> (<span id="pieChartOthersPercent">0</span>%)</span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Document Statistics Chart -->
                    <div class="bg-white rounded-lg shadow-md p-6">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4">Document Statistics</h3>
                        <div class="h-64 flex items-center justify-center bg-gray-50 rounded-lg">
                            <div class="w-full space-y-4">
                                <!-- Bar Chart Representation -->
                                <div class="space-y-3">
                                    <div>
                                        <div class="flex justify-between text-sm text-gray-600 mb-1">
                                            <span>Available</span>
                                            <span id="barChartAvailableCount">0</span>
                                        </div>
                                        <div class="w-full bg-gray-200 rounded-full h-4">
                                            <div id="barChartAvailableBar" class="bg-[#800020] h-4 rounded-full" style="width: 0%"></div>
                                        </div>
                                    </div>
                                    <div>
                                        <div class="flex justify-between text-sm text-gray-600 mb-1">
                                            <span>Borrowed</span>
                                            <span id="barChartBorrowedCount">0</span>
                                        </div>
                                        <div class="w-full bg-gray-200 rounded-full h-4">
                                            <div id="barChartBorrowedBar" class="bg-amber-500 h-4 rounded-full" style="width: 0%"></div>
                                        </div>
                                    </div>
                                    <div>
                                        <div class="flex justify-between text-sm text-gray-600 mb-1">
                                            <span>Archived</span>
                                            <span id="barChartArchivedCount">0</span>
                                        </div>
                                        <div class="w-full bg-gray-200 rounded-full h-4">
                                            <div id="barChartArchivedBar" class="bg-slate-400 h-4 rounded-full" style="width: 0%"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Recent Documents Table -->
                <div class="bg-white rounded-lg shadow-md p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-gray-800">Recent Documents</h3>
                        <!-- Search Bar -->
                        <div class="flex items-center gap-2">
                            <div class="relative">
                                <input type="text" id="dashboardSearchInput" placeholder="Search documents..." 
                                       class="pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#800000] focus:border-[#800000] outline-none text-sm">
                                <svg class="w-5 h-5 text-gray-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                </svg>
                            </div>
                        </div>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Document Name</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Category</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date Added</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                </tr>
                            </thead>
                            <tbody id="recentDocumentsTableBody" class="divide-y divide-gray-200">
                                <!-- Recent documents will be dynamically loaded via JavaScript -->
                            </tbody>
                        </table>
                    </div>
                </div>
        </main>
    </div>
    
    <?php if ($isDev): ?>
        <!-- Load your JavaScript entry point -->
        <script type="module">
            import { initDashboard } from '/OSAS-SIS/backend/CMS/js/pages/dashboard.js';
            window.initCMSDashboard = initDashboard;
            initDashboard();
        </script>
    <?php else: ?>
        <!-- Production: Load built assets -->
        <script type="module">
            import { initDashboard } from '/OSAS-SIS/dist/backend/js/pages/dashboard.js';
            window.initCMSDashboard = initDashboard;
            initDashboard();
        </script>
    <?php endif; ?>
</body>
</html>
