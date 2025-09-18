<?php
session_start();

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;


// Set default timezone to GMT+8
// Composer autoloader for PHPMailer
require_once __DIR__ . '/../vendor/autoload.php';
date_default_timezone_set('Asia/Manila');

// Fix path issue - check if we're in admin folder or root
$config_path = __DIR__ . '/../config/database.php';
if (!file_exists($config_path)) {
    // If not found, try from admin folder
    $config_path = __DIR__ . '/../../config/database.php';
    if (!file_exists($config_path)) {
        // Last resort - try relative path
        $config_path = 'config/database.php';
    }
}
require_once $config_path;

// Security function to sanitize input
function sanitize($input) {
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Check if user is admin (includes superadmin)
function isAdmin() {
    return isset($_SESSION['role']) && ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'superadmin');
}

// Check if user is superadmin
function isSuperAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'superadmin';
}

// Require login
function requireLogin() {
    if (!isLoggedIn()) {
        // Check if we're in admin folder
        $login_path = file_exists('../login.php') ? '../login.php' : 'login.php';
        header('Location: ' . $login_path);
        exit();
    }
}

// Require admin access (admin or superadmin)
function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        // Check if we're in admin folder
        $index_path = file_exists('../index.php') ? '../index.php' : 'index.php';
        header('Location: ' . $index_path);
        exit();
    }
}

// Require superadmin access only
function requireSuperAdmin() {
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'superadmin') {
        header('Location: ../login.php');
        exit();
    }
}

// Get user role display name
function getRoleDisplayName($role) {
    switch ($role) {
        case 'superadmin':
            return 'Super Administrator';
        case 'admin':
            return 'Administrator';
        case 'user':
            return 'User';
        default:
            return ucfirst($role);
    }
}

// Get role badge class
function getRoleBadgeClass($role) {
    switch ($role) {
        case 'superadmin':
            return 'bg-gradient-danger';
        case 'admin':
            return 'bg-danger';
        case 'user':
            return 'bg-primary';
        default:
            return 'bg-secondary';
    }
}

// Check if current user can manage another user
function canManageUser($target_user_role) {
    if (isSuperAdmin()) {
        return true; // Superadmin can manage everyone
    }
    
    if (isAdmin()) {
        return $target_user_role === 'user'; // Admin can only manage regular users
    }
    
    return false; // Regular users can't manage anyone
}

// Check if current user can delete another user
function canDeleteUser($target_user_id, $target_user_role) {
    // Can't delete yourself
    if ($target_user_id == $_SESSION['user_id']) {
        return false;
    }
    
    if (isSuperAdmin()) {
        return true; // Superadmin can delete anyone except themselves
    }
    
    if (isAdmin()) {
        return $target_user_role === 'user'; // Admin can only delete regular users
    }
    
    return false;
}

// Set alert message
function setAlert($message, $type = 'info') {
    $_SESSION['alert'] = [
        'message' => $message,
        'type' => $type
    ];
}

// Display alert message
function displayAlert() {
    if (isset($_SESSION['alert'])) {
        $alert = $_SESSION['alert'];
        echo '<div class="alert alert-' . $alert['type'] . ' alert-dismissible fade show" role="alert">';
        echo $alert['message'];
        echo '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
        echo '</div>';
        unset($_SESSION['alert']);
    }
}

// Format file size
function formatFileSize($bytes, $precision = 2) {
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    
    for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
        $bytes /= 1024;
    }
    
    return round($bytes, $precision) . ' ' . $units[$i];
}

// Time ago function
function timeAgo($datetime) {
    $time = time() - strtotime($datetime);
    
    if ($time < 60) return 'just now';
    if ($time < 3600) return floor($time/60) . ' minutes ago';
    if ($time < 86400) return floor($time/3600) . ' hours ago';
    if ($time < 2592000) return floor($time/86400) . ' days ago';
    if ($time < 31536000) return floor($time/2592000) . ' months ago';
    
    return floor($time/31536000) . ' years ago';
}

// Get file icon based on file extension
function getFileIcon($extension) {
    $extension = strtolower($extension);
    
    $icons = [
        // Documents
        'pdf' => 'fas fa-file-pdf text-danger',
        'doc' => 'fas fa-file-word text-primary',
        'docx' => 'fas fa-file-word text-primary',
        'xls' => 'fas fa-file-excel text-success',
        'xlsx' => 'fas fa-file-excel text-success',
        'ppt' => 'fas fa-file-powerpoint text-warning',
        'pptx' => 'fas fa-file-powerpoint text-warning',
        'txt' => 'fas fa-file-alt text-muted',
        
        // Images
        'jpg' => 'fas fa-file-image text-info',
        'jpeg' => 'fas fa-file-image text-info',
        'png' => 'fas fa-file-image text-info',
        'gif' => 'fas fa-file-image text-info',
        'bmp' => 'fas fa-file-image text-info',
        'svg' => 'fas fa-file-image text-info',
        
        // Archives
        'zip' => 'fas fa-file-archive text-secondary',
        'rar' => 'fas fa-file-archive text-secondary',
        '7z' => 'fas fa-file-archive text-secondary',
        'tar' => 'fas fa-file-archive text-secondary',
        
        // Audio
        'mp3' => 'fas fa-file-audio text-success',
        'wav' => 'fas fa-file-audio text-success',
        'flac' => 'fas fa-file-audio text-success',
        'aac' => 'fas fa-file-audio text-success',
        
        // Video
        'mp4' => 'fas fa-file-video text-purple',
        'avi' => 'fas fa-file-video text-purple',
        'mkv' => 'fas fa-file-video text-purple',
        'mov' => 'fas fa-file-video text-purple',
        'wmv' => 'fas fa-file-video text-purple',
    ];
    
    return $icons[$extension] ?? 'fas fa-file text-muted';
}

