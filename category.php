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

  if (isset($_POST['submit'])) {

    $cName = $_POST['cName'];
    $store1 = $_POST['store1'];
    $store2 = $_POST['store2'];
    $store3 = $_POST['store3'];
    $store4 = $_POST['store4'];
    $store5 = $_POST['store5'];
    $store6 = $_POST['store6'];

    if ($store2 == "") {
      $sql = "INSERT INTO category(category_name, store_1)
    VALUES ('$cName','$store1')";
      $sqlst1 = "SELECT * FROM store WHERE store_number = '$store1'";
      $resultst1 = $conn->query($sqlst1);
    } else if ($store3 == "") {
      $sql = "INSERT INTO category(category_name, store_1, store_2)
    VALUES ('$cName','$store1','$store2')";
      $sqlst1 = "SELECT * FROM store WHERE store_number = '$store1'";
      $resultst1 = $conn->query($sqlst1);
      $sqlst2 = "SELECT * FROM store WHERE store_number = '$store2'";
      $resultst2 = $conn->query($sqlst2);
    } else if ($store4 == "") {
      $sql = "INSERT INTO category(category_name, store_1, store_2, store_3)
    VALUES ('$cName','$store1','$store2','$store3')";
      $sqlst1 = "SELECT * FROM store WHERE store_number = '$store1'";
      $resultst1 = $conn->query($sqlst1);
      $sqlst2 = "SELECT * FROM store WHERE store_number = '$store2'";
      $resultst2 = $conn->query($sqlst2);
      $sqlst3 = "SELECT * FROM store WHERE store_number = '$store3'";
      $resultst3 = $conn->query($sqlst3);
    } else if ($store5 == "") {
      $sql = "INSERT INTO category(category_name, store_1, store_2, store_3, store_4)
    VALUES ('$cName','$store1','$store2','$store3','$store4')";
      $sqlst1 = "SELECT * FROM store WHERE store_number = '$store1'";
      $resultst1 = $conn->query($sqlst1);
      $sqlst2 = "SELECT * FROM store WHERE store_number = '$store2'";
      $resultst2 = $conn->query($sqlst2);
      $sqlst3 = "SELECT * FROM store WHERE store_number = '$store3'";
      $resultst3 = $conn->query($sqlst3);
      $sqlst4 = "SELECT * FROM store WHERE store_number = '$store4'";
      $resultst4 = $conn->query($sqlst4);
    } else if ($store6 == "") {
      $sql = "INSERT INTO category(category_name, store_1, store_2, store_3, store_4, store_5)
    VALUES ('$cName','$store1','$store2','$store3','$store4','$store5')";
      $sqlst1 = "SELECT * FROM store WHERE store_number = '$store1'";
      $resultst1 = $conn->query($sqlst1);
      $sqlst2 = "SELECT * FROM store WHERE store_number = '$store2'";
      $resultst2 = $conn->query($sqlst2);
      $sqlst3 = "SELECT * FROM store WHERE store_number = '$store3'";
      $resultst3 = $conn->query($sqlst3);
      $sqlst4 = "SELECT * FROM store WHERE store_number = '$store4'";
      $resultst4 = $conn->query($sqlst4);
      $sqlst5 = "SELECT * FROM store WHERE store_number = '$store5'";
      $resultst5 = $conn->query($sqlst5);
    } else {
      $sql = "INSERT INTO category(category_name, store_1, store_2, store_3, store_4, store_5, store_6)
    VALUES ('$cName','$store1','$store2','$store3','$store4','$store5','$store6')";
      $sqlst1 = "SELECT * FROM store WHERE store_number = '$store1'";
      $resultst1 = $conn->query($sqlst1);
      $sqlst2 = "SELECT * FROM store WHERE store_number = '$store2'";
      $resultst2 = $conn->query($sqlst2);
      $sqlst3 = "SELECT * FROM store WHERE store_number = '$store3'";
      $resultst3 = $conn->query($sqlst3);
      $sqlst4 = "SELECT * FROM store WHERE store_number = '$store4'";
      $resultst4 = $conn->query($sqlst4);
      $sqlst5 = "SELECT * FROM store WHERE store_number = '$store5'";
      $resultst5 = $conn->query($sqlst5);
      $sqlst6 = "SELECT * FROM store WHERE store_number = '$store6'";
      $resultst6 = $conn->query($sqlst6);
    }

    if (($resultst1 && $resultst1->num_rows == 0) || ($resultst2 && $resultst2->num_rows == 0) || ($resultst3 && $resultst3->num_rows == 0) || ($resultst4 && $resultst4->num_rows == 0) || ($resultst5 && $resultst5->num_rows == 0) || ($resultst6 && $resultst6->num_rows == 0)) {

      if ($store2 != "") {
        $sqls2 = "INSERT INTO store(store_number, category_name) 
        VALUES ('$store2','$cName')";
        $results2 = $conn->query($sqls2);
      }
      if ($store3 != "") {
        $sqls3 = "INSERT INTO store(store_number, category_name) 
        VALUES ('$store3','$cName')";
        $results3 = $conn->query($sqls3);
      }
      if ($store4 != "") {
        $sqls4 = "INSERT INTO store(store_number, category_name) 
        VALUES ('$store4','$cName')";
        $results4 = $conn->query($sqls4);
      }
      if ($store5 != "") {
        $sqls5 = "INSERT INTO store(store_number, category_name) 
        VALUES ('$store5','$cName')";
        $results5 = $conn->query($sqls5);
      }
      if ($store6 != "") {
        $sqls6 = "INSERT INTO store(store_number, category_name) 
        VALUES ('$store6','$cName')";
        $results6 = $conn->query($sqls6);
      }
      $sqls1 = "INSERT INTO store(store_number, category_name) 
      VALUES ('$store1','$cName')";
      $results1 = $conn->query($sqls1);

      $result = $conn->query($sql);
    } else {
      echo "<script>alert('Some Store(s) is Alreddy Exist!');
      history.back();
      </script>";
    }

    if ($result == TRUE) {
      echo "<script>alert('Category Added Successfully!');
      window.location.href='Categories.php';
      </script>";
    } else {
      echo "Error:" . $sql . "<br>" . $conn->error;
    }
  }
