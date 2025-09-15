<?php
require_once '../includes/functions.php';
requireLogin();
requireSuperAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username']);
    $email = sanitize($_POST['email']);
    $full_name = sanitize($_POST['full_name']);
    $password = $_POST['password'];
    $role = sanitize($_POST['role']);
    $status = sanitize($_POST['status']);
    
    // Validation
    $errors = [];
    
    if (strlen($username) < 3) {
        $errors[] = 'Username must be at least 3 characters long';
    }
    
    if (strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters long';
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email address';
    }
    
    if (!in_array($role, ['user', 'admin'])) {
        $errors[] = 'Invalid role selected';
    }
    
    if (!in_array($status, ['active', 'inactive'])) {
        $errors[] = 'Invalid status selected';
    }
    
    // Check if username or email already exists
    try {
        $stmt = $db->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        if ($stmt->fetch()) {
            $errors[] = 'Username or email already exists';
        }
    } catch (PDOException $e) {
        $errors[] = 'Database error: ' . $e->getMessage();
    }
    
    if (empty($errors)) {
        try {
            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert user
            $stmt = $db->prepare("
                INSERT INTO users (username, email, password, full_name, role, status, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, NOW())
            ");
            
            $stmt->execute([
                $username,
                $email,
                $hashed_password,
                $full_name,
                $role,
                $status
            ]);
            
            setAlert("User '$username' created successfully", 'success');
            logActivity('User Created', "Created user: $username ($email)");
            
        } catch (PDOException $e) {
            setAlert('Error creating user: ' . $e->getMessage(), 'danger');
        }
    } else {
        setAlert(implode('<br>', $errors), 'danger');
    }
}

header('Location: users.php');
exit();
?>
