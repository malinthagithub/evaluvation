<?php
include "config.php";
session_start();

if (isset($_SESSION["user"])) {
  header("location: index.php");
} else {

  if (isset($_POST['signin'])) {

    $username = $_POST['username'];
    $password = $_POST['password'];

    $sql = "SELECT * FROM evaluator WHERE evaluator_name = '$username'";
    $result = $conn->query($sql);
    $row = $result->fetch_assoc();

    if ($result->num_rows == 1) {
      if ($row['password'] == md5($password)) {
        $_SESSION["id"] = $row['evaluator_id'];
        $_SESSION["user"] = $row['evaluator_name'];
        $_SESSION["isAdmin"] = $row['is_admin'];
        $_SESSION["isEvaluator"] = $row['is_evaluator'];
        $_SESSION["PLE"] = $row['PLE'];
        $_SESSION["OE"] = $row['OE'];
        $_SESSION["QM"] = $row['QM'];
        $_SESSION["PE"] = $row['PE'];
        header("location: index.php");
      } else {
        echo '<script>alert("Incorrect Username or Password")</script>';
      }
    } else {
      echo '<script>alert("User doesnt Exist")</script>';
    }
  }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title>Sign In - Employee Evaluation System</title>
    <link rel="icon" type="image/x-icon" href="./img/jb.png">
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    
    <!-- Boxicons CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/boxicons@latest/css/boxicons.min.css" />
    
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #3498db;
            --accent-color: #e74c3c;
            --light-color: #ecf0f1;
            --dark-color: #2c3e50;
            --success-color: #2ecc71;
            --warning-color: #f39c12;
            --gradient-primary: linear-gradient(135deg, #2c3e50 0%, #3498db 100%);
            --gradient-accent: linear-gradient(135deg, #e74c3c 0%, #f39c12 100%);
            --shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            --border-radius: 12px;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f5f7fa;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            position: relative;
            overflow-x: hidden;
        }
        
        .background-shapes {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
            z-index: -1;
        }
        
        .shape {
            position: absolute;
            border-radius: 50%;
            opacity: 0.1;
        }
        
        .shape-1 {
            width: 300px;
            height: 300px;
            background: var(--secondary-color);
            top: -150px;
            right: -100px;
        }
        
        .shape-2 {
            width: 200px;
            height: 200px;
            background: var(--accent-color);
            bottom: -100px;
            left: -50px;
        }
        
        .shape-3 {
            width: 150px;
            height: 150px;
            background: var(--warning-color);
            top: 50%;
            right: 20%;
        }
        
        .login-container {
            display: flex;
            width: 100%;
            max-width: 1100px;
            min-height: 650px;
            border-radius: var(--border-radius);
            overflow: hidden;
            box-shadow: var(--shadow);
            background-color: white;
        }
        
        .login-left {
            flex: 1;
            background: var(--gradient-primary);
            padding: 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            color: white;
            position: relative;
            overflow: hidden;
        }
        
        .login-left::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" preserveAspectRatio="none"><path d="M0,0 L100,0 L100,100 Z" fill="white" opacity="0.05"/></svg>');
            background-size: cover;
        }
        
        .logo-container {
            text-align: center;
            margin-bottom: 40px;
            z-index: 1;
        }
        
        .logo-circle {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background-color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }
        
        .logo-circle img {
            width: 80px;
            height: auto;
        }
        
        .logo-text {
            font-family: 'Montserrat', sans-serif;
            font-weight: 700;
            font-size: 28px;
            margin-bottom: 5px;
        }
        
        .logo-subtext {
            font-size: 16px;
            opacity: 0.9;
            font-weight: 300;
        }
        
        .features-list {
            margin-top: 30px;
            z-index: 1;
        }
        
        .feature-item {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .feature-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: rgba(255, 255, 255, 0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
        }
        
        .feature-text {
            font-size: 15px;
            font-weight: 300;
        }
        
        .login-right {
            flex: 1.2;
            padding: 50px 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        
        .login-header {
            margin-bottom: 40px;
        }
        
        .login-title {
            font-family: 'Montserrat', sans-serif;
            font-size: 32px;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 10px;
        }
        
        .login-subtitle {
            color: #7f8c8d;
            font-size: 16px;
        }
        
        .login-form {
            width: 100%;
        }
        
        .form-group {
            margin-bottom: 25px;
            position: relative;
        }
        
        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--primary-color);
            font-size: 14px;
        }
        
        .input-with-icon {
            position: relative;
        }
        
        .input-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #95a5a6;
            font-size: 18px;
        }
        
        .form-input {
            width: 100%;
            padding: 15px 15px 15px 50px;
            border: 2px solid #e8e8e8;
            border-radius: 10px;
            font-size: 16px;
            font-family: 'Poppins', sans-serif;
            transition: all 0.3s ease;
            background-color: #f9f9f9;
        }
        
        .form-input:focus {
            outline: none;
            border-color: var(--secondary-color);
            background-color: white;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
        }
        
        .password-toggle {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #95a5a6;
            cursor: pointer;
            font-size: 18px;
        }
        
        .form-options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        
        .remember-me {
            display: flex;
            align-items: center;
        }
        
        .remember-checkbox {
            margin-right: 8px;
            accent-color: var(--secondary-color);
        }
        
        .remember-label {
            font-size: 14px;
            color: #7f8c8d;
        }
        
        .forgot-password {
            color: var(--secondary-color);
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: color 0.3s ease;
        }
        
        .forgot-password:hover {
            color: var(--accent-color);
            text-decoration: underline;
        }
        
        .submit-btn {
            width: 100%;
            padding: 16px;
            background: var(--gradient-primary);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            font-family: 'Poppins', sans-serif;
            letter-spacing: 0.5px;
            box-shadow: 0 5px 15px rgba(52, 152, 219, 0.3);
        }
        
        .submit-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(52, 152, 219, 0.4);
        }
        
        .submit-btn:active {
            transform: translateY(0);
        }
        
        .login-footer {
            text-align: center;
            margin-top: 30px;
            color: #7f8c8d;
            font-size: 14px;
        }
        
        .footer-link {
            color: var(--secondary-color);
            text-decoration: none;
            font-weight: 500;
        }
        
        .footer-link:hover {
            text-decoration: underline;
        }
        
        .notification {
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 25px;
            display: none;
        }
        
        .notification.error {
            background-color: rgba(231, 76, 60, 0.1);
            color: #c0392b;
            border-left: 4px solid #e74c3c;
        }
        
        .notification.success {
            background-color: rgba(46, 204, 113, 0.1);
            color: #27ae60;
            border-left: 4px solid #2ecc71;
        }
        
        @media (max-width: 992px) {
            .login-container {
                flex-direction: column;
                max-width: 500px;
            }
            
            .login-left {
                padding: 30px;
            }
            
            .logo-circle {
                width: 100px;
                height: 100px;
            }
            
            .logo-circle img {
                width: 60px;
            }
        }
        
        @media (max-width: 576px) {
            body {
                padding: 10px;
            }
            
            .login-container {
                min-height: auto;
            }
            
            .login-left, .login-right {
                padding: 30px 20px;
            }
            
            .logo-text {
                font-size: 24px;
            }
            
            .login-title {
                font-size: 28px;
            }
            
            .form-options {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .forgot-password {
                margin-top: 10px;
            }
        }
        
        .copyright {
            position: absolute;
            bottom: 15px;
            left: 0;
            right: 0;
            text-align: center;
            color: rgba(255, 255, 255, 0.7);
            font-size: 13px;
            z-index: 1;
        }
    </style>
</head>

<body>
    <div class="background-shapes">
        <div class="shape shape-1"></div>
        <div class="shape shape-2"></div>
        <div class="shape shape-3"></div>
    </div>
    
    <div class="login-container">
        <div class="login-left">
            <div class="logo-container">
                <div class="logo-circle">
                    <img src="./img/jb.png" alt="JB Logo" />
                </div>
                <div class="logo-text">Employee Evaluation</div>
                <div class="logo-subtext">Performance Management System</div>
            </div>
            
            <div class="features-list">
                <div class="feature-item">
                    <div class="feature-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div class="feature-text">Multi-evaluator assessment system</div>
                </div>
                
                <div class="feature-item">
                    <div class="feature-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="feature-text">Track employee performance metrics</div>
                </div>
                
                <div class="feature-item">
                    <div class="feature-icon">
                        <i class="fas fa-trophy"></i>
                    </div>
                    <div class="feature-text">Identify and reward top performers</div>
                </div>
                
                <div class="feature-item">
                    <div class="feature-icon">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <div class="feature-text">Secure and confidential data handling</div>
                </div>
            </div>
            
            <div class="copyright">
                &copy; <?php echo date("Y"); ?> Employee Evaluation System. All rights reserved.
            </div>
        </div>
        
        <div class="login-right">
            <div class="login-header">
                <h1 class="login-title">Welcome Back</h1>
                <p class="login-subtitle">Sign in to access the Employee Evaluation System</p>
            </div>
            
            <form class="login-form" name="addForm" enctype="multipart/form-data" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST">
                <div class="form-group">
                    <label class="form-label" for="username">Username</label>
                    <div class="input-with-icon">
                        <i class="fas fa-user input-icon"></i>
                        <input type="text" id="username" name="username" placeholder="Enter your username" class="form-input" required />
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="password">Password</label>
                    <div class="input-with-icon">
                        <i class="fas fa-lock input-icon"></i>
                        <input type="password" id="password" name="password" placeholder="Enter your password" class="form-input" required />
                        <button type="button" class="password-toggle" id="togglePassword">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
                
                <div class="form-options">
                    <div class="remember-me">
                        <input type="checkbox" id="remember" class="remember-checkbox" name="remember">
                        <label for="remember" class="remember-label">Remember me</label>
                    </div>
                    <a href="http://localhost:8000/forgot.php" class="forgot-password">Forgot password?</a>
                </div>
                
                <button type="submit" class="submit-btn" name="signin">
                    <i class="fas fa-sign-in-alt" style="margin-right: 8px;"></i> Sign In
                </button>
                
                <div class="login-footer">
                    <p>Need help? <a href="#" class="footer-link">Contact System Administrator</a></p>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Toggle password visibility
        const togglePassword = document.getElementById('togglePassword');
        const passwordInput = document.getElementById('password');
        
        togglePassword.addEventListener('click', function() {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            
            // Toggle eye icon
            this.innerHTML = type === 'password' ? '<i class="fas fa-eye"></i>' : '<i class="fas fa-eye-slash"></i>';
        });
        
        // Form validation
        const loginForm = document.querySelector('.login-form');
        loginForm.addEventListener('submit', function(e) {
            const username = document.getElementById('username').value.trim();
            const password = document.getElementById('password').value.trim();
            
            if (!username || !password) {
                e.preventDefault();
                alert('Please fill in both username and password fields.');
                return false;
            }
            
            return true;
        });
        
        // Check for URL parameters that might indicate an error
        document.addEventListener('DOMContentLoaded', function() {
            // This would normally check for error parameters in the URL
            // For example: if (window.location.search.includes('error=1')) { show error }
            
            // Auto-focus on username field
            document.getElementById('username').focus();
        });
    </script>
</body>
</html>

<?php } ?>