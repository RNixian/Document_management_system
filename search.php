<?php
$page_title = 'Search Files';
require_once 'includes/functions.php';
requireLogin();

// Get search parameters
$query = isset($_GET['q']) ? sanitize($_GET['q']) : '';
$category = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$file_type = isset($_GET['type']) ? sanitize($_GET['type']) : '';
$sort = isset($_GET['sort']) ? sanitize($_GET['sort']) : 'relevance';

$results = [];
$total_results = 0;

// Perform search if query is provided
if (!empty($query) && strlen($query) >= 2) {
    try {
        // Build search query
        $where_conditions = ['user_id = ?'];
        $params = [$_SESSION['user_id']];
        
        // Add search condition
        $where_conditions[] = '(title LIKE ? OR description LIKE ? OR tags LIKE ? OR original_name LIKE ?)';
        $search_param = "%$query%";
        $params[] = $search_param;
        $params[] = $search_param;
        $params[] = $search_param;
        $params[] = $search_param;
        
        // Add category filter
        if ($category > 0) {
            $where_conditions[] = 'category_id = ?';
            $params[] = $category;
        }
        
        // Add file type filter
        if (!empty($file_type)) {
            $where_conditions[] = 'file_type = ?';
            $params[] = $file_type;
        }
        
        $where_clause = implode(' AND ', $where_conditions);
        
        // Determine sort order
        $order_clause = 'created_at DESC'; // default
        switch ($sort) {
            case 'name':
                $order_clause = 'title ASC';
                break;
            case 'date':
                $order_clause = 'created_at DESC';
                break;
            case 'size':
                $order_clause = 'file_size DESC';
                break;
            case 'downloads':
                $order_clause = 'downloads DESC';
                break;
        }
        
        // Get results
        $sql = "
            SELECT d.*, c.name as category_name, c.color as category_color 
            FROM documents d 
            LEFT JOIN categories c ON d.category_id = c.id 
            WHERE $where_clause 
            ORDER BY $order_clause
        ";
        
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $results = $stmt->fetchAll();
        $total_results = count($results);
        
    } catch (PDOException $e) {
        setAlert('Error performing search', 'danger');
    }
}

// Get categories for filter
try {
    $stmt = $db->query("SELECT * FROM categories ORDER BY name");
    $categories = $stmt->fetchAll();
} catch (PDOException $e) {
    $categories = [];
}

// Get file types for filter
try {
    $stmt = $db->prepare("SELECT DISTINCT file_type FROM documents WHERE user_id = ? ORDER BY file_type");
    $stmt->execute([$_SESSION['user_id']]);
    $file_types = $stmt->fetchAll();
} catch (PDOException $e) {
    $file_types = [];
}

require_once 'includes/header.php';
?>

