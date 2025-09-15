<?php
$page_title = 'Manage Users';
require_once '../includes/functions.php';
requireLogin();
requireAdmin();

// Check if division_id column exists in users table
$division_column_exists = false;
try {
    $stmt = $db->query("SHOW COLUMNS FROM users LIKE 'division_id'");
    $division_column_exists = $stmt->rowCount() > 0;
} catch (PDOException $e) {
    // Column doesn't exist, handled gracefully
}

// Handle user actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'toggle_status':
                $user_id = (int)$_POST['user_id'];
                $new_status = $_POST['status'] === 'active' ? 'inactive' : 'active';
                
                if ($user_id !== $_SESSION['user_id']) { // Prevent self-deactivation
                    try {
                        $stmt = $db->prepare("UPDATE users SET status = ? WHERE id = ? AND role = 'user'");
                        $stmt->execute([$new_status, $user_id]);
                        setAlert('User status updated successfully', 'success');
                    } catch (PDOException $e) {
                        setAlert('Error updating user status: ' . $e->getMessage(), 'danger');
                    }
                } else {
                    setAlert('You cannot change your own status', 'warning');
                }
                break;

                case 'assign_division':
                    $user_id = (int)($_POST['user_id'] ?? 0);
                    $division_ids = $_POST['division_ids'] ?? []; // array of selected divisions
                
                    if ($user_id > 0) {
                        try {
                            // Remove any existing divisions for this user
                            $stmt = $db->prepare("DELETE FROM user_divisions WHERE user_id = ?");
                            $stmt->execute([$user_id]);
                
                            // Insert new divisions if any selected
                            if (!empty($division_ids)) {
                                $stmt = $db->prepare("INSERT INTO user_divisions (user_id, division_id) VALUES (?, ?)");
                                foreach ($division_ids as $div_id) {
                                    $stmt->execute([$user_id, (int)$div_id]);
                                }
                            }
                
                            setAlert('Division(s) assigned successfully', 'success');
                        } catch (PDOException $e) {
                            setAlert('Error assigning division(s): ' . $e->getMessage(), 'danger');
                        }
                    } else {
                        setAlert('Invalid user selected', 'danger');
                    }
                    break;
                
                    case 'edit_user':
                        $user_id = (int)$_POST['user_id'];
                        $username = sanitize($_POST['username']);
                        $email = sanitize($_POST['email']);
                        $full_name = sanitize($_POST['full_name']);
                        $status = sanitize($_POST['status']);
                        $password = $_POST['password'] ?? '';
                    
                        if ($user_id > 0 && !empty($username) && !empty($email)) {
                            try {
                                // Check duplicates
                                $stmt = $db->prepare("SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ? AND role = 'user'");
                                $stmt->execute([$username, $email, $user_id]);
                    
                                if ($stmt->fetch()) {
                                    setAlert('Username or email already exists for another user', 'danger');
                                } else {
                                    // Update user
                                    if (!empty($password)) {
                                        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                                        $stmt = $db->prepare("UPDATE users SET username = ?, email = ?, full_name = ?, status = ?, password = ? WHERE id = ? AND role = 'user'");
                                        $stmt->execute([$username, $email, $full_name, $status, $hashed_password, $user_id]);
                                    } else {
                                        $stmt = $db->prepare("UPDATE users SET username = ?, email = ?, full_name = ?, status = ? WHERE id = ? AND role = 'user'");
                                        $stmt->execute([$username, $email, $full_name, $status, $user_id]);
                                    }
                    
                                    setAlert('User updated successfully', 'success');
                                }
                            } catch (PDOException $e) {
                                setAlert('Error updating user: ' . $e->getMessage(), 'danger');
                            }
                        } else {
                            setAlert('Please fill in all required fields', 'danger');
                        }
                        break;
                    
                    
                
                        case 'add_user':
                            $username = sanitize($_POST['username']);
                            $email_prefix = sanitize($_POST['email_prefix']);
                            $email = $email_prefix . '@ptni.gov.ph';
                            $full_name = sanitize($_POST['full_name']);
                            $password = $_POST['password'];
                            $status = sanitize($_POST['status']);
                            $division_ids = $_POST['division_ids'] ?? []; // array of selected divisions
                        
                            if (!empty($username) && !empty($email) && !empty($password)) {
                                try {
                                    // Check duplicates
                                    $stmt = $db->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
                                    $stmt->execute([$username, $email]);
                        
                                    if ($stmt->fetch()) {
                                        setAlert('Username or email already exists', 'danger');
                                    } else {
                                        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                        
                                        // Insert user
                                        $stmt = $db->prepare("INSERT INTO users (username, email, full_name, password, role, status) VALUES (?, ?, ?, ?, 'user', ?)");
                                        $stmt->execute([$username, $email, $full_name, $hashed_password, $status]);
                        
                                        $user_id = $db->lastInsertId();
                        
                                        // Insert divisions
                                        $stmt = $db->prepare("INSERT INTO user_divisions (user_id, division_id) VALUES (?, ?)");
                                        foreach ($division_ids as $div_id) {
                                            $stmt->execute([$user_id, (int)$div_id]);
                                        }
                        
                                        setAlert('User added successfully', 'success');
                                    }
                                } catch (PDOException $e) {
                                    setAlert('Error adding user: ' . $e->getMessage(), 'danger');
                                }
                            } else {
                                setAlert('Please fill in all required fields', 'danger');
                            }
                            break;
                        
        }
        
        // Redirect to prevent resubmission
        header('Location: users.php');
        exit();
    }
}

