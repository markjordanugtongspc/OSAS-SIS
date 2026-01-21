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

// Create inventory_logs table if it doesn't exist
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

// Ensure 'reason' column exists in items table
try {
    $pdo->query("SELECT reason FROM items LIMIT 1");
} catch (PDOException $e) {
    try {
        $pdo->exec("ALTER TABLE items ADD COLUMN reason TEXT DEFAULT NULL");
    } catch (PDOException $ex) {
        // Continue if error
    }
}

$item_id = intval($_POST['item_id']);
$item_name = htmlspecialchars(trim($_POST['item_name']));
$unique_id = htmlspecialchars(trim($_POST['unique_id']));
$category = htmlspecialchars(trim($_POST['category']));
$quantity = intval($_POST['quantity']);
$price = floatval($_POST['price']);
$brand = htmlspecialchars(trim($_POST['brand'] ?? ''));
$color = htmlspecialchars(trim($_POST['color'] ?? ''));
$size = htmlspecialchars(trim($_POST['size'] ?? ''));
$sport = htmlspecialchars(trim($_POST['sport'] ?? ''));
$semester = htmlspecialchars(trim($_POST['semester'] ?? ''));
$school_year = htmlspecialchars(trim($_POST['school_year'] ?? ''));
$location = htmlspecialchars(trim($_POST['location'] ?? ''));
$status = htmlspecialchars(trim($_POST['status']));
$description = htmlspecialchars(trim($_POST['description'] ?? ''));
$reason = htmlspecialchars(trim($_POST['reason'] ?? ''));

// Fetch current item state to check for quantity changes
$stmt = $pdo->prepare("SELECT quantity, item_name FROM items WHERE id = ?");
$stmt->execute([$item_id]);
$currentItem = $stmt->fetch(PDO::FETCH_ASSOC);
$old_quantity = $currentItem ? (int)$currentItem['quantity'] : 0;
$item_name_log = $currentItem ? $currentItem['item_name'] : $item_name;

// Handle image upload (only if new image is provided)
$image_name = null;
$update_image = false;

if (isset($_FILES['item_image']) && $_FILES['item_image']['error'] === UPLOAD_ERR_OK) {
    $upload_dir = '../../frontend/images/items/';
    
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    $file_extension = strtolower(pathinfo($_FILES['item_image']['name'], PATHINFO_EXTENSION));
    $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
    
    if (in_array($file_extension, $allowed_extensions)) {
        $new_filename = 'item_' . time() . '_' . uniqid() . '.' . $file_extension;
        $upload_path = $upload_dir . $new_filename;
        
        if (move_uploaded_file($_FILES['item_image']['tmp_name'], $upload_path)) {
            $image_name = $new_filename;
            $update_image = true;
        }
    }
}

try {
    if ($update_image) {
        $sql = "UPDATE items SET item_name = :item_name, unique_id = :unique_id, category = :category, 
                quantity = :quantity, price = :price, brand = :brand, color = :color, size = :size, sport = :sport, semester = :semester, school_year = :school_year, location = :location, 
                status = :status, description = :description, reason = :reason, image = :image WHERE id = :id";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':item_name' => $item_name,
            ':unique_id' => $unique_id,
            ':category' => $category,
            ':quantity' => $quantity,
            ':price' => $price,
            ':brand' => $brand,
            ':color' => $color,
            ':size' => $size,
            ':sport' => $sport,
            ':semester' => $semester,
            ':school_year' => $school_year,
            ':location' => $location,
            ':status' => $status,
            ':description' => $description,
            ':reason' => $reason,
            ':image' => $image_name,
            ':id' => $item_id
        ]);
    } else {
        $sql = "UPDATE items SET item_name = :item_name, unique_id = :unique_id, category = :category, 
                quantity = :quantity, price = :price, brand = :brand, color = :color, size = :size, sport = :sport, semester = :semester, school_year = :school_year, location = :location, 
                status = :status, description = :description, reason = :reason WHERE id = :id";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':item_name' => $item_name,
            ':unique_id' => $unique_id,
            ':category' => $category,
            ':quantity' => $quantity,
            ':price' => $price,
            ':brand' => $brand,
            ':color' => $color,
            ':size' => $size,
            ':sport' => $sport,
            ':semester' => $semester,
            ':school_year' => $school_year,
            ':location' => $location,
            ':status' => $status,
            ':description' => $description,
            ':reason' => $reason,
            ':id' => $item_id
        ]);
    }
    
    // Log if quantity reduced
    if ($quantity < $old_quantity) {
        // Fetch current item image if not updated
        $itemImageForLog = $image_name;
        if (!$update_image) {
            $imgStmt = $pdo->prepare("SELECT image FROM items WHERE id = ?");
            $imgStmt->execute([$item_id]);
            $row = $imgStmt->fetch();
            $itemImageForLog = $row['image'] ?? null;
        }

        $logSql = "INSERT INTO inventory_logs (user_id, item_id, item_name, action, reason, old_quantity, new_quantity, image) 
                   VALUES (:user_id, :item_id, :item_name, 'Quantity Reduced', :reason, :old_quantity, :new_quantity, :image)";
        $logStmt = $pdo->prepare($logSql);
        $logStmt->execute([
            ':user_id' => $_SESSION['user_id'],
            ':item_id' => $item_id,
            ':item_name' => $item_name_log,
            ':reason' => $reason ?: 'No reason provided',
            ':old_quantity' => $old_quantity,
            ':new_quantity' => $quantity,
            ':image' => $itemImageForLog
        ]);
    }
    
    // Create Notification
    try {
        // If image wasn't updated, fetch the existing one for the notification
        if (!$update_image) {
            $stmt = $pdo->prepare("SELECT image FROM items WHERE id = ?");
            $stmt->execute([$item_id]);
            $existingItem = $stmt->fetch();
            $image_name = $existingItem['image'] ?? null;
        }

        $notifSql = "INSERT INTO notifications (user_id, role_target, title, message, type, link, image, created_at, status) 
                     VALUES (:user_id, 'All', 'Item Updated', :message, 'SIS', '/OSAS-SIS/frontend/pages/item_management.php', :image, NOW(), 'unread')";
        $notifStmt = $pdo->prepare($notifSql);
        $notifStmt->execute([
            ':user_id' => $_SESSION['user_id'] ?? 0,
            ':message' => "Item '{$item_name}' details have been updated.",
            ':image' => $image_name
        ]);
    } catch (Exception $e) {
        // Ignore notification errors
    }

    echo json_encode(['success' => true, 'message' => 'Item updated successfully']);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
