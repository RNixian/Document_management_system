<?php
$page_title = 'System Logs';
require_once '../includes/functions.php';
requireLogin();
requireSuperAdmin();

// Pagination settings
$limit = 50;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Filter settings
$filter_action = $_GET['action'] ?? '';
$filter_admin = $_GET['admin'] ?? '';
$filter_date_from = $_GET['date_from'] ?? '';
$filter_date_to = $_GET['date_to'] ?? '';
$search = $_GET['search'] ?? '';

// Build WHERE clause
$where_conditions = [];
$params = [];

if (!empty($filter_action)) {
    $where_conditions[] = "al.action = ?";
    $params[] = $filter_action;
}

if (!empty($filter_admin)) {
    $where_conditions[] = "al.admin_id = ?";
    $params[] = $filter_admin;
}

if (!empty($filter_date_from)) {
    $where_conditions[] = "DATE(al.created_at) >= ?";
    $params[] = $filter_date_from;
}

if (!empty($filter_date_to)) {
    $where_conditions[] = "DATE(al.created_at) <= ?";
    $params[] = $filter_date_to;
}

if (!empty($search)) {
    $where_conditions[] = "(al.details LIKE ? OR u.username LIKE ? OR u.full_name LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Get total count for pagination
try {
    $count_sql = "
        SELECT COUNT(*) 
        FROM admin_logs al 
        LEFT JOIN users u ON al.admin_id = u.id 
        $where_clause
    ";
    $count_stmt = $db->prepare($count_sql);
    $count_stmt->execute($params);
    $total_logs = $count_stmt->fetchColumn();
    $total_pages = ceil($total_logs / $limit);
} catch (PDOException $e) {
    $total_logs = 0;
    $total_pages = 0;
}

// Get logs
$logs = [];
try {
    $sql = "
        SELECT 
            al.*,
            u.username as admin_username,
            u.full_name as admin_name,
            tu.username as target_username,
            tu.full_name as target_name
        FROM admin_logs al
        LEFT JOIN users u ON al.admin_id = u.id
        LEFT JOIN users tu ON al.target_user_id = tu.id
        $where_clause
        ORDER BY al.created_at DESC
        LIMIT ? OFFSET ?
    ";
    
    $stmt = $db->prepare($sql);
    $stmt->execute(array_merge($params, [$limit, $offset]));
    $logs = $stmt->fetchAll();
} catch (PDOException $e) {
    setAlert('Error fetching logs: ' . $e->getMessage(), 'danger');
}

// Get unique actions for filter
$actions = [];
try {
    $stmt = $db->query("SELECT DISTINCT action FROM admin_logs ORDER BY action");
    $actions = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    // Ignore error
}

// Get admins for filter
$admins = [];
try {
    $stmt = $db->query("
        SELECT DISTINCT u.id, u.username, u.full_name 
        FROM users u 
        INNER JOIN admin_logs al ON u.id = al.admin_id 
        WHERE u.role IN ('admin', 'superadmin')
        ORDER BY u.username
    ");
    $admins = $stmt->fetchAll();
} catch (PDOException $e) {
    // Ignore error
}

require_once '../includes/superadmin_header.php';
?>

<div class="container-fluid px-4">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="admin-header">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h1 class="h3 mb-0">
                            <i class="fas fa-clipboard-list me-2"></i>System Logs
                        </h1>
                        <p class="text-white mb-0">Monitor system activities and admin actions</p>
                    </div>
                    <div>
                        <button class="btn btn-light me-2" onclick="exportLogs()">
                            <i class="fas fa-download me-2"></i>Export Logs
                        </button>
                        <a href="dashboard.php" class="btn btn-light">
                            <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php displayAlert(); ?>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">
                <i class="fas fa-filter me-2"></i>Filter Logs
            </h5>
        </div>
        <div class="card-body">
            <form method="GET" action="" class="row g-3">
                <div class="col-md-3">
                    <label for="action" class="form-label">Action</label>
                    <select class="form-select" id="action" name="action">
                        <option value="">All Actions</option>
                        <?php foreach ($actions as $action): ?>
                            <option value="<?php echo htmlspecialchars($action); ?>" 
                                    <?php echo $filter_action === $action ? 'selected' : ''; ?>>
                                <?php echo ucwords(str_replace('_', ' ', $action)); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-3">
                    <label for="admin" class="form-label">Admin</label>
                    <select class="form-select" id="admin" name="admin">
                        <option value="">All Admins</option>
                        <?php foreach ($admins as $admin): ?>
                            <option value="<?php echo $admin['id']; ?>" 
                                    <?php echo $filter_admin == $admin['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($admin['username'] . ' (' . $admin['full_name'] . ')'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-2">
                    <label for="date_from" class="form-label">From Date</label>
                    <input type="date" class="form-control" id="date_from" name="date_from" 
                           value="<?php echo htmlspecialchars($filter_date_from); ?>">
                </div>
                
                <div class="col-md-2">
                    <label for="date_to" class="form-label">To Date</label>
                    <input type="date" class="form-control" id="date_to" name="date_to" 
                           value="<?php echo htmlspecialchars($filter_date_to); ?>">
                </div>
                
                <div class="col-md-2">
                    <label for="search" class="form-label">Search</label>
                    <input type="text" class="form-control" id="search" name="search" 
                           placeholder="Search details..." value="<?php echo htmlspecialchars($search); ?>">
                </div>
                
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search me-2"></i>Filter
                    </button>
                    <a href="logs.php" class="btn btn-secondary">
                        <i class="fas fa-times me-2"></i>Clear Filters
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Logs Statistics -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card  bg-dark text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-title">Total Logs</h6>
                            <h3 class="mb-0"><?php echo number_format($total_logs); ?></h3>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-list fa-2x opacity-75"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card bg-dark text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-title">Today's Logs</h6>
                            <h3 class="mb-0">
                                <?php 
                                try {
                                    $stmt = $db->query("SELECT COUNT(*) FROM admin_logs WHERE DATE(created_at) = CURDATE()");
                                    echo number_format($stmt->fetchColumn());
                                } catch (PDOException $e) {
                                    echo '0';
                                }
                                ?>
                            </h3>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-calendar-day fa-2x opacity-75"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card  bg-dark text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-title">This Week</h6>
                            <h3 class="mb-0">
                                <?php 
                                try {
                                    $stmt = $db->query("SELECT COUNT(*) FROM admin_logs WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
                                    echo number_format($stmt->fetchColumn());
                                } catch (PDOException $e) {
                                    echo '0';
                                }
                                ?>
                            </h3>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-calendar-week fa-2x opacity-75"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card  bg-dark text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="card-title">Active Admins</h6>
                            <h3 class="mb-0">
                                <?php 
                                try {
                                    $stmt = $db->query("
                                        SELECT COUNT(DISTINCT admin_id) 
                                        FROM admin_logs 
                                        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
                                    ");
                                    echo number_format($stmt->fetchColumn());
                                } catch (PDOException $e) {
                                    echo '0';
                                }
                                ?>
                            </h3>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-users fa-2x opacity-75"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Logs Table -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0 ">
                <i class="fas fa-table me-2"></i>Activity Logs
                <small class="text-white">(<?php echo number_format($total_logs); ?> total entries)</small>
            </h5>
        </div>
        <div class="card-body p-0">
            <?php if (empty($logs)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">No logs found</h5>
                    <p class="text-muted">No activity logs match your current filters.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Date/Time</th>
                                <th>Admin</th>
                                <th>Action</th>
                                <th>Details</th>
                                <th>Target User</th>
                                <th>IP Address</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($logs as $log): ?>
                                <tr>
                                    <td>
                                        <div class="fw-bold">
                                            <?php echo date('M j, Y', strtotime($log['created_at'])); ?>
                                        </div>
                                        <small class="text-muted">
                                            <?php echo date('h:i:s A', strtotime($log['created_at'])); ?>
                                        </small>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-sm me-2">
                                                <div class="avatar-title bg-primary rounded-circle">
                                                    <?php echo strtoupper(substr($log['admin_name'] ?? $log['admin_username'] ?? 'U', 0, 1)); ?>
                                                </div>
                                            </div>
                                            <div>
                                                <div class="fw-bold"><?php echo htmlspecialchars($log['admin_username'] ?? 'Unknown'); ?></div>
                                                <small class="text-muted"><?php echo htmlspecialchars($log['admin_name'] ?? ''); ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php echo getActionBadgeColor($log['action']); ?>">
                                            <?php echo ucwords(str_replace('_', ' ', $log['action'])); ?>
                                        </span>
                                    </td>
                                                      <td>
                                        <div class="log-details">
                                            <?php if (strlen($log['details']) > 100): ?>
                                                <span class="details-short">
                                                    <?php echo htmlspecialchars(substr($log['details'], 0, 100)); ?>...
                                                </span>
                                                <span class="details-full" style="display: none;">
                                                    <?php echo htmlspecialchars($log['details']); ?>
                                                </span>
                                                <br>
                                                <button class="btn btn-link btn-sm p-0 toggle-details" onclick="toggleDetails(this)">
                                                    Show more
                                                </button>
                                            <?php else: ?>
                                                <?php echo htmlspecialchars($log['details'] ?? ''); ?>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if ($log['target_user_id']): ?>
                                            <div>
                                                <div class="fw-bold"><?php echo htmlspecialchars($log['target_username'] ?? 'Unknown'); ?></div>
                                                <small class="text-muted"><?php echo htmlspecialchars($log['target_name'] ?? ''); ?></small>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <code><?php echo htmlspecialchars($log['ip_address'] ?? 'Unknown'); ?></code>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <button class="btn btn-outline-primary" onclick="viewLogDetails(<?php echo $log['id']; ?>)" 
                                                    title="View Details">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <?php if ($_SESSION['role'] === 'superadmin'): ?>
                                                <button class="btn btn-outline-danger" onclick="deleteLog(<?php echo $log['id']; ?>)" 
                                                        title="Delete Log">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
        
        <?php if ($total_pages > 1): ?>
            <div class="card-footer">
                <nav aria-label="Logs pagination">
                    <ul class="pagination justify-content-center mb-0">
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>">
                                    <i class="fas fa-chevron-left"></i>
                                </a>
                            </li>
                        <?php endif; ?>
                        
                        <?php
                        $start_page = max(1, $page - 2);
                        $end_page = min($total_pages, $page + 2);
                        
                        for ($i = $start_page; $i <= $end_page; $i++):
                        ?>
                            <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>">
                                    <?php echo $i; ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                        
                        <?php if ($page < $total_pages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>">
                                    <i class="fas fa-chevron-right"></i>
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
                
                <div class="text-center mt-2">
                    <small class="text-muted">
                        Showing <?php echo number_format($offset + 1); ?> to <?php echo number_format(min($offset + $limit, $total_logs)); ?> 
                        of <?php echo number_format($total_logs); ?> entries
                    </small>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Log Details Modal -->
<div class="modal fade" id="logDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Log Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="logDetailsContent">
                    <div class="text-center">
                        <div class="spinner-border" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
// Toggle log details
function toggleDetails(button) {
    const container = button.closest('.log-details');
    const shortText = container.querySelector('.details-short');
    const fullText = container.querySelector('.details-full');
    
    if (fullText.style.display === 'none') {
        shortText.style.display = 'none';
        fullText.style.display = 'block';
        button.textContent = 'Show less';
    } else {
        shortText.style.display = 'block';
        fullText.style.display = 'none';
        button.textContent = 'Show more';
    }
}

// View log details in modal
function viewLogDetails(logId) {
    const modal = new bootstrap.Modal(document.getElementById('logDetailsModal'));
    const content = document.getElementById('logDetailsContent');
    
    // Show loading
    content.innerHTML = `
        <div class="text-center">
            <div class="spinner-border" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>
    `;
    
    modal.show();
    
    // Fetch log details
    fetch(`ajax/get_log_details.php?id=${logId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                content.innerHTML = formatLogDetails(data.log);
            } else {
                content.innerHTML = `<div class="alert alert-danger">Error: ${data.message}</div>`;
            }
        })
        .catch(error => {
            content.innerHTML = `<div class="alert alert-danger">Error loading log details</div>`;
        });
}

// Format log details for modal
function formatLogDetails(log) {
    return `
        <div class="row">
            <div class="col-md-6">
                <h6>Basic Information</h6>
                <table class="table table-sm">
                    <tr>
                        <td><strong>ID:</strong></td>
                        <td>${log.id}</td>
                    </tr>
                    <tr>
                        <td><strong>Date/Time:</strong></td>
                        <td>${new Date(log.created_at).toLocaleString()}</td>
                    </tr>
                    <tr>
                        <td><strong>Action:</strong></td>
                        <td><span class="badge bg-primary">${log.action.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase())}</span></td>
                    </tr>
                    <tr>
                        <td><strong>IP Address:</strong></td>
                        <td><code>${log.ip_address || 'Unknown'}</code></td>
                    </tr>
                </table>
            </div>
            <div class="col-md-6">
                <h6>User Information</h6>
                <table class="table table-sm">
                    <tr>
                        <td><strong>Admin:</strong></td>
                        <td>${log.admin_username || 'Unknown'}<br><small class="text-muted">${log.admin_name || ''}</small></td>
                    </tr>
                    <tr>
                        <td><strong>Target User:</strong></td>
                        <td>${log.target_username ? `${log.target_username}<br><small class="text-muted">${log.target_name || ''}</small>` : '<span class="text-muted">None</span>'}</td>
                    </tr>
                </table>
            </div>
        </div>
        <div class="row mt-3">
            <div class="col-12">
                <h6>Details</h6>
                <div class="bg-light p-3 rounded">
                    <pre class="mb-0">${log.details || 'No additional details'}</pre>
                </div>
            </div>
        </div>
    `;
}

// Delete log
function deleteLog(logId) {
    if (confirm('Are you sure you want to delete this log entry? This action cannot be undone.')) {
        fetch('ajax/delete_log.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ id: logId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error deleting log: ' + data.message);
            }
        })
        .catch(error => {
            alert('Error deleting log');
        });
    }
}

// Export logs
function exportLogs() {
    const params = new URLSearchParams(window.location.search);
    params.set('export', '1');
    window.location.href = 'ajax/export_logs.php?' + params.toString();
}

// Auto-refresh logs every 30 seconds
setInterval(() => {
    if (document.visibilityState === 'visible') {
        const currentUrl = new URL(window.location);
        currentUrl.searchParams.set('auto_refresh', '1');
        
        fetch(currentUrl.toString())
            .then(response => response.text())
            .then(html => {
                // Update only the logs table content
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                const newTable = doc.querySelector('.table-responsive');
                const currentTable = document.querySelector('.table-responsive');
                
                if (newTable && currentTable) {
                    currentTable.innerHTML = newTable.innerHTML;
                }
            })
            .catch(error => {
                console.log('Auto-refresh failed:', error);
            });
    }
}, 30000);
</script>

<style>
.admin-header {
    background: linear-gradient(90deg,rgb(43, 46, 63) 0%,rgb(6, 30, 187) 100%) !important; /* Change these colors for your preferred blue or any other color */
    color: white;
    padding: 2rem;
    border-radius: 10px;
    margin-bottom: 2rem;
}
body {
     background: rgba(243, 244, 247, 1);
}
.card {
    border: none;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    border-radius: 10px;
    margin-bottom: 1.5rem;
}

.card-header {
    background: rgba(9, 18, 66, 1);
    border-bottom: 1px solid #e9ecef;
    border-radius: 10px 10px 0 0 !important;
    padding: 1rem 1.5rem;
    color: white;
}

.avatar-sm {
    width: 32px;
    height: 32px;
}

.avatar-title {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.875rem;
    font-weight: 600;
}

.log-details {
    max-width: 300px;
}

.toggle-details {
    color: #667eea;
    text-decoration: none;
    font-size: 0.875rem;
}

.toggle-details:hover {
    text-decoration: underline;
}

.table th {
   border-top: none;
    font-weight: 600;
    font-size: 0.875rem;
   background: linear-gradient(90deg, #0b257cff 0%, #010b33ff 100%);
    color: white;
}

.table td {
    vertical-align: middle;
    font-size: 0.875rem;
}

.badge {
    font-size: 0.75rem;
    padding: 0.375rem 0.75rem;
}

.btn-group-sm > .btn {
    padding: 0.25rem 0.5rem;
    font-size: 0.875rem;
}

.pagination .page-link {
    border-radius: 6px;
    margin: 0 2px;
    border: 1px solid #e1e5e9;
    color: #667eea;
}

.pagination .page-item.active .page-link {
    background-color: #667eea;
    border-color: #667eea;
}

.pagination .page-link:hover {
    background-color: #f8f9fa;
    border-color: #667eea;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .admin-header {
        padding: 1.5rem;
        text-align: center;
    }
    
    .admin-header .d-flex {
        flex-direction: column;
        gap: 1rem;
    }
    
    .table-responsive {
        font-size: 0.875rem;
    }
    
    .log-details {
        max-width: 200px;
    }
    
    .btn-group-sm > .btn {
        padding: 0.125rem 0.25rem;
        font-size: 0.75rem;
    }
}

/* Loading animation */
@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.spinner-border {
    animation: spin 1s linear infinite;
}
</style>

<?php
// Helper function for action badge colors
function getActionBadgeColor($action) {
    $colors = [
        'login' => 'success',
        'logout' => 'secondary',
        'create' => 'primary',
        'update' => 'warning',
        'delete' => 'danger',
        'approve' => 'success',
        'reject' => 'danger',
        'upload' => 'info',
        'download' => 'info',
        'backup_database' => 'info',
        'optimize_database' => 'warning',
        'clear_cache' => 'secondary',
        'settings_update' => 'primary',
        'user_management' => 'warning',
        'file_management' => 'info'
    ];
    
    return $colors[$action] ?? 'secondary';
}

require_once '../includes/footer.php';
?>
