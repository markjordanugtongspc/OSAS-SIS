<?php
session_start();
require_once '../../backend/vite_helper.php';
require_once '../../config/db.php';

// Security check
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../index.php");
    exit;
}

$firstname = htmlspecialchars($_SESSION['firstname']);
$lastname = htmlspecialchars($_SESSION['lastname']);
$position = htmlspecialchars($_SESSION['position']);

// Automatically detect current semester based on date
// Academic Calendar (adjust months as needed for your institution):
// 1st Semester: August - December
// 2nd Semester: January - May  
// Summer: June - July
$current_month = date('n'); // 1-12
$current_year = date('Y');
$current_date_formatted = date('F j, Y'); // e.g., "January 14, 2026"

if ($current_month >= 8 && $current_month <= 12) {
    // August to December = 1st Semester
    $current_semester = '1st Semester';
    $academic_year = $current_year . '-' . ($current_year + 1);
} elseif ($current_month >= 1 && $current_month <= 5) {
    // January to May = 2nd Semester
    $current_semester = '2nd Semester';
    $academic_year = ($current_year - 1) . '-' . $current_year;
} else {
    // June to July = Summer
    $current_semester = 'Summer';
    $academic_year = $current_year . '-' . ($current_year + 1);
}

// Fetch inventory statistics
try {
    // Total items (sum of all quantities)
    $stmt = $pdo->query("SELECT SUM(quantity) as total FROM items");
    $total_items = $stmt->fetch()['total'] ?? 0;
    
    // Available items (sum of quantities)
    $stmt = $pdo->query("SELECT SUM(quantity) as total FROM items WHERE status = 'Available'");
    $available_items = $stmt->fetch()['total'] ?? 0;
    
    // Unavailable items (sum of quantities)
    $stmt = $pdo->query("SELECT SUM(quantity) as total FROM items WHERE status = 'Unavailable'");
    $unavailable_items = $stmt->fetch()['total'] ?? 0;
    
    // Damaged items (sum of quantities)
    $stmt = $pdo->query("SELECT SUM(quantity) as total FROM items WHERE status = 'Damaged'");
    $damaged_items = $stmt->fetch()['total'] ?? 0;
    
    // Recently added in current semester (sum of quantities)
    $stmt = $pdo->prepare("SELECT SUM(quantity) as total FROM items WHERE semester = :semester");
    $stmt->execute(['semester' => $current_semester]);
    $semester_items = $stmt->fetch()['total'] ?? 0;
    
    // Low stock items (sum of quantities where quantity < 5)
    $stmt = $pdo->query("SELECT SUM(quantity) as total FROM items WHERE quantity < 5 AND quantity > 0");
    $low_stock_items = $stmt->fetch()['total'] ?? 0;
    
    // Out of stock (count of items, not quantity since they're 0)
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM items WHERE quantity = 0");
    $out_of_stock = $stmt->fetch()['total'] ?? 0;
    
    // Recent items (sum of quantities added in last 7 days)
    $stmt = $pdo->query("SELECT SUM(quantity) as total FROM items WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
    $recent_additions = $stmt->fetch()['total'] ?? 0;
    
    // Total value of inventory
    $stmt = $pdo->query("SELECT SUM(price * quantity) as total_value FROM items");
    $total_value = $stmt->fetch()['total_value'] ?? 0;
    
    // Category breakdown (sum of quantities)
    $stmt = $pdo->query("SELECT category, SUM(quantity) as count FROM items GROUP BY category ORDER BY count DESC LIMIT 6");
    $categories = $stmt->fetchAll();
    
    // Sport breakdown (sum of quantities)
    $stmt = $pdo->query("SELECT sport, SUM(quantity) as count FROM items WHERE sport IS NOT NULL AND sport != '' GROUP BY sport ORDER BY count DESC LIMIT 6");
    $sports = $stmt->fetchAll();
    
    // Recent items list
    $stmt = $pdo->query("SELECT * FROM items ORDER BY created_at DESC LIMIT 5");
    $recent_items = $stmt->fetchAll();

    $stmt = $pdo->query("SELECT * FROM items ORDER BY item_name ASC");
    $all_items = $stmt->fetchAll();
    
} catch (PDOException $e) {
    $total_items = 0;
    $available_items = 0;
    $unavailable_items = 0;
    $damaged_items = 0;
    $semester_items = 0;
    $low_stock_items = 0;
    $out_of_stock = 0;
    $recent_additions = 0;
    $total_value = 0;
    $categories = [];
    $sports = [];
    $recent_items = [];
    $all_items = [];
}

// Calculate percentages
$available_percentage = $total_items > 0 ? round(($available_items / $total_items) * 100, 1) : 0;
$unavailable_percentage = $total_items > 0 ? round(($unavailable_items / $total_items) * 100, 1) : 0;
$damaged_percentage = $total_items > 0 ? round(($damaged_items / $total_items) * 100, 1) : 0;
?>
<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="../../frontend/images/spc.png">
    <title>Item Inventory | OSAS SIS</title>
    <?= vite(['backend/js/main.js', 'frontend/css/styles.css']) ?>
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <style>
        /* shadcn/ui inspired styles */
        .card {
            @apply bg-white rounded-lg border-2 border-slate-200 shadow-sm;
        }
        
        .card-hover {
            @apply transition-all duration-200 hover:shadow-lg hover:border-slate-400;
        }
        
        .stat-card {
            @apply relative overflow-hidden;
        }
        
        /* Smooth animations */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .animate-in {
            animation: fadeIn 0.4s ease-out forwards;
        }
        
        /* Custom scrollbar */
        .custom-scrollbar::-webkit-scrollbar {
            width: 8px;
        }
        
        .custom-scrollbar::-webkit-scrollbar-track {
            background: #f1f5f9;
            border-radius: 4px;
        }
        
        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 4px;
        }
        
        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }
        
        /* Progress bar animation */
        .progress-bar {
            transition: width 1s ease-out;
        }
        
        /* Badge styles */
        .badge {
            @apply inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset;
        }
    </style>
