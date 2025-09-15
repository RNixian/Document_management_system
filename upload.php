<?php
$page_title = 'Upload Document';
require_once 'includes/functions.php';
requireLogin();

// Get categories for dropdown
try {
    $stmt = $db->query("SELECT * FROM categories ORDER BY name");
    $categories = $stmt->fetchAll();
} catch (PDOException $e) {
    $categories = [];
}

require_once 'includes/header.php';
?>

<div class="container-fluid px-4" style="background-color: #f7f7faff; min-block-size: 100vh; padding-block-start: 20px;">

    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm" style="background-color: #040a3dff; color: white;">
                <div class="card-body py-4">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h1 class="display-6 fw-bold mb-2">
                                <i class="fas fa-cloud-upload-alt me-3"></i>Upload Documents
                            </h1>
                            <p class="lead mb-0">
                                Share your files securely. Drag and drop or click to select files.
                            </p>
                        </div>
                        <div class="col-md-4 text-end">
                            <a href="dashboard.php" class="btn btn-outline-light btn-lg">
                                <i class="fas fa-arrow-left me-2"></i>Back to Documents
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Upload Section -->
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-sm" style="background-color: #e6e3e3ff; color: white;">
                <div class="card-header border-0" style="background-color: #25273e; color: #f5f4f4ff;">
                    <h5 class="mb-0">
                        <i class="fas fa-plus me-2"></i>Add New Document
                    </h5>
                </div>
                <div class="card-body">
                <form id="uploadForm" enctype="multipart/form-data">
                    <!-- File Upload Area -->
                    <div class="upload-area" id="uploadArea" style="border: 2px dashed #ddd; padding: 40px; text-align: center; border-radius: 8px; cursor: pointer; transition: all 0.3s ease;">
                        <div class="text-center">
                            <i class="fas fa-cloud-upload-alt" style="font-size: 3rem; color: #141414ff; margin-block-end: 1rem;"></i>
                                <h5 style="color: #2c2f48;">Drag & Drop Files Here</h5>
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
                            <label for="category" class="form-label" style="color: #25273e;">Category</label>
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
                            <label for="visibility" class="form-label"style="color: #25273e;">Visibility</label>
                            <select class="form-select" id="visibility" name="visibility">
                                <option value="0">Private (Only You)</option>
                                <option value="1">Public (Everyone)</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mt-3">
                        <label for="tags" class="form-label" style="color: #25273e;">Tags (Optional)</label>
                        <input type="text" class="form-control" id="tags" name="tags"
                               placeholder="Enter tags separated by commas (e.g., report, 2024, important)">
                        <small class="text-muted">Tags help you organize and find your documents easily</small>
                    </div>
                    
                    <div class="mt-3">
                        <label for="description" class="form-label" style="color: #25273e;">Description (Optional)</label>
                        <textarea class="form-control" id="description" name="description" rows="3"
                                  placeholder="Add a description for your documents..."></textarea>
                    </div>
                    
                    <!-- Upload Progress -->
                    <div class="upload-progress mt-4" style="display: none;">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span>Uploading...</span>
                            <span id="progressText">0%</span>
                        </div>
                        <div class="progress">
                            <div class="progress-bar" id="progressBar" style="inline-size: 0%"></div>
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



            
            <!-- Upload Tips -->
            <div class="card mt-4 shadow-sm" style="background-color: #2c2f48; color: white;">
                <div class="card-header border-0" style="background-color: #25273e; color: #f1f1f1;">
                    <h5 class="mb-0">
                        <i class="fas fa-lightbulb me-2"></i>Upload Tips
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6><i class="fas fa-check-circle text-success me-2"></i>Best Practices</h6>
                            <ul class="list-unstyled">
                                <li><i class="fas fa-angle-right me-2 text-primary"></i>Use descriptive file names</li>
                                <li><i class="fas fa-angle-right me-2 text-primary"></i>Add relevant tags</li>
                                <li><i class="fas fa-angle-right me-2 text-primary"></i>Choose appropriate categories</li>
                                <li><i class="fas fa-angle-right me-2 text-primary"></i>Compress large files</li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <h6><i class="fas fa-shield-alt text-info me-2"></i>Security & Privacy</h6>
                            <ul class="list-unstyled">
                                <li><i class="fas fa-angle-right me-2 text-primary"></i>Files scanned for viruses</li>
                                <li><i class="fas fa-angle-right me-2 text-primary"></i>Private files visible only to you</li>
                                <li><i class="fas fa-angle-right me-2 text-primary"></i>All uploads are encrypted</li>
                                <li><i class="fas fa-angle-right me-2 text-primary"></i>You can delete anytime</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

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
        
    xhr.open('POST', 'api/upload.php');
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

</script>



<?php require_once 'includes/footer.php'; ?>
