<?php
session_start();
require_once '../../config/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$item_id = intval($_POST['item_id']);
$reason = isset($_POST['reason']) ? htmlspecialchars(trim($_POST['reason'])) : '';

// Create inventory_logs table if it doesn't exist (safety check)
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

// Add image column if missing
try {
    $pdo->query("SELECT image FROM inventory_logs LIMIT 1");
} catch (PDOException $e) {
    try {
        $pdo->exec("ALTER TABLE inventory_logs ADD COLUMN image VARCHAR(255) NULL");
    } catch (PDOException $ex) {}
}

// Ensure 'deleted_at' column exists in items table
try {
    $pdo->query("SELECT deleted_at FROM items LIMIT 1");
} catch (PDOException $e) {
    try {
        $pdo->exec("ALTER TABLE items ADD COLUMN deleted_at TIMESTAMP NULL DEFAULT NULL");
    } catch (PDOException $ex) {
        // Continue if error
    }
}

try {
    // Get item item details to log
    $stmt = $pdo->prepare("SELECT item_name, image, quantity FROM items WHERE id = :id");
    $stmt->execute([':id' => $item_id]);
    $item = $stmt->fetch();
    
    // Log deletion
    if ($item) {
        $logSql = "INSERT INTO inventory_logs (user_id, item_id, item_name, action, reason, old_quantity, new_quantity, image) 
                   VALUES (:user_id, :item_id, :item_name, 'Item Deleted', :reason, :quantity, 0, :image)";
        $logStmt = $pdo->prepare($logSql);
        $logStmt->execute([
            ':user_id' => $_SESSION['user_id'],
            ':item_id' => $item_id, 
            ':item_name' => $item['item_name'],
            ':reason' => $reason ?: 'No reason provided',
            ':quantity' => $item['quantity'],
            ':image' => $item['image']
        ]);
    }
    
    // Soft Delete the item from database (to preserve FK integrity with borrow_lists)
    $stmt = $pdo->prepare("UPDATE items SET deleted_at = NOW(), status = 'Unavailable', quantity = 0 WHERE id = :id");
    $stmt->execute([':id' => $item_id]);
    
    // We DON'T delete the image file anymore because the item still exists in DB/history
    // if ($item && $item['image']) { ... } has been removed for soft delete safety
    
    // Create Notification
    try {
        if ($item) {
            $notifSql = "INSERT INTO notifications (user_id, role_target, title, message, type, link, image, created_at, status) 
                        VALUES (:user_id, 'All', 'Item Deleted', :message, 'SIS', '/OSAS-SIS/frontend/pages/item_management.php', :image, NOW(), 'unread')";
            $notifStmt = $pdo->prepare($notifSql);
            $notifStmt->execute([
                ':user_id' => $_SESSION['user_id'] ?? 0,
                ':message' => "Item '{$item['item_name']}' has been removed from inventory.",
                ':image' => $item['image']
            ]);
        }
    } catch (Exception $e) {
        // Ignore errors
    }
    
    echo json_encode(['success' => true, 'message' => 'Item deleted successfully']);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
