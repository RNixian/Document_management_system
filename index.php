<?php
session_start();

// Simple auth check
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Include classes
require_once 'config/Database.php';
require_once 'classes/Document.php';
require_once 'classes/User.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    $document = new Document($db);
    $user = new User($db);
    
    // Get user info
    $currentUser = $user->getById($_SESSION['user_id']);
    
} catch (Exception $e) {
    die("Application error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modern OpenDocMan - Dashboard</title>
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link href="assets/css/beautiful.css" rel="stylesheet">
</head>
<body>
    <!-- Theme Toggle -->
    <button class="theme-toggle" title="Toggle Theme">
        <i class="fas fa-moon"></i>
    </button>

    <!-- Header -->
    <header class="header">
        <div class="header-content">
            <div class="logo">
                <i class="fas fa-file-alt"></i>
                <span>Modern OpenDocMan</span>
            </div>
            <nav>
                <ul class="nav-menu">
                    <li class="nav-item"><a href="#" class="active"><i class="fas fa-home"></i> Dashboard</a></li>
                    <li class="nav-item"><a href="upload.php"><i class="fas fa-upload"></i> Upload</a></li>
                    <li class="nav-item"><a href="#" onclick="docManager.handleFilter('favorites')"><i class="fas fa-heart"></i> Favorites</a></li>
                    <li class="nav-item"><a href="#"><i class="fas fa-chart-bar"></i> Reports</a></li>
                </ul>
            </nav>
            
            <div class="user-profile">
                <div class="user-avatar">
                    <?php echo strtoupper(substr($currentUser['first_name'] ?? 'U', 0, 1)); ?>
                </div>
                <div class="user-info">
                    <div style="font-weight: 600;"><?php echo htmlspecialchars($currentUser['first_name'] . ' ' . $currentUser['last_name']); ?></div>
                    <div style="font-size: 0.85rem; opacity: 0.8;"><?php echo htmlspecialchars($currentUser['role'] ?? 'User'); ?></div>
                </div>
                <a href="logout.php" class="btn" style="margin-inline-start: 1rem; padding: 8px 16px;">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
    </header>

    <!-- Main Container -->
    <div class="main-container">
        <!-- Stats Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon documents">
                    <i class="fas fa-file-alt"></i>
                </div>
                <div class="stat-number" id="totalDocs">0</div>
                <div class="stat-label">Total Documents</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon storage">
                    <i class="fas fa-hdd"></i>
                </div>
                <div class="stat-number" id="totalSize">0 MB</div>
                <div class="stat-label">Storage Used</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon downloads">
                    <i class="fas fa-download"></i>
                </div>
                <div class="stat-number" id="totalDownloads">0</div>
                <div class="stat-label">Total Downloads</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon users">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-number">1</div>
                <div class="stat-label">Active Users</div>
            </div>
        </div>

        <!-- Dashboard Grid -->
        <div class="dashboard-grid">
            <!-- Sidebar -->
            <aside class="sidebar">
                <h3><i class="fas fa-filter"></i> Quick Filters</h3>
                <ul class="sidebar-menu">
                    <li><a href="#" class="filter-btn active" data-filter="all">
                        <i class="fas fa-list"></i> All Documents
                    </a></li>
                    <li><a href="#" class="filter-btn" data-filter="recent">
                        <i class="fas fa-clock"></i> Recent
                    </a></li>
                    <li><a href="#" class="filter-btn" data-filter="favorites">
                        <i class="fas fa-heart"></i> Favorites
                    </a></li>
                    <li><a href="#" class="filter-btn" data-filter="images">
                        <i class="fas fa-image"></i> Images
                    </a></li>
                    <li><a href="#" class="filter-btn" data-filter="documents">
                        <i class="fas fa-file-pdf"></i> Documents
                    </a></li>
                </ul>

                <hr style="border: 1px solid rgba(255,255,255,0.1); margin: 2rem 0;">

                <h3><i class="fas fa-cog"></i> Quick Actions</h3>
                <ul class="sidebar-menu">
                    <li><a href="#" onclick="docManager.openUploadModal()">
                        <i class="fas fa-plus"></i> Upload File
                    </a></li>
                    <li><a href="#" onclick="docManager.handleFilter('recent')">
                        <i class="fas fa-search"></i> Search Files
                    </a></li>
                    <li><a href="#">
                        <i class="fas fa-trash"></i> Manage Files
                    </a></li>
                </ul>

                <!-- Keyboard Shortcuts -->
                <div style="margin-block-start: 2rem; padding: 1rem; background: rgba(255,255,255,0.05); border-radius: 12px;">
                    <h4 style="color: white; margin-block-end: 1rem; font-size: 0.9rem;">Keyboard Shortcuts</h4>
                    <div style="font-size: 0.8rem; color: rgba(255,255,255,0.7);">
                        <div><kbd>Ctrl+U</kbd> Upload</div>
                        <div><kbd>Ctrl+F</kbd> Search</div>
                        <div><kbd>Esc</kbd> Close Modal</div>
                    </div>
                </div>
            </aside>

            <!-- Main Content -->
            <main class="main-content">
                <!-- Search Bar -->
                <div class="search-container">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" id="searchInput" class="search-input" placeholder="Search documents... (Ctrl+F)">
                </div>

                <!-- Filter Buttons -->
                <div class="filters-container">
                    <button class="filter-btn active" data-filter="all">All</button>
                    <button class="filter-btn" data-filter="recent">Recent</button>
                    <button class="filter-btn" data-filter="favorites">Favorites</button>
                    <button class="filter-btn" data-filter="images">Images</button>
                    <button class="filter-btn" data-filter="documents">Documents</button>
                </div>

                <!-- Documents Grid -->
                <div id="documentsGrid" class="documents-grid">
                    <!-- Documents will be loaded here by JavaScript -->
                    <div class="glass-card" style="text-align: center; padding: 3rem;">
                        <div class="loading"></div>
                        <p style="color: rgba(255,255,255,0.7); margin-block-start: 1rem;">Loading documents...</p>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Floating Action Button -->
    <button class="fab" title="Upload Document (Ctrl+U)">
        <i class="fas fa-plus"></i>
    </button>

    <!-- Upload Modal -->
    <div id="uploadModal" class="modal">
        <div class="modal-content">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-block-end: 2rem;">
                <h2 style="color: white; margin: 0;">Upload Document</h2>
                <button onclick="docManager.hideModal('uploadModal')" style="background: none; border: none; color: white; font-size: 1.5rem; cursor: pointer;">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <!-- Drag & Drop Zone -->
            <div id="uploadZone" class="upload-zone">
                <div class="upload-icon">
                    <i class="fas fa-cloud-upload-alt"></i>
                </div>
                <div class="upload-text">Drag & Drop Files Here</div>
                <div class="upload-subtext">or click to browse</div>
                <input type="file" id="fileInput" multiple style="display: none;">
            </div>

            <!-- Progress Bar -->
            <div class="progress-container">
                <div class="progress-bar">
                    <div class="progress-fill"></div>
                </div>
            </div>

            <!-- Upload Form -->
            <form id="uploadForm" style="margin-block-start: 2rem;">
                <div class="form-group">
                    <label class="form-label">Category</label>
                    <select id="categorySelect" class="form-input">
                        <option value="1">General</option>
                        <option value="2">Documents</option>
                        <option value="3">Images</option>
                        <option value="4">Archives</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">
                        <input type="checkbox" id="publishableCheck" checked style="margin-inline-end: 8px;">
                        Make document public
                    </label>
                </div>

                <div style="display: flex; gap: 1rem; justify-content: flex-end;">
                    <button type="button" class="btn" onclick="docManager.hideModal('uploadModal')">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="document.getElementById('fileInput').click()">
                        <i class="fas fa-plus"></i> Select Files
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Preview Modal -->
    <div id="previewModal" class="modal">
        <div class="modal-content" style="max-inline-size: 800px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-block-end: 2rem;">
                <h2 style="color: white; margin: 0;">Document Preview</h2>
                <button onclick="docManager.hideModal('previewModal')" style="background: none; border: none; color: white; font-size: 1.5rem; cursor: pointer;">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div id="previewContent">
                <!-- Preview content will be loaded here -->
            </div>
        </div>
    </div>

    <!-- JavaScript -->
    <script src="assets/js/interactive.js"></script>
</body>
</html>
