<?php
/**
 * Dashboard Stats API Endpoint
 * Returns statistics for the dashboard
 * TODO: Add authentication/session validation
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../../../config/db.php';

// Helper function to send JSON response
function sendResponse($success, $data = null, $message = '', $statusCode = 200) {
    if (ob_get_level()) {
        ob_clean();
    }
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => $success,
        'data' => $data,
        'message' => $message
    ]);
    exit;
}

try {
    // $pdo is available from config/db.php
    
    $stats = [];
    
    // Total files (non-deleted)
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM files WHERE deleted_at IS NULL");
    $stmt->execute();
    $stats['total_files'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Total cabinets (active and pending, not archived)
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM cabinets WHERE status != 'archived' OR status IS NULL");
    $stmt->execute();
    $stats['total_cabinets'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Pending cabinets
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM cabinets WHERE status = 'pending'");
    $stmt->execute();
    $stats['pending_cabinets'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Files by status
    $stmt = $pdo->prepare("SELECT status, COUNT(*) as count FROM files WHERE deleted_at IS NULL GROUP BY status");
    $stmt->execute();
    $statusCounts = ['available' => 0, 'borrowed' => 0, 'archived' => 0];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $statusCounts[$row['status']] = (int)$row['count'];
    }
    $stats['files_by_status'] = $statusCounts;
    
    // Files by category
    $stmt = $pdo->prepare("SELECT category, COUNT(*) as count FROM files WHERE deleted_at IS NULL GROUP BY category");
    $stmt->execute();
    $categoryCounts = ['Documents' => 0, 'Sports' => 0, 'Objects' => 0];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $category = $row['category'] ?: 'Documents';
        if (isset($categoryCounts[$category])) {
            $categoryCounts[$category] = (int)$row['count'];
        } else {
            $categoryCounts['Documents'] += (int)$row['count'];
        }
    }
    $stats['files_by_category'] = $categoryCounts;
    
    // Total archived files (files with deleted_at IS NOT NULL are soft-deleted)
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM files WHERE deleted_at IS NOT NULL OR status = 'archived'");
    $stmt->execute();
    $stats['archived_files'] = (int)$stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Recent documents (latest 4, ordered by created_at DESC)
    $stmt = $pdo->prepare("
        SELECT 
            id,
            filename,
            category,
            status,
            created_at,
            description
        FROM files
        WHERE deleted_at IS NULL
        ORDER BY created_at DESC
        LIMIT 4
    ");
    $stmt->execute();
    $recentDocuments = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $recentDocuments[] = [
            'id' => (int)$row['id'],
            'filename' => $row['filename'],
            'category' => $row['category'] ?: 'Documents',
            'status' => $row['status'] ?: 'available',
            'created_at' => $row['created_at'],
            'description' => $row['description']
        ];
    }
    $stats['recent_documents'] = $recentDocuments;
    
    sendResponse(true, $stats);
} catch (Exception $e) {
    sendResponse(false, null, 'Server error: ' . $e->getMessage(), 500);
}
?>
