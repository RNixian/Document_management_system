<?php
require_once '../../includes/functions.php';
requireLogin();
requireAdmin();

// Get filter parameters
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

try {
    $sql = "
        SELECT 
            al.id,
            al.created_at,
            al.action,
            al.details,
            al.ip_address,
            u.username as admin_username,
            u.full_name as admin_name,
            tu.username as target_username,
            tu.full_name as target_name
        FROM admin_logs al
        LEFT JOIN users u ON al.admin_id = u.id
        LEFT JOIN users tu ON al.target_user_id = tu.id
        $where_clause
        ORDER BY al.created_at DESC
    ";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Generate filename
    $filename = 'system_logs_' . date('Y-m-d_H-i-s') . '.csv';
    
    // Set headers for CSV download
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    
    // Create file pointer
    $output = fopen('php://output', 'w');
    
    // Add CSV headers
    fputcsv($output, [
        'ID',
        'Date/Time',
        'Action',
        'Admin Username',
        'Admin Name',
        'Target Username',
        'Target Name',
        'IP Address',
        'Details'
    ]);
    
    // Add data rows
    foreach ($logs as $log) {
        fputcsv($output, [
            $log['id'],
            $log['created_at'],
            $log['action'],
            $log['admin_username'] ?? '',
            $log['admin_name'] ?? '',
            $log['target_username'] ?? '',
            $log['target_name'] ?? '',
            $log['ip_address'] ?? '',
            $log['details'] ?? ''
        ]);
    }
    
    // Log this export action
    logAdminAction(
        $_SESSION['user_id'], 
        'export_logs', 
        "Exported " . count($logs) . " log entries to CSV"
    );
    
    fclose($output);
    
} catch (PDOException $e) {
    header('Content-Type: text/plain');
    echo "Error exporting logs: " . $e->getMessage();
}
?>
