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

  if (isset($_GET['st'])) {

    $store = $_GET['st'];

    $sql = "SELECT * FROM store WHERE store_number ='$store'";
    $resultfind = $conn->query($sql);

    if ($resultfind->num_rows == 1) {
      $rowfind = $resultfind->fetch_assoc();
      $category1 = $rowfind['category_name'];
      if ($category1 == "Flavoring") {
        $sqlatt = "SELECT * FROM attribute WHERE category ='Packing' OR category ='Common'";
        $resultatt = $conn->query($sqlatt);
      } else {
        $sqlatt = "SELECT * FROM attribute WHERE category ='$category1' OR category ='Common'";
        $resultatt = $conn->query($sqlatt);
      }
    } else {
      header("location: store_step2.php?st=$store");
    }
  }

  if (isset($_POST['submit'])) {

    $attribute = $_POST['attribute'];
    $store = $_POST['storeNumber'];

    // $sql = "SELECT * FROM attribute WHERE attribute_name ='$attribute'";
    // $result = $conn->query($sql);
    // $row = $result->fetch_assoc();
    // $att = $row['attribute_id'];

    // if ($result == TRUE) {
    header("location: Store_evaluation.php?st=$store&att=$attribute");
    // } else {
    //   echo "Error:" . $sql . "<br>" . $conn->error;
    // }
  }
