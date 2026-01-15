<?php
// Simple test file to check if items exist in database
require_once 'config/db.php';

try {
    echo "<h2>Database Connection Test</h2>";
    
    // Test 1: Count total items
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM items");
    $total = $stmt->fetch()['total'];
    echo "<p><strong>Total items in database:</strong> $total</p>";
    
    // Test 2: Fetch all items
    $stmt = $pdo->query("SELECT * FROM items ORDER BY item_name ASC");
    $items = $stmt->fetchAll();
    
    echo "<p><strong>Items found:</strong> " . count($items) . "</p>";
    
    if (!empty($items)) {
        echo "<table border='1' cellpadding='10'>";
        echo "<tr><th>ID</th><th>Item Name</th><th>Category</th><th>Quantity</th><th>Status</th></tr>";
        foreach ($items as $item) {
            echo "<tr>";
            echo "<td>" . $item['id'] . "</td>";
            echo "<td>" . htmlspecialchars($item['item_name']) . "</td>";
            echo "<td>" . htmlspecialchars($item['category']) . "</td>";
            echo "<td>" . $item['quantity'] . "</td>";
            echo "<td>" . $item['status'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: red;'><strong>No items found!</strong> Please add items in Item Management first.</p>";
    }
    
} catch (PDOException $e) {
    echo "<p style='color: red;'><strong>Database Error:</strong> " . $e->getMessage() . "</p>";
}
?>
