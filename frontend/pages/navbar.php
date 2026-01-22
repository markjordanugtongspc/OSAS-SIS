<?php
    // Determine current page for active nav highlighting
    $current_page = basename($_SERVER['PHP_SELF']);
    // Also determine current directory (e.g. 'pages' vs 'CMS') so dashboards don't both appear active
    $current_dir = basename(dirname($_SERVER['PHP_SELF']));

    // Calculate base path based on the physical location of this file (navbar.php)
    // This ensures correct paths regardless of which page includes this navbar
    $file_dir = str_replace('\\', '/', __DIR__); // .../frontend/pages
    $doc_root = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']); // .../htdocs

    // Get path relative to web root (case-insensitive for Windows safety)
    $web_path = str_ireplace($doc_root, '', $file_dir); // Result: /OSAS-SIS/frontend/pages

    // We want the frontend root (/OSAS-SIS/frontend/)
    // Remove '/pages' from the end
    $base_path = str_ireplace('/pages', '', $web_path);

    // Ensure trailing slash
    $base_path = rtrim($base_path, '/') . '/';
?>

<!-- Left Sidebar Navigation -->
<aside id="sidebar" class="fixed left-0 top-0 z-40 h-screen w-64 bg-gradient-to-b from-[#800020] to-[#5c0016] flex flex-col shadow-xl transition-all duration-300">
    
    <!-- Logo & Brand -->
    <div class="p-4 border-b border-white/10 flex items-center justify-between">
        <div class="flex items-center gap-3 overflow-hidden">
            <div class="w-12 h-12 rounded-full bg-white overflow-hidden flex items-center justify-center shadow-lg flex-shrink-0">
                <img src="<?= $base_path ?>images/spc.png" alt="SPC Logo" class="w-full h-full object-cover">
            </div>
            <div class="sidebar-text">
                <h1 class="text-lg font-bold text-white whitespace-nowrap">SPC - OSAS</h1>
                <p class="text-[10px] text-white/70 leading-tight">Office of Student Affairs and Services</p>
            </div>
        </div>
        
        <!-- Toggle Button -->
        <button id="sidebarToggle" class="flex-shrink-0 p-1.5 hover:bg-white/10 rounded-lg transition-colors cursor-pointer" title="Collapse Sidebar">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5 text-white">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
            </svg>
        </button>
    </div>

    <!-- Navigation Links -->
    <nav class="flex-1 px-3 py-3 space-y-1 overflow-y-hidden">
        

        
        <!-- SIS Dropdown -->
        <div class="nav-group">
            <!-- Section Label (visible only when collapsed) -->
            <div class="section-label hidden text-[9px] text-white/50 text-center py-1 mb-1 uppercase tracking-wider font-semibold">Sports</div>
            <button class="nav-dropdown-trigger nav-item flex items-center gap-2.5 px-3 py-2.5 rounded-lg text-white/80 hover:text-white hover:bg-white/10 transition-all group relative w-full" data-dropdown="sis-dropdown">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-4 h-4 flex-shrink-0">
                    <path fill-rule="evenodd" d="M7.502 6h7.128A3.375 3.375 0 0118 9.375v9.375a3 3 0 003-3V6.108c0-1.505-1.125-2.811-2.664-2.94a48.972 48.972 0 00-.673-.05A3 3 0 0015 1.5h-1.5a3 3 0 00-2.663 1.618c-.225.015-.45.032-.673.05C8.662 3.295 7.554 4.542 7.502 6zM13.5 3A1.5 1.5 0 0012 4.5h4.5A1.5 1.5 0 0015 3h-1.5z" clip-rule="evenodd" />
                    <path fill-rule="evenodd" d="M3 9.375C3 8.339 3.84 7.5 4.875 7.5h9.75c1.036 0 1.875.84 1.875 1.875v11.25c0 1.035-.84 1.875-1.875 1.875h-9.75A1.875 1.875 0 013 20.625V9.375z" clip-rule="evenodd" />
                </svg>
                <span class="sidebar-text text-sm whitespace-nowrap flex-1 text-left truncate">Sports Equipment</span>
                <svg class="dropdown-arrow sidebar-text w-4 h-4 transition-transform duration-300 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
                <span class="tooltip">Sports Equipment Management</span>
            </button>
            <div id="sis-dropdown" class="dropdown-content overflow-hidden transition-all duration-300 max-h-0 opacity-0">
                <a href="<?= $base_path ?>pages/dashboard.php" class="nav-item nav-subitem flex items-center gap-2.5 px-3 py-2 ml-6 rounded-lg <?= $current_page == 'dashboard.php' && $current_dir == 'pages' ? 'bg-white/10 text-white font-medium' : 'text-white/70 hover:text-white hover:bg-white/5' ?> transition-all group relative">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-3.5 h-3.5 flex-shrink-0">
                        <path d="M11.47 3.84a.75.75 0 011.06 0l8.69 8.69a.75.75 0 101.06-1.06l-8.689-8.69a2.25 2.25 0 00-3.182 0l-8.69 8.69a.75.75 0 001.061 1.06l8.69-8.69z" />
                        <path d="M12 5.432l8.159 8.159c.03.03.06.058.091.086v6.198c0 1.035-.84 1.875-1.875 1.875H15a.75.75 0 01-.75-.75v-4.5a.75.75 0 00-.75-.75h-3a.75.75 0 00-.75.75V21a.75.75 0 01-.75.75H5.625a1.875 1.875 0 01-1.875-1.875v-6.198a2.29 2.29 0 00.091-.086L12 5.43z" />
                    </svg>
                    <span class="sidebar-text text-sm whitespace-nowrap">Dashboard</span>
                    <span class="tooltip">Dashboard</span>
                </a>
                <a href="<?= $base_path ?>pages/item_inventory.php" class="nav-item nav-subitem flex items-center gap-2.5 px-3 py-2 ml-6 rounded-lg <?= $current_page == 'item_inventory.php' ? 'bg-white/10 text-white font-medium' : 'text-white/70 hover:text-white hover:bg-white/5' ?> transition-all group relative">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-3.5 h-3.5 flex-shrink-0">
                        <path fill-rule="evenodd" d="M7.502 6h7.128A3.375 3.375 0 0118 9.375v9.375a3 3 0 003-3V6.108c0-1.505-1.125-2.811-2.664-2.94a48.972 48.972 0 00-.673-.05A3 3 0 0015 1.5h-1.5a3 3 0 00-2.663 1.618c-.225.015-.45.032-.673.05C8.662 3.295 7.554 4.542 7.502 6zM13.5 3A1.5 1.5 0 0012 4.5h4.5A1.5 1.5 0 0015 3h-1.5z" clip-rule="evenodd" />
                        <path fill-rule="evenodd" d="M3 9.375C3 8.339 3.84 7.5 4.875 7.5h9.75c1.036 0 1.875.84 1.875 1.875v11.25c0 1.035-.84 1.875-1.875 1.875h-9.75A1.875 1.875 0 013 20.625V9.375zM6 12a.75.75 0 01.75-.75h.008a.75.75 0 01.75.75v.008a.75.75 0 01-.75.75H6.75a.75.75 0 01-.75-.75V12zm2.25 0a.75.75 0 01.75-.75h3.75a.75.75 0 010 1.5H9a.75.75 0 01-.75-.75zM6 15a.75.75 0 01.75-.75h.008a.75.75 0 01.75.75v.008a.75.75 0 01-.75.75H6.75a.75.75 0 01-.75-.75V15zm2.25 0a.75.75 0 01.75-.75h3.75a.75.75 0 010 1.5H9a.75.75 0 01-.75-.75zM6 18a.75.75 0 01.75-.75h.008a.75.75 0 01.75.75v.008a.75.75 0 01-.75.75H6.75a.75.75 0 01-.75-.75V18zm2.25 0a.75.75 0 01.75-.75h3.75a.75.75 0 010 1.5H9a.75.75 0 01-.75-.75z" clip-rule="evenodd" />
                    </svg>
                    <span class="sidebar-text text-sm whitespace-nowrap">Item Inventory</span>
                    <span class="tooltip">Item Inventory</span>
                </a>
                <a href="<?= $base_path ?>pages/item_management.php" class="nav-item nav-subitem flex items-center gap-2.5 px-3 py-2 ml-6 rounded-lg <?= $current_page == 'item_management.php' ? 'bg-white/10 text-white font-medium' : 'text-white/70 hover:text-white hover:bg-white/5' ?> transition-all group relative">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-3.5 h-3.5 flex-shrink-0">
                        <path d="M21.731 2.269a2.625 2.625 0 00-3.712 0l-1.157 1.157 3.712 3.712 1.157-1.157a2.625 2.625 0 000-3.712zM19.513 8.199l-3.712-3.712-8.4 8.4a5.25 5.25 0 00-1.32 2.214l-.8 2.685a.75.75 0 00.933.933l2.685-.8a5.25 5.25 0 002.214-1.32l8.4-8.4z" />
                        <path d="M5.25 5.25a3 3 0 00-3 3v10.5a3 3 0 003 3h10.5a3 3 0 003-3V13.5a.75.75 0 00-1.5 0v5.25a1.5 1.5 0 01-1.5 1.5H5.25a1.5 1.5 0 01-1.5-1.5V8.25a1.5 1.5 0 011.5-1.5h5.25a.75.75 0 000-1.5H5.25z" />
                    </svg>
                    <span class="sidebar-text text-sm whitespace-nowrap">Item Management</span>
                    <span class="tooltip">Item Management</span>
                </a>
                <a href="<?= $base_path ?>pages/borrow.php" class="nav-item nav-subitem flex items-center gap-2.5 px-3 py-2 ml-6 rounded-lg <?= $current_page == 'borrow.php' ? 'bg-white/10 text-white font-medium' : 'text-white/70 hover:text-white hover:bg-white/5' ?> transition-all group relative">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-3.5 h-3.5 flex-shrink-0">
                        <path d="M11.47 1.72a.75.75 0 011.06 0l3 3a.75.75 0 01-1.06 1.06l-1.72-1.72V7.5h-1.5V4.06L9.53 5.78a.75.75 0 01-1.06-1.06l3-3zM11.25 7.5V15a.75.75 0 001.5 0V7.5h3.75a3 3 0 013 3v9a3 3 0 01-3 3h-9a3 3 0 01-3-3v-9a3 3 0 013-3h3.75z" />
                    </svg>
                    <span class="sidebar-text text-sm whitespace-nowrap">Borrow</span>
                    <span class="tooltip">Borrow</span>
                </a>
                <a href="<?= $base_path ?>pages/history.php" id="nav-history" class="nav-item nav-subitem flex items-center gap-2.5 px-3 py-2 ml-6 rounded-lg <?= $current_page == 'history.php' ? 'bg-white/10 text-white font-medium' : 'text-white/70 hover:text-white hover:bg-white/5' ?> transition-all group relative">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-3.5 h-3.5 flex-shrink-0">
                        <path d="M12 2.25a9.75 9.75 0 1 0 9.75 9.75A9.762 9.762 0 0 0 12 2.25zm0 17.25a7.5 7.5 0 1 1 7.5-7.5 7.509 7.509 0 0 1-7.5 7.5z" />
                        <path d="M12.75 7.5a.75.75 0 0 0-1.5 0v4.19l-2.22 2.22a.75.75 0 1 0 1.06 1.06l2.47-2.47A.75.75 0 0 0 12.75 12V7.5z" />
                    </svg>
                    <span class="sidebar-text text-sm whitespace-nowrap">History</span>
                    <span class="tooltip">History</span>
                </a>
                <a href="<?= $base_path ?>pages/logs.php" id="nav-logs" class="nav-item nav-subitem flex items-center gap-2.5 px-3 py-2 ml-6 rounded-lg <?= $current_page == 'logs.php' ? 'bg-white/10 text-white font-medium' : 'text-white/70 hover:text-white hover:bg-white/5' ?> transition-all group relative">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-3.5 h-3.5 flex-shrink-0">
                        <path fill-rule="evenodd" d="M5.625 1.5H9a3.75 3.75 0 013.75 3.75v1.875c0 1.036.84 1.875 1.875 1.875H16.5a3.75 3.75 0 013.75 3.75v7.875c0 1.035-.84 1.875-1.875 1.875H5.625a1.875 1.875 0 01-1.875-1.875V3.375c0-1.036.84-1.875 1.875-1.875zM12.75 12a.75.75 0 00-1.5 0V7.5a.75.75 0 00-1.5 0v5.25a.75.75 0 001.5 0 .75.75 0 01.75.75v3.75a.75.75 0 001.5 0V12.75a.75.75 0 01-1.5 0v-6.75z" clip-rule="evenodd" />
                    </svg>
                    <span class="sidebar-text text-sm whitespace-nowrap">Logs</span>
                    <span class="tooltip">Logs</span>
                </a>
            </div>
        </div>

        <!-- Divider -->
        <div class="nav-divider sidebar-text my-2 mx-3 border-t border-white/20"></div>

        <!-- Document Management Dropdown -->
        <div class="nav-group">
            <!-- Section Label (visible only when collapsed) -->
            <div class="section-label hidden text-[9px] text-white/50 text-center py-1 mb-1 uppercase tracking-wider font-semibold">Storage</div>
            <button class="nav-dropdown-trigger nav-item flex items-center gap-2.5 px-3 py-2.5 rounded-lg text-white/80 hover:text-white hover:bg-white/10 transition-all group relative w-full" data-dropdown="docs-dropdown">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-4 h-4 flex-shrink-0">
                    <path fill-rule="evenodd" d="M5.625 1.5H9a3.75 3.75 0 013.75 3.75v1.875c0 1.036.84 1.875 1.875 1.875H16.5a3.75 3.75 0 013.75 3.75v7.875c0 1.035-.84 1.875-1.875 1.875H5.625a1.875 1.875 0 01-1.875-1.875V3.375c0-1.036.84-1.875 1.875-1.875zm6.905 9.97a.75.75 0 00-1.06 0l-3 3a.75.75 0 101.06 1.06l1.72-1.72V18a.75.75 0 001.5 0v-4.19l1.72 1.72a.75.75 0 101.06-1.06l-3-3z" clip-rule="evenodd" />
                    <path d="M14.25 5.25a5.23 5.23 0 00-1.279-3.434 9.768 9.768 0 016.963 6.963A5.23 5.23 0 0016.5 7.5h-1.875a.375.375 0 01-.375-.375V5.25z" />
                </svg>
                <span class="sidebar-text text-sm whitespace-nowrap flex-1 text-left">Storage Management</span>
                <svg class="dropdown-arrow sidebar-text w-4 h-4 transition-transform duration-300 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
                <span class="tooltip">Cabinet Management</span>
            </button>
            <div id="docs-dropdown" class="dropdown-content overflow-hidden transition-all duration-300 max-h-0 opacity-0">
                <a href="<?= $base_path ?>CMS/dashboard.php" class="nav-item nav-subitem flex items-center gap-2.5 px-3 py-2 ml-6 rounded-lg <?= $current_page == 'dashboard.php' && $current_dir == 'CMS' ? 'bg-white/10 text-white font-medium' : 'text-white/70 hover:text-white hover:bg-white/5' ?> transition-all group relative">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-3.5 h-3.5 flex-shrink-0">
                        <path d="M11.47 3.84a.75.75 0 011.06 0l8.69 8.69a.75.75 0 101.06-1.06l-8.689-8.69a2.25 2.25 0 00-3.182 0l-8.69 8.69a.75.75 0 001.061 1.06l8.69-8.69z" />
                        <path d="M12 5.432l8.159 8.159c.03.03.06.058.091.086v6.198c0 1.035-.84 1.875-1.875 1.875H15a.75.75 0 01-.75-.75v-4.5a.75.75 0 00-.75-.75h-3a.75.75 0 00-.75.75V21a.75.75 0 01-.75.75H5.625a1.875 1.875 0 01-1.875-1.875v-6.198a2.29 2.29 0 00.091-.086L12 5.43z" />
                    </svg>
                    <span class="sidebar-text text-sm whitespace-nowrap">Dashboard</span>
                    <span class="tooltip">Dashboard</span>
                </a>
                <a href="<?= $base_path ?>CMS/pages/papers.php" class="nav-item nav-subitem flex items-center gap-2.5 px-3 py-2 ml-6 rounded-lg <?= $current_page == 'papers.php' ? 'bg-white/10 text-white font-medium' : 'text-white/70 hover:text-white hover:bg-white/5' ?> transition-all group relative">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-3.5 h-3.5 flex-shrink-0">
                        <path d="M5.625 1.5c-1.036 0-1.875.84-1.875 1.875v17.25c0 1.035.84 1.875 1.875 1.875h12.75c1.035 0 1.875-.84 1.875-1.875V12.75A3.75 3.75 0 0016.5 9h-1.875a1.875 1.875 0 01-1.875-1.875V5.25A3.75 3.75 0 009 1.5H5.625z" />
                        <path d="M12.971 1.816A5.23 5.23 0 0114.25 5.25v1.875c0 .207.168.375.375.375H16.5a5.23 5.23 0 013.434 1.279 9.768 9.768 0 00-6.963-6.963z" />
                    </svg>
                    <span class="sidebar-text text-sm whitespace-nowrap">Supplies/Equipment</span>
                    <span class="tooltip">Supplies/Equipment</span>
                </a>
                

            </div>
        </div>

        <!-- Divider -->
        <div class="nav-divider sidebar-text my-2 mx-3 border-t border-white/20"></div>

        <!-- Admin: User Management -->
        <?php if (isset($_SESSION['position']) && stripos($_SESSION['position'], 'Admin') !== false): ?>
        <a href="<?= $base_path ?>pages/adduser.php" class="nav-item flex items-center gap-2.5 px-3 py-2.5 rounded-lg <?= $current_page == 'adduser.php' ? 'bg-white/10 text-white font-medium' : 'text-white/80 hover:text-white hover:bg-white/10' ?> transition-all group relative">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-4 h-4 flex-shrink-0">
                <path d="M6.25 6.375a4.125 4.125 0 118.25 0 4.125 4.125 0 01-8.25 0zM3.25 19.125a7.125 7.125 0 0114.25 0v.003l-.001.119a.75.75 0 01-.363.63 13.067 13.067 0 01-6.761 1.873c-2.472 0-4.786-.684-6.76-1.873a.75.75 0 01-.364-.63l-.001-.122zM19.75 7.5a.75.75 0 00-1.5 0v2.25H16a.75.75 0 000 1.5h2.25v2.25a.75.75 0 001.5 0v-2.25H22a.75.75 0 000-1.5h-2.25V7.5z" />
            </svg>
            <span class="sidebar-text text-sm whitespace-nowrap">User Management</span>
            <span class="tooltip">Manage Users</span>
        </a>
        <?php endif; ?>

        <!-- Notifications Link (Top Level) -->
        <a href="<?= $base_path ?>notification/notification.php" id="nav-notifications" class="nav-item flex items-center gap-2.5 px-3 py-2.5 rounded-lg <?= strpos($current_page, 'notification') !== false ? 'bg-white/10 text-white font-medium' : 'text-white/80 hover:text-white hover:bg-white/10' ?> transition-all group relative">
            <div class="relative flex-shrink-0">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-4 h-4">
                    <path fill-rule="evenodd" d="M5.25 9a6.75 6.75 0 0113.5 0v.75c0 2.123.8 4.057 2.118 5.52a.75.75 0 01-.297 1.206c-1.544.57-3.16.99-4.831 1.243a3.75 3.75 0 11-7.48 0 24.585 24.585 0 01-4.831-1.244.75.75 0 01-.298-1.205A8.217 8.217 0 005.25 9.75V9zm4.502 8.9c.46.03.92.059 1.383.087a2.25 2.25 0 01-2.925 0c.462-.028.923-.057 1.383-.087zM5 4.5a3 3 0 013-3h6a3 3 0 013 3v1.5a.75.75 0 01-1.5 0v-1.5a1.5 1.5 0 00-1.5-1.5h-6a1.5 1.5 0 00-1.5 1.5v1.5a.75.75 0 01-1.5 0V4.5z" clip-rule="evenodd" />
                </svg>
                <span id="navNotificationBadge" class="absolute -top-1.5 -right-2.5 flex h-4 w-4 items-center justify-center rounded-full bg-red-500 text-[9px] font-bold text-white hidden ring-1 ring-[#800020]">0</span>
            </div>
            <span class="sidebar-text text-sm whitespace-nowrap">Notifications</span>
            <span class="tooltip">Notifications</span>
        </a>
    </nav>

    <!-- User Profile & Logout -->
    <div class="p-3 border-t border-white/10">
        <!-- User Info -->
        <div id="userInfoBlock" class="flex items-center gap-3 px-3 py-2 rounded-lg bg-white/5 mb-2 overflow-hidden">
            <?php if (!empty($_SESSION['image']) && file_exists(dirname(__FILE__) . '/../images/users/' . $_SESSION['image'])): ?>
                <img src="<?= $base_path ?>images/users/<?= htmlspecialchars($_SESSION['image']) ?>" alt="Profile" class="w-10 h-10 rounded-full object-cover shadow border-2 border-white/20 flex-shrink-0">
            <?php else: ?>
                <div class="w-10 h-10 rounded-full bg-white flex items-center justify-center text-[#800020] text-sm font-bold shadow flex-shrink-0">
                    <?= substr($firstname, 0, 1) . substr($lastname, 0, 1) ?>
                </div>
            <?php endif; ?>
            <div class="flex-1 min-w-0 sidebar-text">
                <p class="text-sm font-medium text-white truncate"><?= $firstname . ' ' . $lastname ?></p>
                <p class="text-xs text-white/60 truncate"><?= $position ?></p>
            </div>
            <!-- Edit Profile Button -->
            <button onclick="openEditProfileModal()" class="p-1.5 hover:bg-white/10 rounded transition-colors sidebar-text flex-shrink-0 cursor-pointer" title="Edit Profile">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 text-white/80">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                </svg>
            </button>
        </div>

        <!-- Logout Button -->
        <a href="<?= $base_path ?>pages/logout.php" onclick="confirmLogout(event)" class="nav-item flex items-center gap-2.5 px-3 py-2.5 rounded-lg text-white/80 hover:text-white hover:bg-red-500/20 transition-all group relative">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 flex-shrink-0">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9" />
            </svg>
            <span class="sidebar-text text-sm whitespace-nowrap">Logout</span>
            <span class="tooltip">Logout</span>
        </a>   
    </div>

