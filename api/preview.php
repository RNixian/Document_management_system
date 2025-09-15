<?php
require_once '../includes/functions.php';
requireLogin();

if (!isset($_GET['id'])) {
    http_response_code(400);
    exit('Document ID required');
}

$document_id = (int)$_GET['id'];

try {
    // Get document info
    $stmt = $db->prepare("
        SELECT d.*, u.username 
        FROM documents d 
        JOIN users u ON d.user_id = u.id 
        WHERE d.id = ?
    ");
    $stmt->execute([$document_id]);
    $document = $stmt->fetch();
    
    if (!$document) {
        http_response_code(404);
        exit('Document not found');
    }
    
    // Check permissions (admin or document owner or public document)
    if ($_SESSION['role'] !== 'admin' && 
        $_SESSION['role'] !== 'superadmin' && 
        $document['user_id'] != $_SESSION['user_id'] && 
        !$document['is_public']) {
        http_response_code(403);
        exit('Access denied');
    }
    
    $file_path = '../uploads/' . $document['file_name'];
    
    if (!file_exists($file_path)) {
        http_response_code(404);
        exit('File not found');
    }
    
    // Set appropriate headers based on file type
    $file_type = strtolower($document['file_type']);
    
    switch ($file_type) {
        case 'pdf':
            header('Content-Type: application/pdf');
            header('Content-Disposition: inline; filename="' . $document['original_name'] . '"');
            break;
        case 'jpg':
        case 'jpeg':
            header('Content-Type: image/jpeg');
            break;
        case 'png':
            header('Content-Type: image/png');
            break;
        case 'gif':
            header('Content-Type: image/gif');
            break;
        case 'txt':
            header('Content-Type: text/plain; charset=utf-8');
            break;
        default:
            http_response_code(415);
            exit('File type not supported for preview');
    }
    
    // Set cache headers
    header('Cache-Control: private, max-age=3600');
    header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 3600) . ' GMT');
    header('Content-Length: ' . filesize($file_path));
    
    // Clear any previous output
    if (ob_get_level()) {
        ob_end_clean();
    }
    
    // Output file content
    readfile($file_path);
    exit;
    
} catch (PDOException $e) {
    error_log("Preview error: " . $e->getMessage());
    http_response_code(500);
    exit('Database error');
} catch (Exception $e) {
    error_log("Preview error: " . $e->getMessage());
    http_response_code(500);
    exit('Server error');
}
?>
