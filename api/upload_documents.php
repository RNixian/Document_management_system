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
    // Check if files were uploaded
    if (!isset($_FILES['files']) || empty($_FILES['files']['name'][0])) {
        throw new Exception('No files were uploaded');
    }

    $category_id = !empty($_POST['category']) ? (int)$_POST['category'] : null;
$division_id = !empty($_POST['division']) ? (int)$_POST['division'] : null;
$is_public = isset($_POST['visibility']) ? (int)$_POST['visibility'] : 0;
$description = isset($_POST['description']) ? trim($_POST['description']) : '';
$tags = isset($_POST['tags']) ? trim($_POST['tags']) : '';
$user_id = $_SESSION['user_id'];

// ✅ Verify that the chosen division actually belongs to this user
if ($division_id !== null) {
    $stmt = $db->prepare("
        SELECT d.id, d.name 
        FROM user_divisions ud
        INNER JOIN division d ON d.id = ud.division_id
        WHERE ud.user_id = ? AND ud.division_id = ?
    ");
    $stmt->execute([$user_id, $division_id]);
    $division = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$division) {
        throw new Exception("You are not allowed to upload to this division.");
    }
}


    // Create uploads directory if it doesn't exist
    $upload_dir = '../uploads/documents/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    $uploaded_files = [];
    $errors = [];

    // Process each file
    for ($i = 0; $i < count($_FILES['files']['name']); $i++) {
        if ($_FILES['files']['error'][$i] !== UPLOAD_ERR_OK) {
            $errors[] = "Error uploading " . $_FILES['files']['name'][$i];
            continue;
        }

        $original_name = $_FILES['files']['name'][$i];
        $file_size = $_FILES['files']['size'][$i];
        $file_tmp = $_FILES['files']['tmp_name'][$i];
        $mime_type = $_FILES['files']['type'][$i];

        // Get file extension
        $file_extension = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));
        
        // Allowed file types
        $allowed_types = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'jpg', 'jpeg', 'png', 'gif'];
        
        if (!in_array($file_extension, $allowed_types)) {
            $errors[] = "File type not allowed: " . $original_name;
            continue;
        }

        // Check file size (50MB limit)
        if ($file_size > 50 * 1024 * 1024) {
            $errors[] = "File too large: " . $original_name;
            continue;
        }

        // Generate unique filename
        $filename = uniqid() . '_' . time() . '.' . $file_extension;
        $file_path = $upload_dir . $filename;

        // Move uploaded file
        if (!move_uploaded_file($file_tmp, $file_path)) {
            $errors[] = "Failed to save file: " . $original_name;
            continue;
        }

        // Get title from filename (remove extension)
        $title = pathinfo($original_name, PATHINFO_FILENAME);

        // Insert into database
        $stmt = $db->prepare("
            INSERT INTO documents (
                title, description, filename, original_name, file_size, 
                file_type, mime_type, category_id, division_id, user_id, 
                is_public, tags, created_at, updated_at
            ) VALUES (
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW()
            )
        ");

        $stmt->execute([
            $title,
            $description,
            $filename,
            $original_name,
            $file_size,
            $file_extension,
            $mime_type,
            $category_id,
            $division_id,
            $user_id,
            $is_public,
            $tags
        ]);

        $uploaded_files[] = [
            'id' => $db->lastInsertId(),
            'title' => $title,
            'filename' => $filename,
            'original_name' => $original_name,
            'file_size' => $file_size
        ];
    }

    // Check results
    if (empty($uploaded_files) && !empty($errors)) {
        throw new Exception('All uploads failed: ' . implode(', ', $errors));
    }

    $response = [
        'success' => true,
        'message' => count($uploaded_files) . ' file(s) uploaded successfully',
        'uploaded_files' => $uploaded_files
    ];

    if (!empty($errors)) {
        $response['warnings'] = $errors;
        $response['message'] .= ' (with some errors)';
    }

    echo json_encode($response);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
