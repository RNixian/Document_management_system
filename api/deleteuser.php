<?php
require_once '../includes/functions.php';
requireLogin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    $document_id = isset($input['id']) ? (int)$input['id'] : 0;
    
    if ($document_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid document ID']);
        exit;
    }
    
    // Get document info and verify ownership
    $stmt = $db->prepare("SELECT * FROM documents WHERE id = ? AND user_id = ?");
    $stmt->execute([$document_id, $_SESSION['user_id']]);
    $document = $stmt->fetch();
    
    if (!$document) {
        echo json_encode(['success' => false, 'message' => 'Document not found or access denied']);
        exit;
    }
    
    // Delete the physical file
    $file_path = '../uploads/' . $document['file_name'];
    if (file_exists($file_path)) {
        unlink($file_path);
    }
    
    // Delete from database
    $stmt = $db->prepare("DELETE FROM documents WHERE id = ? AND user_id = ?");
    $result = $stmt->execute([$document_id, $_SESSION['user_id']]);
    
    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Document deleted successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to delete document from database']);
    }
    
} catch (PDOException $e) {
    error_log("Delete document error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
} catch (Exception $e) {
    error_log("Delete document error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred while deleting the document']);
}
?>
