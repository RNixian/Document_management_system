<?php
$page_title = 'System Settings';
require_once '../includes/functions.php';
requireLogin();
requireSuperAdmin();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_settings') {
        $settings = [
            'site_name' => sanitize($_POST['site_name'] ?? ''),
            'site_description' => sanitize($_POST['site_description'] ?? ''),
            'max_file_size' => (int)($_POST['max_file_size'] ?? 10),
            'allowed_extensions' => sanitize($_POST['allowed_extensions'] ?? ''),
            'require_approval' => isset($_POST['require_approval']) ? 1 : 0,
            'allow_public_uploads' => isset($_POST['allow_public_uploads']) ? 1 : 0,
            'enable_downloads' => isset($_POST['enable_downloads']) ? 1 : 0,
            'maintenance_mode' => isset($_POST['maintenance_mode']) ? 1 : 0
        ];
        
        try {
            // Check if settings table exists, create if not
            $stmt = $db->query("SHOW TABLES LIKE 'settings'");
            if ($stmt->rowCount() == 0) {
                // Create settings table
                $createTable = "
                    CREATE TABLE `settings` (
                        `id` int(11) NOT NULL AUTO_INCREMENT,
                        `setting_key` varchar(100) NOT NULL,
                        `setting_value` text DEFAULT NULL,
                        `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
                        `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
                        PRIMARY KEY (`id`),
                        UNIQUE KEY `setting_key` (`setting_key`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
                ";
                $db->exec($createTable);
            }
            
            foreach ($settings as $key => $value) {
                $stmt = $db->prepare("
                    INSERT INTO settings (setting_key, setting_value) 
                    VALUES (?, ?) 
                    ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)
                ");
                $stmt->execute([$key, $value]);
            }
            
            setAlert('Settings updated successfully!', 'success');
        } catch (PDOException $e) {
            error_log("Settings update error: " . $e->getMessage());
            setAlert('Error updating settings: ' . $e->getMessage(), 'danger');
        }
        
        header('Location: settings.php');
        exit();
    }
}

// Get current settings
$current_settings = [];
try {
    // Check if settings table exists
    $stmt = $db->query("SHOW TABLES LIKE 'settings'");
    if ($stmt->rowCount() > 0) {
        $stmt = $db->query("SELECT setting_key, setting_value FROM settings");
        while ($row = $stmt->fetch()) {
            $current_settings[$row['setting_key']] = $row['setting_value'];
        }
    }
} catch (PDOException $e) {
    error_log("Settings fetch error: " . $e->getMessage());
}

// Default values
$defaults = [
    'site_name' => 'PTNI4 Document Management System',
    'site_description' => 'A secure platform for managing and sharing documents',
    'max_file_size' => 10,
    'allowed_extensions' => 'pdf,doc,docx,txt,jpg,jpeg,png,gif,zip,rar',
    'require_approval' => 0,
    'allow_public_uploads' => 1,
    'enable_downloads' => 1,
    'maintenance_mode' => 0
];

