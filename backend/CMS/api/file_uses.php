<?php
/**
 * File Uses API Endpoint
 * Handles GET operations for file_uses table
 * TODO: Add authentication/session validation
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../../../config/db.php';

// Get request method
$method = $_SERVER['REQUEST_METHOD'];

// Helper function to send JSON response
function sendResponse($success, $data = null, $message = '', $statusCode = 200) {
    // Ensure no output before this
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

    switch ($method) {
        case 'GET':
            // Get file uses by cabinet_id
            $cabinetId = isset($_GET['cabinet_id']) ? intval($_GET['cabinet_id']) : null;
            
            if (!$cabinetId) {
                sendResponse(false, null, 'cabinet_id parameter is required', 400);
            }
            
            // Get file uses with joined file information
            $stmt = $pdo->prepare("
                SELECT 
                    fu.*,
                    f.cabinet_number,
                    f.filename,
                    f.description,
                    f.category,
                    f.status as file_status,
                    c.name as cabinet_name
                FROM file_uses fu
                LEFT JOIN files f ON fu.file_id = f.id
                LEFT JOIN cabinets c ON fu.cabinet_id = c.id
                WHERE fu.cabinet_id = ? AND fu.deleted_at IS NULL
                ORDER BY fu.created_at DESC
            ");
            $stmt->execute([$cabinetId]);
            $uses = $stmt->fetchAll(PDO::FETCH_ASSOC);
            sendResponse(true, $uses);
            break;
            
        default:
            sendResponse(false, null, 'Method not allowed', 405);
            break;
    }
} catch (Exception $e) {
    sendResponse(false, null, 'Server error: ' . $e->getMessage(), 500);
}
?>
