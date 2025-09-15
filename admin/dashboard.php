<?php
$page_title = 'Admin Dashboard';
// Include functions (which includes database)
require_once '../includes/functions.php';
requireLogin();
requireAdmin();

// Make sure $db is available globally
global $db;

require_once '../includes/admin_header.php';
?>

<style>
/* Background */
body {
    font-family: 'Segoe UI', sans-serif;
    background:  rgba(47, 52, 77, 0.92);
    background-size: 400% 400%;
    animation: gradientMove 18s ease infinite;
    min-height: 100vh;
}
@keyframes gradientMove {
    0% {background-position: 0% 50%;}
    50% {background-position: 100% 50%;}
    100% {background-position: 0% 50%;}
}

/* Glassmorphism */
.glass-card {
    background: rgba(30,34,54,0.85);
    border-radius: 1rem;
    backdrop-filter: blur(14px);
    border: 1px solid rgba(255,255,255,0.1);
    padding: 1.5rem;
    color: #fff;
    transition: all 0.3s ease;
    padding-left: 2rem;
}
.glass-card:hover {
    transform: translateY(-5px) scale(1.02);
    box-shadow: 0 12px 30px rgba(53,92,253,0.25);
}

/* Header */
.dashboard-header {
    background: linear-gradient(135deg, #355cfd, #6e8efb);
    border-radius: 1rem;
    padding: 2rem;
    margin-bottom: 2rem;
    color: #fff;
    box-shadow: 0 10px 30px rgba(0,0,0,0.25);
}
.dashboard-header h1 {
    font-weight: 700;
    text-shadow: 0 2px 6px rgba(0,0,0,0.3);
}

/* Stats */
.stats-number {
    font-size: 1.8rem;
    font-weight: 700;
}
.stats-label {
    font-size: 0.9rem;
    opacity: 0.8;
}

/* Buttons */
.btn-modern {
    background: linear-gradient(90deg,#6e8efb,#355cfd);
    color: #fff !important;
    border: none;
    border-radius: 0.75rem;
    padding: 0.9rem 1.2rem;
    font-weight: 600;
    transition: all 0.3s ease;
    display: inline-block;
    text-align: center;
    width: 100%;
}
.btn-modern:hover {
    transform: translateY(-2px) scale(1.03);
    box-shadow: 0 6px 20px rgba(53,92,253,0.4);
}

/* Table */
.table {
    color: #fff;
}
.table thead {
    background: rgba(255,255,255,0.1);
}
.table tbody tr:hover {
    background: rgba(255,255,255,0.08);
}

/* Animations */
@keyframes fadeInUp {
    from {opacity:0; transform:translateY(20px);}
    to {opacity:1; transform:translateY(0);}
}
.glass-card, .dashboard-header {
    animation: fadeInUp 0.6s ease forwards;
}
</style>

<div class="container-fluid px-4">
    <!-- Header -->
    <div class="dashboard-header mb-4">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h1><i class="fas fa-shield-alt me-2"></i>Admin Dashboard</h1>
                <p class="mb-0">Manage your system efficiently and effectively</p>
            </div>
            <div class="col-md-4 text-end">
                <div class="glass-card p-2">
                    <span class="opacity-75">Welcome back,</span><br>
                    <strong><?php echo htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['username']); ?>!</strong>
                </div>
            </div>
        </div>
    </div>

    <!-- Stats -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="glass-card text-center">
                <div class="stats-number">
                    <?php
                    try { echo number_format($db->query("SELECT COUNT(*) FROM users")->fetchColumn()); }
                    catch (PDOException $e) { echo '0'; }
                    ?>
                </div>
                <div class="stats-label"><i class="fas fa-users me-1"></i>Total Users</div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="glass-card text-center">
                <div class="stats-number">
                    <?php
                    try { echo number_format($db->query("SELECT COUNT(*) FROM documents")->fetchColumn()); }
                    catch (PDOException $e) { echo '0'; }
                    ?>
                </div>
                <div class="stats-label"><i class="fas fa-file-alt me-1"></i>Total Documents</div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="glass-card text-center">
                <div class="stats-number">
                    <?php
                    try { echo number_format($db->query("SELECT COUNT(*) FROM categories")->fetchColumn()); }
                    catch (PDOException $e) { echo '0'; }
                    ?>
                </div>
                <div class="stats-label"><i class="fas fa-tags me-1"></i>Categories</div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="glass-card text-center">
                <div class="stats-number"><?php echo PHP_VERSION; ?></div>
                <div class="stats-label"><i class="fas fa-server me-1"></i>PHP Version</div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="glass-card mb-4">
        <h5><i class="fas fa-bolt me-2"></i>Admin Actions</h5>
        <div class="row">
            <div class="col-md-3 mb-3" style="margin-left: 250px;">
                <a href="users.php" class="btn-modern"><i class="fas fa-users me-2"></i>Manage Users</a>
            </div>
            <div class="col-md-3 mb-3">
                <a href="documents.php" class="btn-modern"><i class="fas fa-file-alt me-2"></i>Manage Documents</a>
            </div>
            <div class="col-md-3 mb-3">
                <a href="categories.php" class="btn-modern"><i class="fas fa-tags me-2"></i>Categories</a>
            </div>
            
        </div>
    </div>

    <!-- Recent Users -->
    <div class="row mb-4">
        <div class="col-lg-6">
            <div class="glass-card">
                <h5><i class="fas fa-clock me-2"></i>Recent Users</h5>
                <?php
                try {
                    $stmt = $db->query("SELECT username, full_name, role, created_at FROM users ORDER BY created_at DESC LIMIT 5");
                    $users = $stmt->fetchAll();
                    if ($users): ?>
                        <div class="table-responsive">
                            <table class="table table-sm align-middle">
                                <thead>
                                    <tr><th>User</th><th>Role</th><th>Joined</th></tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($users as $user): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($user['full_name'] ?? $user['username']); ?></strong>
                                            <br><small class="text-muted">@<?php echo htmlspecialchars($user['username']); ?></small>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php echo $user['role'] === 'admin' ? 'danger' : ($user['role'] === 'superadmin' ? 'dark' : 'primary'); ?>">
                                                <?php echo ucfirst($user['role']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo timeAgo($user['created_at']); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-muted">No users found.</p>
                    <?php endif;
                } catch (PDOException $e) {
                    echo '<div class="alert alert-danger">Error loading users.</div>';
                } ?>
            </div>
        </div>

        <!-- Recent Documents -->
        <div class="col-lg-6">
            <div class="glass-card">
                <h5><i class="fas fa-file me-2"></i>Recent Documents</h5>
                <?php
                try {
                    $stmt = $db->query("
                        SELECT d.title, d.file_size, d.created_at, u.username
                        FROM documents d
                        JOIN users u ON d.user_id = u.id
                        ORDER BY d.created_at DESC LIMIT 5
                    ");
                    $documents = $stmt->fetchAll();
                    if ($documents): ?>
                        <div class="table-responsive">
                            <table class="table table-sm align-middle">
                                <thead>
                                    <tr><th>Document</th><th>Size</th><th>Uploaded</th></tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($documents as $doc): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($doc['title']); ?></strong>
                                            <br><small class="text-muted">by <?php echo htmlspecialchars($doc['username']); ?></small>
                                        </td>
                                        <td><?php echo formatFileSize($doc['file_size']); ?></td>
                                        <td><?php echo timeAgo($doc['created_at']); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-muted">No documents found.</p>
                    <?php endif;
                } catch (PDOException $e) {
                    echo '<div class="alert alert-warning">Documents table not found or empty.</div>';
                } ?>
            </div>
        </div>
    </div>
</div>

<?php
// Helper for file sizes
if (!function_exists('formatFileSize')) {
    function formatFileSize($bytes) {
        if ($bytes == 0) return '0 B';
        $units = ['B','KB','MB','GB','TB'];
        $i = floor(log($bytes)/log(1024));
        return round($bytes/pow(1024,$i),2).' '.$units[$i];
    }
}
?>

<?php include '../includes/footer.php'; ?>