</aside>

<!-- Sidebar Styles -->
<style>
    /* Navigation Item Smooth Transitions */
    .nav-item {
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
    
    /* Only apply translate on hover when sidebar is NOT collapsed */
    #sidebar:not(.collapsed) .nav-item:hover {
        transform: translateX(2px);
    }
    
    .nav-item svg {
        transition: transform 0.3s ease;
    }
    
    .nav-item:hover svg {
        transform: scale(1.1);
    }
    
    /* Prevent any unwanted expansion in collapsed mode */
    #sidebar.collapsed .nav-item {
        transform: none;
    }

    /* Dropdown Animations */
    .dropdown-content {
        transition: max-height 0.4s cubic-bezier(0.4, 0, 0.2, 1), 
                    opacity 0.3s ease, 
                    margin-top 0.3s ease;
    }

    .dropdown-content.show {
        max-height: 500px !important;
        opacity: 1 !important;
        margin-top: 0.5rem;
    }

    .dropdown-arrow {
        transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .nav-dropdown-trigger.active .dropdown-arrow {
        transform: rotate(180deg);
    }

    /* Subitem hover effects */
    .nav-subitem {
        font-size: 0.875rem;
    }

    .nav-subitem:hover {
        padding-left: 1rem;
    }

    /* Collapsed state */
    #sidebar.collapsed {
        width: 4.5rem; /* 72px */
    }
    
    /* Show dropdown content inline in collapsed mode */
    #sidebar.collapsed .dropdown-content {
        position: static;
        max-height: none !important;
        opacity: 1 !important;
        margin-top: 0 !important;
        margin-left: 0;
        padding: 0;
        background: transparent;
    }

    #sidebar.collapsed .dropdown-content.show {
        display: block !important;
    }

    /* Position the nav-group normally */
    #sidebar.collapsed .nav-group {
        position: relative;
        width: 100%;
    }

    /* Style subitems in collapsed mode - centered icons only */
    #sidebar.collapsed .nav-subitem {
        margin-left: 0 !important;
        padding: 0.75rem !important;
        justify-content: center;
        width: 100%;
    }

    #sidebar.collapsed .dropdown-arrow {
        display: none;
    }

    /* Ensure dropdown triggers are still visible and clickable when collapsed */
    #sidebar.collapsed .nav-dropdown-trigger {
        display: none !important;
    }
    
    /* Prevent horizontal scrollbar on sidebar and nav */
    #sidebar,
    #sidebar nav {
        overflow-x: hidden;
    }
    
    /* Adjust header layout when collapsed */
    #sidebar.collapsed > div:first-child {
        padding: 1rem 0;
        flex-direction: column;
        gap: 0.5rem;
        justify-content: center;
    }

    /* Adjust nav container when collapsed */
    #sidebar.collapsed nav {
        padding-left: 0.5rem;
        padding-right: 0.5rem;
        align-items: center;
        display: flex;
        flex-direction: column;
    }

    /* Ensure main content margin matches collapsed sidebar on all pages
       This avoids a brief "stretch" when navigating to pages that do
       a full reload (item management, borrow, history). */
    #sidebar.collapsed + .ml-64,
    #sidebar.collapsed ~ .ml-64,
    #sidebar.collapsed ~ #spaContentContainer .ml-64 {
        margin-left: 4.5rem !important;
    }

    /* Center navigation icons when collapsed */
    #sidebar.collapsed .nav-item {
        justify-content: center;
        padding: 0.5rem;
        width: 100%;
        margin-bottom: 0.25rem;
    }
    
    /* Reduce padding on nav items for better fit */
    .nav-item {
        padding: 0.5rem 0.75rem !important;
    }

    .nav-subitem {
        padding: 0.4rem 0.75rem !important;
    }

    .nav-dropdown-trigger {
        padding: 0.5rem 0.75rem !important;
    }
    
    #sidebar.collapsed .sidebar-text {
        display: none;
    }

    /* Keep divider visible when collapsed */
    #sidebar.collapsed .nav-divider {
        display: block !important;
        margin-left: 0.75rem;
        margin-right: 0.75rem;
    }

    /* Show section labels only when collapsed */
    #sidebar.collapsed .section-label {
        display: block !important;
    }
    
    #sidebar.collapsed #sidebarToggle svg {
        transform: rotate(180deg);
    }
    
    /* Hide user info block when collapsed */
    #sidebar.collapsed #userInfoBlock {
        display: none;
    }
    
    /* Tooltip for collapsed state */
    #sidebar .tooltip {
        position: absolute;
        left: 100%;
        top: 50%;
        transform: translateY(-50%);
        margin-left: 0.75rem;
        padding: 0.5rem 0.75rem;
        background: rgba(0, 0, 0, 0.9);
        color: white;
        font-size: 0.875rem;
        border-radius: 0.5rem;
        white-space: nowrap;
        opacity: 0;
        pointer-events: none;
        transition: opacity 0.2s ease;
        z-index: 50;
    }
    
    #sidebar.collapsed .nav-item:hover .tooltip {
        opacity: 1;
    }
    
    #sidebar:not(.collapsed) .tooltip {
        display: none;
    }
    
    /* Hover effects for collapsed items */
    #sidebar.collapsed .nav-item:hover {
        background-color: rgba(255, 255, 255, 0.1);
    }
    
    /* Smooth transition for sidebar width */
    #sidebar {
        transition: width 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
    
    /* Smooth transition for sidebar text */
    .sidebar-text {
        transition: opacity 0.2s ease;
    }

    /* Temporary highlight when a row is saved to History */
    #sidebar .history-highlight {
        background-color: rgba(16, 185, 129, 0.2) !important;
        color: #10b981 !important;
        box-shadow: 0 0 15px rgba(16, 185, 129, 0.4);
        transform: scale(1.05);
    }
    #sidebar .history-highlight svg {
        color: #10b981 !important;
        transform: scale(1.2);
    }
