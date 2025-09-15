<?php
require_once '../../includes/functions.php';
requireLogin();
requireSuperAdmin(); // Only superadmin can delete logs

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
$log_id = $input['id'] ?? 0;

if (!$log_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid log ID']);
    exit;
}

try {
    // Get log details before deletion for logging
    $stmt = $db->prepare("SELECT * FROM admin_logs WHERE id = ?");
    $stmt->execute([$log_id]);
    $log = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$log) {
        echo json_encode(['success' => false, 'message' => 'Log not found']);
        exit;
    }
    
    // Delete the log
    $stmt = $db->prepare("DELETE FROM admin_logs WHERE id = ?");
    $stmt->execute([$log_id]);
    
    // Log this action
    logAdminAction(
        $_SESSION['user_id'], 
        'delete_log', 
        "Deleted log entry: ID {$log_id}, Action: {$log['action']}, Date: {$log['created_at']}"
    );
    
    echo json_encode(['success' => true, 'message' => 'Log deleted successfully']);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