// Merge with current settings
$settings = array_merge($defaults, $current_settings);

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
                            <i class="fas fa-cogs me-2"></i>System Settings
                        </h1>
                        <p class="text-white mb-0">Configure system preferences and options</p>
                    </div>
                    <div>
                        <a href="dashboard.php" class="btn btn-light">
                            <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php displayAlert(); ?>

    <!-- Settings Form -->
    <div class="row">
        <div class="col-12">
            <form method="POST" action="">
                <input type="hidden" name="action" value="update_settings">
                
                <!-- General Settings -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-globe me-2"></i>General Settings
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="site_name" class="form-label">Site Name</label>
                                    <input type="text" class="form-control" id="site_name" name="site_name" 
                                           value="<?php echo htmlspecialchars($settings['site_name']); ?>" required>
                                    <div class="form-text">The name of your document management system</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="site_description" class="form-label">Site Description</label>
                                    <input type="text" class="form-control" id="site_description" name="site_description" 
                                           value="<?php echo htmlspecialchars($settings['site_description']); ?>">
                                    <div class="form-text">Brief description of your site</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- File Upload Settings -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-upload me-2"></i>File Upload Settings
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="max_file_size" class="form-label">Maximum File Size (MB)</label>
                                    <input type="number" class="form-control" id="max_file_size" name="max_file_size" 
                                           value="<?php echo $settings['max_file_size']; ?>" min="1" max="100" required>
                                    <div class="form-text">
                                        Maximum allowed file size for uploads
                                        <br><small class="text-muted">Server limit: <?php echo ini_get('upload_max_filesize'); ?></small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="allowed_extensions" class="form-label">Allowed File Extensions</label>
                                    <input type="text" class="form-control" id="allowed_extensions" name="allowed_extensions" 
                                           value="<?php echo htmlspecialchars($settings['allowed_extensions']); ?>" required>
                                    <div class="form-text">Comma-separated list of allowed file extensions (e.g., pdf,doc,jpg)</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-12">
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" id="allow_public_uploads" 
                                           name="allow_public_uploads" <?php echo $settings['allow_public_uploads'] ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="allow_public_uploads">
                                        Allow users to upload public documents
                                    </label>
                                    <div class="form-text">When enabled, users can make their documents publicly accessible</div>
                                </div>
                                
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" id="require_approval" 
                                           name="require_approval" <?php echo $settings['require_approval'] ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="require_approval">
                                        Require admin approval for uploads
                                    </label>
                                    <div class="form-text">When enabled, all uploads must be approved by an admin before being visible</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Download Settings -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-download me-2"></i>Download Settings
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="enable_downloads" 
                                   name="enable_downloads" <?php echo $settings['enable_downloads'] ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="enable_downloads">
                                Enable file downloads
                            </label>
                            <div class="form-text">When disabled, users can only view documents but not download them</div>
                        </div>
                    </div>
                </div>

                <!-- System Settings -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-server me-2"></i>System Settings
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="maintenance_mode" 
                                   name="maintenance_mode" <?php echo $settings['maintenance_mode'] ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="maintenance_mode">
                                <span class="text-warning">
                                    <i class="fas fa-exclamation-triangle me-1"></i>
                                    Enable maintenance mode
                                </span>
                            </label>
                            <div class="form-text">
                                When enabled, only administrators can access the system. 
                                Regular users will see a maintenance message.
                            </div>
                        </div>
                        
                        <?php if ($settings['maintenance_mode']): ?>
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <strong>Warning:</strong> Maintenance mode is currently enabled. 
                                Regular users cannot access the system.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- System Information -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-info-circle me-2"></i>System Information
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <table class="table table-sm">
                                    <tr>
                                        <td><strong>PHP Version:</strong></td>
                                        <td><?php echo PHP_VERSION; ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Server Software:</strong></td>
                                        <td><?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'; ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Upload Max Filesize:</strong></td>
                                        <td><?php echo ini_get('upload_max_filesize'); ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Post Max Size:</strong></td>
                                        <td><?php echo ini_get('post_max_size'); ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Memory Limit:</strong></td>
                                        <td><?php echo ini_get('memory_limit'); ?></td>
                                    </tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <table class="table table-sm">
                                    <tr>
                                        <td><strong>Upload Directory:</strong></td>
                                        <td>
                                            <?php if (is_writable('../uploads')): ?>
                                                <span class="text-success">
                                                    <i class="fas fa-check-circle me-1"></i>Writable
                                                </span>
                                            <?php else: ?>
                                                                                               <span class="text-danger">
                                                    <i class="fas fa-times-circle me-1"></i>Not Writable
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><strong>Database:</strong></td>
                                        <td>
                                            <span class="text-success">
                                                <i class="fas fa-check-circle me-1"></i>Connected
                                            </span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><strong>Session Status:</strong></td>
                                        <td>
                                            <span class="text-success">
                                                <i class="fas fa-check-circle me-1"></i>Active
                                            </span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><strong>Disk Space:</strong></td>
                                        <td>
                                            <?php 
                                            $bytes = disk_free_space('.');
                                            echo $bytes ? formatFileSize($bytes) . ' free' : 'Unknown';
                                            ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><strong>Server Time:</strong></td>
                                        <td><?php 
                                            date_default_timezone_set('Asia/Manila'); // GMT+8
                                            echo date('M j, Y h:i:s A'); 
                                        ?></td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Save Button -->
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <small class="text-muted">
                                    <i class="fas fa-info-circle me-1"></i>
                                    Changes will take effect immediately after saving.
                                </small>
                            </div>
                            <div>
                                <button type="button" class="btn btn-secondary me-2" onclick="resetForm()">
                                    <i class="fas fa-undo me-2"></i>Reset
                                </button>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Save Settings
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Database Management -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-database me-2"></i>Database Management
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="d-grid">
                                <button type="button" class="btn btn-outline-info" onclick="optimizeDatabase()">
                                    <i class="fas fa-tools me-2"></i>Optimize Database
                                </button>
                            </div>
                            <small class="text-muted mt-2 d-block">Optimize database tables for better performance</small>
                        </div>
                        <div class="col-md-4">
                            <div class="d-grid">
                                <button type="button" class="btn btn-outline-warning" onclick="clearCache()">
                                    <i class="fas fa-broom me-2"></i>Clear Cache
                                </button>
                            </div>
                            <small class="text-muted mt-2 d-block">Clear temporary files and cached data</small>
                        </div>
                        <div class="col-md-4">
                            <div class="d-grid">
                                <button type="button" class="btn btn-outline-success" onclick="backupDatabase()">
                                    <i class="fas fa-download me-2"></i>Backup Database
                                </button>
                            </div>
                            <small class="text-muted mt-2 d-block">Download a backup of your database</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Reset form to original values
