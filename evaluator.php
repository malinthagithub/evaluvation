<?php
include "config.php";
session_start();

// Check if system is frozen
$freeze_query = "SELECT evaluation_frozen FROM system_settings WHERE id = 1";
$freeze_result = mysqli_query($conn, $freeze_query);
if ($freeze_result && mysqli_num_rows($freeze_result) > 0) {
    $freeze_data = mysqli_fetch_assoc($freeze_result);
    $is_frozen = $freeze_data['evaluation_frozen'];
} else {
    $is_frozen = '0';
}

if (isset($_SESSION["user"])) {

  if (isset($_POST['signout'])) {
    echo "<script>
    var sout = confirm('Are You Sure Want to SignOut?');
    if(sout == true){
      window.location.href='signout.php';
    } else {
      history.back();
    }
    </script>";
  }

  // Handle CREATE evaluator submission
  if (isset($_POST['submit'])) {
    $emNumber = $_POST['emNumber'];
    $evName = $_POST['evName'];
    $email = $_POST['email'];
    $gender = $_POST['gender'];
    $isAdmin = isset($_POST['isAdmin']) ? $_POST['isAdmin'] : 0;
    $isEvaluator = isset($_POST['isEvaluator']) ? $_POST['isEvaluator'] : 0;
    $password = md5($_POST['password']);

    $sqls = "SELECT * FROM evaluator WHERE evaluator_id = '$emNumber'";
    $results = $conn->query($sqls);

    if ($results->num_rows > 0) {
      echo "<script>alert('Employee Number is Already Exist!');
      window.location.href='Evaluators.php';
      </script>";
    } else {
      $PLE = $OE = $QM = $PE = 0;
      if(isset($_POST['check_list'])) {
        foreach ($_POST['check_list'] as $checkbox) {
          if ($checkbox == 'PLE') {
            $PLE = 1;
          } else if ($checkbox == 'OE') {
            $OE = 1;
          } else if ($checkbox == 'QM') {
            $QM = 1;
          } else if ($checkbox == 'PE') {
            $PE = 1;
          }
        }
      }

      $sql = "INSERT INTO evaluator(evaluator_id, evaluator_name, email, PLE, OE, QM, PE, is_admin, is_evaluator, ev_gender, password)
      VALUES ('$emNumber','$evName','$email','$PLE','$OE','$QM','$PE','$isAdmin','$isEvaluator','$gender','$password')";

      if ($_POST['password'] == $_POST['confirmPassword']) {
        $sqle = "SELECT * FROM evaluator WHERE email ='$email'";
        $resulte = $conn->query($sqle);

        if ($resulte->num_rows == 0) {
          $result = $conn->query($sql);

          if ($result == TRUE) {
            echo "<script>alert('Evaluator Added Successfully!');
            window.location.href='Evaluators.php';
            </script>";
          } else {
            echo "<script>alert('Error: " . $conn->error . "');
            window.location.href='evaluator.php';
            </script>";
          }
        } else {
          echo "<script>alert('Email is Used by Another User!');
            window.location.href='evaluator.php';
            </script>";
        }
      } else {
        echo "<script>alert('Passwords Don\'t Match!');
        window.location.href='evaluator.php';
        </script>";
      }
    }
  }

  // Handle UPDATE evaluator submission
  if (isset($_POST['update'])) {
    $emNumber = $_POST['emNumber'];
    $evName = $_POST['evName'];
    $email = $_POST['email'];
    $gender = $_POST['gender'];
    $isAdmin = isset($_POST['isAdmin']) ? $_POST['isAdmin'] : 0;
    $isEvaluator = isset($_POST['isEvaluator']) ? $_POST['isEvaluator'] : 0;
    $newPassword = $_POST['newPassword'];
    $oldPassword = $_POST['oldPassword'];

    $PLE = $OE = $QM = $PE = 0;
    if(isset($_POST['check_list'])) {
      foreach ($_POST['check_list'] as $checkbox) {
        if ($checkbox == 'PLE') {
          $PLE = 1;
        } else if ($checkbox == 'OE') {
          $OE = 1;
        } else if ($checkbox == 'QM') {
          $QM = 1;
        } else if ($checkbox == 'PE') {
          $PE = 1;
        }
      }
    }

    // Check if email exists for another user
    $sqle = "SELECT * FROM evaluator WHERE email ='$email' AND evaluator_id != '$emNumber'";
    $resulte = $conn->query($sqle);

    if ($resulte->num_rows > 0) {
      echo "<script>alert('Email is Used by Another User!');
      window.location.href='evaluator.php?id=$emNumber';
      </script>";
    } else {
      // Build update query based on password change
      if(empty($newPassword)) {
        // No password change
        $sql = "UPDATE evaluator SET 
                evaluator_name = '$evName', 
                email = '$email', 
                PLE = '$PLE', 
                OE = '$OE', 
                QM = '$QM', 
                PE = '$PE', 
                is_admin = '$isAdmin', 
                is_evaluator = '$isEvaluator', 
                ev_gender = '$gender'
                WHERE evaluator_id = '$emNumber'";
        
        $result = $conn->query($sql);
        
        if ($result == TRUE) {
          echo "<script>alert('Evaluator Updated Successfully!');
          window.location.href='Evaluators.php';</script>";
        } else {
          echo "<script>alert('Error: " . $conn->error . "');
          window.location.href='evaluator.php?id=$emNumber';
          </script>";
        }
      } else {
        // Password change requested
        if ($newPassword != $_POST['confirmPassword']) {
          echo "<script>alert('Passwords Don\'t Match!');
          window.location.href='evaluator.php?id=$emNumber';
          </script>";
        } else {
          // Verify old password
          $checkPassSql = "SELECT password FROM evaluator WHERE evaluator_id = '$emNumber'";
          $passResult = $conn->query($checkPassSql);
          $passRow = $passResult->fetch_assoc();
          
          if(md5($oldPassword) == $passRow['password']) {
            $hashedPassword = md5($newPassword);
            $sql = "UPDATE evaluator SET 
                    evaluator_name = '$evName', 
                    email = '$email', 
                    PLE = '$PLE', 
                    OE = '$OE', 
                    QM = '$QM', 
                    PE = '$PE', 
                    is_admin = '$isAdmin', 
                    is_evaluator = '$isEvaluator', 
                    ev_gender = '$gender',
                    password = '$hashedPassword'
                    WHERE evaluator_id = '$emNumber'";
            
            $result = $conn->query($sql);
            
            if ($result == TRUE) {
              echo "<script>alert('Evaluator Updated Successfully!');
              window.location.href='Evaluators.php';</script>";
            } else {
              echo "<script>alert('Error: " . $conn->error . "');
              window.location.href='evaluator.php?id=$emNumber';
              </script>";
            }
          } else {
            echo "<script>alert('Old Password is Incorrect!');
            window.location.href='evaluator.php?id=$emNumber';
            </script>";
          }
        }
      }
    }
  }
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8" />
  <?php if (isset($_GET["id"])) { ?>
    <title>Update Evaluator</title>
  <?php } else { ?>
    <title>Add Evaluator</title>
  <?php } ?>
  <link rel="icon" type="image/x-icon" href="./img/jb.png">
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />

  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />

  <!-- Bootstrap 5 -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet" />
  
  <!-- Boxicons -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/boxicons@latest/css/boxicons.min.css" />

  <!-- Custom CSS -->
  <link rel="stylesheet" href="./style.css" />
  <link rel="stylesheet" href="css/style.css" />

  <style>
    :root {
      --primary-color: #4361ee;
      --secondary-color: #3a0ca3;
      --light-bg: #fafcf8ff;
      --border-radius: 12px;
      --box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    }

    /* System Alert Styles */
    .system-alert {
      position: fixed;
      top: 70px;
      left: 50%;
      transform: translateX(-50%);
      width: 90%;
      max-width: 800px;
      z-index: 1000;
      margin-top: 10px;
    }

    /* Disabled nav links */
    .nav_link.disabled {
      opacity: 0.5 !important;
      cursor: not-allowed !important;
    }
    
    .nav_link.disabled:hover {
      color: inherit !important;
      background-color: inherit !important;
    }
    
    /* Freeze/Unfreeze button */
    .freeze-btn {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      padding: 10px 20px;
      border-radius: 50px;
      font-weight: 600;
      border: none;
      cursor: pointer;
      transition: all 0.3s ease;
      font-size: 14px;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }
    
    .freeze-btn.frozen {
      background-color: #ef4444;
      color: white;
    }
    
    .freeze-btn.unfrozen {
      background-color: #10b981;
      color: white;
    }
    
    .freeze-btn:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 16px rgba(0, 0, 0, 0.15);
    }
    
    .freeze-btn i {
      font-size: 16px;
    }

    .three-section-container {
      padding: 20px;
      max-width: 1400px;
      margin: 0 auto;
      height: calc(100vh - 70px);
      display: flex;
      flex-direction: column;
    }

    .form-header-minimal {
      background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
      color: white;
      padding: 1.5rem 2rem;
      border-radius: var(--border-radius) var(--border-radius) 0 0;
      flex-shrink: 0;
    }

    .form-header-minimal h1 {
      font-size: 1.8rem;
      font-weight: 600;
      margin: 0;
      display: flex;
      align-items: center;
      gap: 12px;
    }

    .form-header-minimal p {
      color: #000;
      opacity: 0.9;
      margin: 0.5rem 0 0 0;
      font-size: 1rem;
    }

    .three-section-body {
      background: white;
      border-radius: 0 0 var(--border-radius) var(--border-radius);
      box-shadow: var(--box-shadow);
      padding: 0;
      flex: 1;
      display: grid;
      grid-template-columns: 1fr 1fr 1fr;
      gap: 0;
      overflow: hidden;
    }

    @media (max-width: 1200px) {
      .three-section-body {
        grid-template-columns: 1fr;
        overflow-y: auto;
      }
    }

    .form-section-column {
      padding: 1.8rem;
      border-right: 1px solid #e2e8f0;
      overflow-y: auto;
      height: 100%;
    }

    .form-section-column:last-child {
      border-right: none;
    }

    @media (max-width: 1200px) {
      .form-section-column {
        border-right: none;
        border-bottom: 1px solid #e2e8f0;
        overflow-y: visible;
        height: auto;
      }
      
      .form-section-column:last-child {
        border-bottom: none;
      }
    }

    .section-title-minimal {
      font-size: 1.2rem;
      font-weight: 600;
      color: var(--secondary-color);
      margin-bottom: 1.5rem;
      padding-bottom: 0.8rem;
      border-bottom: 2px solid #e2e8f0;
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .section-title-minimal i {
      background: var(--primary-color);
      color: white;
      width: 36px;
      height: 36px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1rem;
    }

    .form-group-vertical {
      margin-bottom: 1.5rem;
    }

    .form-group-vertical label {
      display: block;
      font-weight: 500;
      color: #4a5568;
      margin-bottom: 0.5rem;
      font-size: 0.95rem;
    }

    .form-control-vertical {
      width: 100%;
      padding: 0.8rem 1rem;
      border: 2px solid #e2e8f0;
      border-radius: 8px;
      font-size: 1rem;
      transition: all 0.2s ease;
      background: white;
    }

    .form-control-vertical:focus {
      border-color: var(--primary-color);
      box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.1);
      outline: none;
    }

    .form-control-vertical[readonly] {
      background: #f8fafc;
      color: #64748b;
      cursor: not-allowed;
    }

    .radio-group-vertical {
      display: flex;
      gap: 1rem;
      margin-top: 0.5rem;
    }

    .radio-item-vertical {
      flex: 1;
    }

    .radio-item-vertical input[type="radio"] {
      display: none;
    }

    .radio-item-vertical label {
      display: block;
      padding: 0.8rem 1rem;
      background: #262728ff;
      border: 2px solid #e2e8f0;
      border-radius: 8px;
      text-align: center;
      font-weight: 500;
      cursor: pointer;
      transition: all 0.2s ease;
      margin: 0;
    }

    .radio-item-vertical input[type="radio"]:checked + label {
      background: var(--primary-color);
      color: white;
      border-color: var(--primary-color);
    }

    .role-badges-vertical {
      display: flex;
      flex-direction: column;
      gap: 1rem;
      margin-top: 1rem;
    }

    .role-badge-vertical {
      display: flex;
      align-items: center;
      gap: 12px;
      padding: 1rem 1.2rem;
      background: #23272bff;
      border: 2px solid #e2e8f0;
      border-radius: 10px;
      cursor: pointer;
      transition: all 0.2s ease;
    }

    .role-badge-vertical:hover {
      border-color: var(--primary-color);
      transform: translateY(-2px);
    }

    .role-badge-vertical.active {
      background: var(--primary-color);
      border-color: var(--primary-color);
      color: white;
    }

    .role-badge-vertical input[type="checkbox"] {
      display: none;
    }

    .role-badge-vertical i {
      font-size: 1.2rem;
      width: 24px;
    }

    .role-badge-vertical span {
      font-weight: 500;
      font-size: 1rem;
    }

    .checkbox-grid-vertical {
      display: grid;
      grid-template-columns: 1fr;
      gap: 1rem;
      margin-top: 1rem;
    }

    .checkbox-item-vertical {
      display: flex;
      align-items: center;
      gap: 12px;
      padding: 1rem 1.2rem;
      background: #f8fafc;
      border: 2px solid #e2e8f0;
      border-radius: 10px;
      transition: all 0.2s ease;
    }

    .checkbox-item-vertical:hover {
      border-color: var(--primary-color);
    }

    .checkbox-item-vertical input[type="checkbox"] {
      width: 20px;
      height: 20px;
      accent-color: var(--primary-color);
      cursor: pointer;
    }

    .checkbox-item-vertical label {
      margin: 0;
      font-weight: 500;
      color: #4a5568;
      cursor: pointer;
      flex: 1;
      font-size: 1rem;
    }

    .form-hint {
      font-size: 0.85rem;
      color: #64748b;
      margin-top: 0.5rem;
      display: flex;
      align-items: center;
      gap: 6px;
    }

    .form-hint i {
      font-size: 0.9rem;
    }

    .password-group-vertical {
      display: flex;
      flex-direction: column;
      gap: 1.2rem;
    }

    .form-actions-bottom {
      grid-column: span 3;
      padding: 1.5rem 2rem;
      background: #f8fafc;
      border-top: 1px solid #e2e8f0;
      display: flex;
      justify-content: center;
      gap: 1.5rem;
    }

    @media (max-width: 1200px) {
      .form-actions-bottom {
        grid-column: span 1;
      }
    }

    .btn-action {
      padding: 0.9rem 2.5rem;
      border-radius: 8px;
      font-weight: 600;
      font-size: 1rem;
      border: none;
      cursor: pointer;
      transition: all 0.2s ease;
      display: flex;
      align-items: center;
      gap: 10px;
      min-width: 160px;
      justify-content: center;
    }

    .btn-cancel-action {
      background: white;
      color: #64748b;
      border: 2px solid #cbd5e1;
    }

    .btn-cancel-action:hover {
      background: #f1f5f9;
      transform: translateY(-2px);
    }

    .btn-submit-action {
      background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
      color: white;
      box-shadow: 0 4px 12px rgba(67, 97, 238, 0.3);
    }

    .btn-submit-action:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 16px rgba(67, 97, 238, 0.4);
    }

    /* Hide scrollbar for Chrome, Safari and Opera */
    .form-section-column::-webkit-scrollbar {
      width: 6px;
    }

    .form-section-column::-webkit-scrollbar-track {
      background: #f1f1f1;
      border-radius: 3px;
    }

    .form-section-column::-webkit-scrollbar-thumb {
      background: #c1c1c1;
      border-radius: 3px;
    }

    .form-section-column::-webkit-scrollbar-thumb:hover {
      background: #a8a8a8;
    }

    /* Ensure sections fill available height */
    .three-section-container {
      min-height: calc(100vh - 70px);
    }

    .three-section-body {
      min-height: 500px;
    }

    @media (max-height: 700px) {
      .three-section-body {
        grid-template-columns: 1fr;
        overflow-y: auto;
      }
      
      .form-section-column {
        min-height: auto;
      }
    }
  </style>

  <script type="text/javascript">
    function validate() {
      var letters = /^[A-Z a-z]+$/;
      var nameField = document.forms["addForm"]["evName"];
      
      if (nameField && !nameField.value.match(letters)) {
        alert("Containing Invalid Characters in Evaluator Name. Only letters and spaces allowed.");
        return false;
      }
      
      // Validate password match for CREATE
      var password = document.forms["addForm"]["password"];
      var confirmPassword = document.forms["addForm"]["confirmPassword"];
      
      if (password && confirmPassword && password.value != confirmPassword.value) {
        alert("Passwords don't match!");
        return false;
      }
      
      // Validate password match for UPDATE if new password is entered
      var newPassword = document.forms["addForm"]["newPassword"];
      var confirmNewPassword = document.forms["addForm"]["confirmPassword"];
      
      if (newPassword && confirmNewPassword && newPassword.value != "" && newPassword.value != confirmNewPassword.value) {
        alert("New passwords don't match!");
        return false;
      }
      
      return true;
    }

    document.addEventListener('DOMContentLoaded', function() {
      // Role badge toggle
      document.querySelectorAll('.role-badge-vertical').forEach(badge => {
        badge.addEventListener('click', function() {
          const checkbox = this.querySelector('input[type="checkbox"]');
          if(checkbox && !checkbox.disabled) {
            checkbox.checked = !checkbox.checked;
            this.classList.toggle('active');
          }
        });
      });

      // Auto format employee number for CREATE
      const empInput = document.querySelector('input[name="emNumber"]');
      if (empInput && empInput.value === 'E-') {
        empInput.addEventListener('input', function(e) {
          if (!this.value.startsWith('E-')) {
            this.value = 'E-' + this.value.replace('E-', '');
          }
        });
      }

      // Real-time password validation
      document.querySelectorAll('input[type="password"]').forEach(input => {
        input.addEventListener('input', function() {
          const confirmInput = document.querySelector('input[name="confirmPassword"]');
          const passwordInput = document.querySelector('input[name="password"], input[name="newPassword"]');
          
          if (confirmInput && passwordInput) {
            if (confirmInput.value && passwordInput.value !== confirmInput.value) {
              confirmInput.style.borderColor = '#dc3545';
            } else if (confirmInput.value) {
              confirmInput.style.borderColor = '#28a745';
            } else {
              confirmInput.style.borderColor = '#e2e8f0';
            }
          }
        });
      });
    });
  </script>
