<?php
require_once '../includes/functions.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$user_id = $_SESSION['user_id'];
$division_id = isset($_POST['division_id']) ? (int)$_POST['division_id'] : 0;
$title = trim($_POST['title'] ?? '');
$description = trim($_POST['description'] ?? '');
$tags = trim($_POST['tags'] ?? '');
$is_public = isset($_POST['is_public']) ? (int)$_POST['is_public'] : 0;

if (!$division_id || !$title || empty($_FILES['file']) || !is_uploaded_file($_FILES['file']['tmp_name'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

$file = $_FILES['file'];
$upload_dir = '../uploads/department/';
if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
$basename = uniqid('deptdoc_') . '.' . $ext;
$target_path = $upload_dir . $basename;

if (!move_uploaded_file($file['tmp_name'], $target_path)) {
    echo json_encode(['success' => false, 'message' => 'File upload failed']);
    exit;
}

try {
    $stmt = $db->prepare("INSERT INTO department_documents
        (division_id, user_id, title, description, file_path, original_name, file_type, file_size, tags, is_public)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $division_id,
        $user_id,
        $title,
        $description,
        $target_path,
        $file['name'],
        $ext,
        $file['size'],
        $tags,
        $is_public
    ]);
    echo json_encode(['success' => true, 'message' => 'File uploaded successfully']);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'DB error: ' . $e->getMessage()]);
}