// Get all divisions (if table exists) 
$divisions = []; 
try { 
    $divisions_stmt = $db->query("SELECT * FROM division ORDER BY name ASC"); 
    $divisions = $divisions_stmt->fetchAll(); 
} catch (PDOException $e) { 
    // Division table might not exist 
}

// Get logged-in admin's divisions
$admin_divisions = [];
try {
    $stmt = $db->prepare("SELECT division_id FROM user_divisions WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $admin_divisions = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    $admin_divisions = [];
}

// Search & filters
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? sanitize($_GET['status']) : '';
$sort = isset($_GET['sort']) ? sanitize($_GET['sort']) : 'created_at';
$order = isset($_GET['order']) && $_GET['order'] === 'asc' ? 'ASC' : 'DESC';

// Pagination
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

// Build WHERE conditions
$where_conditions = ["u.role = 'user'"];
$params = [];

// Search filter
if (!empty($search)) {
    $where_conditions[] = "(u.username LIKE ? OR u.email LIKE ? OR u.full_name LIKE ?)";
    $search_param = "%$search%";
    $params = array_merge($params, [$search_param, $search_param, $search_param]);
}

// Status filter
if (!empty($status_filter)) {
    $where_conditions[] = "u.status = ?";
    $params[] = $status_filter;
}

// Division filter: only show users in admin's divisions
if (!empty($admin_divisions)) {
    $placeholders = implode(',', array_fill(0, count($admin_divisions), '?'));
    $where_conditions[] = "ud.division_id IN ($placeholders)";
    $params = array_merge($params, $admin_divisions);
} else {
    $where_conditions[] = "1 = 0"; // Admin has no division → show nothing
}

$where_clause = "WHERE " . implode(' AND ', $where_conditions);

// Valid sorts
$valid_sorts = ['username', 'email', 'full_name', 'status', 'created_at', 'last_login', 'division_names'];
if (!in_array($sort, $valid_sorts)) {
    $sort = 'created_at';
}
$sort_column = $sort === 'division_names' ? 'division_names' : 'u.' . $sort;

try {
    // Count total users
    $count_sql = "
        SELECT COUNT(DISTINCT u.id) as total
        FROM users u
        LEFT JOIN user_divisions ud ON u.id = ud.user_id
        $where_clause
    ";
    $stmt = $db->prepare($count_sql);
    $stmt->execute($params);
    $total_users = $stmt->fetch()['total'];

    // Fetch users with divisions
    $sql = "
        SELECT u.*, GROUP_CONCAT(d.name SEPARATOR ', ') AS division_names
        FROM users u
        LEFT JOIN user_divisions ud ON u.id = ud.user_id
        LEFT JOIN division d ON ud.division_id = d.id
        $where_clause
        GROUP BY u.id
        ORDER BY $sort_column $order
        LIMIT $per_page OFFSET $offset
    ";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Stats
    $stats_sql = "
        SELECT 
            COUNT(*) as total_users,
            SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_count,
            SUM(CASE WHEN status = 'inactive' THEN 1 ELSE 0 END) as inactive_count
        FROM users
        WHERE role = 'user'
    ";
    $stmt = $db->query($stats_sql);
    $stats = $stmt->fetch();

    // New users in last month
    $new_users_sql = "
        SELECT COUNT(*) 
        FROM users 
        WHERE role = 'user' 
        AND created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)
    ";
    $stmt = $db->query($new_users_sql);
    $new_users_count = $stmt->fetchColumn();

    $total_pages = ceil($total_users / $per_page);

} catch (PDOException $e) {
    setAlert('Error loading users: ' . $e->getMessage(), 'danger');
    $users = [];
    $total_users = 0;
    $total_pages = 0;
    $stats = ['total_users' => 0, 'active_count' => 0, 'inactive_count' => 0];
    $new_users_count = 0;
}

