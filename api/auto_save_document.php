<?php
require_once '../includes/functions.php';
requireLogin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['auto_save'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

try {
    $document_id = isset($_POST['document_id']) ? (int)$_POST['document_id'] : 0;
    $title = sanitize($_POST['title'] ?? '');
    $description = sanitize($_POST['description'] ?? '');
    $category_id = isset($_POST['category_id']) ? (int)$_POST['category_id'] : null;
    $tags = sanitize($_POST['tags'] ?? '');
    $is_public = isset($_POST['is_public']) ? 1 : 0;
    
    if ($document_id <= 0 || empty($title)) {
        echo json_encode(['success' => false, 'message' => 'Invalid data']);
        exit;
    }
    
    // Verify document ownership
    $stmt = $db->prepare("SELECT id FROM documents WHERE id = ? AND user_id = ?");
    $stmt->execute([$document_id, $_SESSION['user_id']]);
    
    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Document not found']);
        exit;
    }
    
    // Save draft (you could create a separate drafts table or add a draft column)
    $stmt = $db->prepare("
        UPDATE documents 
        SET title = ?, description = ?, category_id = ?, tags = ?, is_public = ?
        WHERE id = ? AND user_id = ?
    ");
    
    $result = $stmt->execute([
        $title,
        $description,
        $category_id ?: null,
        $tags,
        $is_public,
        $document_id,
        $_SESSION['user_id']
    ]);
    
    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Draft saved']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to save draft']);
    }
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
?>



