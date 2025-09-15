<?php
$page_title = 'Manage Division';
require_once '../includes/functions.php';
requireLogin();
requireAdmin();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add') {
        $name = sanitize($_POST['name']);
        $description = sanitize($_POST['description']) ?: null; // Handle empty description
        
        if (!empty($name)) {
            try {
                // Debug: Check if database connection exists
                if (!isset($db)) {
                    throw new Exception('Database connection not available');
                }
                
                $stmt = $db->prepare("INSERT INTO division (name, description) VALUES (?, ?)");
                $result = $stmt->execute([$name, $description]);
                
                if ($result) {
                    setAlert('Division added successfully!', 'success');
                } else {
                    setAlert('Failed to add division', 'danger');
                }
            } catch (PDOException $e) {
                // Log the actual error for debugging
                error_log("Division add error: " . $e->getMessage());
                
                if ($e->getCode() == 23000) {
                    setAlert('Division name already exists!', 'danger');
                } else {
                    setAlert('Error adding division: ' . $e->getMessage(), 'danger');
                }
            } catch (Exception $e) {
                error_log("General error: " . $e->getMessage());
                setAlert('System error: ' . $e->getMessage(), 'danger');
            }
        } else {
            setAlert('Division name is required', 'danger');
        }
    }
    
    elseif ($action === 'edit') {
        $id = (int)$_POST['id'];
        $name = sanitize($_POST['name']);
        $description = sanitize($_POST['description']) ?: null;
        
        if (!empty($name) && $id > 0) {
            try {
                if (!isset($db)) {
                    throw new Exception('Database connection not available');
                }
                
                $stmt = $db->prepare("UPDATE division SET name = ?, description = ? WHERE id = ?");
                $result = $stmt->execute([$name, $description, $id]);
                
                if ($result) {
                    setAlert('Division updated successfully!', 'success');
                } else {
                    setAlert('Failed to update division', 'danger');
                }
            } catch (PDOException $e) {
                error_log("Division update error: " . $e->getMessage());
                
                if ($e->getCode() == 23000) {
                    setAlert('Division name already exists!', 'danger');
                } else {
                    setAlert('Error updating division: ' . $e->getMessage(), 'danger');
                }
            } catch (Exception $e) {
                error_log("General error: " . $e->getMessage());
                setAlert('System error: ' . $e->getMessage(), 'danger');
            }
        } else {
            setAlert('Division name and valid ID are required', 'danger');
        }
    }
    
    // Redirect to prevent form resubmission
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit();
}

