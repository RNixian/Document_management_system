<?php
$page_title = 'Browse Public Documents';
require_once 'includes/functions.php';

// Get filter parameters
$category_filter = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$search_query = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$sort_by = isset($_GET['sort']) ? sanitize($_GET['sort']) : 'downloads';
$sort_order = isset($_GET['order']) && $_GET['order'] === 'asc' ? 'ASC' : 'DESC';

// Pagination
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$per_page = 12;
$offset = ($page - 1) * $per_page;

// Build query for public documents only
$where_conditions = ['is_public = 1'];
$params = [];

if ($category_filter > 0) {
    $where_conditions[] = 'category_id = ?';
    $params[] = $category_filter;
}

if (!empty($search_query)) {
    $where_conditions[] = '(title LIKE ? OR description LIKE ? OR tags LIKE ?)';
    $search_param = "%$search_query%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

$where_clause = implode(' AND ', $where_conditions);

// Valid sort columns
$valid_sorts = ['title', 'created_at', 'file_size', 'downloads'];
if (!in_array($sort_by, $valid_sorts)) {
    $sort_by = 'downloads';
}

try {
    // Get total count
    $count_sql = "SELECT COUNT(*) as total FROM documents WHERE $where_clause";
    $stmt = $db->prepare($count_sql);
    $stmt->execute($params);
    $total_documents = $stmt->fetch()['total'];
    
    // Get documents with user info
    $sql = "
        SELECT d.*, c.name as category_name, c.color as category_color,
               u.username, u.full_name
        FROM documents d 
        LEFT JOIN categories c ON d.category_id = c.id 
        LEFT JOIN users u ON d.user_id = u.id
        WHERE $where_clause 
        ORDER BY d.$sort_by $sort_order 
        LIMIT $per_page OFFSET $offset
    ";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $documents = $stmt->fetchAll();
    
    // Get categories for filter
    $stmt = $db->query("
        SELECT c.*, COUNT(d.id) as doc_count 
        FROM categories c 
        LEFT JOIN documents d ON c.id = d.category_id AND d.is_public = 1 
        GROUP BY c.id 
        HAVING doc_count > 0 
        ORDER BY c.name
    ");
    $categories = $stmt->fetchAll();
    
    // Get popular tags
    $stmt = $db->query("
        SELECT tags FROM documents 
        WHERE is_public = 1 AND tags IS NOT NULL AND tags != ''
    ");
    $tag_results = $stmt->fetchAll();
    
    $all_tags = [];
    foreach ($tag_results as $result) {
        $tags = explode(',', $result['tags']);
        foreach ($tags as $tag) {
            $tag = trim($tag);
            if (!empty($tag)) {
                $all_tags[] = strtolower($tag);
            }
        }
    }
    $popular_tags = array_slice(array_keys(array_count_values($all_tags)), 0, 10);
    
    // Calculate pagination
    $total_pages = ceil($total_documents / $per_page);
    
} catch (PDOException $e) {
    $documents = [];
    $categories = [];
    $popular_tags = [];
    $total_documents = 0;
    $total_pages = 0;
}

require_once 'includes/header.php';
?>

<div class="container-fluid px-4">
    <!-- Hero Section -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="hero-section">
                <div class="hero-content text-center py-5">
                    <h1 class="display-4 fw-bold mb-3">
                        <i class="fas fa-globe me-3"></i>Public Document Library
                    </h1>
                    <p class="lead mb-4">
                        Discover and download documents shared by our community
                    </p>
                    <div class="hero-stats">
                        <div class="row justify-content-center">
                            <div class="col-md-3">
                                <div class="stat-card">
                                    <h3 class="stat-number"><?php echo number_format($total_documents); ?></h3>
                                    <p class="stat-label">Public Documents</p>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="stat-card">
                                    <h3 class="stat-number"><?php echo count($categories); ?></h3>
                                    <p class="stat-label">Categories</p>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="stat-card">
                                    <?php
                                    $stmt = $db->query("SELECT SUM(downloads) as total_downloads FROM documents WHERE is_public = 1");
                                    $total_downloads = $stmt->fetch()['total_downloads'] ?: 0;
                                    ?>
                                    <h3 class="stat-number"><?php echo number_format($total_downloads); ?></h3>
                                    <p class="stat-label">Total Downloads</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Search and Filters -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <form method="GET" action="" class="row g-3">
                        <!-- Search -->
                        <div class="col-md-4">
                            <div class="search-container">
                                <input type="text" class="form-control search-input" name="search" 
                                       placeholder="Search public documents..." value="<?php echo htmlspecialchars($search_query); ?>">
                                <i class="fas fa-search search-icon"></i>
                         
                        </div>
                        
                        <!-- Category Filter -->
                        <div class="col-md-3">
                            <select class="form-select" name="category">
                                <option value="">All Categories</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['id']; ?>" 
                                            <?php echo $category_filter == $category['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($category['name']); ?> (<?php echo $category['doc_count']; ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <!-- Sort Options -->
                        <div class="col-md-3">
                            <select class="form-select" name="sort">
                                <option value="downloads" <?php echo $sort_by === 'downloads' ? 'selected' : ''; ?>>Most Downloaded</option>
                                <option value="created_at" <?php echo $sort_by === 'created_at' ? 'selected' : ''; ?>>Newest First</option>
                                <option value="title" <?php echo $sort_by === 'title' ? 'selected' : ''; ?>>Title A-Z</option>
                                <option value="file_size" <?php echo $sort_by === 'file_size' ? 'selected' : ''; ?>>File Size</option>
                            </select>
                        </div>
                        
                        <!-- Sort Order -->
                        <div class="col-md-2">
                            <div class="btn-group w-100" role="group">
                                <input type="radio" class="btn-check" name="order" value="desc" id="desc" 
                                       <?php echo $sort_order === 'DESC' ? 'checked' : ''; ?>>
                                <label class="btn btn-outline-secondary" for="desc">
                                    <i class="fas fa-sort-amount-down"></i>
                                </label>
                                
                                <input type="radio" class="btn-check" name="order" value="asc" id="asc" 
                                       <?php echo $sort_order === 'ASC' ? 'checked' : ''; ?>>
                                <label class="btn btn-outline-secondary" for="asc">
                                    <i class="fas fa-sort-amount-up"></i>
                                </label>
                            </div>
                        </div>
                        
                        <!-- Submit Button -->
                        <div class="col-12">
                            <div class="d-flex justify-content-between align-items-center">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search me-2"></i>Search
                                </button>
                                
                                <?php if ($search_query || $category_filter): ?>
                                    <a href="browse.php" class="btn btn-outline-secondary">
                                        <i class="fas fa-times me-2"></i>Clear Filters
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Popular Tags -->
    <?php if (!empty($popular_tags)): ?>
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h6 class="card-title mb-3">
                            <i class="fas fa-fire me-2"></i>Popular Tags
                        </h6>
                        <div class="popular-tags">
                            <?php foreach ($popular_tags as $tag): ?>
                                <a href="?search=<?php echo urlencode($tag); ?>" class="tag-pill-clickable">
                                    <?php echo htmlspecialchars($tag); ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
    
    <!-- Results Header -->
    <div class="row mb-3">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-0">
                        <?php if ($search_query): ?>
                            Search results for "<?php echo htmlspecialchars($search_query); ?>"
                        <?php elseif ($category_filter): ?>
                            <?php
                            $selected_category = array_filter($categories, function($cat) use ($category_filter) {
                                return $cat['id'] == $category_filter;
                            });
                            $selected_category = reset($selected_category);
                            ?>
                            Documents in "<?php echo htmlspecialchars($selected_category['name']); ?>"
                        <?php else: ?>
                            All Public Documents
                        <?php endif; ?>
                    </h5>
                    <small class="text-muted">
                        <?php echo number_format($total_documents); ?> document<?php echo $total_documents !== 1 ? 's' : ''; ?> found
                    </small>
                </div>
                
                <div class="view-toggle">
                    <div class="btn-group" role="group">
                        <input type="radio" class="btn-check" name="view" value="grid" id="gridView" checked>
                        <label class="btn btn-outline-secondary" for="gridView">
                            <i class="fas fa-th"></i>
                        </label>
                        
                        <input type="radio" class="btn-check" name="view" value="list" id="listView">
                        <label class="btn btn-outline-secondary" for="listView">
                            <i class="fas fa-list"></i>
                        </label>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Documents Grid -->
    <?php if (empty($documents)): ?>
        <div class="row">
            <div class="col-12">
                <div class="empty-state text-center py-5">
                    <div class="empty-icon mb-4">
                        <i class="fas fa-search" style="font-size: 4rem; color: #dee2e6;"></i>
                    </div>
                    <h4 class="text-muted">No documents found</h4>
                    <p class="text-muted mb-4">
                        <?php if ($search_query || $category_filter): ?>
                            Try adjusting your search criteria or browse all documents.
                        <?php else: ?>
                            There are no public documents available at the moment.
                        <?php endif; ?>
                    </p>
                    <?php if ($search_query || $category_filter): ?>
                        <a href="browse.php" class="btn btn-primary">
                            <i class="fas fa-globe me-2"></i>Browse All Documents
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php else: ?>
        <!-- Grid View -->
        <div class="documents-grid" id="gridView">
            <div class="row">
                <?php foreach ($documents as $doc): ?>
                    <div class="col-lg-3 col-md-4 col-sm-6 mb-4">
                        <div class="document-card h-100">
                            <div class="document-header">
                                <div class="document-icon">
                                    <i class="<?php echo getFileIcon($doc['file_type']); ?>"></i>
                                </div>
                                <div class="document-type">
                                    <?php echo strtoupper($doc['file_type']); ?>
                                </div>
                            </div>
                            
                            <div class="document-body">
                                <h6 class="document-title" title="<?php echo htmlspecialchars($doc['title']); ?>">
                                    <a href="view.php?id=<?php echo $doc['id']; ?>" class="text-decoration-none">
                                        <?php echo htmlspecialchars($doc['title']); ?>
                                    </a>
                                </h6>
                                
                                <?php if ($doc['description']): ?>
                                    <p class="document-description">
                                        <?php echo htmlspecialchars(substr($doc['description'], 0, 100)); ?>
                                        <?php echo strlen($doc['description']) > 100 ? '...' : ''; ?>
                                    </p>
                                <?php endif; ?>
                                
                                <div class="document-meta">
                                    <small class="text-muted">
                                        <i class="fas fa-user me-1"></i>
                                        <?php echo htmlspecialchars($doc['full_name'] ?: $doc['username']); ?>
                                    </small>
                                    <br>
                                    <small class="text-muted">
                                        <i class="fas fa-calendar me-1"></i>
                                        <?php echo date('M j, Y', strtotime($doc['created_at'])); ?>
                                    </small>
                                    <br>
                                    <small class="text-muted">
                                        <i class="fas fa-weight me-1"></i>
                                        <?php echo formatFileSize($doc['file_size']); ?>
                                    </small>
                                    <br>
                                    <small class="text-muted">
                                        <i class="fas fa-download me-1"></i>
                                        <?php echo number_format($doc['downloads']); ?> downloads
                                    </small>
                                </div>
                                
                                <?php if ($doc['category_name']): ?>
                                    <div class="mt-2">
                                        <span class="category-pill" style="background-color: <?php echo $doc['category_color']; ?>">
                                            <?php echo htmlspecialchars($doc['category_name']); ?>
                                        </span>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if ($doc['tags']): ?>
                                    <div class="document-tags mt-2">
                                        <?php 
                                        $tags = explode(',', $doc['tags']);
                                        foreach (array_slice($tags, 0, 2) as $tag): 
                                            $tag = trim($tag);
                                            if (!empty($tag)):
                                        ?>
                                            <span class="tag-pill"><?php echo htmlspecialchars($tag); ?></span>
                                        <?php 
                                            endif;
                                        endforeach; 
                                        if (count($tags) > 2):
                                        ?>
                                            <span class="tag-pill">+<?php echo count($tags) - 2; ?></span>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="document-footer">
                                <div class="btn-group w-100" role="group">
                                    <a href="api/download.php?id=<?php echo $doc['id']; ?>" 
                                       class="btn btn-outline-primary btn-sm">
                                        <i class="fas fa-download"></i>
                                    </a>
                                    <a href="view.php?id=<?php echo $doc['id']; ?>" 
                                       class="btn btn-outline-info btn-sm">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <button type="button" class="btn btn-outline-secondary btn-sm" 
                                            onclick="copyLink('<?php echo getBaseUrl(); ?>/view.php?id=<?php echo $doc['id']; ?>')">
                                        <i class="fas fa-link"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- List View -->
        <div class="documents-list d-none" id="listView">
            <div class="card">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Document</th>
                                <th>Category</th>
                                <th>Owner</th>
                                <th>Size</th>
                                <th>Downloads</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($documents as $doc): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="file-icon me-3">
                                                <i class="<?php echo getFileIcon($doc['file_type']); ?>"></i>
                                            </div>
                                            <div>
                                                <h6 class="mb-1">
                                                    <a href="view.php?id=<?php echo $doc['id']; ?>" class="text-decoration-none">
                                                        <?php echo htmlspecialchars($doc['title']); ?>
                                                    </a>
                                                </h6>
                                                <small class="text-muted">
                                                    <?php echo htmlspecialchars($doc['original_filename']); ?>
                                                </small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if ($doc['category_name']): ?>
                                            <span class="category-pill-sm" style="background-color: <?php echo $doc['category_color']; ?>">
                                                <?php echo htmlspecialchars($doc['category_name']); ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <small><?php echo htmlspecialchars($doc['full_name'] ?: $doc['username']); ?></small>
                                    </td>
                                    <td>
                                        <small><?php echo formatFileSize($doc['file_size']); ?></small>
                                    </td>
                                    <td>
                                        <small><?php echo number_format($doc['downloads']); ?></small>
                                    </td>
                                    <td>
                                        <small><?php echo date('M j, Y', strtotime($doc['created_at'])); ?></small>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm" role="group">
                                            <a href="api/download.php?id=<?php echo $doc['id']; ?>" 
                                               class="btn btn-outline-primary" title="Download">
                                                <i class="fas fa-download"></i>
                                            </a>
                                            <a href="view.php?id=<?php echo $doc['id']; ?>" 
                                               class="btn btn-outline-info" title="View">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <button type="button" class="btn btn-outline-secondary" 
                                                    onclick="copyLink('<?php echo getBaseUrl(); ?>/view.php?id=<?php echo $doc['id']; ?>')"
                                                    title="Copy Link">
                                                <i class="fas fa-link"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <div class="row mt-4">
                <div class="col-12">
                    <nav aria-label="Document pagination">
                        <ul class="pagination justify-content-center">
                            <!-- Previous Page -->
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>">
                                        <i class="fas fa-chevron-left"></i> Previous
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
                                        Next <i class="fas fa-chevron-right"></i>
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                    
                    <!-- Pagination Info -->
                    <div class="text-center text-muted">
                        Showing <?php echo number_format(($page - 1) * $per_page + 1); ?> to 
                        <?php echo number_format(min($page * $per_page, $total_documents)); ?> of 
                        <?php echo number_format($total_documents); ?> documents
                    </div>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<script>
// View toggle functionality
document.getElementById('gridView').addEventListener('change', function() {
    if (this.checked) {
        document.querySelector('.documents-grid').classList.remove('d-none');
        document.querySelector('.documents-list').classList.add('d-none');
        localStorage.setItem('browse_view', 'grid');
    }
});

document.getElementById('listView').addEventListener('change', function() {
    if (this.checked) {
        document.querySelector('.documents-grid').classList.add('d-none');
        document.querySelector('.documents-list').classList.remove('d-none');
        localStorage.setItem('browse_view', 'list');
    }
});

// Restore saved view preference
document.addEventListener('DOMContentLoaded', function() {
    const savedView = localStorage.getItem('browse_view');
    if (savedView === 'list') {
        document.getElementById('listView').checked = true;
        document.querySelector('.documents-grid').classList.add('d-none');
        document.querySelector('.documents-list').classList.remove('d-none');
    }
});

// Auto-submit form on filter change
document.querySelectorAll('select[name="category"], select[name="sort"], input[name="order"]').forEach(function(element) {
    element.addEventListener('change', function() {
        this.form.submit();
    });
});

// Search input enhancement
const searchInput = document.querySelector('input[name="search"]');
if (searchInput) {
    let searchTimeout;
    searchInput.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            if (this.value.length >= 3 || this.value.length === 0) {
                // Auto-submit after 1 second of no typing
                // this.form.submit();
            }
        }, 1000);
    });
}
</script>

<?php require_once 'includes/footer.php'; ?>