</style>

<!-- Edit Profile Modal -->
<div id="editProfileModal" class="hidden fixed inset-0 z-50 overflow-y-auto">
    <!-- Background overlay with blur -->
    <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-md transition-opacity" onclick="closeEditProfileModal()"></div>
    
    <!-- Modal container -->
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="relative bg-white rounded-2xl shadow-2xl transform transition-all w-full max-w-md">
            <form id="editProfileForm" enctype="multipart/form-data">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">Edit Profile</h3>
                </div>
                
                <div class="px-6 py-4 space-y-4">
                    <!-- Profile Image -->
                    <div class="flex items-center gap-4">
                        <div id="profilePreview" class="relative">
                            <?php if (!empty($_SESSION['image']) && file_exists('../../frontend/images/users/' . $_SESSION['image'])): ?>
                                <img src="../../frontend/images/users/<?= htmlspecialchars($_SESSION['image']) ?>" class="w-20 h-20 rounded-full object-cover border-2 border-gray-300">
                            <?php else: ?>
                                <div class="w-20 h-20 rounded-full bg-gradient-to-br from-[#800020] to-[#5c0016] flex items-center justify-center text-white text-2xl font-bold border-2 border-gray-300">
                                    <?= substr($firstname, 0, 1) . substr($lastname, 0, 1) ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="flex-1">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Profile Picture</label>
                            <input type="file" name="profile_image" accept="image/*" onchange="previewImage(event)" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-medium file:bg-[#800020] file:text-white hover:file:bg-[#5c0016]">
                        </div>
                    </div>
                    
                    <!-- First Name -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">First Name</label>
                        <input type="text" name="firstname" value="<?= htmlspecialchars($firstname) ?>" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-[#800020] focus:border-transparent">
                    </div>
                    
                    <!-- Last Name -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Last Name</label>
                        <input type="text" name="lastname" value="<?= htmlspecialchars($lastname) ?>" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-[#800020] focus:border-transparent">
                    </div>
                </div>
                
                <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex gap-3">
                    <button type="submit" class="flex-1 px-4 py-2 bg-[#800020] text-white rounded-lg hover:bg-[#5c0016] transition-colors font-medium cursor-pointer">
                        Save Changes
                    </button>
                    <button type="button" onclick="closeEditProfileModal()" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors font-medium cursor-pointer">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Sidebar toggle functionality & Smooth Navigation
document.addEventListener('DOMContentLoaded', function() {
    // --- Notification Badge Logic ---
    async function updateNotificationBadge() {
        try {
            const badge = document.getElementById('navNotificationBadge');
            if(!badge) return;
            
            const response = await fetch('/OSAS-SIS/backend/notifications/api.php?mode=count&t=' + new Date().getTime());
            const result = await response.json();
            
            if (result.success && result.data.count > 0) {
                badge.textContent = result.data.count > 99 ? '99+' : result.data.count;
                badge.className = 'absolute -top-1.5 -right-2.5 flex h-4 w-4 items-center justify-center rounded-full bg-red-500 text-[9px] font-bold text-white ring-1 ring-[#800020]';
                badge.classList.remove('hidden');
            } else {
                badge.classList.add('hidden');
            }
        } catch (error) {
            console.error('Failed to update notifications:', error);
        }
    }
    
    // Initial check
    updateNotificationBadge();
    
    // Periodically check every 2 seconds for real-time feel
    setInterval(updateNotificationBadge, 2000);
    
    // Listen for updates from other parts of the app
    window.addEventListener('notificationsUpdated', updateNotificationBadge);
    const sidebar = document.getElementById('sidebar');
    const sidebarToggle = document.getElementById('sidebarToggle');
    const spaMarker = document.getElementById('spaContentMarker');
    let spaContainer = document.getElementById('spaContentContainer');

    if (spaMarker && !spaContainer) {
        spaContainer = document.createElement('div');
        spaContainer.id = 'spaContentContainer';

        const nodesToMove = [];
        let node = spaMarker.nextSibling;
        while (node) {
            nodesToMove.push(node);
            node = node.nextSibling;
        }
        nodesToMove.forEach(n => spaContainer.appendChild(n));

        spaMarker.parentNode.insertBefore(spaContainer, spaMarker.nextSibling);
    }

    // Try to find the main content wrapper
    const getMainContent = () => {
        if (spaContainer) {
            return spaContainer.querySelector('.ml-64') || spaContainer.querySelector('main');
        }
        return document.querySelector('.ml-64') || document.querySelector('main');
    };

    // --- Dropdown Functionality ---
    const dropdownTriggers = document.querySelectorAll('.nav-dropdown-trigger');
    
    // Helper function to save open dropdowns to localStorage
    function saveDropdownStates() {
        const openDropdowns = [];
        document.querySelectorAll('.nav-dropdown-trigger.active').forEach(trigger => {
            openDropdowns.push(trigger.getAttribute('data-dropdown'));
        });
        localStorage.setItem('openDropdowns', JSON.stringify(openDropdowns));
    }
    
    dropdownTriggers.forEach(trigger => {
        trigger.addEventListener('click', (e) => {
            e.preventDefault();
            const dropdownId = trigger.getAttribute('data-dropdown');
            const dropdown = document.getElementById(dropdownId);
            const isActive = trigger.classList.contains('active');
            
            // Toggle current dropdown (no auto-close of other dropdowns)
            if (isActive) {
                trigger.classList.remove('active');
                dropdown.classList.remove('show');
            } else {
                trigger.classList.add('active');
                dropdown.classList.add('show');
            }
            
            // Save state to localStorage
            saveDropdownStates();
        });
    });

    // Auto-open dropdown if a child link is active
    const currentPage = window.location.pathname.split('/').pop();
    document.querySelectorAll('.nav-subitem').forEach(link => {
        const href = link.getAttribute('href');
        if (href === currentPage) {
            // Find parent dropdown
            const dropdown = link.closest('.dropdown-content');
            if (dropdown) {
                const trigger = document.querySelector(`[data-dropdown="${dropdown.id}"]`);
                if (trigger) {
                    trigger.classList.add('active');
                    dropdown.classList.add('show');
                }
            }
        }
    });

    // Restore dropdown states from localStorage
    const savedDropdowns = localStorage.getItem('openDropdowns');
    if (savedDropdowns) {
        try {
            const openDropdowns = JSON.parse(savedDropdowns);
            openDropdowns.forEach(dropdownId => {
                const dropdown = document.getElementById(dropdownId);
                const trigger = document.querySelector(`[data-dropdown="${dropdownId}"]`);
                if (dropdown && trigger) {
                    trigger.classList.add('active');
                    dropdown.classList.add('show');
                }
            });
        } catch (e) {
            // If there's an error parsing, just continue
            console.error('Error parsing dropdown states:', e);
        }
    }
    
    // Save initial state after auto-open
    saveDropdownStates();

    // --- Sidebar Persistence ---
    const savedState = localStorage.getItem('sidebarState');
    if (savedState === 'collapsed') {
        sidebar.classList.add('collapsed');
        const content = getMainContent();
        if (content) content.style.marginLeft = '4.5rem';
    }

    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', () => {
            sidebar.classList.toggle('collapsed');
            const isCollapsed = sidebar.classList.contains('collapsed');
            localStorage.setItem('sidebarState', isCollapsed ? 'collapsed' : 'expanded');
            
            const content = getMainContent();
            if (content) {
                if (!content.classList.contains('transition-all')) {
                    content.classList.add('transition-all', 'duration-300');
                }
                content.style.marginLeft = isCollapsed ? '4.5rem' : '16rem';
            }
        });
    }

    // --- Smooth Navigation (SPA behavior) ---
    const navLinks = document.querySelectorAll('.nav-item');
    // Detect if current page is a CMS route; on CMS pages we always use full page loads
    const isOnCmsRoute = window.location.pathname.includes('/CMS/');
    
    // Active State Classes
    const activeClasses = ['bg-white/10', 'text-white', 'font-medium'];
    const inactiveClasses = ['text-white/80', 'hover:text-white', 'hover:bg-white/10'];

    function setActiveLink(targetLink) {
        navLinks.forEach(link => {
            link.classList.remove(...activeClasses);
            link.classList.add(...inactiveClasses);
        });
        if (targetLink) {
            targetLink.classList.remove(...inactiveClasses);
            targetLink.classList.add(...activeClasses);
        }
    }

    function extractSpaHtml(doc) {
        const marker = doc.getElementById('spaContentMarker');
        if (!marker) return null;
        const temp = doc.createElement('div');
        let n = marker.nextSibling;
        while (n) {
            temp.appendChild(n.cloneNode(true));
            n = n.nextSibling;
        }
        return temp.innerHTML;
    }

    function ensureHeadAssets(doc) {
        const added = [];

        const styles = doc.querySelectorAll('head link[rel="stylesheet"][href]');
        styles.forEach(link => {
            const href = link.getAttribute('href');
            if (!href) return;
            if (document.head.querySelector(`link[rel="stylesheet"][href="${href}"]`)) return;
            const newLink = document.createElement('link');
            Array.from(link.attributes).forEach(attr => newLink.setAttribute(attr.name, attr.value));
            document.head.appendChild(newLink);
        });

        const scripts = doc.querySelectorAll('head script[src]');
        scripts.forEach(script => {
            const src = script.getAttribute('src');
            if (!src) return;
            if (document.querySelector(`script[src="${src}"]`)) return;
            const newScript = document.createElement('script');
            Array.from(script.attributes).forEach(attr => newScript.setAttribute(attr.name, attr.value));
            const p = new Promise((resolve, reject) => {
                newScript.onload = resolve;
                newScript.onerror = reject;
            });
            document.head.appendChild(newScript);
            added.push(p);
        });

        return Promise.allSettled(added);
    }

    async function navigateTo(url) {
        try {
            // Show loading state if desired (optional)
            // document.body.style.cursor = 'wait';

            const response = await fetch(url);
            if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
            const html = await response.text();
            
            // Parse the new page
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            
            await ensureHeadAssets(doc);

            const spaHtml = extractSpaHtml(doc);
            const currentSpaContainer = document.getElementById('spaContentContainer');
            const currentContent = getMainContent();

            if (spaHtml !== null && currentSpaContainer) {
                // Update Document Title
                document.title = doc.title;

                currentSpaContainer.innerHTML = spaHtml;

                // Re-execute scripts in the new content
                const scripts = currentSpaContainer.querySelectorAll('script');
                scripts.forEach(oldScript => {
                    const newScript = document.createElement('script');
                    Array.from(oldScript.attributes).forEach(attr => newScript.setAttribute(attr.name, attr.value));
                    newScript.appendChild(document.createTextNode(oldScript.innerHTML));
                    oldScript.parentNode.replaceChild(newScript, oldScript);
                });
                
                // Trigger page-specific animations after content load
                setTimeout(() => {
                    // Re-scan for icons (fix for missing buttons on dynamic load)
                    if (window.Iconify && window.Iconify.scan) {
                        window.Iconify.scan();
                    }

                    // Trigger inventory page animations if function exists
                    if (typeof window.triggerInventoryAnimations === 'function') {
                        window.triggerInventoryAnimations();
                    }
                    
                    // Initialize borrow page event listeners if function exists
                    if (typeof window.initBorrowPage === 'function') {
                        window.initBorrowPage();
                    }
                    
                    // Initialize inventory page event listeners if function exists
                    if (typeof window.initInventoryPage === 'function') {
                        window.initInventoryPage();
                    }

                    // Initialize dashboard if function exists
                    if (typeof window.initDashboard === 'function') {
                        window.initDashboard();
                    }

                    // CMS Specific initializations
                    if (typeof window.initCMSPapers === 'function') {
                        window.initCMSPapers();
                    }
                    if (typeof window.initCMSDashboard === 'function') {
                        window.initCMSDashboard();
                    }
                    if (typeof window.initCMSCabinetView === 'function') {
                        window.initCMSCabinetView();
                    }
                }, 100);

                // Ensure main content margin matches sidebar state
                const isCollapsed = sidebar.classList.contains('collapsed');
                const updatedMain = getMainContent();
                if (updatedMain) updatedMain.style.marginLeft = isCollapsed ? '4.5rem' : '16rem';
            } else if (currentContent) {
                // Fallback for older pages without marker
                const newContent = doc.querySelector('.ml-64') || doc.querySelector('main');
                if (newContent) {
                    document.title = doc.title;
                    currentContent.innerHTML = newContent.innerHTML;

                    const scripts = currentContent.querySelectorAll('script');
                    scripts.forEach(oldScript => {
                        const newScript = document.createElement('script');
                        Array.from(oldScript.attributes).forEach(attr => newScript.setAttribute(attr.name, attr.value));
                        newScript.appendChild(document.createTextNode(oldScript.innerHTML));
                        oldScript.parentNode.replaceChild(newScript, oldScript);
                    });

                    setTimeout(() => {
                        if (window.Iconify && window.Iconify.scan) {
                            window.Iconify.scan();
                        }
                        if (typeof window.triggerInventoryAnimations === 'function') {
                            window.triggerInventoryAnimations();
                        }
                        if (typeof window.initBorrowPage === 'function') {
                            window.initBorrowPage();
                        }
                        if (typeof window.initInventoryPage === 'function') {
                            window.initInventoryPage();
                        }
                        if (typeof window.initDashboard === 'function') {
                            window.initDashboard();
                        }
                        if (typeof window.initNotificationPage === 'function') {
                            window.initNotificationPage();
                        }
                    }, 100);

                    const isCollapsed = sidebar.classList.contains('collapsed');
                    currentContent.style.marginLeft = isCollapsed ? '4.5rem' : '16rem';
                } else {
                    window.location.href = url;
                }
            } else {
                window.location.href = url;
            }
        } catch (error) {
            console.error('Navigation error:', error);
            window.location.href = url; // Fallback to full reload
        } finally {
            // document.body.style.cursor = 'default';
        }
    }

    navLinks.forEach(link => {
        link.addEventListener('click', (e) => {
            const href = link.getAttribute('href');
            
            // On CMS pages, always use normal navigation (no SPA) to keep layout consistent
            if (isOnCmsRoute) {
                return;
            }
            
            // Ignore links that are:
            // 1. Just '#'
            // 2. JavaScript calls
            // 3. Logout (has onclick)
            // 4. External links (start with http) or separate targets
            if (!href || href === '#' || href.startsWith('javascript:') || link.hasAttribute('onclick') || link.target) {
                return;
            }

            // Pages that need full reload due to complex JavaScript interactions
            // Only keep history.php (and return.php if used) as full reload
            const fullReloadPages = ['return.php', 'history.php'];
            const pageName = href.split('/').pop();

            // Always do a full page load for CMS routes to keep their layout consistent
            const isCmsRoute = href.includes('/CMS/');
            
            // If this page needs a full reload, don't prevent default
            if (fullReloadPages.includes(pageName) || isCmsRoute) {
                return;
            }

            e.preventDefault();
            
            // Update History
            history.pushState(null, '', href);
            
            // Update UI
            setActiveLink(link);
            
            // Load Content
            navigateTo(href);
        });
    });

    // Handle Browser Back/Forward buttons
    window.addEventListener('popstate', () => {
        const path = window.location.pathname.split('/').pop();
        // Find link matching current path
        const activeLink = Array.from(navLinks).find(link => link.getAttribute('href') === path);
        if (activeLink) setActiveLink(activeLink);
        
        navigateTo(window.location.href);
    });
});

