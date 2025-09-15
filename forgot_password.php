<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Forgot Password</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
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
        .form-control, .form-control:focus {
            border-radius: 0.75rem;
            border: 1.5px solid #232946;
            box-shadow: none;
            font-size: 1.05rem;
            background: rgba(36, 40, 61, 0.95);
            color: #f5f6fa;
            transition: border 0.2s, background 0.2s, box-shadow 0.2s;
            inline-size: 100%;
            padding: 12px;
            margin-block-end: 1.5rem;
            inline-size: calc(100% - 20px); 
        }
        .form-control:focus {
            border: 1.5px solid #6e8efb;
            background: #232946;
            color: #fff;
            box-shadow: 0 0 8px #6e8efb55;
        }
        .btn-primary {
            background: linear-gradient(90deg, #6e8efb 0%, #355cfd 100%);
            color: #fff;
            font-weight: 700;
            border-radius: 0.75rem;
            padding: 0.75rem;
            font-size: 1.1rem;
            border: none;
            box-shadow: 0 2px 8px rgba(53,92,253,0.08);
            transition: background 0.2s, transform 0.2s, box-shadow 0.2s;
            inline-size: 100%;
            margin-block-end: 1rem;
            cursor: pointer;
        }
        .btn-primary:hover {
            background: linear-gradient(90deg, #355cfd 0%, #6e8efb 100%);
            transform: translateY(-2px) scale(1.04);
            box-shadow: 0 4px 16px #355cfd33;
        }
        .btn-secondary {
            inline-size: 100%;
            padding: 0.75rem;
            border-radius: 0.75rem;
            border: 1.5px solid #6e8efb;
            background: transparent;
            color: #f5f6fa;
            font-weight: 700;
            cursor: pointer;
            transition: background 0.2s, color 0.2s;
        }
        .btn-secondary:hover {
            background: #232946;
            color: #fff;
        }
    </style>
</head>
<body>
    <div class="animated-bg"></div>
    <div class="auth-container">
        <div class="auth-card">
            <div class="logo-float">
            <img src="assets/images/ptv.png" alt="PTV Logo">
        </div>
            <div class="auth-header">
                <h1>Forgot Password</h1>
                <p>Enter your email to reset password</p>
            </div>
            <form action="send_reset.php" method="POST">
                <input type="email" name="email" class="form-control" placeholder="Enter your email" required>
                <button type="submit" class="btn-primary">
                    <i class="fas fa-paper-plane me-2" > </i>Send Reset Link
                </button>
                <button type="button" class="btn-secondary" onclick="window.location.href='login.php'">
                    <i class="fas fa-arrow-left me-2"></i>Back
                </button>
            </form>
        </div>
    </div>
</body>
</html>
