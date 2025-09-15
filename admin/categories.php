<?php
$page_title = 'Manage Categories';
require_once '../includes/functions.php';
requireLogin();
requireAdmin();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add') {
        $name = sanitize($_POST['name']);
        $description = sanitize($_POST['description']);
        $color = sanitize($_POST['color']);
        
        if (!empty($name)) {
            try {
                $stmt = $db->prepare("INSERT INTO categories (name, description, color) VALUES (?, ?, ?)");
                $stmt->execute([$name, $description, $color]);
                setAlert('Category added successfully!', 'success');
            } catch (PDOException $e) {
                if ($e->getCode() == 23000) {
                    setAlert('Category name already exists!', 'danger');
                } else {
                    setAlert('Error adding category', 'danger');
                }
            }
        } else {
            setAlert('Category name is required', 'danger');
        }
    }
    
    elseif ($action === 'edit') {
        $id = (int)$_POST['id'];
        $name = sanitize($_POST['name']);
        $description = sanitize($_POST['description']);
        $color = sanitize($_POST['color']);
        
        if (!empty($name) && $id > 0) {
            try {
                $stmt = $db->prepare("UPDATE categories SET name = ?, description = ?, color = ? WHERE id = ?");
                $stmt->execute([$name, $description, $color, $id]);
                setAlert('Category updated successfully!', 'success');
            } catch (PDOException $e) {
                if ($e->getCode() == 23000) {
                    setAlert('Category name already exists!', 'danger');
                } else {
                    setAlert('Error updating category', 'danger');
                }
            }
        }
    }
    

    
    // Redirect to prevent form resubmission
    header('Location: categories.php');
    exit();
}

