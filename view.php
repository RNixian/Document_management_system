<?php
require_once 'includes/functions.php';

// Get document ID
$doc_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($doc_id <= 0) {
    header('Location: documents.php');
    exit();
}

try {
    // Get document with category info
    $stmt = $db->prepare("
        SELECT d.*, c.name as category_name, c.color as category_color,
               u.username, u.full_name
        FROM documents d 
        LEFT JOIN categories c ON d.category_id = c.id 
        LEFT JOIN users u ON d.user_id = u.id
        WHERE d.id = ?
    ");
    $stmt->execute([$doc_id]);
    $document = $stmt->fetch();
    
    if (!$document) {
        setAlert('Document not found', 'danger');
        header('Location: documents.php');
        exit();
    }
    
    // Check permissions
    $can_view = false;
    $is_owner = false;
    
    if ($document['is_public']) {
        $can_view = true;
    } elseif (isLoggedIn() && $document['user_id'] == $_SESSION['user_id']) {
        $can_view = true;
        $is_owner = true;
    }
    
    if (!$can_view) {
        setAlert('Access denied', 'danger');
        header('Location: documents.php');
        exit();
    }
    
    // Get recent download logs if owner
    $download_logs = [];

    if ($is_owner) {
        $stmt = $db->prepare("
            SELECT dl.*, u.username, u.full_name 
            FROM download_logs dl 
            LEFT JOIN users u ON dl.user_id = u.id 
            WHERE dl.document_id = ? 
            ORDER BY dl.downloaded_at DESC 
            LIMIT 10
        ");
        $stmt->execute([$doc_id]);
        $download_logs = $stmt->fetchAll();
    }
    
    // Get related documents (same category or tags)
    $related_docs = [];
    if ($document['category_id'] || $document['tags']) {
        $related_sql = "
            SELECT d.*, c.name as category_name, c.color as category_color 
            FROM documents d 
            LEFT JOIN categories c ON d.category_id = c.id 
            WHERE d.id != ? AND d.is_public = 1 AND (
        ";
        $related_params = [$doc_id];
        $conditions = [];
        
        if ($document['category_id']) {
            $conditions[] = "d.category_id = ?";
            $related_params[] = $document['category_id'];
        }
        
        if ($document['tags']) {
            $tags = explode(',', $document['tags']);
            foreach ($tags as $tag) {
                $tag = trim($tag);
                if (!empty($tag)) {
                    $conditions[] = "d.tags LIKE ?";
                    $related_params[] = "%$tag%";
                }
            }
        }
        
        $related_sql .= implode(' OR ', $conditions) . ") ORDER BY d.downloads DESC, d.created_at DESC LIMIT 6";
        
        $stmt = $db->prepare($related_sql);
        $stmt->execute($related_params);
        $related_docs = $stmt->fetchAll();
    }
    
} catch (PDOException $e) {
    setAlert('Error loading document', 'danger');
    header('Location: documents.php');
    exit();
}

$page_title = $document['title'];
require_once 'includes/header.php';
?>

<div class="container-fluid px-4">
    <!-- Document Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                <div class="card-body py-4">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <div class="d-flex align-items-center mb-3">
                                <div class="document-icon-large me-4">
                                    <i class="<?php echo getFileIcon($document['file_type']); ?>" style="font-size: 3rem;"></i>
                                </div>
                                <div>
                                    <h1 class="display-6 fw-bold mb-2">
                                        <?php echo htmlspecialchars($document['title']); ?>
                                    </h1>
                                    <div class="document-meta-header">
                                        <span class="me-4">
                                            <i class="fas fa-user me-2"></i>
                                            <?php echo htmlspecialchars($document['full_name'] ?: $document['username']); ?>
                                        </span>
                                        <span class="me-4">
                                            <i class="fas fa-calendar me-2"></i>
                                            <?php echo date('F j, Y', strtotime($document['created_at'])); ?>
                                        </span>
                                        <span class="me-4">
                                            <i class="fas fa-download me-2"></i>
                                            <?php echo number_format($document['downloads']); ?> downloads
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 text-end">
                            <div class="btn-group" role="group">
                                <a href="api/download.php?id=<?php echo $document['id']; ?>" 
                                   class="btn btn-light btn-lg">
                                    <i class="fas fa-download me-2"></i>Download
                                </a>
                                <?php if ($is_owner): ?>
                                    <a href="edit.php?id=<?php echo $document['id']; ?>" 
                                       class="btn btn-outline-light">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <button type="button" class="btn btn-outline-light" 
                                            onclick="confirmDelete(<?php echo $document['id']; ?>, '<?php echo addslashes($document['title']); ?>')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <!-- Main Content -->
        <div class="col-lg-8">
            <!-- Document Details -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-info-circle me-2"></i>Document Information
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <td class="fw-medium text-muted">Original Filename:</td>
                                    <td><?php echo htmlspecialchars($document['original_name']); ?></td>
                                </tr>
                                <tr>
                                    <td class="fw-medium text-muted">File Size:</td>
                                    <td><?php echo formatFileSize($document['file_size']); ?></td>
                                </tr>
                                <tr>
                                    <td class="fw-medium text-muted">File Type:</td>
                                    <td>
                                        <span class="badge bg-secondary">
                                            <?php echo strtoupper($document['file_type']); ?>
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="fw-medium text-muted">Visibility:</td>
                                    <td>
                                        <?php if ($document['is_public']): ?>
                                            <span class="badge bg-success">
                                                <i class="fas fa-globe me-1"></i>Public
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">
                                                <i class="fas fa-lock me-1"></i>Private
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <td class="fw-medium text-muted">Uploaded:</td>
                                    <td><?php echo date('F j, Y \a\t g:i A', strtotime($document['created_at'])); ?></td>
                                </tr>
                                <tr>
                                    <td class="fw-medium text-muted">Downloads:</td>
                                    <td><?php echo number_format($document['downloads']); ?></td>
                                </tr>
                                <?php if ($document['category_name']): ?>
                                <tr>
                                    <td class="fw-medium text-muted">Category:</td>
                                    <td>
                                        <span class="category-pill" style="background-color: <?php echo $document['category_color']; ?>">
                                            <?php echo htmlspecialchars($document['category_name']); ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endif; ?>
                                <tr>
                                    <td class="fw-medium text-muted">Share Link:</td>
                                    <td>
                                        <div class="input-group input-group-sm">
                                            <input type="text" class="form-control" 
                                                   value="<?php echo getBaseUrl(); ?>/view.php?id=<?php echo $document['id']; ?>" 
                                                   readonly id="shareLink">
                                            <button class="btn btn-outline-secondary" type="button" 
                                                    onclick="copyLink(document.getElementById('shareLink').value)">
                                                <i class="fas fa-copy"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    
                    <?php if ($document['description']): ?>
                        <hr>
                        <div>
                            <h6 class="fw-medium text-muted mb-2">Description:</h6>
                            <p class="mb-0"><?php echo nl2br(htmlspecialchars($document['description'])); ?></p>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($document['tags']): ?>
                        <hr>
                        <div>
                            <h6 class="fw-medium text-muted mb-2">Tags:</h6>
                            <div class="document-tags">
                                <?php 
                                $tags = explode(',', $document['tags']);
                                foreach ($tags as $tag): 
                                    $tag = trim($tag);
                                    if (!empty($tag)):
                                ?>
                                    <span class="tag-pill"><?php echo htmlspecialchars($tag); ?></span>
                                <?php 
                                    endif;
                                endforeach; 
                                ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- File Preview (if supported) -->
            <?php if (in_array($document['file_type'], ['jpg', 'jpeg', 'png', 'gif'])): ?>
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-eye me-2"></i>Preview
                        </h5>
                    </div>
                    <div class="card-body text-center">
                        <img src="<?php echo $document['file_path']; ?>" 
                             alt="<?php echo htmlspecialchars($document['title']); ?>"
                             class="img-fluid rounded shadow" 
                             style="max-block-size: 500px;">
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Related Documents -->
            <?php if (!empty($related_docs)): ?>
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-layer-group me-2"></i>Related Documents
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <?php foreach ($related_docs as $related): ?>
                                <div class="col-md-6 mb-3">
                                    <div class="related-doc-item">
                                        <div class="d-flex align-items-center">
                                            <div class="related-doc-icon me-3">
                                                <i class="<?php echo getFileIcon($related['file_type']); ?>"></i>
                                            </div>
                                            <div class="flex-grow-1">
                                                <h6 class="mb-1">
                                                    <a href="view.php?id=<?php echo $related['id']; ?>" 
                                                       class="text-decoration-none">
                                                        <?php echo htmlspecialchars($related['title']); ?>
                                                    </a>
                                                </h6>
                                                <small class="text-muted">
                                                    <?php echo formatFileSize($related['file_size']); ?> • 
                                                    <?php echo number_format($related['downloads']); ?> downloads
                                                </small>
                                                <?php if ($related['category_name']): ?>
                                                    <br>
                                                    <span class="category-pill-sm" style="background-color: <?php echo $related['category_color']; ?>">
                                                        <?php echo htmlspecialchars($related['category_name']); ?>
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Sidebar -->
        <div class="col-lg-4">
            <!-- Quick Actions -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-bolt me-2"></i>Quick Actions
                    </h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="api/download.php?id=<?php echo $document['id']; ?>" 
                           class="btn btn-primary">
                            <i class="fas fa-download me-2"></i>Download File
                        </a>
                        
                        <?php if ($is_owner): ?>
                            <a href="edit.php?id=<?php echo $document['id']; ?>" 
                               class="btn btn-outline-secondary">
                                <i class="fas fa-edit me-2"></i>Edit Details
                            </a>
                            <button type="button" class="btn btn-outline-danger" 
                                    onclick="confirmDelete(<?php echo $document['id']; ?>, '<?php echo addslashes($document['title']); ?>')">
                                <i class="fas fa-trash me-2"></i>Delete Document
                            </button>
                        <?php endif; ?>
                        
                        <button type="button" class="btn btn-outline-info" 
                                onclick="copyLink('<?php echo getBaseUrl(); ?>/view.php?id=<?php echo $document['id']; ?>')">
                            <i class="fas fa-share me-2"></i>Share Link
                        </button>
                        
                        <a href="documents.php" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Back to Documents
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Download Statistics (Owner Only) -->
            <?php if ($is_owner && !empty($download_logs)): ?>
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-chart-line me-2"></i>Recent Downloads
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="download-logs">
                            <?php foreach ($download_logs as $log): ?>
                                <div class="download-log-item d-flex justify-content-between align-items-center py-2 border-bottom">
                                    <div>
                                        <div class="fw-medium">
                                            <?php if ($log['username']): ?>
                                                <?php echo htmlspecialchars($log['full_name'] ?: $log['username']); ?>
                                            <?php else: ?>
                                                Anonymous User
                                            <?php endif; ?>
                                        </div>
                                        <small class="text-muted">
                                            <?php echo $log['ip_address']; ?>
                                        </small>
                                    </div>
                                    <div class="text-end">
                                        <small class="text-muted">
                                            <?php echo timeAgo($log['downloaded_at']); ?>
                                        </small>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <?php if (count($download_logs) >= 10): ?>
                            <div class="text-center mt-3">
                                <small class="text-muted">Showing last 10 downloads</small>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- File Information -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-info-circle me-2"></i>File Details
                    </h5>
                </div>
                <div class="card-body">
                    <div class="file-info">
                        <div class="file-info-item">
                            <i class="fas fa-file-alt text-muted me-2"></i>
                            <span class="fw-medium">Type:</span>
                            <span class="ms-2"><?php echo strtoupper($document['file_type']); ?></span>
                        </div>
                        
                        <div class="file-info-item">
                            <i class="fas fa-weight text-muted me-2"></i>
                            <span class="fw-medium">Size:</span>
                            <span class="ms-2"><?php echo formatFileSize($document['file_size']); ?></span>
                        </div>
                        
                        <div class="file-info-item">
                            <i class="fas fa-calendar text-muted me-2"></i>
                            <span class="fw-medium">Uploaded:</span>
                            <span class="ms-2"><?php echo date('M j, Y', strtotime($document['created_at'])); ?></span>
                        </div>
                        
                        <div class="file-info-item">
                            <i class="fas fa-download text-muted me-2"></i>
                            <span class="fw-medium">Downloads:</span>
                            <span class="ms-2"><?php echo number_format($document['downloads']); ?></span>
                        </div>
                        
                        <div class="file-info-item">
                            <i class="fas fa-user text-muted me-2"></i>
                            <span class="fw-medium">Owner:</span>
                            <span class="ms-2"><?php echo htmlspecialchars($document['full_name'] ?: $document['username']); ?></span>
                        </div>
                        
                        <div class="file-info-item">
                            <i class="fas fa-<?php echo $document['is_public'] ? 'globe' : 'lock'; ?> text-muted me-2"></i>
                            <span class="fw-medium">Visibility:</span>
                            <span class="ms-2"><?php echo $document['is_public'] ? 'Public' : 'Private'; ?></span>
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
                <p>Are you sure you want to delete this document? This action cannot be undone.</p>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>Warning:</strong> The file will be permanently removed from the server.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteBtn">Delete Document</button>
            </div>
        </div>
    </div>
</div>

<script>
let deleteDocumentId = null;

function confirmDelete(id, title) {
    deleteDocumentId = id;
    const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
    modal.show();
}

document.getElementById('confirmDeleteBtn').addEventListener('click', function() {
    if (deleteDocumentId) {
        deleteDocument(deleteDocumentId);
    }
});

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
            setTimeout(() => {
                window.location.href = 'documents.php';
            }, 1500);
        } else {
            showAlert(data.message, 'danger');
        }
        
        // Hide modal
        const modal = bootstrap.Modal.getInstance(document.getElementById('deleteModal'));
        modal.hide();
    })
    .catch(error => {
        showAlert('Error deleting document. Please try again.', 'danger');
        const modal = bootstrap.Modal.getInstance(document.getElementById('deleteModal'));
        modal.hide();
    });
}
</script>

<?php require_once 'includes/footer.php'; ?>