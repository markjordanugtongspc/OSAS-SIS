<?php
session_start();
require_once '../../backend/vite_helper.php';
require_once '../../config/db.php';

// Security check: Redirect to login if not authenticated
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../index.php");
    exit;
}

$firstname = htmlspecialchars($_SESSION['firstname']);
$lastname = htmlspecialchars($_SESSION['lastname']);
$position = htmlspecialchars($_SESSION['position']);

try {
    $stmt = $pdo->query("SELECT SUM(quantity) as total FROM items");
    $total_items = $stmt->fetch()['total'] ?? 0;

    $stmt = $pdo->query("SELECT SUM(quantity) as total FROM items WHERE status = 'Available'");
    $available_items = $stmt->fetch()['total'] ?? 0;

    $stmt = $pdo->query("SELECT SUM(quantity) as total FROM items WHERE status = 'Unavailable'");
    $unavailable_items = $stmt->fetch()['total'] ?? 0;

    $stmt = $pdo->query("SELECT SUM(quantity) as total FROM items WHERE status = 'Damaged'");
    $damaged_items = $stmt->fetch()['total'] ?? 0;

    $stmt = $pdo->query("SELECT COUNT(*) as total FROM borrow_lists WHERE deleted_at IS NULL");
    $total_borrows = $stmt->fetch()['total'] ?? 0;

    $stmt = $pdo->query("SELECT COUNT(*) as total FROM borrow_lists WHERE borrow_status IN ('Pending', 'Approved') AND deleted_at IS NULL");
    $active_borrows = $stmt->fetch()['total'] ?? 0;

    $stmt = $pdo->query("SELECT COUNT(*) as total FROM borrow_lists WHERE borrow_status = 'Returned' AND deleted_at IS NULL");
    $completed_borrows = $stmt->fetch()['total'] ?? 0;

    $stmt = $pdo->query("SELECT COUNT(*) as total FROM borrow_lists WHERE due_date < CURDATE() AND borrow_status != 'Returned' AND deleted_at IS NULL");
    $overdue_borrows = $stmt->fetch()['total'] ?? 0;

    $stmt = $pdo->query("SELECT COUNT(*) as total FROM borrow_lists WHERE borrow_status = 'Pending' AND deleted_at IS NULL");
    $pending_borrows = $stmt->fetch()['total'] ?? 0;

    $stmt = $pdo->query("SELECT COUNT(*) as total FROM borrow_lists WHERE borrow_status = 'Approved' AND deleted_at IS NULL");
    $approved_borrows = $stmt->fetch()['total'] ?? 0;

    $stmt = $pdo->query("SELECT COUNT(*) as total FROM borrow_lists WHERE borrow_status = 'Rejected' AND deleted_at IS NULL");
    $rejected_borrows = $stmt->fetch()['total'] ?? 0;

    // Total deposit money (only for Approved borrows)
    $stmt = $pdo->query("SELECT SUM(deposit_money)  as total FROM borrow_lists WHERE borrow_status = 'Approved' AND deleted_at IS NULL");
    $total_deposits = $stmt->fetch()['total'] ?? 0;

    $stmt = $pdo->query("SELECT category, SUM(quantity) as count FROM items GROUP BY category ORDER BY count DESC LIMIT 6");
    $category_rows = $stmt->fetchAll();

    $stmt = $pdo->query("SELECT bl.*, i.item_name, i.category FROM borrow_lists bl LEFT JOIN items i ON bl.item_id = i.id WHERE bl.deleted_at IS NULL ORDER BY bl.id DESC LIMIT 8");
    $recent_transactions = $stmt->fetchAll();
} catch (PDOException $e) {
    // Fallback values in case of error
    $total_items = 0; $available_items = 0; $unavailable_items = 0; $damaged_items = 0;
    $total_borrows = 0; $active_borrows = 0; $completed_borrows = 0; $overdue_borrows = 0;
    $pending_borrows = 0; $approved_borrows = 0; $rejected_borrows = 0;
    $category_rows = []; $recent_transactions = [];
}

