<?php
$page_title = 'Login';
require_once 'includes/functions.php';

// Redirect if already logged in
if (isLoggedIn()) {
    if (isSuperAdmin()) {
        header('Location: admin/dashboard.php');
    } elseif (isAdmin()) {
        header('Location: admin/dashboard.php');
    } else {
        header('Location: dashboard.php');
    }
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username']);
    $password = $_POST['password'];

    if (!empty($username) && !empty($password)) {
        if (loginUser($username, $password)) {
            // Redirect based on role
            if (isSuperAdmin()) {
                header('Location: superadmin/dashboard.php');
            } elseif (isAdmin()) {
                header('Location: admin/dashboard.php');
            } else {
                header('Location: dashboard.php');
            }
            exit();
        } else {
            $error = 'Invalid username or password';
        }
    }
}

require_once 'includes/header.php';
?>

<style>
body, .auth-container {
    min-block-size: 100vh;
     background: linear-gradient(120deg,rgb(43, 48, 73) 0%,rgb(53, 59, 88) 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    color: #f5f6fa;
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
    inline-size: 100%;
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
    background: #ffffff;
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
            <h1>Welcome Back</h1>
            <p>Sign in to your account</p>
        </div>
        <?php if ($error): ?>
            <div class="alert alert-danger fade show">
                <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error; ?>
            </div>
        <?php endif; ?>
        <form method="POST" action="" autocomplete="on">
            <div class="mb-3">
                <label for="username" class="form-label">Username or Email</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                    <input type="text" class="form-control" id="username" name="username" 
                           value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" 
                           required autofocus>
                </div>
            </div>
            <div class="mb-4">
                <label for="password" class="form-label">Password</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                    <input type="password" class="form-control" id="password" name="password" required>
                    <button class="btn btn-outline-secondary" type="button" id="togglePassword" tabindex="-1">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
            </div>
            <button type="submit" class="btn btn-primary w-100 mb-3">
                <i class="fas fa-sign-in-alt me-2"></i>Log In
            </button>
        </form>
        <div class="text-center">
            <p class="mb-0">
            <a href="forgot_password.php" class="no-underline text-white">Forgot Password?</a>
            </p>
        </div>
    
    </div>
</div>

<footer class="login-footer">
    <div>&copy; <?php echo date('Y'); ?> People's Television Network Inc. All rights reserved.</div>
</footer>

<script>
document.getElementById('togglePassword').addEventListener('click', function() {
    const password = document.getElementById('password');
    const icon = this.querySelector('i');
    if (password.type === 'password') {
        password.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        password.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
});
</script>
