<?php
require_once '../includes/functions.php';
requireLogin();
requireAdmin();

header('Content-Type: application/json');

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['document_id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request data']);
    exit;
}

$document_id = (int)$input['document_id'];
$category_id = isset($input['category_id']) && $input['category_id'] !== '' ? (int)$input['category_id'] : null;

try {
    // Check if document exists
    $stmt = $db->prepare("SELECT id FROM documents WHERE id = ?");
    $stmt->execute([$document_id]);
    $document = $stmt->fetch();
    
    if (!$document) {
        echo json_encode(['success' => false, 'message' => 'Document not found']);
        exit;
    }
    
    // Get category name if category_id is provided
    $category_name = null;
    if ($category_id) {
        $stmt = $db->prepare("SELECT name FROM categories WHERE id = ?");
        $stmt->execute([$category_id]);
        $category = $stmt->fetch();
        
        if (!$category) {
            echo json_encode(['success' => false, 'message' => 'Category not found']);
            exit;
        }
        
        $category_name = $category['name'];
    }
    
    // Update document category
    $stmt = $db->prepare("UPDATE documents SET category_id = ? WHERE id = ?");
    $stmt->execute([$category_id, $document_id]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Category updated successfully',
        'category_name' => $category_name
    ]);
    
} catch (PDOException $e) {
    error_log("Update category error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
}
?>