<div class="container-fluid px-4">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                <div class="card-body py-4">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h1 class="display-6 fw-bold mb-2">
                                <i class="fas fa-search me-3"></i>Search Files
                            </h1>
                            <p class="lead mb-0">
                                Find your documents quickly by name, content, or tags
                            </p>
                        </div>
                        <div class="col-md-4 text-end">
                            <a href="dashboard.php" class="btn btn-light btn-lg">
                                <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Search Form -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-filter me-2"></i>Search & Filter
                    </h5>
                </div>
                <div class="card-body">
                    <form method="GET" action="" class="row g-3">
                        <!-- Search Query -->
                        <div class="col-md-4">
                            <label for="search_query" class="form-label">Search Query</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-search"></i></span>
                                <input type="text" class="form-control" id="search_query" name="q" 
                                       placeholder="Enter keywords..." value="<?php echo htmlspecialchars($query); ?>" 
                                       required minlength="2">
                            </div>
                            <small class="text-muted">Search in titles, descriptions, tags, and filenames</small>
                        </div>
                        
                        <!-- Category Filter -->
                        <div class="col-md-3">
                            <label for="category_filter" class="form-label">Category</label>
                            <select class="form-select" id="category_filter" name="category">
                                <option value="0">All Categories</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo $cat['id']; ?>" 
                                            <?php echo $category == $cat['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($cat['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <!-- File Type Filter -->
                        <div class="col-md-2">
                            <label for="type_filter" class="form-label">File Type</label>
                            <select class="form-select" id="type_filter" name="type">
                                <option value="">All Types</option>
                                <?php foreach ($file_types as $type): ?>
                                    <option value="<?php echo $type['file_type']; ?>" 
                                            <?php echo $file_type === $type['file_type'] ? 'selected' : ''; ?>>
                                        <?php echo strtoupper($type['file_type']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <!-- Sort By -->
                        <div class="col-md-2">
                            <label for="sort_filter" class="form-label">Sort By</label>
                            <select class="form-select" id="sort_filter" name="sort">
                                <option value="relevance" <?php echo $sort === 'relevance' ? 'selected' : ''; ?>>Relevance</option>
                                <option value="date" <?php echo $sort === 'date' ? 'selected' : ''; ?>>Date</option>
                                <option value="name" <?php echo $sort === 'name' ? 'selected' : ''; ?>>Name</option>
                                <option value="size" <?php echo $sort === 'size' ? 'selected' : ''; ?>>Size</option>
                                <option value="downloads" <?php echo $sort === 'downloads' ? 'selected' : ''; ?>>Downloads</option>
                            </select>
                        </div>
                        
                        <!-- Search Button -->
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

    <!-- Search Results -->
    <?php if (!empty($query)): ?>
        <div class="row mb-3">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <?php if ($total_results > 0): ?>
                            Found <?php echo number_format($total_results); ?> result<?php echo $total_results !== 1 ? 's' : ''; ?> 
                            for "<?php echo htmlspecialchars($query); ?>"
                        <?php else: ?>
                            No results found for "<?php echo htmlspecialchars($query); ?>"
                        <?php endif; ?>
                    </h5>
                    <?php if (!empty($query) || $category > 0 || !empty($file_type)): ?>
                        <a href="search.php" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-times me-1"></i>Clear Filters
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <?php if (empty($results)): ?>
            <!-- No Results -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body text-center py-5">
                            <i class="fas fa-search" style="font-size: 4rem; color: #e9ecef;"></i>
                            <h4 class="mt-4 text-muted">No documents found</h4>
                            <p class="text-muted">Try adjusting your search terms or filters:</p>
                            <ul class="list-unstyled text-muted">
                                <li>• Check your spelling</li>
                                <li>• Use different keywords</li>
                                <li>• Remove some filters</li>
                                <li>• Try broader search terms</li>
                            </ul>
                            <div class="mt-4">
                                <a href="documents.php" class="btn btn-primary me-2">
                                    <i class="fas fa-folder-open me-2"></i>Browse All Documents
                                </a>
                                <a href="upload.php" class="btn btn-outline-primary">
                                    <i class="fas fa-plus me-2"></i>Upload New Document
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <!-- Search Results Grid -->
            <div class="document-grid">
                <?php foreach ($results as $doc): ?>
                    <div class="document-card" data-doc-id="<?php echo $doc['id']; ?>">
                        <div class="document-header">
                            <div class="document-icon">
                                <i class="<?php echo getFileIcon($doc['file_type']); ?>"></i>
                            </div>
                            <div class="dropdown">
                                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" 
                                        data-bs-toggle="dropdown">
                                    <i class="fas fa-ellipsis-v"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li>
                                        <a class="dropdown-item" href="api/download.php?id=<?php echo $doc['id']; ?>">
                                            <i class="fas fa-download me-2"></i>Download
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="view.php?id=<?php echo $doc['id']; ?>">
                                            <i class="fas fa-eye me-2"></i>View Details
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="edit.php?id=<?php echo $doc['id']; ?>">
                                            <i class="fas fa-edit me-2"></i>Edit
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                        
                        <div class="document-body">
                            <h6 class="document-title" title="<?php echo htmlspecialchars($doc['title']); ?>">
                                <?php 
                                // Highlight search terms in title
                                $highlighted_title = str_ireplace($query, '<mark>' . $query . '</mark>', htmlspecialchars($doc['title']));
                                echo $highlighted_title;
                                ?>
                            </h6>
                            
                            <?php if ($doc['description']): ?>
                                <p class="document-description">
                                    <?php 
                                    // Highlight search terms in description
                                    $description = htmlspecialchars(substr($doc['description'], 0, 100));
                                    $highlighted_desc = str_ireplace($query, '<mark>' . $query . '</mark>', $description);
                                    echo $highlighted_desc;
                                    echo strlen($doc['description']) > 100 ? '...' : '';
                                    ?>
                                </p>
                            <?php endif; ?>
                            
                            <div class="document-meta">
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
                                    foreach (array_slice($tags, 0, 3) as $tag): 
                                        $tag = trim($tag);
                                        if (!empty($tag)):
                                            // Highlight search terms in tags
                                                                                    $highlighted_tag = str_ireplace($query, '<mark>' . $query . '</mark>', htmlspecialchars($tag));
                                    ?>
                                        <span class="tag-pill"><?php echo $highlighted_tag; ?></span>
                                    <?php 
                                        endif;
                                    endforeach; 
                                    if (count($tags) > 3):
                                    ?>
                                        <span class="tag-pill">+<?php echo count($tags) - 3; ?> more</span>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($doc['is_public']): ?>
                                <div class="mt-2">
                                    <span class="badge bg-success">
                                        <i class="fas fa-globe me-1"></i>Public
                                    </span>
                                </div>
                            <?php else: ?>
                                <div class="mt-2">
                                    <span class="badge bg-secondary">
                                        <i class="fas fa-lock me-1"></i>Private
                                    </span>
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
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    <?php else: ?>
        <!-- Search Instructions -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body text-center py-5">
                        <i class="fas fa-search" style="font-size: 5rem; color: #e9ecef;"></i>
                        <h3 class="mt-4 text-muted">Search Your Documents</h3>
                        <p class="text-muted mb-4">Enter keywords to find your files quickly</p>
                        
                        <div class="row justify-content-center">
                            <div class="col-md-8">
                                <div class="card bg-light">
                                    <div class="card-body">
                                        <h6 class="card-title">
                                            <i class="fas fa-lightbulb me-2"></i>Search Tips
                                        </h6>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <ul class="list-unstyled text-start">
                                                    <li><i class="fas fa-check text-success me-2"></i>Search by document title</li>
                                                    <li><i class="fas fa-check text-success me-2"></i>Search by description</li>
                                                    <li><i class="fas fa-check text-success me-2"></i>Search by tags</li>
                                                </ul>
                                            </div>
                                            <div class="col-md-6">
                                                <ul class="list-unstyled text-start">
                                                    <li><i class="fas fa-check text-success me-2"></i>Search by filename</li>
                                                    <li><i class="fas fa-check text-success me-2"></i>Filter by category</li>
                                                    <li><i class="fas fa-check text-success me-2"></i>Filter by file type</li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<style>
mark {
    background-color: #fff3cd;
    padding: 1px 2px;
    border-radius: 2px;
}

.document-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 1.5rem;
    margin-block-start: 2rem;
}

.document-card {
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    transition: all 0.3s ease;
    border-inline-start: 4px solid var(--primary-color);
}

.document-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
}

