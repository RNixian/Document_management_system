<?php
require_once '../includes/functions.php';
requireLogin();
requireAdmin();

$user_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($user_id <= 0) {
    echo '<div class="alert alert-danger">Invalid user ID</div>';
    exit();
}

try {
    // Get user details
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    
    if (!$user) {
        echo '<div class="alert alert-danger">User not found</div>';
        exit();
    }
    
    // Get user's document count and total size
    $stmt = $db->prepare("
        SELECT 
            COUNT(*) as doc_count,
            COALESCE(SUM(file_size), 0) as total_size,
            COALESCE(SUM(downloads), 0) as total_downloads
        FROM documents 
        WHERE user_id = ?
    ");
    $stmt->execute([$user_id]);
    $stats = $stmt->fetch();
    
    // Get recent documents
    $stmt = $db->prepare("
        SELECT title, file_size, created_at, downloads 
        FROM documents 
        WHERE user_id = ? 
        ORDER BY created_at DESC 
        LIMIT 5
    ");
    $stmt->execute([$user_id]);
    $recent_docs = $stmt->fetchAll();
    
} catch (PDOException $e) {
    echo '<div class="alert alert-danger">Database error: ' . $e->getMessage() . '</div>';
    exit();
}
?>

<div class="row">
    <!-- User Information -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">
                    <i class="fas fa-user me-2"></i>User Information
                </h6>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-4"><strong>Username:</strong></div>
                    <div class="col-8"><?php echo htmlspecialchars($user['username']); ?></div>
                </div>
                <div class="row mb-3">
                    <div class="col-4"><strong>Email:</strong></div>
                    <div class="col-8"><?php echo htmlspecialchars($user['email']); ?></div>
                </div>
                <div class="row mb-3">
                    <div class="col-4"><strong>Full Name:</strong></div>
                    <div class="col-8"><?php echo htmlspecialchars($user['full_name'] ?? 'Not provided'); ?></div>
                </div>
                <div class="row mb-3">
                    <div class="col-4"><strong>Role:</strong></div>
                    <div class="col-8">
                        <span class="badge bg-<?php echo $user['role'] === 'admin' ? 'danger' : 'primary'; ?>">
                            <i class="fas fa-<?php echo $user['role'] === 'admin' ? 'user-shield' : 'user'; ?> me-1"></i>
                            <?php echo ucfirst($user['role']); ?>
                        </span>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-4"><strong>Status:</strong></div>
                    <div class="col-8">
                        <span class="badge bg-<?php echo $user['status'] === 'active' ? 'success' : 'secondary'; ?>">
                            <i class="fas fa-<?php echo $user['status'] === 'active' ? 'check-circle' : 'times-circle'; ?> me-1"></i>
                            <?php echo ucfirst($user['status']); ?>
                        </span>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-4"><strong>Joined:</strong></div>
                    <div class="col-8"><?php echo date('F j, Y g:i A', strtotime($user['created_at'])); ?></div>
                </div>
                <div class="row mb-3">
                    <div class="col-4"><strong>Last Login:</strong></div>
                    <div class="col-8">
                        <?php 
                        if ($user['last_login']) {
                            echo date('F j, Y g:i A', strtotime($user['last_login']));
                            echo '<br><small class="text-muted">(' . timeAgo($user['last_login']) . ')</small>';
                        } else {
                            echo '<span class="text-muted">Never logged in</span>';
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- User Statistics -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">
                    <i class="fas fa-chart-bar me-2"></i>User Statistics
                </h6>
            </div>
            <div class="card-body">
                <div class="row text-center mb-4">
                    <div class="col-4">
                        <div class="stat-box">
                            <div class="stat-number text-primary"><?php echo number_format($stats['doc_count']); ?></div>
                            <div class="stat-label">Documents</div>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="stat-box">
                            <div class="stat-number text-success"><?php echo formatFileSize($stats['total_size']); ?></div>
                            <div class="stat-label">Storage Used</div>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="stat-box">
                            <div class="stat-number text-info"><?php echo number_format($stats['total_downloads']); ?></div>
                            <div class="stat-label">Downloads</div>
                        </div>
                    </div>
                </div>
                
                <!-- Recent Documents -->
                <h6 class="mb-3">
                    <i class="fas fa-file-alt me-2"></i>Recent Documents
                </h6>
                <?php if (empty($recent_docs)): ?>
                    <p class="text-muted">No documents uploaded yet</p>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($recent_docs as $doc): ?>
                            <div class="list-group-item px-0 py-2">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <div class="fw-medium"><?php echo htmlspecialchars($doc['title']); ?></div>
                                        <small class="text-muted">
                                            <?php echo formatFileSize($doc['file_size']); ?> • 
                                            <?php echo date('M j, Y', strtotime($doc['created_at'])); ?>
                                        </small>
                                    </div>
                                    <small class="text-muted">
                                        <?php echo number_format($doc['downloads']); ?> downloads
                                    </small>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="row mt-3">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">
                    <i class="fas fa-bolt me-2"></i>Quick Actions
                </h6>
            </div>
            <div class="card-body">
                <div class="btn-group" role="group">
                    <?php if ($user['id'] !== $_SESSION['user_id']): ?>
                        <!-- Toggle Status -->
                        <form method="POST" action="users.php" style="display: inline;">
                            <input type="hidden" name="action" value="toggle_status">
                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                            <input type="hidden" name="status" value="<?php echo $user['status']; ?>">
                            <button type="submit" class="btn btn-<?php echo $user['status'] === 'active' ? 'warning' : 'success'; ?>"
                                    onclick="return confirm('Are you sure you want to <?php echo $user['status'] === 'active' ? 'deactivate' : 'activate'; ?> this user?')">
                                <i class="fas fa-<?php echo $user['status'] === 'active' ? 'user-slash' : 'user-check'; ?> me-2"></i>
                                <?php echo $user['status'] === 'active' ? 'Deactivate' : 'Activate'; ?> User
                            </button>
                        </form>
                        
                        <!-- Change Role -->
                        <form method="POST" action="users.php" style="display: inline;">
                            <input type="hidden" name="action" value="change_role">
                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                            <input type="hidden" name="role" value="<?php echo $user['role'] === 'admin' ? 'user' : 'admin'; ?>">
                            <button type="submit" class="btn btn-info ms-2"
                                    onclick="return confirm('Are you sure you want to change this user\'s role to <?php echo $user['role'] === 'admin' ? 'User' : 'Admin'; ?>?')">
                                <i class="fas fa-user-cog me-2"></i>
                                Make <?php echo $user['role'] === 'admin' ? 'User' : 'Admin'; ?>
                            </button>
                        </form>
                        
                      
                    
                    <?php else: ?>
                        <div class="alert alert-info mb-0">
                            <i class="fas fa-info-circle me-2"></i>
                                                     This is your own account. You cannot modify your own status or role.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.stat-box {
    padding: 1rem 0;
}

.stat-number {
    font-size: 1.5rem;
    font-weight: bold;
    margin-bottom: 0.25rem;
}

.stat-label {
    font-size: 0.875rem;
    color: #6c757d;
}

.list-group-item {
    border: none;
    border-bottom: 1px solid #dee2e6;
}

.list-group-item:last-child {
    border-bottom: none;
}
</style>
