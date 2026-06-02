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

  $sqlfind = "SELECT * FROM category";
  $resultfind = $conn->query($sqlfind);

  $sqlst = "SELECT * FROM store";
  $resultst = $conn->query($sqlst);

  if (isset($_POST['submit'])) {

    $empNumber = $_POST['empNumber'];
    $empName = $_POST['empName'];
    $category = $_POST['category'];
    $gender = $_POST['gender'];
    $currentStore = $_POST['currentStore'];
    $year = $_POST['year']; // Get the year from form

    $sqls = "SELECT * FROM employee WHERE emp_id = '$empNumber'";
    $results = $conn->query($sqls);

    if ($results->num_rows > 0) {
      echo "<script>alert('Employee Number is Already Exist!');
      window.location.href='employee.php';
      </script>";
    } else {
      // Start transaction
      $conn->begin_transaction();
      
      try {
        // Insert into employee table
        $sql = "INSERT INTO employee(emp_id, emp_name, current_category, current_store, gender) 
        VALUES ('$empNumber','$empName','$category','$currentStore','$gender')";
        $result = $conn->query($sql);

        // Insert into history table
        $sql_history = "INSERT INTO employee_store_history(emp_id, store, year) 
                        VALUES ('$empNumber','$currentStore','$year')";
        $result_history = $conn->query($sql_history);

        if ($result == TRUE && $result_history == TRUE) {
          $conn->commit();
          echo "<script>alert('Employee Added Successfully!');
          window.location.href='Employees.php';
          </script>";
        } else {
          $conn->rollback();
          echo "Error:" . $sql . "<br>" . $conn->error;
        }
      } catch (Exception $e) {
        $conn->rollback();
        echo "Error: " . $e->getMessage();
      }
    }
  }
