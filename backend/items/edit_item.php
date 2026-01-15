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
$status = htmlspecialchars(trim($_POST['status']));
$description = htmlspecialchars(trim($_POST['description'] ?? ''));

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
                quantity = :quantity, price = :price, brand = :brand, color = :color, size = :size, sport = :sport, semester = :semester, 
                status = :status, description = :description, image = :image WHERE id = :id";
        
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
            ':status' => $status,
            ':description' => $description,
            ':image' => $image_name,
            ':id' => $item_id
        ]);
    } else {
        $sql = "UPDATE items SET item_name = :item_name, unique_id = :unique_id, category = :category, 
                quantity = :quantity, price = :price, brand = :brand, color = :color, size = :size, sport = :sport, semester = :semester, 
                status = :status, description = :description WHERE id = :id";
        
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
            ':status' => $status,
            ':description' => $description,
            ':id' => $item_id
        ]);
    }
    
    echo json_encode(['success' => true, 'message' => 'Item updated successfully']);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
