<?php
require_once '../includes/functions.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $doc_id    = (int)($_POST['document_id'] ?? 0);
    $shared_to = $_POST['shared_to'] ?? [];

    if ($doc_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid document.']);
        exit;
    }

    if (empty($shared_to)) {
        echo json_encode(['success' => false, 'message' => 'No users selected.']);
        exit;
    }

    // Fetch document owner safely
    $stmt = $db->prepare("SELECT user_id FROM documents WHERE id = ?");
    $stmt->execute([$doc_id]);
    $doc = $stmt->fetch(PDO::FETCH_ASSOC); // ✅ force assoc

    // Ownership check
    if (!$doc || $doc['user_id'] !== ($_SESSION['user_id'] ?? null)) {
        echo json_encode(['success' => false, 'message' => 'Not allowed.']);
        exit;
    }

    // Share with users
    foreach ($shared_to as $user_id) {
        $stmt = $db->prepare("
            INSERT INTO shared_documents (document_id, shared_by, shared_to)
            VALUES (?, ?, ?)
        ");
        $stmt->execute([$doc_id, $_SESSION['user_id'], $user_id]);
    }

    echo json_encode(['success' => true, 'message' => 'Document shared successfully.']);
}