?>

  <!DOCTYPE html>
  <html>

  <head>
    <meta charset="utf-8" />
    <?php if (isset($_GET["id"])) { ?>
      <title>Update Employee</title>
    <?php } else { ?>
      <title>Add Employee</title>
    <?php } ?>
    <link rel="icon" type="image/x-icon" href="./img/jb.png">
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta1/dist/css/bootstrap.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.2.1/jquery.min.js" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/boxicons@latest/css/boxicons.min.css" />
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="./style.css" />
    <script src="./script.js"></script>

    <style>
      :root {
        --primary-color: #4361ee;
        --primary-light: #eef2ff;
        --primary-dark: #3a56d4;
        --secondary-color: #7209b7;
        --success-color: #10b981;
        --danger-color: #ef4444;
        --warning-color: #f59e0b;
        --light-color: #f8f9fa;
        --dark-color: #1f2937;
        --gray-color: #6b7280;
        --border-color: #e5e7eb;
        --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
        --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        --radius-sm: 0.375rem;
        --radius-md: 0.5rem;
        --radius-lg: 0.75rem;
        --radius-xl: 1rem;
        --transition: all 0.3s ease;
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
        animation: slideDown 0.5s ease-out;
      }

      @keyframes slideDown {
        from {
          opacity: 0;
          transform: translateX(-50%) translateY(-20px);
        }
        to {
          opacity: 1;
          transform: translateX(-50%) translateY(0);
        }
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
        width: 100%;
        justify-content: center;
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

      * {
        font-family: 'Inter', sans-serif;
        box-sizing: border-box;
      }

      body {
        background-color: #f5f7fb;
        min-height: 100vh;
      }

      .wrapper {
        margin-top: 10vh;
        padding: 2rem;
        display: flex;
        justify-content: center;
        align-items: flex-start;
        min-height: calc(100vh - 10vh);
        width: 100%;
      }

      .modern-form-container {
        width: 100%;
        max-width: 800px;
        background: white;
        border-radius: var(--radius-xl);
        box-shadow: var(--shadow-lg);
        overflow: visible;
        transition: var(--transition);
        position: relative;
        position: relative;
        top: -40px;
      }

      .modern-form-container:hover {
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
      }

      .form-header-section {
        background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
        color: white;
        padding: 2.5rem;
        position: relative;
        overflow: hidden;
        border-radius: var(--radius-xl) var(--radius-xl) 0 0;
      }

      .form-header-section::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: linear-gradient(45deg, rgba(255, 255, 255, 0.1) 0%, transparent 100%);
      }

      .form-header {
        position: relative;
        z-index: 1;
        text-align: center;
      }

      .form-header h2 {
        font-size: 1.8rem;
        font-weight: 700;
        margin-bottom: 0.5rem;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.75rem;
      }

      .form-header h2 i {
        font-size: 1.6rem;
      }

      .form-header p {
        opacity: 0.9;
        font-size: 1rem;
        margin: 0;
      }

      .form-body {
        padding: 2.5rem;
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 1.5rem;
      }

      .form-group {
        margin-bottom: 0;
      }

      .form-group.full-width {
        grid-column: 1 / -1;
      }

      .form-group label {
        display: block;
        margin-bottom: 0.5rem;
        font-weight: 600;
        color: var(--dark-color);
        font-size: 0.9rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
      }

      .form-control-modern {
        width: 100%;
        padding: 0.875rem 1rem;
        border: 2px solid var(--border-color);
        border-radius: var(--radius-lg);
        font-size: 1rem;
        transition: var(--transition);
        background: white;
        color: var(--dark-color);
      }

      .form-control-modern:focus {
        outline: none;
        border-color: var(--primary-color);
        box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.1);
      }

      .form-control-modern::placeholder {
        color: var(--gray-color);
        opacity: 0.7;
      }

      .form-control-modern:read-only {
        background-color: #f9fafb;
        color: var(--gray-color);
        cursor: not-allowed;
      }

      /* Select Styling */
      .select-modern {
        position: relative;
      }

      .select-modern select {
        appearance: none;
        width: 100%;
        padding: 0.875rem 1rem;
        border: 2px solid var(--border-color);
        border-radius: var(--radius-lg);
        font-size: 1rem;
        background: white;
        color: var(--dark-color);
        cursor: pointer;
        transition: var(--transition);
      }

      .select-modern select:focus {
        outline: none;
        border-color: var(--primary-color);
        box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.1);
      }

      .select-modern::after {
        content: '\f078';
        font-family: 'Font Awesome 6 Free';
        font-weight: 900;
        position: absolute;
        right: 1rem;
        top: 50%;
        transform: translateY(-50%);
        color: var(--gray-color);
        pointer-events: none;
      }

      /* Radio Button Styling */
      .gender-selection {
        display: flex;
        gap: 1rem;
        margin-top: 0.5rem;
      }

      .gender-option {
        position: relative;
        flex: 1;
      }

      .gender-option input[type="radio"] {
        position: absolute;
        opacity: 0;
        width: 0;
        height: 0;
      }

      .gender-label {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        padding: 0.875rem 1rem;
        border: 2px solid var(--border-color);
        border-radius: var(--radius-lg);
        background: white;
        color: var(--gray-color);
        font-weight: 600;
        cursor: pointer;
        transition: var(--transition);
        text-align: center;
      }

      .gender-label i {
        font-size: 1.25rem;
      }

      .gender-option input[type="radio"]:checked + .gender-label {
        border-color: var(--primary-color);
        background: var(--primary-light);
        color: var(--primary-color);
        box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.1);
      }

      .gender-option input[type="radio"]:focus + .gender-label {
        outline: 2px solid var(--primary-color);
        outline-offset: 2px;
      }

      /* Submit Button */
      .submit-section {
        grid-column: 1 / -1;
        margin-top: 1rem;
        padding-top: 1.5rem;
        border-top: 1px solid var(--border-color);
        text-align: center;
        display: flex;
        justify-content: center;
        gap: 1rem;
      }

      .submit-btn {
        background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
        color: white;
        border: none;
        padding: 0.875rem 2.5rem;
        border-radius: var(--radius-lg);
        font-size: 1rem;
        font-weight: 600;
        cursor: pointer;
        transition: var(--transition);
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        box-shadow: 0 4px 6px -1px rgba(67, 97, 238, 0.3);
        min-width: 180px;
      }

      .submit-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 12px -1px rgba(67, 97, 238, 0.4);
      }

      .submit-btn:active {
        transform: translateY(0);
      }

      .submit-btn i {
        font-size: 1.1rem;
      }

      .cancel-btn {
        background: white;
        color: var(--gray-color);
        border: 2px solid var(--border-color);
        padding: 0.875rem 2.5rem;
        border-radius: var(--radius-lg);
        font-size: 1rem;
        font-weight: 600;
        cursor: pointer;
        transition: var(--transition);
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        text-decoration: none;
        min-width: 180px;
        justify-content: center;
      }

      .cancel-btn:hover {
        background: #f8f9fa;
        border-color: var(--primary-color);
        color: var(--primary-color);
        transform: translateY(-2px);
      }

      /* Back Button */
      .back-btn {
        position: absolute;
        top: 2rem;
        left: 2rem;
        background: rgba(255, 255, 255, 0.2);
        color: white;
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        text-decoration: none;
        transition: var(--transition);
        z-index: 2;
      }

      .back-btn:hover {
        background: rgba(255, 255, 255, 0.3);
        transform: scale(1.1);
      }

      /* Form Status Indicators */
      .form-status {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        margin-top: 0.5rem;
        font-size: 0.875rem;
        color: var(--gray-color);
      }

      .form-status i {
        font-size: 1rem;
      }

      .status-valid {
        color: var(--success-color);
      }

      .status-invalid {
        color: var(--danger-color);
      }

      /* Required field indicator */
      .required::after {
        content: '*';
        color: var(--danger-color);
        margin-left: 4px;
      }

      /* Info boxes */
      .info-box {
        background: var(--primary-light);
        border-left: 4px solid var(--primary-color);
        padding: 1rem;
        border-radius: var(--radius-lg);
        margin-bottom: 1rem;
        grid-column: 1 / -1;
      }

      .info-box h4 {
        font-size: 0.95rem;
        margin-bottom: 0.5rem;
        color: var(--primary-color);
        display: flex;
        align-items: center;
        gap: 0.5rem;
      }

      .info-box p {
        font-size: 0.9rem;
        color: var(--dark-color);
        margin: 0;
        opacity: 0.9;
      }

      /* Responsive Design */
      @media (max-width: 992px) {
        .modern-form-container {
          max-width: 95%;
        }
        
        .form-body {
          padding: 2rem;
        }
      }

      @media (max-width: 768px) {
        .wrapper {
          padding: 1rem;
          margin-top: 8vh;
        }

        .modern-form-container {
          max-width: 100%;
        }

        .form-header-section {
          padding: 2rem 1.5rem;
        }

        .form-body {
          padding: 1.5rem;
          grid-template-columns: 1fr;
          gap: 1.25rem;
        }

        .form-header h2 {
          font-size: 1.5rem;
          justify-content: flex-start;
        }

        .gender-selection {
          flex-direction: column;
          gap: 0.75rem;
        }

        .submit-section {
          flex-direction: column;
          gap: 1rem;
        }

        .submit-btn, .cancel-btn {
          width: 100%;
          min-width: unset;
        }
      }

      /* Animation for form */
      @keyframes fadeIn {
        from {
          opacity: 0;
          transform: translateY(20px);
        }
        to {
          opacity: 1;
          transform: translateY(0);
        }
      }

      .modern-form-container {
        animation: fadeIn 0.5s ease-out;
      }

      /* Role-based colors */
      .modern-form-container.Admin .form-header-section {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      }

      .modern-form-container.Evaluator .form-header-section {
        background: linear-gradient(135deg, #4CAF50 0%, #2E7D32 100%);
      }

      .modern-form-container.Guest .form-header-section {
        background: linear-gradient(135deg, #757575 0%, #424242 100%);
      }

      /* Ensure no scrolling needed */
      body, html {
        overflow-x: hidden;
      }
      
      .form-body {
        overflow: visible;
      }
      
      .modern-form-container {
        overflow: visible;
      }
    </style>

    <script type="text/javascript">
      function validate() {
        var letters = /^[A-Z a-z]+$/;
        if (!document.addForm.empName.value.match(letters)) {
          alert("Containing Invalid Characters in Employee Name");
          return false;
        }

        return (true);
      }

      function formatEmployeeNumber(input) {
        // Ensure it starts with E- and has numbers after
        if (!input.value.startsWith('E-')) {
          input.value = 'E-' + input.value.replace(/[^0-9]/g, '');
        } else {
          // Keep only E- followed by numbers
          input.value = 'E-' + input.value.substring(2).replace(/[^0-9]/g, '');
        }
      }
    </script>
  </head>

  <body id="body-pd" class="content <?php if ($_SESSION['isAdmin']) {
                                      echo "Admin";
                                    } else if ($_SESSION["isEvaluator"]) {
                                      echo "Evaluator";
                                    } else {
                                      echo "Guest";
                                    } ?>">
    <header class="header <?php if ($_SESSION['isAdmin']) {
                            echo "Admin";
                          } else if ($_SESSION["isEvaluator"]) {
                            echo "Evaluator";
                          } else {
                            echo "Guest";
                          } ?>" id="header">
      <div class="header_toggle"> <i class='bx bx-menu' id="header-toggle"></i> </div>
      <?php if (isset($_SESSION["user"])) { ?>
        <h5 style="font-weight: bold; text-transform: capitalize;"><?php echo $_SESSION["user"]; ?></h5>
      <?php } else { ?>
        <div class="header_img"> <a href="./signin.php"><i class='bx bxs-user nav_icon' style="padding: 1vh;"></i></a> </div>
      <?php } ?>
    </header>

    <!-- System Status Alert -->
    <?php if ($is_frozen == '1'): ?>
      
    <?php endif; ?>

    <div class="l-navbar <?php if ($_SESSION['isAdmin']) {
                            echo "Admin";
                          } else if ($_SESSION["isEvaluator"]) {
                            echo "Evaluator";
                          } else {
                            echo "Guest";
                          } ?>" id="nav-bar">
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
            <a href="./Evaluators.php" class="nav_link"> <i class='bx bxs-user-detail nav_icon'></i> <span class="nav_name">Evaluators</span> </a>
            <a href="./Employees.php" class="nav_link active"> <i class='bx bx-user nav_icon'></i><span class="nav_name">Evaluatees</span> </a>
            
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
              <button type="submit" name="toggle_freeze" class="nav_link freeze-btn <?php echo $is_frozen == '1' ? 'frozen' : 'unfrozen'; ?>" style="border: none;">
                <i class='bx <?php echo $is_frozen == '1' ? 'bx-lock-open' : 'bx-lock'; ?>'></i>
                <span class="nav_name"><?php echo $is_frozen == '1' ? 'Unfreeze System' : 'Freeze System'; ?></span>
              </button>
            </form>
          <?php endif; ?>
          
          <form enctype="multipart/form-data" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST">
            <button type="submit" name="signout" class="nav_link <?php if ($_SESSION['isAdmin']) {
                                                                    echo "Admin";
                                                                  } else if ($_SESSION["isEvaluator"]) {
                                                                    echo "Evaluator";
                                                                  } else {
                                                                    echo "Guest";
                                                                  } ?>" style="background-color: #666; border: none; width: 100%;"> 
              <i class='bx bx-log-out nav_icon'></i> 
              <span class="nav_name">SignOut</span> 
            </button>
          </form>
        </div>
      </nav>
    </div>

    <div class="wrapper">
      <div class="modern-form-container <?php if ($_SESSION['isAdmin']) {
                                          echo "Admin";
                                        } else if ($_SESSION["isEvaluator"]) {
                                          echo "Evaluator";
                                        } else {
                                          echo "Guest";
                                        } ?>">
        
        <!-- Back Button -->
        <a href="Employees.php" class="back-btn">
          <i class='bx bx-arrow-back'></i>
        </a>

        <?php if (isset($_GET["id"])) {

          $id = $_GET["id"];

          $sql = "SELECT * FROM employee WHERE emp_id ='$id'";
          $result = $conn->query($sql);
          $row = $result->fetch_assoc();
          
          // Get current year assignment from history
          $currentYear = date('Y');
          $history_sql = "SELECT * FROM employee_store_history WHERE emp_id = '$id' AND year = '$currentYear'";
          $history_result = $conn->query($history_sql);
          $history_row = $history_result->fetch_assoc();
        ?>
          <form class="editForm" name="addForm" enctype="multipart/form-data" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" onsubmit="return validate()" method="POST">
            <div class="form-header-section">
              <div class="form-header">
                <h2><i class='bx bx-user-plus'></i> Update Employee</h2>
                <p>Update employee information in the system</p>
              </div>
            </div>
            
            <div class="form-body">
              <div class="info-box">
                <h4><i class='bx bx-info-circle'></i> Important Information</h4>
                <p>Update employee details. Employee ID cannot be changed once created.</p>
              </div>
              
              <div class="form-group full-width">
                <label for="empNumber" class="required">Employee Number</label>
                <input type="text" 
                       name="empNumber" 
                       id="empNumber" 
                       class="form-control-modern" 
                       value="<?php echo $row['emp_id'] ?>" 
                       readonly 
                       required 
                       placeholder="E-XXXX" />
                <div class="form-status">
                  <i class='bx bx-lock status-valid'></i>
                  <span>Employee ID is read-only</span>
                </div>
              </div>
              
              <div class="form-group full-width">
                <label for="empName" class="required">Full Name</label>
                <input type="text" 
                       name="empName" 
                       id="empName" 
                       class="form-control-modern" 
                       value="<?php echo $row['emp_name'] ?>" 
                       required 
                       placeholder="Enter employee's full name" />
                <div class="form-status">
                  <i class='bx bx-check-circle status-valid'></i>
                  <span>Enter letters only (A-Z, a-z, spaces)</span>
                </div>
              </div>
              
              <div class="form-group">
                <label for="category" class="required">Category</label>
                <div class="select-modern">
                  <select name="category" id="category" onchange="filter()" required>
                    <option value="">Select Category</option>
                    <?php 
                    // Reset pointer to beginning
                    $resultfind->data_seek(0);
                    if ($resultfind->num_rows > 0) { ?>
                      <?php while ($rowfind = $resultfind->fetch_assoc()) { ?>
                        <?php if ($rowfind['category_name'] != "Common") { ?>
                          <option value="<?php echo $rowfind['category_name'] ?>" 
                            <?php if ($rowfind['category_name'] == $row['current_category']) echo "selected"; ?>>
                            <?php echo $rowfind['category_name'] ?>
                          </option>
                        <?php } ?>
                      <?php } ?>
                    <?php } ?>
                  </select>
                </div>
              </div>
              
              <div class="form-group">
                <label class="required">Gender</label>
                <div class="gender-selection">
                  <div class="gender-option">
                    <input type="radio" 
                           name="gender" 
                           id="male" 
                           value="Male" 
                           <?php if ($row['gender'] == "Male") echo "checked"; ?> />
                    <label for="male" class="gender-label">
                      <i class='bx bx-male'></i>
                      <span>Male</span>
                    </label>
                  </div>
                  
                  <div class="gender-option">
                    <input type="radio" 
                           name="gender" 
                           id="female" 
                           value="Female" 
                           <?php if ($row['gender'] == "Female") echo "checked"; ?> />
                    <label for="female" class="gender-label">
                      <i class='bx bx-female'></i>
                      <span>Female</span>
                    </label>
                  </div>
                </div>
              </div>
              
              <div class="form-group full-width">
                <label for="year" class="required">Assignment Year</label>
                <div class="select-modern">
                  <select name="year" id="year" required>
                    <option value="">Select Year</option>
                    <?php 
                    $currentYear = date('Y');
                    for($y = $currentYear - 2; $y <= $currentYear + 1; $y++) {
                      $selected = ($history_row && $history_row['year'] == $y) ? 'selected' : '';
                      echo "<option value='$y' $selected>$y</option>";
                    }
                    ?>
                  </select>
                </div>
                <div class="form-status">
                  <i class='bx bx-calendar'></i>
                  <span>Select the year for this store assignment</span>
                </div>
              </div>
              
              <div class="form-group full-width">
                <label for="currentStore" class="required">Store for Selected Year</label>
                <div class="select-modern">
                  <select name="currentStore" id="currentStore" required>
                    <option value="">Select Store</option>
                    <?php 
                    // Reset pointer to beginning
                    $resultst->data_seek(0);
                    if ($resultst->num_rows > 0) { ?>
                      <?php while ($rowst = $resultst->fetch_assoc()) { ?>
                        <option value="<?php echo $rowst['store_number'] ?>" 
                                data-related-to="<?php echo $rowst['category_name'] ?>" 
                                <?php if ($rowst['store_number'] == $row['current_store']) echo "selected"; ?>>
                          Store <?php echo $rowst['store_number'] ?> (<?php echo $rowst['category_name'] ?>)
                        </option>
                      <?php } ?>
                    <?php } ?>
                  </select>
                </div>
                <div class="form-status">
                  <i class='bx bx-info-circle'></i>
                  <span>Only stores matching selected category will be shown</span>
                </div>
              </div>
              
              <div class="submit-section">
                <a href="Employees.php" class="cancel-btn">
                  <i class='bx bx-x'></i>
                  Cancel
                </a>
                <button type="submit" 
                        name="update" 
                        class="submit-btn">
                  <i class='bx bx-save'></i>
                  Update Employee
                </button>
              </div>
            </div>
          </form>
        <?php } else { ?>
          <form class="editForm" name="addForm" enctype="multipart/form-data" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" onsubmit="return validate()" method="POST">
            <div class="form-header-section">
              <div class="form-header">
                <h2><i class='bx bx-user-plus'></i> Add New Employee</h2>
                <p>Add a new employee to the evaluation system</p>
              </div>
            </div>
            
            <div class="form-body">
              <div class="info-box">
                <h4><i class='bx bx-info-circle'></i> Important Information</h4>
                <p>Fill in all required fields to add a new employee to the system.</p>
              </div>
              
              <div class="form-group full-width">
                <label for="empNumber" class="required">Employee Number</label>
                <input type="text" 
                       name="empNumber" 
                       id="empNumber" 
                       class="form-control-modern" 
                       value="E-" 
                       maxlength="6" 
                       pattern="E-[0-9]{4}" 
                       oninput="formatEmployeeNumber(this)" 
                       required 
                       placeholder="E-XXXX" />
                <div class="form-status">
                  <i class='bx bx-info-circle'></i>
                  <span>Format: E- followed by 4 digits (e.g., E-1001)</span>
                </div>
              </div>
              
              <div class="form-group full-width">
                <label for="empName" class="required">Full Name</label>
                <input type="text" 
                       name="empName" 
                       id="empName" 
                       class="form-control-modern" 
                       required 
                       placeholder="Enter employee's full name" />
                <div class="form-status">
                  <i class='bx bx-check-circle status-valid'></i>
                  <span>Enter letters only (A-Z, a-z, spaces)</span>
                </div>
              </div>
              
              <div class="form-group">
                <label for="category" class="required">Category</label>
                <div class="select-modern">
                  <select name="category" id="category" onchange="filter()" required>
                    <option value="">Select Category</option>
                    <?php 
                    // Reset pointer to beginning
                    $resultfind->data_seek(0);
                    if ($resultfind->num_rows > 0) { ?>
                      <?php while ($rowfind = $resultfind->fetch_assoc()) { ?>
                        <?php if ($rowfind['category_name'] != "Common") { ?>
                          <option value="<?php echo $rowfind['category_name'] ?>">
                            <?php echo $rowfind['category_name'] ?>
                          </option>
                        <?php } ?>
                      <?php } ?>
                    <?php } ?>
                  </select>
                </div>
              </div>
              
              <div class="form-group">
                <label class="required">Gender</label>
                <div class="gender-selection">
                  <div class="gender-option">
                    <input type="radio" 
                           name="gender" 
                           id="male" 
                           value="Male" 
                           checked />
                    <label for="male" class="gender-label">
                      <i class='bx bx-male'></i>
                      <span>Male</span>
                    </label>
                  </div>
                  
                  <div class="gender-option">
                    <input type="radio" 
                           name="gender" 
                           id="female" 
                           value="Female" />
                    <label for="female" class="gender-label">
                      <i class='bx bx-female'></i>
                      <span>Female</span>
                    </label>
                  </div>
                </div>
              </div>
              
              <div class="form-group full-width">
                <label for="year" class="required">Assignment Year</label>
                <div class="select-modern">
                  <select name="year" id="year" required>
                    <option value="">Select Year</option>
                    <?php 
                    $currentYear = date('Y');
                    for($y = $currentYear; $y <= $currentYear + 1; $y++) {
                      $selected = ($y == $currentYear) ? 'selected' : '';
                      echo "<option value='$y' $selected>$y</option>";
                    }
                    ?>
                  </select>
                </div>
                <div class="form-status">
                  <i class='bx bx-calendar'></i>
                  <span>Select the year for this store assignment</span>
                </div>
              </div>
              
              <div class="form-group full-width">
                <label for="currentStore" class="required">Store for Selected Year</label>
                <div class="select-modern">
                  <select name="currentStore" id="currentStore" required>
                    <option value="">Select Store</option>
                    <?php 
                    // Reset pointer to beginning
                    $resultst->data_seek(0);
                    if ($resultst->num_rows > 0) { ?>
                      <?php while ($rowst = $resultst->fetch_assoc()) { ?>
                        <option value="<?php echo $rowst['store_number'] ?>" 
                                data-related-to="<?php echo $rowst['category_name'] ?>">
                          Store <?php echo $rowst['store_number'] ?> (<?php echo $rowst['category_name'] ?>)
                        </option>
                      <?php } ?>
                    <?php } ?>
                  </select>
                </div>
                <div class="form-status">
                  <i class='bx bx-info-circle'></i>
                  <span>Only stores matching selected category will be shown</span>
                </div>
              </div>
              
              <div class="submit-section">
                <a href="Employees.php" class="cancel-btn">
                  <i class='bx bx-x'></i>
                  Cancel
                </a>
                <button type="submit" 
                        name="submit" 
                        class="submit-btn">
                  <i class='bx bx-user-plus'></i>
                  Add Employee
                </button>
              </div>
            </div>
          </form>
        <?php }
        if (isset($_POST['update'])) {

          $empNumber = $_POST['empNumber'];
          $empName = $_POST['empName'];
          $category = $_POST['category'];
          $gender = $_POST['gender'];
          $currentStore = $_POST['currentStore'];
          $year = $_POST['year'];

          // Start transaction
          $conn->begin_transaction();
          
          try {
            // Update employee table
            $sql = "UPDATE employee
            SET emp_name = '$empName', current_category = '$category', current_store = '$currentStore', gender = '$gender'
            WHERE emp_id = '$empNumber'";
            $result = $conn->query($sql);

            // Check if history exists for this year
            $check_history = "SELECT * FROM employee_store_history WHERE emp_id = '$empNumber' AND year = '$year'";
            $check_result = $conn->query($check_history);
            
            if ($check_result->num_rows > 0) {
              // Update existing history
              $sql_history = "UPDATE employee_store_history 
                              SET store = '$currentStore' 
                              WHERE emp_id = '$empNumber' AND year = '$year'";
            } else {
              // Insert new history
              $sql_history = "INSERT INTO employee_store_history(emp_id, store, year) 
                              VALUES ('$empNumber','$currentStore','$year')";
            }
            $result_history = $conn->query($sql_history);

            if ($result == TRUE && $result_history == TRUE) {
              $conn->commit();
              echo "<script>alert('Employee Updated Successfully!');
              window.location.href='Employees.php';
              </script>";
            } else {
              $conn->rollback();
              echo "Error:" . $sql . "<br>" . $conn->error;
            }
          } catch (Exception $e) {
            $conn->rollback();
            echo "Error: " . $e->getMessage();
          }
        } ?>
      </div>
    </div>

    <script>
      function filter() {
        var selectedOption = document.getElementById('category').value;
        var currentStore = document.getElementById('currentStore');
        var options = currentStore.options;
        
        // Show all options initially
        for (var i = 0; i < options.length; i++) {
          options[i].style.display = '';
        }
        
        // Hide non-matching options if a category is selected
        if (selectedOption) {
          for (var i = 0; i < options.length; i++) {
            var option = options[i];
            var relatedTo = option.getAttribute('data-related-to');
            
            if (relatedTo && relatedTo !== selectedOption) {
              option.style.display = 'none';
            }
            
            // If this option was previously selected but now doesn't match, deselect it
            if (option.selected && relatedTo && relatedTo !== selectedOption) {
              option.selected = false;
              // Select the first visible option
              for (var j = 0; j < options.length; j++) {
                if (options[j].style.display !== 'none' && options[j].value) {
                  options[j].selected = true;
                  break;
                }
              }
            }
          }
        }
      }

      // Initialize filter on page load
      document.addEventListener('DOMContentLoaded', function() {
        filter();
      });
    </script>
  </body>

  </html>

<?php } else {
  header("location: signin.php");
} ?>