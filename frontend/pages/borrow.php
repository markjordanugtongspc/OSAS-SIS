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

// Fetch borrow statistics
try {
    $today = date('Y-m-d');

    // Total borrows
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM borrow_lists WHERE deleted_at IS NULL");
    $total_borrows = $stmt->fetch()['total'] ?? 0;
    
    // Overdue borrows (Any status except Returned, where due_date < today)
    // We use the PHP $today variable to ensure consistency with the frontend/PHP logic
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM borrow_lists WHERE due_date < ? AND borrow_status != 'Returned' AND deleted_at IS NULL");
    $stmt->execute([$today]);
    $overdue_borrows = $stmt->fetch()['total'] ?? 0;
    
    // Active borrows (Pending or Approved) - strictly those NOT overdue
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM borrow_lists WHERE borrow_status IN ('Pending', 'Approved') AND due_date >= ? AND deleted_at IS NULL");
    $stmt->execute([$today]);
    $active_borrows = $stmt->fetch()['total'] ?? 0;
    
    // Pending approval (Not overdue)
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM borrow_lists WHERE borrow_status = 'Pending' AND due_date >= ? AND deleted_at IS NULL");
    $stmt->execute([$today]);
    $pending_approval = $stmt->fetch()['total'] ?? 0;
    
    // Approved borrows (Not overdue)
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM borrow_lists WHERE borrow_status = 'Approved' AND due_date >= ? AND deleted_at IS NULL");
    $stmt->execute([$today]);
    $approved_borrows = $stmt->fetch()['total'] ?? 0;
    
    // Rejected borrows
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM borrow_lists WHERE borrow_status = 'Rejected' AND deleted_at IS NULL");
    $rejected_borrows = $stmt->fetch()['total'] ?? 0;
    
    // Returned borrows
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM borrow_lists WHERE borrow_status = 'Returned' AND deleted_at IS NULL");
    $returned_borrows = $stmt->fetch()['total'] ?? 0;
    
    // Total deposit money (only for Approved borrows, regardless of overdue status, as we still hold the money)
    // Or strictly Approved? If Overdue, we still hold it.
    $stmt = $pdo->query("SELECT SUM(deposit_money)  as total FROM borrow_lists WHERE borrow_status = 'Approved' AND deleted_at IS NULL");
    $total_deposits = $stmt->fetch()['total'] ?? 0;
    
    // Pagination Configuration
    $limit = 5;
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    if ($page < 1) $page = 1;
    $offset = ($page - 1) * $limit;

    // Fetch total count for pagination
    $countStmt = $pdo->query("SELECT COUNT(*) FROM borrow_lists WHERE deleted_at IS NULL");
    $total_borrow_records = $countStmt->fetchColumn();
    $total_pages = ceil($total_borrow_records / $limit);

    // Fetch paginated borrow records with item details
    $stmt = $pdo->prepare("
        SELECT bl.*, i.item_name, i.category, i.image,
               rl.penalty AS penalty
        FROM borrow_lists bl 
        LEFT JOIN items i ON bl.item_id = i.id 
        LEFT JOIN return_lists rl ON bl.id = rl.borrow_list_id
        WHERE bl.deleted_at IS NULL 
        ORDER BY bl.id DESC
        LIMIT :limit OFFSET :offset
    ");
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $borrow_records = $stmt->fetchAll();
    
    // Fetch all items for borrowing (including unavailable to show full inventory)
    $stmt = $pdo->query("SELECT * FROM items ORDER BY item_name ASC");
    $available_items = $stmt->fetchAll();
    
} catch (PDOException $e) {
    $total_borrows = 0;
    $active_borrows = 0;
    $completed_borrows = 0;
    $overdue_borrows = 0;
    $pending_approval = 0;
    $total_deposits = 0;
    $borrow_records = [];
    $available_items = [];
}
?>
<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="../../frontend/images/spc.png">
    <title>Borrow Management | OSAS SIS</title>
    <?= vite(['backend/js/main.js', 'frontend/css/styles.css']) ?>
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- Iconify -->
    <script src="https://code.iconify.design/3/3.1.0/iconify.min.js"></script>
    

</head>
<body class="h-full">
    
    <!-- Include Sidebar -->
    <?php include 'navbar.php'; ?>

    <!-- Main Content -->
    <div class="ml-64 min-h-screen bg-slate-50/50">
        
        <!-- Page Header -->
        <div class="sticky top-0 z-20 bg-white/80 backdrop-blur-md border-b border-slate-200">
            <div class="px-8 py-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-2xl font-semibold text-slate-900 tracking-tight">Borrow Management</h1>
                        <p class="mt-1 text-sm text-slate-600">Track and manage equipment borrowing requests</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <button onclick="openBorrowModal()" class="inline-flex items-center justify-center rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-slate-950 disabled:pointer-events-none disabled:opacity-50 bg-[#800020] text-white hover:bg-[#5c0016] h-10 px-4 py-2 cursor-pointer">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4 mr-2">
                                <line x1="12" y1="5" x2="12" y2="19"></line>
                                <line x1="5" y1="12" x2="19" y2="12"></line>
                            </svg>
                            New Borrow Request
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Content Area -->
        <div class="px-8 py-8">
            
            <!-- Stats Grid - Single Row with All Stats -->
            <div class="grid gap-4 grid-cols-6 animate-in mb-6">
                
                <!-- Total Borrows -->
                <div class="group relative bg-slate-50 rounded-xl border border-slate-200 p-4 hover:shadow-lg transition-all duration-300">
                    <div class="flex items-start justify-between mb-3">
                        <div class="p-2 rounded-lg bg-white shadow-sm transition-all duration-300 group-hover:scale-110">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5 text-slate-600">
                                <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
                            </svg>
                        </div>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-slate-600 mb-1">Total</p>
                        <h3 id="totalStats" class="text-3xl font-bold text-slate-900 mb-1"><?= number_format($total_borrows) ?></h3>
                        <p class="text-[10px] text-slate-500 font-medium tracking-tight">All borrowing records</p>
                    </div>
                </div>
                
                <!-- Approved Borrows -->
                <div class="group relative bg-emerald-50 rounded-xl border border-emerald-100 p-4 hover:shadow-lg transition-all duration-300">
                    <div class="flex items-start justify-between mb-3">
                        <div class="p-2 rounded-lg bg-white shadow-sm transition-all duration-300 group-hover:scale-110">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5 text-emerald-600">
                                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                <polyline points="22 4 12 14.01 9 11.01"></polyline>
                            </svg>
                        </div>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-emerald-700 mb-1">Approved</p>
                        <h3 id="approvedStats" class="text-3xl font-bold text-emerald-600 mb-1"><?= number_format($approved_borrows) ?></h3>
                        <p class="text-[10px] text-emerald-600/70 font-medium tracking-tight">Active requests</p>
                    </div>
                </div>

                <!-- Rejected Borrows -->
                <div class="group relative bg-orange-50 rounded-xl border border-orange-100 p-4 hover:shadow-lg transition-all duration-300">
                    <div class="flex items-start justify-between mb-3">
                        <div class="p-2 rounded-lg bg-white shadow-sm transition-all duration-300 group-hover:scale-110">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5 text-orange-600">
                                <circle cx="12" cy="12" r="10"></circle>
                                <line x1="15" y1="9" x2="9" y2="15"></line>
                                <line x1="9" y1="9" x2="15" y2="15"></line>
                            </svg>
                        </div>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-orange-700 mb-1">Rejected</p>
                        <h3 id="rejectedStats" class="text-3xl font-bold text-orange-600 mb-1"><?= number_format($rejected_borrows) ?></h3>
                        <p class="text-[10px] text-orange-600/70 font-medium tracking-tight">Denied access</p>
                    </div>
                </div>

                <!-- Returned Borrows -->
                <div class="group relative bg-blue-50 rounded-xl border border-blue-100 p-4 hover:shadow-lg transition-all duration-300">
                    <div class="flex items-start justify-between mb-3">
                        <div class="p-2 rounded-lg bg-white shadow-sm transition-all duration-300 group-hover:scale-110">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5 text-blue-600">
                                <polyline points="9 11 12 14 22 4"></polyline>
                                <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path>
                            </svg>
                        </div>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-blue-700 mb-1">Returned</p>
                        <h3 id="returnedStats" class="text-3xl font-bold text-blue-600 mb-1"><?= number_format($returned_borrows) ?></h3>
                        <p class="text-[10px] text-blue-600/70 font-medium tracking-tight">Safe return</p>
                    </div>
                </div>

                <!-- Overdue -->
                <div class="group relative bg-red-50 rounded-xl border border-red-100 p-4 hover:shadow-lg transition-all duration-300">
                    <div class="flex items-start justify-between mb-3">
                        <div class="p-2 rounded-lg bg-white shadow-sm transition-all duration-300 group-hover:scale-110">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5 text-red-600">
                                <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                                <line x1="12" y1="9" x2="12" y2="13"></line>
                                <line x1="12" y1="17" x2="12.01" y2="17"></line>
                            </svg>
                        </div>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-red-700 mb-1">Overdue</p>
                        <h3 id="overdueStats" class="text-3xl font-bold text-red-600 mb-1"><?= number_format($overdue_borrows) ?></h3>
                        <p class="text-[10px] text-red-600/70 font-medium tracking-tight">Past due date</p>
                    </div>
                </div>

                <!-- Total Deposits -->
                <div class="group relative bg-purple-50 rounded-xl border border-purple-100 p-4 hover:shadow-lg transition-all duration-300">
                    <div class="flex items-start justify-between mb-3">
                        <div class="p-2 rounded-lg bg-white shadow-sm transition-all duration-300 group-hover:scale-110">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5 text-purple-600">
                                <line x1="12" y1="1" x2="12" y2="23"></line>
                                <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                            </svg>
                        </div>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-purple-700 mb-1">Deposits</p>
                        <h3 id="depositStats" class="text-2xl font-bold text-purple-600 mb-1">₱<?= number_format($total_deposits, 2) ?></h3>
                        <p class="text-[10px] text-purple-600/70 font-medium tracking-tight">Held approved funds</p>
                    </div>
                </div>

            </div>

            <!-- Search and Status Filter (match spacing with item_management.php) -->
            <div class="flex gap-3 items-center mb-4">
                <!-- Search Box (match item_management.php) -->
                <div class="flex-1 relative">
                    <input 
                        type="text" 
                        id="searchInput" 
                        placeholder="Search by name, ID, or item..." 
                        class="w-full pl-10 pr-4 py-1.5 border border-[#800020] rounded-lg focus:outline-none focus:ring-2 focus:ring-[#800020] focus:border-transparent transition-all text-sm"
                    >
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                    </svg>
                </div>

                <!-- Status Filter (match select sizing in item_management.php) -->
                <div class="w-56">
                    <select 
                        id="statusFilter" 
                        class="w-full px-4 py-1.5 border border-[#800020] rounded-lg focus:outline-none focus:ring-2 focus:ring-[#800020] focus:border-transparent transition-all text-sm"
                    >
                        <option value="">All Status</option>
                        <option value="Pending">Pending</option>
                        <option value="Approved">Approved</option>
                        <option value="Returned">Returned</option>
                        <option value="Rejected">Rejected</option>
                    </select>
                </div>
            </div>

            <!-- Borrow Records Table -->
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden shadow-sm animate-in" style="animation-delay: 0.2s">
                <div class="p-4 border-b border-gray-200 flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-semibold text-gray-900">Borrow Records</h3>
                        <p class="text-xs text-gray-500 mt-1">View and manage all borrowing transactions</p>
                    </div>
                    <button onclick="saveAllRecords()" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg transition-colors shadow-sm hover:shadow-md cursor-pointer">
                        <span class="iconify w-3.5 h-3.5" data-icon="solar:bookmark-circle-bold"></span>
                        Save All Records
                    </button>
                </div>

                <div class="overflow-x-auto">
                    <table id="borrowTable" class="min-w-full">
                        <thead>
                            <tr class="bg-gradient-to-r from-[#800020] to-[#5c0016] text-white">
                                <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider">ID</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider">Borrower</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider">Item</th>
                                <th class="px-6 py-4 text-center text-xs font-semibold uppercase tracking-wider">Qty</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider">Due Date</th>
                                <th class="px-6 py-4 text-right text-xs font-semibold uppercase tracking-wider">Deposit</th>
                                <th class="px-6 py-4 text-center text-xs font-semibold uppercase tracking-wider">Status</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider">Released By</th>
                                <th class="px-6 py-4 text-center text-xs font-semibold uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-100">
                            <?php if (empty($borrow_records)): ?>
                                <tr>
                                    <td colspan="9" class="px-6 py-16 text-center">
                                        <div class="flex flex-col items-center justify-center">
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-16 h-16 text-gray-300 mb-4">
                                                <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
                                            </svg>
                                            <p class="text-gray-500 text-sm font-medium">No borrow records found</p>
                                            <p class="text-gray-400 text-xs mt-1">Click "New Borrow Request" to get started</p>
                                            <button onclick="openBorrowModal()" class="mt-4 inline-flex items-center px-4 py-2 bg-[#800020] text-white rounded-md hover:bg-[#5c0016] transition-colors text-sm font-medium cursor-pointer">
                                                New Borrow Request
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($borrow_records as $record): 
                                    // Determine overdue based on due date and non-returned status
                                    $dueDateObj = new DateTime($record['due_date']);
                                    $todayObj = new DateTime('today'); // Use 'today' to reset time to 00:00:00
                                    $isOverdue = $dueDateObj < $todayObj && $record['borrow_status'] !== 'Returned';
                                ?>
                                    <tr class="hover:bg-gray-50/50 transition-colors duration-150 <?= $isOverdue ? 'overdue-row' : '' ?>" data-id="<?= $record['id'] ?>" data-status="<?= $record['borrow_status'] ?>" data-borrower="<?= htmlspecialchars($record['borrower_name']) ?>" data-item="<?= htmlspecialchars($record['item_name']) ?>">
                                        <td class="px-6 py-4">
                                            <span class="text-sm font-semibold text-gray-900">#<?= $record['id'] ?></span>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span class="text-xs font-medium text-gray-900 whitespace-nowrap max-w-[140px] inline-block truncate"><?= htmlspecialchars($record['borrower_name']) ?></span>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="flex items-center gap-3">
                                                <?php if ($record['image']): ?>
                                                    <img src="../../frontend/images/items/<?= htmlspecialchars($record['image']) ?>" alt="<?= htmlspecialchars($record['item_name']) ?>" class="w-12 h-12 rounded-lg object-cover ring-2 ring-gray-100">
                                                <?php else: ?>
                                                    <div class="w-12 h-12 rounded-lg bg-gradient-to-br from-gray-100 to-gray-200 flex items-center justify-center ring-2 ring-gray-100">
                                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-6 h-6 text-gray-400">
                                                            <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                                                        </svg>
                                                    </div>
                                                <?php endif; ?>
                                                <div class="min-w-0">
                                                    <span class="text-xs font-medium text-gray-900 whitespace-nowrap max-w-[180px] inline-block truncate"><?= htmlspecialchars($record['item_name']) ?></span>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 text-center">
                                             <span class="inline-flex items-center justify-center px-2.5 py-1 rounded-md bg-gray-100 text-sm font-semibold text-gray-900"><?= $record['quantity'] ?></span>
                                         </td>
                                        <td class="px-6 py-4">
                                            <?php 
                                            // Reuse computed overdue flag and due date
                                            $dueDate = $dueDateObj;
                                            ?>
                                            <div class="flex flex-col gap-0.5">
                                                <span class="text-sm <?= $isOverdue ? 'text-red-600 font-semibold' : 'text-gray-900 font-medium' ?>">
                                                    <?= $dueDate->format('M d, Y') ?>
                                                </span>
                                                <?php if ($isOverdue && $record['borrow_status'] !== 'Returned'): ?>
                                                    <span class="text-xs text-red-500 font-medium">⚠ Overdue</span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 text-right">
                                            <span class="text-sm font-semibold text-gray-900">₱<?= number_format($record['deposit_money'], 2) ?></span>
                                        </td>
                                        <td class="px-6 py-4 text-center">
                                            <?php
                                                // Determine label (override to Overdue when applicable)
                                                $statusLabel = $isOverdue && $record['borrow_status'] !== 'Returned'
                                                    ? 'Overdue'
                                                    : $record['borrow_status'];

                                                // Choose an icon per status (clean, minimal inline SVGs)
                                                $statusIcon = '';
                                                $badgeClass = '';

                                                if ($statusLabel === 'Pending') {
                                                    // Yellow clock
                                                    $statusIcon = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-3.5 h-3.5"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>';
                                                    $badgeClass = 'bg-yellow-50 text-yellow-700 ring-1 ring-yellow-600/20';
                                                } elseif ($statusLabel === 'Approved') {
                                                    // Green check
                                                    $statusIcon = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-3.5 h-3.5"><polyline points="20 6 9 17 4 12"/></svg>';
                                                    $badgeClass = 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-600/20';
                                                } elseif ($statusLabel === 'Returned') {
                                                    // Blue check-circle
                                                    $statusIcon = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-3.5 h-3.5"><circle cx="12" cy="12" r="10"/><polyline points="16 8 11 13 8 10"/></svg>';
                                                    $badgeClass = 'bg-blue-50 text-blue-700 ring-1 ring-blue-600/20';
                                                } elseif ($statusLabel === 'Overdue') {
                                                    // Red warning triangle
                                                    $statusIcon = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-3.5 h-3.5"><path d="M10.29 3.86 1.82 18A2 2 0 0 0 3.53 21h16.94A2 2 0 0 0 22.18 18L13.71 3.86a2 2 0 0 0-3.42 0Z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>';
                                                    $badgeClass = 'bg-red-50 text-red-700 ring-1 ring-red-600/20';
                                                } elseif ($statusLabel === 'Rejected') {
                                                    // Red X-circle
                                                    $statusIcon = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-3.5 h-3.5"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>';
                                                    $badgeClass = 'bg-red-50 text-red-700 ring-1 ring-red-600/20';
                                                }
                                            ?>
                                            <span class="status-badge inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-medium <?= $badgeClass ?>">
                                                <?= $statusIcon ?>
                                                <span><?= htmlspecialchars($statusLabel) ?></span>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 released-by-cell">
                                            <span class="text-sm text-gray-600"><?= htmlspecialchars($record['release_by'] ?? 'N/A') ?></span>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="flex items-center justify-center gap-1">
                                                <!-- View Button -->
                                                <button onclick="viewBorrowDetails(<?= $record['id'] ?>)" class="p-1.5 text-blue-500 hover:bg-blue-50 hover:text-blue-700 rounded-lg transition-all duration-200 group cursor-pointer" title="View Details">
                                                    <span class="iconify w-4.5 h-4.5" data-icon="solar:eye-bold" data-inline="false"></span>
                                                </button>

                                                <!-- Save to History Button -->
                                                <button onclick="saveToHistory(<?= $record['id'] ?>)" class="p-1.5 text-emerald-500 hover:bg-emerald-50 hover:text-emerald-700 rounded-lg transition-all duration-200 group save-history-btn cursor-pointer" title="Save to History">
                                                    <span class="iconify w-4.5 h-4.5" data-icon="solar:bookmark-bold" data-inline="false"></span>
                                                </button>

                                                <!-- Actions Dropdown (Status Changes) -->
                                                <div class="dropdown">
                                                    <button onclick="toggleDropdown(event, <?= $record['id'] ?>)" class="p-1.5 text-amber-500 hover:bg-amber-50 hover:text-amber-700 rounded-lg transition-all duration-200 group cursor-pointer" title="Change Status">
                                                        <span class="iconify w-4.5 h-4.5" data-icon="solar:menu-dots-bold" data-inline="false"></span>
                                                    </button>
                                                    <div id="dropdown-<?= $record['id'] ?>" class="dropdown-menu">
                                                        <!-- Approve Option -->
                                                        <div class="dropdown-item" onclick="approveBorrow(<?= $record['id'] ?>)">
                                                            <span class="iconify" data-icon="solar:check-circle-bold" style="color: #10b981; width: 16px; height: 16px;"></span>
                                                            <span style="color: #10b981;">Approve</span>
                                                        </div>
                                                        <!-- Reject Option -->
                                                        <div class="dropdown-item" onclick="rejectBorrow(<?= $record['id'] ?>)">
                                                            <span class="iconify" data-icon="solar:close-circle-bold" style="color: #ef4444; width: 16px; height: 16px;"></span>
                                                            <span style="color: #ef4444;">Reject</span>
                                                        </div>
                                                        <!-- Return Option -->
                                                        <div class="dropdown-item" onclick='markAsReturned(<?= json_encode($record) ?>)'>
                                                            <span class="iconify" data-icon="solar:check-square-bold" style="color: #3b82f6; width: 16px; height: 16px;"></span>
                                                            <span style="color: #3b82f6;">Mark as Returned</span>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Delete Button (Separate) -->
                                                <button onclick="deleteBorrow(<?= $record['id'] ?>)" class="p-1.5 text-red-500 hover:bg-red-50 hover:text-red-700 rounded-lg transition-all duration-200 group cursor-pointer" title="Delete">
                                                    <span class="iconify w-4.5 h-4.5" data-icon="solar:trash-bin-trash-bold" data-inline="false"></span>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Pagination UI -->
            <?php if ($total_pages > 1): ?>
                <div class="mt-6 flex items-center justify-between bg-white px-4 py-3 sm:px-6 rounded-xl border border-slate-200 shadow-sm">
                    <div class="flex flex-1 justify-between sm:hidden">
                        <?php if ($page > 1): ?>
                            <a href="?page=<?= $page - 1 ?>" class="relative inline-flex items-center rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Previous</a>
                        <?php endif; ?>
                        <?php if ($page < $total_pages): ?>
                            <a href="?page=<?= $page + 1 ?>" class="relative ml-3 inline-flex items-center rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Next</a>
                        <?php endif; ?>
                    </div>
                    <div class="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between">
                        <div>
                            <p class="text-sm text-slate-700">
                                Showing
                                <span class="font-medium"><?= $offset + 1 ?></span>
                                to
                                <span class="font-medium"><?= min($offset + $limit, $total_borrow_records) ?></span>
                                of
                                <span class="font-medium"><?= $total_borrow_records ?></span>
                                results
                            </p>
                        </div>
                        <div>
                            <nav class="isolate inline-flex -space-x-px rounded-md shadow-sm" aria-label="Pagination">
                                <!-- Previous Page -->
                                <a href="?page=<?= max(1, $page - 1) ?>" class="relative inline-flex items-center rounded-l-md px-2 py-2 text-slate-400 ring-1 ring-inset ring-slate-300 hover:bg-slate-50 focus:z-20 focus:outline-offset-0 <?= $page <= 1 ? 'pointer-events-none opacity-50' : '' ?>">
                                    <span class="sr-only">Previous</span>
                                    <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                        <path fill-rule="evenodd" d="M12.79 5.23a.75.75 0 01.02 1.06L8.832 10l3.938 3.71a.75.75 0 11-1.04 1.08l-4.5-4.25a.75.75 0 010-1.08l4.5-4.25a.75.75 0 011.06.02z" clip-rule="evenodd" />
                                    </svg>
                                </a>
                                
                                <?php
                                $start_page = max(1, $page - 2);
                                $end_page = min($total_pages, $page + 2);
                                
                                for ($i = $start_page; $i <= $end_page; $i++):
                                ?>
                                    <a href="?page=<?= $i ?>" class="relative inline-flex items-center px-4 py-2 text-sm font-semibold <?= $i === $page ? 'z-10 bg-[#800020] text-white focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#800020]' : 'text-slate-900 ring-1 ring-inset ring-slate-300 hover:bg-slate-50 focus:z-20 focus:outline-offset-0' ?>">
                                        <?= $i ?>
                                    </a>
                                <?php endfor; ?>

                                <!-- Next Page -->
                                <a href="?page=<?= min($total_pages, $page + 1) ?>" class="relative inline-flex items-center rounded-r-md px-2 py-2 text-slate-400 ring-1 ring-inset ring-slate-300 hover:bg-slate-50 focus:z-20 focus:outline-offset-0 <?= $page >= $total_pages ? 'pointer-events-none opacity-50' : '' ?>">
                                    <span class="sr-only">Next</span>
                                    <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                        <path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 01.02-1.06L11.168 10 7.23 6.29a.75.75 0 111.04-1.08l4.5 4.25a.75.75 0 010 1.08l-4.5 4.25a.75.75 0 01-1.06-.02z" clip-rule="evenodd" />
                                    </svg>
                                </a>
                            </nav>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

        </div>
    </div>

    <!-- New Borrow Request Modal -->
    <div id="borrowModal" class="fixed inset-0 z-50 hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <!-- Backdrop -->
        <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm transition-opacity" onclick="closeBorrowModal()"></div>

        <div class="fixed inset-0 z-10 flex items-center justify-center p-4">
            <!-- Modal Panel with max height and scroll -->
            <div class="relative transform bg-white rounded-xl text-left shadow-xl transition-all w-full sm:max-w-2xl max-h-[90vh] flex flex-col">
                
                <!-- Header (Fixed) -->
                <div class="bg-gradient-to-r from-[#800020] to-[#5c0016] px-6 py-4 flex justify-between items-center flex-shrink-0">
                    <h3 class="text-base font-semibold leading-6 text-white flex items-center gap-2" id="modal-title">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5">
                            <path d="M11.47 1.72a.75.75 0 011.06 0l3 3a.75.75 0 01-1.06 1.06l-1.72-1.72V7.5h-1.5V4.06L9.53 5.78a.75.75 0 01-1.06-1.06l3-3zM11.25 7.5V15a.75.75 0 001.5 0V7.5h3.75a3 3 0 013 3v9a3 3 0 01-3 3h-9a3 3 0 01-3-3v-9a3 3 0 013-3h3.75z"></path>
                        </svg>
                        New Borrow Request
                    </h3>
                    <button type="button" onclick="closeBorrowModal()" class="rounded-md bg-white/10 p-1 text-white hover:bg-white/20 focus:outline-none transition-colors cursor-pointer">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                    <!-- Scrollable Form Content -->
                    <div class="overflow-y-auto custom-scrollbar flex-1">
                        <form id="borrowForm" class="px-6 py-5">
                        <div class="grid grid-cols-2 gap-4">
                            
                            <!-- Item Selection -->
                            <div class="col-span-2">
                                <label class="block text-sm font-medium text-slate-700 mb-2">Select Item</label>
                                <select name="item_id" id="itemSelect" required class="w-full px-3 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-[#800020] focus:border-transparent transition-all" onchange="updateItemDescription()">
                                    <option value="">Choose an item...</option>
                                    <?php 
                                    if (empty($available_items)) {
                                        echo '<option value="" disabled>No items found in database</option>';
                                    } else {
                                        // Emoji mapping for categories
                                        $categoryEmojis = [
                                            'Sports Equipment' => '⚽',
                                            'Ball' => '🏀',
                                            'Net' => '🥅',
                                            'Racket' => '🎾',
                                            'Equipment' => '🏋️',
                                            'Protective Gear' => '🦺',
                                            'Electronics' => '💻',
                                            'Audio' => '🎧',
                                            'Camera' => '📷',
                                            'Tools' => '🔧',
                                            'Furniture' => '🪑',
                                            'Office' => '📋',
                                            'Medical' => '⚕️',
                                            'Safety' => '🦺'
                                        ];
                                        
                                        foreach ($available_items as $item): 
                                            $statusBadge = $item['status'] === 'Available' && $item['quantity'] > 0 ? '✓' : '✗';
                                            $statusText = $item['status'] === 'Available' && $item['quantity'] > 0 ? 'Available' : ($item['quantity'] == 0 ? 'Out of Stock' : $item['status']);
                                            
                                            // Find matching emoji for category
                                            $emoji = '📦'; // default
                                            foreach ($categoryEmojis as $key => $emojiIcon) {
                                                if (stripos($item['category'], $key) !== false || stripos($item['item_name'], $key) !== false) {
                                                    $emoji = $emojiIcon;
                                                    break;
                                                }
                                            }
                                    ?>
                                        <option value="<?= $item['id'] ?>" 
                                                data-description="<?= htmlspecialchars($item['description'] ?? $item['category']) ?>" 
                                                data-max="<?= $item['quantity'] ?>" 
                                                data-status="<?= $item['status'] ?>" 
                                                data-category="<?= htmlspecialchars($item['category']) ?>">
                                            <?= $emoji ?> <?= htmlspecialchars($item['item_name']) ?> - <?= htmlspecialchars($item['category']) ?> (Qty: <?= $item['quantity'] ?> - <?= $statusText ?>)
                                        </option>
                                    <?php 
                                        endforeach; 
                                    }
                                    ?>
                                </select>
                                <?php if (!empty($available_items)): ?>
                                    <p class="text-xs text-slate-500 mt-1">Found <?= count($available_items) ?> item(s)</p>
                                <?php endif; ?>
                            </div>

                            <!-- Item Description -->
                            <div class="col-span-2">
                                <label class="block text-sm font-medium text-slate-700 mb-2">Item Description</label>
                                <input type="text" name="item_description" id="itemDescription" readonly class="w-full px-3 py-2 border border-slate-200 rounded-lg bg-slate-50 text-slate-600">
                            </div>

                            <!-- Borrower Name -->
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-2">Borrower Name <span class="text-red-500">*</span></label>
                                <input type="text" name="borrower_name" required class="w-full px-3 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-[#800020] focus:border-transparent transition-all">
                            </div>

                            <!-- Borrower ID -->
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-2">Borrower ID <span class="text-red-500">*</span></label>
                                <input type="text" name="borrower_id" required class="w-full px-3 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-[#800020] focus:border-transparent transition-all">
                            </div>

                            <!-- Course -->
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-2">Course <span class="text-red-500">*</span></label>
                                <input type="text" name="borrower_course" required class="w-full px-3 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-[#800020] focus:border-transparent transition-all">
                            </div>

                            <!-- Year Level -->
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-2">Year Level <span class="text-red-500">*</span></label>
                                <select name="borrower_year" required class="w-full px-3 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-[#800020] focus:border-transparent transition-all">
                                    <option value="">Select year...</option>
                                    <option value="1">1st Year</option>
                                    <option value="2">2nd Year</option>
                                    <option value="3">3rd Year</option>
                                    <option value="4">4th Year</option>
                                </select>
                            </div>

                            <!-- Department -->
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-2">Department <span class="text-red-500">*</span></label>
                                <select name="borrower_department" required class="w-full px-3 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-[#800020] focus:border-transparent transition-all">
                                    <option value="">Select department...</option>
                                    <option value="College of Criminology">College of Criminology</option>
                                    <option value="College of Engineering">College of Engineering</option>
                                    <option value="College of Education">College of Education</option>
                                    <option value="College of Computer Studies">College of Computer Studies</option>
                                    <option value="College of Art and Sciences">College of Art and Sciences</option>
                                    <option value="College of Business Administration">College of Business Administration</option>
                                    <option value="Basic Education">Basic Education</option>
                                    <option value="Others">Others</option>
                                </select>
                            </div>

                            <!-- Semester -->
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-2">Semester <span class="text-red-500">*</span></label>
                                <select name="semester" required class="w-full px-3 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-[#800020] focus:border-transparent transition-all">
                                    <option value="">Select semester...</option>
                                    <option value="1st Semester">1st Semester</option>
                                    <option value="2nd Semester">2nd Semester</option>
                                    <option value="Summer">Summer</option>
                                </select>
                            </div>

                            <!-- School Year -->
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-2">School Year <span class="text-red-500">*</span></label>
                                <select name="school_year" id="schoolYearSelect" required class="w-full px-3 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-[#800020] focus:border-transparent transition-all">
                                    <option value="">Select school year...</option>
                                    <!-- Dynamically populated by JS -->
                                </select>
                            </div>

                            <!-- Contact Number -->
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-2">Contact Number <span class="text-red-500">*</span></label>
                                <input type="tel" name="contact_number" required class="w-full px-3 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-[#800020] focus:border-transparent transition-all">
                            </div>

                            <!-- Quantity -->
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-2">Quantity <span class="text-red-500">*</span></label>
                                <input type="number" name="quantity" id="quantityInput" min="1" required class="w-full px-3 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-[#800020] focus:border-transparent transition-all">
                            </div>

                            <!-- Due Date -->
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-2">Due Date <span class="text-red-500">*</span></label>
                                <input type="date" name="due_date" required class="w-full px-3 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-[#800020] focus:border-transparent transition-all" min="<?= date('Y-m-d', strtotime('+1 day')) ?>">
                            </div>

                            <!-- Deposit Money -->
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-2">Deposit Money <span class="text-red-500">*</span></label>
                                <input type="number" name="deposit_money" step="0.01" min="0" required class="w-full px-3 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-[#800020] focus:border-transparent transition-all">
                            </div>

                         </div>

                         <!-- Actions -->
                         <div class="mt-6 flex gap-3">
                             <button type="submit" class="flex-1 px-4 py-2 bg-[#800020] text-white rounded-md hover:bg-[#5c0016] transition-colors font-medium cursor-pointer">
                                 Submit Borrow Request
                             </button>
                             <button type="button" onclick="closeBorrowModal()" class="px-4 py-2 bg-slate-200 text-slate-700 rounded-md hover:bg-slate-300 transition-colors font-medium cursor-pointer">
                                 Cancel
                             </button>
                         </div>
                        </form>
                    </div>
            </div>
        </div>
    </div>

    <!-- Return Confirmation Modal -->
    <div id="returnModal" class="fixed inset-0 z-50 hidden" aria-labelledby="return-modal-title" role="dialog" aria-modal="true">
        <!-- Backdrop -->
        <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm transition-opacity" onclick="closeReturnModal()"></div>

        <div class="fixed inset-0 z-10 flex items-center justify-center p-4">
            <div class="relative transform bg-white rounded-xl text-left shadow-xl transition-all w-full sm:max-w-lg max-h-[90vh] flex flex-col">
                <!-- Header -->
                <div class="bg-gradient-to-r from-[#800020] to-[#5c0016] px-6 py-4 flex justify-between items-center flex-shrink-0">
                    <div>
                        <h3 class="text-base font-semibold leading-6 text-white flex items-center gap-2" id="return-modal-title">
                            <span class="iconify w-5 h-5" data-icon="solar:check-square-bold"></span>
                            Confirm Item Return
                        </h3>
                        <p class="text-xs text-white/80" id="returnModalSubtext"></p>
                    </div>
                    <button type="button" onclick="closeReturnModal()" class="rounded-md bg-white/10 p-1 text-white hover:bg-white/20 focus:outline-none transition-colors cursor-pointer">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Body -->
                <div class="overflow-y-auto custom-scrollbar flex-1 px-6 py-5 space-y-4">
                    <!-- Summary -->
                    <div class="bg-slate-50 border border-slate-200 rounded-lg p-3 text-xs text-slate-600 space-y-1" id="returnSummary"></div>

                    <!-- Overdue / Penalty Notice -->
                    <div id="returnOverdueNotice" class="hidden text-xs font-medium text-amber-700 bg-amber-50 border border-amber-200 rounded-lg px-3 py-2 flex items-start gap-2">
                        <span class="iconify w-4 h-4 mt-0.5" data-icon="solar:warning-triangle-bold"></span>
                        <span>This borrow is overdue. Please review any applicable penalties before confirming the return.</span>
                    </div>

                    <!-- Form -->
                    <form id="returnForm" class="space-y-3" onsubmit="event.preventDefault(); submitReturn();">
                        <input type="hidden" id="returnBorrowId">
                        <input type="hidden" id="returnItemId">
                        <input type="hidden" id="returnMaxQuantity">
                        <input type="hidden" id="returnDepositMoney">

                        <div class="grid grid-cols-2 gap-3">
                            <div class="col-span-2">
                                <label class="block text-xs font-medium text-slate-700 mb-1">Received By <span class="text-red-500">*</span></label>
                                <input type="text" id="returnReceiveBy" required value="<?= $firstname . ' ' . $lastname ?>" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-[#800020] focus:border-transparent">
                            </div>

                            <div>
                                <label class="block text-xs font-medium text-slate-700 mb-1">Return Date <span class="text-red-500">*</span></label>
                                <input type="date" id="returnDate" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-[#800020] focus:border-transparent">
                            </div>

                            <div>
                                <label class="block text-xs font-medium text-slate-700 mb-1">Item Status <span class="text-red-500">*</span></label>
                                <select id="returnItemStatus" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-[#800020] focus:border-transparent">
                                    <option value="">Select status...</option>
                                    <option value="Good Condition">Good Condition</option>
                                    <option value="Damaged">Damaged</option>
                                    <option value="Lost">Lost</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-medium text-slate-700 mb-1">Quantity Returned <span class="text-red-500">*</span></label>
                                <input type="number" id="returnQuantity" min="1" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-[#800020] focus:border-transparent">
                                <p class="text-[11px] text-slate-500 mt-0.5">Must not exceed borrowed quantity.</p>
                            </div>

                            <div>
                                <label class="block text-xs font-medium text-slate-700 mb-1">Penalty (₱)</label>
                                <input type="number" id="returnPenalty" min="0" step="0.01" value="0" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-[#800020] focus:border-transparent">
                            </div>
                        </div>

                        <div class="pt-2 flex gap-3 justify-end">
                            <button type="button" onclick="closeReturnModal()" class="px-4 py-2 bg-slate-200 text-slate-700 rounded-md hover:bg-slate-300 transition-colors text-sm font-medium cursor-pointer">
                                Cancel
                            </button>
                            <button type="submit" class="px-4 py-2 bg-[#800020] text-white rounded-md hover:bg-[#5c0016] transition-colors text-sm font-medium cursor-pointer">
                                Confirm Return
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- View Details Modal -->
    <div id="detailsModal" class="fixed inset-0 z-50 hidden" aria-labelledby="details-modal-title" role="dialog" aria-modal="true">
        <!-- Backdrop -->
        <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm transition-opacity" onclick="closeDetailsModal()"></div>

        <div class="fixed inset-0 z-10 flex items-center justify-center p-4">
            <!-- Modal Panel with max height and scroll -->
            <div class="relative transform bg-white rounded-xl text-left shadow-xl transition-all w-full sm:max-w-lg max-h-[90vh] flex flex-col">
                
                <!-- Header (Fixed) -->
                <div class="bg-gradient-to-r from-[#800020] to-[#5c0016] px-6 py-4 flex justify-between items-center flex-shrink-0">
                    <h3 class="text-base font-semibold leading-6 text-white" id="details-modal-title">
                        Borrow Details
                    </h3>
                    <button type="button" onclick="closeDetailsModal()" class="rounded-md bg-white/10 p-1 text-white hover:bg-white/20 focus:outline-none transition-colors cursor-pointer">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Scrollable Content -->
                <div id="detailsContent" class="overflow-y-auto custom-scrollbar flex-1 px-6 py-5">
                        <!-- Content will be populated by JavaScript -->
                    </div>
                </div>
            </div>
        </div>
    <!-- End of Content Area, but kept Main Container open for Modals & Scripts -->

    <script>
    // Initialize borrow page - can be called multiple times when content is dynamically loaded
    window.initBorrowPage = function() {
        console.log("Borrow page initialized");
        
        // Modal Functions
        window.openBorrowModal = function() {
            populateSchoolYears(); // Populate SY when opening modal
            document.getElementById('borrowModal').classList.remove('hidden');
            document.body.style.overflow = 'hidden'; // Lock body scroll
        };

        // Populate School Year options dynamically
        window.populateSchoolYears = function() {
            const sySelect = document.getElementById('schoolYearSelect');
            if (!sySelect) return;
            
            // Clear existing options except the first one
            while (sySelect.options.length > 1) {
                sySelect.remove(1);
            }
            
            const currentYear = new Date().getFullYear();
            const startYear = currentYear - 1; // Show from last year
            const endYear = currentYear + 4; // To next 4 years (added 3 more years as requested)
            
            for (let i = startYear; i <= endYear; i++) {
                const sy = `${i}-${i + 1}`;
                const option = document.createElement('option');
                option.value = sy;
                option.textContent = sy;
                
                // Auto-select current school year (rough logic: if month > 5, it's i-(i+1))
                const currentMonth = new Date().getMonth();
                if (i === currentYear && currentMonth >= 5) {
                   option.selected = true;
                } else if (i === currentYear - 1 && currentMonth < 5) {
                   option.selected = true;
                }
                
                sySelect.appendChild(option);
            }
        };

        window.closeBorrowModal = function() {
            document.getElementById('borrowModal').classList.add('hidden');
            document.getElementById('borrowForm').reset();
            document.body.style.overflow = ''; // Unlock body scroll
        };

        window.closeDetailsModal = function() {
            document.getElementById('detailsModal').classList.add('hidden');
            document.body.style.overflow = ''; // Unlock body scroll
        };
        
        window.closeReturnModal = function() {
            const modal = document.getElementById('returnModal');
            if (!modal) return;
            modal.classList.add('hidden');
            document.body.style.overflow = '';
        };

        window.openReturnModal = function(record) {
            const modal = document.getElementById('returnModal');
            const summary = document.getElementById('returnSummary');
            const subtext = document.getElementById('returnModalSubtext');
            const overdueNotice = document.getElementById('returnOverdueNotice');

            if (!modal || !summary || !record) return;

            const today = new Date();
            today.setHours(0, 0, 0, 0);
            
            const dueDateObj = record.due_date ? new Date(record.due_date) : null;
            if (dueDateObj) dueDateObj.setHours(0, 0, 0, 0);
            
            const isOverdue = dueDateObj && !isNaN(dueDateObj) && dueDateObj < today && record.borrow_status !== 'Returned';

            const dueDateLabel = dueDateObj && !isNaN(dueDateObj)
                ? dueDateObj.toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' })
                : 'N/A';

            subtext.textContent = `Borrow #${record.id} • ${record.item_name || 'Unknown item'}`;

            summary.innerHTML = `
                <div class="grid grid-cols-2 gap-x-8 gap-y-1">
                    <div>
                        <p class="font-bold text-slate-800 text-sm mb-1">${record.borrower_name}</p>
                        <p class="text-[11px] mb-0.5"><span class="text-slate-500">ID:</span> ${record.borrower_id || 'N/A'}</p>
                        <p class="text-[11px] mb-0.5"><span class="text-slate-500">Course:</span> ${record.borrower_course || 'N/A'} • <span class="text-slate-500">Year:</span> ${record.borrower_year || 'N/A'}</p>
                        <p class="text-[11px]"><span class="text-slate-500">Department:</span> ${record.borrower_department || 'N/A'}</p>
                    </div>
                    <div class="text-right">
                        <p class="text-[11px] mb-0.5 hover:text-slate-900 transition-colors"><span class="text-slate-500">Item:</span> <span class="font-bold text-slate-800">${record.item_name || 'Unknown item'}</span></p>
                        <p class="text-[11px] mb-0.5"><span class="text-slate-500">Quantity Borrowed:</span> <span class="font-bold text-slate-800">${record.quantity}</span></p>
                        <p class="text-[11px] mb-0.5"><span class="text-slate-500">Due Date:</span> <span class="font-bold ${isOverdue ? 'text-red-600' : 'text-slate-800'}">${dueDateLabel}</span></p>
                        <p class="text-[11px]"><span class="text-slate-500">Deposit:</span> <span class="font-bold text-slate-800">₱${parseFloat(record.deposit_money).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</span></p>
                    </div>
                </div>
            `;

            if (isOverdue) {
                overdueNotice.classList.remove('hidden');
            } else {
                overdueNotice.classList.add('hidden');
            }

            // Populate hidden fields and inputs
            document.getElementById('returnBorrowId').value = record.id;
            document.getElementById('returnItemId').value = record.item_id;
            document.getElementById('returnMaxQuantity').value = record.quantity;
            document.getElementById('returnDepositMoney').value = record.deposit_money;

            const qtyInput = document.getElementById('returnQuantity');
            qtyInput.value = record.quantity;
            qtyInput.max = record.quantity;

            const dateInput = document.getElementById('returnDate');
            const todayStr = new Date().toISOString().split('T')[0];
            dateInput.value = todayStr;
            dateInput.max = todayStr;

            const statusSelect = document.getElementById('returnItemStatus');
            statusSelect.value = '';

            const penaltyInput = document.getElementById('returnPenalty');
            penaltyInput.value = '0';

            modal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        };

        // Helper to close specific dropdown
        window.closeDropdownById = function(id) {
            const dropdown = document.getElementById(`dropdown-${id}`);
            if (dropdown) {
                dropdown.classList.remove('show');
            }
       };
        
        // Close all dropdowns helper
        window.closeAllDropdowns = function(exceptId = null) {
            const dropdowns = document.querySelectorAll('.dropdown-menu.show');
            dropdowns.forEach(dropdown => {
                if (!exceptId || dropdown.id !== `dropdown-${exceptId}`) {
                    dropdown.classList.remove('show');
                }
            });
        };

        // Dropdown toggle function
        window.toggleDropdown = function(event, id) {
            event.stopPropagation();
            
            // Close all other dropdowns
            window.closeAllDropdowns(id);
            
            const dropdown = document.getElementById(`dropdown-${id}`);
            if (dropdown) {
                dropdown.classList.toggle('show');
            }
        };

        // Update item description when item is selected
        window.updateItemDescription = function() {
            const select = document.getElementById('itemSelect');
            const option = select.options[select.selectedIndex];
            const description = option.getAttribute('data-description');
            const maxQty = option.getAttribute('data-max');
            const status = option.getAttribute('data-status');
            const category = option.getAttribute('data-category');
            
            // Update description field
            if (description && category) {
                document.getElementById('itemDescription').value = `${category} - ${description}`;
            } else {
                document.getElementById('itemDescription').value = description || '';
            }
            
            // Set max quantity (minimum 1 to allow input even if 0)
            const quantityInput = document.getElementById('quantityInput');
            quantityInput.max = maxQty > 0 ? maxQty : 999;
            
            // Show warning if item is not available (but don't clear selection)
            if (maxQty == 0) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Item Out of Stock',
                    text: `This item currently has 0 quantity available. The request will be pending until stock is replenished.`,
                    confirmButtonColor: '#800020',
                    confirmButtonText: 'I Understand'
                });
            } else if (status !== 'Available') {
                Swal.fire({
                    icon: 'warning',
                    title: 'Item Not Available',
                    text: `This item is currently ${status.toLowerCase()}. Available quantity: ${maxQty}. The request may require special approval.`,
                    confirmButtonColor: '#800020',
                    confirmButtonText: 'I Understand'
                });
            }
        };

        // View borrow details
        window.viewBorrowDetails = async function(id) {
            const modal = document.getElementById('detailsModal');
            const content = document.getElementById('detailsContent');
            
            // Show loading state
            content.innerHTML = `
                <div class="flex items-center justify-center py-12">
                    <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-[#800020]"></div>
                </div>
            `;
            modal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';

            try {
                const formData = new FormData();
                formData.append('action', 'get_details');
                formData.append('id', id);

                const response = await fetch('../../backend/borrow/process_borrow.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await response.json();

                if (!data.success) {
                    content.innerHTML = `<p class="text-center text-red-500 p-4">${data.message || 'Error loading details'}</p>`;
                    return;
                }

                const record = data.data;
                const statusClass = window.getStatusClass(record);
                
                content.innerHTML = `
                <div class="space-y-4">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-200">
                        <span class="text-sm font-medium text-slate-600">Request ID</span>
                        <span class="text-sm font-semibold text-slate-900">#${record.id}</span>
                    </div>
                    
                    <div>
                        <h4 class="text-sm font-semibold text-slate-900 mb-2">Borrower Information</h4>
                        <div class="space-y-2 bg-slate-50 p-3 rounded-lg">
                            <div class="flex justify-between">
                                <span class="text-sm text-slate-600">Name:</span>
                                <span class="text-sm font-medium text-slate-900">${record.borrower_name}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-sm text-slate-600">ID Number:</span>
                                <span class="text-sm font-medium text-slate-900">${record.borrower_id}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-sm text-slate-600">Course:</span>
                                <span class="text-sm font-medium text-slate-900">${record.borrower_course}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-sm text-slate-600">Year:</span>
                                <span class="text-sm font-medium text-slate-900">${record.borrower_year}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-sm text-slate-600">Department:</span>
                                <span class="text-sm font-medium text-slate-900">${record.borrower_department}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-sm text-slate-600">Contact:</span>
                                <span class="text-sm font-medium text-slate-900">${record.contact_number}</span>
                            </div>
                        </div>
                    </div>
                    
                    <div>
                        <h4 class="text-sm font-semibold text-slate-900 mb-2">Item Information</h4>
                        <div class="space-y-2 bg-slate-50 p-3 rounded-lg">
                            <div class="flex justify-between">
                                <span class="text-sm text-slate-600">Item:</span>
                                <span class="text-sm font-medium text-slate-900">${record.item_name || 'N/A'}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-sm text-slate-600">Description:</span>
                                <span class="text-sm font-medium text-slate-900">${record.item_description || 'N/A'}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-sm text-slate-600">Quantity:</span>
                                <span class="text-sm font-medium text-slate-900">${record.quantity}</span>
                            </div>
                        </div>
                    </div>
                    
                    <div>
                        <h4 class="text-sm font-semibold text-slate-900 mb-2">Borrow Details</h4>
                        <div class="space-y-2 bg-slate-50 p-3 rounded-lg">
                            <div class="flex justify-between">
                                <span class="text-sm text-slate-600">Due Date:</span>
                                <span class="text-sm font-medium text-slate-900">${new Date(record.due_date).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' })}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-sm text-slate-600">Deposit:</span>
                                <span class="text-sm font-medium text-emerald-600">₱${parseFloat(record.deposit_money).toFixed(2)}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-sm text-slate-600">Semester:</span>
                                <span class="text-sm font-medium text-slate-900">${record.semester}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-sm text-slate-600">School Year:</span>
                                <span class="text-sm font-medium text-slate-900">${record.school_year || 'N/A'}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-sm text-slate-600">Status:</span>
                                <span class="badge ${statusClass}">${record.borrow_status}</span>
                            </div>
                            ${record.return_status ? `
                            <div class="flex justify-between">
                                <span class="text-sm text-slate-600">Return Condition:</span>
                                <span class="text-sm font-medium text-slate-900">${record.return_status}</span>
                            </div>
                            ` : ''}
                            ${parseFloat(record.penalty || 0) > 0 ? `
                            <div class="flex justify-between">
                                <span class="text-sm text-slate-600">Penalty:</span>
                                <span class="text-sm font-semibold text-red-600">₱${parseFloat(record.penalty).toFixed(2)}</span>
                            </div>
                            ` : `
                            <div class="flex justify-between">
                                <span class="text-sm text-slate-600">Penalty:</span>
                                <span class="text-sm font-medium text-slate-400 italic">none</span>
                            </div>
                            `}
                            <div class="flex justify-between">
                                <span class="text-sm text-slate-600">Released By:</span>
                                <span class="text-sm font-medium text-slate-900">${record.release_by || 'N/A'}</span>
                            </div>
                        </div>
                    </div>
                </div>
                `;
            } catch (error) {
                 content.innerHTML = `<p class="text-center text-red-500 p-4">Error loading details</p>`;
            }
        };

        window.getStatusClass = function(record) {
            if (record.borrow_status === 'Approved') return 'status-approved';
            if (record.borrow_status === 'Returned') return 'status-returned';
            if (record.borrow_status === 'Rejected') return 'status-rejected';
            if (new Date(record.due_date) < new Date() && record.borrow_status !== 'Returned') return 'status-overdue';
            return 'status-pending';
        };



        // Save to History animation - smooth add-to-cart style
        window.saveToHistory = function(id) {
            const row = document.querySelector(`#borrowTable tbody tr[data-id="${id}"]`);
            const historyNav = document.getElementById('nav-history');
            if (!row || !historyNav) return;

            const saveButton = row.querySelector('.save-history-btn');
            if (!saveButton) return;

            const icon = saveButton.querySelector('.iconify') || saveButton;
            const iconRect = icon.getBoundingClientRect();
            const targetRect = historyNav.getBoundingClientRect();

            // Create flying icon with enhanced styling
            const flyingIcon = icon.cloneNode(true);
            flyingIcon.style.position = 'fixed';
            flyingIcon.style.left = `${iconRect.left}px`;
            flyingIcon.style.top = `${iconRect.top}px`;
            flyingIcon.style.width = `${iconRect.width}px`;
            flyingIcon.style.height = `${iconRect.height}px`;
            flyingIcon.style.zIndex = '9999';
            flyingIcon.style.pointerEvents = 'none';
            flyingIcon.style.color = '#16a34a'; // Green color for saved item
            flyingIcon.style.filter = 'drop-shadow(0 4px 12px rgba(22, 163, 74, 0.4))';
            
            // Create a glowing circle background
            const glowCircle = document.createElement('div');
            glowCircle.style.position = 'fixed';
            glowCircle.style.left = `${iconRect.left + iconRect.width / 2}px`;
            glowCircle.style.top = `${iconRect.top + iconRect.height / 2}px`;
            glowCircle.style.width = '40px';
            glowCircle.style.height = '40px';
            glowCircle.style.marginLeft = '-20px';
            glowCircle.style.marginTop = '-20px';
            glowCircle.style.borderRadius = '50%';
            glowCircle.style.background = 'radial-gradient(circle, rgba(22, 163, 74, 0.3), transparent 70%)';
            glowCircle.style.zIndex = '9998';
            glowCircle.style.pointerEvents = 'none';

            document.body.appendChild(glowCircle);
            document.body.appendChild(flyingIcon);

            // Calculate distance and create parabolic curve
            const startX = iconRect.left + iconRect.width / 2;
            const startY = iconRect.top + iconRect.height / 2;
            const endX = targetRect.left + targetRect.width / 2;
            const endY = targetRect.top + targetRect.height / 2;
            
            const deltaX = endX - startX;
            const deltaY = endY - startY;
            
            // Create a parabolic curve (arc upward then down)
            const curveHeight = -100; // Negative for upward arc
            
            // Animation using keyframes for smooth parabolic motion
            const duration = 1400; // Slower: 1.4 seconds
            let startTime = null;
            
            function animate(currentTime) {
                if (!startTime) startTime = currentTime;
                const elapsed = currentTime - startTime;
                const progress = Math.min(elapsed / duration, 1);
                
                // Smooth easing function (ease-out-cubic)
                const easeProgress = 1 - Math.pow(1 - progress, 3);
                
                // Calculate position along parabolic curve
                const x = startX + deltaX * easeProgress;
                // Parabolic formula: creates arc shape
                const parabolaProgress = 4 * progress * (1 - progress);
                const y = startY + deltaY * easeProgress + curveHeight * parabolaProgress;
                
                // Calculate scale (shrink as it moves)
                const scale = 1 - (easeProgress * 0.5); // From 1 to 0.5
                
                // Add subtle rotation for dynamic effect
                const rotation = easeProgress * 180; // Half rotation
                
                // Fade out in last 30% of animation
                const opacity = progress < 0.7 ? 1 : 1 - ((progress - 0.7) / 0.3);
                
                // Apply transformations
                flyingIcon.style.transform = `translate(${x - startX}px, ${y - startY}px) scale(${scale}) rotate(${rotation}deg)`;
                flyingIcon.style.opacity = opacity;
                
                // Animate glow circle
                glowCircle.style.transform = `translate(${x - startX}px, ${y - startY}px) scale(${1 + easeProgress * 0.5})`;
                glowCircle.style.opacity = 1 - easeProgress;
                
                if (progress < 1) {
                    requestAnimationFrame(animate);
                } else {
                    // Animation complete
                    flyingIcon.remove();
                    glowCircle.remove();
                    row.classList.remove('saved-to-history');
                    
                    // Add success pulse to history nav
                    historyNav.classList.add('history-highlight');
                    
                    setTimeout(() => {
                        historyNav.classList.remove('history-highlight');
                        
                        // Navigate after animation completes
                        window.location.href = `history.php?saved_id=${encodeURIComponent(id)}`;
                    }, 800);
                }
            }
            
            // Start the animation
            row.classList.add('saved-to-history');
            requestAnimationFrame(animate);
        };



        // Approve borrow
        window.approveBorrow = async function(id) {
            closeDropdownById(id);
            const result = await Swal.fire({
                title: 'Approve Borrow Request?',
                text: 'The item will be marked as approved for release',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#10b981',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Yes, approve it',
                cancelButtonText: 'Cancel'
            });

            if (result.isConfirmed) {
                const formData = new FormData();
                formData.append('action', 'approve');
                formData.append('id', id);
                formData.append('release_by', '<?= $firstname . " " . $lastname ?>');

                try {
                    const response = await fetch('../../backend/borrow/process_borrow.php', {
                        method: 'POST',
                        body: formData
                    });
                    const data = await response.json();
                    
                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Approved',
                            text: 'Borrow request has been approved',
                            confirmButtonColor: '#800020',
                            timer: 1500,
                            showConfirmButton: false
                        });
                        
                        // Dynamic update instead of reload
                        const row = document.querySelector(`#borrowTable tr[data-id="${id}"]`);
                        if (row) {
                            row.setAttribute('data-status', 'Approved');
                            
                            // Update status badge
                            const badge = row.querySelector('.status-badge');
                            if (badge) {
                                badge.className = 'status-badge inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700 ring-1 ring-emerald-600/20';
                                badge.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-3.5 h-3.5"><polyline points="20 6 9 17 4 12"/></svg> <span>Approved</span>';
                            }
                            
                            // Update Released By
                            const releasedByCell = row.querySelector('.released-by-cell span');
                            if (releasedByCell) {
                                releasedByCell.textContent = '<?= $firstname . " " . $lastname ?>';
                            }

                            window.refreshStatsCards();
                        }
                    } else {
                        Swal.fire({ icon: 'error', title: 'Error', text: data.message, confirmButtonColor: '#800020' });
                    }
                } catch (error) {
                    Swal.fire({ icon: 'error', title: 'Error', text: 'An error occurred', confirmButtonColor: '#800020' });
                }
            }
        };

        // Reject borrow
        window.rejectBorrow = async function(id) {
            closeDropdownById(id);
            const result = await Swal.fire({
                title: 'Reject Borrow Request?',
                text: 'This action cannot be undone',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc2626',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Yes, reject it',
                cancelButtonText: 'Cancel'
            });

            if (result.isConfirmed) {
                const formData = new FormData();
                formData.append('action', 'reject');
                formData.append('id', id);

                try {
                    const response = await fetch('../../backend/borrow/process_borrow.php', {
                        method: 'POST',
                        body: formData
                    });
                    const data = await response.json();
                    
                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Rejected',
                            text: 'Borrow request has been rejected',
                            confirmButtonColor: '#800020',
                            timer: 1500,
                            showConfirmButton: false
                        });
                        
                        // Dynamic update instead of reload
                        const row = document.querySelector(`#borrowTable tr[data-id="${id}"]`);
                        if (row) {
                            row.setAttribute('data-status', 'Rejected');
                            const badge = row.querySelector('.status-badge');
                            if (badge) {
                                badge.className = 'status-badge inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-medium bg-red-50 text-red-700 ring-1 ring-red-600/20';
                                badge.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-3.5 h-3.5"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg> <span>Rejected</span>';
                            }
                            window.refreshStatsCards();
                        }
                    } else {
                        Swal.fire({ icon: 'error', title: 'Error', text: data.message, confirmButtonColor: '#800020' });
                    }
                } catch (error) {
                    Swal.fire({ icon: 'error', title: 'Error', text: 'An error occurred', confirmButtonColor: '#800020' });
                }
            }
        };

        // Mark as returned (opens modal)
        window.markAsReturned = function(record) {
            if (record && record.id) {
                closeDropdownById(record.id);
            }
            openReturnModal(record);
        };

        window.submitReturn = async function() {
            const borrowId = document.getElementById('returnBorrowId').value;
            const itemId = document.getElementById('returnItemId').value;
            const maxQty = parseInt(document.getElementById('returnMaxQuantity').value || '0', 10);
            const qty = parseInt(document.getElementById('returnQuantity').value || '0', 10);
            const receiveBy = document.getElementById('returnReceiveBy').value.trim();
            const returnDate = document.getElementById('returnDate').value;
            const itemStatus = document.getElementById('returnItemStatus').value;
            const penalty = parseFloat(document.getElementById('returnPenalty').value || '0');

            if (!borrowId || !itemId) return;

            if (!receiveBy || !returnDate || !itemStatus || !qty || qty <= 0) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Incomplete Details',
                    text: 'Please fill in all required return details.',
                    confirmButtonColor: '#800020'
                });
                return;
            }

            if (qty > maxQty) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Invalid Quantity',
                    text: 'Quantity returned cannot be greater than the quantity borrowed.',
                    confirmButtonColor: '#800020'
                });
                return;
            }

            const confirmResult = await Swal.fire({
                title: 'Confirm Return?',
                text: 'This will mark the item as returned and record any penalties.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#800020',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Yes, confirm',
                cancelButtonText: 'Cancel'
            });

            if (!confirmResult.isConfirmed) return;

            const formData = new FormData();
            formData.append('action', 'return');
            formData.append('id', borrowId);
            formData.append('item_id', itemId);
            formData.append('item_quantity_return', qty);
            formData.append('receive_by', receiveBy);
            formData.append('item_return', returnDate);
            formData.append('item_status', itemStatus);
            formData.append('penalty', isNaN(penalty) ? 0 : penalty);

            try {
                const response = await fetch('../../backend/borrow/process_borrow.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await response.json();
                if (data.success) {
                    closeReturnModal();
                    Swal.fire({
                        icon: 'success',
                        title: 'Returned',
                        text: 'Item has been marked as returned',
                        confirmButtonColor: '#800020',
                        timer: 1500,
                        showConfirmButton: false
                    });
                    
                    // Dynamic update instead of reload
                    const row = document.querySelector(`#borrowTable tr[data-id="${borrowId}"]`);
                    if (row) {
                        row.setAttribute('data-status', 'Returned');
                        const badge = row.querySelector('.status-badge');
                        if (badge) {
                            badge.className = 'status-badge inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-medium bg-blue-50 text-blue-700 ring-1 ring-blue-600/20';
                            badge.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-3.5 h-3.5"><circle cx="12" cy="12" r="10"/><polyline points="16 8 11 13 8 10"/></svg> <span>Returned</span>';
                        }
                        window.refreshStatsCards();
                    }
                } else {
                    Swal.fire({ icon: 'error', title: 'Error', text: data.message, confirmButtonColor: '#800020' });
                }
            } catch (error) {
                Swal.fire({ icon: 'error', title: 'Error', text: 'An error occurred', confirmButtonColor: '#800020' });
            }
        };

        // Save All Records
        window.saveAllRecords = async function() {
            const rows = document.querySelectorAll('#borrowTable tbody tr');
            if (rows.length === 0) {
                Swal.fire({
                    icon: 'info',
                    title: 'No Records',
                    text: 'There are no records to save.',
                    confirmButtonColor: '#800020'
                });
                return;
            }

            const result = await Swal.fire({
                title: 'Save All Records?',
                text: `Are you sure you want to save all ${rows.length} records to history?`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#10b981',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Yes, save all',
                cancelButtonText: 'Cancel'
            });

            if (result.isConfirmed) {
                // Show loading state
                Swal.fire({
                    title: 'Saving Records...',
                    text: 'Please wait while we save all records.',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                try {
                    const formData = new FormData();
                    formData.append('action', 'save_all_history');

                    const response = await fetch('../../backend/borrow/process_borrow.php', {
                        method: 'POST',
                        body: formData
                    });

                    const data = await response.json();

                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Saved!',
                            text: `${data.count} records have been saved to history.`,
                            confirmButtonColor: '#800020',
                            timer: 2000,
                            showConfirmButton: false
                        });

                        // Optionally, disable save buttons on all rows or refresh table
                        // For visual feedback, let's disable the save buttons
                        document.querySelectorAll('.save-history-btn').forEach(btn => {
                            btn.disabled = true;
                            btn.classList.add('opacity-50', 'cursor-not-allowed');
                            btn.classList.remove('hover:bg-emerald-50', 'hover:text-emerald-700');
                        });
                    } else {
                        Swal.fire({ icon: 'error', title: 'Error', text: data.message, confirmButtonColor: '#800020' });
                    }
                } catch (error) {
                    console.error('Error saving all records:', error);
                    Swal.fire({ icon: 'error', title: 'Error', text: 'An error occurred while saving records.', confirmButtonColor: '#800020' });
                }
            }
        };

        // Delete borrow
        window.deleteBorrow = async function(id) {
            closeDropdownById(id);
            const result = await Swal.fire({
                title: 'Delete Borrow Record?',
                text: 'This will permanently delete the record',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc2626',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Yes, delete it',
                cancelButtonText: 'Cancel'
            });

            if (result.isConfirmed) {
                const formData = new FormData();
                formData.append('action', 'delete');
                formData.append('id', id);

               try {
                    const response = await fetch('../../backend/borrow/process_borrow.php', {
                        method: 'POST',
                        body: formData
                    });
                    const data = await response.json();
                    
                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Deleted',
                            text: 'Borrow record has been permanently deleted',
                            confirmButtonColor: '#800020',
                            timer: 1500,
                            showConfirmButton: false
                        });
                        
                        // Dynamic update instead of reload
                        const row = document.querySelector(`#borrowTable tr[data-id="${id}"]`);
                        if (row) {
                            row.style.opacity = '0';
                            row.style.transform = 'translateX(20px)';
                            row.style.transition = 'all 0.3s ease';
                            setTimeout(() => {
                                row.remove();
                                window.refreshStatsCards();
                            }, 300);
                        }
                    } else {
                        Swal.fire({ icon: 'error', title: 'Error', text: data.message, confirmButtonColor: '#800020' });
                    }
                } catch (error) {
                    Swal.fire({ icon: 'error', title: 'Error', text: 'An error occurred', confirmButtonColor: '#800020' });
                }
            }
        };

        // Filter functions
        // Filter functions
        window.applyFilters = function() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const statusFilter = document.getElementById('statusFilter').value;
            const rows = document.querySelectorAll('#borrowTable tbody tr');

            rows.forEach(row => {
                const borrower = row.getAttribute('data-borrower').toLowerCase();
                const item = row.getAttribute('data-item').toLowerCase();
                const status = row.getAttribute('data-status');
                const idValue = (row.getAttribute('data-id') || '').toString().toLowerCase();

                // Match on ID (with or without leading #), borrower name, or item name
                const matchesSearch = !searchTerm ||
                    idValue.includes(searchTerm.replace('#', '')) ||
                    (`#${idValue}`).includes(searchTerm) ||
                    borrower.includes(searchTerm) ||
                    item.includes(searchTerm);

                const matchesStatus = !statusFilter || status === statusFilter;
                
                if (matchesSearch && matchesStatus) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        };

        // Fetch and update stats cards
        window.refreshStatsCards = async function() {
            try {
                const formData = new FormData();
                formData.append('action', 'get_stats');
                
                const response = await fetch('../../backend/borrow/process_borrow.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    const data = result.data;
                    
                    // Helper to animate numbers
                    const animateValue = (id, start, end, prefix = '') => {
                        const obj = document.getElementById(id);
                        if (!obj) return;
                        
                        // Simply set for now to avoid complexity, or implement basic animation
                        if (prefix === '₱') {
                            obj.textContent = prefix + parseFloat(end).toLocaleString('en-PH', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                        } else {
                            obj.textContent = parseInt(end).toLocaleString();
                        }
                    };
                    
                    animateValue('totalStats', 0, data.total);
                    animateValue('approvedStats', 0, data.approved);
                    animateValue('rejectedStats', 0, data.rejected);
                    animateValue('returnedStats', 0, data.returned);
                    animateValue('overdueStats', 0, data.overdue);
                    animateValue('depositStats', 0, data.deposits, '₱');
                }
            } catch (error) {
                console.error('Error fetching stats:', error);
            }
        };

        window.resetFilters = function() {
            document.getElementById('searchInput').value = '';
            document.getElementById('statusFilter').value = '';
            const rows = document.querySelectorAll('#borrowTable tbody tr');
            rows.forEach(row => row.style.display = '');
        };

        // Close dropdown when clicking outside
        document.removeEventListener('click', window.borrowPageClickHandler);
        window.borrowPageClickHandler = function(event) {
            if (!event.target.closest('.dropdown')) {
                window.closeAllDropdowns();
            }
        };
        document.addEventListener('click', window.borrowPageClickHandler);
        
        // Handle form submission
        const borrowForm = document.getElementById('borrowForm');
        if (borrowForm) {
            // Remove old listener if exists
            const newForm = borrowForm.cloneNode(true);
            borrowForm.parentNode.replaceChild(newForm, borrowForm);
            
            newForm.addEventListener('submit', async function(e) {
                e.preventDefault();
                
                const formData = new FormData(this);
                formData.append('action', 'create_borrow');
                
                try {
                    const response = await fetch('../../backend/borrow/process_borrow.php', {
                        method: 'POST',
                        body: formData
                    });
                    
                    const result = await response.json();
                    
                    if (result.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Success!',
                            text: 'Borrow request created successfully',
                            confirmButtonColor: '#800020'
                        }).then(() => {
                            location.reload();
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: result.message,
                            confirmButtonColor: '#800020'
                        });
                    }
                } catch (error) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'An error occurred while processing the request',
                        confirmButtonColor: '#800020'
                    });
                }
            });
        }

        // Real-time search & status filter
        const searchInput = document.getElementById('searchInput');
        const statusFilter = document.getElementById('statusFilter');
        if (searchInput) {
            searchInput.removeEventListener('input', window.applyFilters);
            searchInput.addEventListener('input', window.applyFilters);
        }
        if (statusFilter) {
            statusFilter.removeEventListener('change', window.applyFilters);
            statusFilter.addEventListener('change', window.applyFilters);
        }
    };
    
    // Initialize on page load
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', window.initBorrowPage);
    } else {
        window.initBorrowPage();
    }
    </script>
    </div><!-- End of .ml-64 -->
</body>
</html>
