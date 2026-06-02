<?php
session_start();
require 'config.php';

// Use these at the top
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

$msg = "";

if (isset($_POST['pwdrst'])) {
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    
    // Check if email exists
    $check_email = mysqli_query($conn, "SELECT email FROM evaluator WHERE email='$email'");
    $res = mysqli_num_rows($check_email);
    
    if ($res > 0) {
        // Email message
        $message = '
        <div style="font-family: Arial, sans-serif; padding: 20px;">
            <h3>Hello!</h3>
            <p>You are receiving this email because we received a password reset request for your account.</p>
            <br>
            <a href="http://localhost:8000/reset-password.php?secret=' . base64_encode($email) . '" 
               style="background-color: #4CAF50; color: white; padding: 12px 30px; 
                      text-decoration: none; border-radius: 5px; display: inline-block;">
                Reset Password
            </a>
            <br><br>
            <p>If you did not request a password reset, no further action is required.</p>
            <hr>
            <p style="color: #666;">JB EE Tea Dip.</p>
        </div>';
        
        // IMPORTANT: Include PHPMailer files - use correct path
        require_once 'PHPMailer/src/Exception.php';
        require_once 'PHPMailer/src/PHPMailer.php';
        require_once 'PHPMailer/src/SMTP.php';
        
        // Create PHPMailer instance
        $mail = new PHPMailer(true);
        
        try {
            // Server settings
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'mokadaputhe@gmail.com';  // Your Gmail
            $mail->Password   = 'iqwflxqhajqsxywz';       // Your App Password
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;  // SSL
            $mail->Port       = 465;
            
            // Recipients
            $mail->setFrom('mokadaputhe@gmail.com', 'JB EE');
            $mail->addAddress($email);
            
            // Content
            $mail->isHTML(true);
            $mail->Subject = 'JB EE Tea Dip. - Password Reset Request';
            $mail->Body    = $message;
            $mail->AltBody = strip_tags($message);  // Plain text version
            
            $mail->send();
            $msg = '<div class="alert alert-success">Password reset link has been sent to your email!</div>';
            
        } catch (Exception $e) {
            $msg = '<div class="alert alert-danger">Message could not be sent. Error: ' . $mail->ErrorInfo . '</div>';
        }
    } else {
        $msg = '<div class="alert alert-warning">We cannot find a user with that email address!</div>';
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Forgot Password - JB EE</title>
    <link rel="icon" type="image/x-icon" href="./img/jb.png">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta1/dist/css/bootstrap.min.css" />
    <style>
        body {
            background-color: #A9D6E599;
            font-family: Arial, sans-serif;
        }
        .container-box {
            background-color: #f9f6f1;
            margin: 10vh auto;
            padding: 5vh;
            max-width: 500px;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        .btn-success {
            width: 100%;
            padding: 10px;
            font-size: 16px;
        }
        .alert {
            margin-top: 15px;
            padding: 10px;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="container-box">
            <center>
                <h3 style="color: #333; margin-bottom: 30px;">Forgot Password</h3>
            </center>
            
            <form method="POST" action="">
                <div class="form-group mb-3">
                    <label for="email" style="font-weight: bold; color: #555; margin-bottom: 5px; display: block;">
                        Email Address
                    </label>
                    <input type="email" 
                           name="email" 
                           id="email" 
                           class="form-control" 
                           placeholder="Enter your registered email" 
                           required 
                           style="padding: 10px; border-radius: 5px; border: 1px solid #ddd; width: 100%;" />
                </div>
                
                <div class="form-group">
                    <button type="submit" 
                            name="pwdrst" 
                            class="btn btn-success">
                        Send Password Reset Link
                    </button>
                </div>
                
                <div class="text-center mt-3">
                    <a href="signin.php" style="color: #666; text-decoration: none;">Back to Login</a>
                </div>
                
                <?php echo $msg; ?>
            </form>
        </div>
    </div>
</body>
</html>