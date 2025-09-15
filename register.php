<?php
$page_title = 'Register';
require_once 'includes/functions.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username']);
    $email = sanitize($_POST['email']);
    $full_name = sanitize($_POST['full_name']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
// Validation
if (empty($username) || empty($email) || empty($full_name) || empty($password)) {
    $error = 'Please fill in all fields';
} elseif (!preg_match('/^[a-zA-Z0-9_.]{3,30}$/', $username)) {
    $error = 'Username must be 3-30 characters and only contain letters, numbers, underscores, or dots';
} elseif (!preg_match('/^[a-zA-Z\s\'\-]{3,60}$/', $full_name)) {
    $error = 'Full name must be 3-60 characters and only contain letters, spaces, hyphens, or apostrophes';
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $error = 'Please enter a valid email address';
} elseif (strlen($password) < 6) {
    $error = 'Password must be at least 6 characters long';
} elseif ($password !== $confirm_password) {
    $error = 'Passwords do not match';
} elseif (preg_match('/(http|www|<|>|script|@|\\$|%|\\^|&|\\*|\\(|\\)|\\{|\\}|\\[|\\]|\\||\\\\|;|:|,)/i', $username . $full_name)) {
    $error = 'Credentials contain invalid or spammy characters';
} else {
    try {
        // Check if username or email already exists
        $stmt = $db->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        if ($stmt->fetch()) {
            $error = 'Username or email already exists. Please choose another.';
        } else {
            // Create new user
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $db->prepare("INSERT INTO users (username, email, full_name, password) VALUES (?, ?, ?, ?)");
            if ($stmt->execute([$username, $email, $full_name, $hashed_password])) {
                $success = 'Account created successfully! You can now log in.';
            } else {
                $error = 'Registration failed. Please try again.';
            }
        }
    } catch (PDOException $e) {
        $error = 'Registration failed. Please try again.';
    }
}
}

require_once 'includes/header.php';
?>

