<?php
/**
 * Cabinets API Endpoint
 * Handles CRUD operations for cabinets
 * TODO: Add authentication/session validation
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE');
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

// TODO: Get current user ID from session
// For now, using default user_id = 1 (admin)
$currentUserId = 1;
$currentUser = 'Admin'; // TODO: Get from session

try {
    // $pdo is available from config/db.php

    switch ($method) {
        case 'GET':
            // Get all cabinets or single cabinet
            $cabinetId = isset($_GET['id']) ? intval($_GET['id']) : null;
            
            if ($cabinetId) {
                // Get single cabinet
                $stmt = $pdo->prepare("
                    SELECT c.*, 
                           COUNT(DISTINCT f.id) as file_count
                    FROM cabinets c
                    LEFT JOIN files f ON c.id = f.cabinet_id AND f.deleted_at IS NULL
                    WHERE c.id = ?
                    GROUP BY c.id
                ");
                $stmt->execute([$cabinetId]);
                $cabinet = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($cabinet) {
                    sendResponse(true, $cabinet);
                } else {
                    sendResponse(false, null, 'Cabinet not found', 404);
                }
            } else {
                // Get all cabinets with file counts
                // Check if we should include archived cabinets
                $includeArchived = isset($_GET['include_archived']) && $_GET['include_archived'] === 'true';
                
                if ($includeArchived) {
                    // Get all cabinets including archived
                    $stmt = $pdo->prepare("
                        SELECT c.*, 
                               COUNT(DISTINCT f.id) as file_count,
                               GROUP_CONCAT(f.category SEPARATOR '|||') as categories_list
                        FROM cabinets c
                        LEFT JOIN files f ON c.id = f.cabinet_id AND f.deleted_at IS NULL
                        GROUP BY c.id
                        ORDER BY c.position ASC, c.created_at ASC
                    ");
                } else {
                    // Get only non-archived cabinets
                    $stmt = $pdo->prepare("
                        SELECT c.*, 
                               COUNT(DISTINCT f.id) as file_count,
                               GROUP_CONCAT(f.category SEPARATOR '|||') as categories_list
                        FROM cabinets c
                        LEFT JOIN files f ON c.id = f.cabinet_id AND f.deleted_at IS NULL
                        WHERE c.status != 'archived' OR c.status IS NULL
                        GROUP BY c.id
                        ORDER BY c.position ASC, c.created_at ASC
                    ");
                }
                
                $stmt->execute();
                $cabinets = $stmt->fetchAll(PDO::FETCH_ASSOC);
                sendResponse(true, $cabinets);
            }
            break;
            
        case 'POST':
            // Create new cabinet
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($data['name']) || empty(trim($data['name']))) {
                sendResponse(false, null, 'Cabinet name is required', 400);
            }
            
            $name = trim($data['name']);
            $description = isset($data['description']) ? trim($data['description']) : null;
            $position = isset($data['position']) ? intval($data['position']) : null;
            $status = isset($data['status']) ? $data['status'] : 'active';
            
            // Validate status
            if (!in_array($status, ['active', 'pending', 'archived'])) {
                $status = 'active';
            }

            // Check for duplicate name (active/pending only)
            $stmt = $pdo->prepare("SELECT id FROM cabinets WHERE name = ? AND status != 'archived'");
            $stmt->execute([$name]);
            if ($stmt->fetch()) {
                sendResponse(false, null, 'A cabinet with this name already exists', 409);
            }
            
            $stmt = $pdo->prepare("
                INSERT INTO cabinets (user_id, name, description, position, status, added_by, created_at)
                VALUES (?, ?, ?, ?, ?, ?, NOW())
            ");
            
            if ($stmt->execute([$currentUserId, $name, $description, $position, $status, $currentUser])) {
                $cabinetId = $pdo->lastInsertId();
                // Fetch the created cabinet with file count
                $stmt = $pdo->prepare("
                    SELECT c.*, 
                           COUNT(DISTINCT f.id) as file_count
                    FROM cabinets c
                    LEFT JOIN files f ON c.id = f.cabinet_id AND f.deleted_at IS NULL
                    WHERE c.id = ?
                    GROUP BY c.id
                ");
                $stmt->execute([$cabinetId]);
                $cabinet = $stmt->fetch(PDO::FETCH_ASSOC);
                
                sendResponse(true, $cabinet, 'Cabinet created successfully', 201);
            } else {
                sendResponse(false, null, 'Failed to create cabinet', 500);
            }
            break;
            
        case 'PUT':
        case 'PATCH':
            // Update cabinet
            $data = json_decode(file_get_contents('php://input'), true);
            $cabinetId = isset($_GET['id']) ? intval($_GET['id']) : null;
            
            if (!$cabinetId) {
                sendResponse(false, null, 'Cabinet ID is required', 400);
            }
            
            $updates = [];
            $params = [];
            
            if (isset($data['name'])) {
                $updates[] = "name = ?";
                $params[] = trim($data['name']);
            }
            
            if (isset($data['description'])) {
                $updates[] = "description = ?";
                $params[] = trim($data['description']);
            }
            
            if (isset($data['position'])) {
                $updates[] = "position = ?";
                $params[] = intval($data['position']);
            }
            
            if (isset($data['status']) && in_array($data['status'], ['active', 'pending', 'archived'])) {
                $updates[] = "status = ?";
                $params[] = $data['status'];
            }
            
            if (empty($updates)) {
                sendResponse(false, null, 'No fields to update', 400);
            }
            
            $updates[] = "updated_at = NOW()";
            
            // Add ID to params for WHERE clause
            $params[] = $cabinetId;
            
            $sql = "UPDATE cabinets SET " . implode(', ', $updates) . " WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            
            if ($stmt->execute($params)) {
                // Fetch updated cabinet
                $stmt = $pdo->prepare("
                    SELECT c.*, 
                           COUNT(DISTINCT f.id) as file_count
                    FROM cabinets c
                    LEFT JOIN files f ON c.id = f.cabinet_id AND f.deleted_at IS NULL
                    WHERE c.id = ?
                    GROUP BY c.id
                ");
                $stmt->execute([$cabinetId]);
                $cabinet = $stmt->fetch(PDO::FETCH_ASSOC);
                
                sendResponse(true, $cabinet, 'Cabinet updated successfully');
            } else {
                sendResponse(false, null, 'Failed to update cabinet', 500);
            }
            break;
            
        default:
            sendResponse(false, null, 'Method not allowed', 405);
            break;
    }
} catch (Exception $e) {
    sendResponse(false, null, 'Server error: ' . $e->getMessage(), 500);
}
?>
