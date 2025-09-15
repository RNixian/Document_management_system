// PTNI4 - Main JavaScript File

document.addEventListener('DOMContentLoaded', function() {
    // Initialize upload functionality
    initializeUpload();
    
    // Initialize other components
    initializeTooltips();
    initializePasswordToggle();
    initializeSearch();
});

// Upload Functionality
function initializeUpload() {
    const uploadArea = document.getElementById('uploadArea');
    const fileInput = document.getElementById('fileInput');
    const uploadForm = document.getElementById('uploadForm');
    const uploadBtn = document.getElementById('uploadBtn');
    
    if (!uploadArea || !fileInput) return;
    
    let selectedFiles = [];
    
    // Drag and drop events
    uploadArea.addEventListener('dragover', function(e) {
        e.preventDefault();
        uploadArea.classList.add('dragover');
    });
    
    uploadArea.addEventListener('dragleave', function(e) {
        e.preventDefault();
        uploadArea.classList.remove('dragover');
    });
    
    uploadArea.addEventListener('drop', function(e) {
        e.preventDefault();
        uploadArea.classList.remove('dragover');
        
        const files = Array.from(e.dataTransfer.files);
        handleFileSelection(files);
    });
    
    // Click to upload
    uploadArea.addEventListener('click', function() {
        fileInput.click();
    });
    
    // File input change
    fileInput.addEventListener('change', function() {
        const files = Array.from(this.files);
        handleFileSelection(files);
    });
    
        // Handle file selection (continued)
    function handleFileSelection(files) {
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
        const filePreview = document.getElementById('filePreview');
        const fileList = document.getElementById('fileList');
        
        if (selectedFiles.length === 0) {
            filePreview.style.display = 'none';
            return;
        }
        
        filePreview.style.display = 'block';
        fileList.innerHTML = '';
        
        selectedFiles.forEach((file, index) => {
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
        if (uploadBtn) {
            uploadBtn.disabled = selectedFiles.length === 0;
        }
    }
    
    // Handle form submission
    if (uploadForm) {
        uploadForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            if (selectedFiles.length === 0) {
                showAlert('Please select at least one file to upload.', 'warning');
                return;
            }
            
            uploadFiles();
        });
    }
    
    // Upload files
    function uploadFiles() {
        const formData = new FormData();
        const category = document.getElementById('category').value;
        const visibility = document.getElementById('visibility').value;
        const tags = document.getElementById('tags').value;
        const description = document.getElementById('description').value;
        
        // Add files to form data
        selectedFiles.forEach(file => {
            formData.append('files[]', file);
        });
        
        // Add other form data
        formData.append('category', category);
        formData.append('visibility', visibility);
        formData.append('tags', tags);
        formData.append('description', description);
        
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
            if (xhr.status === 200) {
                try {
                    const response = JSON.parse(xhr.responseText);
                    if (response.success) {
                        showAlert(response.message, 'success');
                        resetForm();
                        
                        // Redirect to documents page after 2 seconds
                        setTimeout(() => {
                            window.location.href = 'documents.php';
                        }, 2000);
                    } else {
                        showAlert(response.message, 'danger');
                    }
                } catch (e) {
                    showAlert('Upload completed but response was invalid.', 'warning');
                }
            } else {
                showAlert('Upload failed. Please try again.', 'danger');
            }
            
            // Reset upload state
            progressContainer.style.display = 'none';
            uploadBtn.disabled = false;
            uploadBtn.innerHTML = '<i class="fas fa-upload me-2"></i>Upload Documents';
        });
        
        xhr.addEventListener('error', function() {
            showAlert('Upload failed. Please check your connection and try again.', 'danger');
            progressContainer.style.display = 'none';
            uploadBtn.disabled = false;
            uploadBtn.innerHTML = '<i class="fas fa-upload me-2"></i>Upload Documents';
        });
        
        xhr.open('POST', 'api/upload.php');
        xhr.send(formData);
    }
}