</head>
<body class="h-full">
    
    <!-- Include Sidebar -->
    <?php include 'navbar.php'; ?>

    <!-- Inventory Items Modal (centered, consistent UI) -->
    <div id="inventoryModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/30 backdrop-blur-sm" aria-modal="true" role="dialog">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-3xl mx-4 max-h-[85vh] overflow-hidden flex flex-col">
            <div class="flex items-center justify-between px-5 py-3 border-b border-slate-200">
                <h3 id="inventoryModalTitle" class="text-sm font-semibold text-slate-900">Inventory Items</h3>
                <div class="flex items-center gap-2">
                    <button type="button" onclick="printModalItems()" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-white bg-[#800020] hover:bg-[#5c0016] rounded-md transition-colors cursor-pointer" title="Print / Save as PDF">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-3.5 h-3.5">
                            <path d="M6 9V2h12v7"></path>
                            <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
                            <path d="M6 14h12v8H6z"></path>
                        </svg>
                        Export PDF
                    </button>
                    <button type="button" onclick="closeInventoryModal()" class="p-1.5 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition-colors cursor-pointer">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-4 h-4">
                            <path fill-rule="evenodd" d="M5.47 5.47a.75.75 0 0 1 1.06 0L12 10.94l5.47-5.47a.75.75 0 1 1 1.06 1.06L13.06 12l5.47 5.47a.75.75 0 0 1-1.06 1.06L12 13.06l-5.47 5.47a.75.75 0 0 1-1.06-1.06L10.94 12 5.47 6.53a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" />
                        </svg>
                    </button>
                </div>
            </div>

            <div class="p-4 overflow-y-auto custom-scrollbar text-sm">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-semibold text-slate-700 uppercase tracking-wider">Item</th>
                            <th class="px-4 py-2 text-left text-xs font-semibold text-slate-700 uppercase tracking-wider">Category</th>
                            <th class="px-4 py-2 text-center text-xs font-semibold text-slate-700 uppercase tracking-wider">Qty</th>
                            <th class="px-4 py-2 text-center text-xs font-semibold text-slate-700 uppercase tracking-wider">Status</th>
                        </tr>
                    </thead>
                    <tbody id="inventoryModalBody" class="bg-white divide-y divide-slate-100"></tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="ml-64 min-h-screen bg-slate-50/50">
        
        <!-- Page Header -->
        <div class="sticky top-0 z-20 bg-white/80 backdrop-blur-md border-b border-slate-200">
            <div class="px-8 py-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-2xl font-semibold text-slate-900 tracking-tight">Inventory Overview</h1>
                        <p class="mt-1 text-sm text-slate-600">Monitor and manage your equipment inventory</p>
                    </div>

                    <div class="flex items-center gap-3">
                        <!-- Export and Add Item buttons removed as per user request -->
                    </div>
                </div>
            </div>
        </div>

        <!-- Content Area -->
        <div class="px-8 py-8 space-y-6">
            
            <!-- Primary Stats Grid -->
            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4 animate-in">
                
                <!-- Total Items -->
                <div class="group relative bg-white rounded-xl border-2 border-slate-200 p-4 hover:border-[#800020]/30 hover:shadow-xl transition-all duration-300 cursor-pointer" onclick="openInventoryModal('all')">
                    <div class="flex items-start justify-between mb-3">
                        <div class="p-2 rounded-lg bg-gradient-to-br from-[#800020]/10 to-[#5c0016]/5 group-hover:from-[#800020]/20 group-hover:to-[#5c0016]/10 transition-all duration-300">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5 text-[#800020]">
                                <rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect>
                                <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path>
                            </svg>
                        </div>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-slate-600 mb-1">Total Items</p>
                        <h3 class="text-3xl font-bold text-slate-900 mb-1"><?= number_format($total_items) ?></h3>
                        <p class="text-[10px] text-slate-500">All equipment in system</p>
                    </div>
                </div>

                <!-- Available Items -->
                <div class="group relative bg-white rounded-xl border-2 border-emerald-200 p-4 hover:border-emerald-400 hover:shadow-xl transition-all duration-300 cursor-pointer" onclick="openInventoryModal('available')">
                    <div class="flex items-start justify-between mb-3">
                        <div class="p-2 rounded-lg bg-gradient-to-br from-emerald-100 to-emerald-50 group-hover:from-emerald-200 group-hover:to-emerald-100 transition-all duration-300">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5 text-emerald-600">
                                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                <polyline points="22 4 12 14.01 9 11.01"></polyline>
                            </svg>
                        </div>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-emerald-700 mb-1">Available</p>
                        <h3 class="text-3xl font-bold text-emerald-600 mb-1"><?= number_format($available_items) ?></h3>
                        <p class="text-[10px] text-emerald-600/70"><span class="font-semibold"><?= $available_percentage ?>%</span> of total inventory</p>
                    </div>
                </div>

                <!-- Unavailable Items -->
                <div class="group relative bg-white rounded-xl border-2 border-orange-200 p-4 hover:border-orange-400 hover:shadow-xl transition-all duration-300 cursor-pointer" onclick="openInventoryModal('unavailable')">
                    <div class="flex items-start justify-between mb-3">
                        <div class="p-2 rounded-lg bg-gradient-to-br from-orange-100 to-orange-50 group-hover:from-orange-200 group-hover:to-orange-100 transition-all duration-300">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5 text-orange-600">
                                <circle cx="12" cy="12" r="10"></circle>
                                <line x1="15" y1="9" x2="9" y2="15"></line>
                                <line x1="9" y1="9" x2="15" y2="15"></line>
                            </svg>
                        </div>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-orange-700 mb-1">Unavailable</p>
                        <h3 class="text-3xl font-bold text-orange-600 mb-1"><?= number_format($unavailable_items) ?></h3>
                        <p class="text-[10px] text-orange-600/70"><span class="font-semibold"><?= $unavailable_percentage ?>%</span> currently in use</p>
                    </div>
                </div>

                <!-- Damaged Items -->
                <div class="group relative bg-white rounded-xl border-2 border-red-200 p-4 hover:border-red-400 hover:shadow-xl transition-all duration-300 cursor-pointer" onclick="openInventoryModal('damaged')">
                    <div class="flex items-start justify-between mb-3">
                        <div class="p-2 rounded-lg bg-gradient-to-br from-red-100 to-red-50 group-hover:from-red-200 group-hover:to-red-100 transition-all duration-300">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5 text-red-600">
                                <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                                <line x1="12" y1="9" x2="12" y2="13"></line>
                                <line x1="12" y1="17" x2="12.01" y2="17"></line>
                            </svg>
                        </div>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-red-700 mb-1">Damaged</p>
                        <h3 class="text-3xl font-bold text-red-600 mb-1"><?= number_format($damaged_items) ?></h3>
                        <p class="text-[10px] text-red-600/70"><span class="font-semibold"><?= $damaged_percentage ?>%</span> need repair</p>
                    </div>
                </div>

            </div>

            <!-- Secondary Stats Row -->
            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4 animate-in" style="animation-delay: 0.1s">
                
                <!-- Current Semester -->
                <!-- Current Semester (Auto-detected) -->
                <div onclick="openFilterModal()" class="group relative bg-gradient-to-br from-[#800020] via-[#6b001a] to-[#5c0016] rounded-xl p-4 text-white hover:shadow-2xl transition-all duration-300 overflow-hidden cursor-pointer ring-offset-2 focus-within:ring-2 ring-[#800020]">
                    <div class="absolute top-0 right-0 w-32 h-32 bg-white/5 rounded-full -mr-16 -mt-16 group-hover:scale-110 transition-transform duration-500"></div>
                    
                    <!-- Filter Icon Badge -->
                    <div class="absolute top-3 right-3 p-1.5 rounded-full bg-white/10 backdrop-blur-sm opacity-0 group-hover:opacity-100 transition-opacity duration-300 hover:bg-white/20">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4 text-white">
                            <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"></polygon>
                        </svg>
                    </div>

                    <div class="relative">
                        <div class="flex items-start justify-between mb-3">
                            <div class="p-2 rounded-lg bg-white/10 backdrop-blur-sm">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5">
                                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                                    <line x1="16" y1="2" x2="16" y2="6"></line>
                                    <line x1="8" y1="2" x2="8" y2="6"></line>
                                    <line x1="3" y1="10" x2="21" y2="10"></line>
                                </svg>
                            </div>
                        </div>
                        <div>
                            <p class="text-xs font-medium text-white/80 mb-2">Current Date</p>
                            <h3 class="text-lg font-bold mb-3"><?= $current_date_formatted ?></h3>
                            <div class="flex flex-col gap-1">
                                <p class="text-sm font-semibold text-white/90"><?= $current_semester ?></p>
                                <p class="text-xs text-white/70">Academic Year <?= $academic_year ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Additions -->
                <div class="group relative bg-white rounded-xl border-2 border-blue-200 p-4 hover:border-blue-400 hover:shadow-xl transition-all duration-300 cursor-pointer" onclick="openInventoryModal('recent')">
                    <div class="flex items-start justify-between mb-3">
                        <div class="p-2 rounded-lg bg-gradient-to-br from-blue-100 to-blue-50 group-hover:from-blue-200 group-hover:to-blue-100 transition-all duration-300">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5 text-blue-600">
                                <path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"></path>
                                <polyline points="14 2 14 8 20 8"></polyline>
                                <line x1="12" y1="18" x2="12" y2="12"></line>
                                <line x1="9" y1="15" x2="15" y2="15"></line>
                            </svg>
                        </div>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-blue-700 mb-1">Recent Additions</p>
                        <h3 class="text-3xl font-bold text-blue-600 mb-1"><?= number_format($recent_additions) ?></h3>
                        <p class="text-[10px] text-blue-600/70">Added in last 7 days</p>
                    </div>
                </div>

                <!-- Low Stock Alert -->
                <div class="group relative bg-gradient-to-br from-amber-50 to-amber-100/50 rounded-xl border-2 border-amber-300 p-4 hover:border-amber-400 hover:shadow-xl transition-all duration-300 cursor-pointer" onclick="openInventoryModal('low_stock')">
                    <div class="flex items-start justify-between mb-3">
                        <div class="p-2 rounded-lg bg-gradient-to-br from-amber-200 to-amber-100 group-hover:from-amber-300 group-hover:to-amber-200 transition-all duration-300">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5 text-amber-700">
                                <path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"></path>
                                <line x1="12" y1="9" x2="12" y2="13"></line>
                                <line x1="12" y1="17" x2="12.01" y2="17"></line>
                            </svg>
                        </div>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-amber-800 mb-1">Low Stock Alert</p>
                        <h3 class="text-3xl font-bold text-amber-700 mb-1"><?= number_format($low_stock_items) ?></h3>
                        <p class="text-[10px] text-amber-700/70">Items with qty &lt; 0</p>
                    </div>
                </div>

                <!-- Total Value -->
                <div class="group relative bg-white rounded-xl border-2 border-green-200 p-4 hover:border-green-400 hover:shadow-xl transition-all duration-300">
                    <div class="flex items-start justify-between mb-3">
                        <div class="p-2 rounded-lg bg-gradient-to-br from-green-100 to-green-50 group-hover:from-green-200 group-hover:to-green-100 transition-all duration-300">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5 text-green-600">
                                <line x1="12" y1="1" x2="12" y2="23"></line>
                                <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                            </svg>
                        </div>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-green-700 mb-1">Total Value</p>
                        <h3 class="text-3xl font-bold text-green-600 mb-1">₱<?= number_format($total_value, 2) ?></h3>
                        <p class="text-[10px] text-green-600/70">Total inventory worth</p>
                    </div>
                </div>

            </div>

            <!-- Main Content Grid -->
            <div class="grid gap-6 lg:grid-cols-7 animate-in" style="animation-delay: 0.2s">
                
                <!-- Left Column - Charts -->
                <div class="lg:col-span-4 space-y-6">
                    
                    <!-- Top Categories -->
                    <div class="bg-white rounded-lg border border-slate-200 hover:shadow-md transition-shadow">
                        <div class="p-4">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-sm font-semibold text-slate-900">Top Categories</h3>
                                <span class="text-xs text-slate-500"><?= count($categories) ?> types</span>
                            </div>
                            
                            <?php if (!empty($categories)): ?>
                                <div class="space-y-2.5">
                                    <?php 
                                    $max_count = $categories[0]['count'];
                                    $gradient_colors = [
                                        ['color' => 'bg-[#800020]'],
                                        ['color' => 'bg-blue-600'],
                                        ['color' => 'bg-emerald-600'],
                                        ['color' => 'bg-purple-600'],
                                        ['color' => 'bg-orange-600'],
                                        ['color' => 'bg-pink-600'],
                                    ];
                                    foreach ($categories as $index => $category): 
                                        $percentage = ($category['count'] / $max_count) * 100;
                                        $delay = $index * 0.1;
                                        $colors = $gradient_colors[$index % count($gradient_colors)];
                                    ?>
                                        <div>
                                            <div class="flex items-center justify-between text-xs mb-1.5">
                                                <div class="flex items-center gap-2">
                                                    <span class="flex items-center justify-center w-5 h-5 rounded <?= $colors['color'] ?> text-white text-[10px] font-bold"><?= $index + 1 ?></span>
                                                    <span class="font-medium text-slate-700"><?= htmlspecialchars($category['category']) ?></span>
                                                </div>
                                                <span class="text-slate-600 font-semibold"><?= number_format($category['count']) ?></span>
                                            </div>
                                            <div class="h-1.5 bg-slate-100 rounded-full overflow-hidden">
                                                <div class="h-full <?= $colors['color'] ?> rounded-full progress-bar transition-all duration-1000 ease-out" style="width: 0%" data-width="<?= $percentage ?>%"></div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <script>
                                    // Function to animate progress bars - can be called multiple times
                                    function animateProgressBars() {
                                        // Reset all bars to 0 first
                                        document.querySelectorAll('.progress-bar').forEach(function(bar) {
                                            bar.style.width = '0%';
                                        });
                                        
                                        // Animate them
                                        setTimeout(function() {
                                            document.querySelectorAll('.progress-bar').forEach(function(bar, index) {
                                                setTimeout(function() {
                                                    bar.style.width = bar.dataset.width;
                                                }, index * 100);
                                            });
                                        }, 100);
                                    }
                                    
                                    // Trigger on page load
                                    document.addEventListener('DOMContentLoaded', animateProgressBars);
                                    
                                    // Also trigger when page becomes visible (for SPA navigation)
                                    if (typeof window.triggerInventoryAnimations === 'undefined') {
                                        window.triggerInventoryAnimations = animateProgressBars;
                                    }
                                </script>
                            <?php else: ?>
                                <div class="text-center py-8">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mx-auto h-8 w-8 text-slate-300">
                                        <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
                                    </svg>
                                    <p class="mt-2 text-xs text-slate-500">No data</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Top Sports -->
                    <div class="bg-white rounded-lg border border-slate-200 hover:shadow-md transition-shadow">
                        <div class="p-4">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-sm font-semibold text-slate-900">Top Sports</h3>
                                <span class="text-xs text-slate-500"><?= count($sports) ?> sports</span>
                            </div>
                            
                            <?php if (!empty($sports)): ?>
                                <div class="grid grid-cols-2 gap-2">
                                    <?php 
                                    $sport_badges = [
                                        'Basketball' => ['emoji' => '🏀', 'color' => 'bg-orange-500'],
                                        'Volleyball' => ['emoji' => '🏐', 'color' => 'bg-blue-500'],
                                        'Badminton' => ['emoji' => '🏸', 'color' => 'bg-green-500'],
                                        'Table Tennis' => ['emoji' => '🏓', 'color' => 'bg-red-500'],
                                        'Tennis' => ['emoji' => '🎾', 'color' => 'bg-yellow-500'],
                                        'Football' => ['emoji' => '⚽', 'color' => 'bg-emerald-500'],
                                    ];
                                    
                                    foreach ($sports as $sport): 
                                        $sportInfo = $sport_badges[$sport['sport']] ?? ['emoji' => '🏅', 'color' => 'bg-slate-500'];
                                    ?>
                                        <div class="flex items-center justify-between p-2 rounded-lg border border-slate-100 hover:border-slate-300 hover:shadow-sm transition-all">
                                            <div class="flex items-center gap-2">
                                                <span class="text-lg"><?= $sportInfo['emoji'] ?></span>
                                                <span class="text-xs font-medium text-slate-700"><?= htmlspecialchars($sport['sport']) ?></span>
                                            </div>
                                            <span class="text-xs font-bold text-slate-600"><?= number_format($sport['count']) ?></span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div class="text-center py-8">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mx-auto h-8 w-8 text-slate-300">
                                        <circle cx="12" cy="12" r="10"></circle>
                                    </svg>
                                    <p class="mt-2 text-xs text-slate-500">No data</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                </div>

                <!-- Right Column - Recent Activity -->
                <div class="lg:col-span-3">
                    <div class="bg-white rounded-lg border border-slate-200 hover:shadow-md transition-shadow">
                        <div class="p-4">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-sm font-semibold text-slate-900">Recent Activity</h3>
                                <a href="item_management.php" class="text-xs font-medium text-[#800020] hover:text-[#5c0016]">View all</a>
                            </div>
                            
                            <div id="recentActivityList" class="space-y-2 custom-scrollbar max-h-[600px] overflow-y-auto pr-1">
                                <?php if (!empty($recent_items)): ?>
                                    <?php foreach ($recent_items as $item): ?>
                                        <div class="flex items-start gap-2.5 p-2.5 rounded-lg border border-slate-100 hover:border-slate-300 hover:bg-slate-50 transition-all">
                                            <?php if ($item['image']): ?>
                                                <img src="../../frontend/images/items/<?= htmlspecialchars($item['image']) ?>" alt="<?= htmlspecialchars($item['item_name']) ?>" class="w-12 h-12 rounded-lg object-cover border border-slate-200">
                                            <?php else: ?>
                                                <div class="w-12 h-12 rounded-lg bg-slate-100 flex items-center justify-center border border-slate-200">
                                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5 text-slate-400">
                                                        <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                                                        <circle cx="9" cy="9" r="2"></circle>
                                                        <path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"></path>
                                                    </svg>
                                                </div>
                                            <?php endif; ?>
                                            <div class="flex-1 min-w-0">
                                                <div class="flex items-start justify-between gap-2 mb-1">
                                                    <p class="text-xs font-semibold text-slate-900 truncate"><?= htmlspecialchars($item['item_name']) ?></p>
                                                    <span class="flex-shrink-0 text-xs font-bold <?= $item['quantity'] == 0 ? 'text-red-600' : ($item['quantity'] < 5 ? 'text-amber-600' : 'text-slate-600') ?>"><?= number_format($item['quantity']) ?></span>
                                                </div>
                                                <p class="text-[11px] text-slate-500 mb-1.5"><?= htmlspecialchars($item['category']) ?></p>
                                                <span class="inline-block px-1.5 py-0.5 rounded text-[10px] font-medium <?= $item['status'] === 'Available' ? 'bg-emerald-100 text-emerald-700' : ($item['status'] === 'Damaged' ? 'bg-red-100 text-red-700' : 'bg-orange-100 text-orange-700') ?>">
                                                    <?= htmlspecialchars($item['status']) ?>
                                                </span>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="text-center py-8">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mx-auto h-8 w-8 text-slate-300">
                                            <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
                                        </svg>
                                        <p class="mt-2 text-xs text-slate-500">No recent items</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

<script>
    // Real-time Update for Recent Activity
    document.addEventListener('DOMContentLoaded', function() {
        
        function updateRecentActivity() {
            fetch('/OSAS-SIS/backend/items/api.php?mode=recent&limit=5')
                .then(response => response.json())
                .then(result => {
                    if (result.success) {
                        renderRecentItems(result.data);
                    }
                })
                .catch(err => console.error('Error fetching recent items:', err));
        }

        function renderRecentItems(items) {
            const container = document.getElementById('recentActivityList');
            if (!container) return;

            if (items.length === 0) {
                container.innerHTML = `
                    <div class="text-center py-8">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mx-auto h-8 w-8 text-slate-300">
                            <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
                        </svg>
                        <p class="mt-2 text-xs text-slate-500">No recent items</p>
                    </div>`;
                return;
            }

            let html = '';
            items.forEach(item => {
                const imgHtml = item.image 
                    ? `<img src="../../frontend/images/items/${escapeHtml(item.image)}" alt="${escapeHtml(item.item_name)}" class="w-12 h-12 rounded-lg object-cover border border-slate-200">`
                    : `<div class="w-12 h-12 rounded-lg bg-slate-100 flex items-center justify-center border border-slate-200">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5 text-slate-400">
                                <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                                <circle cx="9" cy="9" r="2"></circle>
                                <path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"></path>
                            </svg>
                       </div>`;
                       
                const qtyClass = item.quantity == 0 ? 'text-red-600' : (item.quantity < 5 ? 'text-amber-600' : 'text-slate-600');
                
                let statusClass = 'bg-orange-100 text-orange-700'; // Default
                if (item.status === 'Available') statusClass = 'bg-emerald-100 text-emerald-700';
                else if (item.status === 'Damaged') statusClass = 'bg-red-100 text-red-700';

                html += `
                    <div class="flex items-start gap-2.5 p-2.5 rounded-lg border border-slate-100 hover:border-slate-300 hover:bg-slate-50 transition-all">
                        ${imgHtml}
                        <div class="flex-1 min-w-0">
                            <div class="flex items-start justify-between gap-2 mb-1">
                                <p class="text-xs font-semibold text-slate-900 truncate">${escapeHtml(item.item_name)}</p>
                                <span class="flex-shrink-0 text-xs font-bold ${qtyClass}">${parseInt(item.quantity).toLocaleString()}</span>
                            </div>
                            <p class="text-[11px] text-slate-500 mb-1.5">${escapeHtml(item.category)}</p>
                            <span class="inline-block px-1.5 py-0.5 rounded text-[10px] font-medium ${statusClass}">
                                ${escapeHtml(item.status)}
                            </span>
                        </div>
                    </div>
                `;
            });
            
            container.innerHTML = html;
        }

        function escapeHtml(text) {
            if (!text) return '';
            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return text.toString().replace(/[&<>"']/g, function(m) { return map[m]; });
        }

        // Poll every 5 seconds
        setInterval(updateRecentActivity, 5000);
        
        // Initial fetch after a short delay
        setTimeout(updateRecentActivity, 2000);
    });
</script>

            <!-- Quick Actions removed as per user request -->

        </div>
    </div>



    <!-- Filter Modal -->
    <div id="filterModal" class="fixed inset-0 z-50 hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <!-- Backdrop -->
        <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm transition-opacity opacity-0" id="filterModalBackdrop"></div>

        <div class="fixed inset-0 z-10 w-screen overflow-y-auto">
            <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                <!-- Modal Panel -->
                <div class="relative transform overflow-hidden rounded-xl bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-xl opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" id="filterModalPanel">
                    
                    <!-- Header -->
                    <div class="bg-gradient-to-r from-[#800020] to-[#5c0016] px-4 py-4 sm:px-6 flex justify-between items-center">
                        <h3 class="text-base font-semibold leading-6 text-white flex items-center gap-2" id="modal-title">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5">
                                <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"></polygon>
                            </svg>
                            Filter Added Items
                        </h3>
                        <button type="button" onclick="closeFilterModal()" class="rounded-md bg-white/10 p-1 text-white hover:bg-white/20 focus:outline-none transition-colors">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <!-- Filter Controls -->
                    <div class="px-4 py-5 sm:p-6 pb-2">
                        <div class="flex gap-4 mb-6">
                            <div class="w-1/3">
                                <label for="filterType" class="block text-sm font-medium leading-6 text-slate-900">Filter By</label>
                                <select id="filterType" onchange="toggleFilterInputs()" class="mt-1 block w-full rounded-md border-0 py-1.5 pl-3 pr-10 text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-[#800020] sm:text-sm sm:leading-6 cursor-pointer">
                                    <option value="semester">Semester</option>
                                    <option value="date">Specific Date</option>
                                </select>
                            </div>
                            
                            <div class="w-2/3" id="semesterInput">
                                <label for="semesterValue" class="block text-sm font-medium leading-6 text-slate-900">Select Semester</label>
                                <select id="semesterValue" class="mt-1 block w-full rounded-md border-0 py-1.5 pl-3 pr-10 text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-[#800020] sm:text-sm sm:leading-6 cursor-pointer">
                                    <option value="1st Semester">1st Semester</option>
                                    <option value="2nd Semester" selected>2nd Semester</option>
                                    <option value="Summer">Summer</option>
                                </select>
                            </div>

                            <div class="w-2/3 hidden" id="dateInput">
                                <label for="dateValue" class="block text-sm font-medium leading-6 text-slate-900">Select Date</label>
                                <input type="date" id="dateValue" class="mt-1 block w-full rounded-md border-0 py-1.5 text-slate-900 ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 focus:ring-2 focus:ring-[#800020] sm:text-sm sm:leading-6">
                            </div>
                        </div>

                        <button onclick="applyFilter()" class="w-full inline-flex justify-center items-center gap-2 rounded-md bg-[#800020] px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-[#5c0016] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#800020] transition-colors cursor-pointer">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4">
                                <circle cx="11" cy="11" r="8"></circle>
                                <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                            </svg>
                            Search Items
                        </button>
                    </div>

                    <!-- Results Area -->
                    <div class="border-t border-slate-200 bg-slate-50/50 px-4 py-4 sm:px-6 min-h-[200px] max-h-[400px] overflow-y-auto custom-scrollbar">
                        <div id="filterResults" class="space-y-3">
                            <div class="text-center text-slate-500 py-8">
                                <p class="text-sm">Select a filter options to see result.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script>
        // Embed inventory data for modal filtering
        window.inventoryItemsData = <?= json_encode($all_items, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?> || [];
        window.currentModalFilterType = 'all'; // Track current filter type
        window.currentSearchType = ''; // Track search (semester or date)
        window.currentSearchValue = ''; // Track search value

        function printModalItems() {
            const items = filterInventoryData(window.currentModalFilterType);
            const dateStr = new Date().toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
            let titleSuffix = '';
            
            if (window.currentModalFilterType === 'available') titleSuffix = ' - Available Items';
            else if (window.currentModalFilterType === 'unavailable') titleSuffix = ' - Unavailable Items';
            else if (window.currentModalFilterType === 'damaged') titleSuffix = ' - Damaged Items';
            else if (window.currentModalFilterType === 'recent') titleSuffix = ' - Recent Additions';
            else if (window.currentModalFilterType === 'low_stock') titleSuffix = ' - Low Stock Items';
            else if (window.currentModalFilterType === 'search') {
                if (window.currentSearchType === 'semester') titleSuffix = ' - ' + window.currentSearchValue;
                else if (window.currentSearchType === 'date') titleSuffix = ' - ' + window.currentSearchValue;
                else titleSuffix = ' - Filtered Items';
            }
            else titleSuffix = ' - All Items';

            // Create printable content
            let rowsHtml = '';
            if (items.length === 0) {
                rowsHtml = '<tr><td colspan="7" style="text-align:center; padding: 20px;">No items found.</td></tr>';
            } else {
                items.forEach((item, index) => {
                    // Normalize fields
                    const sport = item.sport || '-';
                    const location = item.location || '-';
                    const brand = item.brand || '-';
                    
                    rowsHtml += `
                        <tr>
                            <td>${index + 1}</td>
                            <td>
                                <div style="font-weight: bold;">${item.item_name || ''}</div>
                                <div style="font-size: 11px; color: #666;">ID: ${item.unique_id || '-'}</div>
                            </td>
                            <td>${item.category || '-'}</td>
                            <td>${sport}</td>
                            <td>${brand}</td>
                            <td>${location}</td>
                            <td style="text-align: center;">${item.quantity || 0}</td>
                            <td style="text-align: center;">${item.status || '-'}</td>
                        </tr>
                    `;
                });
            }

            const printWindow = window.open('', '_blank');
            printWindow.document.write(`
                <!DOCTYPE html>
                <html>
                <head>
                    <title>Inventory Report${titleSuffix}</title>
                    <style>
                        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
                        @import url('https://fonts.googleapis.com/css2?family=UnifrakturMaguntia&display=swap');

                        @page {
                            size: A4 landscape;
                            margin: 1cm;
                        }

                        html, body {
                            padding: 0;
                            margin: 0;
                            height: 100%;
                        }

                        body {
                            font-family: 'Inter', system-ui, -apple-system, sans-serif;
                            color: #1f2937;
                            line-height: 1.4;
                            background: #fff;
                            -webkit-print-color-adjust: exact;
                            print-color-adjust: exact;
                            font-size: 10px;
                        }

                        .print-wrapper {
                            width: 100%;
                            margin: 0 auto;
                            position: relative;
                            display: flex;
                            flex-direction: column;
                            align-items: center;
                        }

                        .old-english {
                            font-family: 'UnifrakturMaguntia', "Old English Text MT", "Engravers Old English BT", "Goudy Text MT", serif;
                            font-weight: 400; 
                        }

                        /* Header */
                        .print-header {
                            text-align: center;
                            margin-bottom: 20px;
                        }

                        .header-logo {
                            width: 80px;
                            height: auto;
                            margin-bottom: 5px;
                        }

                        .header-text h1 {
                            font-family: 'UnifrakturMaguntia', "Old English Text MT", "Engravers Old English BT", "Goudy Text MT", serif;
                            font-size: 28px;
                            color: #800020;
                            margin: 0;
                            letter-spacing: 1px;
                            font-weight: 400;
                        }

                        .header-text p {
                            font-size: 12px;
                            font-weight: 500;
                            text-transform: uppercase;
                            color: #374151;
                            margin: 5px 0 0;
                        }

                        .report-meta {
                            text-align: center;
                            margin-bottom: 30px;
                            border-bottom: 2px solid #800020;
                            padding-bottom: 10px;
                            width: 100%;
                        }

                        .report-title {
                            font-size: 14px;
                            font-weight: 700;
                            color: #111827;
                            text-transform: uppercase;
                            letter-spacing: 0.05em;
                            margin-bottom: 2px;
                        }

                        .report-date {
                            font-size: 10px;
                            color: #6b7280;
                        }

                        /* Table Design */
                        table {
                            width: 100%;
                            border-collapse: collapse;
                            border: 1px solid #e5e7eb;
                            border-radius: 8px;
                            overflow: hidden;
                        }

                        thead {
                            display: table-header-group;
                        }

                        thead tr {
                            background: linear-gradient(to right, #800020, #5c0016) !important;
                            color: #ffffff !important;
                        }

                        th {
                            padding: 8px 10px;
                            text-align: left;
                            font-size: 9px;
                            font-weight: 600;
                            text-transform: uppercase;
                            letter-spacing: 0.05em;
                            color: #ffffff !important;
                            border-bottom: 1px solid #800020;
                        }

                        tbody tr {
                            border-bottom: 1px solid #f3f4f6;
                        }

                        tbody tr:nth-child(even) {
                            background-color: #f9fafb;
                        }

                        td {
                            padding: 6px 10px;
                            vertical-align: middle;
                            font-size: 10px;
                            color: #374151;
                        }

                        /* Footer */
                        .footer {
                            position: fixed;
                            bottom: 0;
                            left: 0;
                            right: 0;
                            text-align: center;
                            font-size: 8px;
                            color: #9ca3af;
                            padding-top: 1rem;
                            background: #fff;
                            border-top: 1px solid #f3f4f6;
                            width: 100%;
                        }
                    </style>
                </head>
                <body>
                    <div class="print-wrapper">
                        <div class="print-header">
                            <img src="/OSAS-SIS/frontend/images/spc.png" alt="SPC Logo" class="header-logo">
                            <div class="header-text">
                                <h1>St. Peter's College</h1>
                                <p>Office of Student Affairs and Services • SIS</p>
                            </div>
                        </div>
                        
                        <div class="report-meta">
                            <div class="report-title">Inventory Report${titleSuffix}</div>
                            <div class="report-date">Generated on ${dateStr} • Total Items: ${items.length}</div>
                        </div>
                        
                        <div style="width: 100%;">
                            <table>
                                <thead>
                                    <tr>
                                        <th style="width: 40px;">No.</th>
                                        <th>Item Name</th>
                                        <th>Category</th>
                                        <th>Sport</th>
                                        <th>Brand</th>
                                        <th>Location</th>
                                        <th style="text-align: center; width: 60px;">Qty</th>
                                        <th style="text-align: center; width: 80px;">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${rowsHtml}
                                </tbody>
                            </table>
                        </div>

                        <div class="footer">
                            System Generated Report &bull; OSAS-SIS &bull; Inventory Management System
                        </div>
                    </div>
                    
                    <script>
                        window.onload = function() { 
                            setTimeout(() => {
                                window.print();
                            }, 500);
                        }
                    <\/script>
                </body>
                </html>
            `);
            printWindow.document.close();
        }

        function filterInventoryData(type) {
            const now = new Date();
            return window.inventoryItemsData.filter(item => {
                const status = (item.status || '').trim().toLowerCase();
                const qty = parseInt(item.quantity || 0, 10);
                const createdAt = item.created_at ? new Date(item.created_at) : null;

                if (type === 'available') {
                    return status === 'available';
                } else if (type === 'unavailable') {
                    return status === 'unavailable';
                } else if (type === 'damaged') {
                    return status === 'damaged';
                } else if (type === 'recent') {
                    if (createdAt instanceof Date && !isNaN(createdAt)) {
                        const diffMs = now - createdAt;
                        const diffDays = diffMs / (1000 * 60 * 60 * 24);
                        return diffDays <= 7;
                    }
                    return false;
                } else if (type === 'low_stock') {
                    return qty > 0 && qty < 5;
                } else if (type === 'search') {
                    // Filter based on currently stored search params
                    if (window.currentSearchType === 'semester') {
                        // Filter by semester
                        return (item.semester || '') === window.currentSearchValue;
                    } else if (window.currentSearchType === 'date') {
                        // Filter by specific date (YYYY-MM-DD compare)
                        if (!item.created_at) return false;
                        // item.created_at usually "YYYY-MM-DD HH:MM:SS"
                        const itemDate = item.created_at.split(' ')[0]; 
                        return itemDate === window.currentSearchValue;
                    }
                }
                // 'all' fallback
                return true;
            });
        }

        function openInventoryModal(type) {
            const modal = document.getElementById('inventoryModal');
            const titleEl = document.getElementById('inventoryModalTitle');
            const bodyEl = document.getElementById('inventoryModalBody');
            if (!modal || !titleEl || !bodyEl) return;

            let titleSuffix = '';
            if (type === 'available') titleSuffix = ' - Available';
            else if (type === 'unavailable') titleSuffix = ' - Unavailable';
            else if (type === 'damaged') titleSuffix = ' - Damaged';
            else if (type === 'recent') titleSuffix = ' - Recent Additions';
            else if (type === 'low_stock') titleSuffix = ' - Low Stock';
            else if (type === 'search') {
                if (window.currentSearchType === 'semester') titleSuffix = ' - ' + window.currentSearchValue;
                else if (window.currentSearchType === 'date') titleSuffix = ' - ' + window.currentSearchValue;
                else titleSuffix = ' - Search Results';
            }

            titleEl.textContent = 'Inventory Items' + titleSuffix;
            window.currentModalFilterType = type; // Store for export

            const items = filterInventoryData(type);

            if (!items.length) {
                bodyEl.innerHTML = `
                    <tr>
                        <td colspan="4" class="px-4 py-6 text-center text-sm text-slate-500">
                            No items found for this view.
                        </td>
                    </tr>
                `;
            } else {
                let rowsHtml = '';
                items.forEach(item => {
                    const status = (item.status || '').trim();
                    let badgeClass = 'bg-orange-50 text-orange-700 ring-1 ring-inset ring-orange-600/20';
                    if (status === 'Available') {
                        badgeClass = 'bg-emerald-50 text-emerald-700 ring-1 ring-inset ring-emerald-600/20';
                    } else if (status === 'Damaged') {
                        badgeClass = 'bg-red-50 text-red-700 ring-1 ring-inset ring-red-600/20';
                    }

                    rowsHtml += `
                        <tr>
                            <td class="px-4 py-2 whitespace-nowrap text-sm font-medium text-slate-900">${item.item_name || ''}</td>
                            <td class="px-4 py-2 whitespace-nowrap text-xs text-slate-600">${item.category || 'N/A'}</td>
                            <td class="px-4 py-2 text-center text-sm font-semibold text-slate-900">${item.quantity || 0}</td>
                            <td class="px-4 py-2 text-center">
                                <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium ${badgeClass}">
                                    ${status || 'N/A'}
                                </span>
                            </td>
                        </tr>
                    `;
                });
                bodyEl.innerHTML = rowsHtml;
            }

            modal.classList.remove('hidden');
            modal.classList.add('flex');
            document.body.style.overflow = 'hidden';
        }

        function closeInventoryModal() {
            const modal = document.getElementById('inventoryModal');
            if (!modal) return;
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            document.body.style.overflow = '';
        }

        // Modal State Management
        function openFilterModal() {
            const modal = document.getElementById('filterModal');
            const backdrop = document.getElementById('filterModalBackdrop');
            const panel = document.getElementById('filterModalPanel');
            
            modal.classList.remove('hidden');
            // Trigger animation
            setTimeout(() => {
                backdrop.classList.remove('opacity-0');
                panel.classList.remove('opacity-0', 'translate-y-4', 'sm:translate-y-0', 'sm:scale-95');
                panel.classList.add('translate-y-0', 'sm:scale-100');
            }, 10);
        }

        function closeFilterModal() {
            const modal = document.getElementById('filterModal');
            const backdrop = document.getElementById('filterModalBackdrop');
            const panel = document.getElementById('filterModalPanel');
            
            backdrop.classList.add('opacity-0');
            panel.classList.add('opacity-0', 'translate-y-4', 'sm:translate-y-0', 'sm:scale-95');
            panel.classList.remove('translate-y-0', 'sm:scale-100');
            
            setTimeout(() => {
                modal.classList.add('hidden');
            }, 300);
        }

        // Toggle Inputs
        function toggleFilterInputs() {
            const type = document.getElementById('filterType').value;
            const semesterInput = document.getElementById('semesterInput');
            const dateInput = document.getElementById('dateInput');
            
            if (type === 'semester') {
                semesterInput.classList.remove('hidden');
                dateInput.classList.add('hidden');
            } else {
                semesterInput.classList.add('hidden');
                dateInput.classList.remove('hidden');
            }
        }

        // Fetch Data
        async function applyFilter() {
            const resultsContainer = document.getElementById('filterResults');
            const filterType = document.getElementById('filterType').value;
            let value = '';
            
            if (filterType === 'semester') {
                value = document.getElementById('semesterValue').value;
            } else {
                value = document.getElementById('dateValue').value;
            }
            
            if (!value) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Missing Input',
                    text: 'Please select a value to search.',
                    confirmButtonColor: '#800020'
                });
                return;
            }

            // Set global search state
            window.currentSearchType = filterType;
            window.currentSearchValue = value;
            
            // Close filter modal and open inventory result modal to see full results and export
            // We reuse the main modal since it already has the export functionality
            closeFilterModal();
            openInventoryModal('search');
        }
    </script>

    <script>
    // Initialize inventory page - can be called multiple times when content is dynamically loaded
    window.initInventoryPage = function() {
        console.log("Inventory page initialized");
        
        // All onclick handlers are already defined as inline attributes in the HTML,
        // so they'll work automatically. We just need to ensure our animation functions
        // are available on the window object.
        
        // Make sure the animation function is accessible
        if (typeof animateProgressBars === 'function') {
            animateProgressBars();
        }
    };
    
    // Initialize on page load
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', window.initInventoryPage);
    } else {
        window.initInventoryPage();
    }
    </script>

</body>
</html>