// Fetch all divisions for these users
$user_divisions = [];
$user_ids = array_column($users, 'id');
if (!empty($user_ids)) {
    $placeholders = implode(',', array_fill(0, count($user_ids), '?'));
    $stmt = $db->prepare("
        SELECT ud.user_id, d.id AS division_id, d.name AS division_name
        FROM user_divisions ud
        JOIN division d ON ud.division_id = d.id
        WHERE ud.user_id IN ($placeholders)
    ");
    $stmt->execute($user_ids);
    $all_divs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($all_divs as $row) {
        $user_divisions[$row['user_id']][] = [
            'division_id' => $row['division_id'],
            'division_name' => $row['division_name']
        ];
    }
}

require_once '../includes/admin_header.php';
?>

<div class="container-fluid px-4">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0" style="color: white; background: linear-gradient(90deg,rgb(43, 46, 63) 0%,rgb(6, 30, 187) 100%)">
                <div class="card-body py-4">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h1 class="display-6 fw-bold mb-2">
                                <i class="fas fa-users me-3"></i>User Management
                            </h1>
                            <p class="lead mb-0">
                                Manage regular user accounts and permissions
                            </p>
                        </div>
                        <div class="col-md-4 text-end">
                            <button class="btn btn-light btn-lg" data-bs-toggle="modal" data-bs-target="#addUserModal">
                                <i class="fas fa-plus me-2"></i>Add New User
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase text-black mb-1">
                                Total Users
                            </div>
                            <div class="h5 mb-0 font-weight-bold" style="color:black;"><?php echo number_format($stats['total_users']); ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-users fa-2x text-black-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase text-black mb-1">
                                Active Users
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-black-800"><?php echo number_format($stats['active_count']); ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-user-check fa-2x text-black-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase text-black mb-1">
                                Inactive Users
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-black-800"><?php echo number_format($stats['inactive_count']); ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-user-slash fa-2x text-black-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1 text-black">
                                New This Month
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-black-800"><?php echo number_format($new_users_count); ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-user-plus fa-2x text-black-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Search and Filter -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header py-3" style="background-color: #12204bff; color: white;">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-search me-2"></i>Search & Filter
                    </h6>
                </div>
                <div class="card-body">
                    <form method="GET" action="" class="row g-3">
                        <!-- Search -->
                        <div class="col-md-4">
                            <label class="form-label">Search Users</label>
                            <input type="text" class="form-control" name="search" 
                            value="<?php echo htmlspecialchars($search); ?>" 
                            placeholder="Username, email, name...">
                        </div>
                        
                        <!-- Status Filter -->
                        <div class="col-md-2">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="status">
                                <option value="">All Status</option>
                                <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>Active</option>
                                <option value="inactive" <?php echo $status_filter === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                            </select>
                        </div>

                        <!-- Sort By -->
                        <div class="col-md-2">
                            <label class="form-label">Sort By</label>
                            <select class="form-select" name="sort">
                                <option value="created_at" <?php echo $sort === 'created_at' ? 'selected' : ''; ?>>Date Joined</option>
                                <option value="username" <?php echo $sort === 'username' ? 'selected' : ''; ?>>Username</option>
                                <option value="email" <?php echo $sort === 'email' ? 'selected' : ''; ?>>Email</option>
                                <option value="last_login" <?php echo $sort === 'last_login' ? 'selected' : ''; ?>>Last Login</option>
                            </select>
                        </div>
                        
                        <!-- Order -->
                        <div class="col-md-2">
                            <label class="form-label">Order</label>
                            <select class="form-select" name="order">
                                <option value="desc" <?php echo $order === 'DESC' ? 'selected' : ''; ?>>Descending</option>
                                <option value="asc" <?php echo $order === 'ASC' ? 'selected' : ''; ?>>Ascending</option>
                            </select>
                        </div>
                        
                        <!-- Filter Button -->
                        <div class="col-md-1">
                            <label class="form-label">&nbsp;</label>
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Results Info -->
    <div class="row mb-3">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold text-primary">
                    <?php echo number_format($total_users); ?> User<?php echo $total_users !== 1 ? 's' : ''; ?>
                    <?php if (!empty($search)): ?>
                        for "<?php echo htmlspecialchars($search); ?>"
                    <?php endif; ?>
                </h6>
                <?php if (!empty($search) || !empty($status_filter)): ?>
                    <a href="users.php" class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-times me-1"></i>Clear Filters
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Users Table -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header py-3" style="background-color: #12204bff; color: white;">
                    <h6 class="m-0 font-weight-bold text-primary">Users List</h6>
                </div>
                <div class="card-body">
                    <?php if (empty($users)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-users" style="font-size: 5rem; color: #e9ecef;"></i>
                            <h3 class="mt-4 text-muted">No users found</h3>
                            <?php if (!empty($search) || !empty($status_filter)): ?>
                                <p class="text-muted">Try adjusting your search criteria or filters.</p>
                            <?php else: ?>
                                <p class="text-muted">No regular users in the system yet.</p>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>User</th>
                                    <th>Email</th>
                                    <th>Division/Unit</th>
                                    <th>Status</th>
                                    <th>Joined</th>
                                    <th>Last Login</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
<?php foreach ($users as $user): ?>
    <tr>
        <td>
            <div class="d-flex align-items-center">
                <div class="avatar me-3">
                    <div class="avatar-circle">
                        <?php echo strtoupper(substr($user['full_name'] ?? $user['username'], 0, 2)); ?>
                    </div>
                </div>
                <div>
                    <div class="font-weight-bold"><?php echo htmlspecialchars($user['full_name'] ?? $user['username']); ?></div>
                    <small class="text-muted">@<?php echo htmlspecialchars($user['username']); ?></small>
                </div>
            </div>
        </td>
        <td><?php echo htmlspecialchars($user['email']); ?></td>

        <!-- Division Column -->
        <td>
    <?php if (!empty($user_divisions[$user['id']])): ?>
        <?php foreach ($user_divisions[$user['id']] as $div): ?>
            <span class="badge bg-primary text-white me-1">
                <i class="fas fa-building me-1"></i>
                <?php echo htmlspecialchars($div['division_name']); ?>
            </span>
        <?php endforeach; ?>
    <?php else: ?>
        <span class="badge bg-secondary">
            <i class="fas fa-question me-1"></i>No Division
        </span>
    <?php endif; ?>
</td>

        <td>
            <span class="badge bg-<?php echo $user['status'] === 'active' ? 'success' : 'secondary'; ?>">
                <i class="fas fa-<?php echo $user['status'] === 'active' ? 'check-circle' : 'times-circle'; ?> me-1"></i>
                <?php echo ucfirst($user['status']); ?>
            </span>
        </td>

        <td>
            <small><?php echo date('M j, Y', strtotime($user['created_at'])); ?></small>
        </td>

        <td>
            <small>
                <?php
                if ($user['last_login']) {
                    echo timeAgo($user['last_login']);
                } else {
                    echo '<span class="text-muted">Never</span>';
                }
                ?>
            </small>
        </td>

        <td>
            <!-- First row: View, Edit, Toggle -->
            <div class="btn-group mb-1 w-30" role="group">
                <button type="button" class="btn btn-sm btn-outline-info" onclick="viewUser(<?php echo $user['id']; ?>)" title="View Details">
                    <i class="fas fa-eye"></i>
                </button>

                <button type="button" class="btn btn-sm btn-outline-primary" onclick="editUser(<?php echo htmlspecialchars(json_encode($user)); ?>)" title="Edit User">
                    <i class="fas fa-edit"></i>
                </button>

                <form method="POST" style="display:inline;">
                    <input type="hidden" name="action" value="toggle_status">
                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                    <input type="hidden" name="status" value="<?php echo $user['status']; ?>">
                    <button type="submit" class="btn btn-sm btn-outline-<?php echo $user['status'] === 'active' ? 'warning' : 'success'; ?>"
                        title="<?php echo $user['status'] === 'active' ? 'Deactivate' : 'Activate'; ?> User"
                        onclick="return confirm('Are you sure you want to <?php echo $user['status'] === 'active' ? 'deactivate' : 'activate'; ?> this user?')">
                        <i class="fas fa-<?php echo $user['status'] === 'active' ? 'user-slash' : 'user-check'; ?>"></i>
                    </button>
                </form>
            </div>

            <!-- Second row: Assign Division -->
            <div class="btn-group w-30" role="group">
                <button type="button" class="btn btn-sm btn-outline-secondary w-30"
                    onclick="assignDivision(
                        <?php echo $user['id']; ?>,
                        '<?php echo htmlspecialchars($user['username']); ?>',
                        <?php echo json_encode(array_column($user_divisions[$user['id']] ?? [], 'division_id')); ?>
                    )"
                    title="Assign Division/Unit">
                    <i class="fas fa-building me-1"></i>Assign Division/Unit
                </button>
            </div>
        </td>
    </tr>
<?php endforeach; ?>
</tbody>

                            </table>
                        </div>
                        
                        <!-- Pagination -->
                        <?php if ($total_pages > 1): ?>
                            <nav aria-label="Users pagination" class="mt-4">
                                <ul class="pagination justify-content-center">
                                    <!-- Previous Page -->
                                    <?php if ($page > 1): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>">
                                                <i class="fas fa-chevron-left"></i>
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                    
                                    <!-- Page Numbers -->
                                    <?php
                                    $start_page = max(1, $page - 2);
                                    $end_page = min($total_pages, $page + 2);
                                    
                                    if ($start_page > 1):
                                    ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => 1])); ?>">1</a>
                                        </li>
                                        <?php if ($start_page > 2): ?>
                                            <li class="page-item disabled">
                                                <span class="page-link">...</span>
                                            </li>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                    
                                    <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                                        <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>">
                                                <?php echo $i; ?>
                                            </a>
                                        </li>
                                    <?php endfor; ?>
                                    
                                    <?php if ($end_page < $total_pages): ?>
                                        <?php if ($end_page < $total_pages - 1): ?>
                                            <li class="page-item disabled">
                                                <span class="page-link">...</span>
                                            </li>
                                        <?php endif; ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $total_pages])); ?>">
                                                <?php echo $total_pages; ?>
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                    
                                    <!-- Next Page -->
                                    <?php if ($page < $total_pages): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>">
                                                <i class="fas fa-chevron-right"></i>
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </nav>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>