// Prepare data for JS
$borrow_status_labels = ['Pending', 'Approved', 'Returned', 'Overdue', 'Rejected'];
$borrow_status_values = [
    (int) $pending_borrows,
    (int) $approved_borrows,
    (int) $completed_borrows,
    (int) $overdue_borrows,
    (int) $rejected_borrows
];

$inventory_status_labels = ['Available', 'Unavailable', 'Damaged'];
$inventory_status_values = [
    (int) $available_items,
    (int) $unavailable_items,
    (int) $damaged_items,
];

$category_labels = [];
$category_values = [];
foreach ($category_rows as $row) {
    $category_labels[] = $row['category'] !== null && $row['category'] !== '' ? $row['category'] : 'Other';
    $category_values[] = (int) $row['count'];
}
?>
<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | OSAS SIS</title>
    <?= vite(['backend/js/main.js', 'frontend/css/styles.css']) ?>
    <!-- ApexCharts -->
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <script src="https://code.iconify.design/3/3.1.0/iconify.min.js"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap');
        
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .animate-in { animation: fadeIn 0.5s cubic-bezier(0.16, 1, 0.3, 1) forwards; }
        
        .glass-card {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.5);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
        }

        /* Impactful Hover Effects */
        .hover-card {
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }
        .hover-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 25px -5px rgb(0 0 0 / 0.1), 0 8px 10px -6px rgb(0 0 0 / 0.1);
        }
        .hover-card:hover .icon-container {
            transform: scale(1.1) rotate(5deg);
        }

        /* Status Badge Hover Reveal */
        .status-badge {
            opacity: 0;
            transform: translateX(10px);
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }
        .hover-card:hover .status-badge {
            opacity: 1;
            transform: translateX(0);
        }

        .stat-card-gradient-1 { background: linear-gradient(135deg, #e0f2fe 0%, #ffffff 100%); }
        .stat-card-gradient-2 { background: linear-gradient(135deg, #f0fdf4 0%, #ffffff 100%); }
        .stat-card-gradient-3 { background: linear-gradient(135deg, #fef2f2 0%, #ffffff 100%); }
        .stat-card-gradient-4 { background: linear-gradient(135deg, #fdf4ff 0%, #ffffff 100%); }
    </style>
</head>
<body class="h-full">
    
    <!-- Include Sidebar -->
    <?php include 'navbar.php'; ?>

    <!-- Main Content -->
    <div class="ml-64 min-h-screen bg-slate-50/50">
        
        <!-- Header -->
        <div class="sticky top-0 z-30 bg-white/80 backdrop-blur-md border-b border-slate-200/60">
            <div class="px-8 py-5 flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Dashboard Overview</h1>
                    <p class="mt-1 text-sm font-medium text-slate-500">Welcome back, <?= $firstname ?>!</p>
                </div>
                <!-- Date Widget -->
                <div class="hidden md:flex items-center gap-2 px-4 py-2 bg-white rounded-full border border-slate-200 shadow-sm">
                    <div class="w-8 h-8 rounded-full bg-slate-100 flex items-center justify-center text-slate-600">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-slate-900"><?= date('l, F j') ?></p>
                        <p class="text-[10px] text-slate-500 font-medium"><?= date('Y') ?></p>
                    </div>
                </div>
            </div>
        </div>

        <div class="px-8 py-8 space-y-8 max-w-[1600px] mx-auto">
            
            <!-- Bento Grid Stats (6 Cards Row) -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-5 animate-in">
                <!-- Total Borrows -->
                <div class="hover-card relative overflow-hidden rounded-2xl border border-slate-100 bg-white p-5 shadow-sm">
                    <div class="absolute top-0 right-0 -mt-4 -mr-4 w-20 h-20 rounded-full bg-slate-50 blur-2xl opacity-60 transition-opacity"></div>
                    <div class="relative z-10">
                        <div class="flex items-center justify-between mb-3">
                            <div class="icon-container p-2 bg-slate-100 rounded-xl text-slate-600 transition-transform duration-300">
                                <span class="iconify w-6 h-6" data-icon="solar:clipboard-list-bold"></span>
                            </div>
                            <span class="status-badge text-[10px] font-bold text-slate-500 bg-slate-50 px-2 py-0.5 rounded-md uppercase">ALL</span>
                        </div>
                        <h3 class="text-2xl font-extrabold text-slate-900 tracking-tight"><?= number_format($total_borrows) ?></h3>
                        <p class="text-[10px] font-semibold text-slate-400 mt-1 uppercase tracking-wider">Total Borrows</p>
                    </div>
                </div>

                <!-- Approved -->
                <div class="hover-card relative overflow-hidden rounded-2xl border border-emerald-100 bg-emerald-50/30 p-5 shadow-sm">
                    <div class="absolute top-0 right-0 -mt-4 -mr-4 w-20 h-20 rounded-full bg-emerald-100 blur-2xl opacity-60 transition-opacity"></div>
                    <div class="relative z-10">
                        <div class="flex items-center justify-between mb-3">
                            <div class="icon-container p-2 bg-white shadow-sm rounded-xl text-emerald-600 transition-transform duration-300">
                                <span class="iconify w-6 h-6" data-icon="solar:check-circle-bold"></span>
                            </div>
                            <span class="status-badge text-[10px] font-bold text-emerald-600 bg-emerald-100/50 px-2 py-0.5 rounded-md uppercase">LIVE</span>
                        </div>
                        <h3 class="text-2xl font-extrabold text-slate-900 tracking-tight"><?= number_format($approved_borrows) ?></h3>
                        <p class="text-[10px] font-semibold text-slate-400 mt-1 uppercase tracking-wider">Approved</p>
                    </div>
                </div>

                <!-- Rejected -->
                <div class="hover-card relative overflow-hidden rounded-2xl border border-orange-100 bg-orange-50/30 p-5 shadow-sm">
                    <div class="absolute top-0 right-0 -mt-4 -mr-4 w-20 h-20 rounded-full bg-orange-100 blur-2xl opacity-60 transition-opacity"></div>
                    <div class="relative z-10">
                        <div class="flex items-center justify-between mb-3">
                            <div class="icon-container p-2 bg-white shadow-sm rounded-xl text-orange-600 transition-transform duration-300">
                                <span class="iconify w-6 h-6" data-icon="solar:close-circle-bold"></span>
                            </div>
                            <span class="status-badge text-[10px] font-bold text-orange-600 bg-orange-100/50 px-2 py-0.5 rounded-md uppercase">DENIED</span>
                        </div>
                        <h3 class="text-2xl font-extrabold text-slate-900 tracking-tight"><?= number_format($rejected_borrows) ?></h3>
                        <p class="text-[10px] font-semibold text-slate-400 mt-1 uppercase tracking-wider">Rejected</p>
                    </div>
                </div>

                <!-- Returned -->
                <div class="hover-card relative overflow-hidden rounded-2xl border border-blue-100 bg-blue-50/30 p-5 shadow-sm">
                    <div class="absolute top-0 right-0 -mt-4 -mr-4 w-20 h-20 rounded-full bg-blue-100 blur-2xl opacity-60 transition-opacity"></div>
                    <div class="relative z-10">
                        <div class="flex items-center justify-between mb-3">
                            <div class="icon-container p-2 bg-white shadow-sm rounded-xl text-blue-600 transition-transform duration-300">
                                <span class="iconify w-6 h-6" data-icon="solar:reply-bold"></span>
                            </div>
                            <span class="status-badge text-[10px] font-bold text-blue-600 bg-blue-100/50 px-2 py-0.5 rounded-md uppercase">DONE</span>
                        </div>
                        <h3 class="text-2xl font-extrabold text-slate-900 tracking-tight"><?= number_format($completed_borrows) ?></h3>
                        <p class="text-[10px] font-semibold text-slate-400 mt-1 uppercase tracking-wider">Returned</p>
                    </div>
                </div>

                <!-- Overdue -->
                <div class="hover-card relative overflow-hidden rounded-2xl border border-red-100 bg-red-50/30 p-5 shadow-sm">
                    <div class="absolute top-0 right-0 -mt-4 -mr-4 w-20 h-20 rounded-full bg-red-100 blur-2xl opacity-60 transition-opacity"></div>
                    <div class="relative z-10">
                        <div class="flex items-center justify-between mb-3">
                            <div class="icon-container p-2 bg-white shadow-sm rounded-xl text-red-600 transition-transform duration-300">
                                <span class="iconify w-6 h-6" data-icon="solar:danger-bold"></span>
                            </div>
                            <span class="status-badge text-[10px] font-bold text-red-600 bg-red-100/50 px-2 py-0.5 rounded-md uppercase">ALERT</span>
                        </div>
                        <h3 class="text-2xl font-extrabold text-slate-900 tracking-tight"><?= number_format($overdue_borrows) ?></h3>
                        <p class="text-[10px] font-semibold text-slate-400 mt-1 uppercase tracking-wider">Overdue</p>
                    </div>
                </div>

                <!-- Deposits -->
                <div class="hover-card relative overflow-hidden rounded-2xl border border-purple-100 bg-purple-50/30 p-5 shadow-sm">
                    <div class="absolute top-0 right-0 -mt-4 -mr-4 w-20 h-20 rounded-full bg-purple-100 blur-2xl opacity-60 transition-opacity"></div>
                    <div class="relative z-10">
                        <div class="flex items-center justify-between mb-3">
                            <div class="icon-container p-2 bg-white shadow-sm rounded-xl text-purple-600 transition-transform duration-300">
                                <span class="iconify w-6 h-6" data-icon="solar:wad-of-money-bold"></span>
                            </div>
                            <span class="status-badge text-[10px] font-bold text-purple-600 bg-purple-100/50 px-2 py-0.5 rounded-md uppercase">HELD</span>
                        </div>
                        <h3 class="text-2xl font-extrabold text-slate-900 tracking-tight">₱<?= number_format($total_deposits, 2) ?></h3>
                        <p class="text-[10px] font-semibold text-slate-400 mt-1 uppercase tracking-wider">Total Deposits</p>
                    </div>
                </div>
            </div>

            <!-- Charts Section -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 animate-in" style="animation-delay: 0.1s">
                
                <!-- Main Chart: Category Breakdown (Bar) -->
                <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-200 shadow-sm p-6 hover:shadow-md transition-shadow">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h3 class="text-lg font-bold text-slate-900">Inventory by Category</h3>
                            <p class="text-sm text-slate-500">Distribution of items across different categories</p>
                        </div>
                    </div>
                    <div id="categoryChart" class="w-full h-[320px]"></div>
                </div>

                <!-- Secondary Chart: Borrow Status (Donut) -->
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 hover:shadow-md transition-shadow">
                    <div class="mb-6">
                        <h3 class="text-lg font-bold text-slate-900">Borrow Activities</h3>
                        <p class="text-sm text-slate-500">Current status of all transactions</p>
                    </div>
                    <div id="statusChart" class="w-full h-[320px] flex items-center justify-center"></div>
                </div>

            </div>

             <!-- Bottom Section: Inventory Status & Recent Transactions -->
             <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 animate-in" style="animation-delay: 0.2s">
                
                <!-- Inventory Status (Radial/Pie) -->
                 <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 hover:shadow-md transition-shadow">
                    <div class="mb-6">
                        <h3 class="text-lg font-bold text-slate-900">Item Availability</h3>
                        <p class="text-sm text-slate-500">Real-time stock status</p>
                    </div>
                     <div id="inventoryRadialChart" class="w-full h-[320px]"></div>
                 </div>

                <!-- Recent Transactions Table -->
                <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden hover:shadow-md transition-shadow">
                    <div class="px-6 py-5 border-b border-slate-100 flex items-center justify-between bg-white">
                        <div>
                            <h3 class="text-lg font-bold text-slate-900">Recent Transactions</h3>
                            <p class="text-sm text-slate-500">Latest borrowing requests and returns</p>
                        </div>
                         <a href="history.php" class="text-sm font-medium text-slate-600 hover:text-[#800020] transition-colors">See All History &rarr;</a>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="bg-slate-50 border-b border-slate-100">
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">User</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Item</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-right text-xs font-semibold text-slate-500 uppercase tracking-wider">Date</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-50">
                                <?php foreach ($recent_transactions as $row): ?>
                                    <?php
                                        // Initials logic
                                        $initials = '';
                                        if (!empty($row['borrower_name'])) {
                                            $parts = preg_split('/\s+/', trim($row['borrower_name']));
                                            if (count($parts) === 1) {
                                                $initials = strtoupper(mb_substr($parts[0], 0, 2));
                                            } else {
                                                $first = mb_substr($parts[0], 0, 1);
                                                $last = mb_substr(end($parts), 0, 1);
                                                $initials = strtoupper($first . $last);
                                            }
                                        }

                                        // Status Colors
                                        $statusColor = 'bg-gray-100 text-gray-700';
                                        if ($row['borrow_status'] === 'Pending') $statusColor = 'bg-yellow-50 text-yellow-700 ring-1 ring-yellow-600/10';
                                        elseif ($row['borrow_status'] === 'Approved') $statusColor = 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-600/10';
                                        elseif ($row['borrow_status'] === 'Returned') $statusColor = 'bg-blue-50 text-blue-700 ring-1 ring-blue-600/10';
                                        elseif ($row['borrow_status'] === 'Overdue' || $row['borrow_status'] === 'Rejected') $statusColor = 'bg-red-50 text-red-700 ring-1 ring-red-600/10';
                                        
                                        $displayDate = $row['created_at'] ?? $row['borrow_date'] ?? $row['due_date'] ?? null;
                                        if (!$displayDate) $displayDateStr = 'N/A';
                                        else $displayDateStr = date('M d, Y', strtotime($displayDate));
                                    ?>
                                    <tr class="hover:bg-slate-50/80 transition-colors cursor-pointer group">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <div class="h-8 w-8 rounded-full bg-gradient-to-br from-[#800020] to-[#5c0016] flex items-center justify-center text-[10px] font-bold text-white shadow-sm ring-2 ring-white">
                                                    <?= $initials ?>
                                                </div>
                                                <div class="ml-3">
                                                    <div class="text-sm font-semibold text-slate-900"><?= htmlspecialchars($row['borrower_name']) ?></div>
                                                    <div class="text-[11px] text-slate-500"><?= htmlspecialchars($row['borrower_course'] ?? 'Student') ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-slate-700"><?= htmlspecialchars($row['item_name'] ?? 'Item') ?></div>
                                            <div class="text-[11px] text-slate-500"><?= htmlspecialchars($row['category'] ?? 'General') ?></div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $statusColor ?>">
                                                <?= htmlspecialchars($row['borrow_status']) ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-xs font-medium text-slate-500">
                                            <?= $displayDateStr ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (empty($recent_transactions)): ?>
                                    <tr>
                                        <td colspan="4" class="px-6 py-8 text-center text-sm text-slate-400">
                                            No recent transactions found.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- Data Injection for JS -->
    <script>
        window.categoryData = {
            labels: <?= json_encode($category_labels) ?>,
            series: [{ name: 'Items', data: <?= json_encode($category_values) ?> }]
        };
        
        window.statusData = {
            labels: <?= json_encode($borrow_status_labels) ?>,
            series: <?= json_encode($borrow_status_values) ?>
        };

        window.inventoryData = {
            labels: <?= json_encode($inventory_status_labels) ?>,
            series: <?= json_encode($inventory_status_values) ?>
        };
    </script>
    
    <!-- Dashboard Charts Logic -->
    <script>
        window.initDashboard = function() {
            const categoryData = window.categoryData;
            const statusData = window.statusData;
            const inventoryData = window.inventoryData;

            // Check if containers exist (fix for SPA context)
            const catChartEl = document.querySelector("#categoryChart");
            const statusChartEl = document.querySelector("#statusChart");
            const inventoryChartEl = document.querySelector("#inventoryRadialChart");
            
            if (!catChartEl || !statusChartEl || !inventoryChartEl) {
                console.warn("Dashboard chart elements not found, skipping init");
                return;
            }

            if (!categoryData || !statusData || !inventoryData) {
                console.warn("Dashboard chart data not found, skipping init");
                return;
            }

            // Clear previous charts if any
            catChartEl.innerHTML = '';
            statusChartEl.innerHTML = '';
            inventoryChartEl.innerHTML = '';
            
            // 1. Category Chart (Bar)
            const categoryOptions = {
                series: categoryData.series,
                chart: {
                    type: 'bar',
                    height: 320,
                    toolbar: { show: false },
                    fontFamily: 'Plus Jakarta Sans, sans-serif'
                },
                plotOptions: {
                    bar: {
                        borderRadius: 8,
                        columnWidth: '50%',
                        distributed: true,
                    }
                },
                dataLabels: { enabled: false },
                legend: { show: false },
                xaxis: {
                    categories: categoryData.labels,
                    axisBorder: { show: false },
                    axisTicks: { show: false },
                    labels: { style: { colors: '#64748b', fontSize: '11px', fontWeight: 600 } }
                },
                yaxis: {
                    labels: { style: { colors: '#64748b', fontWeight: 600 } }
                },
                grid: {
                    strokeDashArray: 4,
                    borderColor: '#f1f5f9'
                },
                colors: ['#800020', '#be123c', '#fb7185', '#f43f5e', '#e11d48', '#9f1239'],
                tooltip: {
                    theme: 'light',
                    y: { formatter: function (val) { return val + " items" } }
                }
            };
            new ApexCharts(catChartEl, categoryOptions).render();

            // 2. Borrow Status Chart (Donut)
            const statusOptions = {
                series: statusData.series,
                labels: statusData.labels,
                chart: {
                    type: 'donut',
                    height: 320,
                    fontFamily: 'Plus Jakarta Sans, sans-serif',
                    animations: { enabled: true, easing: 'easeinout', speed: 800 }
                },
                colors: ['#f59e0b', '#10b981', '#3b82f6', '#ef4444', '#f97316'], // Pending: Amber, Approved: Emerald, Returned: Blue, Overdue: Red, Rejected: Orange
                plotOptions: {
                    pie: {
                        donut: {
                            size: '78%',
                            labels: {
                                show: true,
                                total: {
                                    show: true,
                                    label: 'Requests',
                                    fontWeight: 800,
                                    color: '#0f172a',
                                    formatter: function (w) {
                                        return w.globals.seriesTotals.reduce((a, b) => a + b, 0)
                                    }
                                }
                            }
                        }
                    }
                },
                dataLabels: { enabled: false },
                stroke: { width: 4, colors: ['#ffffff'] },
                legend: {
                    position: 'bottom',
                    fontSize: '12px',
                    fontWeight: 600,
                    markers: { radius: 12 },
                    itemMargin: { horizontal: 10, vertical: 8 }
                },
                tooltip: { theme: 'light' }
            };
            new ApexCharts(statusChartEl, statusOptions).render();

            // 3. Inventory Status Chart (Donut variant)
             const inventoryOptionsSafe = {
                series: inventoryData.series,
                labels: inventoryData.labels,
                chart: {
                    type: 'donut',
                    height: 320,
                    fontFamily: 'Plus Jakarta Sans, sans-serif',
                    animations: { enabled: true, easing: 'easeinout', speed: 800 }
                },
                colors: ['#10b981', '#f97316', '#ef4444'], // Available: Green, Unavailable: Orange, Damaged: Red
                plotOptions: {
                    pie: {
                        donut: {
                            size: '78%',
                            labels: {
                                show: true,
                                total: {
                                    show: true,
                                    label: 'Total Stock',
                                    fontWeight: 800,
                                    color: '#0f172a',
                                    formatter: function (w) {
                                        return w.globals.seriesTotals.reduce((a, b) => a + b, 0)
                                    }
                                }
                            }
                        }
                    }
                },
                dataLabels: { enabled: false },
                legend: {
                    position: 'bottom',
                    fontSize: '12px',
                    fontWeight: 600
                },
                stroke: { width: 4, colors: ['#ffffff'] },
                tooltip: { theme: 'light' }
            };
            new ApexCharts(inventoryChartEl, inventoryOptionsSafe).render();
        };

        // Initialize on normal page load
        document.addEventListener('DOMContentLoaded', window.initDashboard);
    </script>
</body>
</html>
