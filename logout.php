<?php
// Start session
session_start();

// Clear all session variables
session_unset();

// Destroy the session
session_destroy();

// Clear session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

// Redirect to login with success message
header('Location: login.php?logout=success');
exit();
?>
