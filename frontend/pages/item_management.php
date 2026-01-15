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

// Pagination Configuration
$limit = 5;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

// Fetch items with pagination
try {
    // Get total count for pagination
    $countStmt = $pdo->query("SELECT COUNT(*) FROM items");
    $total_items_count = $countStmt->fetchColumn();
    $total_pages = ceil($total_items_count / $limit);

    // Get paginated items
    $stmt = $pdo->prepare("SELECT * FROM items ORDER BY id DESC LIMIT :limit OFFSET :offset");
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $items = $stmt->fetchAll();
} catch (PDOException $e) {
    $items = [];
    $total_pages = 0;
}
?>
<!DOCTYPE html>
<html lang="en" class="h-full bg-gray-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Item Management | OSAS SIS</title>
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
    <div class="ml-64 min-h-screen">
        
        <!-- Page Header -->
        <div class="bg-white border-b border-gray-200 sticky top-0 z-20">
            <div class="px-8 py-6 flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Item Management</h1>
                    <p class="mt-1 text-sm text-gray-600">Manage your inventory items and stock</p>
                </div>
                <button onclick="openAddItemModal()" class="px-4 py-2 bg-[#800020] text-white rounded-md hover:bg-[#5c0016] transition-colors flex items-center gap-2 cursor-pointer">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    Add New Item
                </button>
            </div>
        </div>

        <!-- Content Area -->
        <div class="px-8 py-8">
            
            <!-- Search and Filter Bar -->
            <div class="flex gap-3 items-center mb-4">
                <!-- Search Box -->
                <div class="flex-1 relative">
                    <input 
                        type="text" 
                        id="searchInput" 
                        placeholder="Search by item name, ID, brand, or category..."
                        class="w-full pl-10 pr-4 py-1.5 border border-[#800020] rounded-lg focus:outline-none focus:ring-2 focus:ring-[#800020] focus:border-transparent transition-all text-sm"
                    >
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                    </svg>
                </div>
                
                <!-- Sport Filter Dropdown -->
                <div class="w-56">
                    <select 
                        id="sportFilter" 
                        class="w-full px-4 py-1.5 border border-[#800020] rounded-lg focus:outline-none focus:ring-2 focus:ring-[#800020] focus:border-transparent transition-all text-sm"
                    >
                        <option value="">All Sports</option>
                        <option value="Basketball">🏀 Basketball</option>
                        <option value="Volleyball">🏐 Volleyball</option>
                        <option value="Badminton">🏸 Badminton</option>
                        <option value="Table Tennis">🏓 Table Tennis</option>
                        <option value="Tennis">🎾 Tennis</option>
                        <option value="Football">⚽ Football</option>
                        <option value="Baseball">⚾ Baseball</option>
                        <option value="Swimming">🏊 Swimming</option>
                        <option value="Athletics">🏃 Athletics</option>
                        <option value="Others">🏅 Others</option>
                    </select>
                </div>
            </div>
            
            <!-- Items Table -->
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden shadow-sm">
                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead>
                            <tr class="bg-gradient-to-r from-[#800020] to-[#5c0016] text-white">
                                <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider">ID</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider">Image</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider">Item Details</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider">Sport</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider">Category</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider">Stock</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider">Price</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider">Status</th>
                                <th class="px-6 py-4 text-center text-xs font-semibold uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-100">
                            <?php if (empty($items)): ?>
                                <tr>
                                    <td colspan="9" class="px-6 py-16 text-center">
                                        <div class="flex flex-col items-center justify-center">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor" class="w-16 h-16 text-gray-300 mb-4">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" />
                                            </svg>
                                            <p class="text-gray-500 text-sm font-medium">No items found</p>
                                            <p class="text-gray-400 text-xs mt-1">Click "Add New Item" to get started</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($items as $item): ?>
                                    <tr class="hover:bg-gray-50/50 transition-colors duration-150">
                                        <td class="px-6 py-4">
                                            <span class="text-xs font-mono text-gray-600"><?= htmlspecialchars($item['unique_id']) ?></span>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="relative group">
                                                <?php 
                                                // Check if item is new (created within 24 hours)
                                                $is_new = false;
                                                if (isset($item['created_at']) && $item['created_at']) {
                                                    $created_time = strtotime($item['created_at']);
                                                    $current_time = time();
                                                    $time_diff = $current_time - $created_time;
                                                    $is_new = $time_diff < 86400; // 86400 seconds = 24 hours
                                                }
                                                ?>
                                                
                                                <?php if ($item['image']): ?>
                                                    <div class="relative w-14 h-14">
                                                        <img src="../../frontend/images/items/<?= htmlspecialchars($item['image']) ?>" alt="<?= htmlspecialchars($item['item_name']) ?>" class="w-full h-full rounded-lg object-cover ring-2 ring-gray-100 group-hover:ring-[#800020]/20 transition-all">
                                                        <?php if ($is_new): ?>
                                                            <div class="absolute top-0 right-0 bg-green-600 text-white text-[8px] font-semibold px-1.5 py-0.5 rounded-br-md rounded-tl-lg">
                                                                NEW
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php else: ?>
                                                    <div class="relative w-14 h-14 rounded-lg bg-gradient-to-br from-gray-100 to-gray-200 flex items-center justify-center ring-2 ring-gray-100">
                                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6 text-gray-400">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                                                        </svg>
                                                        <?php if ($is_new): ?>
                                                            <div class="absolute top-0 right-0 bg-green-600 text-white text-[8px] font-semibold px-1.5 py-0.5 rounded-br-md rounded-tl-lg">
                                                                NEW
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="flex flex-col gap-1">
                                                <p class="text-sm font-semibold text-gray-900"><?= htmlspecialchars($item['item_name']) ?></p>
                                                <div class="flex items-center gap-2">
                                                    <?php if ($item['color']): ?>
                                                        <span class="text-xs text-gray-500"><?= htmlspecialchars($item['color']) ?></span>
                                                    <?php endif; ?>
                                                    <?php if ($item['color'] && $item['size']): ?>
                                                        <span class="text-gray-300">•</span>
                                                    <?php endif; ?>
                                                    <?php if ($item['size']): ?>
                                                        <span class="text-xs text-gray-500"><?= htmlspecialchars($item['size']) ?></span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <?php if ($item['sport']): ?>
                                                <?php
                                                // Color coding and emojis for sports
                                                $sport = $item['sport'];
                                                $sportData = [
                                                    'Basketball' => ['emoji' => '🏀', 'color' => 'bg-orange-100 text-orange-700 ring-orange-600/20'],
                                                    'Volleyball' => ['emoji' => '🏐', 'color' => 'bg-blue-100 text-blue-700 ring-blue-600/20'],
                                                    'Badminton' => ['emoji' => '🏸', 'color' => 'bg-green-100 text-green-700 ring-green-600/20'],
                                                    'Table Tennis' => ['emoji' => '🏓', 'color' => 'bg-red-100 text-red-700 ring-red-600/20'],
                                                    'Tennis' => ['emoji' => '🎾', 'color' => 'bg-yellow-100 text-yellow-700 ring-yellow-600/20'],
                                                    'Football' => ['emoji' => '⚽', 'color' => 'bg-emerald-100 text-emerald-700 ring-emerald-600/20'],
                                                    'Baseball' => ['emoji' => '⚾', 'color' => 'bg-indigo-100 text-indigo-700 ring-indigo-600/20'],
                                                    'Swimming' => ['emoji' => '🏊', 'color' => 'bg-cyan-100 text-cyan-700 ring-cyan-600/20'],
                                                    'Athletics' => ['emoji' => '🏃', 'color' => 'bg-purple-100 text-purple-700 ring-purple-600/20'],
                                                    'Others' => ['emoji' => '🏅', 'color' => 'bg-gray-100 text-gray-700 ring-gray-600/20']
                                                ];
                                                $sportInfo = $sportData[$sport] ?? ['emoji' => '🏅', 'color' => 'bg-gray-100 text-gray-700 ring-gray-600/20'];
                                                ?>
                                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-medium ring-1 <?= $sportInfo['color'] ?>">
                                                    <span><?= $sportInfo['emoji'] ?></span>
                                                    <?= htmlspecialchars($sport) ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="text-xs text-gray-400">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span class="text-sm text-gray-700"><?= htmlspecialchars($item['category'] ?? 'Uncategorized') ?></span>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span class="text-sm font-bold <?= $item['quantity'] == 0 ? 'text-red-600' : 'text-gray-900' ?>"><?= htmlspecialchars($item['quantity']) ?></span>
                                            <span class="text-xs <?= $item['quantity'] == 0 ? 'text-red-500' : 'text-gray-500' ?> ml-1">pcs</span>
                                        </td>
                                        <td class="px-6 py-4">
                                            <span class="text-sm font-semibold text-gray-900">₱<?= number_format($item['price'], 2) ?></span>
                                        </td>
                                        <td class="px-6 py-4">
                                            <?php
                                                $statusClass = '';
                                                $statusIcon = '';
                                                if ($item['status'] === 'Available') {
                                                    $statusClass = 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-600/20';
                                                    $statusIcon = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-3.5 h-3.5"><polyline points="20 6 9 17 4 12"></polyline></svg>';
                                                } elseif ($item['status'] === 'Unavailable') {
                                                    $statusClass = 'bg-orange-50 text-orange-700 ring-1 ring-orange-600/20';
                                                    $statusIcon = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-3.5 h-3.5"><circle cx="12" cy="12" r="10"></circle><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"></line></svg>';
                                                } else { // Damaged
                                                    $statusClass = 'bg-red-50 text-red-700 ring-1 ring-red-600/20';
                                                    $statusIcon = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-3.5 h-3.5"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>';
                                                }
                                            ?>
                                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-medium <?= $statusClass ?>">
                                                <?= $statusIcon ?>
                                                <?= htmlspecialchars($item['status']) ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="flex items-center justify-center">
                                                <!-- View Button -->
                                                <button onclick='viewItem(<?= htmlspecialchars(json_encode($item)) ?>)' class="p-1.5 text-blue-500 hover:bg-blue-50 hover:text-blue-700 rounded-lg transition-all duration-200 group cursor-pointer" title="View Details">
                                                    <span class="iconify w-4.5 h-4.5" data-icon="solar:eye-bold" data-inline="false"></span>
                                                </button>
                                                
                                                <!-- Edit Button -->
                                                <button onclick="openEditModal(<?= htmlspecialchars(json_encode($item)) ?>)" class="p-1.5 text-amber-500 hover:bg-amber-50 hover:text-amber-700 rounded-lg transition-all duration-200 group cursor-pointer" title="Edit Item">
                                                    <span class="iconify w-4.5 h-4.5" data-icon="solar:pen-bold" data-inline="false"></span>
                                                </button>
                                                
                                                <!-- Delete Button -->
                                                <button onclick="deleteItem(<?= $item['id'] ?>)" class="p-1.5 text-red-500 hover:bg-red-50 hover:text-red-700 rounded-lg transition-all duration-200 group cursor-pointer" title="Delete Item">
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
                <div class="mt-6 flex items-center justify-between bg-white px-4 py-3 sm:px-6 rounded-xl border border-gray-200 shadow-sm">
                    <div class="flex flex-1 justify-between sm:hidden">
                        <?php if ($page > 1): ?>
                            <a href="?page=<?= $page - 1 ?>" class="relative inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Previous</a>
                        <?php endif; ?>
                        <?php if ($page < $total_pages): ?>
                            <a href="?page=<?= $page + 1 ?>" class="relative ml-3 inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Next</a>
                        <?php endif; ?>
                    </div>
                    <div class="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between">
                        <div>
                            <p class="text-sm text-gray-700">
                                Showing
                                <span class="font-medium"><?= $offset + 1 ?></span>
                                to
                                <span class="font-medium"><?= min($offset + $limit, $total_items_count) ?></span>
                                of
                                <span class="font-medium"><?= $total_items_count ?></span>
                                results
                            </p>
                        </div>
                        <div>
                            <nav class="isolate inline-flex -space-x-px rounded-md shadow-sm" aria-label="Pagination">
                                <!-- Previous Page -->
                                <a href="?page=<?= max(1, $page - 1) ?>" class="relative inline-flex items-center rounded-l-md px-2 py-2 text-gray-400 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:z-20 focus:outline-offset-0 <?= $page <= 1 ? 'pointer-events-none opacity-50' : '' ?>">
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
                                    <a href="?page=<?= $i ?>" class="relative inline-flex items-center px-4 py-2 text-sm font-semibold <?= $i === $page ? 'z-10 bg-[#800020] text-white focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#800020]' : 'text-gray-900 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:z-20 focus:outline-offset-0' ?>">
                                        <?= $i ?>
                                    </a>
                                <?php endfor; ?>

                                <!-- Next Page -->
                                <a href="?page=<?= min($total_pages, $page + 1) ?>" class="relative inline-flex items-center rounded-r-md px-2 py-2 text-gray-400 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:z-20 focus:outline-offset-0 <?= $page >= $total_pages ? 'pointer-events-none opacity-50' : '' ?>">
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
    <!-- End of Content Area, but kept Main Container open for Modals & Scripts -->

    <!-- View Item Modal -->
    <div id="viewItemModal" class="hidden fixed inset-0 z-50 overflow-y-auto">
        <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-md transition-opacity" onclick="closeViewModal()"></div>
        
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="relative bg-white rounded-2xl shadow-2xl transform transition-all w-full max-w-5xl max-h-[92vh] overflow-hidden flex flex-col">
                <!-- Close Button -->
                <button onclick="closeViewModal()" class="absolute top-3 right-3 z-10 p-2 bg-white/90 hover:bg-white rounded-full shadow-lg transition-colors cursor-pointer">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-4 h-4 text-gray-600">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                    </svg>
                </button>
                
                <!-- Main Content Grid -->
                <div class="grid grid-cols-7 h-full overflow-hidden">
                    <!-- Left Side - Large Product Image (4 columns) -->
                    <div class="col-span-4 relative bg-gradient-to-br from-gray-50 to-gray-100 flex items-center justify-center p-8">
                        <img id="view_image" src="" alt="Product Image" class="w-full h-full object-contain drop-shadow-2xl max-h-[80vh]">
                        <!-- NEW Badge -->
                        <div id="view_new_badge" class="hidden absolute top-4 left-4 bg-green-600 text-white text-xs font-semibold px-3 py-1.5 rounded-lg shadow-lg">
                            NEW
                        </div>
                    </div>
                    
                    <!-- Right Side - Product Details (3 columns) -->
                    <div class="col-span-3 flex flex-col bg-white">
                        <!-- Scrollable Content Area -->
                        <div class="pt-12 px-5 pb-5 overflow-y-auto flex-1 custom-scrollbar">
                            <!-- Title & Status -->
                            <div class="mb-4">
                                <div class="flex items-start justify-between gap-2 mb-2">
                                    <h2 id="view_item_name" class="text-xl font-bold text-gray-900 leading-tight"></h2>
                                    <span id="view_status_badge" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium whitespace-nowrap flex-shrink-0">
                                        <!-- Icon and text will be populated by JavaScript -->
                                    </span>
                                </div>
                                <p id="view_unique_id" class="text-xs text-gray-500 font-mono"></p>
                            </div>
                            
                            <!-- Price & Stock Cards -->
                            <div class="grid grid-cols-2 gap-3 mb-4">
                                <div class="bg-gradient-to-br from-[#800020] to-[#5c0016] rounded-lg p-3 text-white">
                                    <p class="text-[10px] opacity-90 mb-1 uppercase tracking-wide font-medium">PRICE</p>
                                    <p id="view_price" class="text-lg font-bold"></p>
                                </div>
                                <div class="bg-gray-50 rounded-lg p-3 border border-gray-200">
                                    <p class="text-[10px] text-gray-600 mb-1 uppercase tracking-wide font-medium">IN STOCK</p>
                                    <p id="view_quantity" class="text-lg font-bold text-gray-900"></p>
                                </div>
                            </div>
                            
                            <!-- Details Grid -->
                            <div class="space-y-3 mb-4 pb-4 border-b border-gray-200">
                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <p class="text-[10px] font-semibold text-gray-500 mb-1 uppercase tracking-wide">CATEGORY</p>
                                        <p id="view_category" class="text-sm font-semibold text-gray-900"></p>
                                    </div>
                                    <div>
                                        <p class="text-[10px] font-semibold text-gray-500 mb-1 uppercase tracking-wide">SPORT</p>
                                        <p id="view_sport" class="text-sm font-semibold text-gray-900"></p>
                                    </div>
                                </div>
                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <p class="text-[10px] font-semibold text-gray-500 mb-1 uppercase tracking-wide">BRAND</p>
                                        <p id="view_brand" class="text-sm font-semibold text-gray-900"></p>
                                    </div>
                                    <div>
                                        <p class="text-[10px] font-semibold text-gray-500 mb-1 uppercase tracking-wide">COLOR</p>
                                        <p id="view_color" class="text-sm font-semibold text-gray-900"></p>
                                    </div>
                                </div>
                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <p class="text-[10px] font-semibold text-gray-500 mb-1 uppercase tracking-wide">SIZE</p>
                                        <p id="view_size" class="text-sm font-semibold text-gray-900"></p>
                                    </div>
                                    <div>
                                        <p class="text-[10px] font-semibold text-gray-500 mb-1 uppercase tracking-wide">SEMESTER</p>
                                        <p id="view_semester" class="text-sm font-semibold text-gray-900"></p>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Description -->
                            <div class="mb-3">
                                <p class="text-[10px] font-semibold text-gray-500 mb-2 uppercase tracking-wide">DESCRIPTION</p>
                                <p id="view_description" class="text-sm text-gray-700 leading-relaxed"></p>
                            </div>
                        </div>
                        
                        <!-- Action Buttons (Sticky Bottom) -->
                        <div class="p-4 bg-gray-50 border-t border-gray-200 flex-shrink-0">
                            <div class="flex gap-2">
                                <button onclick="closeViewAndEdit()" class="flex-1 px-4 py-2 bg-[#800020] text-white rounded-lg hover:bg-[#5c0016] transition-all font-medium text-sm shadow-md hover:shadow-lg cursor-pointer">
                                    Edit Item
                                </button>
                                <button onclick="closeViewModal()" class="px-4 py-2 bg-white text-gray-700 rounded-lg hover:bg-gray-100 transition-all font-medium text-sm border border-gray-300 cursor-pointer">
                                    Close
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Item Modal -->
    <div id="addItemModal" class="hidden fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <!-- Background overlay with blur -->
        <div class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm transition-opacity" onclick="closeAddItemModal()"></div>
        
        <!-- Modal container -->
        <div class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex min-h-full items-center justify-center p-4">
                
                <!-- Modal content -->
                <div class="relative bg-white rounded-lg shadow-xl transform transition-all w-full max-w-2xl">
                    <form id="addItemForm" enctype="multipart/form-data">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h3 class="text-lg font-semibold text-gray-900">Add New Item</h3>
                        </div>
                        
                        <div class="px-6 py-4 max-h-[70vh] overflow-y-auto">
                            <div class="grid grid-cols-2 gap-4">
                                
                                <!-- Item Name -->
                                <div class="col-span-2">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Item Name *</label>
                                    <input type="text" name="item_name" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-[#800020] focus:border-transparent">
                                </div>

                                <!-- Unique ID -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Unique ID *</label>
                                    <input type="text" name="unique_id" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-[#800020] focus:border-transparent">
                                </div>

                                <!-- Quantity -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Quantity *</label>
                                    <input type="number" name="quantity" required min="0" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-[#800020] focus:border-transparent">
                                </div>

                                <!-- Category -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Category *</label>
                                    <select name="category" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-[#800020] focus:border-transparent">
                                        <option value="">Select Category</option>
                                        <option value="Ball">Ball</option>
                                        <option value="Net">Net</option>
                                        <option value="Racket">Racket</option>
                                        <option value="Bat">Bat</option>
                                        <option value="Goal Post">Goal Post</option>
                                        <option value="Protective Gear">Protective Gear</option>
                                        <option value="Uniform">Uniform</option>
                                        <option value="Training Equipment">Training Equipment</option>
                                        <option value="Shoes">Shoes</option>
                                        <option value="Others">Others</option>
                                    </select>
                                </div>

                                <!-- Sport -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Sport</label>
                                    <select name="sport" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-[#800020] focus:border-transparent">
                                        <option value="">Select Sport</option>
                                        <option value="Basketball">Basketball</option>
                                        <option value="Volleyball">Volleyball</option>
                                        <option value="Football">Football</option>
                                        <option value="Badminton">Badminton</option>
                                        <option value="Table Tennis">Table Tennis</option>
                                        <option value="Athletics">Athletics</option>
                                        <option value="Chess">Chess</option>
                                        <option value="Others">Others</option>
                                    </select>
                                </div>

                                <!-- Price -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Price *</label>
                                    <input type="number" name="price" required min="0" step="0.01" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-[#800020] focus:border-transparent">
                                </div>

                                <!-- Brand -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Brand</label>
                                    <input type="text" name="brand" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-[#800020] focus:border-transparent">
                                </div>

                                <!-- Color -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Color</label>
                                    <input type="text" name="color" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-[#800020] focus:border-transparent">
                                </div>

                                <!-- Size -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Size</label>
                                    <input type="text" name="size" placeholder="e.g., S, M, L, XL, or 42" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-[#800020] focus:border-transparent">
                                </div>

                                <!-- Status -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Status *</label>
                                    <select name="status" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-[#800020] focus:border-transparent">
                                        <option value="Available">Available</option>
                                        <option value="Unavailable">Unavailable</option>
                                        <option value="Damaged">Damaged</option>
                                    </select>
                                </div>

                                <!-- Semester -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Semester</label>
                                    <select name="semester" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-[#800020] focus:border-transparent">
                                        <option value="">Select Semester</option>
                                        <option value="1st Semester">1st Semester</option>
                                        <option value="2nd Semester">2nd Semester</option>
                                        <option value="Summer">Summer</option>
                                    </select>
                                </div>

                                <!-- Description -->
                                <div class="col-span-2">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                                    <textarea name="description" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-[#800020] focus:border-transparent"></textarea>
                                </div>

                                <!-- Image Upload -->
                                <div class="col-span-2">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Item Image</label>
                                    <input type="file" name="item_image" accept="image/*" class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm">
                                </div>

                            </div>
                        </div>

                        <div class="px-6 py-4 bg-gray-50 flex justify-end gap-3 border-t border-gray-200">
                            <button type="button" onclick="closeAddItemModal()" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 transition-colors cursor-pointer">
                                Cancel
                            </button>
                            <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-[#800020] rounded-md hover:bg-[#5c0016] transition-colors cursor-pointer">
                                Add Item
                            </button>
                        </div>
                    </form>
                </div>
                
            </div>
        </div>
    </div>

    <!-- Edit Item Modal -->
    <div id="editItemModal" class="hidden fixed inset-0 z-50 overflow-hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="fixed inset-0 bg-gray-900/50 backdrop-blur-sm transition-opacity" onclick="closeEditModal()"></div>
        
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="relative bg-white rounded-lg shadow-xl transform transition-all w-full max-w-2xl">
                <form id="editItemForm" enctype="multipart/form-data">
                    <input type="hidden" name="item_id" id="edit_item_id">
                    
                    <div class="px-5 py-3 border-b border-gray-200">
                        <h3 class="text-base font-semibold text-gray-900">Edit Item</h3>
                    </div>
                    
                    <div class="px-5 py-4">
                        <div class="grid grid-cols-4 gap-3">
                            <div class="col-span-2">
                                <label class="block text-xs font-medium text-gray-700 mb-0.5">Item Name *</label>
                                <input type="text" name="item_name" id="edit_item_name" required class="w-full px-2.5 py-1.5 border border-gray-300 rounded text-xs focus:ring-1 focus:ring-[#800020] focus:border-transparent">
                            </div>
                            
                            <div class="col-span-2">
                                <label class="block text-xs font-medium text-gray-700 mb-0.5">Unique ID *</label>
                                <input type="text" name="unique_id" id="edit_unique_id" required class="w-full px-2.5 py-1.5 border border-gray-300 rounded text-xs focus:ring-1 focus:ring-[#800020] focus:border-transparent">
                            </div>
                            
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-0.5">Quantity *</label>
                                <input type="number" name="quantity" id="edit_quantity" required min="0" class="w-full px-2.5 py-1.5 border border-gray-300 rounded text-xs focus:ring-1 focus:ring-[#800020] focus:border-transparent">
                            </div>

                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-0.5">Price *</label>
                                <input type="number" name="price" id="edit_price" required min="0" step="0.01" class="w-full px-2.5 py-1.5 border border-gray-300 rounded text-xs focus:ring-1 focus:ring-[#800020] focus:border-transparent">
                            </div>

                            <div class="col-span-2">
                                <label class="block text-xs font-medium text-gray-700 mb-0.5">Category *</label>
                                <select name="category" id="edit_category" required class="w-full px-2.5 py-1.5 border border-gray-300 rounded text-xs focus:ring-1 focus:ring-[#800020] focus:border-transparent">
                                    <option value="">Select Category</option>
                                    <option value="Ball">Ball</option>
                                    <option value="Net">Net</option>
                                    <option value="Racket">Racket</option>
                                    <option value="Bat">Bat</option>
                                    <option value="Goal Post">Goal Post</option>
                                    <option value="Protective Gear">Protective Gear</option>
                                    <option value="Uniform">Uniform</option>
                                    <option value="Training Equipment">Training Equipment</option>
                                    <option value="Shoes">Shoes</option>
                                    <option value="Others">Others</option>
                                </select>
                            </div>
                            
                            <div class="col-span-2">
                                <label class="block text-xs font-medium text-gray-700 mb-0.5">Sport</label>
                                <select name="sport" id="edit_sport" class="w-full px-2.5 py-1.5 border border-gray-300 rounded text-xs focus:ring-1 focus:ring-[#800020] focus:border-transparent">
                                    <option value="">Select Sport</option>
                                    <option value="Basketball">Basketball</option>
                                    <option value="Volleyball">Volleyball</option>
                                    <option value="Football">Football</option>
                                    <option value="Badminton">Badminton</option>
                                    <option value="Table Tennis">Table Tennis</option>
                                    <option value="Athletics">Athletics</option>
                                    <option value="Chess">Chess</option>
                                    <option value="Others">Others</option>
                                </select>
                            </div>
                            
                            <div class="col-span-2">
                                <label class="block text-xs font-medium text-gray-700 mb-0.5">Brand</label>
                                <input type="text" name="brand" id="edit_brand" class="w-full px-2.5 py-1.5 border border-gray-300 rounded text-xs focus:ring-1 focus:ring-[#800020] focus:border-transparent">
                            </div>
                            
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-0.5">Color</label>
                                <input type="text" name="color" id="edit_color" class="w-full px-2.5 py-1.5 border border-gray-300 rounded text-xs focus:ring-1 focus:ring-[#800020] focus:border-transparent">
                            </div>
                            
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-0.5">Size</label>
                                <input type="text" name="size" id="edit_size" placeholder="S, M..." class="w-full px-2.5 py-1.5 border border-gray-300 rounded text-xs focus:ring-1 focus:ring-[#800020] focus:border-transparent">
                            </div>
                            
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-0.5">Status *</label>
                                <select name="status" id="edit_status" required class="w-full px-2.5 py-1.5 border border-gray-300 rounded text-xs focus:ring-1 focus:ring-[#800020] focus:border-transparent">
                                    <option value="Available">Available</option>
                                    <option value="Unavailable">Unavailable</option>
                                    <option value="Damaged">Damaged</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-0.5">Semester</label>
                                <select name="semester" id="edit_semester" class="w-full px-2.5 py-1.5 border border-gray-300 rounded text-xs focus:ring-1 focus:ring-[#800020] focus:border-transparent">
                                    <option value="">Select</option>
                                    <option value="1st Semester">1st Sem</option>
                                    <option value="2nd Semester">2nd Sem</option>
                                    <option value="Summer">Summer</option>
                                </select>
                            </div>
                            
                            <div class="col-span-4">
                                <label class="block text-xs font-medium text-gray-700 mb-0.5">Description</label>
                                <textarea name="description" id="edit_description" rows="2" class="w-full px-2.5 py-1.5 border border-gray-300 rounded text-xs focus:ring-1 focus:ring-[#800020] focus:border-transparent"></textarea>
                            </div>
                            
                            <div class="col-span-4">
                                <label class="block text-xs font-medium text-gray-700 mb-0.5">Item Image</label>
                                <input type="file" name="item_image" accept="image/*" class="w-full px-2.5 py-1.5 border border-gray-300 rounded text-xs">
                            </div>
                        </div>
                    </div>
                    
                    <div class="px-5 py-3 bg-gray-50 flex justify-end gap-2 border-t border-gray-200">
                        <button type="button" onclick="closeEditModal()" class="px-3 py-1.5 text-xs font-medium text-gray-700 bg-white border border-gray-300 rounded hover:bg-gray-50 transition-colors cursor-pointer">
                            Cancel
                        </button>
                        <button type="submit" class="px-3 py-1.5 text-xs font-medium text-white bg-[#800020] rounded hover:bg-[#5c0016] transition-colors cursor-pointer">
                            Update Item
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
{
    // Scoped variables to prevent "Identifier has already been declared" errors during SPA navigation
    const searchInput = document.getElementById('searchInput');
    const sportFilter = document.getElementById('sportFilter');
    // Note: tableRows is dynamic, so we should query it inside the filter function if rows change dynamically
    // But for now we keep the logic somewhat consistent, though querySelectorAll returns a static NodeList
    // Better to query inside filterTable or use live collection if rows are added/removed often.
    // However, keeping original structure but scoped:
    
    // Global functions explicitly attached to window
    window.openAddItemModal = function() {
        document.getElementById('addItemModal').classList.remove('hidden');
    };

    window.closeAddItemModal = function() {
        document.getElementById('addItemModal').classList.add('hidden');
        document.getElementById('addItemForm').reset();
    };

    window.openEditModal = function(item) {
        document.getElementById('edit_item_id').value = item.id;
        document.getElementById('edit_item_name').value = item.item_name;
        document.getElementById('edit_unique_id').value = item.unique_id;
        document.getElementById('edit_category').value = item.category || '';
        document.getElementById('edit_quantity').value = item.quantity;
        document.getElementById('edit_price').value = item.price;
        document.getElementById('edit_brand').value = item.brand || '';
        document.getElementById('edit_color').value = item.color || '';
        document.getElementById('edit_size').value = item.size || '';
        document.getElementById('edit_sport').value = item.sport || '';
        document.getElementById('edit_semester').value = item.semester || '';
        document.getElementById('edit_status').value = item.status;
        document.getElementById('edit_description').value = item.description || '';
        
        document.getElementById('editItemModal').classList.remove('hidden');
    };

    window.closeEditModal = function() {
        document.getElementById('editItemModal').classList.add('hidden');
        document.getElementById('editItemForm').reset();
    };

    window.viewItem = function(item) {
        // Populate modal with item data
        document.getElementById('view_item_name').textContent = item.item_name;
        document.getElementById('view_unique_id').textContent = 'ID: ' + item.unique_id;
        document.getElementById('view_category').textContent = item.category || 'Uncategorized';
        document.getElementById('view_brand').textContent = item.brand || 'No Brand';
        document.getElementById('view_color').textContent = item.color || 'Not specified';
        document.getElementById('view_size').textContent = item.size || 'Not specified';
        document.getElementById('view_sport').textContent = item.sport || 'Not specified';
        document.getElementById('view_semester').textContent = item.semester || 'Not specified';
        document.getElementById('view_quantity').textContent = item.quantity + ' pcs';
        document.getElementById('view_price').textContent = '₱' + parseFloat(item.price).toLocaleString('en-PH', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        document.getElementById('view_description').textContent = item.description || 'No description available.';
        
        // Check if item is new and show badge
        const newBadge = document.getElementById('view_new_badge');
        if (item.created_at) {
            const createdTime = new Date(item.created_at).getTime();
            const currentTime = new Date().getTime();
            const timeDiff = currentTime - createdTime;
            const isNew = timeDiff < 86400000; // 86400000 milliseconds = 24 hours
            
            if (isNew) {
                newBadge.classList.remove('hidden');
            } else {
                newBadge.classList.add('hidden');
            }
        } else {
            newBadge.classList.add('hidden');
        }
        
        // Set image
        if (item.image) {
            document.getElementById('view_image').src = '../../frontend/images/items/' + item.image;
        } else {
            document.getElementById('view_image').src = 'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="gray"%3E%3Cpath stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z"/%3E%3C/svg%3E';
        }
        
        // Set status badge
        const statusBadge = document.getElementById('view_status_badge');
        
        // Create icon HTML based on status
        let iconHTML = '';
        if (item.status === 'Available') {
            statusBadge.className = 'inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700 ring-1 ring-emerald-600/20';
            iconHTML = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-3.5 h-3.5"><polyline points="20 6 9 17 4 12"></polyline></svg>';
        } else if (item.status === 'Unavailable') {
            statusBadge.className = 'inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-medium bg-orange-50 text-orange-700 ring-1 ring-orange-600/20';
            iconHTML = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-3.5 h-3.5"><circle cx="12" cy="12" r="10"></circle><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"></line></svg>';
        } else { // Damaged
            statusBadge.className = 'inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-medium bg-red-50 text-red-700 ring-1 ring-red-600/20';
            iconHTML = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-3.5 h-3.5"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>';
        }
        
        // Update the badge content with icon and status text
        statusBadge.innerHTML = iconHTML + '<span>' + item.status + '</span>';
        
        
        // Store item data for edit transition
        window.currentViewItem = item;
        
        // Show modal
        document.getElementById('viewItemModal').classList.remove('hidden');
    };

    window.closeViewModal = function() {
        document.getElementById('viewItemModal').classList.add('hidden');
    };

    window.closeViewAndEdit = function() {
        window.closeViewModal();
        if (window.currentViewItem) {
            setTimeout(() => window.openEditModal(window.currentViewItem), 150);
        }
    };

    window.deleteItem = function(id) {
        Swal.fire({
            title: 'Delete Item?',
            text: "This action cannot be undone!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#800020',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Yes, delete it',
            cancelButtonText: 'Cancel',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                fetch('../../backend/items/delete_item.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: 'item_id=' + id
                })
                .then(response => response.json())
                .then(result => {
                    if (result.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Deleted!',
                            text: result.message || 'Item has been deleted successfully',
                            confirmButtonColor: '#800020',
                            timer: 2000,
                            showConfirmButton: false
                        }).then(() => location.reload());
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: result.message || 'Failed to delete item',
                            confirmButtonColor: '#800020'
                        });
                    }
                })
                .catch(() => {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: 'An error occurred while deleting the item',
                        confirmButtonColor: '#800020'
                    });
                });
            }
        });
    };

    // Handle Add form submission
    document.getElementById('addItemForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        
        try {
            const response = await fetch('../../backend/items/add_item.php', {
                method: 'POST',
                body: formData
            });
            const result = await response.json();
            
            if (result.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Success!',
                    text: result.message || 'Item added successfully',
                    confirmButtonColor: '#800020',
                    timer: 2000,
                    showConfirmButton: false
                }).then(() => location.reload());
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: result.message || 'Failed to add item',
                    confirmButtonColor: '#800020'
                });
            }
        } catch (error) {
            Swal.fire({
                icon: 'error',
                title: 'Error!',
                text: 'An error occurred while adding the item',
                confirmButtonColor: '#800020'
            });
        }
    });

    // Handle Edit form submission
    document.getElementById('editItemForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        
        try {
            const response = await fetch('../../backend/items/edit_item.php', {
                method: 'POST',
                body: formData
            });
            const result = await response.json();
            
            if (result.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Success!',
                    text: result.message || 'Item updated successfully',
                    confirmButtonColor: '#800020',
                    timer: 2000,
                    showConfirmButton: false
                }).then(() => location.reload());
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: result.message || 'Failed to update item',
                    confirmButtonColor: '#800020'
                });
            }
        } catch (error) {
            Swal.fire({
                icon: 'error',
                title: 'Error!',
                text: 'An error occurred while updating the item',
                confirmButtonColor: '#800020'
            });
        }
    });
    
    // Search and Filter Functionality
    function filterTable() {
        const searchTerm = searchInput.value.toLowerCase().trim();
        const selectedSport = sportFilter.value.toLowerCase().trim();
        const rows = document.querySelectorAll('tbody tr'); // Query dynamically to avoid stale state
        
        rows.forEach(row => {
            // Skip the "no items" row
            if (row.querySelector('td[colspan]')) {
                return;
            }
            
            // Get all searchable text content from row
            const uniqueId = row.querySelector('td:nth-child(1)')?.textContent.toLowerCase() || '';
            const itemName = row.querySelector('td:nth-child(3)')?.textContent.toLowerCase() || '';
            const sport = row.querySelector('td:nth-child(4)')?.textContent.toLowerCase() || '';
            const category = row.querySelector('td:nth-child(5)')?.textContent.toLowerCase() || '';
            
            // Check search match (unique_id, item name, category, etc.)
            const matchesSearch = !searchTerm || 
                uniqueId.includes(searchTerm) ||
                itemName.includes(searchTerm) || 
                category.includes(searchTerm);
            
            // Check sport filter match (exact match for better filtering)
            const matchesSport = !selectedSport || sport.includes(selectedSport);
            
            // Show/hide row based on both filters
            if (matchesSearch && matchesSport) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }
    
    // Event listeners attached to the scoped variables
    if (searchInput) searchInput.addEventListener('input', filterTable);
    if (sportFilter) sportFilter.addEventListener('change', filterTable);

}
    </script>

    </div><!-- End of .ml-64 -->
</body>
</html>