// Get divisions with document counts
try {
    if (!isset($db)) {
        throw new Exception('Database connection not available');
    }
    
    // First, let's check if the division table exists
    $stmt = $db->query("SHOW TABLES LIKE 'division'");
    if ($stmt->rowCount() == 0) {
        throw new Exception('Division table does not exist');
    }
    
    // Check if documents table exists and has division_id column
    $stmt = $db->query("SHOW TABLES LIKE 'documents'");
    if ($stmt->rowCount() > 0) {
        // Check if division_id column exists in documents table
        $stmt = $db->query("SHOW COLUMNS FROM documents LIKE 'division_id'");
        if ($stmt->rowCount() > 0) {
            // Use division_id if it exists
            $stmt = $db->query("
                SELECT d.*, COUNT(doc.id) as document_count
                FROM division d
                LEFT JOIN documents doc ON d.id = doc.division_id
                GROUP BY d.id
                ORDER BY d.name
            ");
        } else {
            // Check if category_id exists instead
            $stmt = $db->query("SHOW COLUMNS FROM documents LIKE 'category_id'");
            if ($stmt->rowCount() > 0) {
                // Use category_id if division_id doesn't exist
                $stmt = $db->query("
                    SELECT d.*, COUNT(doc.id) as document_count
                    FROM division d
                    LEFT JOIN documents doc ON d.id = doc.category_id
                    GROUP BY d.id
                    ORDER BY d.name
                ");
            } else {
                // No linking column found, just get divisions without counts
                $stmt = $db->query("
                    SELECT d.*, 0 as document_count
                    FROM division d
                    ORDER BY d.name
                ");
            }
        }
    } else {
        // Documents table doesn't exist, just get divisions
        $stmt = $db->query("
            SELECT d.*, 0 as document_count
            FROM division d
            ORDER BY d.name
        ");
    }
    
    $divisions = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("Error fetching divisions: " . $e->getMessage());
    $divisions = [];
    setAlert('Error loading divisions: ' . $e->getMessage(), 'warning');
}

require_once '../includes/admin_header.php';
?>

<div class="container-fluid px-4">
    <!-- Display any alerts -->
    <?php if (isset($_SESSION['alert'])): ?>
        <div class="alert alert-<?php echo $_SESSION['alert']['type']; ?> alert-dismissible fade show" role="alert">
            <?php echo $_SESSION['alert']['message']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['alert']); ?>
    <?php endif; ?>

    <!-- Page Header with Red Border -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card page-header-card">
                <div class="page-header-content">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h1 class="page-title">
                                <i class="fas fa-folder-open me-3"></i>Manage Division
                            </h1>
                            <p class="page-subtitle">Organize Divisions</p>
                        </div>
                        <div>
                            <button type="button" class="btn btn-light btn-lg" data-bs-toggle="modal" data-bs-target="#addDivisionModal">
                                <i class="fas fa-plus me-2"></i>Add Division
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Divisions Overview -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon bg-primary">
                    <i class="fas fa-folder"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo count($divisions); ?></h3>
                    <p>Total Divisions</p>
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
                    $total_docs = array_sum(array_column($divisions, 'document_count'));
                    ?>
                    <h3><?php echo $total_docs; ?></h3>
                    <p>Division Documents</p>
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
                    try {
                        // Try to get uncategorized count, handle if documents table doesn't exist
                        $stmt = $db->query("SHOW TABLES LIKE 'documents'");
                        if ($stmt->rowCount() > 0) {
                            $stmt = $db->query("SHOW COLUMNS FROM documents LIKE 'division_id'");
                            if ($stmt->rowCount() > 0) {
                                $stmt = $db->query("SELECT COUNT(*) as count FROM documents WHERE division_id IS NULL");
                            } else {
                                $stmt = $db->query("SELECT COUNT(*) as count FROM documents WHERE category_id IS NULL");
                            }
                            $uncategorized = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
                        } else {
                            $uncategorized = 0;
                        }
                    } catch (Exception $e) {
                        $uncategorized = 0;
                    }
                    ?>
                    <h3><?php echo $uncategorized; ?></h3>
                    <p>Undivisioned</p>
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
                    $avg_docs = count($divisions) > 0 ? round($total_docs / count($divisions), 1) : 0;
                    ?>
                    <h3><?php echo $avg_docs; ?></h3>
                    <p>Avg per Division</p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Divisions Table -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-light">
                    <h5 class="mb-0">
                        <i class="fas fa-list me-2"></i>Division List
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (empty($divisions)): ?>
                        <div class="text-center py-5">
                            <div class="empty-icon mb-3">
                                <i class="fas fa-folder-open" style="font-size: 3rem; color: #dee2e6;"></i>
                            </div>
                            <h5 class="text-muted">No divisions found</h5>
                            <p class="text-muted">Create your first Division</p>
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addDivisionModal">
                                <i class="fas fa-plus me-2"></i>Add First Division
                            </button>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th>ID</th>
                                        <th>Division Name</th>
                                        <th>Description</th>
                                        <th>Documents</th>
                                        <th>Created</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($divisions as $division): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($division['id']); ?></td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div>
                                                        <h6 class="mb-0"><?php echo htmlspecialchars($division['name']); ?></h6>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <?php if (!empty($division['description'])): ?>
                                                    <span class="text-muted">
                                                        <?php echo htmlspecialchars(substr($division['description'], 0, 100)); ?>
                                                        <?php echo strlen($division['description']) > 100 ? '...' : ''; ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="text-muted fst-italic">No description</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <span class="badge bg-primary me-2">
                                                        <?php echo number_format($division['document_count']); ?>
                                                    </span>
                                                    <?php if ($division['document_count'] > 0): ?>
                                                        <a href="documents.php?division=<?php echo $division['id']; ?>"
                                                           class="btn btn-sm btn-outline-secondary" title="View documents">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td>
                                                <small class="text-muted">
                                                    <?php echo date('M j, Y', strtotime($division['created_at'])); ?>
                                                </small>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm" role="group">
                                                    <button type="button" class="btn btn-outline-primary edit-division-btn"
                                                            data-division="<?php echo htmlspecialchars(json_encode($division)); ?>"
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

<!-- Add Division Modal -->
<div class="modal fade" id="addDivisionModal" tabindex="-1" aria-labelledby="addDivisionModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="" id="addDivisionForm">
                <div class="modal-header">
                    <h5 class="modal-title" id="addDivisionModalLabel">
                        <i class="fas fa-
plus me-2"></i>Add New Division
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="add">
                    
                    <div class="mb-3">
                        <label for="addDivisionName" class="form-label">Division Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="addDivisionName" name="name" required>
                        <div class="form-text">Enter a unique name for this division</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="addDivisionDescription" class="form-label">Description</label>
                        <textarea class="form-control" id="addDivisionDescription" name="description" rows="3" 
                                  placeholder="Optional description for this division"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Add Division
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Division Modal -->
<div class="modal fade" id="editDivisionModal" tabindex="-1" aria-labelledby="editDivisionModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="" id="editDivisionForm">
                <div class="modal-header">
                    <h5 class="modal-title" id="editDivisionModalLabel">
                        <i class="fas fa-edit me-2"></i>Edit Division
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id" id="editDivisionId">
                    
                    <div class="mb-3">
                        <label for="editDivisionName" class="form-label">Division Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="editDivisionName" name="name" required>
                        <div class="form-text">Enter a unique name for this division</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="editDivisionDescription" class="form-label">Description</label>
                        <textarea class="form-control" id="editDivisionDescription" name="description" rows="3" 
                                  placeholder="Optional description for this division"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Update Division
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Edit division functionality
    document.querySelectorAll('.edit-division-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            try {
                const divisionData = JSON.parse(this.getAttribute('data-division'));
                
                // Populate edit modal
                document.getElementById('editDivisionId').value = divisionData.id;
                document.getElementById('editDivisionName').value = divisionData.name;
                document.getElementById('editDivisionDescription').value = divisionData.description || '';
                
                // Show modal
                const editModal = new bootstrap.Modal(document.getElementById('editDivisionModal'));
                editModal.show();
            } catch (error) {
                console.error('Error parsing division data:', error);
                alert('Error loading division data');
            }
        });
    });
    
    // Form validation
    document.querySelectorAll('form').forEach(function(form) {
        form.addEventListener('submit', function(e) {
            const nameInput = form.querySelector('input[name="name"]');
            if (nameInput && nameInput.value.trim() === '') {
                e.preventDefault();
                alert('Division name is required');
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
    const addModal = document.getElementById('addDivisionModal');
    if (addModal) {
        addModal.addEventListener('hidden.bs.modal', function() {
            // Reset form
            this.querySelector('form').reset();
        });
    }
    
    const editModal = document.getElementById('editDivisionModal');
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
     background: linear-gradient(45deg, #060558ff, #0056b3);
    border-radius: 15px;
    box-shadow: 0 10px 30px rgba(3, 29, 102, 0.3);
   
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

/* Stat Cards */
.stat-card {
    background: white;
    border-radius: 15px;
    padding: 1.5rem;
    box-shadow: 0 5px 15px rgba(0,0,0,0.08);
    border: 1px solid #e9ecef;
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
    background: #f8f9fa !important;
    border-bottom: 1px solid #dee2e6;
    padding: 1.25rem 1.5rem;
}

.card-body {
    padding: 1.5rem;
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
    background-color: rgba(0,123,255,0.05);
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

/* Reduced motion
<?php
// Temporary debug - remove after fixing
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Check if functions.php exists and is readable
if (!file_exists('../includes/functions.php')) {
    die('functions.php not found');
}

// Check if sanitize function exists
require_once '../includes/functions.php';
if (!function_exists('sanitize')) {
    die('sanitize function not found');
}

// Check database connection
if (!isset($db)) {
    die('Database connection not available');
}

// Test database connection
try {
    $db->query('SELECT 1');
    echo "Database connection OK<br>";
} catch (Exception $e) {
    die('Database error: ' . $e->getMessage());
}
?>

