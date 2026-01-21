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

$log_id = intval($_POST['log_id']);

try {
    $stmt = $pdo->prepare("DELETE FROM inventory_logs WHERE id = :id AND user_id = :user_id");
    $stmt->execute([
        ':id' => $log_id,
        ':user_id' => $_SESSION['user_id']
    ]);

    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'Log deleted successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Log not found or unauthorized']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