?>

  <!DOCTYPE html>
  <html>

  <head>
    <meta charset="utf-8" />
    <?php if (isset($_GET["id"])) { ?>
      <title>Update Category</title>
    <?php } else { ?>
      <title>Add Category</title>
    <?php } ?>
    <link rel="icon" type="image/x-icon" href="./img/jb.png">
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />

    <link rel="stylesheet" href="fonts/material-design-iconic-font/css/material-design-iconic-font.css" />

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta1/dist/css/bootstrap.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta1/dist/js/bootstrap.bundle.min.js" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.2.1/jquery.min.js" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/boxicons@latest/css/boxicons.min.css" />
    <!-- Add Font Awesome for modern icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <!-- Add modern font -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="./style.css" />
    <script src="./script.js"></script>
    <link rel="stylesheet" href="css/style.css" />

    <style>
    /* Modern Form Styles - Keep existing sidebar layout */
    * {
        font-family: 'Inter', sans-serif;
        box-sizing: border-box;
    }
    
    body {
        margin: 0;
        padding: 0;
        overflow: hidden; /* Prevent scrolling */
        height: 100vh;
        background: #f5f7fa;
    }
    
    .wrapper {
        margin-top: 10vh;
        padding: 0 20px;
        width: 100%;
        height: 90vh; /* Fit within viewport */
        display: flex;
        align-items: center; /* Vertical center */
        justify-content: center; /* Horizontal center */
        overflow-y: auto; /* Only scroll inside wrapper if needed */
        position: relative;
            top: -10px;
    }
    
    .modern-form-container {
        width: 100%;
        max-width: 1000px;
        background: white;
        height:95%;;
        border-radius: 16px;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.08);
        overflow: hidden;
        border: 1px solid #e5e7eb;
        margin: auto; /* Center the container */
    }
    
    .form-header-section {
        background: linear-gradient(135deg, #598a38ff 0%, #764ba2 100%);
        padding: 0.5rem;
        color: white;
        text-align: center;
    }
    
    .form-header-section h2 {
        font-size: 1.5rem;
        font-weight: 700;
        margin-bottom: 0.5rem;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
    }
    
    .form-header-section h2 i {
        font-size: 1.3rem;
    }
    
    .form-header-section p {
        font-size: 0.9rem;
        opacity: 0.9;
        max-width: 600px;
        margin: 0 auto;
        line-height: 1.4;
        color: #000;
    }
    
    .form-body {
        padding: 1.5rem;
    }
    
    .section-title {
        font-size: 1rem;
        font-weight: 600;
        color: #333;
        margin-bottom: 1rem;
        padding-bottom: 0.5rem;
        border-bottom: 2px solid #f3f4f6;
        position: relative;
    }
    
    .section-title::after {
        content: '';
        position: absolute;
        bottom: -2px;
        left: 0;
        width: 40px;
        height: 2px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }
    
    .form-group {
        margin-bottom: 1.25rem;
    }
    
    .form-label {
        display: block;
        font-size: 0.9rem;
        font-weight: 500;
        color: #4b5563;
        margin-bottom: 0.5rem;
    }
    
    .form-label .required {
        color: #ef4444;
    }
    
    .form-input {
        width: 100%;
        padding: 0.75rem 1rem;
        font-size: 0.9rem;
        border: 2px solid #e5e7eb;
        border-radius: 8px;
        transition: all 0.3s ease;
        background: #f9fafb;
    }
    
    .form-input:focus {
        outline: none;
        border-color: #667eea;
        background: white;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    }
    
    .form-input::placeholder {
        color: #9ca3af;
    }
    
    .stores-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 1rem;
        margin-bottom: 0.75rem;
    }
    
    .store-input-container {
        position: relative;
    }
    
    .store-input-container label {
        display: block;
        font-size: 0.8rem;
        font-weight: 500;
        color: #6b7280;
        margin-bottom: 0.375rem;
        padding-left: 4px;
    }
    
    .store-input-container label span {
        color: #ef4444;
    }
    
    .store-input-container input {
        width: 100%;
        padding: 0.75rem 1rem 0.75rem 2.5rem;
        font-size: 0.9rem;
        border: 2px solid #e5e7eb;
        border-radius: 8px;
        transition: all 0.3s ease;
        background: #f9fafb;
    }
    
    .store-input-container input:focus {
        outline: none;
        border-color: #667eea;
        background: white;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    }
    
    .store-input-container i {
        position: absolute;
        left: 1rem;
        top: 2.25rem;
        color: #9ca3af;
        font-size: 0.9rem;
    }
    
    .form-actions {
        display: flex;
        justify-content: center;
        gap: 1rem;
        margin-top: 2rem;
        padding-top: 1.5rem;
        border-top: 1px solid #e5e7eb;
    }
    
    .btn-primary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border: none;
        padding: 0.75rem 2rem;
        font-size: 0.9rem;
        font-weight: 600;
        border-radius: 8px;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        gap: 8px;
        box-shadow: 0 4px 6px rgba(102, 126, 234, 0.2);
    }
    
    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 12px rgba(102, 126, 234, 0.25);
    }
    
    .btn-secondary {
        background: #6b7280;
        color: white;
        border: none;
        padding: 0.75rem 1.75rem;
        font-size: 0.9rem;
        font-weight: 600;
        border-radius: 8px;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .btn-secondary:hover {
        background: #4b5563;
        transform: translateY(-2px);
    }
    
    .info-text {
        font-size: 0.8rem;
        color: #6b7280;
        margin-top: 0.375rem;
        font-style: italic;
        line-height: 1.3;
    }
    
    .required-field-note {
        font-size: 0.75rem;
        color: #6b7280;
        margin-top: 1.25rem;
        text-align: right;
    }
    
    .required-field-note span {
        color: #ef4444;
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
    
    /* System Status Alert */
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
    
    /* Responsive adjustments */
    @media (max-width: 1200px) {
        .modern-form-container {
            max-width: 95%;
        }
    }
    
    @media (max-width: 1024px) {
        .stores-grid {
            grid-template-columns: repeat(2, 1fr);
            gap: 0.875rem;
        }
        
        .form-header-section {
            padding: 1.25rem;
        }
        
        .form-body {
            padding: 1.25rem;
        }
    }
    
    @media (max-width: 768px) {
        .wrapper {
            margin-top: 8vh;
            padding: 0 15px;
            height: 92vh;
            display: block; /* Remove flex on mobile */
        }
        
        .modern-form-container {
            max-width: 100%;
            border-radius: 12px;
            margin: auto; /* Still centered */
        }
        
        .stores-grid {
            grid-template-columns: 1fr;
            gap: 0.75rem;
        }
        
        .form-header-section {
            padding: 1rem;
        }
        
        .form-header-section h2 {
            font-size: 1.25rem;
        }
        
        .form-header-section p {
            font-size: 0.85rem;
        }
        
        .form-body {
            padding: 1rem;
        }
        
        .form-actions {
            flex-direction: column;
            gap: 0.75rem;
            margin-top: 1.5rem;
            padding-top: 1.25rem;
        }
        
        .btn-primary, .btn-secondary {
            width: 100%;
            justify-content: center;
            padding: 0.75rem 1.5rem;
        }
        
        .system-alert {
            top: 60px;
            width: 95%;
        }
    }
    
    @media (max-width: 480px) {
        .wrapper {
            margin-top: 7vh;
            padding: 0 12px;
            height: 93vh;
            
            display: block;
        }
        
        .modern-form-container {
            margin: auto; /* Keep it centered */
        }
        
        .form-header-section {
            padding: 0.875rem;
        }
        
        .form-body {
            padding: 0.875rem;
        }
        
        .section-title {
            font-size: 0.95rem;
            margin-bottom: 0.875rem;
        }
        
        .form-input, .store-input-container input {
            padding: 0.625rem 0.875rem;
            font-size: 0.85rem;
        }
        
        .store-input-container input {
            padding-left: 2.25rem;
        }
        
        .store-input-container i {
            top: 2rem;
            left: 0.875rem;
            font-size: 0.85rem;
        }
        
        .btn-primary, .btn-secondary {
            padding: 0.625rem 1.25rem;
            font-size: 0.85rem;
        }
    }
    
    /* Adjust for sidebar */
    .content.shifted .wrapper {
        margin-left: 0;
        transition: margin-left 0.3s ease;
    }
    
    /* Make sure the form container itself doesn't scroll */
    .modern-form-container {
        max-height: 100%;
    }
    
    /* Ensure proper spacing on very short screens */
    @media (max-height: 700px) {
        .wrapper {
            margin-top: 8vh;
            height: 92vh;
        }
        
        .form-header-section {
            padding: 1rem;
        }
        
        .form-body {
            padding: 1rem;
        }
        
        .form-group {
            margin-bottom: 1rem;
        }
        
        .form-actions {
            margin-top: 1.5rem;
            padding-top: 1rem;
        }
    }
    
    @media (max-height: 600px) {
        .wrapper {
            margin-top: 7vh;
            height: 93vh;
        }
        
        .modern-form-container {
            max-height: 90vh; /* Limit height on very short screens */
        }
        
        .form-header-section {
            padding: 0.75rem;
        }
        
        .form-header-section h2 {
            font-size: 1.25rem;
            margin-bottom: 0.25rem;
        }
        
        .form-body {
            padding: 0.75rem;
        }
        
        .section-title {
            margin-bottom: 0.75rem;
            padding-bottom: 0.375rem;
        }
        
        .stores-grid {
            gap: 0.625rem;
            margin-bottom: 0.5rem;
        }
        
        .store-input-container label {
            margin-bottom: 0.25rem;
        }
        
        .store-input-container i {
            top: 2rem;
        }
    }
    
    /* Center the form vertically and horizontally */
    .form-center-container {
        display: flex;
        align-items: center;
        justify-content: center;
        height: 100%;
        width: 100%;
    }
</style>
    <script type="text/javascript">
      
      function validate() {
        var letters = /^[A-Z./:;!`"|<>_-?+@#%^&*~, a-z0-9]+$/;
        if (!document.addForm.cName.value.match(letters)) {
          alert("Containing Invalid Characters in Category Name");
          return false;
        }
        if (!document.addForm.store1.value.match(letters)) {
          alert("Containing Invalid Characters in Store#1");
          return false;
        }
        if (!document.addForm.store2.value.match(letters)) {
          alert("Containing Invalid Characters in Store#2");
          return false;
        }
        if (!document.addForm.store3.value.match(letters)) {
          alert("Containing Invalid Characters in Store#3");
          return false;
        }
        if (!document.addForm.store4.value.match(letters)) {
          alert("Containing Invalid Characters in Store#4");
          return false;
        }
        if (!document.addForm.store5.value.match(letters)) {
          alert("Containing Invalid Characters in Store#5");
          return false;
        }
        if (!document.addForm.store6.value.match(letters)) {
          alert("Containing Invalid Characters in Store#6");
          return false;
        }

        return (true);
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
            <a href="./Categories.php" class="nav_link <?php echo basename($_SERVER['PHP_SELF']) == 'category.php' ? 'active' : ''; ?>"> 
              <i class='bx bx-category nav_icon'></i> 
              <span class="nav_name">Categories</span> 
            </a>
            <a href="./AttributeCategories.php" class="nav_link"> <i class='bx bx-spreadsheet nav_icon'></i> <span class="nav_name">Attributes</span> </a>
            <a href="./ScoringMethods.php" class="nav_link"> <i class='bx bx-tachometer nav_icon'></i> <span class="nav_name">Marking Schemes</span> </a>
            <a href="./Evaluators.php" class="nav_link"> <i class='bx bxs-user-detail nav_icon'></i> <span class="nav_name">Evaluators</span> </a>
            <a href="./Employees.php" class="nav_link"> <i class='bx bx-user nav_icon'></i><span class="nav_name">Evaluatees</span> </a>
            
            <?php if ($is_frozen == '1'): ?>
              <!-- Disabled nav links when system is frozen -->
              <a href="javascript:void(0)" class="nav_link disabled" onclick="alert('Evaluation system is currently frozen. Please contact administrator.')">
                <i class='bx bx-bar-chart-alt-2 nav_icon'></i> 
                <span class="nav_name">Evaluate by Individual <small class="text-danger">(Frozen)</small></span>
              </a>
              <a href="javascript:void(0)" class="nav_link disabled" onclick="alert('Evaluation system is currently frozen. Please contact administrator.')">
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
      <?php if (isset($_GET["id"])) {

        $id = $_GET["id"];

        $sql = "SELECT * FROM category WHERE category_id ='$id'";
        $result = $conn->query($sql);
        $row = $result->fetch_assoc();

      ?>
        <div class="modern-form-container">
          <div class="form-header-section">
            <h2><i class="fas fa-edit"></i> Update Category</h2>
            <p>Modify category details and store assignments</p>
          </div>
          
          <form name="addForm" enctype="multipart/form-data" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" onsubmit="return validate()" method="POST" class="form-body">
            <input type="hidden" name="pstCName" value="<?php echo htmlspecialchars($row['category_name']); ?>">
            
            <div class="section-title">Category Information</div>
            
            <div class="form-group">
              <label class="form-label">Category Name <span class="required">*</span></label>
              <input type="text" name="cName" class="form-input" placeholder="Enter category name" value="<?php echo htmlspecialchars($row['category_name']); ?>" required />
            </div>
            
            <div class="section-title">Stores / Warehouses</div>
            <p class="info-text">Enter store numbers or names. Store #1 is required, others are optional.</p>
            
            <div class="stores-grid">
              <div class="store-input-container">
                <label>Store 1 <span></span></label>
                <i class="fas fa-store"></i>
                <input type="text" name="store1" placeholder="e.g., 04" value="<?php echo htmlspecialchars($row['store_1']); ?>" required />
              </div>
              
              <div class="store-input-container">
                <label>Store 2</label>
                <i class="fas fa-store"></i>
                <input type="text" name="store2" placeholder="e.g., 05" value="<?php echo htmlspecialchars($row['store_2']); ?>" />
              </div>
              
              <div class="store-input-container">
                <label>Store 3</label>
                <i class="fas fa-store"></i>
                <input type="text" name="store3" placeholder="e.g., 06" value="<?php echo htmlspecialchars($row['store_3']); ?>" />
              </div>
              
              <div class="store-input-container">
                <label>Store 4</label>
                <i class="fas fa-store"></i>
                <input type="text" name="store4" placeholder="e.g., 45" value="<?php echo htmlspecialchars($row['store_4']); ?>" />
              </div>
              
              <div class="store-input-container">
                <label>Store 5</label>
                <i class="fas fa-store"></i>
                <input type="text" name="store5" placeholder="e.g., Store 5" value="<?php echo htmlspecialchars($row['store_5']); ?>" />
              </div>
              
              <div class="store-input-container">
                <label>Store 6</label>
                <i class="fas fa-store"></i>
                <input type="text" name="store6" placeholder="e.g., Store 6" value="<?php echo htmlspecialchars($row['store_6']); ?>" />
              </div>
            </div>
            
            <div class="form-actions">
              <button type="submit" name="update" class="btn-primary">
                <i class="fas fa-save"></i> Update Category
              </button>
              <a href="Categories.php" class="btn-secondary">
                <i class="fas fa-times"></i> Cancel
              </a>
            </div>
            
            <div class="required-field-note">
              <span>*</span> indicates a required field
            </div>
          </form>
        </div>
      <?php } else { ?>
        <div class="modern-form-container">
          <div class="form-header-section">
            <h2><i class="fas fa-plus-circle"></i> Add New Category</h2>
            <p>Create a new category with associated stores</p>
          </div>
          
          <form name="addForm" enctype="multipart/form-data" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" onsubmit="return validate()" method="POST" class="form-body">
            <div class="section-title">Category Information</div>
            
            <div class="form-group">
              <label class="form-label">Category Name <span class="required">*</span></label>
              <input type="text" name="cName" class="form-input" placeholder="Enter category name" required />
            </div>
            
            <div class="section-title">Stores / Warehouses</div>
            <p class="info-text">Enter store numbers or names. Store #1 is required, others are optional.</p>
            
            <div class="stores-grid">
              <div class="store-input-container">
                <label>Store #1 <span>*</span></label>
                <i class="fas fa-store"></i>
                <input type="text" name="store1" placeholder="e.g., 04" required />
              </div>
              
              <div class="store-input-container">
                <label>Store #2</label>
                <i class="fas fa-store"></i>
                <input type="text" name="store2" placeholder="e.g., 05" />
              </div>
              
              <div class="store-input-container">
                <label>Store #3</label>
                <i class="fas fa-store"></i>
                <input type="text" name="store3" placeholder="e.g., 06" />
              </div>
              
              <div class="store-input-container">
                <label>Store #4</label>
                <i class="fas fa-store"></i>
                <input type="text" name="store4" placeholder="e.g., 45" />
              </div>
              
              <div class="store-input-container">
                <label>Store #5</label>
                <i class="fas fa-store"></i>
                <input type="text" name="store5" placeholder="e.g., Store 5" />
              </div>
              
              <div class="store-input-container">
                <label>Store #6</label>
                <i class="fas fa-store"></i>
                <input type="text" name="store6" placeholder="e.g., Store 6" />
              </div>
            </div>
            
            <div class="form-actions">
              <button type="submit" name="submit" class="btn-primary">
                <i class="fas fa-check"></i> Create Category
              </button>
              <a href="Categories.php" class="btn-secondary">
                <i class="fas fa-times"></i> Cancel
              </a>
            </div>
            
            <div class="required-field-note">
              <span>*</span> indicates a required field
            </div>
          </form>
        </div>
      <?php }
      if (isset($_POST['update'])) {

        $cName = $_POST['cName'];
        $pstCName = $_POST['pstCName'];
        $store1 = $_POST['store1'];
        $store2 = $_POST['store2'];
        $store3 = $_POST['store3'];
        $store4 = $_POST['store4'];
        $store5 = $_POST['store5'];
        $store6 = $_POST['store6'];

        $conn->query("DELETE FROM store WHERE category_name = '$pstCName'");

        if ($store2 == "") {
          $sql = "UPDATE category
          SET category_name = '$cName', store_1 = '$store1', store_2 = NULL, store_3 = NULL, store_4 = NULL, store_5 = NULL, store_6 = NULL
          WHERE category_name = '$pstCName'";
          $sqlst1 = "SELECT * FROM store WHERE store_number = '$store1'";
          $resultst1 = $conn->query($sqlst1);
        } else if ($store3 == "") {
          $sql = "UPDATE category
          SET category_name = '$cName', store_1 = '$store1', store_2 = '$store2', store_3 = NULL, store_4 = NULL, store_5 = NULL, store_6 = NULL
          WHERE category_name = '$pstCName'";
          $sqlst1 = "SELECT * FROM store WHERE store_number = '$store1'";
          $resultst1 = $conn->query($sqlst1);
          $sqlst2 = "SELECT * FROM store WHERE store_number = '$store2'";
          $resultst2 = $conn->query($sqlst2);
        } else if ($store4 == "") {
          $sql = "UPDATE category
          SET category_name = '$cName', store_1 = '$store1', store_2 = '$store2', store_3 = '$store3', store_4 = NULL, store_5 = NULL, store_6 = NULL
          WHERE category_name = '$pstCName'";
          $sqlst1 = "SELECT * FROM store WHERE store_number = '$store1'";
          $resultst1 = $conn->query($sqlst1);
          $sqlst2 = "SELECT * FROM store WHERE store_number = '$store2'";
          $resultst2 = $conn->query($sqlst2);
          $sqlst3 = "SELECT * FROM store WHERE store_number = '$store3'";
          $resultst3 = $conn->query($sqlst3);
        } else if ($store5 == "") {
          $sql = "UPDATE category
          SET category_name = '$cName', store_1 = '$store1', store_2 = '$store2', store_3 = '$store3', store_4 = '$store4', store_5 = NULL, store_6 = NULL
          WHERE category_name = '$pstCName'";
          $sqlst1 = "SELECT * FROM store WHERE store_number = '$store1'";
          $resultst1 = $conn->query($sqlst1);
          $sqlst2 = "SELECT * FROM store WHERE store_number = '$store2'";
          $resultst2 = $conn->query($sqlst2);
          $sqlst3 = "SELECT * FROM store WHERE store_number = '$store3'";
          $resultst3 = $conn->query($sqlst3);
          $sqlst4 = "SELECT * FROM store WHERE store_number = '$store4'";
          $resultst4 = $conn->query($sqlst4);
        } else if ($store6 == "") {
          $sql = "UPDATE category
          SET category_name = '$cName', store_1 = '$store1', store_2 = '$store2', store_3 = '$store3', store_4 = '$store4', store_5 = '$store5', store_6 = NULL
          WHERE category_name = '$pstCName'";
          $sqlst1 = "SELECT * FROM store WHERE store_number = '$store1'";
          $resultst1 = $conn->query($sqlst1);
          $sqlst2 = "SELECT * FROM store WHERE store_number = '$store2'";
          $resultst2 = $conn->query($sqlst2);
          $sqlst3 = "SELECT * FROM store WHERE store_number = '$store3'";
          $resultst3 = $conn->query($sqlst3);
          $sqlst4 = "SELECT * FROM store WHERE store_number = '$store4'";
          $resultst4 = $conn->query($sqlst4);
          $sqlst5 = "SELECT * FROM store WHERE store_number = '$store5'";
          $resultst5 = $conn->query($sqlst5);
        } else {
          $sql = "UPDATE category
          SET category_name = '$cName', store_1 = '$store1', store_2 = '$store2', store_3 = '$store3', store_4 = '$store4', store_5 = '$store5', store_6 = '$store6'
          WHERE category_name = '$pstCName'";
          $sqlst1 = "SELECT * FROM store WHERE store_number = '$store1'";
          $resultst1 = $conn->query($sqlst1);
          $sqlst2 = "SELECT * FROM store WHERE store_number = '$store2'";
          $resultst2 = $conn->query($sqlst2);
          $sqlst3 = "SELECT * FROM store WHERE store_number = '$store3'";
          $resultst3 = $conn->query($sqlst3);
          $sqlst4 = "SELECT * FROM store WHERE store_number = '$store4'";
          $resultst4 = $conn->query($sqlst4);
          $sqlst5 = "SELECT * FROM store WHERE store_number = '$store5'";
          $resultst5 = $conn->query($sqlst5);
          $sqlst6 = "SELECT * FROM store WHERE store_number = '$store6'";
          $resultst6 = $conn->query($sqlst6);
        }

        if (($resultst1 && $resultst1->num_rows > 0) || ($resultst2 && $resultst2->num_rows > 0) || ($resultst3 && $resultst3->num_rows > 0) || ($resultst4 && $resultst4->num_rows > 0) || ($resultst5 && $resultst5->num_rows > 0) || ($resultst6 && $resultst6->num_rows > 0)) {
          echo "<script>alert('Some Store(s) is Alreddy Exist!');
          history.back();
          </script>";
        } else {
          if ($store2 != "") {
            $sqls2 = "INSERT INTO store(store_number, category_name) 
            VALUES ('$store2','$cName')";
            $results2 = $conn->query($sqls2);
          }
          if ($store3 != "") {
            $sqls3 = "INSERT INTO store(store_number, category_name) 
            VALUES ('$store3','$cName')";
            $results3 = $conn->query($sqls3);
          }
          if ($store4 != "") {
            $sqls4 = "INSERT INTO store(store_number, category_name) 
            VALUES ('$store4','$cName')";
            $results4 = $conn->query($sqls4);
          }
          if ($store5 != "") {
            $sqls5 = "INSERT INTO store(store_number, category_name) 
            VALUES ('$store5','$cName')";
            $results5 = $conn->query($sqls5);
          }
          if ($store6 != "") {
            $sqls6 = "INSERT INTO store(store_number, category_name) 
            VALUES ('$store6','$cName')";
            $results6 = $conn->query($sqls6);
          }
          $sqls1 = "INSERT INTO store(store_number, category_name) 
          VALUES ('$store1','$cName')";
          $results1 = $conn->query($sqls1);

          $result = $conn->query($sql);
        }

        if ($result == TRUE) {
          echo "<script>alert('Category Updated Successfully!');
          window.location.href='Categories.php';
          </script>";
        } else {
          echo "Error:" . $sql . "<br>" . $conn->error;
        }
      } ?>
    </div>

  </body>

  </html>

<?php } else {
  header("location: signin.php");
} ?>