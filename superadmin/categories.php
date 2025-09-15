<?php
$page_title = 'Manage Categories';
require_once '../includes/functions.php';
requireLogin();
requireSuperAdmin();

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
    
    elseif ($action === 'delete') {
        $id = (int)$_POST['id'];
        
        if ($id > 0) {
            try {
                // Check if category has documents
                $stmt = $db->prepare("SELECT COUNT(*) as count FROM documents WHERE category_id = ?");
                $stmt->execute([$id]);
                $doc_count = $stmt->fetch()['count'];
                
                if ($doc_count > 0) {
                    setAlert("Cannot delete category. It contains $doc_count document(s).", 'danger');
                } else {
                    $stmt = $db->prepare("DELETE FROM categories WHERE id = ?");
                    $stmt->execute([$id]);
                    setAlert('Category deleted successfully!', 'success');
                }
            } catch (PDOException $e) {
                setAlert('Error deleting category', 'danger');
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

require_once '../includes/superadmin_header.php';
?>

<div class="container-fluid px-4">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="admin-header">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h1 class="h3 mb-0">
                            <i class="fas fa-folder-open me-2"></i>Manage Categories
                        </h1>
                      <p class="text-white mb-0">Organize documents with categories</p>

                    </div>
                    <div>
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                            <i class="fas fa-plus me-2"></i>Add Category
                        </button>
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
            <div class="card">
                <div class="card-header">
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
                                <thead class="basta">
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
                                                        <a href="../documents.php?category=<?php echo $category['id']; ?>" 
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
                                                    <button type="button" class="btn btn-outline-primary" 
                                                            onclick="editCategory(<?php echo htmlspecialchars(json_encode($category)); ?>)"
                                                            title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-outline-danger" 
                                                            onclick="deleteCategory(<?php echo $category['id']; ?>, '<?php echo addslashes($category['name']); ?>', <?php echo $category['document_count']; ?>)"
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
    </div>
</div>

<!-- Add Category Modal -->
<div class="modal fade" id="addCategoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-plus me-2"></i>Add New Category
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
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
<div class="modal fade" id="editCategoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-edit me-2"></i>Edit Category
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
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

<!-- Delete Category Modal -->
<div class="modal fade" id="deleteCategoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="">
                <div class="modal-header">
                    <h5 class="modal-title text-danger">
                        <i class="fas fa-trash me-2"></i>Delete Category
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" id="delete_id">
                    
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Warning:</strong> This action cannot be undone!
                    </div>
                    
                    <p>Are you sure you want to delete the category "<strong id="delete_category_name"></strong>"?</p>
                    
                    <div id="delete_warning" class="alert alert-warning d-none">
                        <i class="fas fa-info-circle me-2"></i>
                        This category contains <strong id="delete_doc_count"></strong> document(s). 
                        You must move or delete these documents before deleting the category.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger" id="delete_confirm_btn">
                        <i class="fas fa-trash me-2"></i>Delete Category
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Color preview functionality
document.getElementById('add_color').addEventListener('input', function() {
    document.getElementById('add_color_preview').style.backgroundColor = this.value;
});

document.getElementById('edit_color').addEventListener('input', function() {
    document.getElementById('edit_color_preview').style.backgroundColor = this.value;
});

// Edit category function
function editCategory(category) {
    document.getElementById('edit_id').value = category.id;
    document.getElementById('edit_name').value = category.name;
    document.getElementById('edit_description').value = category.description || '';
    document.getElementById('edit_color').value = category.color;
    document.getElementById('edit_color_preview').style.backgroundColor = category.color;
    document.getElementById('edit_color_preview').textContent = category.name;
    
    const modal = new bootstrap.Modal(document.getElementById('editCategoryModal'));
    modal.show();
}

// Delete category function
function deleteCategory(id, name, docCount) {
    document.getElementById('delete_id').value = id;
    document.getElementById('delete_category_name').textContent = name;
    document.getElementById('delete_doc_count').textContent = docCount;
    
    const warningDiv = document.getElementById('delete_warning');
    const deleteBtn = document.getElementById('delete_confirm_btn');
    
    if (docCount > 0) {
        warningDiv.classList.remove('d-none');
        deleteBtn.disabled = true;
        deleteBtn.innerHTML = '<i class="fas fa-ban me-2"></i>Cannot Delete';
    } else {
        warningDiv.classList.add('d-none');
        deleteBtn.disabled = false;
        deleteBtn.innerHTML = '<i class="fas fa-trash me-2"></i>Delete Category';
    }
    
    const modal = new bootstrap.Modal(document.getElementById('deleteCategoryModal'));
    modal.show();
}

// Form validation
document.querySelectorAll('form').forEach(function(form) {
    form.addEventListener('submit', function(e) {
        const nameInput = form.querySelector('input[name="name"]');
        if (nameInput && nameInput.value.trim() === '') {
            e.preventDefault();
            showAlert('Category name is required', 'danger');
            nameInput.focus();
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
</script>

<style>
.category-color-indicator {
    width: 20px;
    height: 20px;
    border-radius: 50%;
    border: 2px solid #fff;
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

.table  thead th {
    border-top: none;
    font-weight: 600;
    font-size: 0.875rem;
   background: linear-gradient(90deg, #0b257cff 0%, #010b33ff 100%);
    color: white;
}
.stat-card {
    background: white;
    border-radius: 10px;
    padding: 1.5rem;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    border: 1px solid #e9ecef;
    display: flex;
    align-items: center;
    margin-bottom: 1rem;
}

.stat-icon {
    width: 60px;
    height: 60px;
    border-radius: 10px;
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
}

.admin-header {
    background: linear-gradient(90deg, #0b257cff 0%, #010b33ff 100%);
    color: white;
    padding: 2rem;
    border-radius: 10px;
    margin-bottom: 2rem;
}

.empty-icon {
    opacity: 0.3;
}

.color-preview {
    min-width: 150px;
}

.form-control-color {
    width: 50px;
    height: 38px;
    border-radius: 6px;
}
</style>

<?php require_once '../includes/footer.php'; ?>
