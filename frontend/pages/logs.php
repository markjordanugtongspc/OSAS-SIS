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

// Create inventory_logs table if it doesn't exist (with image column)
try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS inventory_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            item_id INT NULL,
            item_name VARCHAR(255) NOT NULL,
            action VARCHAR(50) NOT NULL,
            reason TEXT,
            old_quantity INT NULL,
            new_quantity INT NULL,
            image VARCHAR(255) NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
} catch (PDOException $e) {
    // Continue
}

// Ensure 'image' column exists (migration)
try {
    $pdo->query("SELECT image FROM inventory_logs LIMIT 1");
} catch (PDOException $e) {
    try {
        $pdo->exec("ALTER TABLE inventory_logs ADD COLUMN image VARCHAR(255) NULL");
    } catch (PDOException $ex) {}
}

// Fetch Inventory Logs
$inventoryLogs = [];
try {
    // Pagination for logs
    $limit = 10;
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    if ($page < 1) $page = 1;
    $offset = ($page - 1) * $limit;

    // Get total count
    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM inventory_logs WHERE user_id = :user_id");
    $countStmt->execute([':user_id' => $userId]);
    $total_logs = $countStmt->fetchColumn();
    $total_pages = ceil($total_logs / $limit);

    // Get logs
    $logStmt = $pdo->prepare("
        SELECT * FROM inventory_logs 
        WHERE user_id = :user_id 
        ORDER BY created_at DESC 
        LIMIT :limit OFFSET :offset
    ");
    $logStmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
    $logStmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $logStmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $logStmt->execute();
    $inventoryLogs = $logStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $inventoryLogs = [];
    $total_pages = 0;
}
?>
<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="../../frontend/images/spc.png">
    <title>Inventory Logs | OSAS SIS</title>
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
            <div class="px-8 py-6 flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-semibold text-slate-900 tracking-tight">Inventory Logs</h1>
                    <p class="mt-1 text-sm text-slate-600">Complete audit trail of inventory actions</p>
                </div>
                <div class="flex items-center gap-3">
                    <button type="button" onclick="exportLogs()" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-full border border-emerald-200 text-sm font-medium text-emerald-700 hover:bg-emerald-50 hover:border-emerald-300 transition-all cursor-pointer bg-white shadow-sm">
                        <span class="iconify w-4 h-4" data-icon="solar:printer-bold" data-inline="false"></span>
                        <span>Export All</span>
                    </button>                </div>
            </div>
        </div>

        <!-- Content Area -->
        <div class="px-8 py-8 space-y-6">

            <!-- Logs Table Container -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden animate-in fade-in slide-in-from-bottom-4 duration-700">
                <div class="overflow-x-auto">
                    <table id="logs-table" class="min-w-full divide-y divide-slate-200">
                        <thead>
                            <tr class="bg-gradient-to-r from-[#800020] to-[#5c0016] text-white">
                                <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider">Log ID</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider">Date & Time</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider">Activity</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider">Item Name</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider">Changes</th>
                                <th class="px-6 py-4 text-center text-xs font-semibold uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-slate-100">
                            <?php if (empty($inventoryLogs)): ?>
                                <tr>
                                    <td colspan="6" class="px-6 py-12 text-center">
                                        <div class="flex flex-col items-center gap-2">
                                            <span class="iconify w-12 h-12 text-slate-300" data-icon="solar:clipboard-list-broken"></span>
                                            <p class="text-slate-500 text-sm">No activity logs found yet.</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($inventoryLogs as $log): ?>
                                    <tr class="hover:bg-slate-50/80 transition-colors group">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-slate-900">#<?= $log['id'] ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-xs text-slate-600">
                                            <?= date('M d, Y h:i A', strtotime($log['created_at'])) ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-xs">
                                            <?php if ($log['action'] === 'Item Deleted'): ?>
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-50 text-red-700 border border-red-100">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-red-500 mr-1.5"></span>
                                                    Deletion
                                                </span>
                                            <?php else: ?>
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-orange-50 text-orange-700 border border-orange-100">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-orange-500 mr-1.5"></span>
                                                    Reduction
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="text-sm font-medium text-slate-900"><?= htmlspecialchars($log['item_name']) ?></span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-xs text-slate-600">
                                            <?php if ($log['action'] === 'Quantity Reduced'): ?>
                                                <div class="flex items-center gap-1.5 font-medium">
                                                    <span class="text-slate-400"><?= $log['old_quantity'] ?></span>
                                                    <span class="iconify text-slate-300" data-icon="solar:arrow-right-linear"></span>
                                                    <span class="text-[#800020]"><?= $log['new_quantity'] ?></span>
                                                </div>
                                            <?php else: ?>
                                                <span class="text-slate-500 italic">Qty was: <?= $log['old_quantity'] ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                                            <div class="flex items-center justify-center gap-2">
                                                <button type="button" onclick='viewLogDetails(<?= json_encode($log, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>)' class="p-2 text-indigo-600 hover:bg-indigo-50 rounded-xl transition-all tooltip cursor-pointer" title="View Details">
                                                    <span class="iconify w-5 h-5" data-icon="solar:eye-bold"></span>
                                                </button>
                                                <button type="button" onclick="deleteLog(<?= $log['id'] ?>)" class="p-2 text-red-600 hover:bg-red-50 rounded-xl transition-all tooltip cursor-pointer" title="Delete Log">
                                                    <span class="iconify w-5 h-5" data-icon="solar:trash-bin-minimalistic-bold"></span>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Footer/Pagination -->
                <div class="bg-slate-50/50 px-6 py-4 border-t border-slate-100">
                    <div class="flex items-center justify-between">
                        <div class="text-xs text-slate-500 font-medium">
                            Showing page <span class="text-slate-900 font-bold"><?= $page ?></span> of <span class="text-slate-900 font-bold"><?= $total_pages ?></span> 
                            (<?= $total_logs ?> records)
                        </div>
                        <?php if ($total_pages > 1): ?>
                            <nav class="flex items-center gap-1">
                                <?php if ($page > 1): ?>
                                    <a href="?page=<?= $page - 1 ?>" class="p-2 text-slate-600 hover:bg-white hover:shadow-sm rounded-lg border border-transparent hover:border-slate-200 transition-all">
                                        <span class="iconify w-4 h-4" data-icon="solar:alt-arrow-left-linear"></span>
                                    </a>
                                <?php endif; ?>

                                <?php for($i = 1; $i <= $total_pages; $i++): ?>
                                    <a href="?page=<?= $i ?>" class="w-8 h-8 flex items-center justify-center text-xs font-bold rounded-lg transition-all <?= $i === $page ? 'bg-[#800020] text-white shadow-lg' : 'text-slate-600 hover:bg-white hover:border-slate-200 border border-transparent' ?>">
                                        <?= $i ?>
                                    </a>
                                <?php endfor; ?>

                                <?php if ($page < $total_pages): ?>
                                    <a href="?page=<?= $page + 1 ?>" class="p-2 text-slate-600 hover:bg-white hover:shadow-sm rounded-lg border border-transparent hover:border-slate-200 transition-all">
                                        <span class="iconify w-4 h-4" data-icon="solar:alt-arrow-right-linear"></span>
                                    </a>
                                <?php endif; ?>
                            </nav>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- View Log Modal -->
    <div id="logDetailModal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm transition-opacity" onclick="closeLogModal()"></div>
        <div class="relative bg-white rounded-3xl shadow-2xl w-full max-w-2xl overflow-hidden animate-in zoom-in duration-300">
            <!-- Modal Header -->
            <div class="px-8 py-6 bg-gradient-to-r from-[#800020] to-[#5c0016] text-white flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-bold flex items-center gap-2">
                        <span class="iconify" data-icon="solar:document-text-bold"></span>
                        Log Details
                    </h3>
                    <p class="text-xs text-white/70">Complete history entry information</p>
                </div>
                <div class="flex items-center gap-2">
                    <button onclick="exportLogDetails()" class="p-1.5 hover:bg-white/20 rounded-lg transition-colors cursor-pointer" title="Print Log Details">
                        <span class="iconify w-6 h-6" data-icon="solar:printer-bold"></span>
                    </button>
                    <button onclick="closeLogModal()" class="p-1.5 hover:bg-white/20 rounded-lg transition-colors cursor-pointer">
                        <span class="iconify w-6 h-6" data-icon="solar:close-circle-bold"></span>
                    </button>
                </div>
            </div>

            <!-- Modal Body -->
            <div class="p-8">
                <div class="flex flex-col md:flex-row gap-8">
                    <!-- Left: Item Image -->
                    <div class="w-full md:w-1/3">
                        <div id="modalItemImage" class="aspect-square rounded-2xl bg-slate-100 flex items-center justify-center overflow-hidden border-2 border-slate-100 shadow-inner">
                            <!-- Image injected here -->
                        </div>
                    </div>
                    
                    <!-- Right: Details -->
                    <div class="flex-1 space-y-6">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <h4 class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Activity Type</h4>
                                <div id="modalAction" class="text-sm font-bold text-slate-900 uppercase tracking-tight"></div>
                            </div>
                            <div>
                                <h4 class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Date Logged</h4>
                                <div id="modalDate" class="text-sm font-medium text-slate-600"></div>
                            </div>
                        </div>

                        <div>
                            <h4 class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Item Name</h4>
                            <div id="modalItemName" class="text-base font-bold text-[#800020] leading-tight"></div>
                        </div>

                        <div class="bg-slate-50 rounded-2xl p-4 border border-slate-100">
                            <h4 class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-2">Quantity Changes</h4>
                            <div id="modalChanges" class="flex items-center gap-3 text-lg font-black text-slate-900">
                                <!-- Changes injected here -->
                            </div>
                        </div>

                        <div>
                            <h4 class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Reason for Action</h4>
                            <div id="modalReason" class="text-sm text-slate-600 italic leading-relaxed bg-slate-50/50 p-4 rounded-xl border border-dashed border-slate-200">
                                <!-- Reason injected here -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="px-8 py-5 bg-slate-50 border-t border-slate-100 flex justify-end">
                <button onclick="exportLogDetails()" class="px-6 py-2.5 bg-emerald-50 text-emerald-700 rounded-xl font-bold hover:bg-emerald-100 transition-all cursor-pointer mr-auto border border-emerald-200">
                    Print Details
                </button>
                <button onclick="closeLogModal()" class="px-6 py-2.5 bg-slate-200 text-slate-700 rounded-xl font-bold hover:bg-slate-300 transition-all cursor-pointer">
                    Close
                </button>
            </div>
        </div>
    </div>

    <script>
        window.viewLogDetails = function(log) {
            window.currentLogData = log; // Store globally for printer
            const modal = document.getElementById('logDetailModal');
            
            // Populate Details
            document.getElementById('modalAction').textContent = log.action;
            document.getElementById('modalDate').textContent = new Date(log.created_at).toLocaleString();
            document.getElementById('modalItemName').textContent = log.item_name;
            document.getElementById('modalReason').textContent = '"' + log.reason + '"';
            
            // Populate Changes
            const changesDiv = document.getElementById('modalChanges');
            if (log.action === 'Quantity Reduced') {
                changesDiv.innerHTML = `
                    <span class="text-slate-400 line-through">${log.old_quantity}</span>
                    <span class="iconify text-[#800020] w-6 h-6" data-icon="solar:alt-arrow-right-bold"></span>
                    <span class="text-[#800020]">${log.new_quantity}</span>
                    <span class="text-xs font-bold text-red-500 ml-auto">-${log.old_quantity - log.new_quantity} Units</span>
                `;
            } else {
                changesDiv.innerHTML = `
                    <span class="text-slate-400">Previous Stock: ${log.old_quantity}</span>
                    <span class="text-xs font-bold text-red-500 ml-auto uppercase tracking-tighter">Inventory Removed</span>
                `;
            }

            // Populate Image
            const imgDiv = document.getElementById('modalItemImage');
            if (log.image) {
                imgDiv.innerHTML = `<img src="../images/items/${log.image}" class="w-full h-full object-cover">`;
            } else {
                imgDiv.innerHTML = `<span class="iconify w-12 h-12 text-slate-300" data-icon="solar:camera-minimalistic-broken"></span>`;
            }

            modal.classList.remove('hidden');
        };

        window.closeLogModal = function() {
            document.getElementById('logDetailModal').classList.add('hidden');
        };

        window.exportLogs = function() {
            const table = document.getElementById('logs-table');
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
                        align-items: center; /* Center */
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
                        border-radius: 8px;
                        overflow: hidden;
                        border: 1px solid #e5e7eb;
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

                    /* Constrain status icons so they don't blow up in PDF */
                    td svg {
                        width: 14px !important;
                        height: 14px !important;
                    }

                    /* Badges */
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
                        <title>Inventory Logs Report</title>
                        ${styles}
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
                                <div class="report-title">Inventory Logs Audit</div>
                                <div class="report-date">Generated on ${date}</div>
                            </div>
                            
                            <div style="width: 100%;">
                                ${clone.outerHTML}
                            </div>

                            <div class="footer">
                                System Generated Report &bull; OSAS-SIS &bull; Inventory Audit
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
        };

        window.exportLogDetails = function() {
            if (!window.currentLogData) return;
            const log = window.currentLogData;
            
            const date = new Date().toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
            
            // Generate changes HTML based on log type
            let changesHtml = '';
            if (log.action === 'Quantity Reduced') {
                const diff = parseInt(log.old_quantity) - parseInt(log.new_quantity);
                changesHtml = `
                    <div class="quantity-change-box">
                        <div class="qt-row">
                            <span class="qt-label">Previous:</span>
                            <span class="qt-val line-through">${log.old_quantity}</span>
                        </div>
                        <div class="qt-arrow">↓</div>
                        <div class="qt-row">
                            <span class="qt-label">New Stock:</span>
                            <span class="qt-val highlight">${log.new_quantity}</span>
                        </div>
                        <div class="qt-diff">
                            -${diff} Units
                        </div>
                    </div>
                `;
            } else {
                changesHtml = `
                    <div class="quantity-change-box">
                        <div class="qt-row">
                            <span class="qt-label">Previous Stock:</span>
                            <span class="qt-val">${log.old_quantity}</span>
                        </div>
                        <div class="qt-diff full-width">
                            INVENTORY REMOVED
                        </div>
                    </div>
                `;
            }

            // Image HTML
            let imageHtml = '';
            if (log.image) {
                imageHtml = `<img src="../images/items/${log.image}" class="item-image" />`;
            } else {
                imageHtml = `<div class="no-image">No Image Available</div>`;
            }

            const printWindow = window.open('', '_blank', 'width=1100,height=800');
            if (!printWindow) return;

            const styles = `
                <style>
                    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');
                    @import url('https://fonts.googleapis.com/css2?family=UnifrakturMaguntia&display=swap');

                    @page {
                        size: A4 portrait;
                        margin: 1cm;
                    }

                    body {
                        font-family: 'Inter', sans-serif;
                        color: #1f2937;
                        line-height: 1.5;
                        padding: 0;
                        margin: 0;
                        -webkit-print-color-adjust: exact;
                        print-color-adjust: exact;
                    }

                    .container {
                        max-width: 800px;
                        margin: 0 auto;
                        border: 1px solid #e5e7eb;
                        border-radius: 12px;
                        overflow: hidden;
                    }

                    .old-english {
                        font-family: 'UnifrakturMaguntia', "Old English Text MT", "Engravers Old English BT", "Goudy Text MT", serif;
                        font-weight: 400;
                    }

                    /* Header */
                    .print-header {
                        text-align: center;
                        padding-bottom: 2rem;
                        border-bottom: 2px solid #800020;
                        margin-bottom: 2rem;
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

                    .meta {
                        text-align: center;
                        margin-bottom: 2rem;
                    }

                    .meta-title {
                        font-size: 14px;
                        font-weight: 700;
                        color: #111827;
                        text-transform: uppercase;
                        letter-spacing: 0.05em;
                        margin-bottom: 4px;
                    }

                    .meta-date {
                        font-size: 11px;
                        color: #6b7280;
                    }

                    /* Content */
                    .content {
                        padding: 2.5rem;
                        background: #fff;
                    }

                    .log-id {
                        text-align: center;
                        margin-bottom: 2rem;
                    }
                    
                    .log-id span {
                        background: #f3f4f6;
                        padding: 0.5rem 1rem;
                        border-radius: 9999px;
                        font-family: monospace;
                        font-size: 14px;
                        font-weight: 600;
                        color: #4b5563;
                        border: 1px solid #e5e7eb;
                    }

                    .main-grid {
                        display: grid;
                        grid-template-columns: 200px 1fr;
                        gap: 3rem;
                        margin-bottom: 3rem;
                    }

                    .image-container {
                        width: 200px;
                        height: 200px;
                        background: #f9fafb;
                        border: 1px solid #e5e7eb;
                        border-radius: 12px;
                        overflow: hidden;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                    }

                    .item-image {
                        width: 100%;
                        height: 100%;
                        object-fit: cover;
                    }

                    .no-image {
                        font-size: 10px;
                        color: #9ca3af;
                        text-transform: uppercase;
                    }

                    .details-list {
                        display: flex;
                        flex-direction: column;
                        gap: 1.5rem;
                    }

                    .field-group {
                        
                    }

                    .label {
                        font-size: 10px;
                        font-weight: 700;
                        color: #9ca3af;
                        text-transform: uppercase;
                        letter-spacing: 0.1em;
                        margin-bottom: 0.25rem;
                    }

                    .value {
                        font-size: 16px;
                        font-weight: 600;
                        color: #1f2937;
                    }

                    .value.highlight {
                        color: #800020;
                        font-size: 20px;
                        font-weight: 800;
                    }

                    /* Quantity Box */
                    .quantity-box {
                        background: #f9fafb;
                        border: 1px solid #e5e7eb;
                        border-radius: 12px;
                        padding: 1.5rem;
                        margin-top: 1rem;
                    }

                    .quantity-change-box {
                        display: flex;
                        align-items: center;
                        background: #f9fafb;
                        border: 1px solid #e5e7eb;
                        border-radius: 8px;
                        padding: 0.75rem 1rem;
                        gap: 1rem;
                    }

                    .qt-row {
                        display: flex;
                        flex-direction: column;
                        align-items: center;
                    }

                    .qt-label {
                        font-size: 9px;
                        color: #6b7280;
                        text-transform: uppercase;
                        font-weight: 600;
                    }

                    .qt-val {
                        font-size: 16px;
                        font-weight: 700;
                        color: #374151;
                    }

                    .qt-val.highlight {
                        color: #800020;
                    }

                    .qt-val.line-through {
                        text-decoration: line-through;
                        color: #9ca3af;
                    }

                    .qt-arrow {
                        font-size: 18px;
                        color: #d1d5db;
                    }

                    .qt-diff {
                        margin-left: auto;
                        background: #fee2e2;
                        color: #991b1b;
                        font-size: 11px;
                        font-weight: 700;
                        padding: 2px 8px;
                        border-radius: 4px;
                    }

                    .qt-diff.full-width {
                        margin-left: 0;
                        flex: 1;
                        text-align: center;
                    }

                    /* Reason Box */
                    .reason-box {
                        background: #f8fafc;
                        border: 1px dashed #cbd5e1;
                        padding: 1.5rem;
                        border-radius: 8px;
                        font-size: 14px;
                        color: #475569;
                        line-height: 1.6;
                        font-style: italic;
                    }

                    .footer {
                        text-align: center;
                        font-size: 10px;
                        color: #9ca3af;
                        padding-top: 2rem;
                        border-top: 1px solid #f3f4f6;
                        margin-top: 2rem;
                    }
                </style>
            `;

            printWindow.document.write(`
                <html>
                    <head>
                        <title>Log Details #${log.id}</title>
                        ${styles}
                    </head>
                    <body>
                        <div class="print-wrapper">
                            <div class="container">
                                <div class="content">
                                    <div class="print-header">
                                        <img src="/OSAS-SIS/frontend/images/spc.png" alt="SPC Logo" class="header-logo">
                                        <div class="header-text">
                                            <h1>St. Peter's College</h1>
                                            <p>Office of Student Affairs and Services • SIS</p>
                                        </div>
                                    </div>

                                    <div class="meta">
                                        <div class="meta-title">Log Details Report</div>
                                        <div class="meta-date">Generated on ${date}</div>
                                    </div>
                                    
                                    <div class="log-id">
                                        <span>Log #${log.id}</span>
                                    </div>

                                    <div class="main-grid">
                                        <div class="image-container">
                                            ${imageHtml}
                                        </div>

                                        <div class="details-list">
                                            <div class="field-group">
                                                <div class="label">Date & Time Logged</div>
                                                <div class="value">${new Date(log.created_at).toLocaleString()}</div>
                                            </div>

                                            <div class="field-group">
                                                <div class="label">Activity Type</div>
                                                <div class="value" style="color: #800020; text-transform: uppercase;">${log.action}</div>
                                            </div>

                                            <div class="field-group">
                                                <div class="label">Item Name</div>
                                                <div class="value" style="font-size: 18px;">${log.item_name}</div>
                                            </div>

                                            <div class="field-group">
                                                <div class="label">Inventory Changes</div>
                                                ${changesHtml}
                                            </div>
                                        </div>
                                    </div>

                                    <div class="field-group">
                                        <div class="label">Reason for Action</div>
                                        <div class="reason-box">
                                            "${log.reason}"
                                        </div>
                                    </div>

                                    <div class="footer">
                                        System Generated Record • OSAS-SIS Inventory Audit Trail
                                    </div>
                                </div>
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
        };

        window.deleteLog = function(id) {
            Swal.fire({
                title: 'Delete Log?',
                text: "This log entry will be removed permanently from your audit trail.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#800020',
                cancelButtonColor: '#adb5bd',
                confirmButtonText: 'Yes, delete it',
                customClass: {
                    container: 'p-0',
                    popup: 'rounded-3xl p-4',
                    confirmButton: 'rounded-xl font-bold px-6 py-3',
                    cancelButton: 'rounded-xl font-bold px-6 py-3',
                    title: 'text-slate-900 font-bold',
                    htmlContainer: 'text-slate-600'
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch('../../backend/items/delete_log.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: 'log_id=' + id
                    })
                    .then(r => r.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire({
                                title: 'Deleted!',
                                text: 'Log entry removed.',
                                icon: 'success',
                                showConfirmButton: false,
                                timer: 1500,
                                customClass: { popup: 'rounded-3xl' }
                            }).then(() => location.reload());
                        } else {
                            Swal.fire('Error', data.message, 'error');
                        }
                    });
                }
            });
        };
    </script>

</body>
</html>
