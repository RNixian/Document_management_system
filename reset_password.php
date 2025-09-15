<?php
require_once 'config/database.php';

$token = $_GET['token'] ?? '';

// Check token validity (expires in 5 minutes)
$stmt = $db->prepare("SELECT * FROM password_resets WHERE token = ? AND expires_at > NOW()");
$stmt->execute([$token]);
$reset = $stmt->fetch();

if (!$reset) {
    die("Invalid or expired token.");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password</title>
    
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

    <style>
        body {
            background-color: #1f2235; /* dark shade */
            font-family: 'Segoe UI', sans-serif;
        }
        .reset-container {
            min-block-size: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .reset-card {
            background: #fff;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0px 6px 20px rgba(0,0,0,0.2);
            inline-size: 100%;
            max-inline-size: 420px;
        }
        .reset-card h2 {
            font-weight: 600;
            margin-block-end: 20px;
            color: #2c2f48;
            text-align: center;
        }
        .form-control {
            border-radius: 8px;
            padding: 12px;
        }
        .btn-primary {
            background: #2c2f48;
            border: none;
            border-radius: 8px;
            padding: 12px;
            font-weight: 600;
            transition: 0.2s;
        }
        .btn-primary:hover {
            background: #1a1d33;
        }
        .btn-secondary {
            border-radius: 8px;
            padding: 12px;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="reset-container">
        <div class="reset-card">
            <h2><i class="fas fa-lock me-2"></i>Reset Password</h2>
            
            <form action="update_password.php" method="POST">
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">

                <div class="mb-3">
                    <label for="password" class="form-label fw-semibold">New Password</label>
                    <input type="password" class="form-control" id="password" name="password" placeholder="Enter new password" required>
                </div>

                <div class="mb-3">
                    <label for="confirm_password" class="form-label fw-semibold">Confirm Password</label>
                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" placeholder="Confirm new password" required>
                </div>

                <div class="d-flex justify-content-between">
                    <a href="forgot_password.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Back
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-check-circle me-2"></i>Reset Password
                    </button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
