<?php
require_once '../includes/functions.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $doc_id = (int)$_POST['document_id'];
    $shared_to = $_POST['shared_to'] ?? [];

    if (empty($shared_to)) {
        echo json_encode(['success' => false, 'message' => 'No users selected.']);
        exit;
    }

    $stmt = $db->prepare("SELECT user_id FROM documents WHERE id = ?");
    $stmt->execute([$doc_id]);
    $doc = $stmt->fetch();

    if (!$doc || $doc['user_id'] != $_SESSION['user_id']) {
        echo json_encode(['success' => false, 'message' => 'Not allowed.']);
        exit;
    }

    foreach ($shared_to as $user_id) {
        $stmt = $db->prepare("
            INSERT INTO shared_documents (document_id, shared_by, shared_to) 
            VALUES (?, ?, ?)
        ");
        $stmt->execute([$doc_id, $_SESSION['user_id'], $user_id]);
    }

    echo json_encode(['success' => true, 'message' => 'Document shared successfully.']);
}
