<?php
$page_title = 'Manage Documents - Admin';
require_once '../includes/functions.php';
requireLogin();
requireAdmin();

$user_id    = $_SESSION['user_id'] ?? null;
$username   = $_SESSION['username'] ?? 'guest';

// ================== Folder Creation ==================
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['folder_name'])) {
    $folderName  = trim($_POST['folder_name']);
    $division_id = $_POST['division_id'] ?? null;

    if ($folderName !== '') {
        try {
            if (empty($division_id)) {
                $stmtDiv = $db->prepare("SELECT division_id FROM user_divisions WHERE user_id = ? LIMIT 1");
                $stmtDiv->execute([$user_id]);
                $division = $stmtDiv->fetch(PDO::FETCH_ASSOC);
                $division_id = $division ? $division['division_id'] : null;
            }

            $stmt = $db->prepare("
                INSERT INTO folders (folder_name, created_by, division_id)
                VALUES (:folder_name, :username, :division_id)
            ");
            $stmt->execute([
                ':folder_name' => $folderName,
                ':username'    => $username,
                ':division_id' => $division_id
            ]);

            header("Location: documents.php?success=1");
            exit;
        } catch (PDOException $e) {
            echo "Error creating folder: " . htmlspecialchars($e->getMessage());
        }
    } else {
        echo "Folder name cannot be empty.";
    }
}

// ================== Edit Document Visibility ==================
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['doc_id'], $_POST['visibility'])) {
    header('Content-Type: application/json');

    $doc_id = intval($_POST['doc_id']);
    $visibility = intval($_POST['visibility']);

    if ($doc_id <= 0) {
        echo json_encode(["success" => false, "message" => "Invalid document ID"]);
        exit;
    }

    if ($visibility !== 0 && $visibility !== 1) {
        $visibility = 0;
    }

    try {
        $stmt = $db->prepare("UPDATE documents SET is_public = ? WHERE id = ?");
        $stmt->execute([$visibility, $doc_id]);

        echo json_encode(["success" => true, "message" => "Visibility updated successfully"]);
        exit;
    } catch (PDOException $e) {
        echo json_encode(["success" => false, "message" => $e->getMessage()]);
        exit;
    }
}

// ================== Document → Folder Assignment ==================
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['document_id'], $_POST['folder_id'])) {
    $documentId = $_POST['document_id'];
    $folderId   = $_POST['folder_id'];

    try {
        $stmt = $db->prepare("UPDATE documents SET folder_id = :folder_id WHERE id = :document_id");
        $stmt->execute([
            ':folder_id'   => $folderId,
            ':document_id' => $documentId
        ]);

        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
        exit;
    } catch (PDOException $e) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => htmlspecialchars($e->getMessage())]);
        exit;
    }
}

// ================== Categories ==================
try {
    $stmt       = $db->query("SELECT * FROM categories ORDER BY name");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $categories = [];
}

// ================== Logged-in admin's Divisions ==================
$userDivisions   = [];
$userDivisionIds = [];

try {
    $stmt = $db->prepare("
        SELECT d.id, d.name
        FROM user_divisions ud
        JOIN division d ON ud.division_id = d.id
        WHERE ud.user_id = ?
    ");
    $stmt->execute([$user_id]);
    $userDivisions   = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $userDivisionIds = array_column($userDivisions, 'id'); 
} catch (PDOException $e) {
    $userDivisions   = [];
    $userDivisionIds = [];
}

// ================== Filters ==================
$search        = trim($_GET['search'] ?? '');
$user_filter   = $_GET['user']   ?? '';
$type_filter   = $_GET['type']   ?? '';
$status_filter = $_GET['status'] ?? '';
$folder_filter = $_GET['folder_id'] ?? '';

// ================== WHERE Builder ==================
$where  = [];
$params = [];

if (!empty($userDivisionIds)) {
    $placeholders = implode(',', array_fill(0, count($userDivisionIds), '?'));

    $where[] = "(
        (ud.division_id IN ($placeholders) AND d.is_public = 1)
        OR d.user_id = ?
    )";
    $params   = array_merge($params, $userDivisionIds);
    $params[] = $user_id;
}

if ($search !== '') {
    $where[]  = "d.title LIKE ?";
    $params[] = "%$search%";
}
if ($user_filter !== '') {
    $where[]  = "d.user_id = ?";
    $params[] = $user_filter;
}
if ($type_filter !== '') {
    $where[]  = "d.file_type = ?";
    $params[] = $type_filter;
}
if ($status_filter !== '') {
    $where[]  = "d.is_public = ?";
    $params[] = ($status_filter === 'public') ? 1 : 0;
}
if ($folder_filter !== '') {
    $where[]  = "d.folder_id = ?";
    $params[] = $folder_filter;
} elseif (empty($search) && empty($user_filter) && empty($type_filter) && empty($status_filter)) {
    $where[] = "d.folder_id IS NULL";
}

$where_sql = $where ? "WHERE " . implode(" AND ", $where) : "";

// ================== Documents ==================
$sql = "
    SELECT DISTINCT d.*, u.username, u.full_name, c.name AS category_name
    FROM documents d
    JOIN users u ON d.user_id = u.id
    JOIN user_divisions ud ON u.id = ud.user_id
    LEFT JOIN categories c ON d.category_id = c.id
    $where_sql
    ORDER BY d.created_at DESC