// Get categories with document counts
try {
    $stmt = $db->query("
        SELECT c.*, COUNT(d.id) as document_count
        FROM categories c
        LEFT JOIN documents d ON c.id = d.category_id
        GROUP BY c.id
        ORDER BY c.name
    ");
    $categories = $stmt->fetchAll();
} catch (PDOException $e) {
    $categories = [];
}

require_once '../includes/admin_header.php';
?>

<div class="container-fluid px-4">
    <!-- Page Header with Red Border -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card page-header-card">
                <div class="page-header-content">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h1 class="page-title">
                                <i class="fas fa-folder-open me-3"></i>Manage Categories
                            </h1>
                            <p class="page-subtitle">Organize documents with categories</p>
                        </div>
                        <div>
                            <button type="button" class="btn btn-light btn-lg" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                                <i class="fas fa-plus me-2"></i>Add Category
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Categories Overview -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon bg-primary">
                    <i class="fas fa-folder"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo count($categories); ?></h3>
                    <p>Total Categories</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon bg-success">
                    <i class="fas fa-file-alt"></i>
                </div>
                <div class="stat-content">
                    <?php
                    $total_docs = array_sum(array_column($categories, 'document_count'));
                    ?>
                    <h3><?php echo $total_docs; ?></h3>
                    <p>Categorized Documents</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon bg-warning">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <div class="stat-content">
                    <?php
                    $stmt = $db->query("SELECT COUNT(*) as count FROM documents WHERE category_id IS NULL");
                    $uncategorized = $stmt->fetch()['count'];
                    ?>
                    <h3><?php echo $uncategorized; ?></h3>
                    <p>Uncategorized</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon bg-info">
                    <i class="fas fa-chart-pie"></i>
                </div>
                <div class="stat-content">
                    <?php
                    $avg_docs = count($categories) > 0 ? round($total_docs / count($categories), 1) : 0;
                    ?>
                    <h3><?php echo $avg_docs; ?></h3>
                    <p>Avg per Category</p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Categories Table -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-light">
                    <h5 class="mb-0">
                        <i class="fas fa-list me-2"></i>Categories List
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (empty($categories)): ?>
                        <div class="text-center py-5">
                            <div class="empty-icon mb-3">
                                <i class="fas fa-folder-open" style="font-size: 3rem; color: #dee2e6;"></i>
                            </div>
                            <h5 class="text-muted">No categories found</h5>
                            <p class="text-muted">Create your first category to organize documents</p>
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                                <i class="fas fa-plus me-2"></i>Add First Category
                            </button>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Category</th>
                                        <th>Description</th>
                                        <th>Documents</th>
                                        <th>Created</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($categories as $category): ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="category-color-indicator me-3"
                                                         style="background-color: <?php echo $category['color']; ?>"></div>
                                                    <div>
                                                        <h6 class="mb-0"><?php echo htmlspecialchars($category['name']); ?></h6>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <?php if ($category['description']): ?>
                                                    <span class="text-muted">
                                                        <?php echo htmlspecialchars(substr($category['description'], 0, 100)); ?>
                                                        <?php echo strlen($category['description']) > 100 ? '...' : ''; ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="text-muted fst-italic">No description</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <span class="badge bg-primary me-2">
                                                        <?php echo number_format($category['document_count']); ?>
                                                    </span>
                                                    <?php if ($category['document_count'] > 0): ?>
                                                        <a href="documents.php?category=<?php echo $category['id']; ?>"
                                                           class="btn btn-sm btn-outline-secondary" title="View documents">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td>
                                                <small class="text-muted">
                                                    <?php echo date('M j, Y', strtotime($category['created_at'])); ?>
                                                </small>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm" role="group">
                                                    <button type="button" class="btn btn-outline-primary edit-category-btn"
                                                            data-category="<?php echo htmlspecialchars(json_encode($category)); ?>"
                                                            title="Edit">
                                                        <i class="fas fa-edit"></i>
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
    </div>
</div>

<!-- Add Category Modal -->
<div class="modal fade" id="addCategoryModal" tabindex="-1" aria-labelledby="addCategoryModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="">
                <div class="modal-header">
                    <h5 class="modal-title" id="addCategoryModalLabel">
                        <i class="fas fa-plus me-2"></i>Add New Category
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="add">
                    
                    <div class="mb-3">
                        <label for="add_name" class="form-label">Category Name *</label>
                        <input type="text" class="form-control" id="add_name" name="name" required maxlength="100">
                    </div>
                    
                    <div class="mb-3">
                        <label for="add_description" class="form-label">Description</label>
                        <textarea class="form-control" id="add_description" name="description" rows="3" maxlength="500"></textarea>
                        <div class="form-text">Optional description for this category</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="add_color" class="form-label">Color</label>
                        <div class="d-flex align-items-center">
                            <input type="color" class="form-control form-control-color me-3" id="add_color" name="color" value="#007bff">
                            <div class="color-preview">
                                <span class="category-pill" id="add_color_preview" style="background-color: #007bff;">
                                    Sample Category
                                </span>
                            </div>
                        </div>
                        <div class="form-text">Choose a color to identify this category</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>Add Category
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Category Modal -->
<div class="modal fade" id="editCategoryModal" tabindex="-1" aria-labelledby="editCategoryModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="">
                <div class="modal-header">
                    <h5 class="modal-title" id="editCategoryModalLabel">
                        <i class="fas fa-edit me-2"></i>Edit Category
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id" id="edit_id">
                    
                    <div class="mb-3">
                        <label for="edit_name" class="form-label">Category Name *</label>
                        <input type="text" class="form-control" id="edit_name" name="name" required maxlength="100">
                    </div>
                    
                  
                    <div class="mb-3">
                        <label for="edit_description" class="form-label">Description</label>
                        <textarea class="form-control" id="edit_description" name="description" rows="3" maxlength="500"></textarea>
                        <div class="form-text">Optional description for this category</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_color" class="form-label">Color</label>
                        <div class="d-flex align-items-center">
                            <input type="color" class="form-control form-control-color me-3" id="edit_color" name="color">
                            <div class="color-preview">
                                <span class="category-pill" id="edit_color_preview">
                                    Sample Category
                                </span>
                            </div>
                        </div>
                        <div class="form-text">Choose a color to identify this category</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Update Category
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>



<script>
// Wait for DOM to be fully loaded
document.addEventListener('DOMContentLoaded', function() {
    console.log('Categories page loaded');
    
    // Color preview functionality
    const addColorInput = document.getElementById('add_color');
    const addColorPreview = document.getElementById('add_color_preview');
    const editColorInput = document.getElementById('edit_color');
    const editColorPreview = document.getElementById('edit_color_preview');
    
    if (addColorInput && addColorPreview) {
        addColorInput.addEventListener('input', function() {
            addColorPreview.style.backgroundColor = this.value;
        });
    }
    
    if (editColorInput && editColorPreview) {
        editColorInput.addEventListener('input', function() {
            editColorPreview.style.backgroundColor = this.value;
        });
    }
    
    // Edit category functionality
    document.querySelectorAll('.edit-category-btn').forEach(function(button) {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            console.log('Edit button clicked');
            
            try {
                const categoryData = JSON.parse(this.getAttribute('data-category'));
                console.log('Category data:', categoryData);
                
                // Populate edit form
                document.getElementById('edit_id').value = categoryData.id;
                document.getElementById('edit_name').value = categoryData.name;
                document.getElementById('edit_description').value = categoryData.description || '';
                document.getElementById('edit_color').value = categoryData.color;
                document.getElementById('edit_color_preview').style.backgroundColor = categoryData.color;
                document.getElementById('edit_color_preview').textContent = categoryData.name;
                
                // Show modal
                const editModal = new bootstrap.Modal(document.getElementById('editCategoryModal'));
                editModal.show();
                
            } catch (error) {
                console.error('Error parsing category data:', error);
                alert('Error loading category data');
            }
        });
    });
    
    
    
    // Form validation
    document.querySelectorAll('form').forEach(function(form) {
        form.addEventListener('submit', function(e) {
            const nameInput = form.querySelector('input[name="name"]');
            if (nameInput && nameInput.value.trim() === '') {
                e.preventDefault();
                alert('Category name is required');
                nameInput.focus();
                return false;
            }
        });
    });
    
    // Auto-resize textareas
    document.querySelectorAll('textarea').forEach(function(textarea) {
        textarea.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = this.scrollHeight + 'px';
        });
    });

    
    // Add modal event listeners
    const addModal = document.getElementById('addCategoryModal');
    if (addModal) {
        addModal.addEventListener('hidden.bs.modal', function() {
            // Reset form
            this.querySelector('form').reset();
            if (addColorPreview) {
                addColorPreview.style.backgroundColor = '#007bff';
            }
        });
    }
    
    const editModal = document.getElementById('editCategoryModal');
    if (editModal) {
        editModal.addEventListener('hidden.bs.modal', function() {
            // Reset form
            this.querySelector('form').reset();
        });
    }
});