<style>
body {
    min-block-size: 100vh;
    background: linear-gradient(120deg,rgb(43, 48, 73) 0%,rgb(53, 59, 88) 100%);
    color: #f5f6fa;
    overflow-y: auto !important;
}
.auth-container {
    min-block-size: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    /* Remove overflow: hidden here */
}
.animated-bg {
    position: fixed;
    inset-block-start: 0; inset-inline-start: 0; inline-size: 100vw; block-size: 100vh;
    z-index: 0;
    background: linear-gradient(270deg, #232946, #181c2f, #355cfd, #6e8efb, #232946);
    background-size: 400% 400%;
    animation: gradientMove 18s ease-in-out infinite;
}
@keyframes gradientMove {
    0% {background-position: 0% 50%;}
    50% {background-position: 100% 50%;}
    100% {background-position: 0% 50%;}
}
.auth-container {
    inline-size: 100vw;
    block-size: 100vh;
    position: relative;
    z-index: 1;
    display: flex;
    align-items: center;
    justify-content: center;
}
.auth-card {
    background: rgba(30,34,54,0.92);
    border-radius: 1.5rem;
    box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.18);
    padding: 2.5rem 2rem 2rem 2rem;
    max-inline-size: 400px;
    inline-size: 80%;
    margin: 2rem 0;
    animation: cardIn 1.1s cubic-bezier(.68,-0.55,.27,1.55);
    backdrop-filter: blur(12px);
    border: 1.5px solid rgba(255,255,255,0.08);
    position: relative;
    color: #f5f6fa;
    z-index: 2;
}
@keyframes cardIn {
    0% { opacity: 0; transform: scale(0.92) translateY(60px);}
    60% { opacity: 1; transform: scale(1.03) translateY(-8px);}
    100% { opacity: 1; transform: scale(1) translateY(0);}
}
.auth-header {
    text-align: center;
    margin-block-end: 2rem;
    animation: fadeIn 1.2s;
}
@keyframes fadeIn {
    from { opacity: 0; transform: scale(0.95);}
    to { opacity: 1; transform: scale(1);}
}
.auth-header h1 {
    color: #6e8efb;
    font-weight: 800;
    margin-block-end: 0.5rem;
    letter-spacing: 1px;
}
.auth-header p {
    color: #c7c9d9;
    opacity: 0.9;
    font-size: 1.08rem;
}
.logo-float {
    display: flex;
    justify-content: center;
    margin-block-end: 1.2rem;
    animation: floatLogo 2.5s infinite ease-in-out;
    filter: drop-shadow(0 0 16px #6e8efb88);
}
@keyframes floatLogo {
    0%,100% { transform: translateY(0);}
    50% { transform: translateY(-12px);}
}
.logo-float img {
    block-size: 60px;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    background: #fff;
    padding: 4px 10px;
    animation: logoGlow 2.5s infinite alternate;
}
@keyframes logoGlow {
    0% { box-shadow: 0 0 12px #6e8efb44, 0 2px 8px rgba(0,0,0,0.08);}
    100% { box-shadow: 0 0 32px #355cfd88, 0 2px 8px rgba(0,0,0,0.08);}
}
.input-group-text {
    background: #232946;
    border: none;
    border-radius: 0.75rem 0 0 0.75rem;
    color: #f5f6fa;
    transition: background 0.2s;
}
.form-control, .form-control:focus {
    border-radius: 0.75rem;
    border: 1.5px solid #232946;
    box-shadow: none;
    font-size: 1.05rem;
    background: rgba(36, 40, 61, 0.95);
    color: #f5f6fa;
    transition: border 0.2s, background 0.2s, box-shadow 0.2s;
}
.form-control:focus {
    border: 1.5px solid #6e8efb;
    background: #232946;
    color: #fff;
    box-shadow: 0 0 8px #6e8efb55;
}
.btn-primary, .btn-primary:focus {
    background: linear-gradient(90deg, #6e8efb 0%, #355cfd 100%);
    color: #fff;
    font-weight: 700;
    border-radius: 0.75rem;
    padding: 0.75rem;
    font-size: 1.1rem;
    border: none;
    box-shadow: 0 2px 8px rgba(53,92,253,0.08);
    transition: background 0.2s, transform 0.2s, box-shadow 0.2s;
    outline: none;
}
.btn-primary:hover, .btn-primary:active {
    background: linear-gradient(90deg, #355cfd 0%, #6e8efb 100%);
    transform: translateY(-2px) scale(1.04);
    box-shadow: 0 4px 16px #355cfd33;
}
.btn-outline-secondary, .btn-outline-secondary:focus {
    border-radius: 0.75rem;
    color: #f5f6fa;
    border-color: #6e8efb;
    background: transparent;
    transition: background 0.2s, color 0.2s;
}
.btn-outline-secondary:hover {
    background: #232946;
    color: #fff;
}
.alert {
    border-radius: 0.75rem;
    font-size: 1rem;
    background: rgba(255, 0, 0, 0.12);
    color: #fff;
    border: none;
    animation: fadeIn 0.7s;
}
.alert-success {
    background: rgba(40, 167, 69, 0.15);
    color: #b6fcb6;
    border: none;
}
a.text-decoration-none {
    color: #6e8efb;
    font-weight: 600;
    transition: color 0.2s;
}
a.text-decoration-none:hover {
    color: #fff;
    text-decoration: underline;
}
.login-footer {
    inline-size: 100vw;
    position: fixed;
    inset-inline-start: 0;
    inset-block-end: 0;
    background: rgba(36, 40, 61, 0.95);
    color: #b3b8d6;
    text-align: center;
    font-size: 1rem;
    padding: 0.7rem 0;
    letter-spacing: 0.5px;
    box-shadow: 0 -2px 12px rgba(53,92,253,0.04);
    z-index: 100;
    animation: fadeIn 1.5s;
}
* {
    transition: background 0.2s, color 0.2s, box-shadow 0.2s, border 0.2s;
}
@media (max-width: 600px) {
    .auth-card { padding: 1.2rem 0.5rem; }
}
</style>

<div class="animated-bg"></div>
<div class="auth-container">
    <div class="auth-card">
        <div class="logo-float">
            <img src="assets/images/ptv.png" alt="PTV Logo">
        </div>
        <div class="auth-header">
            <h1><i class="fas fa-user-plus me-2"></i>Join PTNI</h1>
            <p>Create your account</p>
        </div>
        <?php if ($error): ?>
            <div class="alert alert-danger fade show">
                <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error; ?>
            </div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success fade show">
                <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
            </div>
        <?php endif; ?>
        <form method="POST" action="">
            <div class="mb-3">
                <label for="full_name" class="form-label">Full Name</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-id-card"></i></span>
                    <input type="text" class="form-control" id="full_name" name="full_name" 
                           value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : ''; ?>" 
                           required>
                </div>
            </div>
            <div class="mb-3">
                <label for="username" class="form-label">Username</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                    <input type="text" class="form-control" id="username" name="username" 
                           value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" 
                           required>
                </div>
            </div>
            <div class="mb-3">
                <label for="email" class="form-label">Email Address</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                    <input type="email" class="form-control" id="email" name="email" 
                           value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" 
                           required>
                </div>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>
            </div>
            <div class="mb-4">
                <label for="confirm_password" class="form-label">Confirm Password</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                </div>
            </div>
            <button type="submit" class="btn btn-primary w-100 mb-3">
                <i class="fas fa-user-plus me-2"></i>Create Account
            </button>
        </form>
        <div class="text-center">
            <p class="mb-0">Already have an account? 
                <a href="login.php" class="text-decoration-none">Sign in here</a>
            </p>
        </div>
    </div>
</div>