</head>

<body id="body-pd" class="content <?php 
  if ($_SESSION['isAdmin']) {
    echo "Admin";
  } else if ($_SESSION["isEvaluator"]) {
    echo "Evaluator";
  } else {
    echo "Guest";
  } 
?>">
  <header class="header <?php 
    if ($_SESSION['isAdmin']) {
      echo "Admin";
    } else if ($_SESSION["isEvaluator"]) {
      echo "Evaluator";
    } else {
      echo "Guest";
    } 
  ?>" id="header">
    <div class="header_toggle"> <i class='bx bx-menu' id="header-toggle"></i> </div>
    <?php if (isset($_SESSION["user"])) { ?>
      <h5 style="font-weight: bold; text-transform: capitalize;"><?php echo $_SESSION["user"]; ?></h5>
    <?php } else { ?>
      <div class="header_img"> <a href="./signin.php"><i class='bx bxs-user nav_icon' style="padding: 1vh;"></i></a> </div>
    <?php } ?>
  </header>

  <div class="l-navbar <?php 
    if ($_SESSION['isAdmin']) {
      echo "Admin";
    } else if ($_SESSION["isEvaluator"]) {
      echo "Evaluator";
    } else {
      echo "Guest";
    } 
  ?>" id="nav-bar">
    <nav class="nav">
      <div> 
        <a href="./index.php" class="nav_logo" style="color: #ffffff; font-weight: bold;"> 
          <i class='bx bxs-dashboard me-2'></i>
          <span class="nav_logo-name" style="font-weight: normal;">Employee Evaluation</span> 
        </a>
        <div class="nav_list">
          <a href="./Categories.php" class="nav_link"> <i class='bx bx-category nav_icon'></i> <span class="nav_name">Categories</span> </a>
          <a href="./AttributeCategories.php" class="nav_link"> <i class='bx bx-spreadsheet nav_icon'></i> <span class="nav_name">Attributes</span> </a>
          <a href="./ScoringMethods.php" class="nav_link"> <i class='bx bx-tachometer nav_icon'></i> <span class="nav_name">Marking Schemes</span> </a>
          <a href="./Evaluators.php" class="nav_link active"> <i class='bx bxs-user-detail nav_icon'></i> <span class="nav_name">Evaluators</span> </a>
          <a href="./Employees.php" class="nav_link"> <i class='bx bx-user nav_icon'></i><span class="nav_name">Evaluatees</span> </a>
          
          <?php if ($is_frozen == '1'): ?>
            <!-- Disabled nav links when system is frozen -->
            <a href="javascript:void(0)" class="nav_link disabled">
              <i class='bx bx-bar-chart-alt-2 nav_icon'></i> 
              <span class="nav_name">Evaluate by Individual <small class="text-danger">(Frozen)</small></span>
            </a>
            <a href="javascript:void(0)" class="nav_link disabled">
              <i class='bx bx-grid-alt nav_icon'></i> 
              <span class="nav_name">Evaluate by Warehouse <small class="text-danger">(Frozen)</small></span>
            </a>
          <?php else: ?>
            <!-- Active nav links when system is not frozen -->
            <a href="./namely_evaluation.php" class="nav_link"> <i class='bx bx-bar-chart-alt-2 nav_icon'></i> <span class="nav_name">Evaluate by Individual</span> </a>
            <a href="./Warehouses.php" class="nav_link"> <i class='bx bx-grid-alt nav_icon'></i> <span class="nav_name">Evaluate by Warehouse</span> </a>
          <?php endif; ?>
          
          <a href="./periodRatings.php" class="nav_link"> <i class='bx bxs-star-half'></i> <span class="nav_name">Results & Grading</span> </a>
        </div>
      </div>
      <div>
        <?php if ($_SESSION['isAdmin']): ?>
          <!-- Freeze/Unfreeze button for Admin -->
          <form method="POST" action="index.php" style="margin-bottom: 10px;">
            <input type="hidden" name="freeze_status" value="<?php echo $is_frozen; ?>">
            <button type="submit" name="toggle_freeze" class="nav_link freeze-btn <?php echo $is_frozen == '1' ? 'frozen' : 'unfrozen'; ?>" style="border: none; width: 100%;">
              <i class='bx <?php echo $is_frozen == '1' ? 'bx-lock-open' : 'bx-lock'; ?>'></i>
              <span class="nav_name"><?php echo $is_frozen == '1' ? 'Unfreeze System' : 'Freeze System'; ?></span>
            </button>
          </form>
        <?php endif; ?>
        
        <form enctype="multipart/form-data" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST">
          <button type="submit" name="signout" class="nav_link <?php 
            if ($_SESSION['isAdmin']) {
              echo "Admin";
            } else if ($_SESSION["isEvaluator"]) {
              echo "Evaluator";
            } else {
              echo "Guest";
            } 
          ?>" style="background-color: #666; border: none; width: 100%;"> 
            <i class='bx bx-log-out nav_icon'></i> 
            <span class="nav_name">SignOut</span> 
          </button>
        </form>
      </div>
    </nav>
  </div>

  <div class="three-section-container" style="margin-top: 70px;">
    <?php 
    if (isset($_GET["id"])) {
      $id = $_GET["id"];
      $sql = "SELECT * FROM evaluator WHERE evaluator_id ='$id'";
      $result = $conn->query($sql);
      $row = $result->fetch_assoc();
    ?>
    
    <!-- START OF FORM for UPDATE -->
    <form name="addForm" enctype="multipart/form-data" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>?id=<?php echo $id; ?>" onsubmit="return validate()" method="POST">
      
      <div class="form-header-minimal">
        <h1><i class="fas fa-user-edit"></i> Update Evaluator</h1>
        <p>Modify evaluator details and permissions</p>
      </div>
      
      <div class="three-section-body">
        <!-- Section 1: Basic Information -->
        <div class="form-section-column">
          <div class="section-title-minimal">
            <i class="fas fa-user"></i>
            <span>Basic Information</span>
          </div>
          
          <div class="form-group-vertical">
            <label>Employee Number</label>
            <input type="text" name="emNumber" class="form-control-vertical" value="<?php echo $row['evaluator_id']; ?>" readonly />
            <div class="form-hint">
              <i class="fas fa-info-circle"></i> ID cannot be modified
            </div>
          </div>
          
          <div class="form-group-vertical">
            <label>Full Name</label>
            <input type="text" name="evName" class="form-control-vertical" placeholder="Enter full name" value="<?php echo $row['evaluator_name']; ?>" required />
          </div>
          
          <div class="form-group-vertical">
            <label>Email Address</label>
            <input type="email" name="email" class="form-control-vertical" placeholder="email@example.com" value="<?php echo $row['email']; ?>" required />
          </div>
          
          <div class="form-group-vertical">
            <label>Gender</label>
            <div class="radio-group-vertical">
              <div class="radio-item-vertical">
                <input type="radio" id="male" name="gender" value="Male" <?php echo $row['ev_gender'] == 'Male' ? 'checked' : ''; ?> />
                <label for="male">Male</label>
              </div>
              <div class="radio-item-vertical">
                <input type="radio" id="female" name="gender" value="Female" <?php echo $row['ev_gender'] == 'Female' ? 'checked' : ''; ?> />
                <label for="female">Female</label>
              </div>
            </div>
          </div>
        </div>
        
        <!-- Section 2: Password Update -->
        <div class="form-section-column">
          <div class="section-title-minimal">
            <i class="fas fa-key"></i>
            <span>Password Update</span>
          </div>
          
          <div class="password-group-vertical">
            <div class="form-group-vertical">
              <label>Current Password</label>
              <input type="password" name="oldPassword" class="form-control-vertical" placeholder="Enter current password" />
              <div class="form-hint">
                <i class="fas fa-info-circle"></i> Required only if changing password
              </div>
            </div>
            
            <div class="form-group-vertical">
              <label>New Password</label>
              <input type="password" name="newPassword" class="form-control-vertical" placeholder="Enter new password" />
            </div>
            
            <div class="form-group-vertical">
              <label>Confirm New Password</label>
              <input type="password" name="confirmPassword" class="form-control-vertical" placeholder="Re-enter new password" />
              <div class="form-hint">
                <i class="fas fa-shield-alt"></i> Use 8+ characters with letters & numbers
              </div>
            </div>
          </div>
        </div>
        
        <!-- Section 3: User Roles & Permissions -->
        <div class="form-section-column">
          <div class="section-title-minimal">
            <i class="fas fa-user-tag"></i>
            <span>User Roles</span>
          </div>
          
          <div class="role-badges-vertical">
            <div class="role-badge-vertical <?php echo $row['is_admin'] ? 'active' : ''; ?>">
              <input type="checkbox" id="isAdmin" name="isAdmin" value="1" <?php echo $row['is_admin'] ? 'checked' : ''; ?> />
              <i class="fas fa-user-shield"></i>
              <span>Administrator</span>
              <div style="flex: 1;"></div>
            </div>
            
            <div class="role-badge-vertical <?php echo $row['is_evaluator'] || $row['is_admin'] ? 'active' : ''; ?>">
              <input type="checkbox" id="isEvaluator" name="isEvaluator" value="1" <?php echo $row['is_evaluator'] || $row['is_admin'] ? 'checked' : ''; ?> />
              <i class="fas fa-clipboard-check"></i>
              <span>Evaluator</span>
              <div style="flex: 1;"></div>
            </div>
            
            <div class="role-badge-vertical active">
              <input type="checkbox" checked disabled />
              <i class="fas fa-user"></i>
              <span>Guest Access</span>
              <div style="flex: 1;"></div>
            </div>
          </div>
          
          <div class="section-title-minimal" style="margin-top: 2rem;">
            <i class="fas fa-list-check"></i>
            <span>Access Categories</span>
          </div>
          
          <div class="checkbox-grid-vertical">
            <div class="checkbox-item-vertical">
              <input type="checkbox" id="PLE" name="check_list[]" value="PLE" <?php echo $row['PLE'] ? 'checked' : ''; ?> />
              <label for="PLE">PLE (Professional & Leadership Excellence)</label>
            </div>
            
            <div class="checkbox-item-vertical">
              <input type="checkbox" id="OE" name="check_list[]" value="OE" <?php echo $row['OE'] ? 'checked' : ''; ?> />
              <label for="OE">OE (Operational Excellence)</label>
            </div>
            
            <div class="checkbox-item-vertical">
              <input type="checkbox" id="QM" name="check_list[]" value="QM" <?php echo $row['QM'] ? 'checked' : ''; ?> />
              <label for="QM">QM (Quality Management)</label>
            </div>
            
            <div class="checkbox-item-vertical">
              <input type="checkbox" id="PE" name="check_list[]" value="PE" <?php echo $row['PE'] ? 'checked' : ''; ?> />
              <label for="PE">PE (Performance Excellence)</label>
            </div>
          </div>
          
          <div class="form-hint" style="margin-top: 1.5rem;">
            <i class="fas fa-lightbulb"></i> Select categories this evaluator can access
          </div>
        </div>
        
        <!-- Form Actions -->
        <div class="form-actions-bottom">
          <a href="Evaluators.php" class="btn-action btn-cancel-action">
            <i class="fas fa-times"></i>
            Cancel
          </a>
          <button type="submit" name="update" class="btn-action btn-submit-action">
            <i class="fas fa-save"></i>
            Save Changes
          </button>
        </div>
      </div>
    </form>
    <!-- END OF FORM for UPDATE -->
    
    <?php } else { ?>
    
    <!-- START OF FORM for CREATE -->
    <form name="addForm" enctype="multipart/form-data" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" onsubmit="return validate()" method="POST">
      
      <div class="form-header-minimal">
        <h1><i class="fas fa-user-plus"></i> Add New Evaluator</h1>
        <p>Create a new evaluator account with specific permissions</p>
      </div>
      
      <div class="three-section-body">
        <!-- Section 1: Basic Information -->
        <div class="form-section-column">
          <div class="section-title-minimal">
            <i class="fas fa-user"></i>
            <span>Basic Information</span>
          </div>
          
          <div class="form-group-vertical">
            <label>Employee Number</label>
            <input type="text" name="emNumber" class="form-control-vertical" placeholder="E-001" value="E-" maxlength="5" pattern="E-[0-9]{3}" required />
            <div class="form-hint">
              <i class="fas fa-info-circle"></i> Format: E- followed by 3 digits
            </div>
          </div>
          
          <div class="form-group-vertical">
            <label>Full Name</label>
            <input type="text" name="evName" class="form-control-vertical" placeholder="Enter full name" required />
          </div>
          
          <div class="form-group-vertical">
            <label>Email Address</label>
            <input type="email" name="email" class="form-control-vertical" placeholder="email@example.com" required />
          </div>
          
          <div class="form-group-vertical">
            <label>Gender</label>
            <div class="radio-group-vertical">
              <div class="radio-item-vertical">
                <input type="radio" id="male" name="gender" value="Male" checked />
                <label for="male">Male</label>
              </div>
              <div class="radio-item-vertical">
                <input type="radio" id="female" name="gender" value="Female" />
                <label for="female">Female</label>
              </div>
            </div>
          </div>
        </div>
        
        <!-- Section 2: Password -->
        <div class="form-section-column">
          <div class="section-title-minimal">
            <i class="fas fa-key"></i>
            <span>Account Security</span>
          </div>
          
          <div class="password-group-vertical">
            <div class="form-group-vertical">
              <label>Password</label>
              <input type="password" name="password" class="form-control-vertical" placeholder="Create password" required />
            </div>
            
            <div class="form-group-vertical">
              <label>Confirm Password</label>
              <input type="password" name="confirmPassword" class="form-control-vertical" placeholder="Re-enter password" required />
              <div class="form-hint">
                <i class="fas fa-shield-alt"></i> Use 8+ characters with letters & numbers
              </div>
            </div>
            
            <div class="form-hint" style="margin-top: 1rem;">
              <i class="fas fa-lightbulb"></i> Password is encrypted for security
            </div>
          </div>
        </div>
        
        <!-- Section 3: User Roles & Permissions -->
        <div class="form-section-column">
          <div class="section-title-minimal">
            <i class="fas fa-user-tag"></i>
            <span>User Roles</span>
          </div>
          
          <div class="role-badges-vertical">
            <div class="role-badge-vertical">
              <input type="checkbox" id="isAdmin" name="isAdmin" value="1" />
              <i class="fas fa-user-shield"></i>
              <span>Administrator</span>
              <div style="flex: 1;"></div>
            </div>
            
            <div class="role-badge-vertical">
              <input type="checkbox" id="isEvaluator" name="isEvaluator" value="1" />
              <i class="fas fa-clipboard-check"></i>
              <span>Evaluator</span>
              <div style="flex: 1;"></div>
            </div>
            
            <div class="role-badge-vertical active">
              <input type="checkbox" checked disabled />
              <i class="fas fa-user"></i>
              <span>Guest Access</span>
              <div style="flex: 1;"></div>
            </div>
          </div>
          
          <div class="section-title-minimal" style="margin-top: 2rem;">
            <i class="fas fa-list-check"></i>
            <span>Access Categories</span>
          </div>
          
          <div class="checkbox-grid-vertical">
            <div class="checkbox-item-vertical">
              <input type="checkbox" id="PLE" name="check_list[]" value="PLE" />
              <label for="PLE">PLE (Professional & Leadership Excellence)</label>
            </div>
            
            <div class="checkbox-item-vertical">
              <input type="checkbox" id="OE" name="check_list[]" value="OE" />
              <label for="OE">OE (Operational Excellence)</label>
            </div>
            
            <div class="checkbox-item-vertical">
              <input type="checkbox" id="QM" name="check_list[]" value="QM" />
              <label for="QM">QM (Quality Management)</label>
            </div>
            
            <div class="checkbox-item-vertical">
              <input type="checkbox" id="PE" name="check_list[]" value="PE" />
              <label for="PE">PE (Performance Excellence)</label>
            </div>
          </div>
          
          <div class="form-hint" style="margin-top: 1.5rem;">
            <i class="fas fa-lightbulb"></i> Select categories this evaluator can access
          </div>
        </div>
        
        <!-- Form Actions -->
        <div class="form-actions-bottom">
          <a href="Evaluators.php" class="btn-action btn-cancel-action">
            <i class="fas fa-times"></i>
            Cancel
          </a>
          <button type="submit" name="submit" class="btn-action btn-submit-action">
            <i class="fas fa-user-plus"></i>
            Create Evaluator
          </button>
        </div>
      </div>
    </form>
    <!-- END OF FORM for CREATE -->
    
    <?php } ?>
  </div>

  <!-- Bootstrap JS Bundle -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
  
</body>

</html>

<?php } else {
  header("location: signin.php");
} ?>