?>

  <!DOCTYPE html>
  <html>

  <head>
    <meta charset="utf-8" />
    <title>Evaluation | JB Employee Evaluation</title>
    <link rel="icon" type="image/x-icon" href="./img/jb.png">
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />

    <link rel="stylesheet" href="fonts/material-design-iconic-font/css/material-design-iconic-font.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta1/dist/css/bootstrap.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta1/dist/js/bootstrap.bundle.min.js" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.2.1/jquery.min.js" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/boxicons@latest/css/boxicons.min.css" />
    
    <!-- Modern Font Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />

    <link rel="stylesheet" href="./style.css" />
    <script src="./script.js"></script>
    <link rel="stylesheet" href="css/style.css" />
    
    <!-- Modern Form CSS -->
    <style>
      :root {
        --primary-color: #4361ee;
        --primary-dark: #3a56d4;
        --secondary-color: #3f37c9;
        --accent-color: #4cc9f0;
        --success-color: #4ade80;
        --warning-color: #f59e0b;
        --danger-color: #ef4444;
        --light-color: #f8fafc;
        --dark-color: #1e293b;
        --gray-color: #64748b;
        --light-gray: #e2e8f0;
        --border-radius: 12px;
        --box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08);
        --box-shadow-light: 0 5px 15px rgba(0, 0, 0, 0.05);
        --transition: all 0.3s ease;
      }

      .modern-form-wrapper {
        margin-top: 12vh;
        display: flex;
        justify-content: center;
        align-items: center;
        padding: 20px;
        min-height: calc(100vh - 12vh);
        
      }

      .modern-form-card {
        background: white;
        border-radius: var(--border-radius);
        box-shadow: var(--box-shadow);
        width: 100%;
        max-width: 900px;
        overflow: hidden;
        transition: var(--transition);
        animation: fadeIn 0.5s ease-out;
      }

      .modern-form-card:hover {
        box-shadow: 0 15px 35px rgba(0, 0, 0, 0.12);
      }

      .form-header-section {
        background: linear-gradient(to right, var(--primary-color), var(--secondary-color));
        color: white;
        padding: 35px 40px;
        text-align: center;
        position: relative;
        overflow: hidden;
      }

      .form-header-section::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 320"><path fill="%23ffffff" fill-opacity="0.1" d="M0,192L48,197.3C96,203,192,213,288,229.3C384,245,480,267,576,250.7C672,235,768,181,864,181.3C960,181,1056,235,1152,234.7C1248,235,1344,181,1392,154.7L1440,128L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"></path></svg>');
        background-size: cover;
        opacity: 0.3;
      }

      .form-header-section h2 {
        font-size: 2.2rem;
        font-weight: 700;
        margin-bottom: 10px;
        position: relative;
        z-index: 1;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      }

      .form-header-section p {
        font-size: 1.1rem;
        opacity: 0.9;
        position: relative;
        z-index: 1;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        margin-bottom: 25px;
      }

      .form-icon {
        font-size: 2.8rem;
        margin-bottom: 20px;
        position: relative;
        z-index: 1;
        color: rgba(255, 255, 255, 0.95);
      }

      .form-body {
        padding: 40px;
      }

      .form-group {
        margin-bottom: 30px;
        position: relative;
      }

      .form-label {
        display: block;
        margin-bottom: 12px;
        font-weight: 600;
        color: var(--dark-color);
        font-size: 1.05rem;
        display: flex;
        align-items: center;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      }

      .form-label i {
        margin-right: 12px;
        color: var(--primary-color);
        font-size: 1.1rem;
      }

      .form-control-modern {
        width: 100%;
        padding: 16px 20px;
        border: 2px solid var(--light-gray);
        border-radius: var(--border-radius);
        font-size: 1rem;
        transition: var(--transition);
        background-color: white;
        color: var(--dark-color);
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        cursor: pointer;
      }

      .form-control-modern:focus {
        outline: none;
        border-color: var(--primary-color);
        box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.15);
      }

      .form-control-modern:read-only {
        background-color: #f8fafc;
        color: var(--gray-color);
        cursor: not-allowed;
        border-color: #d1d5db;
      }

      .select-wrapper {
        position: relative;
      }

      .select-wrapper::after {
        content: '\f078';
        font-family: 'Font Awesome 6 Free';
        font-weight: 900;
        position: absolute;
        top: 50%;
        right: 20px;
        transform: translateY(-50%);
        color: var(--gray-color);
        pointer-events: none;
        font-size: 1rem;
        transition: var(--transition);
      }

      .select-wrapper:hover::after {
        color: var(--primary-color);
      }

      .select-wrapper select {
        appearance: none;
        cursor: pointer;
        padding-right: 50px;
      }

      .select-wrapper select:hover {
        cursor: pointer;
      }

      .select-wrapper select option {
        padding: 12px 20px;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        cursor: pointer;
      }

      .select-wrapper select option:hover {
        background-color: var(--primary-color);
        color: white;
      }

      .select-wrapper select option[disabled] {
        background-color: var(--light-gray);
        color: var(--gray-color);
        font-weight: 600;
        font-size: 1rem;
        padding: 16px 20px;
        text-align: center;
        font-style: italic;
        cursor: default;
      }

      .btn-modern {
        padding: 17px 50px;
        background: linear-gradient(to right, var(--primary-color), var(--secondary-color));
        color: white;
        border: none;
        border-radius: var(--border-radius);
        font-size: 1.1rem;
        font-weight: 600;
        cursor: pointer;
        transition: var(--transition);
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 5px 15px rgba(67, 97, 238, 0.3);
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        letter-spacing: 0.5px;
      }

      .btn-modern:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 20px rgba(67, 97, 238, 0.4);
        background: linear-gradient(to right, var(--primary-dark), var(--secondary-color));
      }

      .btn-modern:active {
        transform: translateY(-1px);
      }

      .btn-modern i {
        margin-left: 12px;
        font-size: 1.2rem;
        transition: transform 0.3s ease;
      }

      .btn-modern:hover i {
        transform: translateX(5px);
      }

      .store-info-badge {
        display: inline-flex;
        align-items: center;
        background: linear-gradient(to right, #e0e7ff, #c7d2fe);
        color: var(--primary-color);
        padding: 10px 18px;
        border-radius: 50px;
        font-weight: 600;
        margin-top: 10px;
        font-size: 0.95rem;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      }

      .store-info-badge i {
        margin-right: 10px;
        font-size: 1rem;
      }

      .category-display {
        display: flex;
        align-items: center;
        background-color: #f0f9ff;
        border-left: 4px solid var(--accent-color);
        padding: 18px 22px;
        border-radius: var(--border-radius);
        margin-top: 8px;
        transition: var(--transition);
      }

      .category-display:hover {
        background-color: #e0f2fe;
        transform: translateX(5px);
      }

      .category-display i {
        color: var(--accent-color);
        margin-right: 15px;
        font-size: 1.4rem;
      }

      .category-display span {
        font-weight: 600;
        color: var(--dark-color);
        font-size: 1.15rem;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      }

      .form-button-container {
        display: flex;
        justify-content: center;
        margin-top: 40px;
      }

      .info-text {
        margin-top: 10px;
        color: var(--gray-color);
        font-size: 0.9rem;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        display: flex;
        align-items: center;
      }

      .info-text i {
        margin-right: 8px;
        color: var(--primary-color);
      }

      /* Step indicator */
      .step-indicator {
        display: flex;
        justify-content: center;
        margin-top: 20px;
        position: relative;
        z-index: 1;
      }

      .step {
        display: flex;
        flex-direction: column;
        align-items: center;
        position: relative;
        z-index: 1;
      }

      .step-number {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background-color: rgba(255, 255, 255, 0.3);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        margin-bottom: 8px;
        transition: var(--transition);
        border: 2px solid rgba(255, 255, 255, 0.5);
      }

      .step.active .step-number {
        background-color: white;
        color: var(--primary-color);
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
      }

      .step-label {
        font-size: 0.9rem;
        color: rgba(255, 255, 255, 0.8);
        font-weight: 500;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      }

      .step.active .step-label {
        color: white;
        font-weight: 600;
      }

      .step-line {
        width: 100px;
        height: 2px;
        background-color: rgba(255, 255, 255, 0.3);
        margin: 0 10px;
        position: relative;
        top: 20px;
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

      /* Hide old form */
      .wrapper, .editForm, .form-row, .form-holder, .select, .image-holder {
        display: none !important;
      }

      /* Responsive adjustments */
      @media (max-width: 992px) {
        .modern-form-card {
          max-width: 95%;
        }
        
        .form-body {
          padding: 30px 25px;
        }
        
        .form-header-section {
          padding: 30px 25px;
        }
        
        .form-header-section h2 {
          font-size: 1.9rem;
        }
        
        .step-line {
          width: 80px;
        }
      }

      @media (max-width: 768px) {
        .modern-form-wrapper {
          padding: 15px;
          margin-top: 10vh;
        }
        
        .form-header-section {
          padding: 25px 20px;
        }
        
        .form-body {
          padding: 25px 20px;
        }
        
        .form-header-section h2 {
          font-size: 1.7rem;
        }
        
        .step-line {
          width: 60px;
        }
        
        .btn-modern {
          padding: 15px 40px;
          width: 100%;
        }
      }

      @media (max-width: 576px) {
        .form-header-section {
          padding: 20px 15px;
        }
        
        .form-body {
          padding: 20px 15px;
        }
        
        .form-header-section h2 {
          font-size: 1.5rem;
        }
        
        .step-line {
          width: 40px;
        }
        
        .step-label {
          font-size: 0.8rem;
        }
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

    <!-- Modern Form Section -->
    <div class="modern-form-wrapper">
      <div class="modern-form-card">
        <div class="form-header-section">
          <div class="form-icon">
            <i class="fas fa-clipboard-check"></i>
          </div>
          <h2>Employee Evaluation</h2>
          <p style="color: white;">Select the attribute you wish to evaluate for this store</p>
          <!-- Step Indicator -->
          <div class="step-indicator">
            <div class="step active">
              <div class="step-number">1</div>
              <div class="step-label">Select Attribute</div>
            </div>
            <div class="step-line"></div>
            <div class="step">
              <div class="step-number">2</div>
              <div class="step-label">Evaluate</div>
            </div>
            <div class="step-line"></div>
            <div class="step">
              <div class="step-number">3</div>
              <div class="step-label">Submit</div>
            </div>
          </div>
        </div>
        
        <div class="form-body">
          <form class="modern-form" name="addForm" enctype="multipart/form-data" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST">
            <div class="form-group">
              <label class="form-label"><i class="fas fa-store"></i> Store Information</label>
              <input type="text" name="storeNumber" class="form-control-modern" value="<?php echo $rowfind['store_number'] ?>" <?php if ($rowfind['store_number']) { echo "readonly"; } ?> placeholder="Store Number" />
              <div class="store-info-badge">
                <i class="fas fa-info-circle"></i>
                Store is pre-selected from previous step
              </div>
            </div>
            
            <div class="form-group">
              <label class="form-label"><i class="fas fa-folder"></i> Category</label>
              <div class="category-display">
                <i class="fas fa-tag"></i>
                <span>
                  <?php
                  $sqlcat = "SELECT * FROM category";
                  $resultcat = $conn->query($sqlcat);
                  while ($rowcat = $resultcat->fetch_assoc()) {
                    if ($rowfind['category_name'] == $rowcat['category_name']) {
                      echo $rowcat['category_name'];
                    }
                  }
                  ?>
                </span>
              </div>
            </div>
            
            <div class="form-group">
              <label class="form-label"><i class="fas fa-list-check"></i> Select Attribute to Evaluate *</label>
              <div class="select-wrapper">
                <select class="form-control-modern" name="attribute" required>
                  <option value="" disabled selected>Choose an attribute...</option>
                  <?php if ($resultatt->num_rows > 0) {
                    $i = 0; ?>
                    <?php while ($rowatt = $resultatt->fetch_assoc()) {
                      $i = $i + 1;
                      if ($i == 1) { ?>
                        <option style="background-color: #f8fafc; color: #64748b; font-weight: 600; padding: 16px 20px; text-align: center;" disabled>────────── JOB CAPABILITIES ──────────</option>
                      <?php
                      } else if ($i == 11) { ?>
                        <option style="background-color: #f8fafc; color: #64748b; font-weight: 600; padding: 16px 20px; text-align: center;" disabled>────────── PERSONAL SKILLS ──────────</option>
                      <?php
                      } else if ($i == 16) { ?>
                        <option style="background-color: #f8fafc; color: #64748b; font-weight: 600; padding: 16px 20px; text-align: center;" disabled>────────── PERSONAL VALUES ──────────</option>
                      <?php
                      } ?>
                      <?php if ($rowatt['PLE'] && $_SESSION["PLE"]) {  ?>
                        <option value="<?php echo $rowatt['attribute_id'] ?>"><?php echo $i . ". " . $rowatt['attribute_name'] ?></option>
                      <?php
                      } else if ($rowatt['OE'] && $_SESSION["OE"]) {  ?>
                        <option value="<?php echo $rowatt['attribute_id'] ?>"><?php echo $i . ". " . $rowatt['attribute_name'] ?></option>
                      <?php
                      } else if ($rowatt['QM'] && $_SESSION["QM"]) {  ?>
                        <option value="<?php echo $rowatt['attribute_id'] ?>"><?php echo $i . ". " . $rowatt['attribute_name'] ?></option>
                      <?php
                      } else if ($rowatt['PE'] && $_SESSION["PE"]) {  ?>
                        <option value="<?php echo $rowatt['attribute_id'] ?>"><?php echo $i . ". " . $rowatt['attribute_name'] ?></option>
                  <?php
                      }
                    }
                  } ?>
                </select>
              </div>
              <div class="info-text">
                <i class="fas fa-lightbulb"></i> Attributes are filtered based on your evaluation permissions
              </div>
            </div>
            
            <div class="form-button-container">
              <button type="submit" name="submit" class="btn-modern">
                Continue to Evaluation <i class="fas fa-arrow-right"></i>
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- Additional JavaScript for form validation -->
    <script>
      $(document).ready(function() {
        // Add animation to form elements
        $('.form-control-modern').on('focus', function() {
          $(this).parent().addClass('focused');
        }).on('blur', function() {
          $(this).parent().removeClass('focused');
        });
        
        // Form validation feedback
        $('.modern-form').on('submit', function(e) {
          const attributeSelect = $('select[name="attribute"]');
          if (attributeSelect.val() === '' || attributeSelect.val() === null) {
            e.preventDefault();
            attributeSelect.addClass('is-invalid');
            attributeSelect.focus();
            
            // Create error message if not exists
            if (!$('.select-error').length) {
              $('<div class="select-error text-danger mt-2" style="font-size: 0.9rem; font-weight: 500;"><i class="fas fa-exclamation-circle"></i> Please select an attribute to evaluate</div>').insertAfter(attributeSelect.parent());
            }
          }
        });
        
        // Remove error when selection made
        $('select[name="attribute"]').on('change', function() {
          $(this).removeClass('is-invalid');
          $('.select-error').remove();
        });
        
        // Add smooth hover effect to category display
        $('.category-display').hover(
          function() {
            $(this).css('transform', 'translateX(5px)');
          },
          function() {
            $(this).css('transform', 'translateX(0)');
          }
        );
      });
    </script>

  </body>

  </html>

<?php } else {
  header("location: signin.php");
} ?>