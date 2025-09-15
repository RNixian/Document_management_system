<?php
$page_title = 'Admin Dashboard';

// Include functions (which includes database)
require_once '../includes/functions.php';

requireLogin();
requireSuperAdmin();

// Make sure $db is available globally
global $db;

require_once '../includes/superadmin_header.php';
?>

<!-- Animated Background -->
<style>
    body {
        background: #1E1E2E;
        min-height: 100vh;
        overflow-x: hidden;
        font-family: 'Segoe UI', sans-serif;
    }
    /* .animated-bg {
        position: fixed;
        inset: 0;
        background: rgba(245, 245, 250, 0.92);
        background-size: 400% 400%;
        z-index: -1;
    } */

    .glass-card {
        background: rgba(30,34,54,0.92);
        border-radius: 1.25rem;
        box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.18);
        backdrop-filter: blur(12px);
        border: 1.5px solid #3B82F6;
        padding: 1.5rem;
        color: white;
        transition: transform 0.2s, box-shadow 0.2s;
        margin-bottom: 1.5rem;
    }
    .glass-card:hover {
        transform: translateY(-4px) scale(1.02);
        box-shadow: 0 8px 32px 0 rgba(53,92,253,0.18);
    }

    /* Headers */
    h1, h2, h5, h6 {
        color: #fff;
    }
    .glass-header {
        font-size: 1.3rem;
        font-weight: 700;
        margin-bottom: 1rem;
        color: #6e8efb;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    /* Stats Cards */
    .stats-card {
        text-align: center;
        padding: 1.25rem;
        border-radius: 1rem;
        background:#545454;
        transition: transform 0.2s;
        color: #f1f1f1ff;
        background:  rgba(38, 49, 95, 0.92);
    
    }
    .stats-card:hover {
        transform: translateY(-3px);
    }
    .stats-icon {
        font-size: 2rem;
        margin-bottom: 0.5rem;
        color: #6e8efb;
    }
    .stats-number {
        font-size: 1.6rem;
        font-weight: 700;
    }
    .stats-label {
        font-size: 0.95rem;
        color: #f5f5f5ff;
    }

    /* Buttons */
    .btn-primary, .btn-success, .btn-info, .btn-warning {
        border-radius: 0.75rem;
        padding: 0.7rem 1.2rem;
        font-weight: 600;
        border: none;
        transition: transform 0.2s, box-shadow 0.2s;
        width: 100%;
    }
    .btn-primary { background: linear-gradient(90deg, #6e8efb 0%, #355cfd 100%); color: #fff; }
    .btn-success { background: linear-gradient(90deg, #6e8efb 0%, #355cfd 100%); color: #fff; }
    .btn-info { background: linear-gradient(90deg, #6e8efb 0%, #355cfd 100%); color: #fff; }
    .btn-warning { background: linear-gradient(90deg, #6e8efb 0%, #355cfd 100%); color: #fff; }

    .btn-primary:hover, .btn-success:hover, .btn-info:hover, .btn-warning:hover {
        transform: translateY(-2px) scale(1.04);
        box-shadow: 0 4px 16px rgba(0,0,0,0.2);
    }

    /* Tables */
    table {
        color: #fff;
    }
    table thead {
        background: rgba(53,92,253,0.2);
    }
    table tbody tr:hover {
        background: rgba(53,92,253,0.1);
    }

    .badge {
        padding: 0.4em 0.6em;
        border-radius: 0.5em;
    }
</style>

<div class="animated-bg"></div>
<div class="container-fluid px-4">

    <!-- Header -->
    <!-- <div class="glass-card">
        <h1><i class="fas fa-shield-alt me-2"></i>Super Admin</h1>
        <p>Welcome, <strong><?php echo htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['username']); ?>!</strong></p>
    </div> -->

    <!-- Database Status -->
    <div class="glass-card">
             <h1><i class="fas fa-shield-alt me-2"></i>Super Admin</h1>
        <p>Welcome, <strong><?php echo htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['username']); ?>!</strong></p>
        <h5><i class="fas fa-database me-2"></i>System Status:</h5>
        <?php
        try {
            if (isset($db) && $db instanceof PDO) {
                echo "✅ Database connection successful!<br>";
                echo "✅ Database name: ptni4<br>";
                $stmt = $db->query("SHOW TABLES");
                $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
                echo "✅ Found " . count($tables) . " tables: " . implode(', ', $tables);
            } else {
                echo "❌ Database connection failed";
            }
        } catch (Exception $e) {
            echo "❌ Database error: " . $e->getMessage();
        }
        ?>
    </div>

    <!-- Stats -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="stats-card">
                <div class="stats-icon"><i class="fas fa-users"></i></div>
                <div class="stats-number">
                    <?php try { $stmt = $db->query("SELECT COUNT(*) FROM users"); echo $stmt->fetchColumn(); } catch (PDOException $e) { echo 'Error'; } ?>
                </div>
                <div class="stats-label">Total Users</div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="stats-card">
                <div class="stats-icon"><i class="fas fa-file-alt"></i></div>
                <div class="stats-number">
                    <?php try { $stmt = $db->query("SELECT COUNT(*) FROM documents"); echo $stmt->fetchColumn(); } catch (PDOException $e) { echo 'N/A'; } ?>
                </div>
                <div class="stats-label">Documents</div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="stats-card">
                <div class="stats-icon"><i class="fas fa-tags"></i></div>
                <div class="stats-number">
                    <?php try { $stmt = $db->query("SELECT COUNT(*) FROM categories"); echo $stmt->fetchColumn(); } catch (PDOException $e) { echo 'N/A'; } ?>
                </div>
                <div class="stats-label">Categories</div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="stats-card">
                <div class="stats-icon"><i class="fas fa-server"></i></div>
                <div class="stats-number"><?php echo PHP_VERSION; ?></div>
                <div class="stats-label">PHP Version</div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="glass-card">
        <h6 class="glass-header"><i class="fas fa-bolt"></i> Admin Actions</h6>
        <div class="row">
            <div class="col-md-3 mb-3"><a href="users.php" class="btn btn-primary"><i class="fas fa-users me-2"></i> Manage Users</a></div>
            <div class="col-md-3 mb-3"><a href="documents.php" class="btn btn-success"><i class="fas fa-file-alt me-2"></i> Manage Documents</a></div>
            <div class="col-md-3 mb-3"><a href="categories.php" class="btn btn-info"><i class="fas fa-tags me-2"></i> Categories</a></div>
            <div class="col-md-3 mb-3"><a href="settings.php" class="btn btn-warning"><i class="fas fa-cogs me-2"></i> Settings</a></div>
        </div>
        <div class="alert alert-success mt-3">
            <i class="fas fa-shield-check me-2"></i> <strong>Admin Access Granted:</strong> You have full system privileges.
        </div>
    </div>

    <!-- Recent Users and Documents -->
    <div class="row">
        <div class="col-lg-6">
            <div class="glass-card">
                <h6 class="glass-header"><i class="fas fa-clock"></i> Recent Users</h6>
                <?php
                try {
                    $stmt = $db->query("SELECT username, full_name, role, created_at FROM users ORDER BY created_at DESC LIMIT 5");
                    $users = $stmt->fetchAll();
                    if ($users): ?>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead><tr><th>User</th><th>Role</th><th>Joined</th></tr></thead>
                                <tbody>
                                <?php foreach ($users as $user): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($user['full_name'] ?? $user['username']); ?></strong><br><small>@<?php echo htmlspecialchars($user['username']); ?></small></td>
                                        <td><span class="badge bg-<?php echo $user['role'] === 'admin' ? 'danger' : 'primary'; ?>"><?php echo ucfirst($user['role']); ?></span></td>
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

        <div class="col-lg-6">
            <div class="glass-card">
                <h6 class="glass-header"><i class="fas fa-file"></i> Recent Documents</h6>
                <?php
                try {
                    $stmt = $db->query("
                        SELECT d.title, d.file_size, d.created_at, u.username 
                        FROM documents d 
                        JOIN users u ON d.user_id = u.id
                        ORDER BY d.created_at DESC 
                        LIMIT 5
                    ");
                    $documents = $stmt->fetchAll();
                    if ($documents): ?>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead><tr><th>Document</th><th>Size</th><th>Uploaded</th></tr></thead>
                                <tbody>
                                <?php foreach ($documents as $doc): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($doc['title']); ?></strong><br><small>by <?php echo htmlspecialchars($doc['username']); ?></small></td>
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

<?php require_once '../includes/footer.php'; ?>
