<?php
require_once('../config/database.php');
$db = (new Database())->getConnection();

// Get GET parameters
$folder_id   = $_GET['folder_id'] ?? null;
$search      = $_GET['search'] ?? '';
$user        = $_GET['user'] ?? '';
$type        = $_GET['type'] ?? '';
$status      = $_GET['status'] ?? '';

// Build query
$where_conditions = [];
$params = [];

// Folder filter
if (!empty($folder_id)) {
    $where_conditions[] = "d.folder_id = ?";
    $params[] = $folder_id;
}

// Exact match search (case-insensitive)
if (!empty($search)) {
    $where_conditions[] = "LOWER(d.title) = LOWER(?)";
    $params[] = $search;
}

// User filter
if (!empty($user)) {
    $where_conditions[] = "d.user_id = ?";
    $params[] = $user;
}

// File type filter
if (!empty($type)) {
    $where_conditions[] = "d.file_type = ?";
    $params[] = $type;
}

// Status filter
if (!empty($status)) {
    if ($status === 'public') {
        $where_conditions[] = "d.is_public = ?";
        $params[] = 1;
    } elseif ($status === 'private') {
        $where_conditions[] = "d.is_public = ?";
        $params[] = 0;
    }
}

// Build WHERE clause
$where_clause = '';
if (!empty($where_conditions)) {
    $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
}

try {
    // Fetch documents
    $sql = "
        SELECT d.*, u.username, u.full_name, c.name AS category_name
        FROM documents d
        JOIN users u ON d.user_id = u.id
        LEFT JOIN categories c ON d.category_id = c.id
        $where_clause
        ORDER BY d.created_at DESC
    ";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $documents = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$documents) {
        echo '<div class="text-center py-4 text-muted">No documents found</div>';
        exit;
    }

} catch (PDOException $e) {
    echo '<div class="alert alert-danger">Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
    exit;
}
?>

<div class="row g-2">
<?php foreach ($documents as $doc): ?>
  <div class="col-sm-6 col-md-4 col-lg-3">
    <div class="card shadow-sm h-100">
      <div class="card-body d-flex flex-column">
        <h6 class="fw-bold text-truncate"><?php echo htmlspecialchars($doc['title']); ?></h6>
        <small class="text-muted text-truncate"><?php echo htmlspecialchars($doc['original_name']); ?></small>
        <small><strong><?php echo htmlspecialchars($doc['full_name'] ?? $doc['username']); ?></strong></small>
        <small>
          <?php echo $doc['category_name'] 
            ? '<span class="badge bg-info">'.htmlspecialchars($doc['category_name']).'</span>'
            : '<span class="text-muted">Uncategorized</span>'; ?>
        </small>
        <div class="mt-auto btn-group btn-group-sm">
          <a href="../api/download.php?id=<?php echo $doc['id']; ?>" 
             class="btn btn-outline-success" title="Download">
            <i class="fas fa-download"></i>
          </a>
          <button type="button" class="btn btn-outline-primary"
                  onclick="previewDocument(<?php echo $doc['id']; ?>, '<?php echo addslashes($doc['original_name']); ?>', '<?php echo $doc['file_type']; ?>')"
                  title="Preview">
            <i class="fas fa-eye"></i>
          </button>
          <button type="button" class="btn btn-outline-warning"
                  onclick="editCategory(<?php echo $doc['id']; ?>, <?php echo $doc['category_id'] ?: 'null'; ?>)"
                  title="Edit Category">
            <i class="fas fa-tag"></i>
          </button>
          <button type="button" class="btn btn-outline-danger"
                  onclick="deleteDocument(<?php echo $doc['id']; ?>, '<?php echo addslashes($doc['title']); ?>')"
                  title="Delete">
            <i class="fas fa-trash"></i>
          </button>
        </div>
      </div>
    </div>
  </div>
<?php endforeach; ?>
</div>
