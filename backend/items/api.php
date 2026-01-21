<?php
session_start();
require_once '../../config/db.php';

header('Content-Type: application/json');

// Check authentication
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$mode = $_GET['mode'] ?? 'recent';

try {
    if ($mode === 'recent') {
        $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 5;
        
        $stmt = $pdo->prepare("SELECT item_name, quantity, category, status, image, created_at FROM items ORDER BY created_at DESC LIMIT :limit");
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'data' => $items]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid mode']);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