function openEditProfileModal() {
    document.getElementById('editProfileModal').classList.remove('hidden');
}

function closeEditProfileModal() {
    document.getElementById('editProfileModal').classList.add('hidden');
}

function previewImage(event) {
    const file = event.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const preview = document.getElementById('profilePreview');
            preview.innerHTML = `<img src="${e.target.result}" class="w-20 h-20 rounded-full object-cover border-2 border-gray-300">`;
        }
        reader.readAsDataURL(file);
    }
}

// Handle form submission
document.getElementById('editProfileForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    try {
        const response = await fetch('<?= $base_path ?>../backend/profile/update_profile.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            location.reload();
        } else {
            alert('Error: ' + result.message);
        }
    } catch (error) {
        alert('An error occurred while updating profile');
    }
});

function confirmLogout(event) {
    event.preventDefault();
    
    Swal.fire({
        title: 'Are you sure?',
        text: "You will be logged out of your session.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#800020',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Yes, logout',
        cancelButtonText: 'Cancel',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = '<?= $base_path ?>pages/logout.php';
        }
    });
}
</script>

<!-- Ensure SweetAlert2 is loaded if not already -->
<script>
    if (typeof Swal === 'undefined') {
        document.write('<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"><\/script>');
    }
</script>

<div id="spaContentMarker" style="display:none"></div>
