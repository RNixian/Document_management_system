<?php
require_once '../../includes/functions.php';
requireLogin();
requireAdmin();

header('Content-Type: application/json');

try {
    // Get all tables in the database
    $stmt = $db->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $optimized = [];
    foreach ($tables as $table) {
        $stmt = $db->query("OPTIMIZE TABLE `$table`");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $optimized[] = $table;
    }
    
    // Log the action
    if (function_exists('logAdminAction')) {
        logAdminAction($_SESSION['user_id'], 'optimize_database', 'Optimized database tables: ' . implode(', ', $optimized));
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Database optimized successfully',
        'tables' => $optimized
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
