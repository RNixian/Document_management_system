<?php
$page_title = 'Department Documents';
require_once '../includes/functions.php';
requireLogin();
requireAdmin();

$user_division_id = $_SESSION['division_id'] ?? 0;

// Get divisions for buttons
$divisions = [];
try {
    $stmt = $db->query("SELECT id, name FROM division ORDER BY name");
    $divisions = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $divisions = [];
}

// --- Filters ---
$division_filter = isset($_GET['division']) ? (int)$_GET['division'] : 0; // 0 = all
$search = trim($_GET['search'] ?? '');

// Build WHERE
$where = "WHERE 1";
$params = [];

// Apply division filter if chosen
if ($division_filter > 0) {
    $where .= " AND d.division_id = ?";
    $params[] = $division_filter;
}


// Apply search filter if entered
if ($search) {
    $where .= " AND (d.title LIKE ? OR d.description LIKE ? OR d.original_name LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

// Final query
$sql = "SELECT d.*, u.full_name, u.username, v.name AS division_name
        FROM department_documents d
        JOIN users u ON d.user_id = u.id
        JOIN division v ON d.division_id = v.id
        $where
        ORDER BY d.created_at DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$documents = $stmt->fetchAll();

// --- Breadcrumb name (if division selected) ---
$breadcrumb_division_name = '';
if ($division_filter > 0) {
    foreach ($divisions as $div) {
        if ($div['id'] == $division_filter) {
            $breadcrumb_division_name = $div['name'];
            break;
        }
    }
}

require_once '../includes/admin_header.php';
?>

<style>
/* Dashboard-style CSS (copied from dashboard.php) */
.border-left-primary { border-left: 0.25rem solid #4e73df !important; }
.border-left-success { border-left: 0.25rem solid #1cc88a !important; }
.border-left-info { border-left: 0.25rem solid #36b9cc !important; }
.border-left-warning { border-left: 0.25rem solid #f6c23e !important; }
.text-gray-800 { color: #5a5c69 !important; }
.text-gray-300 { color: #dddfeb !important; }
.font-weight-bold { font-weight: 700 !important; }
.text-xs { font-size: 0.7rem; }
.no-gutters { margin-right: 0; margin-left: 0; }
.no-gutters > .col, .no-gutters > [class*="col-"] { padding-right: 0; padding-left: 0; }
.card { position: relative; display: flex; flex-direction: column; min-width: 0; word-wrap: break-word; background-color: #020930ff; background-clip: border-box; border: 2px solid #0d183bff; border-radius: 0.35rem; }
.shadow { box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15) !important; }
.py-2 { padding-top: 0.5rem !important; padding-bottom: 0.5rem !important; }
.py-3 { padding-top: 1rem !important; padding-bottom: 1rem !important; }
.h-100 { height: 100% !important; }
.card-header { background-color: #f8f9fc; border-bottom: 1px solid #e3e6f0; }
.dashboard-header { background: linear-gradient(90deg,rgb(43, 46, 63) 0%,rgb(6, 30, 187) 100%) !important; color: white; border-radius: 0.75rem; margin-bottom: 2rem; border: none; box-shadow: 0 0.5rem 2rem rgba(102, 126, 234, 0.3); }
.dashboard-header .card-body { padding: 2rem; }
.dashboard-header h1 { color: white !important; font-weight: 700; text-shadow: 0 2px 4px rgba(0,0,0,0.1); }
.dashboard-header .welcome-text { background: rgba(255, 255, 255, 0.15); border-radius: 0.5rem; padding: 1rem 1.5rem; backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.2); }
.dashboard-header .welcome-text .opacity-75 { opacity: 0.85; }
.dashboard-header .lead { color: rgba(255, 255, 255, 0.9); text-shadow: 0 1px 2px rgba(0,0,0,0.1); }
.border-left-primary:hover, .border-left-success:hover, .border-left-info:hover, .border-left-warning:hover { transform: translateY(-5px); box-shadow: 0 0.5rem 2rem rgba(58, 59, 69, 0.25) !important; transition: all 0.3s ease; }
.btn-lg { padding: 1rem 1.5rem; font-size: 1.1rem; border-radius: 0.5rem; transition: all 0.3s ease; }
.btn-lg:hover { transform: translateY(-2px); box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15); }
.table th {  border-top: none;font-weight: 600;font-size: 0.875rem;background: linear-gradient(90deg, #0b257cff 0%, #010b33ff 100%);color: white;}
.table td { border-top: 1px solid #0e1b46ff; vertical-align: middle; }
@media (max-width: 768px) { .dashboard-header .card-body { padding: 1.5rem; } .dashboard-header h1 { font-size: 1.75rem; } .dashboard-header .col-md-4 { text-align: center !important; margin-top: 1rem; } .welcome-text { display: inline-block; text-align: center; } }
.table-responsive { border-radius: 0.5rem; overflow: hidden; }
.table tbody tr:hover { background-color: rgba(78, 115, 223, 0.05); transform: scale(1.01); }
@keyframes fadeInUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
.card { animation: fadeInUp 0.6s ease-out; }
.card:nth-child(1) { animation-delay: 0.1s; }
.card:nth-child(2) { animation-delay: 0.2s; }
.card:nth-child(3) { animation-delay: 0.3s; }
.card:nth-child(4) { animation-delay: 0.4s; }
.bg-light { background-color: #f8f9fc !important; border: 1px solid #091438ff; transition: all 0.3s ease; }
.bg-light:hover { background-color: #eaecf4 !important; transform: translateY(-2px); box-shadow: 0 0.25rem 0.75rem rgba(58, 59, 69, 0.1); }
</style>

<div class="container-fluid px-4">
    <!-- Dashboard Header with Gradient Background -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card dashboard-header">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h1 class="h3 mb-2 fw-bold">
                                <i class="fas fa-building me-3"></i>Department Documents
                            </h1>
                            <p class="lead mb-0">
                                Upload and manage files for your department
                            </p>
                        </div>
                        <div class="col-md-4 text-end">
                            <div class="welcome-text">
                                <span class="opacity-75">Welcome,</span><br>
                                <strong class="fs-5"><?php echo htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['username']); ?>!</strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Stats + Upload -->
    <div class="row mb-4 align-items-center">
        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Department Files</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo number_format(count($documents)); ?></div>
                    </div>
                    <i class="fas fa-file-alt fa-2x text-gray-300"></i>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4 d-flex align-items-center">
            <button class="btn btn-success btn-lg ms-xl-3" data-bs-toggle="modal" data-bs-target="#uploadModal">
                <i class="fas fa-plus me-2"></i>Upload Department File
            </button>
        </div>
    </div>

 <!-- 📍 Breadcrumb -->
 <div id="breadcrumb-path" class="mb-3 fw-bold text-secondary">
    <span class="breadcrumb-item"><a href="department.php">Division</a></span>
    <?php if ($division_filter > 0): ?>
        > <span class="breadcrumb-item active"><?= htmlspecialchars($breadcrumb_division_name) ?></span>
    <?php endif; ?>
</div>


  <!-- Division Buttons -->
<?php if ($division_filter === 0): ?>
<div id="division-buttons" class="mb-4" style="
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 20px;
    width: 100%;
">
    <?php foreach ($divisions as $division): ?>
        <a href="?division=<?= $division['id'] ?>" 
           class="text-center text-decoration-none"
           style="cursor: pointer; display: flex; flex-direction: column; 
                  align-items: center; justify-content: flex-start; padding: 10px;">
            <div style="background-image: url('https://cdn-icons-png.flaticon.com/512/716/716784.png');
                        background-size: contain; background-repeat: no-repeat;
                        background-position: center; width: 150px; height: 150px;
                        margin-bottom: 8px;"></div>
            <span style="color: #000; font-weight: 600;">
                <?= htmlspecialchars($division['name']) ?>
            </span>
        </a>
    <?php endforeach; ?>
</div>
<?php endif; ?>


<!-- File Container -->
    <?php if ($division_filter > 0): ?>
<div id="documents-container" class="card shadow bg-dark text-white">
    <div class="card-header">
        <b>Department Files (<?= count($documents) ?>)</b>
    </div>
    <div class="card-body">
        <?php if (empty($documents)): ?>
            <div class="text-center text-muted py-4">No files found for this department.</div>
        <?php else: ?>
            <div class="row">
                <?php foreach ($documents as $doc): ?>
                    <div class="col-md-3 mb-3">
                        <div class="border rounded p-3 shadow-sm h-100 bg-secondary text-white">
                            <div class="mb-2">
                                <strong>Title:</strong><br>
                                <i class="<?php echo getFileIcon($doc['file_type'] ?? 'unknown'); ?> me-2"></i>
                                <?= htmlspecialchars($doc['title']) ?><br>
                                <small class="text-muted"><?= htmlspecialchars($doc['original_name']) ?></small>
                            </div>
                            <div class="mb-2"><strong>Owner:</strong><br>
                                <?= htmlspecialchars($doc['full_name'] ?? $doc['username']) ?>
                            </div>
                            <div class="mb-2"><strong>Department:</strong><br>
                                <?= htmlspecialchars($doc['division_name']) ?>
                            </div>
                            <div class="mb-2"><strong>Size:</strong><br>
                                <?= formatFileSize($doc['file_size']) ?>
                            </div>
                            <div class="mb-2"><strong>Uploaded:</strong><br>
                                <?= date('M j, Y g:i A', strtotime($doc['created_at'])) ?>
                            </div>
                            <div class="mt-3 d-flex flex-wrap gap-2">
                                <a href="../api/department_download.php?id=<?= $doc['id'] ?>" class="btn btn-sm btn-success">
                                    <i class="fas fa-download"></i> Download
                                </a>
                                <?php 
$can_delete = false;

// Case 1: owner of the document
if ($doc['user_id'] == $_SESSION['user_id']) {
    $can_delete = true;

// Case 2: admin + division check only if division_id exists in session
} elseif (isAdmin() && isset($_SESSION['division_id']) && $doc['division_id'] == $_SESSION['division_id']) {
    $can_delete = true;
}

if ($can_delete): ?>
    <button class="btn btn-sm btn-danger" onclick="deleteDepartmentDoc(<?= $doc['id'] ?>, this)">
        <i class="fas fa-trash"></i> Delete
    </button>
<?php endif; ?>

                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

</div>

<script>
let currentDivision = '';

function backToDivFolder() {
    currentDivision = '';
    document.getElementById('division-buttons').style.display = 'grid';
    document.getElementById('documents-container').style.display = 'none';
    document.getElementById('breadcrumb-path').innerHTML =
        `<span class="breadcrumb-item active" onclick="backToDivFolder()" style="cursor:pointer">Division</span>`;
}

function showDivisionDocs(divisionName) {
    currentDivision = divisionName;
    document.getElementById('division-buttons').style.display = 'none';
    document.getElementById('documents-container').style.display = 'block';
    document.getElementById('breadcrumb-path').innerHTML =
        `<span class="breadcrumb-item text-primary" onclick="backToDivFolder()" style="cursor:pointer">Division</span> > 
         <span class="breadcrumb-item active">${divisionName}</span>`;

    // filter documents
    const docs = document.querySelectorAll('#documents-container .col-md-3');
    docs.forEach(doc => {
        const divAttr = doc.getAttribute('data-division');
        doc.style.display = (divAttr === divisionName) ? 'block' : 'none';
    });
}
</script>


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
                    <div class="mb-3">
                        <label class="form-label">Title</label>
                        <input type="text" name="title" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Department</label>
                        <select name="division_id" class="form-select" required>
                            <?php foreach ($divisions as $div): ?>
                                <option value="<?php echo $div['id']; ?>" <?php if ($division_filter == $div['id']) echo 'selected'; ?>>
                                    <?php echo htmlspecialchars($div['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
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
    fetch('../api/department_upload.php', {
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
    fetch('../api/department_delete.php', {
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


<?php require_once '../includes/footer.php'; ?>