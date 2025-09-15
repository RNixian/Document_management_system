<?php
require_once '../../includes/functions.php';
requireLogin();
requireAdmin();

try {
    // Database configuration (you may need to adjust these)
    $host = 'localhost';
    $username = 'root'; // Adjust as needed
    $password = ''; // Adjust as needed
    $database = 'ptni4'; // Your database name
    
    $filename = 'ptni4_backup_' . date('Y-m-d_H-i-s') . '.sql';
    
    // Create backup using mysqldump
    $command = "mysqldump --host=$host --user=$username --password=$password $database";
    
    if (empty($password)) {
        $command = "mysqldump --host=$host --user=$username $database";
    }
    
    $backup = shell_exec($command);
    
    if ($backup) {
        // Set headers for download
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($backup));
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        
        // Log the action
        if (function_exists('logAdminAction')) {
            logAdminAction($_SESSION['user_id'], 'backup_database', 'Database backup created: ' . $filename);
        }
        
        echo $backup;
    } else {
        // Fallback: PHP-based backup
        $backup = generatePHPBackup($db);
        
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($backup));
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        
        echo $backup;
    }
    
} catch (Exception $e) {
    header('Content-Type: text/plain');
    echo "Error creating backup: " . $e->getMessage();
}

function generatePHPBackup($db) {
    $backup = "-- PTNI4 Database Backup\n";
    $backup .= "-- Generated on: " . date('Y-m-d H:i:s') . "\n\n";
    $backup .= "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n";
    $backup .= "START TRANSACTION;\n";
    $backup .= "SET time_zone = \"+00:00\";\n\n";
    
    // Get all tables
    $stmt = $db->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    foreach ($tables as $table) {
        // Table structure
        $stmt = $db->query("SHOW CREATE TABLE `$table`");
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $backup .= "\n-- Table structure for table `$table`\n";
        $backup .= "DROP TABLE IF EXISTS `$table`;\n";
        $backup .= $row['Create Table'] . ";\n\n";
        
        // Table data
        $stmt = $db->query("SELECT * FROM `$table`");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (!empty($rows)) {
            $backup .= "-- Dumping data for table `$table`\n";
            $backup .= "INSERT INTO `$table` VALUES\n";
            
            $values = [];
            foreach ($rows as $row) {
                $rowValues = [];
                foreach ($row as $value) {
                    if ($value === null) {
                        $rowValues[] = 'NULL';
                    } else {
                        $rowValues[] = "'" . addslashes($value) . "'";
                    }
                }
                $values[] = '(' . implode(',', $rowValues) . ')';
            }
            
            $backup .= implode(",\n", $values) . ";\n\n";
        }
    }
    
    $backup .= "COMMIT;\n";
    
    return $backup;
}
?>
