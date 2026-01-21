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
$userId = $_SESSION['user_id'];

// Create the user_history_saved table if it doesn't exist
try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS user_history_saved (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            borrow_list_id INT NOT NULL,
            saved_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_user_borrow (user_id, borrow_list_id),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (borrow_list_id) REFERENCES borrow_lists(id) ON DELETE CASCADE
        )
    ");
} catch (PDOException $e) {
    // Table might already exist or there's a DB error - continue anyway
}

$savedId = isset($_GET['saved_id']) ? (int) $_GET['saved_id'] : 0;
$savedRecord = null;
$savedHistoryList = [];

try {
    // If a specific ID is passed, save it to the database (if not already saved)
    if ($savedId > 0) {
        // Check if this borrow exists
        $checkStmt = $pdo->prepare("
            SELECT id FROM borrow_lists WHERE id = ? AND deleted_at IS NULL
        ");
        $checkStmt->execute([$savedId]);
        
        if ($checkStmt->fetch()) {
            // Insert or ignore if already exists
            $saveStmt = $pdo->prepare("
                INSERT IGNORE INTO user_history_saved (user_id, borrow_list_id) 
                VALUES (?, ?)
            ");
            $saveStmt->execute([$userId, $savedId]);
        }
    }
    
    // Pagination Configuration
    $limit = 10;
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    if ($page < 1) $page = 1;
    $offset = ($page - 1) * $limit;

    // Fetch total count for pagination
    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM user_history_saved uhs INNER JOIN borrow_lists bl ON uhs.borrow_list_id = bl.id WHERE uhs.user_id = ? AND bl.deleted_at IS NULL");
    $countStmt->execute([$userId]);
    $total_history_count = $countStmt->fetchColumn();
    $total_pages = ceil($total_history_count / $limit);

    // Fetch saved history with pagination
    $stmt = $pdo->prepare("
        SELECT bl.*, i.item_name, i.category, i.image, i.semester AS item_semester,
               rl.penalty AS penalty, rl.item_status AS return_status
        FROM user_history_saved uhs
        INNER JOIN borrow_lists bl ON uhs.borrow_list_id = bl.id
        LEFT JOIN items i ON bl.item_id = i.id 
        LEFT JOIN return_lists rl ON bl.id = rl.borrow_list_id
        WHERE uhs.user_id = :user_id AND bl.deleted_at IS NULL 
        ORDER BY uhs.saved_at DESC, bl.id DESC
        LIMIT :limit OFFSET :offset
    ");
    $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $savedHistoryList = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    
    // If we have any saved history, use the most recent as the featured record
    if (!empty($savedHistoryList)) {
        $savedRecord = $savedHistoryList[0];
    }
    
    // Recent history (kept for potential future use, not shown on page)
    $recentStmt = $pdo->query(" 
        SELECT bl.*, i.item_name, i.category, i.image, i.semester AS item_semester 
        FROM borrow_lists bl 
        LEFT JOIN items i ON bl.item_id = i.id 
        WHERE bl.deleted_at IS NULL 
        ORDER BY bl.id DESC 
        LIMIT 20
    ");
    $recentHistory = $recentStmt->fetchAll(PDO::FETCH_ASSOC);


} catch (PDOException $e) {
    $savedRecord = null;
    $recentHistory = [];
    $savedHistoryList = [];
}
?>
<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="../../frontend/images/spc.png">
    <title>History | OSAS SIS</title>
    <?= vite(['backend/js/main.js', 'frontend/css/styles.css']) ?>
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- Iconify -->
    <script src="https://code.iconify.design/3/3.1.0/iconify.min.js"></script>

    <style>
        /* Page-specific overrides or minor adjustments can go here */
    </style>
</head>
<body class="h-full">

    <!-- Include Sidebar -->
    <?php include 'navbar.php'; ?>

    <!-- Main Content -->
    <div class="ml-64 min-h-screen bg-slate-50/50">

        <!-- Page Header -->
        <div class="sticky top-0 z-20 bg-white/80 backdrop-blur-md border-b border-slate-200">
            <div class="px-8 py-6 flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-semibold text-slate-900 tracking-tight">History</h1>
                    <p class="mt-1 text-sm text-slate-600">Recently saved borrow records and their details</p>
                </div>
            </div>
        </div>

        <!-- Content Area -->
        <div class="px-8 py-8 space-y-6">

            <?php if (!empty($savedHistoryList)): ?>
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm animate-in transition-shadow duration-200 hover:shadow-md hover:border-slate-300" style="animation-delay: 0.05s">
                    <div class="p-4 border-b border-gray-200 flex items-center justify-between gap-3">
                        <div>
                            <h3 class="text-sm font-semibold text-slate-900">Saved Borrow History</h3>
                            <p class="text-xs text-slate-500 mt-1">Records you saved from Borrow Management</p>
                        </div>
                        <div class="flex items-center gap-3">
                            <select id="historySchoolYearFilter" onchange="applyHistoryFilters()" class="px-3 py-1 rounded-full border border-slate-200 text-xs text-slate-700 bg-white focus:outline-none focus:ring-2 focus:ring-[#800020] focus:border-transparent">
                                <option value="">All School Years</option>
                                <!-- Dynamically populated -->
                            </select>
                            <select id="historySemesterFilter" onchange="applyHistoryFilters()" class="px-3 py-1 rounded-full border border-slate-200 text-xs text-slate-700 bg-white focus:outline-none focus:ring-2 focus:ring-[#800020] focus:border-transparent">
                                <option value="">All Semesters</option>
                                <option value="1st Semester">1st Semester</option>
                                <option value="2nd Semester">2nd Semester</option>
                                <option value="Summer">Summer</option>
                            </select>
                            <button type="button" onclick="exportAllHistory()" class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full border border-emerald-200 text-xs font-medium text-emerald-700 hover:bg-emerald-50 hover:border-emerald-300 transition-colors cursor-pointer">
                                <span class="iconify w-4 h-4" data-icon="solar:documents-bold" data-inline="false"></span>
                                <span>Export All</span>
                            </button>
                        </div>
                    </div>
                    <div class="overflow-x-auto">
                        <table id="saved-history-list-table" class="min-w-full">
                            <thead>
                                <tr class="bg-gradient-to-r from-[#800020] to-[#5c0016] text-white">
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider">ID</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider">Borrower</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider">Item</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider">SY / Sem</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider">Due Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider">Penalty</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-100">
                                <?php foreach ($savedHistoryList as $row): ?>
                                    <?php
                                        $rowDue = new DateTime($row['due_date']);
                                        $rowOverdue = $rowDue < new DateTime() && $row['borrow_status'] !== 'Returned';
                                        $rowStatusLabel = $rowOverdue && $row['borrow_status'] !== 'Returned' ? 'Overdue' : $row['borrow_status'];

                                        $rowBadgeClass = '';
                                        $rowStatusIcon = '';

                                        if ($rowStatusLabel === 'Pending') {
                                            $rowStatusIcon = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-3.5 h-3.5"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>';
                                            $rowBadgeClass = 'bg-yellow-50 text-yellow-700 ring-1 ring-yellow-600/20';
                                        } elseif ($rowStatusLabel === 'Approved') {
                                            $rowStatusIcon = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-3.5 h-3.5"><polyline points="20 6 9 17 4 12"/></svg>';
                                            $rowBadgeClass = 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-600/20';
                                        } elseif ($rowStatusLabel === 'Returned') {
                                            $rowStatusIcon = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-3.5 h-3.5"><circle cx="12" cy="12" r="10"/><polyline points="16 8 11 13 8 10"/></svg>';
                                            $rowBadgeClass = 'bg-blue-50 text-blue-700 ring-1 ring-blue-600/20';
                                        } elseif ($rowStatusLabel === 'Overdue') {
                                            $rowStatusIcon = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-3.5 h-3.5"><path d="M10.29 3.86 1.82 18A2 2 0 0 0 3.53 21h16.94A2 2 0 0 0 22.18 18L13.71 3.86a2 2 0 0 0-3.42 0Z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>';
                                            $rowBadgeClass = 'bg-red-50 text-red-700 ring-1 ring-red-600/20';
                                        } elseif ($rowStatusLabel === 'Rejected') {
                                            $rowStatusIcon = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-3.5 h-3.5"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>';
                                            $rowBadgeClass = 'bg-red-50 text-red-700 ring-1 ring-red-600/20';
                                        }
                                    ?>
                                    <tr class="transition-all duration-150 hover:bg-slate-100 hover:ring-1 hover:ring-[#800020]/15" 
                                        data-semester="<?= htmlspecialchars($row['semester'] ?? '') ?>" 
                                        data-sy="<?= htmlspecialchars($row['school_year'] ?? '') ?>">
                                        <td class="px-6 py-3 text-sm font-semibold text-gray-900">#<?= $row['id'] ?></td>
                                        <td class="px-6 py-3 text-xs font-medium text-gray-900 whitespace-nowrap max-w-[160px] truncate"><?= htmlspecialchars($row['borrower_name']) ?></td>
                                        <td class="px-6 py-3 text-xs font-medium text-gray-900 whitespace-nowrap max-w-[200px] truncate"><?= htmlspecialchars($row['item_name'] ?? 'Unknown item') ?></td>
                                        <td class="px-6 py-3">
                                            <div class="flex flex-col">
                                                <span class="text-xs font-semibold text-gray-900"><?= htmlspecialchars($row['school_year'] ?? 'N/A') ?></span>
                                                <span class="text-[10px] text-gray-500"><?= htmlspecialchars($row['semester'] ?? 'N/A') ?></span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-3 text-xs text-gray-700"><?= $rowDue->format('M d, Y') ?></td>
                                        <td class="px-6 py-3 text-xs font-medium text-gray-900">
                                            <?= (!empty($row['penalty']) && $row['penalty'] > 0) ? '₱' . number_format($row['penalty'], 2) : '<span class="text-slate-400 italic">none</span>' ?>
                                        </td>
                                        <td class="px-6 py-3">
                                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-medium <?= $rowBadgeClass ?>">
                                                <?= $rowStatusIcon ?>
                                                <span><?= htmlspecialchars($rowStatusLabel) ?></span>
                                            </span>
                                        </td>
                                        <td class="px-6 py-3">
                                            <div class="flex items-center gap-1.5">
                                                <button type="button" onclick='viewHistoryDetails(<?= json_encode($row, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>, event)' class="p-1.5 text-blue-500 hover:bg-blue-50 hover:text-blue-700 rounded-lg transition-all duration-200 group cursor-pointer" title="View">
                                                    <span class="iconify w-4.5 h-4.5" data-icon="solar:eye-bold" data-inline="false"></span>
                                                </button>
                                                <button type="button" onclick="deleteBorrow(<?= (int) $row['id'] ?>, event)" class="p-1.5 text-red-500 hover:bg-red-50 hover:text-red-700 rounded-lg transition-all duration-200 cursor-pointer" title="Delete">
                                                    <span class="iconify w-4 h-4" data-icon="solar:trash-bin-trash-bold" data-inline="false"></span>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                </div>

                <!-- Pagination UI -->
                <?php if ($total_pages > 1): ?>
                    <div class="mt-4 flex items-center justify-between bg-white px-4 py-3 sm:px-6 rounded-xl border border-slate-200 shadow-sm">
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
                                <p class="text-[11px] text-slate-500 uppercase tracking-wider">
                                    Page <span class="font-bold text-slate-900"><?= $page ?></span> of <span class="font-bold text-slate-900"><?= $total_pages ?></span> 
                                    (<?= $total_history_count ?> total records)
                                </p>
                            </div>
                            <div>
                                <nav class="isolate inline-flex -space-x-px rounded-md shadow-sm" aria-label="Pagination">
                                    <!-- Previous Page -->
                                    <a href="?page=<?= max(1, $page - 1) ?>" class="relative inline-flex items-center rounded-l-md px-2 py-2 text-slate-400 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:z-20 focus:outline-offset-0 <?= $page <= 1 ? 'pointer-events-none opacity-50' : '' ?>">
                                        <span class="sr-only">Previous</span>
                                        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                            <path fill-rule="evenodd" d="M12.79 5.23a.75.75 0 01.02 1.06L8.832 10l3.938 3.71a.75.75 0 11-1.04 1.08l-4.5-4.25a.75.75 0 010-1.08l4.5-4.25a.75.75 0 011.06.02z" clip-rule="evenodd" />
                                        </svg>
                                    </a>
                                    
                                    <?php
                                    $start_page = max(1, $page - 2);
                                    $end_page = min($total_pages, $page + 2);
                                    
                                    for ($i = $start_page; $i <= $end_page; $i++):
                                    ?>
                                        <a href="?page=<?= $i ?>" class="relative inline-flex items-center px-3 py-1.5 text-xs font-semibold <?= $i === $page ? 'z-10 bg-[#800020] text-white focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#800020]' : 'text-slate-900 ring-1 ring-inset ring-slate-300 hover:bg-slate-50 focus:z-20 focus:outline-offset-0' ?>">
                                            <?= $i ?>
                                        </a>
                                    <?php endfor; ?>

                                    <!-- Next Page -->
                                    <a href="?page=<?= min($total_pages, $page + 1) ?>" class="relative inline-flex items-center rounded-r-md px-2 py-2 text-slate-400 ring-1 ring-inset ring-slate-300 hover:bg-slate-50 focus:z-20 focus:outline-offset-0 <?= $page >= $total_pages ? 'pointer-events-none opacity-50' : '' ?>">
                                        <span class="sr-only">Next</span>
                                        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                            <path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 01.02-1.06L11.168 10 7.23 6.29a.75.75 0 111.04-1.08l4.5 4.25a.75.75 0 010 1.08l-4.5 4.25a.75.75 0 01-1.06-.02z" clip-rule="evenodd" />
                                        </svg>
                                    </a>
                                </nav>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            <?php else: ?>
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm animate-in" style="animation-delay: 0.05s">
                    <div class="p-6 text-center text-xs text-slate-500">
                        No saved history records yet. Use "Save to History" in Borrow Management to add entries.
                    </div>
                </div>
            <?php endif; ?>

            <!-- Inventory Logs Section Removed (Moved to dedicated Logs page) -->

        </div>
    </div>

    <!-- Saved History Details Modal -->
    <div id="historyDetailsModal" class="fixed inset-0 z-40 hidden items-center justify-center bg-black/30 backdrop-blur-sm">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-3xl mx-4 max-h-[85vh] overflow-hidden flex flex-col">
            <div class="flex items-center justify-between px-5 py-3 border-b border-slate-200">
                <h3 class="text-sm font-semibold text-slate-900">Saved Borrow Details</h3>
                <div class="flex items-center gap-2">
                    <button type="button" onclick="exportCurrentHistoryRecord()" class="inline-flex items-center px-3 py-1 rounded-full border border-emerald-200 text-xs font-medium text-emerald-700 hover:bg-emerald-50 hover:border-emerald-300 transition-colors cursor-pointer">
                        <span class="iconify w-4 h-4 mr-1" data-icon="solar:document-text-bold" data-inline="false"></span>
                        Export
                    </button>
                    <button type="button" onclick="closeHistoryDetails()" class="p-1.5 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition-colors cursor-pointer">
                        <span class="iconify w-4 h-4" data-icon="solar:close-circle-bold" data-inline="false"></span>
                    </button>
                </div>
            </div>
            <div id="historyDetailsContent" class="p-5 overflow-y-auto text-sm text-slate-700"></div>
        </div>
    </div>

    <script>
        // Keep track of the currently viewed history record so we can
        // re-render details (and recompute overdue/status) before export
        let currentHistoryRecord = null;
        function applyHistoryFilters() {
            const semSelect = document.getElementById('historySemesterFilter');
            const sySelect = document.getElementById('historySchoolYearFilter');
            const table = document.getElementById('saved-history-list-table');
            if (!table) return;

            const semValue = semSelect ? semSelect.value : '';
            const syValue = sySelect ? sySelect.value : '';
            const rows = table.querySelectorAll('tbody tr');

            rows.forEach(row => {
                const semester = row.getAttribute('data-semester') || '';
                const sy = row.getAttribute('data-sy') || '';
                
                const matchesSem = !semValue || semester === semValue;
                const matchesSY = !syValue || sy === syValue;
                
                if (matchesSem && matchesSY) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        function populateHistorySYFilter() {
            const sySelect = document.getElementById('historySchoolYearFilter');
            const table = document.getElementById('saved-history-list-table');
            if (!sySelect || !table) return;

            const syValues = new Set();
            table.querySelectorAll('tbody tr').forEach(row => {
                const sy = row.getAttribute('data-sy');
                if (sy && sy !== 'N/A') syValues.add(sy);
            });

            // Convert set to sorted array
            const sortedSY = Array.from(syValues).sort().reverse();
            
            sortedSY.forEach(sy => {
                const option = document.createElement('option');
                option.value = sy;
                option.textContent = sy;
                sySelect.appendChild(option);
            });
        }

        // Initialize SY filter on load
        document.addEventListener('DOMContentLoaded', populateHistorySYFilter);

        function viewHistoryDetails(record, event) {
            if (event) event.stopPropagation();

            const modal = document.getElementById('historyDetailsModal');
            const content = document.getElementById('historyDetailsContent');
            if (!modal || !content || !record) return;

            // Store the latest record being viewed for export use
            currentHistoryRecord = record;

            const rawDueDate = record.due_date ? new Date(record.due_date) : null;
            const dueDate = rawDueDate && !isNaN(rawDueDate)
                ? rawDueDate.toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' })
                : 'N/A';

            const deposit = record.deposit_money
                ? parseFloat(record.deposit_money).toFixed(2)
                : '0.00';

            // Match previous Saved Borrow status logic
            let statusLabel = record.borrow_status || 'N/A';
            if (rawDueDate && !isNaN(rawDueDate) && record.borrow_status !== 'Returned') {
                const today = new Date();
                if (rawDueDate < today) {
                    statusLabel = 'Overdue';
                }
            }

            let badgeClass = '';
            if (statusLabel === 'Pending') {
                badgeClass = 'bg-yellow-50 text-yellow-700 ring-1 ring-yellow-600/20';
            } else if (statusLabel === 'Approved') {
                badgeClass = 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-600/20';
            } else if (statusLabel === 'Returned') {
                badgeClass = 'bg-blue-50 text-blue-700 ring-1 ring-blue-600/20';
            } else if (statusLabel === 'Overdue' || statusLabel === 'Rejected') {
                badgeClass = 'bg-red-50 text-red-700 ring-1 ring-red-600/20';
            }

            const imageSrc = record.image
                ? `../../frontend/images/items/${record.image}`
                : null;

            content.innerHTML = `
                <div class="space-y-4">
                    <div class="flex items-center justify-between mb-2">
                        <div>
                            <h2 class="text-sm font-semibold text-slate-900">Saved Borrow #${record.id}</h2>
                            <p class="text-xs text-slate-500 mt-1">Details of the record you saved from Borrow Management.</p>
                        </div>
                        <div>
                            <span class="badge ${badgeClass}">
                                <span class="ml-1 text-xs">${statusLabel}</span>
                            </span>
                        </div>
                    </div>

                    <div class="overflow-x-auto border border-gray-200 rounded-lg">
                        <table class="min-w-full">
                            <thead>
                                <tr class="bg-gradient-to-r from-[#800020] to-[#5c0016] text-white">
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider">Borrower</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider">Item</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider">Details</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-100">
                                <tr>
                                    <td class="align-top px-6 py-4 text-xs text-slate-600">
                                        <h3 class="text-[11px] font-semibold text-slate-500 uppercase tracking-wide mb-1">Borrower</h3>
                                        <p class="text-sm font-medium text-slate-900 mb-0.5">${record.borrower_name || ''}</p>
                                        <p class="text-xs text-slate-500 mb-0.5">ID: ${record.borrower_id || 'N/A'}</p>
                                        <p class="text-xs text-slate-500 mb-0.5">Course: ${record.borrower_course || 'N/A'}</p>
                                        <p class="text-xs text-slate-500 mb-0.5">Year: ${record.borrower_year || 'N/A'}</p>
                                        <p class="text-xs text-slate-500">Department: ${record.borrower_department || 'N/A'}</p>
                                    </td>
                                    <td class="align-top px-6 py-4 text-xs text-slate-600">
                                        <h3 class="text-[11px] font-semibold text-slate-500 uppercase tracking-wide mb-1">Item</h3>
                                        <div class="flex items-center gap-3 mb-1.5">
                                            ${imageSrc
                                                ? `<img src="${imageSrc}" alt="${record.item_name || ''}" class="w-12 h-12 rounded-lg object-cover ring-2 ring-gray-100">`
                                                : `<div class=\"w-12 h-12 rounded-lg bg-gradient-to-br from-gray-100 to-gray-200 flex items-center justify-center ring-2 ring-gray-100\">
                                                        <svg xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 24 24\" fill=\"none\" stroke=\"currentColor\" stroke-width=\"2\" stroke-linecap=\"round\" stroke-linejoin=\"round\" class=\"w-6 h-6 text-gray-400\">
                                                            <rect x=\"3\" y=\"3\" width=\"18\" height=\"18\" rx=\"2\" ry=\"2\"></rect>
                                                        </svg>
                                                   </div>`}
                                            <div class="min-w-0">
                                                <p class="text-sm font-medium text-slate-900 truncate">${record.item_name || 'Unknown item'}</p>
                                                <p class="text-xs text-slate-500 truncate">${record.item_description || ''}</p>
                                            </div>
                                        </div>
                                        <p class="text-xs text-slate-500 mt-1">Category: ${record.category || 'N/A'}</p>
                                    </td>
                                    <td class="align-top px-6 py-4 text-xs text-slate-600">
                                        <h3 class="text-[11px] font-semibold text-slate-500 uppercase tracking-wide mb-1">Details</h3>
                                        <p class="text-xs text-slate-500 mb-0.5">Quantity: <span class="font-semibold text-slate-900">${record.quantity}</span></p>
                                        <p class="text-xs text-slate-500 mb-0.5">Deposit: <span class="font-semibold text-slate-900">₱${deposit}</span></p>
                                        <p class="text-xs text-slate-500 mb-0.5">SY / Semester: <span class="font-semibold text-slate-900">${record.school_year || 'N/A'} - ${record.semester || 'N/A'}</span></p>
                                        <p class="text-xs text-slate-500 mb-0.5">Due Date:
                                            <span class="font-semibold text-slate-900">${dueDate}</span>
                                        </p>
                                        ${record.return_status ? `
                                        <p class="text-xs text-slate-500 mb-0.5">Return Condition: 
                                            <span class="font-semibold text-slate-900">${record.return_status}</span>
                                        </p>
                                        ` : ''}
                                        <p class="text-xs text-slate-500 mb-0.5">Penalty: 
                                            <span class="font-semibold ${parseFloat(record.penalty || 0) > 0 ? 'text-red-600' : 'text-slate-900'}">
                                                ${parseFloat(record.penalty || 0) > 0 ? '₱' + parseFloat(record.penalty).toFixed(2) : 'none'}
                                            </span>
                                        </p>
                                        <p class="text-xs text-slate-500">Released By: <span class="font-semibold text-slate-900">${record.release_by || 'N/A'}</span></p>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            `;

            modal.classList.remove('hidden');
            modal.classList.add('flex');
            document.body.style.overflow = 'hidden';
        }

        function closeHistoryDetails() {
            const modal = document.getElementById('historyDetailsModal');
            if (!modal) return;
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            document.body.style.overflow = '';
        }

        function exportCurrentHistoryRecord() {
            // Re-render the details using the latest laptop date/time so that
            // overdue/status and any date-based fields are always up to date
            if (currentHistoryRecord) {
                viewHistoryDetails(currentHistoryRecord, null);
            }

            const content = document.getElementById('historyDetailsContent');
            if (!content) return;

            const clone = content.cloneNode(true);

            const date = new Date().toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });

            const printWindow = window.open('', '_blank', 'width=900,height=700');
            if (!printWindow) return;

            const styles = `
                <style>
                    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
                    
                    @page {
                        size: A4;
                        margin: 1.25cm;
                    }

                    html, body {
                        padding: 0;
                        margin: 0;
                        height: 100%;
                    }

                    body {
                        font-family: 'Inter', system-ui, -apple-system, sans-serif;
                        color: #1f2937;
                        line-height: 1.5;
                        background: #fff;
                        -webkit-print-color-adjust: exact;
                        print-color-adjust: exact;
                        font-size: 12px;
                    }

                    .print-wrapper {
                        max-width: 100%;
                        margin: 0 auto;
                        position: relative;
                        min-height: 100vh;
                    }

                    .print-header {
                        display: flex;
                        justify-content: space-between;
                        align-items: flex-end;
                        margin-bottom: 2rem;
                        padding-bottom: 1.5rem;
                        border-bottom: 2px solid #800020;
                    }

                    .header-left {
                        display: flex;
                        align-items: center;
                        gap: 1rem;
                    }

                    .header-logo {
                        width: 50px;
                        height: 50px;
                        object-fit: contain;
                    }

                    .header-text h1 {
                        font-size: 16px;
                        font-weight: 700;
                        text-transform: uppercase;
                        color: #111827;
                        margin: 0;
                        line-height: 1.2;
                        letter-spacing: -0.01em;
                    }

                    .header-text p {
                        font-size: 10px;
                        color: #6b7280;
                        text-transform: uppercase;
                        letter-spacing: 0.05em;
                        margin: 2px 0 0;
                        font-weight: 500;
                    }

                    .report-meta {
                        text-align: right;
                    }

                    .report-title {
                        font-size: 14px;
                        font-weight: 600;
                        color: #800020;
                        text-transform: uppercase;
                        letter-spacing: 0.05em;
                        margin-bottom: 4px;
                    }

                    .report-date {
                        font-size: 10px;
                        color: #9ca3af;
                        font-weight: 500;
                    }

                    table {
                        width: 100%;
                        border-collapse: separate;
                        border-spacing: 0;
                        margin-bottom: 1.5rem;
                        border: 1px solid #e5e7eb;
                        border-radius: 8px;
                        overflow: hidden;
                    }

                    thead {
                        background-color: #f9fafb;
                    }

                    th, td {
                        padding: 12px 16px;
                        text-align: left;
                        vertical-align: top;
                    }

                    th {
                        font-size: 10px;
                        text-transform: uppercase;
                        letter-spacing: 0.05em;
                        color: #6b7280;
                        font-weight: 600;
                        border-bottom: 1px solid #e5e7eb;
                        background: #f8fafc;
                    }

                    td {
                        border-bottom: 1px solid #f1f5f9;
                        font-size: 12px;
                        color: #374151;
                    }

                    tr:last-child td {
                        border-bottom: none;
                    }

                    h3 {
                        font-size: 10px;
                        text-transform: uppercase;
                        letter-spacing: 0.05em;
                        color: #9ca3af;
                        margin: 0 0 6px 0;
                        font-weight: 600;
                    }

                    p {
                        margin: 0 0 4px;
                    }

                    td img {
                        width: 48px !important;
                        height: 48px !important;
                        object-fit: cover !important;
                        border-radius: 6px !important;
                        border: 1px solid #e5e7eb !important;
                    }

                    .badge {
                        display: inline-flex;
                        align-items: center;
                        border-radius: 9999px;
                        padding: 2px 8px;
                        font-size: 10px;
                        font-weight: 600;
                        background: #f3f4f6 !important;
                        color: #374151 !important;
                        border: 1px solid #d1d5db;
                    }

                    .footer {
                        position: absolute;
                        bottom: 0;
                        left: 0;
                        right: 0;
                        text-align: center;
                        font-size: 9px;
                        color: #d1d5db;
                        padding-top: 1rem;
                        border-top: 1px solid #f3f4f6;
                    }
                </style>
            `;

            printWindow.document.write(`
                <html>
                    <head>
                        <title>Saved Borrow Record</title>
                        ${styles}
                    </head>
                    <body>
                        <div class="print-wrapper">
                            <div class="print-header">
                                <div class="header-left">
                                    <img src="../../frontend/images/spc.png" alt="Logo" class="header-logo" />
                                    <div class="header-text">
                                        <h1>St. Peter's College</h1>
                                        <p>Office of Student Affairs and Services</p>
                                    </div>
                                </div>
                                <div class="report-meta">
                                    <div class="report-title">Individual Record</div>
                                    <div class="report-date">Generated on ${date}</div>
                                </div>
                            </div>

                            ${clone.innerHTML}

                            <div class="footer">
                                OSAS-SIS Borrow Management System &bull; Confidential
                            </div>
                        </div>
                    </body>
                </html>
            `);

            printWindow.document.close();
            printWindow.focus();

            setTimeout(() => {
                printWindow.print();
            }, 500);
        }

        function exportAllHistory() {
            const table = document.getElementById('saved-history-list-table');
            if (!table) return;

            // Clone table to modify for print
            const clone = table.cloneNode(true);
            
            // Remove the 'Actions' column from header
            const headerRow = clone.querySelector('thead tr');
            if (headerRow && headerRow.lastElementChild) {
                headerRow.removeChild(headerRow.lastElementChild);
            }
            
            // Remove the 'Actions' column from all body rows
            const bodyRows = clone.querySelectorAll('tbody tr');
            bodyRows.forEach(row => {
                if (row.lastElementChild) {
                    row.removeChild(row.lastElementChild);
                }
            });

            // Get current date for the report
            const date = new Date().toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });

            const printWindow = window.open('', '_blank', 'width=1100,height=800');
            if (!printWindow) return;

            const styles = `
                <style>
                    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');

                    @page {
                        size: A4 landscape; /* Landscape for better table fit */
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
                    }

                    /* Brand Header */
                    .print-header {
                        display: flex;
                        justify-content: space-between;
                        align-items: flex-end;
                        margin-bottom: 1.5rem;
                        padding-bottom: 1rem;
                        border-bottom: 2px solid #800020; /* System Burgundy */
                    }

                    .header-left {
                        display: flex;
                        align-items: center;
                        gap: 0.75rem;
                    }

                    .header-logo {
                        width: 40px; /* Reduced from 45px */
                        height: 40px;
                        object-fit: contain;
                    }

                    .header-text h1 {
                        font-size: 14px; /* Reduced to balance */
                        font-weight: 800;
                        text-transform: uppercase;
                        color: #800020; /* System Burgundy */
                        margin: 0;
                        line-height: 1.1;
                    }

                    .header-text p {
                        font-size: 9px;
                        color: #4b5563;
                        margin: 2px 0 0;
                        font-weight: 500;
                        text-transform: uppercase;
                        letter-spacing: 0.05em;
                    }

                    .report-meta {
                        text-align: right;
                    }

                    .report-title {
                        font-size: 12px;
                        font-weight: 700;
                        color: #111827;
                        text-transform: uppercase;
                        letter-spacing: 0.05em;
                        margin-bottom: 2px;
                    }

                    .report-date {
                        font-size: 9px;
                        color: #6b7280;
                    }

                    /* Table Design */
                    table {
                        width: 100%;
                        border-collapse: collapse;
                        border-radius: 8px;
                        overflow: hidden;
                        border: 1px solid #e5e7eb;
                    }

                    thead {
                        display: table-header-group;
                    }

                    thead tr {
                        /* Gradient matching the system design */
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

                    /* Constrain status icons so they don't blow up in PDF */
                    td svg {
                        width: 14px !important;
                        height: 14px !important;
                    }

                    /* Enforce Image Size */
                    td img {
                        width: 32px !important;
                        height: 32px !important;
                        object-fit: cover !important;
                        border-radius: 4px !important;
                        border: 1px solid #e5e7eb !important;
                    }

                    /* Badges and formatting */
                    .badge {
                        display: inline-flex;
                        align-items: center;
                        border-radius: 9999px;
                        padding: 1px 6px;
                        font-size: 8px;
                        font-weight: 600;
                        gap: 3px;
                        background: #f3f4f6;
                        border: 1px solid #e5e7eb;
                        color: #374151;
                    }

                    /* Override badge colors for print to ensure contrast if backgrounds are stripped */
                    .badge svg {
                        width: 6px; /* Reduced from 8px */
                        height: 6px; /* Reduced from 8px */
                    }
                    
                    /* Specific text styles for readability */
                    .text-gray-900 { color: #111827 !important; font-weight: 600; }
                    .text-gray-500 { color: #6b7280 !important; }
                    
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
                    }
                </style>
            `;

            printWindow.document.write(`
                <html>
                    <head>
                        <title>History Report</title>
                        ${styles}
                    </head>
                    <body>
                        <div class="print-wrapper">
                            <div class="print-header">
                                <div class="header-left">
                                    <img src="../../frontend/images/spc.png" alt="Logo" class="header-logo" />
                                    <div class="header-text">
                                        <h1>St. Peter's College</h1>
                                        <p>Office of Student Affairs and Services • SIS</p>
                                    </div>
                                </div>
                                <div class="report-meta">
                                    <div class="report-title">History Report</div>
                                    <div class="report-date">Generated on ${date}</div>
                                </div>
                            </div>
                            
                            ${clone.outerHTML}

                            <div class="footer">
                                System Generated Report &bull; OSAS-SIS &bull; Page <span class="page-number"></span>
                            </div>
                        </div>
                    </body>
                </html>
            `);

            printWindow.document.close();
            printWindow.focus();

            setTimeout(() => {
                printWindow.print();
            }, 500);
        }

        async function deleteBorrow(id, event) {
            if (event) event.stopPropagation();
            const result = await Swal.fire({
                title: 'Delete Borrow Record?',
                text: 'This will soft delete the record',
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
                        await Swal.fire({
                            icon: 'success',
                            title: 'Deleted',
                            text: 'Borrow record has been deleted',
                            confirmButtonColor: '#800020'
                        });
                        window.location.href = 'history.php';
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: data.message,
                            confirmButtonColor: '#800020'
                        });
                    }
                } catch (error) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'An error occurred',
                        confirmButtonColor: '#800020'
                    });
                }
            }
        }
    </script>

</body>
</html>
