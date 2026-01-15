<?php
require_once '../../config/db.php';

header('Content-Type: application/json');

try {
    $filterType = $_GET['type'] ?? 'semester';
    $value = $_GET['value'] ?? '';

    if (empty($value)) {
        throw new Exception("Please provide a value to filter by.");
    }

    $items = [];
    $count = 0;

    if ($filterType === 'date') {
        // Filter by specific date
        $sql = "SELECT item_name, category, quantity, created_at, status, image 
                FROM items 
                WHERE DATE(created_at) = :date_val 
                ORDER BY created_at DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['date_val' => $value]);
    } else {
        // Filter by semester
        $sql = "SELECT item_name, category, quantity, created_at, status, image 
                FROM items 
                WHERE semester = :semester_val 
                ORDER BY created_at DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['semester_val' => $value]);
    }

    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate total quantity
    foreach ($items as $item) {
        $count += $item['quantity'];
    }

    echo json_encode([
        'success' => true,
        'count' => $count,
        'items' => $items
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
