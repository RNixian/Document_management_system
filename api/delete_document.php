<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors in response
ini_set('log_errors', 1);

require_once '../includes/functions.php';
requireLogin();

// Set proper headers
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    // Get and validate input
    $raw_input = file_get_contents('php://input');
    error_log("Delete document raw input: " . $raw_input);
    
    $input = json_decode($raw_input, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        echo json_encode(['success' => false, 'message' => 'Invalid JSON input']);
        exit;
    }
    
    $document_id = isset($input['id']) ? (int)$input['id'] : 0;
    
    if ($document_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid document ID']);
        exit;
    }
    
    error_log("Attempting to delete document ID: " . $document_id . " for user: " . $_SESSION['user_id']);
    
    // Get document info and verify ownership
    $stmt = $db->prepare("SELECT * FROM documents WHERE id = ? AND user_id = ?");
    $stmt->execute([$document_id, $_SESSION['user_id']]);
    $document = $stmt->fetch();
    
    if (!$document) {
        echo json_encode(['success' => false, 'message' => 'Document not found or access denied']);
        exit;
    }
    
    error_log("Document found: " . $document['title'] . " - File: " . $document['file_name']);
    
    // Delete the physical file first
    $file_deleted = true;
    $file_path = '../uploads/' . $document['file_name'];
    
    if (file_exists($file_path)) {
        $file_deleted = unlink($file_path);
        if (!$file_deleted) {
            error_log("Failed to delete physical file: " . $file_path);
        } else {
            error_log("Physical file deleted successfully: " . $file_path);
        }
    } else {
        error_log("Physical file not found: " . $file_path);
    }
    
    // Delete from database
    $stmt = $db->prepare("DELETE FROM documents WHERE id = ? AND user_id = ?");
    $db_result = $stmt->execute([$document_id, $_SESSION['user_id']]);
    $rows_affected = $stmt->rowCount();
    
    error_log("Database deletion result: " . ($db_result ? 'success' : 'failed') . " - Rows affected: " . $rows_affected);
    
    if ($db_result && $rows_affected > 0) {
        $response = [
            'success' => true, 
            'message' => 'Document deleted successfully',
            'file_deleted' => $file_deleted,
            'rows_affected' => $rows_affected
        ];
        error_log("Sending success response: " . json_encode($response));
        echo json_encode($response);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to delete document from database']);
    }
    
} catch (PDOException $e) {
    error_log("Delete document PDO error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    error_log("Delete document general error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()]);
}

// Ensure no extra output
exit;
?>
