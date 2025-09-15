<?php
$page_title = 'My Department Documents';
require_once 'includes/functions.php';
requireLogin();

$user_id = $_SESSION['user_id'];
$user_division_id = (int)($_SESSION['division_id'] ?? 0);

// Get division name
$division_name = '';
try {
    $stmt = $db->prepare("SELECT name FROM division WHERE id = ?");
    $stmt->execute([$user_division_id]);
    $division_name = $stmt->fetchColumn();
} catch (PDOException $e) {
    $division_name = 'Unknown';
}

// Search/filter
$search = trim($_GET['search'] ?? '');

// Query department documents
$where = "
    WHERE (
        d.user_id = ? 
        OR d.division_id IN (
            SELECT division_id FROM user_divisions WHERE user_id = ?
        )
    )
";
$params = [$user_id, $user_id];

if ($search) {
    $where .= " AND (d.title LIKE ? OR d.description LIKE ? OR d.original_name LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$sql = "SELECT d.*, u.full_name, u.username, dv.name AS division_name
        FROM department_documents d
        JOIN users u ON d.user_id = u.id
        JOIN division dv ON d.division_id = dv.id
        $where
        ORDER BY d.created_at DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$documents = $stmt->fetchAll(PDO::FETCH_ASSOC);

require_once 'includes/header.php';
?>

<style>
.dashboard-welcome {
    background: linear-gradient(135deg, #030e3fff 0%, #103d9eff 100%);
    color: white;
    border-radius: 1.5rem;
    box-shadow: 0 8px 32px 0 rgba(76, 68, 182, 0.15);
    overflow: hidden;
    padding: 2.5rem 1.5rem 2rem 1.5rem;
    margin-block-end: 1.25rem;
}
.stats-card {
    background: #fff;
    border-radius: 1rem;
    box-shadow: 0 4px 24px 0 rgba(76, 68, 182, 0.07);
    padding: 1rem 0.75rem;
    text-align: center;
    transition: transform 0.15s, box-shadow 0.15s;
    position: relative;
    margin-block-end: 0.75rem;
     border-style: solid;
    border-color: rgba(2, 25, 63, 0.5); 
}
.stats-card:hover {
    transform: translateY(-4px) scale(1.03);
    box-shadow: 0 8px 32px 0 rgba(76, 68, 182, 0.13);
    
}
.stats-icon {
    font-size: 2rem;
    margin-block-end: 0.35rem;
    color: #020d69ff;
    
}
.stats-number {
    font-size: 1.7rem;
    font-weight: 700;
    color: #333;
}
.stats-label {
    font-size: 1rem;
    color: #888;
    margin-block-start: 0.15rem;
}
.quick-action-btn {
    font-size: 1.05rem;
    font-weight: 500;
    border-radius: 0.75em;
    transition: background 0.15s, color 0.15s;
}
.quick-action-btn-primary {
    background: linear-gradient(90deg, #062091ff 0%, #0612b4ff 100%);
    color: #fff;
    border: none;
}
.quick-action-btn-primary:hover {
    background: linear-gradient(90deg, #764ba2 0%, #667eea 100%);
    color: #fff;
}
.table th, .table td { vertical-align: middle; }
</style>

<div class="container-fluid px-4">
    <!-- Welcome Section -->
    <div class="row mb-3">
        <div class="col-12">
            <div class="dashboard-welcome p-4 mb-2">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h1 class="display-5 fw-bold mb-2">
                            My Department: <?php echo htmlspecialchars($division_name); ?>
                        </h1>
                        <p class="lead mb-3">
                            View and upload files for your department.
                        </p>
                        <button class="btn quick-action-btn quick-action-btn-primary btn-lg shadow" data-bs-toggle="modal" data-bs-target="#uploadModal">
                            <i class="fas fa-cloud-upload-alt me-2"></i>Upload File
                        </button>
                    </div>
                    <div class="col-md-4 text-center">
                        <i class="fas fa-building" style="font-size: 7rem; opacity: 0.18;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics Card -->
    <div class="row mb-3 g-3">
        <div class="col-md-4">
            <div class="stats-card">
                <div class="stats-icon">
                    <i class="fas fa-file-alt"></i>
                </div>
                <div class="stats-number"><?php echo number_format(count($documents)); ?></div>
                <div class="stats-label">Department Files</div>
            </div>
        </div>
    </div>

    <!-- Search -->
    <form method="GET" class="row g-2 mb-3">
        <div class="col-md-10">
            <input type="text" class="form-control" name="search" placeholder="Search files..." value="<?php echo htmlspecialchars($search); ?>">
        </div>
        <div class="col-md-2">
            <button class="btn btn-outline-primary w-100"><i class="fas fa-search"></i> Search</button>
        </div>
    </form>

    <!-- Table -->
    <div class="card shadow">
        <div class="card-header"><b>Department Files (<?php echo count($documents); ?>)</b></div>
        <div class="card-body">
            <?php if (empty($documents)): ?>
                <div class="text-center text-muted py-4">No files found for your department.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Title</th>
                                <th>Owner</th>
                                <th>Size</th>
                                <th>Uploaded</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($documents as $doc): ?>
                            <tr>
                                <td>
                                    <b><?php echo htmlspecialchars($doc['title']); ?></b><br>
                                    <small class="text-muted"><?php echo htmlspecialchars($doc['original_name']); ?></small>
                                </td>
                                <td><?php echo htmlspecialchars($doc['full_name'] ?? $doc['username']); ?></td>
                                <td><?php echo formatFileSize($doc['file_size']); ?></td>
                                <td><?php echo date('M j, Y g:i A', strtotime($doc['created_at'])); ?></td>
                                <td>
                                    <a href="api/department_download.php?id=<?php echo $doc['id']; ?>" class="btn btn-sm btn-success" title="Download"><i class="fas fa-download"></i></a>
                                    <?php if ($doc['user_id'] == $user_id): ?>
                                    <button class="btn btn-sm btn-danger" onclick="deleteDepartmentDoc(<?php echo $doc['id']; ?>, this)"><i class="fas fa-trash"></i></button>
                                    <?php endif; ?>
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

<!-- Upload Modal -->
<div class="modal fade" id="uploadModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="deptUploadForm" enctype="multipart/form-data">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-upload me-2"></i>Upload Department File</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="division_id" value="<?php echo (int)$user_division_id; ?>">
                    <div class="mb-3">
                        <label class="form-label">Title</label>
                        <input type="text" name="title" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">File</label>
                        <input type="file" name="file" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tags</label>
                        <input type="text" name="tags" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Visibility</label>
                        <select name="is_public" class="form-select">
                            <option value="0">Private (Department Only)</option>
                            <option value="1">Public (All Departments)</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Upload</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.getElementById('deptUploadForm').onsubmit = function(e) {
    e.preventDefault();
    var form = e.target;
    var data = new FormData(form);
    fetch('api/department_upload.php', {
        method: 'POST',
        body: data
    })
    .then(r => r.json())
    .then(res => {
        alert(res.message);
        if (res.success) location.reload();
    });
};

function deleteDepartmentDoc(id, btn) {
    if (!confirm('Delete this file?')) return;
    btn.disabled = true;
    fetch('api/department_delete.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({id: id})
    })
    .then(r => r.json())
    .then(res => {
        alert(res.message);
        if (res.success) location.reload();
        else btn.disabled = false;
    });
}
</script>

<?php require_once 'includes/footer.php'; ?>