function resetForm() {
    if (confirm('Are you sure you want to reset all changes?')) {
        location.reload();
    }
}

// Database optimization
function optimizeDatabase() {
    if (confirm('This will optimize all database tables. Continue?')) {
        const btn = event.target;
        const originalText = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Optimizing...';
        btn.disabled = true;
        
        fetch('ajax/optimize_database.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Database optimized successfully!');
            } else {
                alert('Error optimizing database: ' + data.message);
            }
        })
        .catch(error => {
            alert('Error: ' + error.message);
        })
        .finally(() => {
            btn.innerHTML = originalText;
            btn.disabled = false;
        });
    }
}

// Clear cache
function clearCache() {
    if (confirm('This will clear all cached data. Continue?')) {
        const btn = event.target;
        const originalText = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Clearing...';
        btn.disabled = true;
        
        fetch('ajax/clear_cache.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Cache cleared successfully!');
            } else {
                alert('Error clearing cache: ' + data.message);
            }
        })
        .catch(error => {
            alert('Error: ' + error.message);
        })
        .finally(() => {
            btn.innerHTML = originalText;
            btn.disabled = false;
        });
    }
}

// Backup database
function backupDatabase() {
    if (confirm('This will create a database backup. Continue?')) {
        const btn = event.target;
        const originalText = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Creating Backup...';
        btn.disabled = true;
        
        // Create a temporary form to download the backup
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'ajax/backup_database.php';
        form.style.display = 'none';
        document.body.appendChild(form);
        form.submit();
        document.body.removeChild(form);
        
        // Reset button after a delay
        setTimeout(() => {
            btn.innerHTML = originalText;
            btn.disabled = false;
        }, 3000);
    }
}

// Form validation with better server limit handling
document.querySelector('form').addEventListener('submit', function(e) {
    const maxFileSize = parseInt(document.getElementById('max_file_size').value);
    const serverLimitStr = '<?php echo ini_get('upload_max_filesize'); ?>';
    
    // Convert server limit to MB
    let serverLimitMB = parseInt(serverLimitStr);
    if (serverLimitStr.toLowerCase().includes('g')) {
        serverLimitMB = parseInt(serverLimitStr) * 1024;
    }
    
    if (maxFileSize > serverLimitMB) {
        e.preventDefault();
        alert(`Maximum file size cannot exceed server limit of ${serverLimitStr}`);
        return false;
    }
    
    // Show loading state
    const submitBtn = this.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Saving...';
    submitBtn.disabled = true;
    
    // Re-enable button if there's an error (form doesn't submit)
    setTimeout(() => {
        if (submitBtn.disabled) {
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        }
    }, 5000);
});

// Real-time validation for file extensions
document.getElementById('allowed_extensions').addEventListener('input', function() {
    const value = this.value;
    const extensions = value.split(',').map(ext => ext.trim().toLowerCase());
    const validExtensions = extensions.filter(ext => /^[a-z0-9]+$/.test(ext));
    
    if (extensions.length !== validExtensions.length) {
        this.setCustomValidity('Please enter valid file extensions (letters and numbers only, separated by commas)');
    } else {
        this.setCustomValidity('');
    }
});

// Maintenance mode warning
document.getElementById('maintenance_mode').addEventListener('change', function() {
    if (this.checked) {
        if (!confirm('WARNING: Enabling maintenance mode will prevent regular users from accessing the system. Only administrators will be able to log in. Continue?')) {
            this.checked = false;
        }
    }
});

// Auto-save draft functionality
let autoSaveTimer;
const formInputs = document.querySelectorAll('input, textarea, select');

formInputs.forEach(input => {
    input.addEventListener('input', function() {
        clearTimeout(autoSaveTimer);
        autoSaveTimer = setTimeout(saveDraft, 2000);
    });
});

