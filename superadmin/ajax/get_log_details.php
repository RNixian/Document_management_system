<?php
require_once '../../includes/functions.php';
requireLogin();
requireAdmin();

header('Content-Type: application/json');

$log_id = $_GET['id'] ?? 0;

if (!$log_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid log ID']);
    exit;
}

try {
    $stmt = $db->prepare("
        SELECT 
            al.*,
            u.username as admin_username,
            u.full_name as admin_name,
            u.email as admin_email,
            tu.username as target_username,
            tu.full_name as target_name,
            tu.email as target_email
        FROM admin_logs al
        LEFT JOIN users u ON al.admin_id = u.id
        LEFT JOIN users tu ON al.target_user_id = tu.id
        WHERE al.id = ?
    ");
    
    $stmt->execute([$log_id]);
    $log = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$log) {
        echo json_encode(['success' => false, 'message' => 'Log not found']);
        exit;
    }
    
    echo json_encode(['success' => true, 'log' => $log]);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