<!-- Add User Modal -->
<div class="modal fade" id="addUserModal" tabindex="-1">
    <div class="modal-dialog modal-lg"> <!-- Make modal bigger -->
        <div class="modal-content shadow-lg border-0 rounded-3">
            
            <!-- Header -->
            <div class="modal-header" style="background-color: #2c2f48; color: #fff;">
                <h5 class="modal-title">
                    <i class="fas fa-user-plus me-2"></i> Add New User
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <!-- Form -->
            <form id="addUserForm" method="POST" action="">
                <div class="modal-body" style="background-color: #f8f9fc;">
                    <input type="hidden" name="action" value="add_user">

                    <!-- Row 1 -->
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="add_username" class="form-label fw-semibold">Username *</label>
                            <input type="text" class="form-control rounded-2 shadow-sm" id="add_username" name="username" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="add_email" class="form-label fw-semibold">Email *</label>
                            <div class="input-group shadow-sm">
                                <input type="text" class="form-control rounded-start-2" id="add_email" name="email_prefix" placeholder="Enter email prefix" required>
                                <span class="input-group-text bg-light text-muted rounded-end-2">@ptni.gov.ph</span>
                            </div>
                        </div>
                    </div>

                    <!-- Row 2 -->
                    <div class="mb-3">
                        <label for="add_full_name" class="form-label fw-semibold">Full Name</label>
                        <input type="text" class="form-control rounded-2 shadow-sm" id="add_full_name" name="full_name">
                    </div>

                    <!-- Row 3 -->
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="add_password" class="form-label fw-semibold">Password *</label>
                            <input type="password" class="form-control rounded-2 shadow-sm" id="add_password" name="password" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="add_status" class="form-label fw-semibold">Status *</label>
                            <select class="form-select rounded-2 shadow-sm" id="add_status" name="status" required>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                    </div>

                    <!-- Division Buttons -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Division/Unit</label>
                        <div id="add_division_buttons" class="d-flex flex-wrap gap-2">
                            <?php foreach ($divisions as $division): ?>
                                <input type="checkbox" class="btn-check" name="division_ids[]" id="division_<?php echo $division['id']; ?>" value="<?php echo $division['id']; ?>" autocomplete="off">
                                <label class="btn btn-outline-primary btn-sm rounded-pill px-3" for="division_<?php echo $division['id']; ?>">
                                    <?php echo htmlspecialchars($division['name']); ?>
                                </label>
                            <?php endforeach; ?>
                        </div>
                        <small class="text-muted">You can select multiple divisions/units.</small>
                    </div>
                </div>

                <!-- Footer -->
                <div class="modal-footer" style="background-color: #f1f3f7;">
                    <button type="button" class="btn btn-light border shadow-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary shadow-sm">
                        <i class="fas fa-plus me-2"></i> Add User
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>