// Global error handler
window.addEventListener('error', function(e) {
    console.error('JavaScript error:', e.error);
});
</script>

<style>
/* Page Header with Red Border - Consistent with other admin pages */
.page-header-card {
    background: linear-gradient(90deg,rgb(43, 46, 63) 0%,rgb(6, 30, 187) 100%) !important;
    border-radius: 15px;
    box-shadow: 0 10px 30px rgba(29, 35, 131, 0.3);
    border: 3px solid #040c50ff;
    overflow: hidden;
    position: relative;
}

.page-header-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    
}

.page-header-content {
    padding: 2.5rem;
    position: relative;
    z-index: 2;
}

.page-title {
    font-size: 2.5rem;
    font-weight: 700;
    color: white;
    margin: 0;
    text-shadow: 0 2px 4px rgba(0,0,0,0.3);
}

.page-subtitle {
    font-size: 1.1rem;
    color: rgba(255,255,255,0.9);
    margin: 0.5rem 0 0 0;
    font-weight: 300;
}

/* Category specific styles */
.category-color-indicator {
    width: 20px;
    height: 20px;
    border-radius: 50%;
    border: 1px solid #100e85ff;
    box-shadow: 0 0 0 1px rgba(0,0,0,0.1);
}

.category-pill {
    display: inline-block;
    padding: 0.25rem 0.75rem;
    border-radius: 50px;
    color: white;
    font-size: 0.875rem;
    font-weight: 500;
    text-shadow: 0 1px 2px rgba(0,0,0,0.2);
}

/* Stat Cards */
.stat-card {
    background: white;
    border-radius: 15px;
    padding: 1.5rem;
    box-shadow: 0 5px 15px rgba(0,0,0,0.08);
    border: 1px solid #0d0e6dff;
    display: flex;
    align-items: center;
    margin-bottom: 1rem;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
}

.stat-icon {
    width: 60px;
    height: 60px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 1rem;
    font-size: 1.5rem;
    color: white;
}

.stat-content h3 {
    margin: 0;
    font-size: 2rem;
    font-weight: bold;
    color: #2c3e50;
}

.stat-content p {
    margin: 0;
    color: #6c757d;
    font-size: 0.9rem;
    font-weight: 500;
}

/* Card improvements */
.card {
    border: none;
    border-radius: 15px;
    overflow: hidden;
}

