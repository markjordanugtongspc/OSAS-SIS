<?php
/**
 * Files API Endpoint
 * Handles CRUD operations for files
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

// Helper function to generate next cabinet number
function getNextCabinetNumber($pdo, $cabinetId) {
    // Get cabinet prefix (C1, C2, C3, etc.)
    $stmt = $pdo->prepare("SELECT id FROM cabinets WHERE id = ?");
    $stmt->execute([$cabinetId]);
    $cabinet = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$cabinet) {
        return null;
    }
    
    $prefix = 'C' . $cabinetId;
    
    // Get max cabinet number for this cabinet
    // Extract the number after the prefix (e.g., C1.3 -> 3)
    $stmt = $pdo->prepare("
        SELECT MAX(CAST(SUBSTRING(cabinet_number, LENGTH(?) + 2) AS UNSIGNED)) as max_num
        FROM files
        WHERE cabinet_id = ? AND deleted_at IS NULL AND cabinet_number LIKE CONCAT(?, '.%')
    ");
    $stmt->execute([$prefix, $cabinetId, $prefix]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $nextNum = ($row['max_num'] ?? 0) + 1;
    return $prefix . '.' . $nextNum;
}

// TODO: Get current user ID from session
$currentUser = 'Admin'; // TODO: Get from session

try {
    // $pdo is available from config/db.php

    switch ($method) {
        case 'GET':
            // Get files by cabinet_id, single file, or global search
            $cabinetId = isset($_GET['cabinet_id']) ? intval($_GET['cabinet_id']) : null;
            $fileId = isset($_GET['id']) ? intval($_GET['id']) : null;
            $search = isset($_GET['search']) ? trim($_GET['search']) : null;
            // Normalize status filter to be case-insensitive and handle common variants
            $status = isset($_GET['status']) ? strtolower(trim($_GET['status'])) : null;
            if ($status === 'archive') {
                // Allow "archive" from older frontends to behave like "archived"
                $status = 'archived';
            }
            
            if ($fileId) {
                // Get single file
                $stmt = $pdo->prepare("
                    SELECT f.*, c.name as cabinet_name
                    FROM files f
                    LEFT JOIN cabinets c ON f.cabinet_id = c.id
                    WHERE f.id = ? AND f.deleted_at IS NULL
                ");
                $stmt->execute([$fileId]);
                $file = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($file) {
                    sendResponse(true, $file);
                } else {
                    sendResponse(false, null, 'File not found', 404);
                }
            } else if ($cabinetId) {
                // Get files by cabinet_id
                $sql = "
                    SELECT f.*, c.name as cabinet_name
                    FROM files f
                    LEFT JOIN cabinets c ON f.cabinet_id = c.id
                    WHERE f.cabinet_id = ? AND f.deleted_at IS NULL
                ";
                
                $params = [$cabinetId];
                
                // Add status filter if provided
                if ($status && in_array($status, ['available', 'borrowed', 'archived'])) {
                    // Explicit status filter from client (including archived)
                    $sql .= " AND f.status = ?";
                    $params[] = $status;
                } else {
                    // Default behavior: hide archived files unless specifically requested
                    $sql .= " AND f.status <> 'archived'";
                }
                
                if ($search) {
                    $sql .= " AND (f.cabinet_number LIKE ? OR f.filename LIKE ?)";
                    $searchTerm = '%' . $search . '%';
                    $params[] = $searchTerm;
                    $params[] = $searchTerm;
                }
                
                $sql .= " ORDER BY CAST(SUBSTRING(f.cabinet_number, LENGTH(CONCAT('C', f.cabinet_id, '.')) + 1) AS UNSIGNED) ASC";
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                $files = $stmt->fetchAll(PDO::FETCH_ASSOC);
                sendResponse(true, $files);
            } else if ($search) {
                // Global search across all cabinets by filename or cabinet number
                $sql = "
                    SELECT f.*, c.name as cabinet_name
                    FROM files f
                    LEFT JOIN cabinets c ON f.cabinet_id = c.id
                    WHERE f.deleted_at IS NULL
                ";

                $params = [];

                // Apply explicit status filter when provided, otherwise hide archived by default
                if ($status && in_array($status, ['available', 'borrowed', 'archived'])) {
                    $sql .= " AND f.status = ?";
                    $params[] = $status;
                } else {
                    $sql .= " AND f.status <> 'archived'";
                }

                $sql .= " AND (f.cabinet_number LIKE ? OR f.filename LIKE ?)";
                $searchTerm = '%' . $search . '%';
                $params[] = $searchTerm;
                $params[] = $searchTerm;

                $sql .= " ORDER BY f.cabinet_id ASC, CAST(SUBSTRING(f.cabinet_number, LENGTH(CONCAT('C', f.cabinet_id, '.')) + 1) AS UNSIGNED) ASC";

                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                $files = $stmt->fetchAll(PDO::FETCH_ASSOC);

                sendResponse(true, $files);
            } else {
                sendResponse(false, null, 'cabinet_id, id, or search parameter is required', 400);
            }
            break;
            
        case 'POST':
            // Create new file
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($data['cabinet_id']) || !isset($data['filename']) || empty(trim($data['filename']))) {
                sendResponse(false, null, 'cabinet_id and filename are required', 400);
            }
            
            $cabinetId = intval($data['cabinet_id']);
            $filename = trim($data['filename']);
            $description = isset($data['description']) ? trim($data['description']) : null;
            $category = isset($data['category']) ? trim($data['category']) : 'Documents';
            // Normalize status coming from client to lowercase values used in DB
            $status = isset($data['status']) ? strtolower(trim($data['status'])) : 'available';
            
            // Validate status
            if (!in_array($status, ['available', 'borrowed', 'archived'])) {
                $status = 'available';
            }
            
            // Determine cabinet number
            // If client sends cabinet_number, use it; otherwise fall back to auto-generation
            if (isset($data['cabinet_number']) && trim($data['cabinet_number']) !== '') {
                $cabinetNumber = trim($data['cabinet_number']);
                // Optional: enforce max length to match DB column (VARCHAR(20))
                if (strlen($cabinetNumber) > 20) {
                    sendResponse(false, null, 'cabinet_number is too long (max 20 characters)', 400);
                }
            } else {
                // Generate next cabinet number (legacy behavior)
                $cabinetNumber = getNextCabinetNumber($pdo, $cabinetId);
                if (!$cabinetNumber) {
                    sendResponse(false, null, 'Invalid cabinet_id', 400);
                }
            }
            
            $stmt = $pdo->prepare("
                INSERT INTO files (cabinet_id, cabinet_number, filename, description, category, status, added_by, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            
            if ($stmt->execute([$cabinetId, $cabinetNumber, $filename, $description, $category, $status, $currentUser])) {
                $fileId = $pdo->lastInsertId();
                // Fetch the created file
                $stmt = $pdo->prepare("
                    SELECT f.*, c.name as cabinet_name
                    FROM files f
                    LEFT JOIN cabinets c ON f.cabinet_id = c.id
                    WHERE f.id = ?
                ");
                $stmt->execute([$fileId]);
                $file = $stmt->fetch(PDO::FETCH_ASSOC);
                
                sendResponse(true, $file, 'File created successfully', 201);
            } else {
                sendResponse(false, null, 'Failed to create file', 500);
            }
            break;
            
        case 'PUT':
        case 'PATCH':
            // Update file
            $data = json_decode(file_get_contents('php://input'), true);
            $fileId = isset($_GET['id']) ? intval($_GET['id']) : null;
            
            if (!$fileId) {
                sendResponse(false, null, 'File ID is required', 400);
            }
            
            $updates = [];
            $params = [];
            
            if (isset($data['filename'])) {
                $updates[] = "filename = ?";
                $params[] = trim($data['filename']);
            }
            
            if (isset($data['cabinet_number'])) {
                $updates[] = "cabinet_number = ?";
                $cabNum = trim($data['cabinet_number']);
                if (strlen($cabNum) > 20) {
                    sendResponse(false, null, 'cabinet_number is too long (max 20 characters)', 400);
                }
                $params[] = $cabNum;
            }
            
            if (isset($data['description'])) {
                $updates[] = "description = ?";
                $params[] = trim($data['description']);
            }
            
            if (isset($data['category'])) {
                $updates[] = "category = ?";
                $params[] = trim($data['category']);
            }
            
            if (isset($data['status'])) {
                $normalizedStatus = strtolower(trim($data['status']));
                if (in_array($normalizedStatus, ['available', 'borrowed', 'archived'])) {
                    $updates[] = "status = ?";
                    $params[] = $normalizedStatus;
                }
            }

            // Optional: store borrower name when a dedicated borrow_by column exists
            if (isset($data['borrow_by'])) {
                $updates[] = "borrow_by = ?";
                $borrowBy = trim($data['borrow_by']);
                $params[] = $borrowBy !== '' ? $borrowBy : null;
            }
            
            if (empty($updates)) {
                sendResponse(false, null, 'No fields to update', 400);
            }
            
            $updates[] = "updated_at = NOW()";
            
            // Add ID to params for WHERE clause
            $params[] = $fileId;
            
            $sql = "UPDATE files SET " . implode(', ', $updates) . " WHERE id = ? AND deleted_at IS NULL";
            $stmt = $pdo->prepare($sql);
            
            if ($stmt->execute($params)) {
                // Fetch updated file
                $stmt = $pdo->prepare("
                    SELECT f.*, c.name as cabinet_name
                    FROM files f
                    LEFT JOIN cabinets c ON f.cabinet_id = c.id
                    WHERE f.id = ?
                ");
                $stmt->execute([$fileId]);
                $file = $stmt->fetch(PDO::FETCH_ASSOC);
                
                sendResponse(true, $file, 'File updated successfully');
            } else {
                sendResponse(false, null, 'Failed to update file', 500);
            }
            break;
            
        case 'DELETE':
            // Archive file by setting status to 'archived' (no hard delete)
            $fileId = isset($_GET['id']) ? intval($_GET['id']) : null;
            
            if (!$fileId) {
                sendResponse(false, null, 'File ID is required', 400);
            }
            
            $stmt = $pdo->prepare("
                UPDATE files 
                SET status = 'archived',
                    updated_at = NOW()
                WHERE id = ?
            ");
            
            if ($stmt->execute([$fileId])) {
                sendResponse(true, null, 'File archived successfully');
            } else {
                sendResponse(false, null, 'Failed to archive file', 500);
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