<!-- Edit User Modal -->
<div class="modal fade" id="editUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-edit me-2"></i>Edit User
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="editUserForm" method="POST" action="">
                <div class="modal-body">
                    <input type="hidden" name="action" value="edit_user">
                    <input type="hidden" name="user_id" id="edit_user_id">
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="edit_username" class="form-label">Username *</label>
                            <input type="text" class="form-control" id="edit_username" name="username" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="edit_email" class="form-label">Email *</label>
                            <input type="text" class="form-control" id="edit_email" name="email" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_full_name" class="form-label">Full Name</label>
                        <input type="text" class="form-control" id="edit_full_name" name="full_name">
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="edit_password" class="form-label">New Password</label>
                            <input type="password" class="form-control" id="edit_password" name="password">
                            <div class="form-text">Leave blank to keep current password</div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="edit_status" class="form-label">Status *</label>
                            <select class="form-select" id="edit_status" name="status" required>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Update User
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Assign Division Modal -->
<div class="modal fade" id="assignDivisionModal" tabindex="-1">
    <div class="modal-dialog modal-lg"> <!-- bigger modal -->
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-building me-2"></i>Assign Division/Unit
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="assignDivisionForm" method="POST" action="">
                <div class="modal-body">
                    <input type="hidden" name="action" value="assign_division">
                    <input type="hidden" name="user_id" id="assign_user_id">

                    <div class="mb-3">
                        <label class="form-label">User</label>
                        <div class="form-control-plaintext" id="assign_username_display"></div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Select Division/Unit *</label>
                        <div id="assign_division_buttons" class="d-flex flex-wrap gap-2">
                            <?php foreach ($divisions as $division): ?>
                                <button type="button" class="btn btn-outline-secondary btn-sm division-btn"
                                        data-id="<?php echo $division['id']; ?>">
                                    <?php echo htmlspecialchars($division['name']); ?>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        You can select multiple divisions. Selected divisions will be assigned to the user.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-building me-2"></i>Assign Division
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>



