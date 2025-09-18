<?php
require_once 'includes/functions.php';

// Get document ID safely
$doc_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($doc_id <= 0) {
    header('Location: dashboard.php');
    exit();
}

try {
    // Get document with related category + uploader info
    $stmt = $db->prepare("
        SELECT d.*, c.name as category_name, c.color as category_color,
               u.full_name, u.username
        FROM documents d
        LEFT JOIN categories c ON d.category_id = c.id
        LEFT JOIN users u ON d.user_id = u.id
        WHERE d.id = ?
    ");
    $stmt->execute([$doc_id]);
    $document = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$document) {
        header('Location: dashboard.php');
        exit();
    }
} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}

$page_title = htmlspecialchars($document['title'] ?? 'Document');
require_once 'includes/header.php';
?>

<style>
body {
    background-color: #f4f6fb;
    font-family: 'Segoe UI', sans-serif;
}

.page-header {
    background: linear-gradient(90deg,rgb(43, 46, 63) 0%,rgb(6, 30, 187) 100%) !important; /* Change these colors for your preferred blue or any other color */
    color: #fff;
    padding: 1.5rem;
    border-radius: 1rem;
    margin-bottom: 2rem;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.page-header h4 {
    margin: 0;
    font-weight: 700;
}

.page-header .btn {
    border-radius: 8px;
    font-weight: 600;
}

.card {
    border: none;
    border-radius: 1rem;
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
}

.card-title {
    font-weight: 600;
    color: #2c3e50;
}

.badge {
    border-radius: 50rem;
    padding: 0.5rem 0.75rem;
    font-size: 0.85rem;
}

.uploader-info img {
    border: 2px solid #ddd;
}

.modal-content {
    border-radius: 1rem;
    box-shadow: 0 4px 15px rgba(0,0,0,0.2);
}

.modal-header {
    border-bottom: none;
    font-weight: 600;
}

.modal-footer .btn {
    border-radius: 8px;
    font-weight: 600;
}

.alert {
    border-radius: 0.75rem;
    font-weight: 500;
}
</style>

<div class="container-fluid py-4">
    <!-- Header Section -->
    <div class="page-header d-flex justify-content-between align-items-center">
        <h4>
            <i class="bi bi-file-earmark-text me-2"></i> Download Document
           
        </h4>
        <div>
            <a href="dashboard.php" class="btn btn-light btn-sm me-2">
                <i class="bi bi-arrow-left"></i> Back
            </a>
            <a href="edit.php?id=<?= $document['id']; ?>" class="btn btn-warning btn-sm me-2">
                <i class="bi bi-pencil"></i> Edit
            </a>
            <button class="btn btn-primary btn-sm me-2" data-bs-toggle="modal" data-bs-target="#shareModal">
                <i class="bi bi-share"></i> Share
            </button>
            <button class="btn btn-danger btn-sm" onclick="confirmDelete(<?= $document['id']; ?>, '<?= htmlspecialchars($document['title']); ?>')">
                <i class="bi bi-trash"></i> Delete
            </button>
        </div>
    </div>

    <!-- Document Details -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="row">
                <!-- Left: Document Info -->
                <div class="col-md-8">
                    <h5 class="card-title mb-3">Document Details</h5>
                    <p><strong>Category:</strong>
                        <span class="badge" style="background-color: <?= htmlspecialchars($document['category_color']); ?>">
                            <?= htmlspecialchars($document['category_name']); ?>
                        </span>
                    </p>
                    <p><strong>Description:</strong></p>
                    <p><?= nl2br(htmlspecialchars($document['description'])); ?></p>
                    <p><strong>File:</strong>
                        <a href="uploads/<?= htmlspecialchars($document['file_name']); ?>" target="_blank" class="fw-semibold text-decoration-none">
                            <i class="bi bi-link-45deg me-1"></i><?= htmlspecialchars($document['file_name']); ?>
                        </a>
                    </p>
                </div>

                <!-- Right: Uploader Info -->
                <div class="col-md-4">
                    <h5 class="card-title mb-3">Uploaded By</h5>
          
                            <p class="mb-1 fw-semibold"><?= htmlspecialchars($document['full_name'] ?: $document['username']); ?></p>
                            <small class="text-muted">Uploaded on <?= date('F j, Y g:i A', strtotime($document['created_at'])); ?></small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Share Modal -->
    <div class="modal fade" id="shareModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form id="shareForm">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="bi bi-share me-2"></i>Share Document</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p>Select users to share <strong><?= htmlspecialchars($document['title']); ?></strong> with:</p>
                        <div class="mb-3">
                            <select class="form-select" name="shared_to[]" multiple required>
                                <?php
                                $userStmt = $db->prepare("SELECT id, full_name, username FROM users WHERE id != ?");
                                $userStmt->execute([(int)$document['user_id']]);
                                while ($user = $userStmt->fetch()) {
                                    $displayName = htmlspecialchars($user['full_name'] ?: $user['username']);
                                    echo "<option value='{$user['id']}'>{$displayName}</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <input type="hidden" name="document_id" value="<?= $document['id']; ?>">
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary"><i class="bi bi-send me-1"></i>Share</button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title"><i class="bi bi-exclamation-triangle me-2"></i>Confirm Delete</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    Are you sure you want to delete this document?
                    <strong id="deleteDocTitle"></strong>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirmDeleteBtn">Delete</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let deleteDocumentId = null;

// Confirm delete modal
function confirmDelete(id, title) {
    deleteDocumentId = id;
    document.getElementById('deleteDocTitle').textContent = title;
    const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
    modal.show();
}

// Delete logic
document.getElementById('confirmDeleteBtn').addEventListener('click', function() {
    if (!deleteDocumentId) return;

    fetch('api/delete.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: deleteDocumentId })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            showAlert(data.message, 'success');
            setTimeout(() => window.location.href = 'documents.php', 1500);
        } else {
            showAlert(data.message, 'danger');
        }
        bootstrap.Modal.getInstance(document.getElementById('deleteModal')).hide();
    })
    .catch(() => {
        showAlert('Error deleting document. Please try again.', 'danger');
        bootstrap.Modal.getInstance(document.getElementById('deleteModal')).hide();
    });
});

// Share logic
document.getElementById('shareForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);

    fetch('api/share.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            showAlert(data.message, 'success');
            bootstrap.Modal.getInstance(document.getElementById('shareModal')).hide();
        } else {
            showAlert(data.message, 'danger');
        }
    })
    .catch(() => {
        showAlert('Error sharing document. Please try again.', 'danger');
    });
});

// Reusable alert
function showAlert(message, type) {
    const alertBox = document.createElement('div');
    alertBox.className = `alert alert-${type} fixed-top m-3 shadow`;
    alertBox.innerHTML = message;
    document.body.appendChild(alertBox);
    setTimeout(() => alertBox.remove(), 3000);
}
</script>

<?php require_once 'includes/footer.php'; ?>
