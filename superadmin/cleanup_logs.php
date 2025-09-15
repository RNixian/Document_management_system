<?php
require_once '../includes/functions.php';
requireLogin();
requireSuperAdmin();

// Delete logs older than specified days
$days_to_keep = $_POST['days'] ?? 90;

try {
    $stmt = $db->prepare("
        DELETE FROM admin_logs 
        WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)
    ");
    $stmt->execute([$days_to_keep]);
    
    $deleted_count = $stmt->rowCount();
    
    // Log this cleanup action
    logAdminAction(
        $_SESSION['user_id'], 
        'cleanup_logs', 
        "Cleaned up {$deleted_count} log entries older than {$days_to_keep} days"
    );
    
    setAlert("Successfully deleted {$deleted_count} old log entries.", 'success');
    
} catch (PDOException $e) {
    setAlert('Error cleaning up logs: ' . $e->getMessage(), 'danger');
}

header('Location: logs.php');
exit();
?>