<!-- User Details Modal -->
<div class="modal fade" id="userDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-user me-2"></i>User Details
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="userDetailsContent">
                <!-- Content will be loaded via AJAX -->
            </div>
        </div>
    </div>
</div>

<style>
/* Dashboard-style CSS */
.border-left-primary {
    border-left: 0.25rem solid rgba(27, 36, 83, 0.92) !important;
}

.border-left-success {
    border-left: 0.25rem solid rgba(27, 36, 83, 0.92) !important;
}

.border-left-info {
    border-left: 0.25rem solid rgba(27, 36, 83, 0.92) !important;
}

.border-left-warning {
    border-left: 0.25rem solid rgba(27, 36, 83, 0.92) !important;
}

.text-gray-800 {
    color: #5a5c69 !important;
}

.text-gray-300 {
    color: #dddfeb !important;
}

.font-weight-bold {
    font-weight: 700 !important;
}

.text-xs {
    font-size: 0.7rem;
}

.no-gutters {
    margin-right: 0;
    margin-left: 0;
}

.no-gutters > .col,
.no-gutters > [class*="col-"] {
    padding-right: 0;
    padding-left: 0;
}

.card {
    position: relative;
    display: flex;
    flex-direction: column;
    min-width: 0;
    word-wrap: break-word;
    background-color: #f7f7ffff;
    background-clip: border-box;
    border: 1px solid #0c1b5fff;
    border-radius: 0.35rem;
    
}

.shadow {
    box-shadow: 0 0.15rem 1.75rem 0 rgba(1, 2, 17, 0.15) !important;
}

.py-2 {
    padding-top: 0.5rem !important;
    padding-bottom: 0.5rem !important;
}

.py-3 {
    padding-top: 1rem !important;
    padding-bottom: 1rem !important;
    
}

.h-100 {
    height: 100% !important;
}

