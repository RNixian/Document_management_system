<?php
require_once '../includes/functions.php';
requireLogin();
requireAdmin();

header('Content-Type: application/json');

try {
    $action = $_POST['action'] ?? 'list';
    
    switch ($action) {
        case 'list':
            // Get all divisions
            $stmt = $db->query("SELECT * FROM division ORDER BY name");
            $divisions = $stmt->fetchAll();
            
            echo json_encode([
                'success' => true,
                'divisions' => $divisions
            ]);
            break;
            
        case 'add':
        case 'create':
            // Add new division
            $name = trim($_POST['name'] ?? '');
            $description = trim($_POST['description'] ?? '');
            
            if (empty($name)) {
                throw new Exception('Division name is required');
            }
            
            // Check if division already exists
            $stmt = $db->prepare("SELECT id FROM division WHERE name = ?");
            $stmt->execute([$name]);
            if ($stmt->fetch()) {
                throw new Exception('Division with this name already exists');
            }
            
            // Insert new division
            $stmt = $db->prepare("INSERT INTO division (name, description) VALUES (?, ?)");
            $stmt->execute([$name, $description]);
            
            $divisionId = $db->lastInsertId();
            
            // Get the created division
            $stmt = $db->prepare("SELECT * FROM division WHERE id = ?");
            $stmt->execute([$divisionId]);
            $division = $stmt->fetch();
            
            echo json_encode([
                'success' => true,
                'message' => 'Division created successfully',
                'division' => $division
            ]);
            break;
            
        case 'update':
            // Update division
            $id = (int)($_POST['id'] ?? 0);
            $name = trim($_POST['name'] ?? '');
            $description = trim($_POST['description'] ?? '');
            
            if ($id <= 0) {
                throw new Exception('Invalid division ID');
            }
            
            if (empty($name)) {
                throw new Exception('Division name is required');
            }
            
            // Check if division exists
            $stmt = $db->prepare("SELECT id FROM division WHERE id = ?");
            $stmt->execute([$id]);
            if (!$stmt->fetch()) {
                throw new Exception('Division not found');
            }
            
            // Check if name already exists (excluding current division)
            $stmt = $db->prepare("SELECT id FROM division WHERE name = ? AND id != ?");
            $stmt->execute([$name, $id]);
            if ($stmt->fetch()) {
                throw new Exception('Division with this name already exists');
            }
            
            // Update division
            $stmt = $db->prepare("UPDATE division SET name = ?, description = ? WHERE id = ?");
            $stmt->execute([$name, $description, $id]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Division updated successfully'
            ]);
            break;
            
        case 'delete':
            // Delete division
            $id = (int)($_POST['id'] ?? 0);
            
            if ($id <= 0) {
                throw new Exception('Invalid division ID');
            }
            
            // Check if division exists
            $stmt = $db->prepare("SELECT id FROM division WHERE id = ?");
            $stmt->execute([$id]);
            if (!$stmt->fetch()) {
                throw new Exception('Division not found');
            }
            
            // Check if division is being used by any documents
            $stmt = $db->prepare("SELECT COUNT(*) as count FROM documents WHERE division_id = ?");
            $stmt->execute([$id]);
            $result = $stmt->fetch();
            
            if ($result['count'] > 0) {
                // Don't delete, just set documents' division_id to NULL
                $stmt = $db->prepare("UPDATE documents SET division_id = NULL WHERE division_id = ?");
                $stmt->execute([$id]);
            }
            
            // Delete division
            $stmt = $db->prepare("DELETE FROM division WHERE id = ?");
            $stmt->execute([$id]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Division deleted successfully'
            ]);
            break;
            
        default:
            throw new Exception('Invalid action');
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
