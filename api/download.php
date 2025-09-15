<?php
require_once '../includes/functions.php';

// Get document ID
$doc_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($doc_id <= 0) {
    http_response_code(404);
    die('Document not found');
}

try {
    // Get document details
    $stmt = $db->prepare("SELECT * FROM documents WHERE id = ?");
    $stmt->execute([$doc_id]);
    $document = $stmt->fetch();
    
    if (!$document) {
        http_response_code(404);
        die('Document not found');
    }
    
    // Check permissions
    $can_download = false;
    
    if ($document['is_public']) {
        // Public document - anyone can download
        $can_download = true;
    } elseif (isLoggedIn() && $document['user_id'] == $_SESSION['user_id']) {
        // Private document - only owner can download
        $can_download = true;
    }
    
    if (!$can_download) {
        http_response_code(403);
        die('Access denied');
    }
    
    // Check if file exists - FIXED: Use correct path structure
    $file_path = '../uploads/' . $document['filename']; // Changed from 'file_path' to 'filename'
    if (!file_exists($file_path)) {
        http_response_code(404);
        die('File not found on server: ' . $file_path);
    }
    
    // Update download count
    $stmt = $db->prepare("UPDATE documents SET downloads = downloads + 1 WHERE id = ?");
    $stmt->execute([$doc_id]);
    
    // Log download if user is logged in
    if (isLoggedIn()) {
        try {
            // Check if download_logs table exists, if not skip logging
            $stmt = $db->prepare("SHOW TABLES LIKE 'download_logs'");
            $stmt->execute();
            if ($stmt->rowCount() > 0) {
                $stmt = $db->prepare("INSERT INTO download_logs (document_id, user_id, ip_address, downloaded_at) VALUES (?, ?, ?, NOW())");
                $stmt->execute([$doc_id, $_SESSION['user_id'], $_SERVER['REMOTE_ADDR']]);
            }
        } catch (PDOException $e) {
            // Log error but don't stop download
            error_log("Download log error: " . $e->getMessage());
        }
    }
    
    // Set headers for file download
    $file_size = filesize($file_path);
    $file_name = $document['original_name']; // Changed from 'original_filename' to 'original_name'
    
    // Clean output buffer
    if (ob_get_level()) {
        ob_end_clean();
    }
    
    // Set headers
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . $file_name . '"');
    header('Content-Length: ' . $file_size);
    header('Cache-Control: private');
    header('Pragma: private');
    header('Expires: 0');
    
    // Handle range requests for large files
    if (isset($_SERVER['HTTP_RANGE'])) {
        $range = $_SERVER['HTTP_RANGE'];
        $ranges = explode('=', $range);
        $offsets = explode('-', $ranges[1]);
        $offset = intval($offsets[0]);
        $length = intval($offsets[1]) - $offset;
        
        if (!$length) {
            $length = $file_size - $offset;
        }
        
        header('HTTP/1.1 206 Partial Content');
        header('Accept-Ranges: bytes');
        header("Content-Range: bytes $offset-" . ($offset + $length - 1) . "/$file_size");
        header("Content-Length: $length");
        
        $file = fopen($file_path, 'rb');
        fseek($file, $offset);
        echo fread($file, $length);
        fclose($file);
    } else {
        // Regular download
        readfile($file_path);
    }
    
    exit();
    
} catch (PDOException $e) {
    error_log("Download error: " . $e->getMessage());
    http_response_code(500);
    die('Server error');
}
?>
