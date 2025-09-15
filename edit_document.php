<?php
$page_title = 'Edit Document';
require_once 'includes/functions.php';
requireLogin();

// Get document ID
$document_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($document_id <= 0) {
    setAlert('Invalid document ID', 'danger');
    header('Location: documents.php');
    exit;
}

// Get document details and verify ownership
try {
    $stmt = $db->prepare("SELECT d.*, c.name as category_name FROM documents d LEFT JOIN categories c ON d.category_id = c.id WHERE d.id = ? AND d.user_id = ?");
    $stmt->execute([$document_id, $_SESSION['user_id']]);
    $document = $stmt->fetch();
    
    if (!$document) {
        setAlert('Document not found or access denied', 'danger');
        header('Location: documents.php');
        exit;
    }
    
    // Get categories for dropdown
    $stmt = $db->query("SELECT * FROM categories ORDER BY name");
    $categories = $stmt->fetchAll();
    
} catch (PDOException $e) {
    setAlert('Error loading document: ' . $e->getMessage(), 'danger');
    header('Location: documents.php');
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = sanitize($_POST['title'] ?? '');
    $description = sanitize($_POST['description'] ?? '');
    $category_id = isset($_POST['category_id']) ? (int)$_POST['category_id'] : null;
    $tags = sanitize($_POST['tags'] ?? '');
    $is_public = isset($_POST['is_public']) ? 1 : 0;
    
    // Validation
    $errors = [];
    
    if (empty($title)) {
        $errors[] = 'Title is required';
    }
    
    if (strlen($title) > 255) {
        $errors[] = 'Title must be less than 255 characters';
    }
    
    if (strlen($description) > 1000) {
        $errors[] = 'Description must be less than 1000 characters';
    }
    
    if ($category_id && $category_id > 0) {
        // Verify category exists
        $stmt = $db->prepare("SELECT id FROM categories WHERE id = ?");
        $stmt->execute([$category_id]);
        if (!$stmt->fetch()) {
            $errors[] = 'Invalid category selected';
        }
    } else {
        $category_id = null;
    }
    
    if (empty($errors)) {
        try {
            // Update document
            $stmt = $db->prepare("
                UPDATE documents 
                SET title = ?, description = ?, category_id = ?, tags = ?, is_public = ?, updated_at = NOW()
                WHERE id = ? AND user_id = ?
            ");
            
            $result = $stmt->execute([
                $title,
                $description,
                $category_id,
                $tags,
                $is_public,
                $document_id,
                $_SESSION['user_id']
            ]);
            
            if ($result) {
                setAlert('Document updated successfully!', 'success');
                header('Location: documents.php');
                exit;
            } else {
                $errors[] = 'Failed to update document';
            }
            
        } catch (PDOException $e) {
            $errors[] = 'Database error: ' . $e->getMessage();
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

<style>
/* Enhanced form styling */
.edit-document-container {
    background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
    min-block-size: 100vh;
    padding: 2rem 0;
}

.edit-card {
    background: #fff;
    border-radius: 20px;
    box-shadow: 0 10px 40px rgba(0,0,0,0.1);
    border: none;
    overflow: hidden;
}

.edit-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 2rem;
    border-radius: 20px 20px 0 0;
}

.edit-header h1 {
    color: white !important;
    font-weight: 700;
    margin-block-end: 0.5rem;
}

.edit-header .lead {
    color: rgba(255, 255, 255, 0.9);
    margin-block-end: 0;
}

.form-floating {
    margin-block-end: 1.5rem;
}

.form-floating > .form-control,
.form-floating > .form-select {
    border: 2px solid #e9ecef;
    border-radius: 15px;
    padding: 1rem 0.75rem;
    font-size: 1rem;
    transition: all 0.3s ease;
}

.form-floating > .form-control:focus,
.form-floating > .form-select:focus {
    border-color: #667eea;
    box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
}

.form-floating > label {
    color: #6c757d;
    font-weight: 500;
}

.form-check {
    background: #f8f9fc;
    border-radius: 15px;
    padding: 1.5rem;
    border: 2px solid #e9ecef;
    transition: all 0.3s ease;
}

.form-check:hover {
    border-color: #667eea;
    background: rgba(102, 126, 234, 0.05);
}

.form-check-input:checked {
    background-color: #667eea;
    border-color: #667eea;
}

.btn-update {
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
    border: none;
    color: white;
    padding: 1rem 2rem;
    border-radius: 15px;
    font-weight: 600;
    font-size: 1.1rem;
    transition: all 0.3s ease;
    box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
}

.btn-update:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(40, 167, 69, 0.4);
    color: white;
}

.btn-cancel {
    background: linear-gradient(135deg, #6c757d 0%, #495057 100%);
    border: none;
    color: white;
    padding: 1rem 2rem;
    border-radius: 15px;
    font-weight: 600;
    font-size: 1.1rem;
    transition: all 0.3s ease;
    box-shadow: 0 4px 15px rgba(108, 117, 125, 0.3);
}

.btn-cancel:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(108, 117, 125, 0.4);
    color: white;
}

.document-info {
    background: linear-gradient(135deg, #f8f9fc 0%, #eaecf4 100%);
    border-radius: 15px;
    padding: 1.5rem;
    margin-block-end: 2rem;
    border: 1px solid #e3e6f0;
}

.info-item {
    display: flex;
    align-items: center;
    margin-block-end: 0.75rem;
    font-size: 0.95rem;
    color: #6c757d;
}

.info-item:last-child {
    margin-block-end: 0;
}

.info-item i {
    inline-size: 20px;
    margin-inline-end: 10px;
    color: #667eea;
}

.tag-input-container {
    position: relative;
}

.tag-suggestions {
    position: absolute;
    inset-block-start: 100%;
    inset-inline-start: 0;
    inset-inline-end: 0;
    background: white;
    border: 1px solid #e9ecef;
    border-radius: 0 0 15px 15px;
    max-block-size: 200px;
    overflow-y: auto;
    z-index: 1000;
    display: none;
}

.tag-suggestion {
    padding: 0.75rem 1rem;
    cursor: pointer;
    border-block-end: 1px solid #f8f9fc;
    transition: background-color 0.2s ease;
}

.tag-suggestion:hover {
    background-color: #f8f9fc;
}

.tag-suggestion:last-child {
    border-block-end: none;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .edit-document-container {
        padding: 1rem 0;
    }
    
    .edit-header {
        padding: 1.5rem;
    }
    
    .edit-header h1 {
        font-size: 1.75rem;
    }
    
    .btn-update,
    .btn-cancel {
        inline-size: 100%;
        margin-block-end: 1rem;
    }
}

/* Loading animation */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.edit-card {
    animation: fadeInUp 0.6s ease-out;
}

/* Character counter */
.char-counter {
    font-size: 0.875rem;
    color: #6c757d;
    text-align: end;
    margin-block-start: 0.25rem;
}

.char-counter.warning {
    color: #ffc107;
}

.char-counter.danger {
    color: #dc3545;
}
</style>

<div class="edit-document-container">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8 col-md-10">
                <div class="card edit-card">
                    <!-- Header -->
                    <div class="edit-header">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <h1 class="display-6 mb-2">
                                    <i class="fas fa-edit me-3"></i>Edit Document
                                </h1>
                                <p class="lead">Update your document information</p>
                            </div>
                            <div class="col-md-4 text-end">
                                <a href="documents.php" class="btn btn-light btn-lg">
                                    <i class="fas fa-arrow-left me-2"></i>Back to Documents
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card-body p-4">
                        <!-- Document Info -->
                        <div class="document-info">
                            <h5 class="mb-3">
                                <i class="fas fa-info-circle me-2 text-primary"></i>Document Information
                            </h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="info-item">
                                        <i class="fas fa-file"></i>
                                        <span><strong>File:</strong> <?php echo htmlspecialchars($document['original_name']); ?></span>
                                    </div>
                                    <div class="info-item">
                                        <i class="fas fa-weight"></i>
                                        <span><strong>Size:</strong> <?php echo formatFileSize($document['file_size']); ?></span>
                                    </div>
                                    <div class="info-item">
                                        <i class="fas fa-file-alt"></i>
                                        <span><strong>Type:</strong> <?php echo strtoupper($document['file_type']); ?></span>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info-item">
                                        <i class="fas fa-calendar-plus"></i>
                                        <span><strong>Uploaded:</strong> <?php echo date('M j, Y g:i A', strtotime($document['created_at'])); ?></span>
                                    </div>
                                    <div class="info-item">
                                        <i class="fas fa-download"></i>
                                        <span><strong>Downloads:</strong> <?php echo number_format($document['downloads'] ?? 0); ?></span>
                                    </div>
                                    <div class="info-item">
                                        <i class="fas fa-<?php echo $document['is_public'] ? 'globe text-success' : 'lock text-secondary'; ?>"></i>
                                        <span><strong>Visibility:</strong> <?php echo $document['is_public'] ? 'Public' : 'Private'; ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Edit Form -->
                        <form method="POST" action="" id="editDocumentForm">
                            <div class="row">
                                <div class="col-12">
                                    <!-- Title -->
                                    <div class="form-floating">
                                        <input type="text" class="form-control" id="title" name="title" 
                                               placeholder="Document Title" value="<?php echo htmlspecialchars($document['title']); ?>" 
                                               required maxlength="255">
                                        <label for="title">
                                            <i class="fas fa-heading me-2"></i>Document Title *
                                        </label>
                                        <div class="char-counter" id="titleCounter">0/255</div>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <!-- Category -->
                                    <div class="form-floating">
                                        <br>    
                                        <select class="form-select" id="category_id" name="category_id">
                                            <option value="">No Category</option>
                                            <?php foreach ($categories as $category): ?>
                                                <option value="<?php echo $category['id']; ?>" 
                                                        <?php echo $document['category_id'] == $category['id'] ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($category['name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <label for="category_id">
                                            <i class="fas fa-folder me-2"></i>Category
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <!-- Tags -->
                                    <div class="form-floating tag-input-container">
                                        <input type="text" class="form-control" id="tags" name="tags" 
                                               placeholder="Tags (comma separated)" value="<?php echo htmlspecialchars($document['tags']); ?>">
                                        <label for="tags">
                                            <i class="fas fa-tags me-2"></i>Tags (comma separated)
                                        </label>
                                        <div class="tag-suggestions" id="tagSuggestions"></div>
                                    </div>
                                </div>
                                
                                <div class="col-12">
                                    <!-- Description -->
                                    <div class="form-floating">
                                        <textarea class="form-control" id="description" name="description" 
                                                  placeholder="Document Description" style="block-size: 120px;" maxlength="1000"><?php echo htmlspecialchars($document['description']); ?></textarea>
                                        <label for="description">
                                            <i class="fas fa-align-left me-2"></i>Description
                                        </label>
                                        <div class="char-counter" id="descriptionCounter">0/1000</div>
                                    </div>
                                </div>
                                
                                <div class="col-12">
                                    <!-- Public/Private Toggle -->
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="is_public" name="is_public" 
                                               <?php echo $document['is_public'] ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="is_public">
                                            <div class="d-flex align-items-center">
                                                <i class="fas fa-globe me-3 text-success"></i>
                                                <div>
                                                    <strong>Make this document public</strong>
                                                    <br>
                                                    <small class="text-muted">
                                                        Public documents can be viewed and downloaded by anyone. 
                                                        Private documents are only visible to you.
                                                    </small>
                                                </div>
                                            </div>
                                        </label>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Action Buttons -->
                            <div class="row mt-4">
                                <div class="col-12 text-center">
                                    <button type="submit" class="btn btn-update me-3" id="updateBtn">
                                        <i class="fas fa-save me-2"></i>Update Document
                                    </button>
                                    <a href="documents.php" class="btn btn-cancel">
                                        <i class="fas fa-times me-2"></i>Cancel
                                    </a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Character counters
    const titleInput = document.getElementById('title');
    const descriptionInput = document.getElementById('description');
    const titleCounter = document.getElementById('titleCounter');
    const descriptionCounter = document.getElementById('descriptionCounter');
    
    // Update character counters
    function updateCharCounter(input, counter, maxLength) {
        const currentLength = input.value.length;
        counter.textContent = `${currentLength}/${maxLength}`;
        
        // Update counter color based on usage
        counter.classList.remove('warning', 'danger');
        if (currentLength > maxLength * 0.8) {
            counter.classList.add('warning');
        }
        if (currentLength > maxLength * 0.95) {
            counter.classList.add('danger');
        }
    }
    
    // Initialize counters
    updateCharCounter(titleInput, titleCounter, 255);
    updateCharCounter(descriptionInput, descriptionCounter, 1000);
    
    // Add event listeners for real-time updates
    titleInput.addEventListener('input', function() {
        updateCharCounter(this, titleCounter, 255);
    });
    
    descriptionInput.addEventListener('input', function() {
        updateCharCounter(this, descriptionCounter, 1000);
    });
    
    // Tag suggestions functionality
    const tagsInput = document.getElementById('tags');
    const tagSuggestions = document.getElementById('tagSuggestions');
    
    // Common tag suggestions
    const commonTags = [
        'document', 'report', 'presentation', 'spreadsheet', 'image', 'pdf', 
        'important', 'draft', 'final', 'review', 'archive', 'personal', 
        'work', 'project', 'meeting', 'contract', 'invoice', 'receipt',
        'manual', 'guide', 'tutorial', 'reference', 'backup', 'template'
    ];
    
    tagsInput.addEventListener('input', function() {
        const inputValue = this.value.toLowerCase();
        const lastTag = inputValue.split(',').pop().trim();
        
        if (lastTag.length > 0) {
            const suggestions = commonTags.filter(tag => 
                tag.toLowerCase().includes(lastTag) && 
                !inputValue.includes(tag)
            );
            
            if (suggestions.length > 0) {
                showTagSuggestions(suggestions, lastTag);
            } else {
                hideTagSuggestions();
            }
        } else {
            hideTagSuggestions();
        }
    });
    
    function showTagSuggestions(suggestions, currentTag) {
        tagSuggestions.innerHTML = '';
        suggestions.slice(0, 5).forEach(suggestion => {
            const div = document.createElement('div');
            div.className = 'tag-suggestion';
            div.textContent = suggestion;
            div.addEventListener('click', function() {
                const currentTags = tagsInput.value.split(',');
                currentTags[currentTags.length - 1] = ' ' + suggestion;
                tagsInput.value = currentTags.join(',') + ', ';
                hideTagSuggestions();
                tagsInput.focus();
            });
            tagSuggestions.appendChild(div);
        });
        tagSuggestions.style.display = 'block';
    }
    
    function hideTagSuggestions() {
        tagSuggestions.style.display = 'none';
    }
    
    // Hide suggestions when clicking outside
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.tag-input-container')) {
            hideTagSuggestions();
        }
    });
    
    // Form validation and submission
    const form = document.getElementById('editDocumentForm');
    const updateBtn = document.getElementById('updateBtn');
    
    form.addEventListener('submit', function(e) {
        // Show loading state
        updateBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Updating...';
        updateBtn.disabled = true;
        
        // Basic validation
        const title = titleInput.value.trim();
        if (!title) {
            e.preventDefault();
            showAlert('Please enter a document title', 'danger');
            updateBtn.innerHTML = '<i class="fas fa-save me-2"></i>Update Document';
            updateBtn.disabled = false;
            return;
        }
        
        if (title.length > 255) {
            e.preventDefault();
            showAlert('Title must be less than 255 characters', 'danger');
            updateBtn.innerHTML = '<i class="fas fa-save me-2"></i>Update Document';
            updateBtn.disabled = false;
            return;
        }
        
        if (descriptionInput.value.length > 1000) {
            e.preventDefault();
            showAlert('Description must be less than 1000 characters', 'danger');
            updateBtn.innerHTML = '<i class="fas fa-save me-2"></i>Update Document';
            updateBtn.disabled = false;
            return;
        }
    });
    
    // Auto-save draft functionality (optional)
    let autoSaveTimeout;
    const formInputs = form.querySelectorAll('input, textarea, select');
    
    formInputs.forEach(input => {
        input.addEventListener('input', function() {
            clearTimeout(autoSaveTimeout);
            autoSaveTimeout = setTimeout(autoSaveDraft, 30000); // Auto-save after 30 seconds of inactivity
        });
    });
    
    function autoSaveDraft() {
        const formData = new FormData(form);
        formData.append('auto_save', '1');
        
        fetch('api/auto_save_document.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast('Draft saved automatically', 'info');
            }
        })
        .catch(error => {
            console.log('Auto-save failed:', error);
        });
    }
    
    // Keyboard shortcuts
    document.addEventListener('keydown', function(e) {
        // Ctrl+S to save
        if (e.ctrlKey && e.key === 's') {
            e.preventDefault();
            form.submit();
        }
        
        // Escape to cancel
        if (e.key === 'Escape') {
            if (confirm('Are you sure you want to cancel editing? Any unsaved changes will be lost.')) {
                window.location.href = 'documents.php';
            }
        }
    });
    
    // Warn about unsaved changes
    let formChanged = false;
    formInputs.forEach(input => {
        input.addEventListener('input', function() {
            formChanged = true;
        });
    });
    
    window.addEventListener('beforeunload', function(e) {
        if (formChanged) {
            e.preventDefault();
            e.returnValue = 'You have unsaved changes. Are you sure you want to leave?';
            return e.returnValue;
        }
    });
    
    // Reset form changed flag on successful submission
    form.addEventListener('submit', function() {
        formChanged = false;
    });
});

// Helper functions
function showAlert(message, type) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    const container = document.querySelector('.container');
    container.insertBefore(alertDiv, container.firstChild);
    
    // Auto-dismiss after 5 seconds
    setTimeout(() => {
        if (alertDiv.parentNode) {
            alertDiv.remove();
        }
    }, 5000);
}

function showToast(message, type) {
    // Create toast container if it doesn't exist
    let toastContainer = document.querySelector('.toast-container');
    if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.className = 'toast-container position-fixed top-0 end-0 p-3';
        document.body.appendChild(toastContainer);
    }
    
    const toast = document.createElement('div');
    toast.className = `toast align-items-center text-white bg-${type} border-0`;
    toast.setAttribute('role', 'alert');
    toast.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">${message}</div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    `;
    
    toastContainer.appendChild(toast);
    
    const bsToast = new bootstrap.Toast(toast);
    bsToast.show();
    
    // Remove toast element after it's hidden
    toast.addEventListener('hidden.bs.toast', function() {
        toast.remove();
    });
}

// Format file size function (if not available globally)
function formatFileSize(bytes) {
    if (bytes === 0) return '0 B';
    const k = 1024;
    const sizes = ['B', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}
</script>

<?php require_once 'includes/footer.php'; ?>