.avatar-circle {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: linear-gradient(45deg, #4e73df, #224abe);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    font-size: 0.9rem;
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

.btn-group .btn {
    margin-right: 2px;
}

.btn-group .btn:last-child {
    margin-right: 0;
}

.btn-group-vertical .btn-group {
    margin-bottom: 5px;
}

.card-header {
    background-color: #f8f9fc;
    border-bottom: 1px solid #e3e6f0;
}

.modal-header {
    background-color: #f8f9fc;
    border-bottom: 1px solid #e3e6f0;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .d-sm-flex {
        flex-direction: column !important;
        align-items: flex-start !important;
    }
    
    .d-sm-flex .btn {
        margin-top: 1rem;
        width: 100%;
    }
    
    .table-responsive {
        font-size: 0.875rem;
    }
    
    .btn-sm {
        padding: 0.2rem 0.4rem;
        font-size: 0.8rem;
    }
    
    .btn-group-vertical .btn {
        font-size: 0.75rem;
    }
}

@media (max-width: 576px) {
    .col-xl-3 {
        margin-bottom: 1rem;
    }
    
    .h5 {
        font-size: 1.1rem;
    }
    
    .text-xs {
        font-size: 0.65rem;
    }
    
    .btn-group-vertical .btn {
        padding: 0.15rem 0.3rem;
    }
}
</style>

<script>
// --------------------------
// Configuration
// --------------------------
const divisionEnabled = <?php echo $division_column_exists ? 'true' : 'false'; ?>;
window.selectedDivisions = [];

// --------------------------
// View User Details
// --------------------------
function viewUser(userId) {
    fetch('get_user_details.php?id=' + userId)
        .then(res => res.text())
        .then(data => {
            document.getElementById('userDetailsContent').innerHTML = data;
            new bootstrap.Modal(document.getElementById('userDetailsModal')).show();
        })
        .catch(() => {
            document.getElementById('userDetailsContent').innerHTML = '<p class="text-center">Loading user details...</p>';
            new bootstrap.Modal(document.getElementById('userDetailsModal')).show();
        });
}

// --------------------------
// Edit User
// --------------------------
function editUser(userData) {
    if (typeof userData === 'string') userData = JSON.parse(userData);

    document.getElementById('edit_user_id').value = userData.id;
    document.getElementById('edit_username').value = userData.username;
    document.getElementById('edit_email').value = userData.email;
    document.getElementById('edit_full_name').value = userData.full_name || '';
    document.getElementById('edit_status').value = userData.status;
    document.getElementById('edit_password').value = '';
    new bootstrap.Modal(document.getElementById('editUserModal')).show();
}

// --------------------------
// Assign Division (Multiple)
// --------------------------
function assignDivision(userId, username, currentDivisions = []) {
    document.getElementById('assign_user_id').value = userId;
    document.getElementById('assign_username_display').textContent = username;

    const buttons = document.querySelectorAll('#assign_division_buttons .division-btn');
    buttons.forEach(btn => {
        const id = btn.getAttribute('data-id');
        if (currentDivisions.includes(parseInt(id))) {
            btn.classList.add('btn-primary');
            btn.classList.remove('btn-outline-secondary');
        } else {
            btn.classList.remove('btn-primary');
            btn.classList.add('btn-outline-secondary');
        }
    });

    window.selectedDivisions = currentDivisions.map(String);

    new bootstrap.Modal(document.getElementById('assignDivisionModal')).show();
}

// Division button click
document.querySelectorAll('#assign_division_buttons .division-btn').forEach(btn => {
    btn.addEventListener('click', function () {
        const id = btn.getAttribute('data-id');
        if (window.selectedDivisions.includes(id)) {
            window.selectedDivisions = window.selectedDivisions.filter(d => d !== id);
            btn.classList.remove('btn-primary');
            btn.classList.add('btn-outline-secondary');
        } else {
            window.selectedDivisions.push(id);
            btn.classList.add('btn-primary');
            btn.classList.remove('btn-outline-secondary');
        }
    });
});

// Form submit: add hidden inputs for multiple divisions
document.getElementById('assignDivisionForm').addEventListener('submit', function(e) {
    document.querySelectorAll('#assignDivisionForm input[name="division_ids[]"]').forEach(el => el.remove());
    window.selectedDivisions.forEach(id => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'division_ids[]';
        input.value = id;
        this.appendChild(input);
    });
});

// --------------------------
// Form Validations
// --------------------------
// Email validation
function isValidPTNIEmail(email) {
    return email.toLowerCase().endsWith('@ptni.gov.ph');
}


// Edit User submit
document.getElementById('editUserForm').addEventListener('submit', function(e) {
    const username = document.getElementById('edit_username').value.trim();
    const password = document.getElementById('edit_password').value;
    const email = document.getElementById('edit_email').value.trim();

    if (username.length < 3) { 
        e.preventDefault(); 
        alert('Username must be at least 3 characters'); 
        return; 
    }
    if (password && password.length < 6) { 
        e.preventDefault(); 
        alert('Password must be at least 6 characters'); 
        return; 
    }
    if (!isValidPTNIEmail(email)) { 
        e.preventDefault(); 
        alert('Email must end with @ptni.gov.ph'); 
        return; 
    }
});


// Enable/disable Update button while typing
const editEmailInput = document.getElementById('edit_email');
const editUserBtn = document.querySelector('#editUserForm button[type="submit"]');

