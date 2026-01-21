<?php
/*
 * Database Connection File
 * Using default XAMPP credentials (root / empty password)
 */

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'osas_db'); // TODO: Create this database in phpMyAdmin if it doesn't exist

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    
    // Set Error Mode to Exception
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Set Default Fetch Mode to Associative Array
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    // Optional: Set character set
    $pdo->exec("set names utf8");

} catch(PDOException $e) {
    // If this is being called from a CMS API endpoint or Notifications API, return JSON instead of HTML
    $requestUri = $_SERVER['REQUEST_URI'] ?? '';
    if (strpos($requestUri, '/backend/CMS/api/') !== false || strpos($requestUri, '/backend/notifications/') !== false) {
        header('Content-Type: application/json');
        http_response_code(500);

        $message = $e->getCode() == 1049
            ? "The database '" . DB_NAME . "' does not exist. Please create it in phpMyAdmin."
            : 'Connection failed: ' . $e->getMessage();

        echo json_encode([
            'success' => false,
            'data' => null,
            'message' => $message
        ]);
        exit;
    }

    // Default HTML error for non-API contexts
    if ($e->getCode() == 1049) {
        die("<strong>Database Error:</strong> The database '" . DB_NAME . "' does not exist. Please create it in phpMyAdmin.");
    }
    die("<strong>Connection Failed:</strong> " . $e->getMessage());
}
