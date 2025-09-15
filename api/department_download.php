<?php
require_once '../includes/functions.php';
requireLogin();

$id = (int)($_GET['id'] ?? 0);
if (!$id) exit('Invalid');

// Fetch document
$stmt = $db->prepare("SELECT * FROM department_documents WHERE id = ?");
$stmt->execute([$id]);
$doc = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$doc) exit('Not found');

$user_id = $_SESSION['user_id'];

// ✅ Check division access
if (!isAdmin()) {
    $stmt = $db->prepare("
        SELECT 1 
        FROM user_divisions 
        WHERE user_id = ? AND division_id = ?
    ");
    $stmt->execute([$user_id, $doc['division_id']]);
    $hasAccess = $stmt->fetchColumn();

    if (!$hasAccess) {
        exit('Forbidden');
    }
}

// ✅ Serve file securely
if (!file_exists($doc['file_path'])) {
    exit('File not found');
}

header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . basename($doc['original_name']) . '"');
header('Content-Length: ' . filesize($doc['file_path']));
readfile($doc['file_path']);
exit;
