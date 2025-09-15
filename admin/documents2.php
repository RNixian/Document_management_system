<?php
$page_title = 'Manage Documents - Admin';
require_once '../includes/functions.php';
requireLogin();
requireAdmin();

// Get categories for dropdown
try {
    $stmt = $db->query("SELECT * FROM categories ORDER BY name");
    $categories = $stmt->fetchAll();
} catch (PDOException $e) {
    $categories = [];
}



// GET DIVISIONS - PUT THE NEW CODE HERE
$divisions = [];
try {
    $stmt = $db->query("SELECT id, name FROM division ORDER BY name");
    $divisions = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Error loading divisions: " . $e->getMessage();
    $divisions = [];
}

// Get filter parameters
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$user_filter = isset($_GET['user']) ? (int)$_GET['user'] : 0;
$type_filter = isset($_GET['type']) ? sanitize($_GET['type']) : '';
$status_filter = isset($_GET['status']) ? sanitize($_GET['status']) : '';
$division_filter = isset($_GET['division']) ? (int)$_GET['division'] : 0;

// Build query conditions
$where_conditions = [];
$params = [];

if (!empty($search)) {
    $where_conditions[] = "(d.title LIKE ? OR d.description LIKE ? OR d.original_name LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

if ($user_filter > 0) {
    $where_conditions[] = "d.user_id = ?";
    $params[] = $user_filter;
}

if (!empty($type_filter)) {
    $where_conditions[] = "d.file_type = ?";
    $params[] = $type_filter;
}


if ($division_filter > 0) {
    $where_conditions[] = "d.division_id = ?";
    $params[] = $division_filter;
}

if (!empty($status_filter)) {
    if ($status_filter === 'public') {
        $where_conditions[] = "d.is_public = 1";
    } elseif ($status_filter === 'private') {
        $where_conditions[] = "d.is_public = 0";
    }
    
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Get all documents with user information
try {
 $sql = "
    SELECT 
        d.*, 
        u.username, 
        u.full_name, 
        c.name AS category_name,
        c.color AS category_color,
        c.icon AS category_icon,
        v.name AS division_name
    FROM documents d
    JOIN users u ON d.user_id = u.id
    LEFT JOIN categories c ON d.category_id = c.id
    LEFT JOIN division v ON d.division_id = v.id
    $where_clause
    ORDER BY d.created_at DESC
";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $documents = $stmt->fetchAll();
    
    // Get users for filter dropdown
    $stmt = $db->query("SELECT id, username, full_name FROM users ORDER BY username");
    $users = $stmt->fetchAll();
    
    // Get file types for filter
    $stmt = $db->query("SELECT DISTINCT file_type FROM documents ORDER BY file_type");
    $file_types = $stmt->fetchAll();
    
} catch (PDOException $e) {
    $documents = [];
    $users = [];
    $file_types = [];
    $error = "Error loading documents: " . $e->getMessage();
}

require_once '../includes/admin_header.php';
?>

<div class="container-fluid px-4">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0" style="background: linear-gradient(135deg, #dc3545 0%, #c82333 100%); color: white;">
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
                            <a href="dashboard.php" class="btn btn-outline-light">
                                <i class="fas fa-arrow-left me-2"></i>Dashboard
                            </a>
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
                    <form method="GET" action="" class="row g-3">
                        <!-- Search -->
                        <div class="col-md-3">
                            <label for="search" class="form-label">Search</label>
                            <input type="text" class="form-control" id="search" name="search" 
                                   placeholder="Title, description, filename..." 
                                   value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        
                        <!-- User Filter -->
                        <div class="col-md-3">
                            <label for="user" class="form-label">User</label>
                            <select class="form-select" id="user" name="user">
                                <option value="">All Users</option>
                                <?php foreach ($users as $user): ?>
                                    <option value="<?php echo $user['id']; ?>" 
                                            <?php echo $user_filter == $user['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($user['full_name'] ?? $user['username']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <!-- File Type Filter -->
                        <div class="col-md-2">
                            <label for="type" class="form-label">File Type</label>
                            <select class="form-select" id="type" name="type">
                                <option value="">All Types</option>
                                <?php foreach ($file_types as $type): ?>
                                    <option value="<?php echo $type['file_type']; ?>" 
                                            <?php echo $type_filter === $type['file_type'] ? 'selected' : ''; ?>>
                                        <?php echo strtoupper($type['file_type']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <!-- Status Filter -->
                        <div class="col-md-2">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status">
                                <option value="">All Status</option>
                                <option value="public" <?php echo $status_filter === 'public' ? 'selected' : ''; ?>>Public</option>
                                <option value="private" <?php echo $status_filter === 'private' ? 'selected' : ''; ?>>Private</option>
                            </select>
                        </div>
                        
                        <!-- Filter Button -->
                        <div class="col-md-2">
                            <label class="form-label">&nbsp;</label>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search me-1"></i>Filter
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
<!-- Add this filter section before your documents table -->
<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold">Filter Documents</h6>
    </div>
    <div class="card-body">
        <form method="GET" action="">
            <div class="row">
                <div class="col-md-3">
                    <label for="search" class="form-label">Search</label>
                    <input type="text" class="form-control" id="search" name="search" 
                           value="<?php echo htmlspecialchars($search); ?>" 
                           placeholder="Search documents...">
                </div>
                
                <div class="col-md-2">
                    <label for="user" class="form-label">User</label>
                    <select class="form-select" id="user" name="user">
                        <option value="">All Users</option>
                        <?php foreach ($users as $user): ?>
                            <option value="<?php echo $user['id']; ?>" 
                                    <?php echo $user_filter == $user['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($user['full_name'] ?: $user['username']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
            <div class="col-md-2">
    <label for="division" class="form-label">Division/Unit</label>
    <select class="form-select" id="division" name="division">
        <option value="">All Divisions</option>
        <?php foreach ($divisions as $division): ?>
            <option value="<?php echo $division['id']; ?>" 
                    <?php echo $division_filter == $division['id'] ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($division['name']); ?>
            </option>
        <?php endforeach; ?>
    </select>
</div>
                
                <div class="col-md-2">
                    <label for="type" class="form-label">File Type</label>
                    <select class="form-select" id="type" name="type">
                        <option value="">All Types</option>
                        <?php foreach ($file_types as $type): ?>
                            <option value="<?php echo htmlspecialchars($type['file_type']); ?>" 
                                    <?php echo $type_filter == $type['file_type'] ? 'selected' : ''; ?>>
                                <?php echo strtoupper($type['file_type']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-2">
                    <label for="status" class="form-label">Status</label>
                    <select class="form-select" id="status" name="status">
                        <option value="">All Status</option>
                        <option value="public" <?php echo $status_filter == 'public' ? 'selected' : ''; ?>>Public</option>
                        <option value="private" <?php echo $status_filter == 'private' ? 'selected' : ''; ?>>Private</option>
                    </select>
                </div>
                
                <div class="col-md-1">
                    <label class="form-label">&nbsp;</label>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </div>
            </div>
            
            <div class="row mt-2">
                <div class="col-12">
                    <a href="documents.php" class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-times me-1"></i>Clear Filters
                    </a>
                    <?php if (!empty($search) || $user_filter > 0 || $division_filter > 0 || !empty($type_filter) || !empty($status_filter)): ?>
                        <span class="badge bg-info ms-2">Filters Active</span>
                    <?php endif; ?>
                </div>
            </div>
        </form>
    </div>
</div>

    <!-- Documents Table -->
    <div class="card shadow">
        <div class="card-header py-3">
            <div class="d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold text-">
                    All Documents (<?php echo count($documents); ?> total)
                </h6>
                <?php if (!empty($search) || $user_filter > 0 || !empty($type_filter) || !empty($status_filter)): ?>
                    <a href="documents.php" class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-times me-1"></i>Clear Filters
                    </a>
                <?php endif; ?>
            </div>
        </div>
        <div class="card-body">
            <?php if (empty($documents)): ?>
                <div class="text-center py-4">
                    <i class="fas fa-folder-open fa-3x text-muted mb-3"></i>
                    <h5>No documents found</h5>
                    <p class="text-muted">
                        <?php if (!empty($search) || $user_filter > 0 || !empty($type_filter) || !empty($status_filter)): ?>
                            Try adjusting your search criteria or filters.
                        <?php else: ?>
                            No documents have been uploaded yet.
                        <?php endif; ?>
                    </p>
                </div>
            <?php else: ?>
                <div class="table-responsive">




                    <table class="table table-bordered table-hover" width="100%" cellspacing="0">
                        <thead class="table-dark">
                            <tr>
                                <th>Document</th>
                                <th>Owner</th>
                                <th>Category</th>
                                <th>Size</th>
                                <th>Uploaded</th>
                                <th>Downloads</th>

                                  <th>Division/Unit</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($documents as $doc): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <i class="<?php echo getFileIcon($doc['file_type'] ?? 'unknown'); ?> me-2"></i>
                                        <div>
                                            <div class="fw-bold"><?php echo htmlspecialchars($doc['title']); ?></div>
                                            <small class="text-muted"><?php echo htmlspecialchars($doc['original_name']); ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div>
                                        <strong><?php echo htmlspecialchars($doc['full_name'] ?? $doc['username']); ?></strong>
                                        <br><small class="text-muted">@<?php echo htmlspecialchars($doc['username']); ?></small>
                                    </div>
                                </td>
                                <td>
                                    <?php if ($doc['category_name']): ?>
                                        <span class="badge bg-info"><?php echo htmlspecialchars($doc['category_name']); ?></span>
                                    <?php else: ?>
                                        <span class="text-muted">Uncategorized</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo formatFileSize($doc['file_size']); ?></td>
                                <td>
                                    <small>
                                        <?php echo date('M j, Y', strtotime($doc['created_at'])); ?><br>
                                        <?php echo date('g:i A', strtotime($doc['created_at'])); ?>
                                    </small>
                                </td>
                                <td>
                                    <span class="badge bg-success">
                                        <i class="fas fa-download me-1"></i>
                                        <?php echo number_format($doc['downloads'] ?? 0); ?>
                                    </span>
                                    </td>
                            <td>
                                <span class="badge bg-secondary">
                                  <?php echo htmlspecialchars($doc['division_name'] ?? 'No Division'); ?>
                                    </span>
                            </td>

                                <td>
                                    <?php if ($doc['is_public']): ?>
                                        <span class="badge bg-success">
                                            <i class="fas fa-globe me-1"></i>Public
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">
                                            <i class="fas fa-lock me-1"></i>Private
                                        </span>
                                    <?php endif; ?>
                                </td>
<td>
    <div class="btn-group" role="group">
        <a href="../api/download.php?id=<?php echo $doc['id']; ?>"
           class="btn btn-sm btn-outline-success" title="Download">
            <i class="fas fa-download"></i>
        </a>
        <button type="button" class="btn btn-sm btn-outline-primary"
                onclick="previewDocument(<?php echo $doc['id']; ?>, '<?php echo addslashes($doc['original_name']); ?>', '<?php echo $doc['file_type']; ?>')"
                title="Preview">
            <i class="fas fa-eye"></i>
        </button>
        <button type="button" class="btn btn-sm btn-outline-warning"
                onclick="editCategory(<?php echo $doc['id']; ?>, <?php echo $doc['category_id'] ?: 'null'; ?>)"
                title="Edit Category">
            <i class="fas fa-tag"></i>
        </button>
        <button type="button" class="btn btn-sm btn-outline-danger"
                onclick="deleteDocument(<?php echo $doc['id']; ?>, '<?php echo addslashes($doc['title']); ?>')"
                title="Delete">
            <i class="fas fa-trash"></i>
        </button>
    </div>
</td>

                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>


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
                            <input type="file" id="fileInput" name="files[]" multiple accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.jpg,.jpeg,.png,.gif" style="display: none;">
                            <button type="button" class="btn btn-outline-primary" onclick="document.getElementById('fileInput').click()">
                                <i class="fas fa-folder-open me-2"></i>Choose Files
                            </button>
                        </div>
                    </div>
                    
                    <!-- Selected Files Display -->
                    <div id="selectedFiles" class="mt-3" style="display: none;">
                        <h6>Selected Files:</h6>
                        <div id="filesList"></div>
                    </div>
                    
                    <!-- Document Details -->
                    <div class="row mt-4">
                        <div class="col-md-4">
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
                        
                        <div class="col-md-4">
                            <label for="division" class="form-label">Division/Unit</label>
                            <select class="form-select" id="uploadDivision" name="division">
                                <option value="">Select Division</option>
                                <?php foreach ($divisions as $division): ?>
                                    <option value="<?php echo $division['id']; ?>">
                                        <?php echo htmlspecialchars($division['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-4">
                            <label for="visibility" class="form-label">Visibility</label>
                            <select class="form-select" id="visibility" name="visibility">
                                <option value="0">Private (Only You)</option>
                                <option value="1">Public (Everyone)</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row mt-3">
                        <div class="col-12">
                            <label for="description" class="form-label">Description (Optional)</label>
                            <textarea class="form-control" id="description" name="description" rows="3" placeholder="Enter document description..."></textarea>
                        </div>
                    </div>
                    
                    <div class="row mt-3">
                        <div class="col-12">
                            <label for="tags" class="form-label">Tags (Optional)</label>
                            <input type="text" class="form-control" id="tags" name="tags" placeholder="Enter tags separated by commas (e.g., report, finance, 2024)">
                        </div>
                    </div>
                    
                    <!-- Upload Progress -->
                    <div id="uploadProgress" class="mt-3" style="display: none;">
                        <div class="progress">
                            <div class="progress-bar" role="progressbar" style="width: 0%"></div>
                        </div>
                        <small class="text-muted mt-1 d-block">Uploading files...</small>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="uploadBtn" onclick="uploadFiles()">
                    <i class="fas fa-upload me-2"></i>Upload Documents
                </button>
            </div>
        </div>
    </div>
</div>


<style>
.table th {
    border-top: none;
    font-weight: 600;
    font-size: 0.875rem;
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
    background: linear-gradient(45deg,rgb(114, 182, 6),rgb(212, 168, 21));
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


.upload-area {
    transition: all 0.3s ease;
}

.upload-area:hover {
    border-color: #007bff !important;
    background-color: #f8f9fa !important;
}

.upload-area.dragover {
    border-color: #007bff !important;
    background-color: #e3f2fd !important;
}

#selectedFiles .alert {
    margin-bottom: 0.5rem;
}

#uploadProgress .progress {
    height: 20px;
}

.file-icon {
    font-size: 1.2em;
}

/* Modal improvements */
.modal-lg {
    max-width: 800px;
}

.form-label {
    font-weight: 600;
    color: #495057;
}

.btn-outline-primary:hover {
    transform: translateY(-1px);
}

</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('Admin documents page loaded');
    
    const uploadArea = document.getElementById('uploadArea');
    const fileInput = document.getElementById('fileInput');
    const uploadForm = document.getElementById('uploadForm');
    const uploadBtn = document.getElementById('uploadBtn');
    const filePreview = document.getElementById('filePreview');
    const fileList = document.getElementById('fileList');
    const clearBtn = document.getElementById('clearBtn');
    
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
    
    // Reset form
    function resetForm() {
        fileInput.value = '';
        filePreview.style.display = 'none';
        uploadBtn.disabled = true;
        selectedFiles = [];
        
        document.getElementById('category').value = '';
        document.getElementById('visibility').value = '0';
        document.getElementById('tags').value = '';
        document.getElementById('description').value = '';
        
        const progressContainer = document.querySelector('.upload-progress');
        progressContainer.style.display = 'none';
        
        uploadBtn.innerHTML = '<i class="fas fa-upload me-2"></i>Upload Documents';
    }
    
    window.resetForm = resetForm;
});

// Delete document function
function deleteDocument(id, title) {
    if (confirm('Are you sure you want to delete "' + title + '"? This action cannot be undone.')) {
        // Show loading state
        const btn = event.target.closest('button');
        const originalContent = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        btn.disabled = true;
        
        fetch('../api/delete.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ id: id })
        })
        .then(response => {
            console.log('Delete response status:', response.status);
            return response.json();
        })
        .then(data => {
            console.log('Delete response:', data);
            if (data.success) {
                showAlert('Document deleted successfully', 'success');
                // Remove row from table
                btn.closest('tr').remove();
                // Update document count
                updateDocumentCount();
            } else {
                showAlert('Error deleting document: ' + data.message, 'danger');
                // Restore button
                btn.innerHTML = originalContent;
                btn.disabled = false;
            }
        })
        .catch(error => {
            console.error('Delete error:', error);
            showAlert('Error deleting document', 'danger');
            // Restore button
            btn.innerHTML = originalContent;
            btn.disabled = false;
        });
    }
}

// Show alert function
function showAlert(message, type) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    // Insert at the top of the container
    const container = document.querySelector('.container-fluid');
    container.insertBefore(alertDiv, container.firstChild);
    
    // Auto-dismiss after 5 seconds
    setTimeout(() => {
        if (alertDiv.parentNode) {
            alertDiv.remove();
        }
    }, 5000);
}

// Update document count in header
function updateDocumentCount() {
    const rows = document.querySelectorAll('tbody tr');
    const countElement = document.querySelector('.card-header h6');
    if (countElement) {
        countElement.innerHTML = countElement.innerHTML.replace(/\(\d+ total\)/, `(${rows.length} total)`);
    }
}

// Initialize tooltips
document.addEventListener('DOMContentLoaded', function() {
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[title]'));
    tooltipTriggerList.map(function(tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});



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



// File upload handling
let selectedFiles = [];

document.addEventListener('DOMContentLoaded', function() {
    const uploadArea = document.getElementById('uploadArea');
    const fileInput = document.getElementById('fileInput');
    const selectedFilesDiv = document.getElementById('selectedFiles');
    const filesList = document.getElementById('filesList');

    // Drag and drop functionality
    uploadArea.addEventListener('dragover', function(e) {
        e.preventDefault();
        uploadArea.style.borderColor = '#007bff';
        uploadArea.style.backgroundColor = '#f8f9fa';
    });

    uploadArea.addEventListener('dragleave', function(e) {
        e.preventDefault();
        uploadArea.style.borderColor = '#ddd';
        uploadArea.style.backgroundColor = 'transparent';
    });

    uploadArea.addEventListener('drop', function(e) {
        e.preventDefault();
        uploadArea.style.borderColor = '#ddd';
        uploadArea.style.backgroundColor = 'transparent';
        
        const files = Array.from(e.dataTransfer.files);
        handleFiles(files);
    });

    // Click to upload
    uploadArea.addEventListener('click', function() {
        fileInput.click();
    });

    fileInput.addEventListener('change', function() {
        const files = Array.from(this.files);
        handleFiles(files);
    });

    function handleFiles(files) {
        selectedFiles = files;
        displaySelectedFiles();
    }

    function displaySelectedFiles() {
        if (selectedFiles.length === 0) {
            selectedFilesDiv.style.display = 'none';
            return;
        }

        selectedFilesDiv.style.display = 'block';
        filesList.innerHTML = '';

        selectedFiles.forEach((file, index) => {
            const fileItem = document.createElement('div');
            fileItem.className = 'alert alert-info d-flex justify-content-between align-items-center';
            fileItem.innerHTML = `
                <div>
                    <i class="${getFileIcon(file.name)} me-2"></i>
                    <strong>${file.name}</strong>
                    <small class="text-muted ms-2">(${formatFileSize(file.size)})</small>
                </div>
                <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeFile(${index})">
                    <i class="fas fa-times"></i>
                </button>
            `;
            filesList.appendChild(fileItem);
        });
    }

    // Make functions global
    window.removeFile = function(index) {
        selectedFiles.splice(index, 1);
        displaySelectedFiles();
    };

    window.getFileIcon = function(filename) {
        const ext = filename.split('.').pop().toLowerCase();
        const icons = {
            'pdf': 'fas fa-file-pdf text-danger',
            'doc': 'fas fa-file-word text-primary',
            'docx': 'fas fa-file-word text-primary',
            'xls': 'fas fa-file-excel text-success',
            'xlsx': 'fas fa-file-excel text-success',
            'ppt': 'fas fa-file-powerpoint text-warning',
            'pptx': 'fas fa-file-powerpoint text-warning',
            'txt': 'fas fa-file-alt text-secondary',
            'jpg': 'fas fa-file-image text-info',
            'jpeg': 'fas fa-file-image text-info',
            'png': 'fas fa-file-image text-info',
            'gif': 'fas fa-file-image text-info'
        };
        return icons[ext] || 'fas fa-file text-secondary';
    };

    window.formatFileSize = function(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    };
});

// Upload function
function uploadFiles() {
    if (selectedFiles.length === 0) {
        alert('Please select files to upload');
        return;
    }

    const formData = new FormData();
    const category = document.getElementById('category').value;
    const division = document.getElementById('uploadDivision').value;
    const visibility = document.getElementById('visibility').value;
    const description = document.getElementById('description').value;
    const tags = document.getElementById('tags').value;

    // Add files to FormData
    selectedFiles.forEach(file => {
        formData.append('files[]', file);
    });

    // Add other form data
    formData.append('category', category);
    formData.append('division', division);
    formData.append('visibility', visibility);
    formData.append('description', description);
    formData.append('tags', tags);

    // Show progress
    document.getElementById('uploadProgress').style.display = 'block';
    document.getElementById('uploadBtn').disabled = true;

    // Upload files
    fetch('../api/upload_documents.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Files uploaded successfully!');
            location.reload(); // Refresh the page to show new documents
        } else {
            alert('Upload failed: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Upload failed: ' + error.message);
    })
    .finally(() => {
        document.getElementById('uploadProgress').style.display = 'none';
        document.getElementById('uploadBtn').disabled = false;
    });
}

// Add this to your existing JavaScript section
function deleteDocument(id, title) {
    if (confirm(`Are you sure you want to delete "${title}"? This action cannot be undone.`)) {
        fetch('../api/delete_document.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ id: id })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Document deleted successfully!');
                location.reload();
            } else {
                alert('Delete failed: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Delete failed: ' + error.message);
        });
    }
}


</script>


<?php require_once '../includes/footer.php'; ?>
