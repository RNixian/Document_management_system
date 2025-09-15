<?php
require_once '../../includes/functions.php';
requireLogin();
requireAdmin();

header('Content-Type: application/json');

try {
    // Get all tables in the database
    $tables = [];
    $stmt = $db->query("SHOW TABLES");
    while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
        $tables[] = $row[0];
    }
    
    $optimized = [];
    foreach ($tables as $table) {
        $stmt = $db->query("OPTIMIZE TABLE `$table`");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $optimized[] = [
            'table' => $table,
            'status' => $result['Msg_text'] ?? 'OK'
        ];
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