if (editEmailInput && editUserBtn) {
    const checkEmail = () => {
        editUserBtn.disabled = !editEmailInput.value.toLowerCase().endsWith('@ptni.gov.ph');
    };

    // Check whenever the user types
    editEmailInput.addEventListener('input', checkEmail);

    // Check when the modal opens (for prefilled emails)
    const editModal = document.getElementById('editUserModal');
    editModal.addEventListener('shown.bs.modal', checkEmail);

    // Initial check if needed
    checkEmail();
}



// Add User
document.getElementById('addUserForm').addEventListener('submit', function(e) {
    const username = document.getElementById('add_username').value.trim();
    const password = document.getElementById('add_password').value;
    const emailPrefix = document.getElementById('add_email').value.trim();

    if (username.length < 3) { e.preventDefault(); alert('Username must be at least 3 characters'); return; }
    if (password.length < 6) { e.preventDefault(); alert('Password must be at least 6 characters'); return; }
    if (emailPrefix.length < 3) { e.preventDefault(); alert('Email prefix must be at least 3 characters'); return; }
});


// Assign Division
if (divisionEnabled) {
    const assignForm = document.getElementById('assignDivisionForm');
    assignForm.addEventListener('submit', function(e) {
        if (window.selectedDivisions.length === 0) { e.preventDefault(); alert('Select at least one division'); return; }
        if (!confirm('Are you sure you want to assign this user to the selected division(s)?')) e.preventDefault();
    });
}

// --------------------------
// Modal Reset
// --------------------------
function resetModal(formId, buttonSelector) {
    const form = document.getElementById(formId);
    if (!form) return;
    form.reset();
    if (buttonSelector) {
        document.querySelectorAll(buttonSelector).forEach(btn => {
            btn.classList.remove('btn-primary');
            btn.classList.add('btn-outline-secondary');
        });
        window.selectedDivisions = [];
    }
}

document.getElementById('addUserModal').addEventListener('hidden.bs.modal', () => resetModal('addUserForm'));
document.getElementById('editUserModal').addEventListener('hidden.bs.modal', () => resetModal('editUserForm'));
if (divisionEnabled) {
    document.getElementById('assignDivisionModal').addEventListener('hidden.bs.modal', () => {
        resetModal('assignDivisionForm', '#assign_division_buttons .division-btn');
    });
}

// --------------------------
// Auto-refresh page
// --------------------------
setTimeout(() => {
    if (!document.querySelector('.modal.show')) location.reload();
}, 300000);

// --------------------------
// Enhanced search
// --------------------------
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.querySelector('input[name="search"]');
    if (searchInput) {
        let timer;
        searchInput.addEventListener('input', function() {
            clearTimeout(timer);
            timer = setTimeout(() => {
                if (this.value.length >= 3 || this.value.length === 0) this.form.submit();
            }, 1000);
        });
    }

    // Tooltips
    document.querySelectorAll('[title]').forEach(el => new bootstrap.Tooltip(el));

});

// --------------------------
// Export / Print / Reset
// --------------------------
function exportUsers() {
    const url = new URL(window.location.href);
    url.searchParams.set('export', 'csv');
    window.location.href = url.toString();
}

function printUserList() { window.print(); }

function resetFilters() {
    const form = document.querySelector('form[method="GET"]');
    form.querySelectorAll('input, select').forEach(el => {
        if (el.type === 'text' || el.type === 'search') el.value = '';
        else if (el.tagName.toLowerCase() === 'select') el.selectedIndex = 0;
    });
    form.submit();
}

// --------------------------
// Keyboard Shortcuts
// --------------------------
document.addEventListener('keydown', function(e) {
    if (e.ctrlKey && e.key === 'n') { e.preventDefault(); document.querySelector('[data-bs-target="#addUserModal"]').click(); }
    if (e.ctrlKey && e.key === 'f') { e.preventDefault(); document.querySelector('input[name="search"]').focus(); }
    if (e.key === 'Escape') document.querySelectorAll('.modal.show').forEach(modal => bootstrap.Modal.getInstance(modal).hide());
});

// --------------------------
// Add/Edit User button toggles
// --------------------------
function toggleButton(input, button, validator) { button.disabled = !validator(input.value); }

const addEmailInput = document.getElementById('add_email');
const addUserBtn = document.querySelector('#addUserForm button[type="submit"]');
if (addEmailInput && addUserBtn) addEmailInput.addEventListener('input', () => toggleButton(addEmailInput, addUserBtn, v => v.length >= 3));

</script>


<?php include '../includes/footer.php'; ?>
