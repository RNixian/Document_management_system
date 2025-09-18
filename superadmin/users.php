<?php
$page_title = 'Manage Users';
require_once '../includes/functions.php';
requireLogin();
requireSuperAdmin();

// Handle user actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {

            case 'delete_user':
                $user_id = (int)$_POST['user_id'];
                if ($user_id !== $_SESSION['user_id']) { // Prevent admin from deleting themselves
                    try {
                        // Delete user's documents first
                        $stmt = $db->prepare("DELETE FROM documents WHERE user_id = ?");
                        $stmt->execute([$user_id]);
                        
                        // Delete user
                        $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
                        $stmt->execute([$user_id]);
                        
                        setAlert('User deleted successfully', 'success');
                    } catch (PDOException $e) {
                        setAlert('Error deleting user: ' . $e->getMessage(), 'danger');
                    }
                } else {
                    setAlert('You cannot delete your own account', 'warning');
                }
                break;
                
            case 'toggle_status':
                $user_id = (int)$_POST['user_id'];
                $new_status = $_POST['status'] === 'active' ? 'inactive' : 'active';
                
                if ($user_id !== $_SESSION['user_id']) { // Prevent admin from deactivating themselves
                    try {
                        $stmt = $db->prepare("UPDATE users SET status = ? WHERE id = ?");
                        $stmt->execute([$new_status, $user_id]);
                        
                        setAlert('User status updated successfully', 'success');
                    } catch (PDOException $e) {
                        setAlert('Error updating user status: ' . $e->getMessage(), 'danger');
                    }
                } else {
                    setAlert('You cannot change your own status', 'warning');
                }
                break;
                
            case 'change_role':
                $user_id = (int)$_POST['user_id'];
                $new_role = $_POST['role'];
                
                if ($user_id !== $_SESSION['user_id']) { // Prevent admin from changing their own role
                    try {
                        $stmt = $db->prepare("UPDATE users SET role = ? WHERE id = ?");
                        $stmt->execute([$new_role, $user_id]);
                        
                        setAlert('User role updated successfully', 'success');
                    } catch (PDOException $e) {
                        setAlert('Error updating user role: ' . $e->getMessage(), 'danger');
                    }
                } else {
                    setAlert('You cannot change your own role', 'warning');
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

                    case 'add_user':
                        $username = sanitize($_POST['username']);
                        $email_prefix = sanitize($_POST['email_prefix']);
                        $email = $email_prefix . '@ptni.gov.ph';
                        $full_name = sanitize($_POST['full_name']);
                        $password = $_POST['password'];
                        $status = sanitize($_POST['status']);
                        $division_ids = $_POST['division_ids'] ?? []; // array of selected divisions
                        $role = $_POST['role'] ?? 'user'; // default to user

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
$stmt = $db->prepare("INSERT INTO users (username, email, full_name, password, role, status) 
VALUES (?, ?, ?, ?, ?, ?)");
$stmt->execute([$username, $email, $full_name, $hashed_password, $role, $status]);

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
        // Redirect to prevent form resubmission
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

// Fetch all user divisions
$user_divisions = [];
try {
    $stmt = $db->query("SELECT user_id, division_id FROM user_divisions");
    $all_user_divs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($all_user_divs as $ud) {
        $user_divisions[$ud['user_id']][] = $ud; // store by user_id
    }
} catch (PDOException $e) {
    $user_divisions = []; // fallback
}

// Get search and filter parameters
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$role_filter = isset($_GET['role']) ? sanitize($_GET['role']) : '';
$status_filter = isset($_GET['status']) ? sanitize($_GET['status']) : '';
$sort = isset($_GET['sort']) ? sanitize($_GET['sort']) : 'created_at';
$order = isset($_GET['order']) && $_GET['order'] === 'asc' ? 'ASC' : 'DESC';

// Pagination
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

// Build query
$where_conditions = [];
$params = [];

if (!empty($search)) {
    $where_conditions[] = "(username LIKE ? OR email LIKE ? OR full_name LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

if (!empty($role_filter)) {
    $where_conditions[] = "role = ?";
    $params[] = $role_filter;
}

if (!empty($status_filter)) {
    $where_conditions[] = "status = ?";
    $params[] = $status_filter;
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Valid sort columns
$valid_sorts = ['username', 'email', 'full_name', 'role', 'status', 'created_at', 'last_login'];
if (!in_array($sort, $valid_sorts)) {
    $sort = 'created_at';
}

try {
    // Get total count
    $count_sql = "SELECT COUNT(*) as total FROM users $where_clause";
    $stmt = $db->prepare($count_sql);
    $stmt->execute($params);
    $total_users = $stmt->fetch()['total'];
    
    // Get users
    $sql = "SELECT * FROM users $where_clause ORDER BY $sort $order LIMIT $per_page OFFSET $offset";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $users = $stmt->fetchAll();
    
    // Calculate pagination
    $total_pages = ceil($total_users / $per_page);
    
    // Get statistics
    $stats_sql = "SELECT 
        COUNT(*) as total_users,
        SUM(CASE WHEN role = 'admin' THEN 1 ELSE 0 END) as admin_count,
        SUM(CASE WHEN role = 'user' THEN 1 ELSE 0 END) as user_count,
        SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_count,
        SUM(CASE WHEN status = 'inactive' THEN 1 ELSE 0 END) as inactive_count
    FROM users";
    $stmt = $db->query($stats_sql);
    $stats = $stmt->fetch();
    
} catch (PDOException $e) {
    setAlert('Error loading users: ' . $e->getMessage(), 'danger');
    $users = [];
    $total_users = 0;
    $total_pages = 0;
    $stats = ['total_users' => 0, 'admin_count' => 0, 'user_count' => 0, 'active_count' => 0, 'inactive_count' => 0];
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


require_once '../includes/superadmin_header.php';
?>

<div class="container-fluid px-4" style="background: #f8f9faff;">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0" style="background: rgba(1, 9, 51, 0.92); box-shadow: 0 8px 32px rgba(31, 38, 135, 0.18);color: white;">
                <div class="card-body py-4">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h1 class="display-6 fw-bold mb-2">
                                <i class="fas fa-users me-3"></i>User Management
                            </h1>
                            <p class="lead mb-0">
                                Manage user accounts, roles, and permissions
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
        <div class="card border-left-primary shadow h-100 py-2" >
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-primary text-black text-uppercase mb-1">
                            Total Users</div>
                        <div class="h5 mb-0 font-weight-bold">
                            <?php echo number_format($stats['total_users']); ?>
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-users fa-2x text-gray-300"></i>
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
                        <div class="text-xs font-weight-bold text-success  text-black text-uppercase mb-1">
                            Active Users</div>
                        <div class="h5 mb-0 font-weight-bold" style="color:black;">
                            <?php echo number_format($stats['active_count']); ?>
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-user-check fa-2x text-gray-300"></i>
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
                        <div class="text-xs font-weight-bold text-warning text-black text-uppercase mb-1">
                            Inactive Users</div>
                        <div class="h5 mb-0 font-weight-bold" style="color:black;">
                            <?php echo number_format($stats['inactive_count']); ?>
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-user-slash fa-2x text-gray-300"></i>
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
                        <div class="text-xs font-weight-bold text-info  text-black text-uppercase mb-1">
                            New This Month</div>
                        <div class="h5 mb-0 font-weight-bold" style="color:black;">
                            <?php
                            try {
                                $stmt = $db->query("SELECT COUNT(*) FROM users WHERE role = 'user' AND created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)");
                                echo number_format($stmt->fetchColumn());
                            } catch (PDOException $e) {
                                echo '0';
                            }
                            ?>
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-user-plus fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

    <!-- Filters and Search -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header" style="background: #040547ff; color: white;">
                    <h5 class="mb-0">
                        <i class="fas fa-filter me-2"></i>Search & Filter Users
                    </h5>
                </div>
                <div class="card-body">
                    <form method="GET" action="" class="row g-3">
                        <!-- Search -->
                        <div class="col-md-4">
                            <label for="search" class="form-label">Search Users</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-search"></i></span>
                                <input type="text" class="form-control" id="search" name="search" 
                                       placeholder="Username, email, or name..." value="<?php echo htmlspecialchars($search); ?>">
                            </div>
                        </div>
                        
                        <!-- Role Filter -->
                        <div class="col-md-2">
                            <label for="role" class="form-label">Role</label>
                            <select class="form-select" id="role" name="role">
                                <option value="">All Roles</option>
                                <option value="admin" <?php echo $role_filter === 'admin' ? 'selected' : ''; ?>>Admin</option>
                                <option value="user" <?php echo $role_filter === 'user' ? 'selected' : ''; ?>>User</option>
                            </select>
                        </div>
                        
                        <!-- Status Filter -->
                        <div class="col-md-2">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status">
                                <option value="">All Status</option>
                                <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>Active</option>
                                <option value="inactive" <?php echo $status_filter === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                            </select>
                        </div>
                        
                        <!-- Sort By -->
                        <div class="col-md-2">
                            <label for="sort" class="form-label">Sort By</label>
                            <select class="form-select" id="sort" name="sort">
                                <option value="created_at" <?php echo $sort === 'created_at' ? 'selected' : ''; ?>>Date Joined</option>
                                <option value="username" <?php echo $sort === 'username' ? 'selected' : ''; ?>>Username</option>
                                <option value="email" <?php echo $sort === 'email' ? 'selected' : ''; ?>>Email</option>
                                <option value="full_name" <?php echo $sort === 'full_name' ? 'selected' : ''; ?>>Full Name</option>
                                <option value="last_login" <?php echo $sort === 'last_login' ? 'selected' : ''; ?>>Last Login</option>
                            </select>
                        </div>
                        
                        <!-- Sort Order -->
                        <div class="col-md-1">
                            <label for="order" class="form-label">Order</label>
                            <select class="form-select" id="order" name="order">
                                <option value="desc" <?php echo $order === 'DESC' ? 'selected' : ''; ?>>↓</option>
                                <option value="asc" <?php echo $order === 'ASC' ? 'selected' : ''; ?>>↑</option>
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
                <h5 class="mb-0">
                    <?php echo number_format($total_users); ?> User<?php echo $total_users !== 1 ? 's' : ''; ?>
                    <?php if (!empty($search)): ?>
                        for "<?php echo htmlspecialchars($search); ?>"
                    <?php endif; ?>
                </h5>
                <?php if (!empty($search) || !empty($role_filter) || !empty($status_filter)): ?>
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
            <div class="card">
                <div class="card-body">
                    <?php if (empty($users)): ?>
                                     <div class="text-center py-5">
                            <i class="fas fa-users" style="font-size: 5rem; color: #04084dff;"></i>
                            <h3 class="mt-4 text-muted">No users found</h3>
                            <?php if (!empty($search) || !empty($role_filter) || !empty($status_filter)): ?>
                                <p class="text-muted">Try adjusting your search criteria or filters.</p>
                            <?php else: ?>
                                <p class="text-muted">No users in the system yet.</p>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                             <table class="table table-bordered table-hover" width="100%" cellspacing="0">
                                  <thead class="table-dark">
                                    <tr>
                                        <th>User</th>
                                        <th>Email</th>
                                        <th>Role</th>
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
                                                        <div class="fw-bold"><?php echo htmlspecialchars($user['full_name'] ?? $user['username']); ?></div>
                                                        <small class="text-muted">@<?php echo htmlspecialchars($user['username']); ?></small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                                                
                                            <td>
                                                <?php if ($user['role'] === 'superadmin'): ?>
                                                    <span class="badge bg-danger">
                                                        <i class="fas fa-crown me-1"></i> Superadmin
                                                    </span>
                                                <?php elseif ($user['role'] === 'admin'): ?>
                                                    <span class="badge bg-warning text-dark">
                                                        <i class="fas fa-user-shield me-1"></i> Admin
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge bg-primary">
                                                        <i class="fas fa-user me-1"></i> <?php echo ucfirst($user['role']); ?>
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                             <!-- Division Column -->
                                             <td>
                                                <?php if ($user['role'] === 'superadmin'): ?>
                                                    <span class="badge bg-danger text-white">
                                                        <i class="fas fa-crown me-1"></i> Superadmin
                                                    </span>
                                                <?php elseif (!empty($user_divisions[$user['id']])): ?>
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
                                                <div class="btn-group" role="group">
                                                    <!-- View User -->
                                                    <button type="button" class="btn btn-sm btn-outline-info" 
                                                            onclick="viewUser(<?php echo $user['id']; ?>)" 
                                                            title="View Details">
                                                        <i class="fas fa-eye"></i>
                                                    </button>

                                                    <!-- Toggle Status -->
                                                    <?php if ($user['id'] !== $_SESSION['user_id']): ?>
                                                        <form method="POST" style="display: inline;">
                                                            <input type="hidden" name="action" value="toggle_status">
                                                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                            <input type="hidden" name="status" value="<?php echo $user['status']; ?>">
                                                            <button type="submit" class="btn btn-sm btn-outline-<?php echo $user['status'] === 'active' ? 'warning' : 'success'; ?>" 
                                                                    title="<?php echo $user['status'] === 'active' ? 'Deactivate' : 'Activate'; ?> User"
                                                                    onclick="return confirm('Are you sure you want to <?php echo $user['status'] === 'active' ? 'deactivate' : 'activate'; ?> this user?')">
                                                                <i class="fas fa-<?php echo $user['status'] === 'active' ? 'user-slash' : 'user-check'; ?>"></i>
                                                            </button>
                                                        </form>
                                                    <?php endif; ?>
                                                    
                                                    <!-- Delete User -->
                                                    <?php if ($user['id'] !== $_SESSION['user_id']): ?>
                                                        <button type="button" class="btn btn-sm btn-outline-danger" 
                                                                onclick="deleteUser(<?php echo $user['id']; ?>, '<?php echo addslashes($user['username']); ?>')" 
                                                                title="Delete User">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    <?php endif; ?>

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
    <div class="modal-dialog modal-lg">
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
<!-- Role Selection -->
<div class="mb-3">
    <label class="form-label fw-semibold">Role</label>
    <div id="add_role_buttons" class="d-flex flex-wrap gap-2">
        <input type="radio" class="btn-check" name="role" id="role_user" value="user" autocomplete="off" checked>
        <label class="btn btn-outline-success btn-sm rounded-pill px-3" for="role_user">
            User
        </label>

        <input type="radio" class="btn-check" name="role" id="role_admin" value="admin" autocomplete="off">
        <label class="btn btn-outline-danger btn-sm rounded-pill px-3" for="role_admin">
            Admin
        </label>
    </div>
    <small class="text-muted">Default is <b>User</b>. Only superadmins can assign roles.</small>
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
                    <input type="hidden" name="division_ids[]" id="assign_division_ids">

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



<style>
.avatar-circle {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: linear-gradient(45deg, #007bff, #0056b3);
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
</style>

<script>
function isValidPTNIEmail(email) {
    return email.toLowerCase().endsWith('@ptni.gov.ph');
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


// Delete user function
function deleteUser(userId, username) {
    if (confirm(`Are you sure you want to delete user "${username}"? This will also delete all their documents. This action cannot be undone.`)) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="delete_user">
            <input type="hidden" name="user_id" value="${userId}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

// View user details
function viewUser(userId) {
    fetch(`get_user_details.php?id=${userId}`)
        .then(response => response.text())
        .then(data => {
            document.getElementById('userDetailsContent').innerHTML = data;
            new bootstrap.Modal(document.getElementById('userDetailsModal')).show();
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error loading user details');
        });
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

if (divisionEnabled) {
    document.getElementById('assignDivisionModal').addEventListener('hidden.bs.modal', () => {
        resetModal('assignDivisionForm', '#assign_division_buttons .division-btn');
    });
}


// Auto-refresh page every 5 minutes to show updated login times
setTimeout(() => {
    if (!document.querySelector('.modal.show')) { // Don't refresh if modal is open
        location.reload();
    }
}, 300000);

// Form validation for add user modal
document.getElementById('addUserForm').addEventListener('submit', function(e) {
    const password = document.getElementById('password').value;
    const username = document.getElementById('username').value;
    const email = document.getElementById('email').value;
    
    // Basic validation
    if (password.length < 6) {
        e.preventDefault();
        alert('Password must be at least 6 characters long');
        return;
    }
    
    if (username.length < 3) {
        e.preventDefault();
        alert('Username must be at least 3 characters long');
        return;
    }
    
    if (!email.includes('@')) {
        e.preventDefault();
        alert('Please enter a valid email address');
        return;
    }
});

// Clear form when modal is closed
document.getElementById('addUserModal').addEventListener('hidden.bs.modal', function() {
    document.getElementById('addUserForm').reset();
});
// ...existing code...

// Disable Add User button if email is not @ptni.gov.ph
const emailInput = document.getElementById('email');
const addUserBtn = document.querySelector('#addUserForm button[type="submit"]');

function validateEmailDomain() {
    const email = emailInput.value.trim();
    if (email.endsWith('@ptni.gov.ph')) {
        addUserBtn.disabled = false;
    } else {
        addUserBtn.disabled = true;
    }
}

emailInput.addEventListener('input', validateEmailDomain);

// Run on page load in case of autofill
validateEmailDomain();

// ...existing code...





</script>

<?php require_once '../includes/footer.php'; ?>
