<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - PTNI4' : 'PTNI4 - Document Management System'; ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <style>
        :root {
            --primary-color: #4e73df;
            --secondary-color: #858796;
            --success-color: #1cc88a;
            --info-color: #36b9cc;
            --warning-color: #f6c23e;
            --danger-color: #e74a3b;
            --light-color: #f8f9fc;
            --dark-color: #5a5c69;
        }

        body {
            font-family: 'Nunito', -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'Helvetica Neue', Arial, sans-serif;
            background-color: var(--light-color);
        }

        /* Customizable Navbar Gradient */
        .custom-navbar {
            background: linear-gradient(90deg,rgb(43, 46, 63) 0%,rgb(6, 30, 187) 100%) !important;
            box-shadow: 0 4px 16px rgba(53,92,253,0.15);
            border-radius: 0 0 18px 18px;
            padding-top: 0.5rem;
            padding-bottom: 0.5rem;
        }
        .custom-navbar .navbar-brand,
        .custom-navbar .nav-link,
        .custom-navbar .navbar-toggler-icon {              
            color: #fff !important;
        }
        .custom-navbar .nav-link:hover, .custom-navbar .nav-link.active {
            color: #ffd6d6 !important;
        }
        .navbar-brand img {
            vertical-align: middle;
            margin-right: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            border-radius: 6px;
            background: #fff;
            padding: 2px 6px;
        }
        @media (max-width: 768px) {
            .custom-navbar {
                border-radius: 0 0 10px 10px;
            }
        }
    </style>
</head>
<body>

<?php
// Check if we're on an auth page (login/register)
$current_page = basename($_SERVER['PHP_SELF']);
$auth_pages = ['login.php', 'register.php'];
$is_auth_page = in_array($current_page, $auth_pages);

// Don't show navbar on auth pages
if (!$is_auth_page):
?>

<!-- Navigation -->
<nav class="navbar navbar-expand-lg navbar-dark custom-navbar shadow">
    <div class="container-fluid">
        <a class="navbar-brand d-flex align-items-center" href="<?php echo isAdmin() ? '../dashboard.php' : 'dashboard.php'; ?>">
            <!-- Single Logo -->
            <img src="PTV_4_Para_Sa_Bayan_Logo_June_2017.svg.png" alt="PTV Para Sa Bayan Logo" style="height:50px; margin-right:8px; background:rgb(43, 46, 63);">
            People's Television Network Inc.
        </a>
        
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <?php if (isLoggedIn()): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">
                            <i class="fas fa-tachometer-alt me-1"></i>Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="documents.php">
                            <i class="fas fa-folder me-1"></i>Documents
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="upload.php">
                            <i class="fas fa-upload me-1"></i>Upload
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="user_department.php">
                            <i class="fas fa-upload me-1"></i>Division / Unit
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
            
            <ul class="navbar-nav">
                <?php if (isLoggedIn()): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user me-1"></i>
                            <?php echo htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['username']); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="<?php echo isAdmin() ? '../profile.php' : 'profile.php'; ?>">
                                <i class="fas fa-user-edit me-2"></i>Profile
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="<?php echo isAdmin() ? '../logout.php' : 'logout.php'; ?>" onclick="return confirm('Are you sure you want to logout?')">
                                <i class="fas fa-sign-out-alt me-2"></i>Logout
                            </a></li>
                        </ul>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link" href="login.php">
                            <i class="fas fa-sign-in-alt me-1"></i>Login
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="register.php">
                            <i class="fas fa-user-plus me-1"></i>Register
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<?php endif; ?>

<!-- Main Content Container -->
<?php if (!$is_auth_page): ?>
<div class="container-fluid mt-4">
    <?php 
    // Display alerts if function exists
    if (function_exists('displayAlert')) {
        displayAlert(); 
    }
    ?>
<?php endif; ?>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
