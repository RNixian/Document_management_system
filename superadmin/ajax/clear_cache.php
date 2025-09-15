<?php
require_once '../../includes/functions.php';
requireLogin();
requireAdmin();

header('Content-Type: application/json');

try {
    $cleared = [];
    
    // Clear session files (if using file-based sessions)
    if (ini_get('session.save_handler') === 'files') {
        $sessionPath = session_save_path();
        if ($sessionPath && is_dir($sessionPath)) {
            $files = glob($sessionPath . '/sess_*');
            foreach ($files as $file) {
                if (is_file($file) && time() - filemtime($file) > 3600) { // Only clear old sessions
                    unlink($file);
                    $cleared[] = basename($file);
                }
            }
        }
    }
    
    // Clear temporary files
    $tempDir = '../../temp/';
    if (is_dir($tempDir)) {
        $files = glob($tempDir . '*');
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
                $cleared[] = basename($file);
            }
        }
    }
    
    // Clear any cached thumbnails
    $thumbDir = '../../uploads/thumbnails/';
    if (is_dir($thumbDir)) {
        $files = glob($thumbDir . '*');
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
                $cleared[] = basename($file);
            }
        }
    }
    
    // Log the action
    if (function_exists('logAdminAction')) {
        logAdminAction($_SESSION['user_id'], 'clear_cache', 'Cleared cache files: ' . count($cleared) . ' files');
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Cache cleared successfully',
        'files_cleared' => count($cleared)
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
