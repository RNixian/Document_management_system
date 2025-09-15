<?php
require_once 'config/database.php';
require 'vendor/autoload.php'; // PHPMailer autoload

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];

    // Check if email exists
    $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user) {
        // Generate token + expires_at 5 minutes from now
        $token = bin2hex(random_bytes(32));
        $stmt = $db->prepare("
            INSERT INTO password_resets (email, token, expires_at)
            VALUES (:email, :token, DATE_ADD(NOW(), INTERVAL 5 MINUTE))
        ");
        $stmt->execute([
            ':email' => $email,
            ':token' => $token
        ]);

        $resetLink = "http://dms.local/reset_password.php?token=" . urlencode($token);

        // Send email via PHPMailer
        $mail = new PHPMailer(true);
        try {
            // Server settings
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'ptv.dms@gmail.com';     
            $mail->Password   = 'nlikywhrmnhwqgec';            
            $mail->SMTPSecure = 'ssl';
            $mail->Port       = 465;
            
            $mail->setFrom('ptv.dms@gmail.com', 'DMS');
            $mail->addAddress($email);
            
            // Content
            $mail->isHTML(true);
            $mail->Subject = 'Password Reset Request';
            $mail->Body    = "
                <p>Hello,</p>
                <p>Click the link below to reset your password. This link expires in 5 minutes:</p>
                <p><a href='$resetLink'>$resetLink</a></p>
                <p>If you didn't request this, please ignore this email.</p>
            ";

            $mail->send();
            echo "Reset link sent to your email!";
        } catch (Exception $e) {
            echo "Email could not be sent. Mailer Error: {$mail->ErrorInfo}";
        }

    } else {
        echo "No account found with that email.";
    }
}
