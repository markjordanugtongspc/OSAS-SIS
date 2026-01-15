<?php
session_start();
require_once '../../config/db.php';

header('Content-Type: application/json');

// Security check
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    try {
        switch ($action) {
            case 'create_borrow':
                // Validate required fields (including semester of borrow)
                $required_fields = ['item_id', 'borrower_name', 'borrower_id', 'borrower_course', 
                                   'borrower_year', 'borrower_department', 'contact_number', 
                                   'quantity', 'due_date', 'deposit_money', 'semester', 'school_year'];
                
                foreach ($required_fields as $field) {
                    if (!isset($_POST[$field]) || empty($_POST[$field])) {
                        echo json_encode(['success' => false, 'message' => "Missing required field: $field"]);
                        exit;
                    }
                }

                // Get item details
                $stmt = $pdo->prepare("SELECT item_name, quantity FROM items WHERE id = ?");
                $stmt->execute([$_POST['item_id']]);
                $item = $stmt->fetch();

                if (!$item) {
                    echo json_encode(['success' => false, 'message' => 'Item not found']);
                    exit;
                }

                // Check if quantity is available
                if ($item['quantity'] < $_POST['quantity']) {
                    echo json_encode(['success' => false, 'message' => 'Insufficient quantity available']);
                    exit;
                }

                // Get item description from POST or use item name as fallback
                $item_description = $_POST['item_description'] ?? $item['item_name'];

                // Insert borrow record (with semester)
                $stmt = $pdo->prepare("
                    INSERT INTO borrow_lists (
                        item_id, user_id, borrower_name, borrower_id, borrower_course, 
                        borrower_year, borrower_department, contact_number, item_description,
                        quantity, due_date, deposit_money, semester, school_year, borrow_status
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending')
                ");

                $stmt->execute([
                    $_POST['item_id'],
                    $_SESSION['user_id'],
                    $_POST['borrower_name'],
                    $_POST['borrower_id'],
                    $_POST['borrower_course'],
                    $_POST['borrower_year'],
                    $_POST['borrower_department'],
                    $_POST['contact_number'],
                    $item_description,
                    $_POST['quantity'],
                    $_POST['due_date'],
                    $_POST['deposit_money'],
                    $_POST['semester'],
                    $_POST['school_year']
                ]);

                echo json_encode(['success' => true, 'message' => 'Borrow request created successfully']);
                break;

            case 'approve':
                if (!isset($_POST['id'])) {
                    echo json_encode(['success' => false, 'message' => 'Missing borrow ID']);
                    exit;
                }

                // Get borrow details
                $stmt = $pdo->prepare("SELECT item_id, quantity FROM borrow_lists WHERE id = ? AND deleted_at IS NULL");
                $stmt->execute([$_POST['id']]);
                $borrow = $stmt->fetch();

                if (!$borrow) {
                    echo json_encode(['success' => false, 'message' => 'Borrow record not found']);
                    exit;
                }

                // Check item availability again before approving
                $stmt = $pdo->prepare("SELECT quantity FROM items WHERE id = ?");
                $stmt->execute([$borrow['item_id']]);
                $item = $stmt->fetch();

                if ($item['quantity'] < $borrow['quantity']) {
                    echo json_encode(['success' => false, 'message' => 'Insufficient item quantity. Cannot approve.']);
                    exit;
                }

                // Start transaction
                $pdo->beginTransaction();

                // Update borrow status
                $stmt = $pdo->prepare("UPDATE borrow_lists SET borrow_status = 'Approved', release_by = ? WHERE id = ?");
                $stmt->execute([$_POST['release_by'] ?? '', $_POST['id']]);

                // Deduct quantity (Deduct on Approve)
                $stmt = $pdo->prepare("UPDATE items SET quantity = quantity - ? WHERE id = ?");
                $stmt->execute([$borrow['quantity'], $borrow['item_id']]);

                // Update item status if quantity becomes 0
                $stmt = $pdo->prepare("UPDATE items SET status = 'Unavailable' WHERE id = ? AND quantity = 0");
                $stmt->execute([$borrow['item_id']]);

                $pdo->commit();

                echo json_encode(['success' => true, 'message' => 'Borrow request approved']);
                break;

            case 'reject':
                if (!isset($_POST['id'])) {
                    echo json_encode(['success' => false, 'message' => 'Missing borrow ID']);
                    exit;
                }

                // Just update status, no stock change needed since it wasn't deducted yet
                $stmt = $pdo->prepare("UPDATE borrow_lists SET borrow_status = 'Rejected' WHERE id = ? AND deleted_at IS NULL");
                $stmt->execute([$_POST['id']]);

                echo json_encode(['success' => true, 'message' => 'Borrow request rejected']);
                break;

            case 'return':
                if (!isset($_POST['id'])) {
                    echo json_encode(['success' => false, 'message' => 'Missing borrow ID']);
                    exit;
                }

                // Get borrow details
                $stmt = $pdo->prepare("SELECT item_id, quantity, borrow_status, deposit_money FROM borrow_lists WHERE id = ? AND deleted_at IS NULL");
                $stmt->execute([$_POST['id']]);
                $borrow = $stmt->fetch();

                if (!$borrow) {
                    echo json_encode(['success' => false, 'message' => 'Borrow record not found']);
                    exit;
                }

                if ($borrow['borrow_status'] !== 'Approved') {
                    echo json_encode(['success' => false, 'message' => 'Only approved borrows can be marked as returned']);
                    exit;
                }

                // Validate and clamp return quantity
                $returnQty = isset($_POST['item_quantity_return']) ? (int) $_POST['item_quantity_return'] : (int) $borrow['quantity'];
                if ($returnQty < 0) {
                    $returnQty = 0;
                }
                if ($returnQty > (int) $borrow['quantity']) {
                    $returnQty = (int) $borrow['quantity'];
                }

                $receiveBy = trim($_POST['receive_by'] ?? '');
                $itemReturnDate = $_POST['item_return'] ?? date('Y-m-d');
                $itemStatus = $_POST['item_status'] ?? 'Good Condition';
                $penalty = isset($_POST['penalty']) ? (float) $_POST['penalty'] : 0.0;

                if ($returnQty <= 0) {
                    echo json_encode(['success' => false, 'message' => 'Invalid return quantity']);
                    exit;
                }

                if ($receiveBy === '') {
                    echo json_encode(['success' => false, 'message' => 'Missing receiver name']);
                    exit;
                }

                // Start transaction
                $pdo->beginTransaction();

                // Update borrow status
                $stmt = $pdo->prepare("UPDATE borrow_lists SET borrow_status = 'Returned' WHERE id = ?");
                $stmt->execute([$_POST['id']]);

                // Increase item quantity (Restocking) based on actual return quantity
                $stmt = $pdo->prepare("UPDATE items SET quantity = quantity + ? WHERE id = ?");
                $stmt->execute([$returnQty, $borrow['item_id']]);

                // Update item status to Available if it was Unavailable
                $stmt = $pdo->prepare("UPDATE items SET status = 'Available' WHERE id = ? AND status = 'Unavailable' AND quantity > 0");
                $stmt->execute([$borrow['item_id']]);

                // Record return details in return_lists
                $stmt = $pdo->prepare("INSERT INTO return_lists (
                        item_id,
                        user_id,
                        borrow_list_id,
                        receive_by,
                        item_return,
                        item_status,
                        deposit_money,
                        item_quantity_return,
                        status,
                        penalty
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

                $stmt->execute([
                    $borrow['item_id'],
                    $_SESSION['user_id'],
                    $_POST['id'],
                    $receiveBy,
                    $itemReturnDate,
                    $itemStatus,
                    $borrow['deposit_money'] ?? 0,
                    $returnQty,
                    'Returned',
                    $penalty
                ]);

                $pdo->commit();

                echo json_encode(['success' => true, 'message' => 'Item marked as returned']);
                break;

            case 'delete':
                if (!isset($_POST['id'])) {
                    echo json_encode(['success' => false, 'message' => 'Missing borrow ID']);
                    exit;
                }

                // Get borrow details before deleting
                $stmt = $pdo->prepare("SELECT item_id, quantity, borrow_status FROM borrow_lists WHERE id = ? AND deleted_at IS NULL");
                $stmt->execute([$_POST['id']]);
                $borrow = $stmt->fetch();

                if (!$borrow) {
                    echo json_encode(['success' => false, 'message' => 'Borrow record not found']);
                    exit;
                }

                // Start transaction
                $pdo->beginTransaction();

                // Only restore quantity if it was Approved (since Pending didn't deduct yet)
                if ($borrow['borrow_status'] === 'Approved') {
                    // Restore item quantity
                    $stmt = $pdo->prepare("UPDATE items SET quantity = quantity + ? WHERE id = ?");
                    $stmt->execute([$borrow['quantity'], $borrow['item_id']]);

                    // Update item status to Available if it was Unavailable
                    $stmt = $pdo->prepare("UPDATE items SET status = 'Available' WHERE id = ? AND status = 'Unavailable' AND quantity > 0");
                    $stmt->execute([$borrow['item_id']]);
                }
                
                // Remove from user_history_saved if exists
                $stmt = $pdo->prepare("DELETE FROM user_history_saved WHERE borrow_list_id = ?");
                $stmt->execute([$_POST['id']]);

                // Soft delete
                $stmt = $pdo->prepare("UPDATE borrow_lists SET deleted_at = NOW() WHERE id = ?");
                $stmt->execute([$_POST['id']]);

                $pdo->commit();

                echo json_encode(['success' => true, 'message' => 'Borrow record deleted' . ($borrow['borrow_status'] === 'Approved' ? ' and inventory restored' : '')]);
                break;

            default:
                echo json_encode(['success' => false, 'message' => 'Invalid action']);
                break;
        }
    } catch (PDOException $e) {
        // Rollback transaction if active
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}