.document-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-block-end: 1rem;
}

.document-icon {
    font-size: 2.5rem;
}

.document-title {
    font-weight: 600;
    margin-block-end: 0.5rem;
    color: var(--dark-color);
}

.document-description {
    color: #6b7280;
    font-size: 0.9rem;
    margin-block-end: 1rem;
}

.document-meta {
    font-size: 0.875rem;
    color: #6b7280;
    margin-block-end: 1rem;
}

.category-pill {
    display: inline-block;
    padding: 0.25rem 0.75rem;
    border-radius: 50px;
    font-size: 0.75rem;
    font-weight: 500;
    color: white;
    text-decoration: none;
}

.tag-pill {
    display: inline-block;
    padding: 0.25rem 0.5rem;
    background: #f3f4f6;
    border-radius: 15px;
    font-size: 0.75rem;
    color: #374151;
    margin: 0.125rem;
}

.document-footer {
    margin-block-start: 1rem;
    padding-block-start: 1rem;
    border-block-start: 1px solid #e5e7eb;
}
</style>

<script>
// Auto-focus search input
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('search_query');
    if (searchInput && !searchInput.value) {
        searchInput.focus();
    }
});

// Copy link function
function copyLink(url) {
    navigator.clipboard.writeText(url).then(() => {
        showAlert('Link copied to clipboard!', 'success');
    }).catch(() => {
        showAlert('Failed to copy link.', 'danger');
    });
}

// Show alert function
function showAlert(message, type = 'info') {
    const alertContainer = document.createElement('div');
    alertContainer.className = `alert alert-${type} alert-dismissible fade show`;
    alertContainer.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    const container = document.querySelector('.container-fluid');
    if (container) {
        container.insertBefore(alertContainer, container.firstChild);
        
        setTimeout(() => {
            if (alertContainer.parentNode) {
                alertContainer.remove();
            }
        }, 5000);
    }
}
</script>

<?php require_once 'includes/footer.php'; ?>