// Reset upload form
function resetForm() {
    const fileInput = document.getElementById('fileInput');
    const filePreview = document.getElementById('filePreview');
    const uploadBtn = document.getElementById('uploadBtn');
    const progressContainer = document.querySelector('.upload-progress');
    
    if (fileInput) fileInput.value = '';
    if (filePreview) filePreview.style.display = 'none';
    if (uploadBtn) uploadBtn.disabled = true;
    if (progressContainer) progressContainer.style.display = 'none';
    
    selectedFiles = [];
    
    // Reset form fields
    document.getElementById('category').value = '';
    document.getElementById('visibility').value = '0';
    document.getElementById('tags').value = '';
    document.getElementById('description').value = '';
}

// Get file icon based on extension
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

// Format file size
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

// Show alert
function showAlert(message, type = 'info') {
    const alertContainer = document.createElement('div');
    alertContainer.className = `alert alert-${type} alert-dismissible fade show`;
    alertContainer.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    // Insert at the top of the container
    const container = document.querySelector('.container-fluid');
    if (container) {
        container.insertBefore(alertContainer, container.firstChild);
        
        // Auto-dismiss after 5 seconds
        setTimeout(() => {
            if (alertContainer.parentNode) {
                alertContainer.remove();
            }
        }, 5000);
    }
}

// Initialize tooltips
function initializeTooltips() {
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function(tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
}

// Initialize password toggle
function initializePasswordToggle() {
    const togglePassword = document.getElementById('togglePassword');
    const passwordInput = document.getElementById('password');
    
    if (togglePassword && passwordInput) {
        togglePassword.addEventListener('click', function() {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            
            const icon = this.querySelector('i');
            icon.classList.toggle('fa-eye');
            icon.classList.toggle('fa-eye-slash');
        });
    }
}

// Initialize search functionality
function initializeSearch() {
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        let searchTimeout;
        
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                performSearch(this.value);
            }, 300);
        });
    }
}

// Perform search
function performSearch(query) {
    if (query.length < 2) {
        return;
    }
    
    fetch(`api/search.php?q=${encodeURIComponent(query)}`)
        .then(response => response.json())
        .then(data => {
            displaySearchResults(data);
        })
        .catch(error => {
            console.error('Search error:', error);
        });
}

// Display search results
function displaySearchResults(results) {
    const resultsContainer = document.getElementById('searchResults');
    if (!resultsContainer) return;
    
    if (results.length === 0) {
        resultsContainer.innerHTML = '<p class="text-muted">No documents found.</p>';
        return;
    }
    
    let html = '';
    results.forEach(doc => {
        const icon = getFileIcon(doc.file_type);
        html += `
            <div class="search-result-item p-3 border-bottom">
                <div class="d-flex align-items-start">
                    <i class="${icon} me-3" style="font-size: 1.5rem;"></i>
                    <div class="flex-grow-1">
                        <h6 class="mb-1">${doc.title}</h6>
                        <p class="text-muted mb-1">${doc.description || 'No description'}</p>
                        <small class="text-muted">
                            ${formatFileSize(doc.file_size)} • ${new Date(doc.created_at).toLocaleDateString()}
                        </small>
                    </div>
                    <div class="ms-3">
                        <a href="api/download.php?id=${doc.id}" class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-download"></i>
                        </a>
                    </div>
                </div>
            </div>
        `;
    });
    
    resultsContainer.innerHTML = html;
}

// Confirm delete
function confirmDelete(id, filename) {
    if (confirm(`Are you sure you want to delete "${filename}"? This action cannot be undone.`)) {
        deleteDocument(id);
    }
}

// Delete document
function deleteDocument(id) {
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
            // Remove the document element from the page
            const docElement = document.querySelector(`[data-doc-id="${id}"]`);
            if (docElement) {
                docElement.remove();
            }
            // Reload page after 1 second
            setTimeout(() => {
                location.reload();
            }, 1000);
        } else {
            showAlert(data.message, 'danger');
        }
    })
    .catch(error => {
        showAlert('Error deleting document. Please try again.', 'danger');
    });
}

// Copy link to clipboard
function copyLink(url) {
    navigator.clipboard.writeText(url).then(() => {
        showAlert('Link copied to clipboard!', 'success');
    }).catch(() => {
        showAlert('Failed to copy link.', 'danger');
    });
}