";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$documents = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ================== Shared With Me ==================
$sharedDocuments = [];
try {
    $stmt = $db->prepare("
        SELECT d.*, u.username, u.full_name, c.name AS category_name
        FROM shared_documents s
        JOIN documents d ON s.document_id = d.id
        JOIN users u ON d.user_id = u.id
        LEFT JOIN categories c ON d.category_id = c.id
        WHERE s.shared_to = ?
        ORDER BY s.shared_at DESC
    ");
    $stmt->execute([$user_id]);
    $sharedDocuments = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $sharedDocuments = [];
}

// ================== Folders ==================
$whereFolder  = [];
$paramsFolder = [];

if (!empty($userDivisionIds)) {
    $placeholders = implode(',', array_fill(0, count($userDivisionIds), '?'));
    $whereFolder[] = "f.division_id IN ($placeholders)";
    $paramsFolder  = array_merge($paramsFolder, $userDivisionIds);
}

$whereSQL = $whereFolder ? "WHERE " . implode(" AND ", $whereFolder) : "";

$stmt = $db->prepare("
    SELECT f.id, f.folder_name, f.created_by, f.created_at,
           f.division_id, d.name AS division_name
    FROM folders f
    LEFT JOIN division d ON f.division_id = d.id
    $whereSQL
    ORDER BY f.created_at DESC
");
$stmt->execute($paramsFolder);
$folders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ================== File Types Dropdown ==================
$file_types = $db->query("SELECT DISTINCT file_type FROM documents ORDER BY file_type")->fetchAll(PDO::FETCH_ASSOC);

require_once '../includes/admin_header.php';
?>


<div class="container-fluid px-4">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0" style=" background:rgb(43, 46, 63); color: white;">
                <div class="card-body py-4">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h1 class="display-6 fw-bold mb-2">
                                <i class="fas fa-file-alt me-3"></i>Document Management
                            </h1>
                            <p class="lead mb-0">
                                Upload, manage, and monitor all system documents
                            </p>
                        </div>
                        <div class="col-md-4 text-end">
                            <button class="btn btn-light btn-lg me-2" data-bs-toggle="modal" data-bs-target="#uploadModal">
                                <i class="fas fa-plus me-2"></i>Upload Document
                            </button>

                            <button class="btn btn-light btn-lg me-2" data-bs-toggle="modal" data-bs-target="#openFolderModal">
    <i class="fas fa-plus me-2"></i> New Folder
</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>

    <!-- Filters -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-filter me-2"></i>Search & Filter Documents
                    </h5>
                </div>
                <div class="card-body">
                <form id="filterForm" class="row g-3 mb-4" method="GET" action="">
  <!-- Search -->
  <div class="col-md-3">
    <label for="search" class="form-label">Search</label>
    <input 
        type="text" 
        class="form-control" 
        id="search" 
        name="search"
        value="<?= htmlspecialchars($search ?? '') ?>" 
        placeholder="Title contains...">
  </div>

  <!-- User -->
  <div class="col-md-3">
    <label for="user" class="form-label">User</label>
    <select class="form-select" id="user" name="user">
        <option value="">All Users</option>
        <?php foreach ($users as $u): ?>
            <option 
                value="<?= htmlspecialchars($u['id']) ?>" 
                <?= ($user_filter == $u['id']) ? 'selected' : '' ?>>
                <?= htmlspecialchars($u['full_name'] ?: $u['username']) ?>
            </option>
        <?php endforeach; ?>
    </select>
  </div>

  <!-- File Type -->
  <div class="col-md-2">
    <label for="type" class="form-label">File Type</label>
    <select class="form-select" id="type" name="type">
        <option value="">All Types</option>
        <?php foreach ($file_types as $ft): ?>
            <option 
                value="<?= $ft['file_type'] ?>" 
                <?= ($type_filter === $ft['file_type']) ? 'selected' : '' ?>>
                <?= strtoupper($ft['file_type']) ?>
            </option>
        <?php endforeach; ?>
    </select>
  </div>

  <!-- Status -->
  <div class="col-md-2">
    <label for="status" class="form-label">Status</label>
    <select class="form-select" id="status" name="status">
        <option value="">All Status</option>
        <option value="public" <?= ($status_filter === 'public') ? 'selected' : '' ?>>Public</option>
        <option value="private" <?= ($status_filter === 'private') ? 'selected' : '' ?>>Private</option>
    </select>
  </div>

  <!-- Actions -->
  <div class="col-md-2 d-grid">
    <label class="form-label">&nbsp;</label>
    <button type="submit" class="btn btn-primary">
        <i class="fas fa-search me-1"></i>Filter
    </button>
    <a href="documents.php" class="btn btn-secondary mt-1">
        <i class="fas fa-trash me-1"></i>Clear
    </a>
  </div>
</form>


                </div>
            </div>
        </div>
    </div>

<!-- Breadcrumb Back -->
<div id="breadcrumbContainer" class="mb-3" style="display:none;">
  <a href="javascript:void(0)" id="backToFolders" class="btn btn-outline-secondary btn-sm">
    <i class="fas fa-arrow-left"></i> <span id="breadcrumbText">Back to Folders</span>
  </a>
</div>

 <?php 
$filtersActive = !empty($search) || !empty($user_filter) || !empty($type_filter) || !empty($status_filter);
?>
<!-- ================== Shared With Me ================== -->
<?php if (!empty($sharedDocuments)): ?>
<div class="mb-4">
    <h4 class="mb-3">Shared With Me</h4>
    <div class="row g-2" id="sharedDocumentsContainer">
        <?php foreach ($sharedDocuments as $document): ?>
            <div class="col-sm-6 col-md-4 col-lg-3" data-document-id="<?= $document['id'] ?>">
                <div class="card shadow-sm h-100">
                    <div class="card-body d-flex flex-column">
                        <h6 class="fw-bold text-truncate"><?= htmlspecialchars($document['title']) ?></h6>
                        <small class="text-muted text-truncate"><?= htmlspecialchars($document['original_name']) ?></small>
                        <strong><?= htmlspecialchars($document['full_name'] ?? $document['username']) ?></strong>
                        <small>
                            <?= $document['category_name']
                                ? '<span class="badge bg-info">'.htmlspecialchars($document['category_name']).'</span>'
                                : '<span class="text-muted">Uncategorized</span>'; ?>
                        </small>

                        <div class="mt-auto btn-group btn-group-sm">
                        <a href="../api/download.php?id=<?= $document['id'] ?>" class="btn btn-outline-success" title="Download">
                    <i class="fas fa-download"></i>
                </a>
                <button type="button" class="btn btn-outline-primary"
                    onclick="previewDocument(<?= $document['id'] ?>, '<?= addslashes($document['original_name']) ?>', '<?= $document['file_type'] ?>')"
                    title="Preview">
                    <i class="fas fa-eye"></i>
                </button>
                <button type="button" class="btn btn-outline-warning"
                onclick="editCategory(<?= $document['id'] ?>, <?= $document['category_id'] ?: 'null' ?>)"
                title="Edit Category">
                <i class="fas fa-tag"></i>
              </button>
    
<button type="button"
    class="btn btn-sm btn-outline-primary"
    onclick="window.location.href='view.php?id=<?= $document['id'] ?>'"
    title="View Document">
    <i class="fas fa-folder-open"></i>
</button>

                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
<?php else: ?>
    <div class="mb-4 text-center text-muted">
        <p>No documents have been shared with you.</p>
    </div>
<?php endif; ?>


<!-- ================== Folders ================== -->
<?php if (empty($folder_filter) && !$filtersActive): ?>
    <div id="foldersContainer" class="row g-3 mb-4">
        <?php foreach ($folders as $folder): ?>
            <div class="col-sm-6 col-md-4 col-lg-3">
                <a href="javascript:void(0)" class="text-decoration-none text-dark folder-item"
                   data-folder-id="<?= $folder['id'] ?>">
                  <div class="card folder-card text-center h-100">
                    <div class="card-body d-flex flex-column align-items-center justify-content-center">
                      <div class="folder-icon mb-2"
                           style="background-image:url('https://cdn-icons-png.flaticon.com/512/716/716784.png');
                                  width:60px; height:60px; background-size:cover;">
                      </div>
                      <h6 class="fw-bold text-truncate">
                      <h5 class="card-title"><?= htmlspecialchars($folder['folder_name']) ?></h5>
        <p class="text-muted mb-1">Created by: <?= htmlspecialchars($folder['created_by']) ?></p>
        <p class="text-muted mb-0">Division: <?= htmlspecialchars($folder['division_name'] ?? 'Unassigned') ?></p>
                      </h6>
                      <small class="text-muted">
                        Created: <?= date('M j, Y', strtotime($folder['created_at'])) ?>
                      </small>
                    </div>
                  </div>
                </a>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>


<!-- ================== Documents ================== -->
<div id="documentsContainer" style="display:block; padding-top:20px;">
  <a href="javascript:void(0)" id="backToFolders"
     class="btn btn-outline-secondary btn-sm mb-3" style="display:none;">
    <i class="fas fa-arrow-left"></i> Back to Folders
  </a>

  <div id="documentsContent" class="row g-2">
    <?php foreach ($documents as $document): ?>
        <div class="col-sm-6 col-md-4 col-lg-3" data-document-id="<?= $document['id'] ?>">
    <div class="card shadow-sm h-100">
        <div class="card-body d-flex flex-column">
            <h6 class="fw-bold text-truncate"><?= htmlspecialchars($document['title']) ?></h6>
            <small class="text-muted text-truncate"><?= htmlspecialchars($document['original_name']) ?></small>
            <small><strong><?= htmlspecialchars($document['full_name'] ?? $document['username']) ?></strong></small>
            <small>
                <?= $document['category_name']
                    ? '<span class="badge bg-info">'.htmlspecialchars($document['category_name']).'</span>'
                    : '<span class="text-muted">Uncategorized</span>'; ?>
            </small>
            <div class="mt-auto btn-group btn-group-sm">
                <a href="../api/download.php?id=<?= $document['id'] ?>" class="btn btn-outline-success" title="Download">
                    <i class="fas fa-download"></i>
                </a>
                <button type="button" class="btn btn-outline-primary"
                    onclick="previewDocument(<?= $document['id'] ?>, '<?= addslashes($document['original_name']) ?>', '<?= $document['file_type'] ?>')"
                    title="Preview">
                    <i class="fas fa-eye"></i>
                </button>
                <button type="button" class="btn btn-outline-warning"
                onclick="editCategory(<?= $document['id'] ?>, <?= $document['category_id'] ?: 'null' ?>)"
                title="Edit Category">
                <i class="fas fa-tag"></i>
              </button>
              <button type="button" class="btn btn-outline-warning"
                onclick="openAssignFolderModal(<?= $document['id'] ?>, '<?= addslashes($document['title']) ?>')"
                title="Assign Folder">
                <i class="fas fa-folder-plus"></i>
            </button>

                <button type="button" class="btn btn-outline-danger"
                    onclick="deleteDocument(<?= $document['id'] ?>, '<?= addslashes($document['title']) ?>')"
                    title="Delete">
                    <i class="fas fa-trash"></i>
                </button>
                <button type="button"
        class="btn btn-sm btn-outline-warning edit-visibility-btn"
        data-id="<?= intval($document['id']) ?>" 
        data-visibility="<?= intval($document['is_public']) ?>"
        title="Edit Visibility">
    <i class="fas <?= $document['is_public'] ? 'fa-eye' : 'fa-eye-slash' ?>"></i>
</button>

<button type="button"
    class="btn btn-sm btn-outline-primary"
    onclick="window.location.href='view.php?id=<?= $document['id'] ?>'"
    title="View Document">
    <i class="fas fa-folder-open"></i>
</button>

<button type="button"
    class="btn btn-sm btn-outline-primary"
    onclick="window.location.href='document_access.php?id=<?= $document['id'] ?>'"
    title="Access Point">
    <i class="fas fa-person"></i>
</button>

            </div>
        </div>
    </div>
</div>

    <?php endforeach; ?>

    <?php if (empty($documents)): ?>
      <div class="col-12 text-center text-muted py-5">No unassigned documents found.</div>
    <?php endif; ?>
  </div>
</div>

<!-- Modal
=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+=+
-->
<!-- Preview Document Modal -->
<div class="modal fade" id="previewModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-eye me-2"></i>Document Preview
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <div id="previewLoader" class="text-center p-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-3">Loading preview...</p>
                </div>
                <div id="previewContent" style="display: none;">
                    <!-- Preview content will be loaded here -->
                </div>
                <div id="previewError" style="display: none;" class="text-center p-5">
                    <i class="fas fa-exclamation-triangle fa-3x text-warning mb-3"></i>
                    <h5>Preview Not Available</h5>
                    <p class="text-muted">This file type cannot be previewed in the browser.</p>
                    <button class="btn btn-primary" onclick="downloadFromPreview()">
                        <i class="fas fa-download me-2"></i>Download File
                    </button>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="downloadFromPreviewBtn">
                    <i class="fas fa-download me-2"></i>Download
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Edit Category Modal -->
<div class="modal fade" id="editCategoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-tag me-2"></i>Edit Document Category
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="editCategoryForm">
                    <input type="hidden" id="editDocumentId" value="">
                    <div class="mb-3">
                        <label for="editCategorySelect" class="form-label">Select Category</label>
                        <select class="form-select" id="editCategorySelect" required>
                            <option value="">-- Select Category --</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['id']; ?>">
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="updateCategory()">
                    <i class="fas fa-save me-2"></i>Update Category
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Upload Document Modal -->
<div class="modal fade" id="uploadModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-cloud-upload-alt me-2"></i>Upload New Document
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="uploadForm" enctype="multipart/form-data">
                    <!-- File Upload Area -->
                    <div class="upload-area" id="uploadArea" style="border: 2px dashed #ddd; padding: 40px; text-align: center; border-radius: 8px; cursor: pointer; transition: all 0.3s ease;">
                    <div class="text-center">
                            <i class="fas fa-cloud-upload-alt" style="font-size: 3rem; color: #6b7280; margin-bottom: 1rem;"></i>
                            <h5>Drag & Drop Files Here</h5>
                            <p class="text-muted mb-3">or click to browse files</p>
                            <input type="file" id="fileInput" name="files[]" multiple style="display: none;"
                                   accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.jpg,.jpeg,.png,.gif,.zip,.rar,.mp4,.mp3">
                    
                                                        <button type="button" class="btn btn-outline-primary" onclick="document.getElementById('fileInput').click()">
                                <i class="fas fa-folder-open me-2"></i>Choose Files
                            </button>
                        </div>
                        
                        <!-- File Info -->
                        <div class="mt-3">
                            <small class="text-muted">
                                <strong>Supported formats:</strong> PDF, DOC, DOCX, XLS, XLSX, PPT, PPTX, TXT, JPG, PNG, GIF, ZIP, RAR, MP4, MP3<br>
                                <strong>Maximum file size:</strong> <?php
                                $upload_max = ini_get('upload_max_filesize');
                                $post_max = ini_get('post_max_size');
                                echo $upload_max . ' per file (PHP limit: ' . $post_max . ')';
                                ?> per file
                            </small>
                        </div>
                    </div>
                    
                    <!-- Selected Files Preview -->
                    <div id="filePreview" class="mt-4" style="display: none;">
                        <h6><i class="fas fa-list me-2"></i>Selected Files</h6>
                        <div id="fileList"></div>
                    </div>

                    <!-- Document Details -->
                    <div class="row mt-4">
                        <div class="col-md-6">
                            <label for="category" class="form-label">Category</label>
                            <select class="form-select" id="category" name="category">
                                <option value="">Select Category</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo $cat['id']; ?>">
                                        <?php echo htmlspecialchars($cat['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="visibility" class="form-label">Visibility</label>
                            <select class="form-select" id="visibility" name="visibility">
                                <option value="0">Private (Only You)</option>
                                <option value="1">Public (Everyone)</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mt-3">
                        <label for="tags" class="form-label">Tags (Optional)</label>
                        <input type="text" class="form-control" id="tags" name="tags"
                               placeholder="Enter tags separated by commas (e.g., report, 2024, important)">
                        <small class="text-muted">Tags help you organize and find your documents easily</small>
                    </div>
                    
                    <div class="mt-3">
                        <label for="description" class="form-label">Description (Optional)</label>
                        <textarea class="form-control" id="description" name="description" rows="3"
                                  placeholder="Add a description for your documents..."></textarea>
                    </div>
                    
                    <div class="mt-3">
                        <label for="folder_id" class="form-label">Folder to Put</label>
                        <select name="folder_id" id="folder_id" class="form-control" required>
                            <option value="">-- Select Folder --</option>
                            <?php foreach ($folders as $folder): ?>
                                <option value="<?php echo $folder['id']; ?>">
                                    <?php echo htmlspecialchars($folder['folder_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Upload Progress -->
                    <div class="upload-progress mt-4" style="display: none;">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span>Uploading...</span>
                            <span id="progressText">0%</span>
                        </div>
                        <div class="progress">
                            <div class="progress-bar" id="progressBar" style="width: 0%"></div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" id="uploadBtn" disabled>
                    <i class="fas fa-upload me-2"></i>Upload Documents
                </button>
                <button type="button" class="btn btn-outline-secondary" id="clearBtn">
                    <i class="fas fa-times me-2"></i>Clear All
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Create Folder Modal -->
<div class="modal fade" id="openFolderModal" tabindex="-1" aria-labelledby="folderModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      
      <!-- Header -->
      <div class="modal-header">
        <h5 class="modal-title" id="folderModalLabel">Create New Folder</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <!-- Body -->
      <div class="modal-body">
      <form method="POST" action="documents.php" class="row g-3">
  <div class="col-md-6">
    <label for="folder_name" class="form-label">Folder Name</label>
    <input type="text" name="folder_name" id="folder_name" class="form-control" required>
  </div>

  <?php if (count($userDivisions) > 1): ?>
  <div class="col-md-6">
    <label for="division_id" class="form-label">Division</label>
    <select name="division_id" id="division_id" class="form-select" required>
      <option value="">-- Select Division --</option>
      <?php foreach ($userDivisions as $division): ?>
        <option value="<?= htmlspecialchars($division['id']) ?>">
          <?= htmlspecialchars($division['name']) ?>
        </option>
      <?php endforeach; ?>
    </select>
  </div>
<?php endif; ?>

  <div class="col-12">
    <button type="submit" class="btn btn-primary">
      <i class="fas fa-folder-plus"></i> Create Folder
    </button>
  </div>
</form>

      </div>

    </div>
  </div>
</div>

<!-- Assign Folder Modal -->
<div class="modal fade" id="assignFolderModal" tabindex="-1" aria-labelledby="assignFolderModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="assignFolderForm">
        <div class="modal-header">
          <h5 class="modal-title" id="assignFolderModalLabel">Assign Folder</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <!-- hidden input -->
          <input type="hidden" id="documentId" name="document_id">

          <!-- show document title -->
          <div class="mb-3">
            <label class="form-label">Document:</label>
            <p id="assignDocTitle" class="fw-bold"></p>
          </div>

          <!-- folder selection -->
          <div class="mb-3">
            <label for="folderSelect" class="form-label">Select Folder</label>
            <select class="form-select" id="folderSelect" name="folder_id" required>
              <option value="">-- Select Folder --</option>
              <?php foreach ($folders as $folder): ?>
                <option value="<?= $folder['id'] ?>"><?= htmlspecialchars($folder['folder_name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Assign</button>
        </div>
      </form>
    </div>
  </div>
</div>


<!-- Edit Visibility Modal -->
<div class="modal fade" id="editVisibilityModal" tabindex="-1" aria-labelledby="editVisibilityLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="editVisibilityForm">
        <div class="modal-header">
          <h5 class="modal-title" id="editVisibilityLabel">Edit Document Visibility</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="doc_id" id="visibility_doc_id">
          <div class="mb-3">
            <label for="visibility" class="form-label">Visibility</label>
            <select class="form-select" id="visibility" name="visibility">
              <option value="0">Private (Only You)</option>
              <option value="1">Public (Everyone)</option>
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-primary">Save Changes</button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        </div>
      </form>
    </div>
  </div>
</div>


</div>

<style>


.table th {
     border-top: none;
    font-weight: 600;
    font-size: 0.875rem;
   background: linear-gradient(90deg, #0b257cff 0%, #010b33ff 100%);
    color: white;

}

.table td {
     vertical-align: middle;
    font-size: 0.875rem;
}

.btn-group .btn {
    margin-right: 2px;
}

.btn-group .btn:last-child {
    margin-right: 0;
}

.badge {
    font-size: 0.75rem;
}

.form-text {
    font-size: 0.8rem;
}

.modal-lg {
    max-width: 800px;
}

.card-header {
    background: linear-gradient(90deg,rgb(43, 46, 63) 0%,rgb(6, 30, 187) 100%) !important; /* Change these colors for your preferred blue or any other color */
    color: white;
}

.table-hover tbody tr:hover {
    background-color: rgba(0, 123, 255, 0.05);
}

/* Upload area styling */
.upload-area:hover {
    border-color: #007bff !important;
    background-color: #f8f9fa !important;
}

.upload-area.dragover {
    border-color: #28a745 !important;
    background-color: rgba(40, 167, 69, 0.1) !important;
}

.folder-icon {
  width: 64px; height: 64px;
  background-size: cover;
  background-position: center;
}
.folder-item { cursor: pointer; }

#foldersContainer {
  margin-bottom: 30px; /* adds space below folders */
}

</style>

<script> 
document.addEventListener('DOMContentLoaded', function () {
  console.log('Admin documents page loaded');

  // 🔹 Upload elements
    const uploadArea = document.getElementById('uploadArea');
    const fileInput = document.getElementById('fileInput');
    const uploadForm = document.getElementById('uploadForm');
    const uploadBtn = document.getElementById('uploadBtn');
    const filePreview = document.getElementById('filePreview');
    const fileList = document.getElementById('fileList');
    const clearBtn = document.getElementById('clearBtn');

  // 🔹 Document/folder elements
  const filterForm = document.getElementById("filterForm");
  const documentsContent = document.getElementById("documentsContent");
  const foldersContainer = document.getElementById("foldersContainer");
  const backToFolders = document.getElementById("backToFolders");
  const documentsContainer = documentsContent?.parentElement; // wrapper if needed
  const applyFilterBtn = document.getElementById("applyFilterBtn");
  const clearFilterBtn = document.getElementById("clearFilterBtn");

  let folderPath = []; // stack of folder names

  function fetchDocuments(folderId = null, folderName = null) {
  const params = new URLSearchParams();
  if (folderId) params.set('folder_id', folderId);

  documentsContent.innerHTML = `
    <div class="text-center py-4">
      <i class="fas fa-spinner fa-spin fa-2x text-muted"></i>
      <p>Loading documents...</p>
    </div>`;

  fetch('documents.php?' + params.toString())
    .then(res => res.text())
    .then(html => {
      const parser = new DOMParser();
      const doc = parser.parseFromString(html, 'text/html');

      const newDocs = doc.querySelector('#documentsContent');
      const newFolders = doc.querySelector('#foldersContainer');

      if (newDocs) {
        documentsContent.innerHTML = newDocs.innerHTML;

        if (folderId) {
          // Inside a folder → hide folders
          if (foldersContainer) foldersContainer.style.display = 'none';

          // Show breadcrumb
          document.getElementById('breadcrumbContainer').style.display = 'block';

          if (folderName) {
            folderPath.push(folderName);
          }

          document.getElementById('breadcrumbText').textContent = 'Folders' + (folderPath.length ? ' > ' + folderPath.join(' > ') : '');
        } else {
          // Root → show folders + unassigned docs
          if (foldersContainer && newFolders) {
            foldersContainer.innerHTML = newFolders.innerHTML;
            foldersContainer.style.display = 'flex';
            bindFolderClicks();
          }

          document.getElementById('breadcrumbContainer').style.display = 'none';
          folderPath = [];
        }
      } else {
        documentsContent.innerHTML = `<div class="alert alert-warning">No documents found.</div>`;
      }
    })
    .catch(err => {
      console.error('Fetch error:', err);
      documentsContent.innerHTML = `<div class="alert alert-danger">Error loading documents</div>`;
    });
}


function bindFolderClicks() {
  document.querySelectorAll(".folder-item").forEach(folder => {
    folder.addEventListener("click", () => {
      const folderId = folder.dataset.folderId;
      const folderName = folder.querySelector('h6').textContent; // get folder title
      fetchDocuments(folderId, folderName);
    });
  });
}



// Initial bind
bindFolderClicks();

// Back button
backToFolders?.addEventListener("click", () => {
  fetchDocuments(null, true);
});


  // ✅ Folder clicks (delegation)
  foldersContainer?.addEventListener("click", (e) => {
    const folder = e.target.closest(".folder-item");
    if (folder) {
      fetchDocuments(folder.dataset.folderId);
    }
  });

// ✅ Apply filter
applyFilterBtn?.addEventListener("click", () => {
  fetchDocuments(null, true);
  foldersContainer.style.display = "none"; // hide folders
});

// ✅ Clear filter
clearFilterBtn?.addEventListener("click", () => {
  filterForm.reset();
  fetchDocuments(null, true);
  foldersContainer.style.display = "block"; // show folders back
});


  // ✅ Back to folders
  backToFolders?.addEventListener("click", () => {
  // Pop last folder
  folderPath.pop();
  if (folderPath.length === 0) {
    // Go back to root
    fetchDocuments(null, null);
  } else {
    // Optional: fetch parent folder if you have nested folders
    // For now, just show root after one level
    fetchDocuments(null, null);
  }
});

});
 /** -----------------------------
   * EDIT DOCUMENT VISIBILITY
   * ----------------------------- */
  document.addEventListener("DOMContentLoaded", function() {
    const editButtons = document.querySelectorAll(".edit-visibility-btn");
    const modal = new bootstrap.Modal(document.getElementById("editVisibilityModal"));
    const docIdField = document.getElementById("visibility_doc_id");
    const visibilityField = document.getElementById("visibility");

    editButtons.forEach(btn => {
        btn.addEventListener("click", function() {
            const docId = this.dataset.id;
            const visibility = this.dataset.visibility;

            docIdField.value = docId;
            visibilityField.value = visibility;

            modal.show();
        });
    });

    document.getElementById("editVisibilityForm").addEventListener("submit", function(e) {
        e.preventDefault();
        const formData = new FormData(this);

        fetch("documents.php", {
            method: "POST",
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                location.reload();
            } else {
                alert("Failed to update: " + data.message);
            }
        })
        .catch(err => {
            console.error(err);
            alert("Error connecting to server.");
        });
    });
});
 /** -----------------------------
   * ASSIGN FOLDER
   * ----------------------------- */
  let currentDocumentId = null;

// Open modal
function openAssignFolderModal(documentId, documentTitle) {
    currentDocumentId = documentId;
    document.getElementById('documentId').value = documentId;
    document.getElementById('assignDocTitle').textContent = documentTitle;

    const modalEl = document.getElementById('assignFolderModal');
    const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
    modal.show();
}

// Handle assign form submit
document.getElementById('assignFolderForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);

    fetch('documents.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            // ✅ Remove the assigned document card from "Unassigned"
            if (currentDocumentId) {
                const card = document.querySelector(`[data-document-id="${currentDocumentId}"]`);
                if (card) card.remove();
            }

            // ✅ Close modal properly
            const modalEl = document.getElementById('assignFolderModal');
            const modal = bootstrap.Modal.getInstance(modalEl);
            modal.hide();

            // Reset form for next time
            document.getElementById('assignFolderForm').reset();
            currentDocumentId = null;
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(err => {
        console.error(err);
        alert('Something went wrong.');
    });
});

  /** -----------------------------
   * UPLOAD HANDLING
   * ----------------------------- */
  let selectedFiles = [];
    
    // File input change event
    fileInput.addEventListener('change', function(e) {
        console.log('Files selected:', this.files.length);
        
        if (this.files.length > 0) {
            const files = Array.from(this.files);
            handleFileSelection(files);
        }
    });
    
    // Upload area click
    uploadArea.addEventListener('click', function(e) {
        if (e.target.tagName !== 'BUTTON') {
            fileInput.click();
        }
    });
    
    // Clear button
    clearBtn.addEventListener('click', function() {
        resetForm();
    });
    
    // Upload button
    uploadBtn.addEventListener('click', function() {
        if (selectedFiles.length === 0) {
            showAlert('Please select at least one file to upload.', 'warning');
            return;
        }
        uploadFiles();
    });
    
    // Drag and drop events
    uploadArea.addEventListener('dragover', function(e) {
        e.preventDefault();
        e.stopPropagation();
        this.classList.add('dragover');
    });
    
    uploadArea.addEventListener('dragleave', function(e) {
        e.preventDefault();
        e.stopPropagation();
        this.classList.remove('dragover');
    });
    
    uploadArea.addEventListener('drop', function(e) {
        e.preventDefault();
        e.stopPropagation();
        this.classList.remove('dragover');
        
        const files = Array.from(e.dataTransfer.files);
        if (files.length > 0) {
            handleFileSelection(files);
        }
    });
    
    // Handle file selection
    function handleFileSelection(files) {
        console.log('Processing files:', files.length);
        
        const allowedExtensions = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'jpg', 'jpeg', 'png', 'gif', 'zip', 'rar', 'mp4', 'mp3'];
        const maxFileSize = 50 * 1024 * 1024; // 50MB
        
        files.forEach(file => {
            const extension = file.name.split('.').pop().toLowerCase();
            
            // Validate file type
            if (!allowedExtensions.includes(extension)) {
                showAlert(`File "${file.name}" has an unsupported format.`, 'warning');
                return;
            }
            
            // Validate file size
            if (file.size > maxFileSize) {
                showAlert(`File "${file.name}" is too large. Maximum size is 50MB.`, 'warning');
                return;
            }
            
            // Check if file already selected
            if (selectedFiles.find(f => f.name === file.name && f.size === file.size)) {
                showAlert(`File "${file.name}" is already selected.`, 'info');
                return;
            }
            
            selectedFiles.push(file);
        });
        
        updateFilePreview();
        updateUploadButton();
    }
    
    // Update file preview
    function updateFilePreview() {
        if (selectedFiles.length === 0) {
            filePreview.style.display = 'none';
            return;
        }
        
        filePreview.style.display = 'block';
        fileList.innerHTML = '';
        
        selectedFiles.forEach(function(file, index) {
            const fileItem = document.createElement('div');
            fileItem.className = 'file-item d-flex justify-content-between align-items-center p-3 mb-2 border rounded';
            
            const extension = file.name.split('.').pop().toLowerCase();
            const icon = getFileIcon(extension);
            
            fileItem.innerHTML = `
                <div class="d-flex align-items-center">
                    <i class="${icon} me-3" style="font-size: 1.5rem;"></i>
                    <div>
                        <div class="fw-medium">${file.name}</div>
                        <small class="text-muted">${formatFileSize(file.size)}</small>
                    </div>
                </div>
                <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeFile(${index})">
                    <i class="fas fa-times"></i>
                </button>
            `;
            
            fileList.appendChild(fileItem);
        });
    }
    
    // Remove file from selection
    window.removeFile = function(index) {
        selectedFiles.splice(index, 1);
        updateFilePreview();
        updateUploadButton();
    };
    
    // Update upload button state
    function updateUploadButton() {
        uploadBtn.disabled = selectedFiles.length === 0;
    }
    
    // Upload files
    function uploadFiles() {
        const formData = new FormData();
        
        // Add files to form data
        selectedFiles.forEach(function(file) {
            formData.append('files[]', file);
        });
        
        // Add other form data
        formData.append('category', document.getElementById('category').value);
        formData.append('visibility', document.getElementById('visibility').value);
        formData.append('tags', document.getElementById('tags').value);
        formData.append('description', document.getElementById('description').value);
        formData.append('folder_id', document.getElementById('folder_id').value);
        // Show progress
        const progressContainer = document.querySelector('.upload-progress');
        const progressBar = document.getElementById('progressBar');
        const progressText = document.getElementById('progressText');
        
        progressContainer.style.display = 'block';
        uploadBtn.disabled = true;
        uploadBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Uploading...';
        
        // Create XMLHttpRequest for progress tracking
        const xhr = new XMLHttpRequest();
        
        xhr.upload.addEventListener('progress', function(e) {
            if (e.lengthComputable) {
                const percentComplete = Math.round((e.loaded / e.total) * 100);
                progressBar.style.width = percentComplete + '%';
                progressText.textContent = percentComplete + '%';
            }
        });
        
        xhr.addEventListener('load', function() {
            console.log('Upload response status:', xhr.status);
            console.log('Upload response:', xhr.responseText);
            
            if (xhr.status === 200) {
                try {
                    const response = JSON.parse(xhr.responseText);
                    console.log('Parsed response:', response);
                    
                    if (response.success) {
                        showAlert(response.message, 'success');
                        resetForm();
                        
                        // Close modal
                        const modal = bootstrap.Modal.getInstance(document.getElementById('uploadModal'));
                        if (modal) {
                            modal.hide();
                        }
                        
                        // Reload page after 2 seconds to show new files
                        setTimeout(() => {
                            console.log('Reloading page...');
                            window.location.reload();
                        }, 2000);
                    } else {
                        showAlert('Upload failed: ' + response.message, 'danger');
                        if (response.errors && response.errors.length > 0) {
                            console.log('Upload errors:', response.errors);
                            response.errors.forEach(error => {
                                showAlert(error, 'warning');
                            });
                        }
                    }
                } catch (e) {
                    console.error('JSON parse error:', e);
                    showAlert('Upload completed but response was invalid.', 'warning');
                    // Still reload the page in case upload actually worked
                    setTimeout(() => {
                        window.location.reload();
                    }, 2000);
                }
            } else {
                showAlert(`Upload failed with status ${xhr.status}. Please try again.`, 'danger');
            }
            
            // Reset upload state
            progressContainer.style.display = 'none';
            uploadBtn.disabled = false;
            uploadBtn.innerHTML = '<i class="fas fa-upload me-2"></i>Upload Documents';
        });
        
        xhr.addEventListener('error', function() {
            console.error('Upload request failed');
            showAlert('Upload failed. Please check your connection and try again.', 'danger');
            progressContainer.style.display = 'none';
            uploadBtn.disabled = false;
            uploadBtn.innerHTML = '<i class="fas fa-upload me-2"></i>Upload Documents';
        });
        
        xhr.open('POST', '../api/upload.php');
        xhr.send(formData);
    }
    
    // Helper functions
    function getFileIcon(extension) {
        const icons = {
            'pdf': 'fas fa-file-pdf text-danger',
            'doc': 'fas fa-file-word text-primary',
            'docx': 'fas fa-file-word text-primary',
            'xls': 'fas fa-file-excel text-success',
            'xlsx': 'fas fa-file-excel text-success',
            'ppt': 'fas fa-file-powerpoint text-warning',
            'pptx': 'fas fa-file-powerpoint text-warning',
            'jpg': 'fas fa-file-image text-info',
            'jpeg': 'fas fa-file-image text-info',
            'png': 'fas fa-file-image text-info',
            'gif': 'fas fa-file-image text-info',
            'zip': 'fas fa-file-archive text-secondary',
            'rar': 'fas fa-file-archive text-secondary',
            'mp4': 'fas fa-file-video text-purple',
            'mp3': 'fas fa-file-audio text-success',
            'txt': 'fas fa-file-alt text-muted'
        };
        
        return icons[extension.toLowerCase()] || 'fas fa-file text-muted';
    }
    
    function formatFileSize(bytes) {
        if (bytes >= 1073741824) {
            return (bytes / 1073741824).toFixed(2) + ' GB';
        } else if (bytes >= 1048576) {
            return (bytes / 1048576).toFixed(2) + ' MB';
        } else if (bytes >= 1024) {
            return (bytes / 1024).toFixed(2) + ' KB';
        } else {
            return bytes + ' bytes';
        }
    }

// ===============================
// Form Reset
// ===============================
function resetForm() {
    fileInput.value = '';
    filePreview.style.display = 'none';
    uploadBtn.disabled = true;
    selectedFiles = [];

    document.getElementById('category').value = '';
    document.getElementById('visibility').value = '0';
    document.getElementById('tags').value = '';
    document.getElementById('description').value = '';
    document.getElementById('folder_id').value = '';

    const progressContainer = document.querySelector('.upload-progress');
    progressContainer.style.display = 'none';

    uploadBtn.innerHTML = '<i class="fas fa-upload me-2"></i>Upload Documents';
}
window.resetForm = resetForm;

// ===============================
// Delete Document
// ===============================
function deleteDocument(id, title) {
    if (!confirm(`Are you sure you want to delete "${title}"? This action cannot be undone.`)) return;

    const btn = event.target.closest('button');
    const originalContent = btn.innerHTML;

    // Show loading spinner
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    btn.disabled = true;

    fetch('../api/delete.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('Document deleted successfully', 'success');
            btn.closest('tr').remove();
            updateDocumentCount();
        } else {
            showAlert(`Error deleting document: ${data.message}`, 'danger');
            btn.innerHTML = originalContent;
            btn.disabled = false;
        }
    })
    .catch(error => {
        console.error('Delete error:', error);
        showAlert('Error deleting document', 'danger');
        btn.innerHTML = originalContent;
        btn.disabled = false;
    });
}

// ===============================
// Alert Function
// ===============================
function showAlert(message, type) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;

    const container = document.querySelector('.container-fluid');
    container.insertBefore(alertDiv, container.firstChild);

    setTimeout(() => alertDiv.remove(), 5000);
}

// ===============================
// Document Count
// ===============================
function updateDocumentCount() {
    const rows = document.querySelectorAll('tbody tr');
    const countElement = document.querySelector('.card-header h6');
    if (countElement) {
        countElement.innerHTML = countElement.innerHTML.replace(
            /\(\d+ total\)/,
            `(${rows.length} total)`
        );
    }
}

// ===============================
// Init Tooltips
// ===============================
document.addEventListener('DOMContentLoaded', () => {
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[title]'));
    tooltipTriggerList.forEach(el => new bootstrap.Tooltip(el));
});

// ===============================
// Edit Category
// ===============================
function editCategory(documentId, currentCategoryId) {
    document.getElementById('editDocumentId').value = documentId;
    document.getElementById('editCategorySelect').value = currentCategoryId || '';

    const modal = new bootstrap.Modal(document.getElementById('editCategoryModal'));
    modal.show();
}

function updateCategory() {
    const documentId = document.getElementById('editDocumentId').value;
    const categoryId = document.getElementById('editCategorySelect').value;
    const categorySelect = document.getElementById('editCategorySelect');
    const selectedOption = categorySelect.options[categorySelect.selectedIndex];
    const categoryName = selectedOption.text;

    const updateBtn = event.target;
    const originalText = updateBtn.innerHTML;

    updateBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Updating...';
    updateBtn.disabled = true;

    fetch('../api/category.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            document_id: parseInt(documentId),
            category_id: categoryId ? parseInt(categoryId) : null
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('Category updated successfully!', 'success');

            // Update in TABLE
            document.querySelectorAll('tbody tr').forEach(row => {
                const downloadBtn = row.querySelector(`a[href*="download.php?id=${documentId}"]`);
                if (downloadBtn) {
                    const categoryCell = row.children[2];
                    if (categoryCell) {
                        categoryCell.innerHTML = data.category_name
                            ? `<span class="badge bg-info">${data.category_name}</span>`
                            : `<span class="text-muted">Uncategorized</span>`;
                    }
                }
            });

            // Update in CARD
            const card = document.querySelector(`button[onclick*="editCategory(${documentId}"]`)?.closest('.card');
            if (card) {
                const badgeContainer = card.querySelector('.card-body small.mb-1');
                if (badgeContainer) {
                    badgeContainer.innerHTML = data.category_name
                        ? `<span class="badge bg-info">${data.category_name}</span>`
                        : `<span class="text-muted">Uncategorized</span>`;
                }
                const editBtn = card.querySelector('button[title="Edit Category"]');
                if (editBtn) {
                    editBtn.setAttribute('onclick', `editCategory(${documentId}, ${categoryId || 'null'})`);
                }
            }

            // Close modal
            bootstrap.Modal.getInstance(document.getElementById('editCategoryModal')).hide();
        } else {
            showAlert(`Error updating category: ${data.message || 'Unknown error'}`, 'danger');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert(`Error updating category: ${error.message}`, 'danger');
    })
    .finally(() => {
        updateBtn.innerHTML = originalText;
        updateBtn.disabled = false;
    });
}


// Preview Document Function
let currentPreviewDocId = null;

function previewDocument(documentId, fileName, fileType) {
    console.log('Preview document:', documentId, fileName, fileType);
    
    currentPreviewDocId = documentId;
    
    // Show modal
    const modal = new bootstrap.Modal(document.getElementById('previewModal'));
    modal.show();
    
    // Update modal title
    document.querySelector('#previewModal .modal-title').innerHTML = 
        `<i class="fas fa-eye me-2"></i>Preview: ${fileName}`;
    
    // Update download button
    const downloadBtn = document.getElementById('downloadFromPreviewBtn');
    downloadBtn.onclick = () => downloadFromPreview();
    
    // Show loader
    document.getElementById('previewLoader').style.display = 'block';
    document.getElementById('previewContent').style.display = 'none';
    document.getElementById('previewError').style.display = 'none';
    
    // Load preview content
    loadPreviewContent(documentId, fileName, fileType);
}

function loadPreviewContent(documentId, fileName, fileType) {
    const previewContent = document.getElementById('previewContent');
    const previewLoader = document.getElementById('previewLoader');
    const previewError = document.getElementById('previewError');
    
    // Check if file type can be previewed
    const previewableTypes = ['pdf', 'jpg', 'jpeg', 'png', 'gif', 'txt'];
    const lowerFileType = fileType.toLowerCase();
    
    if (!previewableTypes.includes(lowerFileType)) {
        // Show error for non-previewable files
        previewLoader.style.display = 'none';
        previewError.style.display = 'block';
        return;
    }
    
    // Create preview content based on file type
    let previewHTML = '';
    
    if (lowerFileType === 'pdf') {
        previewHTML = `
            <div class="text-center p-3">
                <embed src="../api/preview.php?id=${documentId}" 
                       type="application/pdf" 
                       width="100%" 
                       height="600px"
                       style="border: none;">
                <p class="mt-2 text-muted">
                    <small>If PDF doesn't load, <a href="../api/download.php?id=${documentId}" target="_blank">click here to download</a></small>
                </p>
            </div>
        `;
    } else if (['jpg', 'jpeg', 'png', 'gif'].includes(lowerFileType)) {
        previewHTML = `
            <div class="text-center p-3">
                <img src="../api/preview.php?id=${documentId}" 
                     class="img-fluid" 
                     style="max-height: 600px; border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.1);"
                     alt="${fileName}"
                     onload="hidePreviewLoader()"
                     onerror="showPreviewError()">
            </div>
        `;
    } else if (lowerFileType === 'txt') {
        // Load text content via AJAX
        fetch(`../api/preview.php?id=${documentId}`)
            .then(response => response.text())
            .then(text => {
                previewHTML = `
                    <div class="p-4">
                        <pre style="white-space: pre-wrap; font-family: 'Courier New', monospace; background: #f8f9fa; padding: 20px; border-radius: 8px; max-height: 600px; overflow-y: auto;">${text}</pre>
                    </div>
                `;
                previewContent.innerHTML = previewHTML;
                previewLoader.style.display = 'none';
                previewContent.style.display = 'block';
            })
            .catch(error => {
                console.error('Error loading text preview:', error);
                showPreviewError();
            });
        return;
    }
    
    // Set the preview content
    previewContent.innerHTML = previewHTML;
    
    // For images, we'll hide loader when image loads
    // For PDF, hide loader immediately
    if (lowerFileType === 'pdf') {
        setTimeout(() => {
            previewLoader.style.display = 'none';
            previewContent.style.display = 'block';
        }, 1000);
    }
}

function hidePreviewLoader() {
    document.getElementById('previewLoader').style.display = 'none';
    document.getElementById('previewContent').style.display = 'block';
}

function showPreviewError() {
    document.getElementById('previewLoader').style.display = 'none';
    document.getElementById('previewContent').style.display = 'none';
    document.getElementById('previewError').style.display = 'block';
}

function downloadFromPreview() {
    if (currentPreviewDocId) {
        window.open(`../api/download.php?id=${currentPreviewDocId}`, '_blank');
    }
}

// Edit Category Functions
function editCategory(documentId, currentCategoryId) {
    console.log('Edit category called:', documentId, currentCategoryId);
    
    // Set the document ID
    document.getElementById('editDocumentId').value = documentId;
    
    // Set the current category
    const categorySelect = document.getElementById('editCategorySelect');
    categorySelect.value = currentCategoryId || '';
    
    // Show the modal
    const modal = new bootstrap.Modal(document.getElementById('editCategoryModal'));
    modal.show();
}

function updateCategory() {
    const documentId = document.getElementById('editDocumentId').value;
    const categoryId = document.getElementById('editCategorySelect').value;
    const categorySelect = document.getElementById('editCategorySelect');
    const selectedOption = categorySelect.options[categorySelect.selectedIndex];
    const categoryName = selectedOption.text;
    
    console.log('Updating category:', documentId, categoryId, categoryName);
    
    // Show loading state
    const updateBtn = event.target;
    const originalText = updateBtn.innerHTML;
    updateBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Updating...';
    updateBtn.disabled = true;
    
    // Send update request
    fetch('../api/category.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            document_id: parseInt(documentId),
            category_id: categoryId ? parseInt(categoryId) : null
        })
    })
    .then(response => response.json())
    .then(data => {
        console.log('Update response:', data);
        
        if (data.success) {
            showAlert('Category updated successfully!', 'success');
            
            // Update the category display in the table
            const rows = document.querySelectorAll('tbody tr');
            rows.forEach(row => {
                const downloadBtn = row.querySelector('a[href*="download.php?id=' + documentId + '"]');
                if (downloadBtn) {
                    const categoryCell = row.children[2]; // Category is the 3rd column (index 2)
                    if (categoryCell) {
                        if (data.category_name) {
                            categoryCell.innerHTML = `<span class="badge bg-info">${data.category_name}</span>`;
                        } else {
                            categoryCell.innerHTML = `<span class="text-muted">Uncategorized</span>`;
                        }
                    }
                    
                    // Update the edit button's onclick to reflect new category ID
                    const editBtn = row.querySelector('button[title="Edit Category"]');
                    if (editBtn) {
                        editBtn.setAttribute('onclick', `editCategory(${documentId}, ${categoryId || 'null'})`);
                    }
                }
            });
            
            // Close modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('editCategoryModal'));
            modal.hide();
            
        } else {
            showAlert('Error updating category: ' + (data.message || 'Unknown error'), 'danger');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Error updating category: ' + error.message, 'danger');
    })
    .finally(() => {
        // Reset button
        updateBtn.innerHTML = originalText;
        updateBtn.disabled = false;
    });
}


</script>


<?php require_once '../includes/footer.php'; ?>
