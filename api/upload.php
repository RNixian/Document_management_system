<?php
header('Content-Type: application/json');
require_once '../includes/functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Please log in to upload files.']);
    exit();
}

// Check if files were uploaded
if (!isset($_FILES['files']) || empty($_FILES['files']['name'][0])) {
    echo json_encode(['success' => false, 'message' => 'No files were selected.']);
    exit();
}

try {
    $uploadDir = '../uploads/';
    
    // Create uploads directory if it doesn't exist
    if (!file_exists($uploadDir)) {
        if (!mkdir($uploadDir, 0777, true)) {
            echo json_encode(['success' => false, 'message' => 'Failed to create upload directory.']);
            exit();
        }
    }
    
    // Check if directory is writable
    if (!is_writable($uploadDir)) {
        echo json_encode(['success' => false, 'message' => 'Upload directory is not writable.']);
        exit();
    }
    
    $uploadedFiles = [];
    $errors = [];
    
    // Get form data
    $category = isset($_POST['category']) && $_POST['category'] !== '' ? (int)$_POST['category'] : null;
    $visibility = isset($_POST['visibility']) ? (int)$_POST['visibility'] : 0;
    $tags = isset($_POST['tags']) ? sanitize($_POST['tags']) : '';
    $description = isset($_POST['description']) ? sanitize($_POST['description']) : '';
    
    // Process each file
    $fileCount = count($_FILES['files']['name']);
    
    for ($i = 0; $i < $fileCount; $i++) {
        // Skip if no file
        if (empty($_FILES['files']['name'][$i])) {
            continue;
        }
        
        $fileName = $_FILES['files']['name'][$i];
        $fileTmpName = $_FILES['files']['tmp_name'][$i];
        $fileSize = $_FILES['files']['size'][$i];
        $fileError = $_FILES['files']['error'][$i];
        $mimeType = $_FILES['files']['type'][$i];
        
        // Check for upload errors
        if ($fileError !== UPLOAD_ERR_OK) {
            $errors[] = "Error uploading $fileName: Upload error code $fileError";
            continue;
        }
        
        // Check if temp file exists
        if (!file_exists($fileTmpName)) {
            $errors[] = "Temporary file does not exist for $fileName";
            continue;
        }
        
        // Validate file size (500MB max)
        $maxFileSize = 500 * 1024 * 1024;
        if ($fileSize > $maxFileSize) {
            $errors[] = "File $fileName is too large. Maximum size is 500MB.";
            continue;
        }
        
        // Get file extension
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        
        // Validate file type
        $allowedExtensions = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'jpg', 'jpeg', 'png', 'gif', 'zip', 'rar', 'mp4', 'mp3'];
        
        if (!in_array($fileExtension, $allowedExtensions)) {
            $errors[] = "File $fileName has an unsupported format ($fileExtension).";
            continue;
        }
        
        // Generate unique filename
        $uniqueFileName = uniqid() . '_' . time() . '.' . $fileExtension;
        $filePath = $uploadDir . $uniqueFileName;
        
        // Move uploaded file
        if (move_uploaded_file($fileTmpName, $filePath)) {
            // Insert into database using your exact table structure
            try {
                $sql = "INSERT INTO documents (
                    title, 
                    description, 
                    filename, 
                    original_name, 
                    file_size, 
                    file_type, 
                    mime_type, 
                    category_id, 
                    user_id, 
                    downloads, 
                    is_public, 
                    tags
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                
                $stmt = $db->prepare($sql);
                $result = $stmt->execute([
                    pathinfo($fileName, PATHINFO_FILENAME), // title
                    $description,                           // description
                    $uniqueFileName,                        // filename
                    $fileName,                              // original_name
                    $fileSize,                              // file_size
                    $fileExtension,                         // file_type
                    $mimeType,                              // mime_type
                    $category,                              // category_id (can be null)
                    $_SESSION['user_id'],                   // user_id
                    0,                                      // downloads
                    $visibility,                            // is_public
                    $tags                                   // tags
                ]);
                
                if ($result) {
                    $uploadedFiles[] = [
                        'id' => $db->lastInsertId(),
                        'name' => $fileName,
                        'size' => $fileSize
                    ];
                } else {
                    $errorInfo = $stmt->errorInfo();
                    $errors[] = "Database insert failed for $fileName: " . $errorInfo[2];
                    unlink($filePath); // Delete the uploaded file
                }
                
            } catch (PDOException $e) {
                // Delete the uploaded file if database insert fails
                unlink($filePath);
                $errors[] = "Database error for $fileName: " . $e->getMessage();
                error_log("Upload database error: " . $e->getMessage());
            }
        } else {
            $errors[] = "Failed to move uploaded file: $fileName";
        }
    }
    
    // Prepare response
    if (!empty($uploadedFiles)) {
        $message = count($uploadedFiles) . ' file(s) uploaded successfully.';
        if (!empty($errors)) {
            $message .= ' ' . count($errors) . ' file(s) failed.';
        }
        
        echo json_encode([
            'success' => true,
            'message' => $message,
            'uploaded' => $uploadedFiles,
            'errors' => $errors
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'No files were uploaded successfully.',
            'errors' => $errors
        ]);
    }
    
} catch (Exception $e) {
    error_log("Upload exception: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Upload failed: ' . $e->getMessage()
    ]);
}
?>
