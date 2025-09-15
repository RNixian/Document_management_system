<?php
header('Content-Type: application/json');
require_once '../includes/functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Please log in to delete documents.']);
    exit();
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);
$doc_id = isset($input['id']) ? (int)$input['id'] : 0;

if ($doc_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid document ID.']);
    exit();
}

try {
    // Get document details
    $stmt = $db->prepare("SELECT * FROM documents WHERE id = ?");
    $stmt->execute([$doc_id]);
    $document = $stmt->fetch();
    
    if (!$document) {
        echo json_encode(['success' => false, 'message' => 'Document not found.']);
        exit();
    }
    
    // Check permissions - only owner or admin can delete
    $can_delete = false;
    
    if ($document['user_id'] == $_SESSION['user_id']) {
        // Owner can delete
        $can_delete = true;
    } elseif (isAdmin()) {
        // Admin can delete any document
        $can_delete = true;
    }
    
    if (!$can_delete) {
        echo json_encode(['success' => false, 'message' => 'You do not have permission to delete this document.']);
        exit();
    }
    
    // Delete the physical file
    $file_path = '../uploads/' . $document['filename'];
    if (file_exists($file_path)) {
        if (!unlink($file_path)) {
            error_log("Failed to delete file: " . $file_path);
            // Continue with database deletion even if file deletion fails
        }
    }
    
    // Delete from database
    $stmt = $db->prepare("DELETE FROM documents WHERE id = ?");
    $result = $stmt->execute([$doc_id]);
    
    if ($result) {
        // Also delete any download logs for this document
        try {
            $stmt = $db->prepare("DELETE FROM download_logs WHERE document_id = ?");
            $stmt->execute([$doc_id]);
        } catch (PDOException $e) {
            // Log error but don't fail the deletion
            error_log("Failed to delete download logs: " . $e->getMessage());
        }
        
        echo json_encode([
            'success' => true, 
            'message' => 'Document deleted successfully.'
        ]);
    } else {
        echo json_encode([
            'success' => false, 
            'message' => 'Failed to delete document from database.'
        ]);
    }
    
} catch (PDOException $e) {
    error_log("Delete error: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Database error occurred while deleting document.'
    ]);
}
?>
