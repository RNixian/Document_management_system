<?php
$page_title = 'Dashboard';
require_once 'includes/functions.php';
requireLogin();

$user_id = $_SESSION['user_id'];
$user_division_id = (int)($_SESSION['division_id'] ?? 0);

try {
    // -------------------------------
    // Total documents (user or division)
    // -------------------------------
    $stmt = $db->prepare("
        SELECT COUNT(*) AS total 
        FROM documents d
        WHERE (
            d.user_id = ? 
            OR d.division_id IN (
                SELECT division_id FROM user_divisions WHERE user_id = ?
            )
        )
    ");
    $stmt->execute([$user_id, $user_id]);
    $total_docs = $stmt->fetch()['total'] ?? 0;

    // -------------------------------
    // Total file size
    // -------------------------------
    $stmt = $db->prepare("
        SELECT SUM(file_size) AS total_size 
        FROM documents d
        WHERE (
            d.user_id = ? 
            OR d.division_id IN (
                SELECT division_id FROM user_divisions WHERE user_id = ?
            )
        )
    ");
    $stmt->execute([$user_id, $user_id]);
    $total_size = $stmt->fetch()['total_size'] ?? 0;

    // -------------------------------
    // Total downloads
    // -------------------------------
    $stmt = $db->prepare("
        SELECT SUM(downloads) AS total_downloads 
        FROM documents d
        WHERE (
            d.user_id = ? 
            OR d.division_id IN (
                SELECT division_id FROM user_divisions WHERE user_id = ?
            )
        )
    ");
    $stmt->execute([$user_id, $user_id]);
    $total_downloads = $stmt->fetch()['total_downloads'] ?? 0;

    // -------------------------------
    // Recent documents (limit 6)
    // -------------------------------
    $stmt = $db->prepare("
        SELECT d.*, c.name AS category_name, c.color AS category_color
        FROM documents d
        LEFT JOIN categories c ON d.category_id = c.id
        WHERE (
            d.user_id = ? 
            OR d.division_id IN (
                SELECT division_id FROM user_divisions WHERE user_id = ?
            )
        )
        ORDER BY d.created_at DESC
        LIMIT 6
    ");
    $stmt->execute([$user_id, $user_id]);
    $recent_docs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // -------------------------------
    // Full documents query (for table/listing)
    // -------------------------------
    $sql = "
        SELECT d.*, 
               u.full_name, 
               u.username, 
               dv.name AS division_name
        FROM documents d
        INNER JOIN users u ON d.user_id = u.id
        INNER JOIN division dv ON d.division_id = dv.id
        WHERE (
            d.user_id = ? 
            OR d.division_id IN (
                SELECT division_id FROM user_divisions WHERE user_id = ?
            )
        )
        ORDER BY d.created_at DESC
    ";
    $stmt = $db->prepare($sql);
    $stmt->execute([$user_id, $user_id]);
    $documents = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    setAlert('Error loading dashboard data: ' . $e->getMessage(), 'danger');
    $total_docs = $total_size = $total_downloads = 0;
    $recent_docs = $documents = [];
}

require_once 'includes/header.php';
?>

<!-- Dashboard Styles -->
<style>
    body {
        background: #1E1E2E;
        background-size: 400% 400%;
        font-family: 'Segoe UI', sans-serif;
        color: #f5f6fa;
    }
    /* @keyframes gradientMove {
        0% {background-position: 0% 50%;}
        50% {background-position: 100% 50%;}
        100% {background-position: 0% 50%;}
    } */

    .glass-card {
        background: rgba(30,34,54,0.92);
        border-radius: 1.25rem;
        box-shadow: 0 8px 32px rgba(31, 38, 135, 0.18);
        backdrop-filter: blur(12px);
        border: 1.5px solid #3B82F6;
        padding: 1.5rem;
        transition: transform 0.2s, box-shadow 0.2s;
    }
    .glass-card:hover {
        transform: translateY(-4px) scale(1.02);
        box-shadow: 0 8px 32px rgba(53,92,253,0.18);
    }

    .stats-card {
        text-align: center;
        padding: 1.25rem;
        border-radius: 1rem;
        background: rgba(36, 40, 61, 0.95);
    }
    .stats-icon {
        font-size: 2rem;
        margin-block-end: 0.5rem;
        color: #6e8efb;
    }
    .stats-number {
        font-size: 1.6rem;
        font-weight: 700;
    }
    .stats-label {
        font-size: 0.95rem;
        color: #c7c9d9;
    }

    .btn-primary {
        display: inline-block;
        background: linear-gradient(90deg, #6e8efb 0%, #355cfd 100%);
        color: #fff !important;
        border-radius: 0.75rem;
        padding: 0.7rem 1.2rem;
        font-weight: 600;
        border: none;
        text-decoration: none;
        transition: transform 0.2s, box-shadow 0.2s;
    }
    .btn-primary:hover {
        transform: translateY(-2px) scale(1.04);
        box-shadow: 0 4px 16px #355cfd55;
    }
    .btn-outline {
        display: inline-block;
        border: 1.5px solid #6e8efb;
        border-radius: 0.75rem;
        padding: 0.7rem 1.2rem;
        color: #f5f6fa !important;
        font-weight: 600;
        text-decoration: none;
    }
    .btn-outline:hover {
        background: #232946;
        color: #fff !important;
    }

    .document-card {
        background: rgba(36, 40, 61, 0.9);
        border-radius: 1rem;
        padding: 1rem;
        margin-block-end: 1rem;
        transition: box-shadow 0.2s, transform 0.2s;
    }
    .document-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 18px rgba(53,92,253,0.18);
    }
    .document-title {
        font-weight: 600;
        color: #fff;
        margin-block-end: 0.3rem;
    }
    .document-meta small {
        color: #c7c9d9;
    }
    .category-pill {
        display: inline-block;
        padding: 0.2em 0.6em;
        border-radius: 1em;
        font-size: 0.8em;
        color: #fff;
        margin-block-start: 0.3em;
        font-weight: 500;
    }

    .file-type-badge {
        background: linear-gradient(90deg, #6e8efb 0%, #355cfd 100%);
        border-radius: 0.75em;
        padding: 0.25em 0.75em;
        color: #fff;
        font-weight: 500;
        font-size: 0.85rem;
    }
</style>

<div class="container-fluid px-4">

    <!-- Welcome -->
    <div class="glass-card mb-4">
        <h2>👋 Welcome, <?php echo htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['username']); ?></h2>
        <p>Manage your documents efficiently with <b>PTNI4</b>. Upload, organize, and share your files with ease.</p>
        <a href="upload.php" class="btn-primary"><i class="fas fa-cloud-upload-alt me-2"></i> Upload New Document</a>
    </div>

    <!-- Stats -->
    <div class="row mb-4">
        <div class="col-md-3"><div class="stats-card">
            <div class="stats-icon"><i class="fas fa-file-alt"></i></div>
            <div class="stats-number"><?php echo number_format($total_docs); ?></div>
            <div class="stats-label">Total Documents</div>
        </div></div>
        <div class="col-md-3"><div class="stats-card">
            <div class="stats-icon"><i class="fas fa-hdd"></i></div>
            <div class="stats-number"><?php echo formatFileSize($total_size); ?></div>
            <div class="stats-label">Storage Used</div>
        </div></div>
        <div class="col-md-3"><div class="stats-card">
            <div class="stats-icon"><i class="fas fa-download"></i></div>
            <div class="stats-number"><?php echo number_format($total_downloads); ?></div>
            <div class="stats-label">Total Downloads</div>
        </div></div>
        <div class="col-md-3"><div class="stats-card">
            <div class="stats-icon"><i class="fas fa-calendar-day"></i></div>
            <div class="stats-number"><?php echo date('d'); ?></div>
            <div class="stats-label"><?php echo date('M Y'); ?></div>
        </div></div>
    </div>

    <div class="row">
        <!-- Recent Documents -->
        <div class="col-lg-8 mb-3">
            <div class="glass-card">
                <h5 class="mb-3"><i class="fas fa-clock me-2"></i>Recent Documents</h5>
                <?php if (empty($recent_docs)): ?>
                    <div class="text-center py-4">
                        <i class="fas fa-folder-open" style="font-size: 3rem; color: #777;"></i>
                        <h5 class="mt-2">No documents yet</h5>
                        <a href="upload.php" class="btn-primary mt-2"><i class="fas fa-plus me-2"></i> Upload Document</a>
                    </div>
                <?php else: ?>
                    <div class="row">
                        <?php foreach ($recent_docs as $doc): ?>
                            <div class="col-md-6">
                                <div class="document-card">
                                    <h6 class="document-title"><?php echo htmlspecialchars($doc['title']); ?></h6>
                                    <div class="document-meta">
                                        <small><i class="fas fa-calendar me-1"></i> <?php echo date('M j, Y', strtotime($doc['created_at'])); ?></small><br>
                                        <small><i class="fas fa-weight me-1"></i> <?php echo formatFileSize($doc['file_size']); ?></small><br>
                                        <?php if ($doc['category_name']): ?>
                                            <span class="category-pill" style="background-color: <?php echo $doc['category_color']; ?>">
                                                <?php echo htmlspecialchars($doc['category_name']); ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="mt-2">
                                        <a href="api/download.php?id=<?php echo $doc['id']; ?>" class="btn-outline btn-sm">Download</a>
                                        <a href="view.php?id=<?php echo $doc['id']; ?>" class="btn-outline btn-sm">View</a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="text-center mt-2">
                        <a href="documents.php" class="btn-outline"><i class="fas fa-folder-open me-2"></i> View All Documents</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- File Types & Quick Actions -->
        <div class="col-lg-4">
            <div class="glass-card mb-3">
                <h5 class="mb-3"><i class="fas fa-chart-pie me-2"></i>File Types</h5>
                <?php if (empty($file_types)): ?>
                    <p class="text-center">No files uploaded yet</p>
                <?php else: ?>
                    <?php foreach ($file_types as $type): ?>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="file-type-badge"><?php echo strtoupper($type['file_type']); ?></span>
                            <span><?php echo $type['count']; ?></span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div class="glass-card">
                <h5 class="mb-3"><i class="fas fa-bolt me-2"></i>Quick Actions</h5>
                <div class="d-grid gap-3">
                    <a href="upload.php" class="btn-primary"><i class="fas fa-cloud-upload-alt me-2"></i> Upload Document</a>
                    <a href="documents.php" class="btn-outline"><i class="fas fa-folder-open me-2"></i> Browse Documents</a>
                    <a href="profile.php" class="btn-outline"><i class="fas fa-user me-2"></i> Edit Profile</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
