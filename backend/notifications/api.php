<?php
/**
 * Notifications API Endpoint
 * Handles fetching and managing notifications for SIS and CMS
 */

// Disable error reporting for production/API safety
error_reporting(E_ALL);
ini_set('display_errors', 0);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // Adjust for production security
header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');

// Adjust path based on location: backend/notifications/api.php -> ../../config/db.php
require_once __DIR__ . '/../../config/db.php';

$method = $_SERVER['REQUEST_METHOD'];

function sendResponse($success, $data = null, $message = '', $statusCode = 200) {
    if (ob_get_level()) ob_clean();
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode(['success' => $success, 'data' => $data, 'message' => $message]);
    exit;
}

// Current User Session (Mock for now, or use session)
session_start();
$userId = $_SESSION['user_id'] ?? 1; // Default to 1 if not logged in
$userRole = $_SESSION['position'] ?? 'Admin'; 

try {
    // Ensure table exists (Auto-migration for demo purposes)
    $pdo->exec("CREATE TABLE IF NOT EXISTS `notifications` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `user_id` INT NULL, 
        `role_target` VARCHAR(50) NULL,
        `title` VARCHAR(100) NOT NULL,
        `message` TEXT NOT NULL,
        `type` ENUM('SIS', 'CMS', 'System') DEFAULT 'System',
        `status` ENUM('unread', 'read') DEFAULT 'unread',
        `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
        `link` VARCHAR(255) NULL,
        `image` VARCHAR(255) NULL
    )");

    // Ensure image column exists (in case table was created before this change)
    try {
        $pdo->exec("ALTER TABLE `notifications` ADD COLUMN `image` VARCHAR(255) NULL AFTER `link` text");
    } catch (Exception $e) {
        // Column may already exist
    }

    switch ($method) {
        case 'GET':
            // Get unread count or list
            $mode = $_GET['mode'] ?? 'list'; // list or count

            if ($mode === 'count') {
                // Get unread count
                $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM `notifications` WHERE `status` = 'unread' AND (`user_id` = ? OR `role_target` = ? OR `role_target` = 'All')");
                $stmt->execute([$userId, $userRole]);
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                sendResponse(true, ['count' => $result['count']]);
            } else {
                // Get list
                $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 20;
                // Get list
                $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 20;
                
                try {
                    $sql = "SELECT * FROM `notifications` 
                            WHERE `user_id` = :uid OR `role_target` = :role OR `role_target` = 'All'
                            ORDER BY `created_at` DESC 
                            LIMIT :limit";
                    
                    $stmt = $pdo->prepare($sql);
                    $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
                    $stmt->bindValue(':role', $userRole, PDO::PARAM_STR);
                    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
                    
                    $stmt->execute();
                    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    if (!$notifications) {
                        $notifications = [];
                    }
                    sendResponse(true, $notifications);
                } catch (PDOException $e) {
                    sendResponse(false, null, 'DB Error: ' . $e->getMessage(), 500);
                }
            }
            break;

        case 'POST':
            // Create notification (Internal use or admin)
            $input = json_decode(file_get_contents('php://input'), true);
            $title = $input['title'] ?? 'Notification';
            $message = $input['message'] ?? '';
            $type = $input['type'] ?? 'System';
            $link = $input['link'] ?? null;
            $targetRole = $input['target_role'] ?? 'All';

            $stmt = $pdo->prepare("INSERT INTO `notifications` (`role_target`, `title`, `message`, `type`, `link`, `created_at`) VALUES (?, ?, ?, ?, ?, NOW())");
            if ($stmt->execute([$targetRole, $title, $message, $type, $link])) {
                sendResponse(true, null, 'Notification created', 201);
            } else {
                sendResponse(false, null, 'Failed to create notification', 500);
            }
            break;

        case 'PATCH':
            // Mark as read
            $input = json_decode(file_get_contents('php://input'), true);
            $id = $input['id'] ?? null;
            $action = $input['action'] ?? 'read'; // read or read_all

            if ($action === 'read_all') {
                $stmt = $pdo->prepare("UPDATE `notifications` SET `status` = 'read' WHERE `status` = 'unread' AND (`user_id` = ? OR `role_target` = ? OR `role_target` = 'All')");
                $stmt->execute([$userId, $userRole]);
                sendResponse(true, null, 'All marked as read');
            } elseif ($id) {
                $stmt = $pdo->prepare("UPDATE `notifications` SET `status` = 'read' WHERE `id` = ?");
                $stmt->execute([$id]);
                sendResponse(true, null, 'Marked as read');
            } else {
                sendResponse(false, null, 'ID required', 400);
            }
            break;

        case 'DELETE':
            // Delete notification
            $input = json_decode(file_get_contents('php://input'), true);
            $id = $input['id'] ?? null;
            $action = $input['action'] ?? null;

            if ($action === 'delete_all') {
                // Delete ALL notifications visible to this user
                $stmt = $pdo->prepare("DELETE FROM `notifications` WHERE (`user_id` = ? OR `role_target` = ? OR `role_target` = 'All')");
                $stmt->execute([$userId, $userRole]);
                sendResponse(true, null, 'All notifications deleted');
            } elseif ($id) {
                $stmt = $pdo->prepare("DELETE FROM `notifications` WHERE `id` = ? AND (`user_id` = ? OR `role_target` = ? OR `role_target` = 'All')");
                // Check ownership/target to prevent deleting others' notifications
                $stmt->execute([$id, $userId, $userRole]);
                if ($stmt->rowCount() > 0) {
                    sendResponse(true, null, 'Notification deleted');
                } else {
                    sendResponse(false, null, 'Notification not found or access denied', 404);
                }
            } else {
                sendResponse(false, null, 'ID required', 400);
            }
            break;
    }
} catch (Exception $e) {
    sendResponse(false, null, $e->getMessage(), 500);
}
