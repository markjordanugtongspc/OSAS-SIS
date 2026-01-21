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
                $stmt = $pdo->prepare("SELECT item_name, quantity, image FROM items WHERE id = ?");
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

                // Create Notification
                try {
                    // Ensure image column exists (Migration check)
                    try {
                        $pdo->exec("ALTER TABLE `notifications` ADD COLUMN `image` VARCHAR(255) NULL");
                    } catch (Exception $e) { /* Column likely exists */ }

                    $notifSql = "INSERT INTO `notifications` (`user_id`, `role_target`, `title`, `message`, `type`, `link`, `image`, `created_at`, `status`) 
                                 VALUES (:user_id, 'All', 'New Borrow Request', :message, 'SIS', '/OSAS-SIS/frontend/pages/borrow.php', :image, NOW(), 'unread')";
                    $notifStmt = $pdo->prepare($notifSql);
                    $notifStmt->execute([
                        ':user_id' => $_SESSION['user_id'] ?? 0,
                        ':message' => "{$_POST['borrower_name']} requested to borrow {$item['item_name']} (Qty: {$_POST['quantity']}).",
                        ':image' => $item['image'] ?? null
                    ]);
                } catch (Exception $e) {
                    file_put_contents(__DIR__ . '/error_log.txt', date('Y-m-d H:i:s') . " - Notification Error: " . $e->getMessage() . "\n", FILE_APPEND);
                }

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

                // Create Notification
                try {
                    $stmt = $pdo->prepare("SELECT bl.borrower_name, bl.quantity, i.item_name, i.image 
                                         FROM borrow_lists bl JOIN items i ON bl.item_id = i.id WHERE bl.id = ?");
                    $stmt->execute([$_POST['id']]);
                    $details = $stmt->fetch();
                    
                    if ($details) {
                        $notifSql = "INSERT INTO `notifications` (`user_id`, `role_target`, `title`, `message`, `type`, `link`, `image`, `created_at`, `status`) 
                                     VALUES (:user_id, 'All', 'Borrow Approved', :message, 'SIS', '/OSAS-SIS/frontend/pages/borrow.php', :image, NOW(), 'unread')";
                        $notifStmt = $pdo->prepare($notifSql);
                        $notifStmt->execute([
                            ':user_id' => $_SESSION['user_id'] ?? 0,
                            ':message' => "Borrow request for {$details['item_name']} by {$details['borrower_name']} has been approved.",
                            ':image' => $details['image']
                        ]);
                    }
                } catch (Exception $e) {}

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

                // Create Notification
                try {
                    $stmt = $pdo->prepare("SELECT bl.borrower_name, i.item_name, i.image 
                                         FROM borrow_lists bl JOIN items i ON bl.item_id = i.id WHERE bl.id = ?");
                    $stmt->execute([$_POST['id']]);
                    $details = $stmt->fetch();
                    
                    if ($details) {
                        $notifSql = "INSERT INTO `notifications` (`user_id`, `role_target`, `title`, `message`, `type`, `link`, `image`, `created_at`, `status`) 
                                     VALUES (:user_id, 'All', 'Borrow Rejected', :message, 'SIS', '/OSAS-SIS/frontend/pages/borrow.php', :image, NOW(), 'unread')";
                        $notifStmt = $pdo->prepare($notifSql);
                        $notifStmt->execute([
                            ':user_id' => $_SESSION['user_id'] ?? 0,
                            ':message' => "Borrow request for {$details['item_name']} by {$details['borrower_name']} has been rejected.",
                            ':image' => $details['image']
                        ]);
                    }
                } catch (Exception $e) {}

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

                // Create Notification
                try {
                    $stmt = $pdo->prepare("SELECT bl.borrower_name, i.item_name, i.image 
                                         FROM borrow_lists bl JOIN items i ON bl.item_id = i.id WHERE bl.id = ?");
                    $stmt->execute([$_POST['id']]);
                    $details = $stmt->fetch();
                    
                    if ($details) {
                        $notifSql = "INSERT INTO `notifications` (`user_id`, `role_target`, `title`, `message`, `type`, `link`, `image`, `created_at`, `status`) 
                                     VALUES (:user_id, 'All', 'Item Returned', :message, 'SIS', '/OSAS-SIS/frontend/pages/history.php', :image, NOW(), 'unread')";
                        $notifStmt = $pdo->prepare($notifSql);
                        $notifStmt->execute([
                            ':user_id' => $_SESSION['user_id'] ?? 0,
                            ':message' => "{$details['borrower_name']} has returned {$details['item_name']}.",
                            ':image' => $details['image']
                        ]);
                    }
                } catch (Exception $e) {}

                echo json_encode(['success' => true, 'message' => 'Item marked as returned']);
                break;

            case 'delete':
                if (!isset($_POST['id'])) {
                    echo json_encode(['success' => false, 'message' => 'Missing borrow ID']);
                    exit;
                }

                // Get borrow details before deleting
                $stmt = $pdo->prepare("SELECT item_id, quantity, borrow_status FROM borrow_lists WHERE id = ?");
                $stmt->execute([$_POST['id']]);
                $borrow = $stmt->fetch();

                if (!$borrow) {
                    echo json_encode(['success' => false, 'message' => 'Borrow record not found']);
                    exit;
                }

                // Start transaction
                $pdo->beginTransaction();

                // Only restore quantity if it was Approved (since Pending didn't deduct yet)
                // If it's Returned, the quantity was already restored during return action.
                if ($borrow['borrow_status'] === 'Approved') {
                    // Restore item quantity
                    $stmt = $pdo->prepare("UPDATE items SET quantity = quantity + ? WHERE id = ?");
                    $stmt->execute([$borrow['quantity'], $borrow['item_id']]);

                    // Update item status to Available if it was Unavailable
                    $stmt = $pdo->prepare("UPDATE items SET status = 'Available' WHERE id = ? AND status = 'Unavailable' AND quantity > 0");
                    $stmt->execute([$borrow['item_id']]);
                }
                
                // Remove from return_lists first (Foreign Key Constraint)
                $stmt = $pdo->prepare("DELETE FROM return_lists WHERE borrow_list_id = ?");
                $stmt->execute([$_POST['id']]);
                
                // Remove from user_history_saved if exists
                $stmt = $pdo->prepare("DELETE FROM user_history_saved WHERE borrow_list_id = ?");
                $stmt->execute([$_POST['id']]);

                // Hard delete from borrow_lists
                $stmt = $pdo->prepare("DELETE FROM borrow_lists WHERE id = ?");
                $stmt->execute([$_POST['id']]);

                $pdo->commit();

                echo json_encode(['success' => true, 'message' => 'Borrow record permanently deleted' . ($borrow['borrow_status'] === 'Approved' ? ' and inventory restored' : '')]);
                break;

            case 'get_stats':
                $today = date('Y-m-d');
                
                // Total borrows
                $stmt = $pdo->query("SELECT COUNT(*) as total FROM borrow_lists WHERE deleted_at IS NULL");
                $total = $stmt->fetch()['total'] ?? 0;
                
                // Overdue (Strictly date based, non-returned)
                $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM borrow_lists WHERE due_date < ? AND borrow_status != 'Returned' AND deleted_at IS NULL");
                $stmt->execute([$today]);
                $overdue = $stmt->fetch()['total'] ?? 0;
                
                // Active (Approved + Pending) - Exclude Overdue
                $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM borrow_lists WHERE borrow_status IN ('Pending', 'Approved') AND due_date >= ? AND deleted_at IS NULL");
                $stmt->execute([$today]);
                $active = $stmt->fetch()['total'] ?? 0;
                
                // Approved - Exclude Overdue
                $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM borrow_lists WHERE borrow_status = 'Approved' AND due_date >= ? AND deleted_at IS NULL");
                $stmt->execute([$today]);
                $approved = $stmt->fetch()['total'] ?? 0;
                
                // Rejected
                $stmt = $pdo->query("SELECT COUNT(*) as total FROM borrow_lists WHERE borrow_status = 'Rejected' AND deleted_at IS NULL");
                $rejected = $stmt->fetch()['total'] ?? 0;
                
                // Returned
                $stmt = $pdo->query("SELECT COUNT(*) as total FROM borrow_lists WHERE borrow_status = 'Returned' AND deleted_at IS NULL");
                $returned = $stmt->fetch()['total'] ?? 0;
                
                // Deposits (only for Approved, include overdue as we still hold money)
                $stmt = $pdo->query("SELECT SUM(deposit_money) as total FROM borrow_lists WHERE borrow_status = 'Approved' AND deleted_at IS NULL");
                $deposits = $stmt->fetch()['total'] ?? 0;
                
                echo json_encode(['success' => true, 'data' => [
                    'total' => $total,
                    'approved' => $approved,
                    'rejected' => $rejected,
                    'returned' => $returned,
                    'overdue' => $overdue,
                    'deposits' => $deposits
                ]]);
                break;

            case 'get_details':
                if (!isset($_POST['id'])) {
                    echo json_encode(['success' => false, 'message' => 'Missing ID']);
                    exit;
                }
                $stmt = $pdo->prepare("
                    SELECT bl.*, i.item_name, i.category, i.image,
                           rl.penalty, rl.item_return, rl.receive_by, rl.item_status as return_status
                    FROM borrow_lists bl 
                    LEFT JOIN items i ON bl.item_id = i.id 
                    LEFT JOIN return_lists rl ON bl.id = rl.borrow_list_id
                    WHERE bl.id = ?
                ");
                $stmt->execute([$_POST['id']]);
                $record = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($record) {
                    echo json_encode(['success' => true, 'data' => $record]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Record not found']);
                }
                break;

            case 'save_all_history':
                // Save all current borrow records to history for the logged-in user
                try {
                    $pdo->beginTransaction();

                    // 1. Fetch all borrow records that are not deleted
                    $stmt = $pdo->query("SELECT id FROM borrow_lists WHERE deleted_at IS NULL");
                    $borrowRecords = $stmt->fetchAll(PDO::FETCH_COLUMN);

                    if (empty($borrowRecords)) {
                        $pdo->commit();
                        echo json_encode(['success' => true, 'count' => 0, 'message' => 'No records found to save']);
                        exit;
                    }

                    $savedCount = 0;
                    foreach ($borrowRecords as $borrowId) {
                        // 2. Check if already saved by this user
                        $checkStmt = $pdo->prepare("SELECT 1 FROM user_history_saved WHERE user_id = ? AND borrow_list_id = ?");
                        $checkStmt->execute([$_SESSION['user_id'], $borrowId]);
                        
                        if (!$checkStmt->fetch()) {
                            // 3. Insert into user_history_saved
                            $insertStmt = $pdo->prepare("INSERT INTO user_history_saved (user_id, borrow_list_id, saved_at) VALUES (?, ?, NOW())");
                            $insertStmt->execute([$_SESSION['user_id'], $borrowId]);
                            $savedCount++;
                        }
                    }

                    $pdo->commit();
                    echo json_encode(['success' => true, 'count' => $savedCount, 'message' => "Successfully list saved $savedCount new records"]);

                } catch (Exception $e) {
                    $pdo->rollBack();
                    throw $e;
                }
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
