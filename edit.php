<?php
$page_title = 'Edit Document';
require_once 'includes/functions.php';
requireLogin();

// Get document ID
$doc_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($doc_id <= 0) {
    setAlert('Invalid document ID', 'danger');
    header('Location: documents.php');
    exit();
}

try {
    // Get document details
    $stmt = $db->prepare("SELECT * FROM documents WHERE id = ? AND user_id = ?");
    $stmt->execute([$doc_id, $_SESSION['user_id']]);
    $document = $stmt->fetch();
    
    if (!$document) {
        setAlert('Document not found or access denied', 'danger');
        header('Location: documents.php');
        exit();
    }
    
    // Get categories
    $stmt = $db->query("SELECT * FROM categories ORDER BY name");
    $categories = $stmt->fetchAll();
    
} catch (PDOException $e) {
    setAlert('Error loading document', 'danger');
    header('Location: documents.php');
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = sanitize($_POST['title']);
    $description = sanitize($_POST['description']);
    $tags = sanitize($_POST['tags']);
    $category_id = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;
    $is_public = isset($_POST['is_public']) ? 1 : 0;
    
    // Validation
    $errors = [];
    
    if (empty($title)) {
        $errors[] = 'Title is required';
    }
    
    if (empty($errors)) {
        try {
            $stmt = $db->prepare("
                UPDATE documents 
                SET title = ?, description = ?, tags = ?, category_id = ?, is_public = ?, updated_at = NOW()
                WHERE id = ? AND user_id = ?
            ");
            
            $stmt->execute([
                $title,
                $description,
                $tags,
                $category_id,
                $is_public,
                $doc_id,
                $_SESSION['user_id']
            ]);
            
            setAlert('Document updated successfully!', 'success');
            header("Location: view.php?id=$doc_id");
            exit();
            
        } catch (PDOException $e) {
            $errors[] = 'Error updating document';
        }
    }
    
    // If there are errors, display them
    if (!empty($errors)) {
        foreach ($errors as $error) {
            setAlert($error, 'danger');
        }
    }
}

require_once 'includes/header.php';
?>

<div class="container-fluid px-4">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                <div class="card-body py-4">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h1 class="display-6 fw-bold mb-2">
                                <i class="fas fa-edit me-3"></i>Edit Document
                            </h1>
                            <p class="lead mb-0">
                                Update document information and settings
                            </p>
                        </div>
                        <div class="col-md-4 text-end">
                            <a href="view.php?id=<?php echo $doc_id; ?>" class="btn btn-light btn-lg">
                                <i class="fas fa-arrow-left me-2"></i>Back to Document
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <!-- Current File Info -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-file me-2"></i>Current File
                    </h5>
                </div>
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="file-icon-large me-4">
                            <i class="<?php echo getFileIcon($document['file_type']); ?>" style="font-size: 3rem;"></i>
                        </div>
                        <div>
                            <h6 class="mb-1"><?php echo htmlspecialchars($document['original_filename']); ?></h6>
                            <p class="text-muted mb-1">
                                <?php echo formatFileSize($document['file_size']); ?> • 
                                <?php echo strtoupper($document['file_type']); ?> • 
                                <?php echo number_format($document['downloads']); ?> downloads
                            </p>
                            <small class="text-muted">
                                Uploaded on <?php echo date('F j, Y \a\t g:i A', strtotime($document['created_at'])); ?>
                            </small>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Edit Form -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-edit me-2"></i>Document Information
                    </h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <div class="row">
                            <div class="col-md-8">
                                <!-- Title -->
                                <div class="mb-3">
                                    <label for="title" class="form-label">
                                        <i class="fas fa-heading me-2"></i>Title *
                                    </label>
                                    <input type="text" class="form-control" id="title" name="title" 
                                           value="<?php echo htmlspecialchars($document['title']); ?>" 
                                           required maxlength="255">
                                    <div class="form-text">Give your document a descriptive title</div>
                                </div>
                                
                                <!-- Description -->
                                <div class="mb-3">
                                    <label for="description" class="form-label">
                                        <i class="fas fa-align-left me-2"></i>Description
                                    </label>
                                    <textarea class="form-control" id="description" name="description" 
                                              rows="4" maxlength="1000"><?php echo htmlspecialchars($document['description']); ?></textarea>
                                    <div class="form-text">Optional description of the document content</div>
                                </div>
                                
                                <!-- Tags -->
                                <div class="mb-3">
                                    <label for="tags" class="form-label">
                                        <i class="fas fa-tags me-2"></i>Tags
                                    </label>
                                    <input type="text" class="form-control" id="tags" name="tags" 
                                           value="<?php echo htmlspecialchars($document['tags']); ?>" 
                                           maxlength="500">
                                    <div class="form-text">Separate tags with commas (e.g., report, finance, 2024)</div>
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <!-- Category -->
                                <div class="mb-3">
                                    <label for="category_id" class="form-label">
                                        <i class="fas fa-folder me-2"></i>Category
                                    </label>
                                    <select class="form-select" id="category_id" name="category_id">
                                        <option value="">No Category</option>
                                        <?php foreach ($categories as $category): ?>
                                            <option value="<?php echo $category['id']; ?>" 
                                                    <?php echo $document['category_id'] == $category['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($category['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <!-- Visibility -->
                                <div class="mb-3">
                                    <label class="form-label">
                                        <i class="fas fa-eye me-2"></i>Visibility
                                    </label>
                                    <div class="form-check">
                                        
                                        <input class="form-check-input" type="checkbox" id="is_public" name="is_public" 
                                               <?php echo $document['is_public'] ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="is_public">
                                            <i class="fas fa-globe me-2"></i>Make this document public
                                        </label>
                                    </div>
                                    <div class="form-text">
                                        Public documents can be viewed and downloaded by anyone with the link
                                    </div>
                                </div>
                                
                                <!-- File Statistics -->
                                <div class="card bg-light">
                                    <div class="card-body">
                                        <h6 class="card-title">
                                            <i class="fas fa-chart-bar me-2"></i>Statistics
                                        </h6>
                                        <div class="stat-item">
                                            <i class="fas fa-download text-primary me-2"></i>
                                            <span class="fw-medium"><?php echo number_format($document['downloads']); ?></span>
                                            <small class="text-muted">downloads</small>
                                        </div>
                                        <div class="stat-item">
                                            <i class="fas fa-calendar text-success me-2"></i>
                                            <span class="fw-medium"><?php echo date('M j, Y', strtotime($document['created_at'])); ?></span>
                                            <small class="text-muted">uploaded</small>
                                        </div>
                                        <?php if ($document['updated_at']): ?>
                                        <div class="stat-item">
                                            <i class="fas fa-edit text-warning me-2"></i>
                                            <span class="fw-medium"><?php echo date('M j, Y', strtotime($document['updated_at'])); ?></span>
                                            <small class="text-muted">last updated</small>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Form Actions -->
                        <div class="row mt-4">
                            <div class="col-12">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <a href="view.php?id=<?php echo $doc_id; ?>" class="btn btn-outline-secondary">
                                            <i class="fas fa-times me-2"></i>Cancel
                                        </a>
                                    </div>
                                    <div>
                                        <button type="submit" class="btn btn-primary btn-lg">
                                            <i class="fas fa-save me-2"></i>Update Document
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Danger Zone -->
            <div class="card border-danger mt-4">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-exclamation-triangle me-2"></i>Danger Zone
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h6 class="text-danger">Delete Document</h6>
                            <p class="text-muted mb-0">
                                Once you delete a document, there is no going back. The file will be permanently 
                                removed from the server and all download links will stop working.
                            </p>
                        </div>
                        <div class="col-md-4 text-end">
                            <button type="button" class="btn btn-outline-danger" 
                                    onclick="confirmDelete(<?php echo $document['id']; ?>, '<?php echo addslashes($document['title']); ?>')">
                                <i class="fas fa-trash me-2"></i>Delete Document
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirm Delete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>Warning:</strong> This action cannot be undone!
                </div>
                <p>Are you sure you want to delete "<strong id="deleteDocumentTitle"></strong>"?</p>
                <p class="text-muted">The file will be permanently removed from the server and all download links will stop working.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteBtn">
                    <i class="fas fa-trash me-2"></i>Delete Document
                </button>
            </div>
        </div>
    </div>
</div>

<script>
let deleteDocumentId = null;

function confirmDelete(id, title) {
    deleteDocumentId = id;
    document.getElementById('deleteDocumentTitle').textContent = title;
    const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
    modal.show();
}

document.getElementById('confirmDeleteBtn').addEventListener('click', function() {
    if (deleteDocumentId) {
        deleteDocument(deleteDocumentId);
    }
});

function deleteDocument(id) {
    const btn = document.getElementById('confirmDeleteBtn');
    const originalText = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Deleting...';
    btn.disabled = true;
    
    fetch('api/delete.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ id: id })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert(data.message, 'success');
            setTimeout(() => {
                window.location.href = 'documents.php';
            }, 1500);
        } else {
            showAlert(data.message, 'danger');
            btn.innerHTML = originalText;
            btn.disabled = false;
        }
        
        // Hide modal
        const modal = bootstrap.Modal.getInstance(document.getElementById('deleteModal'));
        modal.hide();
    })
    .catch(error => {
        showAlert('Error deleting document. Please try again.', 'danger');
        btn.innerHTML = originalText;
        btn.disabled = false;
        
        const modal = bootstrap.Modal.getInstance(document.getElementById('deleteModal'));
        modal.hide();
    });
}

// Character counter for description
document.getElementById('description').addEventListener('input', function() {
    const maxLength = 1000;
    const currentLength = this.value.length;
    const remaining = maxLength - currentLength;
    
    let helpText = this.nextElementSibling;
    if (remaining < 100) {
        helpText.innerHTML = `${remaining} characters remaining`;
        helpText.className = remaining < 50 ? 'form-text text-warning' : 'form-text text-info';
    } else {
        helpText.innerHTML = 'Optional description of the document content';
        helpText.className = 'form-text';
    }
});

// Character counter for tags
document.getElementById('tags').addEventListener('input', function() {
    const maxLength = 500;
    const currentLength = this.value.length;
    const remaining = maxLength - currentLength;
    
    let helpText = this.nextElementSibling;
    if (remaining < 100) {
        helpText.innerHTML = `${remaining} characters remaining`;
        helpText.className = remaining < 50 ? 'form-text text-warning' : 'form-text text-info';
    } else {
        helpText.innerHTML = 'Separate tags with commas (e.g., report, finance, 2024)';
        helpText.className = 'form-text';
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>
