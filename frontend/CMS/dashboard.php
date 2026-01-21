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
    <link rel="icon" type="image/png" href="../images/spc.png">
    <title>Document Management - DSA Project</title>
    
    <!-- Load Styles -->
    <?= vite(['frontend/css/styles.css']) ?>
    
    <?php if ($isDev): ?>
        <!-- Vite HMR Client - Must be loaded first for auto-refresh -->
        <script type="module">
            import('/@vite/client').catch(err => console.error('Vite client error:', err));
        </script>
    <?php endif; ?>
    <!-- ApexCharts -->
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <!-- Iconify -->
    <script src="https://code.iconify.design/3/3.1.0/iconify.min.js"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap');
        
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        
        .hover-card { transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1); }
        .hover-card:hover { transform: translateY(-5px); box-shadow: 0 15px 30px -5px rgba(0, 0, 0, 0.1); }
    </style>
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
                    <h2 class="text-xl font-semibold text-gray-800">Storage Management Dashboard</h2>
                    
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
                <!-- Stats Cards (CMS Style) -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                    <!-- Total Items Card -->
                    <div class="hover-card bg-white rounded-2xl p-6 shadow-sm border border-slate-100 relative overflow-hidden group">
                        <div class="absolute right-0 top-0 h-full w-1 bg-[#800000]"></div>
                        <div class="flex items-center justify-between mb-4">
                            <div class="p-3 bg-red-50 rounded-xl text-[#800000] group-hover:scale-110 transition-transform">
                                <span class="iconify w-6 h-6" data-icon="solar:folder-with-files-bold"></span>
                            </div>
                            <span class="text-[10px] font-bold text-[#800000] bg-red-50 px-2 py-1 rounded-md text-right">TOTAL SUPPLIES/EQUIPMENT</span>
                        </div>
                        <p class="text-3xl font-extrabold text-slate-800" id="totalDocuments">0</p>
                        <p class="text-sm text-slate-400 font-medium mt-1">Stored Items</p>
                    </div>
                    
                    <!-- Total Cabinets Card -->
                     <div class="hover-card bg-white rounded-2xl p-6 shadow-sm border border-slate-100 relative overflow-hidden group">
                        <div class="absolute right-0 top-0 h-full w-1 bg-[#800000] opacity-60"></div>
                        <div class="flex items-center justify-between mb-4">
                            <div class="p-3 bg-red-50 rounded-xl text-[#800000]/80 group-hover:scale-110 transition-transform">
                                <span class="iconify w-6 h-6" data-icon="solar:archive-bold"></span>
                            </div>
                            <span class="text-xs font-bold text-[#800000]/80 bg-red-50 px-2 py-1 rounded-md">CABINETS</span>
                        </div>
                        <p class="text-3xl font-extrabold text-slate-800" id="totalCabinets">0</p>
                        <p class="text-sm text-slate-400 font-medium mt-1">Storage Units</p>
                    </div>
                    
                    <!-- Pending Review Card -->
                     <div class="hover-card bg-white rounded-2xl p-6 shadow-sm border border-slate-100 relative overflow-hidden group">
                        <div class="absolute right-0 top-0 h-full w-1 bg-amber-500"></div>
                        <div class="flex items-center justify-between mb-4">
                            <div class="p-3 bg-amber-50 rounded-xl text-amber-500 group-hover:scale-110 transition-transform">
                                <span class="iconify w-6 h-6" data-icon="solar:clock-circle-bold"></span>
                            </div>
                            <span class="text-xs font-bold text-amber-600 bg-amber-50 px-2 py-1 rounded-md">PENDING</span>
                        </div>
                        <p class="text-3xl font-extrabold text-slate-800" id="pendingCabinets">0</p>
                        <p class="text-sm text-slate-400 font-medium mt-1">Awaiting Action</p>
                    </div>
                    
                    <!-- Archived Card -->
                     <div class="hover-card bg-white rounded-2xl p-6 shadow-sm border border-slate-100 relative overflow-hidden group">
                        <div class="absolute right-0 top-0 h-full w-1 bg-slate-400"></div>
                        <div class="flex items-center justify-between mb-4">
                            <div class="p-3 bg-slate-100 rounded-xl text-slate-500 group-hover:scale-110 transition-transform">
                                <span class="iconify w-6 h-6" data-icon="solar:box-bold"></span>
                            </div>
                            <span class="text-xs font-bold text-slate-500 bg-slate-100 px-2 py-1 rounded-md">ARCHIVED</span>
                        </div>
                        <p class="text-3xl font-extrabold text-slate-800" id="archivedFiles">0</p>
                        <p class="text-sm text-slate-400 font-medium mt-1">History</p>
                    </div>
                </div>
                
                <!-- Charts Section (Dynamic ApexCharts) -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                    <!-- Distribution Chart (Bar) -->
                    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6 hover-card">
                        <h3 class="text-lg font-bold text-slate-900 mb-1">Item Distribution</h3>
                        <p class="text-sm text-slate-500 mb-6">Breakdown by category</p>
                        <!-- Added inline style to guarantee height if tailwind fails -->
                        <div id="chart-distribution" style="height: 320px; width: 100%;"></div>
                    </div>
                    
                    <!-- Status Chart (Donut) -->
                    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6 hover-card">
                        <h3 class="text-lg font-bold text-slate-900 mb-1">Storage Status</h3>
                        <p class="text-sm text-slate-500 mb-6">Current active status of items</p>
                         <!-- Removed flex, added inline style -->
                        <div id="chart-status" style="height: 320px; width: 100%;"></div>
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
            import { initDashboard } from '/OSAS-SIS/backend/CMS/js/pages/dashboard.js?v=<?= time() ?>';
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
