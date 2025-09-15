<?php
session_start();
require_once 'config/database.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'] ?? 'user';
$document_id = intval($_GET['id'] ?? 0);

if (!$document_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid document ID']);
    exit();
}

try {
    // Get document details
    $stmt = $pdo->prepare("
        SELECT d.*, u.username as uploader_name 
        FROM documents d 
        LEFT JOIN users u ON d.uploaded_by = u.id 
        WHERE d.id = ?
    ");
    $stmt->execute([$document_id]);
    $document = $stmt->fetch();

    if (!$document) {
        echo json_encode(['success' => false, 'message' => 'Document not found']);
        exit();
    }

    // Check permissions
    if ($user_role !== 'superadmin' && !$document['is_public'] && $document['uploaded_by'] != $user_id) {
        echo json_encode(['success' => false, 'message' => 'Access denied']);
        exit();
    }

    $file_path = 'uploads/' . $document['file_name'];
    $file_type = strtolower($document['file_type']);

    // Increment view count
    $stmt = $pdo->prepare("UPDATE documents SET views = COALESCE(views, 0) + 1 WHERE id = ?");
    $stmt->execute([$document_id]);

    $response = [
        'success' => true,
        'title' => $document['title'],
        'file_name' => $document['file_name'],
        'file_size' => formatBytes($document['file_size']),
        'file_type' => $document['file_type'],
        'upload_date' => date('M j, Y g:i A', strtotime($document['upload_date'])),
        'uploader' => $document['uploader_name'],
        'downloads' => number_format($document['downloads'] ?? 0),
        'views' => number_format($document['views'] ?? 0),
        'description' => $document['description'],
        'tags' => $document['tags']
    ];

    // Handle different file types
    switch ($file_type) {
        case 'txt':
        case 'log':
        case 'md':
            if (file_exists($file_path) && filesize($file_path) < 1024 * 1024) { // Max 1MB for text preview
                $content = file_get_contents($file_path);
                $response['type'] = 'text';
                $response['content'] = htmlspecialchars($content);
            } else {
                $response['type'] = 'download';
                $response['content'] = $file_path;
            }
            break;

        case 'pdf':
            if (file_exists($file_path)) {
                $response['type'] = 'pdf';
                $response['content'] = $file_path;
            } else {
                $response['type'] = 'download';
                $response['content'] = $file_path;
            }
            break;

        case 'jpg':
        case 'jpeg':
        case 'png':
        case 'gif':
        case 'webp':
        case 'bmp':
            if (file_exists($file_path)) {
                $response['type'] = 'image';
                $response['content'] = $file_path;
            } else {
                $response['type'] = 'download';
                $response['content'] = $file_path;
            }
            break;

        case 'html':
        case 'htm':
            if (file_exists($file_path) && filesize($file_path) < 512 * 1024) { // Max 512KB for HTML preview
                $content = file_get_contents($file_path);
                $response['type'] = 'html';
                $response['content'] = htmlspecialchars($content);
            } else {
                $response['type'] = 'download';
                $response['content'] = $file_path;
            }
            break;

        case 'css':
        case 'js':
        case 'php':
        case 'py':
        case 'java':
        case 'cpp':
        case 'c':
        case 'h':
        case 'json':
        case 'xml':
        case 'sql':
            if (file_exists($file_path) && filesize($file_path) < 512 * 1024) { // Max 512KB for code preview
                $content = file_get_contents($file_path);
                $response['type'] = 'code';
                $response['content'] = htmlspecialchars($content);
                $response['language'] = $file_type;
            } else {
                $response['type'] = 'download';
                $response['content'] = $file_path;
            }
            break;

        default:
            $response['type'] = 'download';
            $response['content'] = $file_path;
            break;
    }

    echo json_encode($response);

} catch (PDOException $e) {
    error_log("Preview error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
}

function formatBytes($bytes, $precision = 2) {
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    
    for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
        $bytes /= 1024;
    }
    
    return round($bytes, $precision) . ' ' . $units[$i];
}
?>