.card-header {
    background: linear-gradient(90deg,rgb(43, 46, 63) 0%,rgb(6, 30, 187) 100%) !important;
    color: white;
} 

.card-body {
    padding: 1.5rem;
    background: linear-gradient(90deg,rgb(43, 46, 63) 0%,rgb(6, 30, 187) 100%) !important;
}

/* Table improvements */
.table {
    margin-bottom: 0;
}

.table thead th {
    border-top: none;
    font-weight: 600;
    font-size: 0.875rem;
    background: linear-gradient(90deg, #0b257cff 0%, #010b33ff 100%);
    color: white;
}
.table tbody tr {
    transition: background-color 0.2s ease;
}

.table tbody tr:hover {
    background-color: rgba(5, 27, 99, 0.05);
}

/* Button improvements */
.btn {
    border-radius: 8px;
    font-weight: 500;
    transition: all 0.2s ease;
}

.btn-lg {
    padding: 0.75rem 1.5rem;
    font-size: 1.1rem;
}

.btn-group-sm .btn {
    padding: 0.375rem 0.75rem;
}

/* Modal improvements */
.modal-content {
    border: none;
    border-radius: 15px;
    box-shadow: 0 20px 60px rgba(0,0,0,0.2);
}

.modal-header {
    border-bottom: 1px solid #dee2e6;
    padding: 1.5rem;
}

.modal-body {
    padding: 1.5rem;
}

.modal-footer {
    border-top: 1px solid #dee2e6;
    padding: 1.5rem;
}

/* Form improvements */
.form-control {
    border-radius: 8px;
    border: 1px solid #ced4da;
    padding: 0.75rem 1rem;
    transition: border-color 0.2s ease, box-shadow 0.2s ease;
}

.form-control:focus {
    border-color: #80bdff;
    box-shadow: 0 0 0 0.2rem rgba(0,123,255,0.25);
}

.form-control-color {
    width: 50px;
    height: 38px;
    border-radius: 8px;
    border: 1px solid #ced4da;
}

/* Color preview */
.color-preview {
    min-width: 150px;
}

/* Empty state */
.empty-icon {
    opacity: 0.3;
}

/* Badge improvements */
.badge {
    font-weight: 500;
    padding: 0.5em 0.75em;
}

/* Alert improvements */
.alert {
    border: none;
    border-radius: 10px;
    padding: 1rem 1.25rem;
}

/* Responsive improvements */
@media (max-width: 768px) {
    .page-header-content {
        padding: 2rem 1.5rem;
    }
    
    .page-title {
        font-size: 2rem;
    }
    
    .stat-card {
        margin-bottom: 1rem;
    }
    
    .btn-group-sm .btn {
        padding: 0.25rem 0.5rem;
        font-size: 0.875rem;
    }
}

@media (max-width: 576px) {
    .page-header-content .d-flex {
        flex-direction: column;
        align-items: flex-start !important;
    }
    
    .page-header-content .btn {
        margin-top: 1rem;
        width: 100%;
    }
    
    .stat-card {
        flex-direction: column;
        text-align: center;
    }
    
    .stat-icon {
        margin-right: 0;
        margin-bottom: 1rem;
    }
}

/* Loading states */
.btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

/* Hover effects */
.btn:hover:not(:disabled) {
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.15);
}

.table tbody tr:hover {
    transform: translateY(-1px);
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

/* Focus states */
.btn:focus,
.form-control:focus {
    outline: none;
}

/* Animation for modals */
.modal.fade .modal-dialog {
    transition: transform 0.3s ease-out;
    transform: translate(0, -50px);
}

.modal.show .modal-dialog {
    transform: none;
}

/* Custom scrollbar for modals */
.modal-body {
    max-height: 70vh;
    overflow-y: auto;
}

.modal-body::-webkit-scrollbar {
    width: 6px;
}

.modal-body::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 3px;
}

.modal-body::-webkit-scrollbar-thumb {
    background: #c1c1c1;
    border-radius: 3px;
}

.modal-body::-webkit-scrollbar-thumb:hover {
    background: #a8a8a8;
}

/* Success/Error states */
.is-valid {
    border-color: #28a745;
}

.is-invalid {
    border-color: #dc3545;
}

/* Category color picker enhancements */
.form-control-color::-webkit-color-swatch-wrapper {
    padding: 0;
    border-radius: 6px;
    border: none;
}

.form-control-color::-webkit-color-swatch {
    border: none;
    border-radius: 6px;
}

