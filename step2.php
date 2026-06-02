<?php
include "config.php";
session_start();

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

  if (isset($_GET['id'])) {

    $id = $_GET['id'];

    $sql = "SELECT * FROM employee WHERE emp_id ='$id'";
    $resultfind = $conn->query($sql);

    if ($resultfind->num_rows == 1) {
      $rowfind = $resultfind->fetch_assoc();
      $category1 = $rowfind['current_category'];
      if ($category1 == "Flavoring") {
        $sqlatt = "SELECT * FROM attribute WHERE category ='Packing' OR category ='Common'";
        $resultatt = $conn->query($sqlatt);
      } else {
        $sqlatt = "SELECT * FROM attribute WHERE category ='$category1' OR category ='Common'";
        $resultatt = $conn->query($sqlatt);
      }
    } else {
      header("location: step2.php?id=$id");
    }
  }

  if (isset($_POST['submit'])) {

    $attribute = $_POST['attribute'];
    $id = $_POST['empNumber'];

    header("location: evaluation.php?id=$id&att=$attribute");
  }
?>

  <!DOCTYPE html>
  <html>

  <head>
    <meta charset="utf-8" />
    <title>Evaluation</title>
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

      * {
        font-family: 'Inter', sans-serif;
        box-sizing: border-box;
      }

      body {
        background-color: #f5f7fb;
        min-height: 100vh;
        overflow-x: hidden;
      }

      .wrapper {
        margin-top: 10vh;
        padding: 1rem;
        display: flex;
        justify-content: center;
        align-items: flex-start;
        min-height: calc(100vh - 10vh);
        width: 100%;
      }

      /* Wide Form Container */
      .wide-form-container {
        width: 100%;
        max-width: 1100px;
        background: white;
        border-radius: var(--radius-xl);
        box-shadow: var(--shadow-lg);
        overflow: hidden;
        transition: var(--transition);
        position: relative;
        display: flex;
        flex-direction: column;
        min-height: 500px;
      }

      .wide-form-container:hover {
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
      }

      /* Header Section */
      .wide-form-header {
        background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
        color: white;
        padding: 1.5rem 2rem;
        position: relative;
        overflow: hidden;
        flex-shrink: 0;
      }

      .wide-form-header::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: linear-gradient(45deg, rgba(255, 255, 255, 0.1) 0%, transparent 100%);
      }

      .header-content {
        position: relative;
        z-index: 1;
        display: flex;
        justify-content: space-between;
        align-items: center;
      }

      .header-title {
        display: flex;
        align-items: center;
        gap: 0.75rem;
      }

      .header-title h2 {
        font-size: 1.5rem;
        font-weight: 700;
        margin: 0;
      }

      .header-title p {
        opacity: 0.9;
        font-size: 0.9rem;
        margin: 0;
        margin-top: 0.25rem;
      }

      /* Back Button */
      .back-btn {
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
      }

      .back-btn:hover {
        background: rgba(255, 255, 255, 0.3);
        transform: scale(1.1);
      }

      /* Form Body - No Scroll */
      .wide-form-body {
        padding: 1.5rem 2rem;
        flex: 1;
        display: flex;
        flex-direction: column;
        gap: 1rem;
        overflow: visible;
      }

      /* Compact Employee Info */
      .compact-employee-info {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 1rem;
        background: #f8f9fa;
        padding: 1rem;
        border-radius: var(--radius-lg);
        border-left: 4px solid var(--primary-color);
        margin-bottom: 0.5rem;
      }

      .info-item {
        display: flex;
        flex-direction: column;
        gap: 0.25rem;
      }

      .info-label {
        font-size: 0.75rem;
        font-weight: 600;
        color: var(--gray-color);
        text-transform: uppercase;
        letter-spacing: 0.5px;
      }

      .info-value {
        font-size: 0.95rem;
        font-weight: 600;
        color: var(--dark-color);
      }

      /* Compact Form Grid */
      .compact-form-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 1rem;
        margin-bottom: 0.5rem;
      }

      /* Compact Form Groups */
      .compact-form-group {
        margin-bottom: 0;
      }

      .compact-form-group label {
        display: block;
        margin-bottom: 0.375rem;
        font-weight: 600;
        color: var(--dark-color);
        font-size: 0.8rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
      }

      .compact-input {
        width: 100%;
        padding: 0.625rem 0.875rem;
        border: 2px solid var(--border-color);
        border-radius: var(--radius-md);
        font-size: 0.9rem;
        transition: var(--transition);
        background: white;
        color: var(--dark-color);
        height: 42px;
      }

      .compact-input:focus {
        outline: none;
        border-color: var(--primary-color);
        box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.1);
      }

      .compact-input:read-only {
        background-color: #f9fafb;
        color: var(--gray-color);
        cursor: not-allowed;
      }

      /* Compact Select */
      .compact-select {
        position: relative;
      }

      .compact-select select {
        appearance: none;
        width: 100%;
        padding: 0.625rem 0.875rem;
        border: 2px solid var(--border-color);
        border-radius: var(--radius-md);
        font-size: 0.9rem;
        background: white;
        color: var(--dark-color);
        cursor: pointer;
        transition: var(--transition);
        height: 42px;
      }

      .compact-select select:focus {
        outline: none;
        border-color: var(--primary-color);
        box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.1);
      }

      .compact-select::after {
        content: '\f078';
        font-family: 'Font Awesome 6 Free';
        font-weight: 900;
        position: absolute;
        right: 0.875rem;
        top: 50%;
        transform: translateY(-50%);
        color: var(--gray-color);
        pointer-events: none;
        font-size: 0.9rem;
      }

      /* Attribute Select (Full Width) */
      .attribute-select-full {
        grid-column: 1 / -1;
        margin-top: 0.5rem;
        height: 30px;
      }

      .attribute-select-full label {
        font-size: 0.8rem;
        margin-bottom: 0.5rem;
        display: block;
      }

      .attribute-select-full select {
        width: 100%;
        height: 50px;
        padding: 0.75rem;
        border: 2px solid var(--border-color);
        border-radius: var(--radius-md);
        font-size: 0.9rem;
        background: white;
        color: var(--dark-color);
        cursor: pointer;
        transition: var(--transition);
      }

      .attribute-select-full select:focus {
        outline: none;
        border-color: var(--primary-color);
        box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.1);
      }

      .attribute-select-full option {
        padding: 0.5rem 0.75rem;
        border-bottom: 1px solid var(--border-color);
        cursor: pointer;
        transition: var(--transition);
      }

      .attribute-select-full option:hover {
        background-color: var(--primary-light);
      }

      .attribute-select-full option[disabled] {
        background-color: #f3f4f6;
        color: var(--gray-color);
        font-weight: 600;
        font-size: 0.8rem;
        padding: 0.75rem;
        text-align: center;
        cursor: default;
      }

      .attribute-select-full option:checked {
        background-color: var(--primary-color);
        color: white;
      }

      /* Action Buttons */
      .action-buttons {
        display: flex;
        justify-content: flex-end;
        gap: 1rem;
        margin-top: 1.5rem;
        padding-top: 1.5rem;
        border-top: 1px solid var(--border-color);
      }

      .btn-primary-compact {
        background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
        color: white;
        border: none;
        padding: 0.625rem 1.5rem;
        border-radius: var(--radius-md);
        font-size: 0.9rem;
        font-weight: 600;
        cursor: pointer;
        transition: var(--transition);
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        box-shadow: 0 2px 4px rgba(67, 97, 238, 0.2);
        min-width: 120px;
        justify-content: center;
      }

      .btn-primary-compact:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(67, 97, 238, 0.3);
      }

      .btn-secondary-compact {
        background: white;
        color: var(--gray-color);
        border: 2px solid var(--border-color);
        padding: 0.625rem 1.5rem;
        border-radius: var(--radius-md);
        font-size: 0.9rem;
        font-weight: 600;
        cursor: pointer;
        transition: var(--transition);
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        text-decoration: none;
        min-width: 120px;
        justify-content: center;
      }

      .btn-secondary-compact:hover {
        background: #f8f9fa;
        border-color: var(--primary-color);
        color: var(--primary-color);
        transform: translateY(-2px);
      }

      /* Role-based colors */
      .wide-form-container.Admin .wide-form-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      }

      .wide-form-container.Evaluator .wide-form-header {
        background: linear-gradient(135deg, #4CAF50 0%, #2E7D32 100%);
      }

      .wide-form-container.Guest .wide-form-header {
        background: linear-gradient(135deg, #757575 0%, #424242 100%);
      }

      .wide-form-container.Admin .compact-employee-info {
        border-left-color: #667eea;
      }

      .wide-form-container.Evaluator .compact-employee-info {
        border-left-color: #4CAF50;
      }

      .wide-form-container.Guest .compact-employee-info {
        border-left-color: #757575;
      }

      /* Responsive Design */
      @media (max-width: 1200px) {
        .wide-form-container {
          max-width: 95%;
        }
      }

      @media (max-width: 992px) {
        .compact-form-grid {
          grid-template-columns: 1fr;
          gap: 0.75rem;
        }
        
        .compact-employee-info {
          grid-template-columns: repeat(2, 1fr);
          gap: 0.75rem;
        }
      }

      @media (max-width: 768px) {
        .wrapper {
          padding: 0.5rem;
          margin-top: 8vh;
        }
        
        .wide-form-body {
          padding: 1rem;
        }
        
        .wide-form-header {
          padding: 1rem 1.5rem;
        }
        
        .header-content {
          flex-direction: column;
          align-items: flex-start;
          gap: 1rem;
        }
        
        .compact-employee-info {
          grid-template-columns: 1fr;
          gap: 0.5rem;
        }
        
        .action-buttons {
          flex-direction: column;
          gap: 0.75rem;
        }
        
        .btn-primary-compact,
        .btn-secondary-compact {
          width: 100%;
          min-width: unset;
        }
      }

      @media (max-width: 576px) {
        .header-title h2 {
          font-size: 1.3rem;
        }
        
        .compact-input,
        .compact-select select {
          height: 40px;
          font-size: 0.85rem;
        }
        
        .attribute-select-full select {
          height: 140px;
        }
      }

      /* Animation */
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

      .wide-form-container {
        animation: fadeIn 0.5s ease-out;
      }
    </style>
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
    <div class="l-navbar <?php if ($_SESSION['isAdmin']) {
                            echo "Admin";
                          } else if ($_SESSION["isEvaluator"]) {
                            echo "Evaluator";
                          } else {
                            echo "Guest";
                          } ?>" id="nav-bar">
      <nav class="nav">
        <div> <a href="./index.php" class="nav_logo" style="color: #ffffff; font-weight: bold;"> JB <span class="nav_logo-name" style="font-weight: normal;">Employee Evaluation</span> </a>
          <div class="nav_list">
            <a href="./Categories.php" class="nav_link"> <i class='bx bx-category nav_icon'></i> <span class="nav_name">Categories</span> </a>
            <a href="./AttributeCategories.php" class="nav_link"> <i class='bx bx-spreadsheet nav_icon'></i> <span class="nav_name">Attributes</span> </a>
            <a href="./ScoringMethods.php" class="nav_link"> <i class='bx bx-tachometer nav_icon'></i> <span class="nav_name">Marking Schemes</span> </a>
            <a href="./Evaluators.php" class="nav_link"> <i class='bx bxs-user-detail nav_icon'></i> <span class="nav_name">Evaluators</span> </a>
            <a href="./Employees.php" class="nav_link"> <i class='bx bx-user nav_icon'></i><span class="nav_name">Evaluatees</span> </a>
            <a href="./namely_evaluation.php" class="nav_link active"> <i class='bx bx-bar-chart-alt-2 nav_icon'></i> <span class="nav_name">Evaluate by Individual</span> </a>
            <a href="./Warehouses.php" class="nav_link"> <i class='bx bx-grid-alt nav_icon'></i> <span class="nav_name">Evaluate by Warehouse</span> </a>
            <a href="./periodRatings.php" class="nav_link"> <i class='bx bxs-star-half'></i> <span class="nav_name">Results & Grading</span> </a>
          </div>
        </div>
        <form enctype="multipart/form-data" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST">
          <button type="submit" name="signout" class="nav_link <?php if ($_SESSION['isAdmin']) {
                                                                  echo "Admin";
                                                                } else if ($_SESSION["isEvaluator"]) {
                                                                  echo "Evaluator";
                                                                } else {
                                                                  echo "Guest";
                                                                } ?>" style="background-color: #666; border: none;"> <i class='bx bx-log-out nav_icon'></i> <span class="nav_name">SignOut</span> </button>
        </form>
      </nav>
    </div>

    <div class="wrapper">
      <div class="wide-form-container <?php if ($_SESSION['isAdmin']) {
                                        echo "Admin";
                                      } else if ($_SESSION["isEvaluator"]) {
                                        echo "Evaluator";
                                      } else {
                                        echo "Guest";
                                      } ?>">
        
        <!-- Header with Back Button -->
        <div class="wide-form-header">
          <div class="header-content">
            <div class="header-title">
              <div>
                <h2><i class='bx bx-clipboard'></i> Employee Evaluation</h2>
                <p>Select attribute to evaluate employee performance</p>
              </div>
            </div>
            <a href="namely_evaluation.php" class="back-btn">
              <i class='bx bx-arrow-back'></i>
            </a>
          </div>
        </div>

        <!-- Form Body - No Scroll -->
        <form name="addForm" enctype="multipart/form-data" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST" class="wide-form-body">
          
          <!-- Hidden Employee Number -->
          <input type="hidden" name="empNumber" value="<?php echo $rowfind['emp_id'] ?>">
          
          <!-- Compact Employee Info -->
          <div class="compact-employee-info">
            <div class="info-item">
              <span class="info-label">Employee ID</span>
              <span class="info-value"><?php echo $rowfind['emp_id'] ?></span>
            </div>
            <div class="info-item">
              <span class="info-label">Employee Name</span>
              <span class="info-value"><?php echo $rowfind['emp_name'] ?></span>
            </div>
            <div class="info-item">
              <span class="info-label">Store Number</span>
              <span class="info-value">#<?php echo $rowfind['current_store'] ?></span>
            </div>
            <div class="info-item">
              <span class="info-label">Category</span>
              <span class="info-value"><?php echo $rowfind['current_category'] ?></span>
            </div>
          </div>

          <!-- Read-only Input Fields (Compact) -->
          <div class="compact-form-grid">
            <div class="compact-form-group">
              <label>Employee Number</label>
              <input type="text" 
                     class="compact-input" 
                     value="<?php echo $rowfind['emp_id'] ?>" 
                     readonly />
            </div>
            
            <div class="compact-form-group">
              <label>Store Number</label>
              <input type="text" 
                     class="compact-input" 
                     value="<?php echo $rowfind['current_store'] ?>" 
                     readonly />
            </div>
            
            <div class="compact-form-group">
              <label>Employee Name</label>
              <input type="text" 
                     class="compact-input" 
                     value="<?php echo $rowfind['emp_name'] ?>" 
                     readonly />
            </div>
            
            <div class="compact-form-group">
              <label>Category</label>
              <div class="compact-select">
                <select class="compact-input" disabled>
                  <?php
                  $sqlcat = "SELECT * FROM category";
                  $resultcat = $conn->query($sqlcat);
                  while ($rowcat = $resultcat->fetch_assoc()) {
                    if ($rowfind['current_category'] == $rowcat['category_name']) {
                  ?>
                    <option value="<?php echo $rowcat['category_name'] ?>" selected>
                      <?php echo $rowcat['category_name'] ?>
                    </option>
                  <?php }
                  } ?>
                </select>
              </div>
            </div>
          </div>

          <!-- Attribute Selection (Full Width) -->
          <div class="attribute-select-full">
            <label>Select Attribute for Evaluation <span style="color: var(--danger-color);">*</span></label>
            <select name="attribute" required>
              <option value="">-- Select an attribute --</option>
              <?php 
              if ($resultatt->num_rows > 0) {
                $i = 0;
                while ($rowatt = $resultatt->fetch_assoc()) {
                  $i = $i + 1;
                  // Section headers
                  if ($i == 1) {
                    echo '<option disabled>────────── JOB CAPABILITIES ──────────</option>';
                  } else if ($i == 11) {
                    echo '<option disabled>────────── PERSONAL SKILLS ──────────</option>';
                  } else if ($i == 16) {
                    echo '<option disabled>────────── PERSONAL VALUES ──────────</option>';
                  }
                  
                  // Check user permissions
                  $showAttribute = false;
                  if ($rowatt['PLE'] && $_SESSION["PLE"]) $showAttribute = true;
                  if ($rowatt['OE'] && $_SESSION["OE"]) $showAttribute = true;
                  if ($rowatt['QM'] && $_SESSION["QM"]) $showAttribute = true;
                  if ($rowatt['PE'] && $_SESSION["PE"]) $showAttribute = true;
                  
                  if ($showAttribute) {
              ?>
                <option value="<?php echo $rowatt['attribute_id'] ?>">
                  <?php echo $i . ". " . $rowatt['attribute_name'] ?>
                </option>
              <?php 
                  }
                }
              } 
              ?>
            </select>
          </div>

          <!-- Action Buttons -->
          <div class="action-buttons">
            <a href="namely_evaluation.php" class="btn-secondary-compact">
              <i class='bx bx-x'></i>
              Cancel
            </a>
            <button type="submit" name="submit" class="btn-primary-compact">
              <i class='bx bx-arrow-right'></i>
              Next
            </button>
          </div>
        </form>
      </div>
    </div>
  </body>

  </html>

<?php } else {
  header("location: signin.php");
} ?>