function saveDraft() {
    const formData = new FormData(document.querySelector('form'));
    const draftData = {};
    
    for (let [key, value] of formData.entries()) {
        if (key !== 'action') {
            draftData[key] = value;
        }
    }
    
    localStorage.setItem('settings_draft', JSON.stringify(draftData));
    
    // Show draft saved indicator
    const indicator = document.createElement('div');
    indicator.className = 'alert alert-info alert-dismissible fade show position-fixed';
    indicator.style.cssText = 'top: 20px; right: 20px; z-index: 9999; width: auto;';
    indicator.innerHTML = `
        <i class="fas fa-save me-2"></i>Draft saved automatically
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    document.body.appendChild(indicator);
    
    setTimeout(() => {
        if (indicator.parentNode) {
            indicator.remove();
        }
    }, 3000);
}

// Load draft on page load
window.addEventListener('load', function() {
    const draft = localStorage.getItem('settings_draft');
    if (draft) {
        try {
            const draftData = JSON.parse(draft);
            let hasChanges = false;
            
            for (let [key, value] of Object.entries(draftData)) {
                const input = document.querySelector(`[name="${key}"]`);
                if (input && input.value !== value) {
                    hasChanges = true;
                    break;
                }
            }
            
            if (hasChanges && confirm('A draft of unsaved changes was found. Would you like to restore it?')) {
                for (let [key, value] of Object.entries(draftData)) {
                    const input = document.querySelector(`[name="${key}"]`);
                    if (input) {
                        if (input.type === 'checkbox') {
                            input.checked = value === 'on';
                        } else {
                            input.value = value;
                        }
                    }
                }
            }
        } catch (e) {
            console.error('Error loading draft:', e);
        }
    }
});

// Clear draft on successful save
document.querySelector('form').addEventListener('submit', function() {
    localStorage.removeItem('settings_draft');
});
</script>

<style>
.admin-header {
   background: linear-gradient(90deg, #0b257cff 0%, #010b33ff 100%);
    color: white;
    padding: 2rem;
    border-radius: 10px;
    margin-bottom: 2rem;
}

.card {
    border: none;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    border-radius: 10px;
    margin-bottom: 1.5rem;
}

.card-header {
    background: #f8f9fa;
    border-bottom: 1px solid #e9ecef;
    border-radius: 10px 10px 0 0 !important;
    padding: 1rem 1.5rem;
}

.card-header h5 {
    color: #2c3e50;
    font-weight: 600;
}

.form-label {
    font-weight: 600;
    color: #2c3e50;
    margin-bottom: 0.5rem;
}

.form-control {
    border-radius: 8px;
    border: 1px solid #e1e5e9;
    padding: 0.75rem 1rem;
    transition: all 0.2s ease;
}

.form-control:focus {
    border-color: #667eea;
    box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
}

.form-check-input {
    border-radius: 4px;
    border: 2px solid #e1e5e9;
}

.form-check-input:checked {
    background-color: #667eea;
    border-color: #667eea;
}

.form-check-input:focus {
    border-color: #667eea;
    box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
}

.form-text {
    color: #6c757d;
    font-size: 0.875rem;
    margin-top: 0.25rem;
}

.btn {
    border-radius: 8px;
    font-weight: 500;
    padding: 0.75rem 1.5rem;
    transition: all 0.2s ease;
}

.btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.btn-primary {
   background: linear-gradient(90deg, #0b257cff 0%, #010b33ff 100%);
    color: white;
    border: none;
}

.btn-primary:hover {
   background: linear-gradient(90deg, #0b257cff 0%, #010b33ff 100%);
    color: white;
}

.table {
    margin-bottom: 0;
}

.table td {
    border-top: 1px solid #f1f3f4;
    padding: 0.75rem 0.5rem;
    vertical-align: middle;
}

.table td:first-child {
    font-weight: 500;
    color: #2c3e50;
    width: 40%;
}

.alert {
    border: none;
    border-radius: 8px;
    padding: 1rem 1.25rem;
}

.alert-warning {
    background-color: rgba(255, 193, 7, 0.1);
    color: #664d03;
    border-left: 4px solid #ffc107;
}

.text-success i, .text-danger i {
    font-size: 0.9rem;
}

/* Loading states */
.btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

/* Custom validation styles */
.form-control:invalid {
    border-color: #dc3545;
}

.form-control:invalid:focus {
    border-color: #dc3545;
    box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
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
    
    .card-body {
        padding: 1rem;
    }
    
    .btn {
        padding: 0.5rem 1rem;
        font-size: 0.9rem;
    }
    
    .table-responsive {
        font-size: 0.875rem;
    }
}

/* Animation for alerts */
@keyframes slideInRight {
    from {
        transform: translateX(100%);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}

.position-fixed.alert {
    animation: slideInRight 0.3s ease-out;
}

/* Maintenance mode warning styling */
.form-check-label .text-warning {
    font-weight: 600;
}

/* Database management section */
.d-grid .btn {
    padding: 1rem;
    font-weight: 600;
}

.d-grid .btn i {
    font-size: 1.1rem;
}

/* Draft indicator */
.alert.position-fixed {
    max-width: 300px;
    font-size: 0.9rem;
}
</style>

<?php require_once '../includes/footer.php'; ?>