/* Tooltip styles */
[data-bs-toggle="tooltip"] {
    cursor: help;
}

/* Print styles */
@media print {
    .btn,
    .modal,
    .page-header-card::before {
        display: none !important;
    }
    
    .page-header-card {
        background: white !important;
        color: black !important;
        border: 1px solid #000 !important;
    }
    
    .page-title,
    .page-subtitle {
        color: black !important;
    }
}

/* Dark mode support (if needed) */
@media (prefers-color-scheme: dark) {
    .card {
        background-color: #2d3748;
        color: #e2e8f0;
    }
    
    .table {
        color: #e2e8f0;
    }
    
    .form-control {
        background-color: #4a5568;
        border-color: #718096;
        color: #e2e8f0;
    }
    
    .form-control:focus {
        background-color: #4a5568;
        border-color: #63b3ed;
        color: #e2e8f0;
    }
}

/* Accessibility improvements */
.sr-only {
    position: absolute;
    width: 1px;
    height: 1px;
    padding: 0;
    margin: -1px;
    overflow: hidden;
    clip: rect(0, 0, 0, 0);
    white-space: nowrap;
    border: 0;
}

/* Focus visible for better keyboard navigation */
.btn:focus-visible,
.form-control:focus-visible {
    outline: 2px solid #0d6efd;
    outline-offset: 2px;
}

/* High contrast mode support */
@media (prefers-contrast: high) {
    .btn {
        border: 2px solid;
    }
    
    .card {
        border: 2px solid #000;
    }
    
    .table {
        border: 2px solid #000;
    }
}

/* Reduced motion support */
@media (prefers-reduced-motion: reduce) {
    *,
    *::before,
    *::after {
        animation-duration: 0.01ms !important;
        animation-iteration-count: 1 !important;
        transition-duration: 0.01ms !important;
        scroll-behavior: auto !important;
    }
}

/* Loading spinner */
.spinner-border-sm {
    width: 1rem;
    height: 1rem;
}

/* Custom checkbox styles */
.form-check-input:checked {
    background-color: #dc3545;
    border-color: #dc3545;
}

/* File input improvements */
.form-control[type="file"] {
    padding: 0.375rem 0.75rem;
}

/* Select improvements */
.form-select {
    border-radius: 8px;
    border: 1px solid #ced4da;
    padding: 0.75rem 1rem;
}

.form-select:focus {
    border-color: #80bdff;
    box-shadow: 0 0 0 0.2rem rgba(0,123,255,0.25);
}

/* Progress bar styles */
.progress {
    border-radius: 10px;
    overflow: hidden;
}

.progress-bar {
    transition: width 0.3s ease;
}

/* Toast notifications */
.toast {
    border-radius: 10px;
    border: none;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

/* Breadcrumb improvements */
.breadcrumb {
    background: transparent;
    padding: 0;
    margin-bottom: 1rem;
}

.breadcrumb-item + .breadcrumb-item::before {
    content: "›";
    color: #6c757d;
}

/* List group improvements */
.list-group-item {
    border-radius: 8px !important;
    margin-bottom: 0.25rem;
    border: 1px solid #dee2e6;
}

.list-group-item:hover {
    background-color: #f8f9fa;
}

/* Pagination improvements */
.page-link {
    border-radius: 8px;
    margin: 0 2px;
    border: 1px solid #dee2e6;
}

.page-item.active .page-link {
    background-color: #dc3545;
    border-color: #dc3545;
}

/* Dropdown improvements */
.dropdown-menu {
    border-radius: 10px;
    border: none;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.dropdown-item:hover {
    background-color: #f8f9fa;
}

/* Tab improvements */
.nav-tabs .nav-link {
    border-radius: 8px 8px 0 0;
}

.nav-tabs .nav-link.active {
    background-color: #dc3545;
    border-color: #dc3545;
    color: white;
}

/* Accordion improvements */
.accordion-button {
    border-radius: 8px;
}

.accordion-button:not(.collapsed) {
    background-color: #dc3545;
    color: white;
}

/* Offcanvas improvements */
.offcanvas {
    border-radius: 15px 0 0 15px;
}

/* Carousel improvements */
.carousel-control-prev,
.carousel-control-next {
    width: 5%;
}

.carousel-indicators [data-bs-target] {
    border-radius: 50%;
    width: 12px;
    height: 12px;
}
</style>

<?php include '../includes/footer.php'; ?>
