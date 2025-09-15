<?php
require_once '../../includes/functions.php';
requireLogin();
requireAdmin();

header('Content-Type: application/json');

try {
    $cleared = [];
    
    // Clear temporary files
    $tempDir = '../../temp';
    if (is_dir($tempDir)) {
        $files = glob($tempDir . '/*');
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
                $cleared[] = basename($file);
            }
        }
    }
    
    // Clear any application-specific cache
    $cacheDir = '../../cache';
    if (is_dir($cacheDir)) {
        $files = glob($cacheDir . '/*');
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
                $cleared[] = basename($file);
            }
        }
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Cache cleared successfully',
        'files_cleared' => count($cleared),
        'files' => $cleared
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
