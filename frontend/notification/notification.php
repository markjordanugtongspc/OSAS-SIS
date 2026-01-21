<?php
session_start();
$firstname = $_SESSION['firstname'] ?? 'User';
$lastname = $_SESSION['lastname'] ?? '';
$position = $_SESSION['position'] ?? '';

// Include Vite Helper (go up to project root, then into backend)
// Path: frontend/notification/notification.php -> ../../backend/vite_helper.php
$viteHelperPath = __DIR__ . '/../../backend/vite_helper.php';
if (file_exists($viteHelperPath)) {
    require_once $viteHelperPath;
} else {
    // Fallback if path is different (e.g., inside CMS logic)
    require_once __DIR__ . '/../../../backend/vite_helper.php';
}

$basePath = '/OSAS-SIS/frontend/';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="../../frontend/images/spc.png">
    <title>Notifications - DSA OSAS</title>
    
    <!-- Load Styles via Vite -->
    <?php if (function_exists('vite')): ?>
        <?= vite(['frontend/css/styles.css']) ?>
    <?php else: ?>
        <!-- Fallback to manual CSS if vite helper fails -->
        <link href="<?= $basePath ?>css/styles.css" rel="stylesheet">
    <?php endif; ?>

    <!-- Iconify -->
    <script src="https://code.iconify.design/3/3.1.0/iconify.min.js"></script>
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .notification-card {
            transition: all 0.2s ease;
        }
        .notification-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }
        .notification-card.unread {
            border-left: 4px solid #800020;
            background-color: #fffafb;
        }
        .notification-card.read {
            border-left: 4px solid transparent;
            background-color: white;
            opacity: 0.8;
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen font-sans text-gray-900">

    <!-- Sidebar -->
    <?php include '../pages/navbar.php'; ?>

    <!-- Main Content -->
    <div class="ml-64 min-h-screen flex flex-col transition-all duration-300">
        
        <!-- SPA Marker -->
        <div id="spaContentMarker" style="display:none"></div>

        <main class="flex-1 p-6">
            <div class="w-full">
                <!-- Header -->
                <header class="flex items-center justify-between mb-6">
                    <div>
                        <h1 class="text-xl font-bold text-gray-800">Notifications</h1>
                        <p class="text-xs text-gray-500 mt-0.5">Stay updated with system activities</p>
                    </div>
                    
                    <div class="flex items-center gap-2">
                        <button id="markAllReadBtn" class="flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-gray-700 bg-white border border-gray-200 rounded-md hover:bg-gray-50 transition-colors shadow-sm">
                            <span class="iconify" data-icon="mdi:check-all"></span>
                            Mark all read
                        </button>
                        <button id="deleteAllBtn" class="flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-red-600 bg-white border border-red-100 rounded-md hover:bg-red-50 transition-colors shadow-sm">
                            <span class="iconify" data-icon="mdi:trash-can-outline"></span>
                            Delete all
                        </button>
                    </div>
                </header>

                <!-- Filters -->
                <div class="flex items-center gap-1 mb-4 bg-white p-1 rounded-lg border border-gray-200 w-fit">
                    <button class="filter-tab active px-3 py-1.5 text-xs font-medium rounded-md transition-all" data-filter="all">All</button>
                    <button class="filter-tab px-3 py-1.5 text-xs font-medium text-gray-500 hover:text-gray-700 rounded-md transition-all" data-filter="unread">Unread</button>
                    <button class="filter-tab px-3 py-1.5 text-xs font-medium text-gray-500 hover:text-gray-700 rounded-md transition-all" data-filter="SIS">SIS</button>
                    <button class="filter-tab px-3 py-1.5 text-xs font-medium text-gray-500 hover:text-gray-700 rounded-md transition-all" data-filter="CMS">CMS</button>
                </div>

                <!-- List Container -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden min-h-[400px]">
                    <!-- Loading (Initial) -->
                    <div id="loading" class="hidden flex justify-center py-12">
                        <span class="iconify text-2xl text-gray-300 animate-spin" data-icon="mdi:loading"></span>
                    </div>

                    <!-- List -->
                    <div id="notificationList" class="divide-y divide-gray-50">
                        <!-- Items populated by JS -->
                    </div>
                    
                    <!-- Load More -->
                    <div id="loadMoreContainer" class="hidden p-4 text-center border-t border-gray-50 bg-gray-50/50 hover:bg-gray-50 transition-colors cursor-pointer group" onclick="document.getElementById('loadMoreBtn').click()">
                        <button id="loadMoreBtn" class="text-xs font-medium text-gray-500 group-hover:text-gray-800 transition-colors flex items-center justify-center gap-1 mx-auto">
                            <span class="iconify" data-icon="mdi:arrow-down"></span>
                            See previous notifications
                        </button>
                    </div>

                    <!-- Empty State -->
                    <div id="emptyState" class="hidden flex flex-col items-center justify-center py-20 text-center">
                        <div class="p-4 bg-gray-50 rounded-full mb-3">
                            <span class="iconify text-3xl text-gray-300" data-icon="mdi:bell-off-outline"></span>
                        </div>
                        <p class="text-sm font-medium text-gray-900">No notifications</p>
                        <p class="text-xs text-gray-400 mt-1">You're all caught up!</p>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Scripts -->
    <script>
        // Initialize Notification Page Logic
        window.initNotificationPage = () => {
            const list = document.getElementById('notificationList');
            const loading = document.getElementById('loading');
            const emptyState = document.getElementById('emptyState');
            const loadMoreContainer = document.getElementById('loadMoreContainer');
            const loadMoreBtn = document.getElementById('loadMoreBtn');
            const tabs = document.querySelectorAll('.filter-tab');
            const markAllReadBtn = document.getElementById('markAllReadBtn');
            const deleteAllBtn = document.getElementById('deleteAllBtn');
            const mainContainer = document.querySelector('main');
            
            let allNotifications = [];
            let currentFilter = 'all';
            let currentLimit = 15; // Start with a smaller limit to enable "See previous" quickly

            // Fetch Notifications
            async function fetchNotifications(isPolling = false) {
                try {
                    const response = await fetch(`/OSAS-SIS/backend/notifications/api.php?mode=list&limit=${currentLimit}&t=` + new Date().getTime());
                    
                    if (!response.ok) {
                        return; // Silent fail on polling
                    }
                    
                    const result = await response.json();
                    
                    if (result.success) {
                        const newData = Array.isArray(result.data) ? result.data : [];
                        
                        // Update if data changed
                        if (allNotifications.length === 0 || JSON.stringify(newData) !== JSON.stringify(allNotifications)) {
                            allNotifications = newData;
                            renderNotifications();
                        }
                        
                        // Show/Hide Load More Button
                        if (newData.length >= currentLimit) {
                            loadMoreContainer.classList.remove('hidden');
                        } else {
                            loadMoreContainer.classList.add('hidden');
                        }
                        
                        // Trigger navbar badge update
                        window.dispatchEvent(new Event('notificationsUpdated'));
                    }
                } catch (error) {
                    // console.error(error);
                }
            }

            // Load More Action
            loadMoreBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                currentLimit += 20; // Increase limit
                const icon = loadMoreBtn.querySelector('.iconify');
                if(icon) icon.classList.add('animate-bounce'); // Visual feedback
                
                fetchNotifications().then(() => {
                    if(icon) icon.classList.remove('animate-bounce');
                });
            });

            // Time string helper
            function timeStr(dateString) {
                try {
                    const date = new Date(dateString);
                    if (!isNaN(date.getTime())) {
                        const now = new Date();
                        const diffMins = Math.floor((now - date) / 60000);
                        
                        if (diffMins < 1) return 'Just now';
                        if (diffMins < 60) return `${diffMins}m ago`;
                        if (diffMins < 1440) return `${Math.floor(diffMins/60)}h ago`;
                        return date.toLocaleDateString(undefined, { month: 'short', day: 'numeric' });
                    }
                } catch (e) {}
                return '';
            }

            // View Notification Details
            window.viewNotification = (notif) => {
                // Mark as read immediately if unread
                if (notif.status === 'unread') {
                    markAsRead(null, notif.id);
                }

                // Prepare Image
                let imgHtml = '';
                if (notif.image) {
                     const imgPath = notif.type === 'SIS' 
                        ? `/OSAS-SIS/frontend/images/items/${notif.image}`
                        : `/OSAS-SIS/frontend/images/users/${notif.image}`;
                     imgHtml = `<div class="flex justify-center mb-4"><img src="${imgPath}" class="max-w-full h-48 object-contain rounded-lg border border-gray-100 shadow-sm" onerror="this.remove()"></div>`;
                }

                // Determine Icon
                let iconHtml = '';
                if (!notif.image) {
                    const icon = notif.type === 'SIS' ? 'mdi:tshirt-crew-outline' : 'mdi:file-document-outline';
                    const bgClass = notif.type === 'SIS' ? 'bg-blue-100 text-blue-600' : 'bg-amber-100 text-amber-600';
                    iconHtml = `<div class="flex justify-center mb-4"><div class="w-16 h-16 rounded-full ${bgClass} flex items-center justify-center text-3xl"><span class="iconify" data-icon="${icon}"></span></div></div>`;
                }

                // Show Modal
                Swal.fire({
                    html: `
                        <div class="text-left">
                            <h3 class="text-xl font-bold text-gray-900 mb-4">${notif.title}</h3>
                            ${imgHtml || iconHtml}
                            <div class="bg-gray-50 p-4 rounded-lg border border-gray-100 mb-4">
                                <p class="text-sm text-gray-700 whitespace-pre-wrap leading-relaxed text-justify">${notif.message}</p>
                            </div>
                            <div class="flex items-center justify-between text-xs text-gray-400">
                                <span class="flex items-center gap-1"><span class="iconify" data-icon="mdi:clock-outline"></span> ${new Date(notif.created_at).toLocaleString()}</span>
                                <span class="capitalize px-2 py-0.5 rounded bg-gray-100 text-gray-500 border border-gray-200">${notif.type}</span>
                            </div>
                        </div>
                    `,
                    showCloseButton: true,
                    showConfirmButton: !!notif.link,
                    confirmButtonText: 'View Related Page',
                    confirmButtonColor: '#3b82f6',
                    cancelButtonText: 'Close',
                    showCancelButton: true,
                    focusConfirm: false,
                    width: '32rem',
                    customClass: {
                        popup: 'rounded-xl shadow-xl',
                        confirmButton: 'px-6',
                        cancelButton: 'px-6'
                    }
                }).then((result) => {
                    if (result.isConfirmed && notif.link) {
                        window.location.href = notif.link;
                    }
                });
            };

            // Render Notifications matched to Compact Design
            function renderNotifications() {
                const filtered = allNotifications.filter(n => {
                    if (currentFilter === 'all') return true;
                    if (currentFilter === 'unread') return n.status === 'unread';
                    return n.type === currentFilter;
                });

                list.innerHTML = '';

                if (filtered.length === 0) {
                    list.classList.add('hidden');
                    emptyState.classList.remove('hidden');
                    return;
                }

                emptyState.classList.add('hidden');
                list.classList.remove('hidden');

                filtered.forEach(notif => {
                    const el = document.createElement('div');
                    // Compact Row Design
                    el.className = `group flex items-start gap-3 p-3 hover:bg-gray-50 transition-colors cursor-pointer relative ${notif.status === 'unread' ? 'bg-blue-50/30' : 'bg-white'}`;
                    
                    // Icon/Image Logic
                    let displayIcon = '';
                    if (notif.image) {
                         const imgPath = notif.type === 'SIS' 
                            ? `/OSAS-SIS/frontend/images/items/${notif.image}`
                            : `/OSAS-SIS/frontend/images/users/${notif.image}`;
                         displayIcon = `<img src="${imgPath}" class="w-9 h-9 rounded-md object-cover border border-gray-200 shrink-0" onerror="this.onerror=null;this.replaceWith(document.createRange().createContextualFragment('<div class=\'w-9 h-9 rounded-md bg-gray-100 flex items-center justify-center shrink-0 text-gray-400\'><span class=\'iconify\' data-icon=\'mdi:image-off-outline\'></span></div>'))">`;
                    } else {
                        const icon = notif.type === 'SIS' ? 'mdi:tshirt-crew-outline' : 'mdi:file-document-outline';
                        const bgClass = notif.type === 'SIS' ? 'bg-blue-100 text-blue-600' : 'bg-amber-100 text-amber-600';
                        displayIcon = `<div class="w-9 h-9 rounded-md ${bgClass} flex items-center justify-center shrink-0 text-lg">
                                        <span class="iconify" data-icon="${icon}"></span>
                                       </div>`;
                    }

                    el.innerHTML = `
                        ${displayIcon}
                        <div class="flex-1 min-w-0 pt-0.5">
                            <div class="flex justify-between items-start gap-2">
                                <p class="text-sm font-semibold text-gray-900 leading-tight ${notif.status === 'unread' ? 'font-bold' : ''}">${notif.title}</p>
                                <span class="text-[10px] text-gray-400 whitespace-nowrap">${timeStr(notif.created_at)}</span>
                            </div>
                            <p class="text-xs text-gray-600 mt-0.5 line-clamp-2 leading-relaxed">${notif.message}</p>
                        </div>
                        
                        <!-- Actions (Hover) -->
                        <div class="absolute right-2 top-1/2 -translate-y-1/2 flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity bg-white/80 backdrop-blur-sm p-1 rounded-md shadow-sm border border-gray-100">
                             ${notif.status === 'unread' ? `
                            <button class="p-1.5 text-gray-400 hover:text-blue-600 hover:bg-blue-50 rounded" title="Mark as read" onclick="markAsRead(event, ${notif.id})">
                                <span class="iconify w-4 h-4" data-icon="mdi:check-circle-outline"></span>
                            </button>` : ''}
                            <button class="p-1.5 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded" title="Delete" onclick="deleteNotification(event, ${notif.id})">
                                <span class="iconify w-4 h-4" data-icon="mdi:trash-can-outline"></span>
                            </button>
                        </div>
                        
                        <!-- Unread Dot -->
                        ${notif.status === 'unread' ? '<div class="absolute left-0 top-3 bottom-3 w-0.5 bg-blue-500 rounded-r-full"></div>' : ''}
                    `;

                    // Click Handler
                    el.addEventListener('click', (e) => {
                        if (e.target.closest('button')) return; 
                        viewNotification(notif);
                    });

                    list.appendChild(el);
                });
            }

            // Delete Notification
            window.deleteNotification = async (e, id) => {
                if (e) e.stopPropagation();
                
                const result = await Swal.fire({
                    title: 'Delete this notification?',
                    text: "You won't be able to revert this!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Yes, delete it!'
                });

                if (result.isConfirmed) {
                    try {
                        await fetch('/OSAS-SIS/backend/notifications/api.php', {
                            method: 'DELETE',
                            body: JSON.stringify({ id: id })
                        });
                        
                        // Remove locally
                        allNotifications = allNotifications.filter(n => n.id !== id);
                        renderNotifications();
                        window.dispatchEvent(new Event('notificationsUpdated'));
                        
                        Swal.fire({
                            title: 'Deleted!',
                            text: 'Notification has been deleted.',
                            icon: 'success',
                            timer: 1500,
                            showConfirmButton: false
                        });
                    } catch (err) {
                        console.error(err);
                    }
                }
            };
            
            // Delete ALL Notifications
            deleteAllBtn.addEventListener('click', async () => {
                const result = await Swal.fire({
                    title: 'Delete ALL notifications?',
                    text: "This action cannot be undone!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Yes, delete all!'
                });

                if (result.isConfirmed) {
                    try {
                        await fetch('/OSAS-SIS/backend/notifications/api.php', {
                            method: 'DELETE',
                            body: JSON.stringify({ action: 'delete_all' })
                        });
                        
                        allNotifications = [];
                        renderNotifications();
                        window.dispatchEvent(new Event('notificationsUpdated'));
                        
                        Swal.fire({
                            title: 'Deleted!',
                            text: 'All notifications have been cleared.',
                            icon: 'success',
                            timer: 1500,
                            showConfirmButton: false
                        });
                    } catch(err) {
                        console.error(err);
                    }
                }
            });

            // Mark Read Action
            window.markAsRead = async (e, id) => {
                if (e) e.stopPropagation();
                try {
                    await fetch('/OSAS-SIS/backend/notifications/api.php', {
                        method: 'PATCH',
                        body: JSON.stringify({ id: id, action: 'read' }),
                        keepalive: true
                    });
                    
                    const item = allNotifications.find(n => n.id === id);
                    if (item) item.status = 'read';
                    renderNotifications();
                    window.dispatchEvent(new Event('notificationsUpdated'));
                } catch (err) {}
            };

            // Mark All Read
            markAllReadBtn.addEventListener('click', async () => {
                try {
                    await fetch('/OSAS-SIS/backend/notifications/api.php', {
                        method: 'PATCH',
                        body: JSON.stringify({ action: 'read_all' })
                    });
                    allNotifications.forEach(n => n.status = 'read');
                    renderNotifications();
                    window.dispatchEvent(new Event('notificationsUpdated'));
                } catch (err) {}
            });

            // Filter Tabs
            tabs.forEach(tab => {
                tab.addEventListener('click', () => {
                    tabs.forEach(t => {
                        t.classList.remove('active', 'bg-gray-100', 'text-gray-900');
                        t.classList.add('text-gray-500');
                    });
                    tab.classList.add('active', 'bg-gray-100', 'text-gray-900');
                    tab.classList.remove('text-gray-500');
                    
                    currentFilter = tab.dataset.filter;
                    renderNotifications();
                });
            });

            // Style active tab initially
            document.querySelector('.filter-tab[data-filter="all"]').classList.add('bg-gray-100', 'text-gray-900');
            document.querySelector('.filter-tab[data-filter="all"]').classList.remove('text-gray-500');

            // Initial Load
            fetchNotifications();

            // Auto-refresh using Visibility API + Polling
            document.addEventListener('visibilitychange', () => {
                if (document.visibilityState === 'visible') fetchNotifications(true);
            });
            window.notifInterval = setInterval(() => {
                if(document.getElementById('notificationList')) {
                    fetchNotifications(true);
                } else {
                    clearInterval(window.notifInterval);
                }
            }, 2000); 
        };

        // Invoke immediately if document is ready or loading
        if (document.readyState === 'loading') {
             document.addEventListener('DOMContentLoaded', window.initNotificationPage);
        } else {
             window.initNotificationPage();
        }
    </script>
</body>
</html>
