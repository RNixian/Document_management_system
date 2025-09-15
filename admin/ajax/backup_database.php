<?php
require_once '../../includes/functions.php';
requireLogin();
requireAdmin();

try {
    // Create backups directory if it doesn't exist
    $backupDir = '../../backups';
    if (!is_dir($backupDir)) {
        mkdir($backupDir, 0755, true);
    }
    
    // Create backup filename
    $backupFile = 'backup_' . DB_NAME . '_' . date('Y-m-d_H-i-s') . '.sql';
    $backupPath = $backupDir . '/' . $backupFile;
    
    // Generate SQL dump
    $output = '';
    
    // Get all tables
    $stmt = $db->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $output .= "-- Database Backup\n";
    $output .= "-- Generated on: " . date('Y-m-d H:i:s') . "\n";
    $output .= "-- Database: " . DB_NAME . "\n\n";
    
    foreach ($tables as $table) {
        // Table structure
        $stmt = $db->query("SHOW CREATE TABLE `$table`");
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $output .= "-- Table structure for table `$table`\n";
        $output .= "DROP TABLE IF EXISTS `$table`;\n";
        $output .= $row['Create Table'] . ";\n\n";
        
        // Table data
        $stmt = $db->query("SELECT * FROM `$table`");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (!empty($rows)) {
            $output .= "-- Dumping data for table `$table`\n";
            
            foreach ($rows as $row) {
                $values = array_map(function($value) use ($db) {
                    return $value === null ? 'NULL' : $db->quote($value);
                }, array_values($row));
                
                $output .= "INSERT INTO `$table` VALUES (" . implode(', ', $values) . ");\n";
            }
            $output .= "\n";
        }
    }
    
    // Save backup file
    file_put_contents($backupPath, $output);
    
    // Set headers for download
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . $backupFile . '"');
    header('Content-Length: ' . filesize($backupPath));
    
    // Output file and delete
    readfile($backupPath);
    unlink($backupPath);
    
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
