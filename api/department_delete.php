<?php
require_once '../includes/functions.php';
requireLogin();

$data = json_decode(file_get_contents('php://input'), true);
$id = (int)($data['id'] ?? 0);

$stmt = $db->prepare("SELECT * FROM department_documents WHERE id = ?");
$stmt->execute([$id]);
$doc = $stmt->fetch();

if (!$doc) {
    echo json_encode(['success' => false, 'message' => 'Not found']);
    exit;
}
if ($doc['user_id'] != $_SESSION['user_id'] && !isAdmin()) {
    echo json_encode(['success' => false, 'message' => 'Forbidden']);
    exit;
}

@unlink($doc['file_path']);
$db->prepare("DELETE FROM department_documents WHERE id = ?")->execute([$id]);
echo json_encode(['success' => true, 'message' => 'Deleted']);