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

try {
    // Get item image to delete file
    $stmt = $pdo->prepare("SELECT image FROM items WHERE id = :id");
    $stmt->execute([':id' => $item_id]);
    $item = $stmt->fetch();
    
    // Delete the item from database
    $stmt = $pdo->prepare("DELETE FROM items WHERE id = :id");
    $stmt->execute([':id' => $item_id]);
    
    // Delete image file if exists
    if ($item && $item['image']) {
        $image_path = '../../frontend/images/items/' . $item['image'];
        if (file_exists($image_path)) {
            unlink($image_path);
        }
    }
    
    echo json_encode(['success' => true, 'message' => 'Item deleted successfully']);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
