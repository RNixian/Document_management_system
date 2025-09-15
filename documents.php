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

$user_id = $_SESSION['user_id'] ?? 0; // logged-in user ID

$user_id = $_SESSION['user_id'] ?? 0; // logged-in user ID
$user_divisions = [];

if ($user_id > 0) {
    $stmt = $db->prepare("
        SELECT d.id, d.name
        FROM user_divisions ud
        INNER JOIN division d ON ud.division_id = d.id
        WHERE ud.user_id = ?
    ");
    if ($stmt) {
        $stmt->execute([$user_id]);
        $user_divisions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        die("Query preparation failed.");
    }
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
/* Header */
.page-header {
    background: #2c2f48;
    color: #fff;
    padding: 1.5rem;
    border-radius: .5rem;
    margin-block-end: 1rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.page-header h1 {
    font-size: 1.6rem;
    margin: 0;
}
.page-header p {
    margin: 0;
    font-size: .9rem;
    opacity: .8;
}
.page-header .btn {
    border-radius: .4rem;
}

/* Card */
.card {
    border: none;
    border-radius: .5rem;
    margin-block-end: 1rem;
}
.card-header {
    background: #2c2f48;
    color: #fff;
    font-weight: 600;
    border-radius: .5rem .5rem 0 0 !important;
}
.card-body {
    background: #fff;
    border-radius: 0 0 .5rem .5rem !important;
}

/* Search */
.search-box input, .search-box select {
    border-radius: .3rem;
}
.search-box button {
    border-radius: .3rem;
}

/* Table */
.table {
    margin-block-end: 0;
}
.table th {
    background: #2c2f48;
    color: #fff;
}
.table td {
    vertical-align: middle;
}
.badge {
    font-size: .8rem;
    padding: .4em .6em;
    border-radius: .3rem;
}
.btn-sm {
    border-radius: .3rem;
    margin-inline-end: .2rem;
}
</style>

<div class="container-fluid px-4">
    <!-- Header -->
    <div class="page-header shadow-sm">
        <div>
            <h1>Department Documents</h1>
            <p>My Department: <?php echo htmlspecialchars($division_name); ?></p>
        </div>
        <div>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#uploadModal">
                <i class="fas fa-cloud-upload-alt"></i> Upload File
            </button>
        </div>
    </div>

    <!-- Search -->
    <div class="card shadow-sm mb-3">
        <div class="card-header">
            <i class="fas fa-search me-2"></i> Search & Filter
        </div>
        <div class="card-body">
            <form method="GET" class="row g-2 search-box">
                <div class="col-md-10">
                    <input type="text" class="form-control" name="search" placeholder="Title, description, filename..." value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="col-md-2">
                    <button class="btn btn-primary w-100"><i class="fas fa-search"></i> Filter</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Documents Table -->
    <div class="card shadow-sm">
        <div class="card-header">
            <i class="fas fa-folder-open me-2"></i> All Department Documents (<?php echo count($documents); ?> total)
        </div>
        <div class="card-body">
            <?php if (empty($documents)): ?>
                <div class="text-center text-muted py-4">No files found for your department.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-bordered align-middle">
                    <thead>
                        <tr>
                            <th>Document</th>
                            <th>Owner</th>
                            <th>Division</th>
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
                            <td><?php echo htmlspecialchars($doc['division_name']); ?></td> <!-- ✅ Added -->
                            <td><?php echo formatFileSize($doc['file_size']); ?></td>
                            <td><?php echo date('M j, Y g:i A', strtotime($doc['created_at'])); ?></td>
                            <td>
                                <a href="api/department_download.php?id=<?php echo $doc['id']; ?>" class="btn btn-success btn-sm" title="Download">
                                    <i class="fas fa-download"></i>
                                </a>
                                <?php if ($doc['user_id'] == $user_id): ?>
                                <button class="btn btn-danger btn-sm" onclick="deleteDepartmentDoc(<?php echo $doc['id']; ?>, this)" title="Delete">
                                    <i class="fas fa-trash"></i>
                                </button>
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
                    <div class="mb-3">
    <label class="form-label">Select Division to Upload</label>
    <div>
        <?php if (!empty($user_divisions)): ?>
            <?php foreach ($user_divisions as $division): ?>
                <button type="button" 
                        class="btn btn-outline-primary btn-sm me-2 mb-2 division-btn" 
                        data-division-id="<?php echo $division['id']; ?>">
                    <?php echo htmlspecialchars($division['name']); ?>
                </button>
            <?php endforeach; ?>
            <input type="hidden" name="division_id" id="divisionInput">
        <?php else: ?>
            <p class="text-muted">No divisions assigned.</p>
        <?php endif; ?>
    </div>
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

document.addEventListener("DOMContentLoaded", function () {
    const divisionInput = document.getElementById("divisionInput");
    const buttons = document.querySelectorAll(".division-btn");

    buttons.forEach(btn => {
        btn.addEventListener("click", function () {
            buttons.forEach(b => b.classList.remove("active")); // remove active from others
            this.classList.add("active");
            divisionInput.value = this.getAttribute("data-division-id");
        });
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>
