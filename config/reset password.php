<?php
require_once 'config/database.php';

// Reset password for renz user
$username = 'renz';
$new_password = 'renz123'; // Change this to desired password
$hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

try {
    $stmt = $db->prepare("UPDATE users SET password = ? WHERE username = ?");
    $stmt->execute([$hashed_password, $username]);
    
    echo "Password updated successfully!<br>";
    echo "Username: $username<br>";
    echo "New Password: $new_password<br>";
    echo "Hash: $hashed_password<br>";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
