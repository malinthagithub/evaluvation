<?php
session_start();
require 'config.php';

$msg = '';

if (isset($_GET['secret'])) {
    $email = base64_decode($_GET['secret']);
    
    if (isset($_POST['reset'])) {
        $password = mysqli_real_escape_string($conn, $_POST['password']);
        $confirm_password = mysqli_real_escape_string($conn, $_POST['confirm_password']);
        
        if ($password === $confirm_password) {
            // IMPORTANT: Use MD5 to match your login system
            $hashed_password = md5($password);
            
            $update = mysqli_query($conn, "UPDATE evaluator SET password='$hashed_password' WHERE email='$email'");
            
            if ($update && mysqli_affected_rows($conn) > 0) {
                $msg = '<div style="background-color: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin-top: 15px;">
                    ✅ Password updated successfully! 
                    <br><br>
                    <a href="signin.php" style="background-color: #28a745; color: white; padding: 8px 20px; text-decoration: none; border-radius: 5px; display: inline-block;">Sign In Now</a>
                </div>';
            } else {
                $msg = '<div style="background-color: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px;">
                    ❌ Error updating password. Email not found.
                </div>';
            }
        } else {
            $msg = '<div style="background-color: #fff3cd; color: #856404; padding: 15px; border-radius: 5px;">
                ⚠ Passwords do not match!
            </div>';
        }
    }
} else {
    header('Location: forgot.php');
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Reset Password - JB EE</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta1/dist/css/bootstrap.min.css" />
</head>
<body style="background-color: #A9D6E599;">
    <div style="background-color: #f9f6f1; margin: 10vh auto; padding: 30px; max-width: 450px; border-radius: 10px;">
        <center>
            <h3>Reset Password</h3>
            <p style="color: #666;">Email: <?php echo htmlspecialchars($email); ?></p>
        </center>
        
        <?php if (empty($msg) || strpos($msg, 'Success') === false) { ?>
            <form method="POST">
                <div class="form-group mb-3">
                    <label>New Password</label>
                    <input type="password" name="password" class="form-control" required minlength="6">
                </div>
                <div class="form-group mb-3">
                    <label>Confirm Password</label>
                    <input type="password" name="confirm_password" class="form-control" required minlength="6">
                </div>
                <button type="submit" name="reset" class="btn btn-success w-100">Update Password</button>
            </form>
        <?php } ?>
        
        <?php echo $msg; ?>
        
        <?php if (empty($msg)) { ?>
            <div class="text-center mt-3">
                <a href="signin.php">← Back to Sign In</a>
            </div>
        <?php } ?>
    </div>
</body>
</html>