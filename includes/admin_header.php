<?php
if (!defined('ADMIN_AREA')) {
    define('ADMIN_AREA', true);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' : ''; ?>PTNI4 Admin</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <style>
        :root {
            --admin-primary: #dc3545;
            --admin-secondary: #6c757d;
            --admin-success: #198754;
            --admin-warning: #ffc107;
            --admin-danger: #dc3545;
        }
        
        body {
        font-family: 'Nunito', -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'Helvetica Neue', Arial, sans-serif;
            background-color: var(--light-color);
        }
        
        .admin-navbar {
            background: linear-gradient(90deg,rgb(43, 46, 63) 0%,rgb(6, 30, 187) 100%) !important; /* Change these colors for your preferred blue or any other color */
            box-shadow: 0 4px 16px rgba(53,92,253,0.15);
            border-radius: 0 0 18px 18px;
            padding-top: 0.5rem;
            padding-bottom: 0.5rem;
        }
        
        .admin-navbar .navbar-brand {
            color: white !important;
            font-weight: 700;
        }
        
        .admin-navbar .nav-link {
            color: rgba(255,255,255,0.9) !important;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .admin-navbar .nav-link:hover {
            color: white !important;
            background: rgba(255,255,255,0.1);
            border-radius: 5px;
        }
        
        .admin-badge {
            background: #ffc107;
            color: #000;
            font-size: 0.7rem;
            padding: 2px 6px;
            border-radius: 10px;
            margin-left: 5px;
        }
        
        .navbar-toggler {
            border-color: rgba(255,255,255,0.3);
        }
        
        .navbar-toggler-icon {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 30 30'%3e%3cpath stroke='rgba%28255, 255, 255, 0.75%29' stroke-linecap='round' stroke-miterlimit='10' stroke-width='2' d='M4 7h22M4 15h22M4 23h22'/%3e%3c/svg%3e");
        }
        
        .btn-logout {
            background: rgba(255,255,255,0.2);
            border: 1px solid rgba(255,255,255,0.3);
            color: white;
            transition: all 0.3s ease;
        }
        
        .btn-logout:hover {
            background: rgba(255,255,255,0.3);
            color: white;
            border-color: rgba(255,255,255,0.5);
        }
    </style>
</head>
<body>

<!-- Admin Navigation -->
<nav class="navbar navbar-expand-lg admin-navbar">
    <div class="container-fluid">
        <!-- Admin Brand -->
         <a class="navbar-brand d-flex align-items-center" href="<?php echo isAdmin() ? '../dashboard.php' : 'dashboard.php'; ?>">
            <!-- Single Logo -->
            <img src="PTV_4_Para_Sa_Bayan_Logo_June_2017.svg.png" alt="PTV Para Sa Bayan Logo" style="height:50px; margin-right:8px; background:rgb(43, 46, 63);">
            People's Television Network Inc.
        </a>
        
        <!-- Mobile Toggle -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#adminNavbar">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <!-- Admin Navigation Links -->
        <div class="collapse navbar-collapse" id="adminNavbar">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link" href="dashboard.php">
                        <i class="fas fa-tachometer-alt me-1"></i>Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="users.php">
                        <i class="fas fa-users me-1"></i>Users
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="documents.php">
                        <i class="fas fa-file-alt me-1"></i>Documents
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="department.php">
                        <i class="fas fa-file-alt me-1"></i>Department Documents
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="categories.php">
                        <i class="fas fa-tags me-1"></i>Categories
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link" href="division.php">
                        <i class="fas fa-tags me-1"></i>Add Division
                    </a>
                </li>


               <li class="nav-item">
            </ul>
            
            <!-- User Info and Logout -->
            <div class="d-flex align-items-center">
                <span class="text-white me-3">
                    <i class="fas fa-user-shield me-1"></i>
                    <?php echo htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['username']); ?>
                </span>
               
                <button type="button" class="btn btn-logout btn-sm" onclick="confirmLogout()">
                    <i class="fas fa-sign-out-alt me-1"></i>Logout
                </button>
            </div>
        </div>
    </div>
</nav>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
function confirmLogout() {
    if (confirm('Are you sure you want to logout?')) {
        window.location.href = '../logout.php';
    }
}
</script>

<!-- Alert Messages -->
<div class="container-fluid px-4 mt-3">
    <?php displayAlert(); ?>
</div>