// Get base URL for the application
function getBaseUrl() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $script = $_SERVER['SCRIPT_NAME'];
    $path = dirname($script);
    
    return $protocol . '://' . $host . ($path === '/' ? '' : $path);
}

// Get setting value
function getSetting($key, $default = '') {
    global $db;
    try {
        $stmt = $db->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
        $stmt->execute([$key]);
        $result = $stmt->fetch();
        return $result ? $result['setting_value'] : $default;
    } catch (PDOException $e) {
        return $default;
    }
}

// Get the maximum file upload size from PHP configuration
function getMaxFileSize() {
    // Get upload_max_filesize and post_max_size from PHP config
    $upload_max = ini_get('upload_max_filesize');
    $post_max = ini_get('post_max_size');
    
    // Convert to bytes
    $upload_max_bytes = convertToBytes($upload_max);
    $post_max_bytes = convertToBytes($post_max);
    
    // Return the smaller of the two values
    return min($upload_max_bytes, $post_max_bytes);
}

// Convert PHP ini size format to bytes
function convertToBytes($size) {
    $size = trim($size);
    $last = strtolower($size[strlen($size) - 1]);
    $size = (int) $size;
    
    switch ($last) {
        case 'g':
            $size *= 1024;
        case 'm':
            $size *= 1024;
        case 'k':
            $size *= 1024;
    }
    
    return $size;
}

// Log admin actions for audit trail
function logAdminAction($admin_id, $action, $details = '', $target_user_id = null) {
    global $db;
    
    if (!isAdmin()) {
        return false;
    }
    
    try {
        $stmt = $db->prepare("
            INSERT INTO admin_logs (admin_id, action, details, target_user_id, ip_address, created_at)
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        
        $stmt->execute([
            $admin_id,
            $action,
            $details,
            $target_user_id,
            $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ]);
        
        return true;
    } catch (PDOException $e) {
        // Table might not exist, fail silently
        return false;
    }
}

// Get user's last login time
function getLastLogin($user_id) {
    global $db;
    try {
        $stmt = $db->prepare("SELECT last_login FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $result = $stmt->fetch();
        return $result ? $result['last_login'] : null;
    } catch (PDOException $e) {
        return null;
    }
}

function loginUser($usernameOrEmail, $password) {
    global $db;

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    try {
        if (filter_var($usernameOrEmail, FILTER_VALIDATE_EMAIL)) {
            if (!str_ends_with(strtolower($usernameOrEmail), '@ptni4.com')) {
                return false;
            }
            $stmt = $db->prepare("SELECT * FROM users WHERE email = ? AND status = 'active'");
        } else {
            $stmt = $db->prepare("SELECT * FROM users WHERE username = ? AND status = 'active'");
        }

        $stmt->execute([$usernameOrEmail]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);  // ✅ enforce assoc

        if ($user && password_verify($password, $user['password'])) {
            $newSession = bin2hex(random_bytes(16));
            $currentIp  = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

            // If another session exists and is different, notify
            if (!empty($user['last_session_id']) && $user['last_session_id'] !== $newSession) {
                sendMultipleLoginAlert($user['email'], $user['username'], $currentIp);
            }

            // Store session info
            $_SESSION['user_id']   = $user['id'];
            $_SESSION['username']  = $user['username'];
            $_SESSION['role']      = $user['role'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['session_id'] = $newSession; // ✅ track in session too

            $stmt = $db->prepare("
                UPDATE users 
                SET last_login = NOW(), last_session_id = ?, last_login_ip = ? 
                WHERE id = ?
            ");
            $stmt->execute([$newSession, $currentIp, $user['id']]);

            if (in_array($user['role'], ['admin', 'superadmin'])) {
                logAdminAction($user['id'], 'login', 'Admin logged in successfully');
            }

            return true;
        }

        return false;

    } catch (PDOException $e) {
        // Better: log the error
        error_log("Login error: " . $e->getMessage());
        return false;
    }
}


function sendMultipleLoginAlert($email, $username, $newIp) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'ptv.dms@gmail.com';
        $mail->Password   = 'nlikywhrmnhwqgec';
        $mail->SMTPSecure = 'ssl';
        $mail->Port       = 465;

        $mail->setFrom('ptv.dms@gmail.com', 'DMS');
        $mail->addAddress($email);

        $mail->isHTML(true);
        $mail->Subject = "⚠️ Multiple Login Detected for Account: $username";
        $mail->Body    = "
            <p>Hello $username,</p>
            <p>We noticed a new login to your account while another session was already active.</p>
            <p><b>New login IP:</b> $newIp</p>
            <p>If this was you, you can ignore this message. If not, we recommend changing your password immediately.</p>
        ";

        $mail->send();
    } catch (Exception $e) {
        error_log("Login alert email failed: {$mail->ErrorInfo}");
    }
}




function deleteFile($file_id, $admin_id) {
    global $db;
    
    try {
        // Get file details first
        $stmt = $db->prepare("SELECT * FROM files WHERE id = ?");
        $stmt->execute([$file_id]);
        $file = $stmt->fetch();
        
        if (!$file) {
            return false;
        }
        
        // Delete physical file
        $file_path = '../uploads/' . $file['filename'];
        if (file_exists($file_path)) {
            unlink($file_path);
        }
        
        // Delete from database
        $stmt = $db->prepare("DELETE FROM files WHERE id = ?");
        $stmt->execute([$file_id]);
        
        // Log the action
        logAdminAction(
            $admin_id,
            'delete_file',
            "Deleted file: {$file['original_name']} (ID: {$file_id})",
            $file['user_id']
        );
        
        return true;
        
    } catch (PDOException $e) {
        return false;
    }
}
?>
