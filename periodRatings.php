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

  if (isset($_POST['submitPeriod']) && !empty($_POST['sPeriod']) && !empty($_POST['ePeriod'])) {
    $sPeriod = $_POST['sPeriod'];
    $ePeriod = $_POST['ePeriod'];
    echo "<script>
          window.location.href='./Ratings.php?sp=$sPeriod&ep=$ePeriod';
          </script>";
  } else if (isset($_POST['submitPeriod'])) {
    echo "<script>
          window.location.href='./periodRatings.php';
          </script>";
  }

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
  
  // Calculate current year periods
  $currentYear = date('Y');
  $currentYearStart = $currentYear . '-01'; // January of current year
  $currentYearEnd = date('Y-m'); // Current month
?>

  <!DOCTYPE html>
  <html lang="en">

  <head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Results & Grading | JB Employee Evaluation</title>
    <link rel="icon" type="image/x-icon" href="./img/jb.png">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta1/dist/css/bootstrap.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta1/dist/js/bootstrap.bundle.min.js" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.2.1/jquery.min.js" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/boxicons@latest/css/boxicons.min.css" />
    
    <!-- Modern Font Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />

    <link rel="stylesheet" href="./style.css" />
    <script src="./script.js"></script>
    
    <!-- Modern CSS for Results Page -->
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
        --kpi-color: #f97316;
        --border-radius: 16px;
        --box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        --box-shadow-light: 0 5px 20px rgba(0, 0, 0, 0.05);
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

      .results-wrapper {
        margin-top: 12vh;
        padding: 2rem;
       
        min-height: calc(100vh - 12vh);
        display: flex;
        justify-content: center;
        align-items: center;
      }

      .results-container {
        width: 100%;
        max-width: 1200px;
        margin: 0 auto;
      }

      .page-header {
        text-align: center;
        margin-bottom: 3rem;
      }

      .page-header h1 {
        color: var(--dark-color);
        font-weight: 700;
        font-size: 2.5rem;
        position: relative;
        padding-bottom: 1.5rem;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        margin-bottom: 1rem;
      }

      .page-header h1::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 50%;
        transform: translateX(-50%);
        width: 150px;
        height: 4px;
        background: linear-gradient(90deg, var(--primary-color), var(--secondary-color));
        border-radius: 2px;
      }

      .page-header p {
        color: black;
        font-size: 1.1rem;
        max-width: 600px;
        margin: 0 auto;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      }

      .cards-container {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
        gap: 2rem;
        margin-bottom: 3rem;
      }

      .period-card {
        background: white;
        border-radius: var(--border-radius);
        box-shadow: var(--box-shadow);
        padding: 2.5rem;
        transition: var(--transition);
        position: relative;
        overflow: hidden;
        cursor: pointer;
        height: 100%;
        display: flex;
        flex-direction: column;
        justify-content: center;
        animation: fadeIn 0.6s ease-out;
      }

      .period-card:hover {
        transform: translateY(-8px);
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
      }

      .period-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 5px;
        background: linear-gradient(90deg, var(--primary-color), var(--secondary-color));
      }

      .period-card.custom-period {
        border: 2px dashed var(--primary-color);
        background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
      }

      .period-card h3 {
        color: var(--dark-color);
        font-weight: 700;
        font-size: 1.8rem;
        margin-bottom: 1rem;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        display: flex;
        align-items: center;
        gap: 12px;
      }

      .period-card h3 i {
        color: var(--primary-color);
        font-size: 1.8rem;
      }

      .period-card p {
        color: var(--gray-color);
        font-size: 1rem;
        margin-bottom: 1.5rem;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        line-height: 1.6;
      }

      .period-card .card-icon {
        font-size: 3.5rem;
        color: var(--primary-color);
        opacity: 0.9;
        margin-bottom: 1.5rem;
        text-align: center;
      }

      /* Custom Period Form */
      .custom-period-form {
        margin-top: 1.5rem;
      }

      .form-row {
        display: flex;
        flex-wrap: wrap;
        gap: 1.5rem;
        margin-bottom: 1.5rem;
      }

      .form-group {
        flex: 1;
        min-width: 200px;
      }

      .form-group label {
        display: block;
        margin-bottom: 8px;
        color: var(--dark-color);
        font-weight: 600;
        font-size: 14px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      }

      .form-control-modern {
        width: 100%;
        padding: 14px 16px;
        border: 2px solid var(--light-gray);
        border-radius: 12px;
        font-size: 15px;
        background: var(--light-color);
        transition: var(--transition);
        color: var(--dark-color);
        font-weight: 500;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      }

      .form-control-modern:focus {
        outline: none;
        border-color: var(--primary-color);
        background: white;
        box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.1);
      }

      /* Submit Button */
      .btn-modern {
        background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
        color: white;
        border: none;
        padding: 16px 40px;
        font-size: 16px;
        font-weight: 600;
        border-radius: 12px;
        cursor: pointer;
        transition: var(--transition);
        box-shadow: 0 5px 20px rgba(67, 97, 238, 0.3);
        text-transform: uppercase;
        letter-spacing: 1px;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        position: relative;
        overflow: hidden;
        width: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
      }

      .btn-modern::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
        transition: 0.5s;
      }

      .btn-modern:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 25px rgba(67, 97, 238, 0.4);
        background: linear-gradient(135deg, var(--primary-dark) 0%, var(--secondary-color) 100%);
      }

      .btn-modern:hover::before {
        left: 100%;
      }

      .btn-modern:active {
        transform: translateY(-1px);
      }

      .btn-modern i {
        font-size: 1.2rem;
        transition: transform 0.3s ease;
      }

      .btn-modern:hover i {
        transform: translateX(5px);
      }

      /* Info Text */
      .info-text {
        color: var(--gray-color);
        font-size: 0.9rem;
        font-style: italic;
        margin-top: 1rem;
        text-align: center;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      }

      /* Link Cards Styling */
      a.period-card-link {
        text-decoration: none;
        display: block;
        transition: var(--transition);
      }

      a.period-card-link:hover .period-card {
        transform: translateY(-8px);
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
      }

      /* Animation */
      @keyframes fadeIn {
        from {
          opacity: 0;
          transform: translateY(30px);
        }
        to {
          opacity: 1;
          transform: translateY(0);
        }
      }

      .period-card:nth-child(1) { animation-delay: 0.1s; }
      .period-card:nth-child(2) { animation-delay: 0.2s; }
      .period-card:nth-child(3) { animation-delay: 0.3s; }

      /* Hide old styling */
      .height-100, .card.p-3.mb-2 {
        display: none !important;
      }

      /* New Report Card Styles */
      .report-section {
        margin-top: 4rem;
        padding-top: 2rem;
        border-top: 2px solid var(--light-gray);
      }

      .report-header {
        text-align: center;
        margin-bottom: 2rem;
      }

      .report-header h2 {
        color: var(--dark-color);
        font-weight: 700;
        font-size: 2rem;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        margin-bottom: 1rem;
      }

      .report-header p {
        color: var(--gray-color);
        max-width: 600px;
        margin: 0 auto;
      }

      .report-cards {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 2rem;
        margin-bottom: 2rem;
      }

      .report-card {
        background: white;
        border-radius: var(--border-radius);
        box-shadow: var(--box-shadow);
        padding: 2rem;
        transition: var(--transition);
        position: relative;
        overflow: hidden;
        text-decoration: none;
        display: block;
        animation: fadeIn 0.6s ease-out;
      }

      .report-card:hover {
        transform: translateY(-8px);
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
      }

      .report-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 5px;
        background: linear-gradient(90deg, #f59e0b, #d97706);
      }

      .report-card.kpi-card::before {
        background: linear-gradient(90deg, var(--kpi-color), #fb923c);
      }

      .report-card .card-icon {
        font-size: 3rem;
        color: #f59e0b;
        margin-bottom: 1.5rem;
        text-align: center;
      }

      .report-card.kpi-card .card-icon {
        color: var(--kpi-color);
      }

      .report-card h3 {
        color: var(--dark-color);
        font-weight: 700;
        font-size: 1.5rem;
        margin-bottom: 1rem;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        text-align: center;
      }

      .report-card p {
        color: var(--gray-color);
        font-size: 0.95rem;
        line-height: 1.6;
        margin-bottom: 1.5rem;
        text-align: center;
      }

      .report-card .report-badge {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
        color: white;
        padding: 8px 20px;
        border-radius: 50px;
        font-weight: 600;
        font-size: 0.9rem;
        margin: 0 auto;
        width: fit-content;
      }

      .report-card.kpi-card .report-badge {
        background: linear-gradient(135deg, var(--kpi-color) 0%, #fb923c 100%);
      }

      /* Responsive Design */
      @media (max-width: 992px) {
        .cards-container {
          grid-template-columns: 1fr;
        }
        
        .results-wrapper {
          padding: 1.5rem;
          margin-top: 10vh;
        }
        
        .page-header h1 {
          font-size: 2.2rem;
        }
      }

      @media (max-width: 768px) {
        .results-wrapper {
          padding: 1rem;
        }
        
        .period-card {
          padding: 2rem;
        }
        
        .page-header h1 {
          font-size: 1.9rem;
        }
        
        .form-row {
          flex-direction: column;
          gap: 1rem;
        }
        
        .form-group {
          min-width: 100%;
        }
        
        .report-cards {
          grid-template-columns: 1fr;
        }
      }

      @media (max-width: 480px) {
        .period-card {
          padding: 1.5rem;
        }
        
        .page-header h1 {
          font-size: 1.6rem;
        }
        
        .period-card h3 {
          font-size: 1.5rem;
        }
        
        .btn-modern {
          padding: 14px 30px;
          font-size: 15px;
        }
      }

      /* Badge for admin/evaluator only */
      .admin-only-badge {
        display: inline-flex;
        align-items: center;
        background: linear-gradient(to right, #e0e7ff, #c7d2fe);
        color: var(--primary-color);
        padding: 6px 14px;
        border-radius: 50px;
        font-weight: 600;
        font-size: 0.8rem;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        margin-bottom: 1rem;
      }

      .admin-only-badge i {
        margin-right: 6px;
        font-size: 0.9rem;
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
            
            <a href="./periodRatings.php" class="nav_link active"> <i class='bx bxs-star-half'></i> <span class="nav_name">Results & Grading</span> </a>
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

    <!-- Modern Results Section -->
    <div class="results-wrapper">
      <div class="results-container">
        <div class="page-header">
          <h1>Results & Grading</h1>
          <p>View evaluation results and performance reports for different time periods</p>
        </div>
        
        <div class="cards-container">
          <!-- Custom Time Period Card -->
          <div class="period-card custom-period">
            <?php if ($_SESSION['isAdmin'] == 0 && $_SESSION["isEvaluator"] == 0): ?>
              <div class="admin-only-badge">
                <i class="fas fa-lock"></i> Admin/Evaluator Access Only
              </div>
            <?php endif; ?>
            
            <div class="card-icon">
              <i class="fas fa-calendar-alt"></i>
            </div>
            
            <h3><i class="fas fa-calendar-plus"></i> Custom Time Period</h3>
            <p>Select specific start and end dates to view evaluation results for any custom time period</p>
            
            <?php if ($_SESSION['isAdmin'] || $_SESSION["isEvaluator"]): ?>
              <div class="custom-period-form">
                <form name="pickPeriod" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST">
                  <div class="form-row">
                    <div class="form-group">
                      <label for="sPeriod"><i class="fas fa-calendar-day"></i> Start Period</label>
                      <input type="month" name="sPeriod" class="form-control-modern" />
                    </div>
                    <div class="form-group">
                      <label for="ePeriod"><i class="fas fa-calendar-week"></i> End Period</label>
                      <input type="month" name="ePeriod" class="form-control-modern" value="<?= date('Y-m') ?>" />
                    </div>
                  </div>
                  <button type="submit" name="submitPeriod" class="btn-modern">
                    <i class="fas fa-chart-line"></i> Generate Report
                  </button>
                </form>
                <div class="info-text">
                  Select both start and end periods to generate custom reports
                </div>
              </div>
            <?php else: ?>
              <div style="text-align: center; padding: 1.5rem; background: var(--light-color); border-radius: 10px; margin-top: 1rem;">
                <i class="fas fa-lock" style="font-size: 2rem; color: var(--gray-color); margin-bottom: 1rem;"></i>
                <p style="color: var(--gray-color); margin-bottom: 0;">This feature requires administrative privileges</p>
              </div>
            <?php endif; ?>
          </div>
          
          <!-- Previous Year Card -->
          <?php 
          $period = date('Y-m', strtotime('first day of January last year'));
          $periodl = date('Y-m', strtotime('last day of December last year'));
          ?>
          <a href="./Ratings.php?sp=<?php echo $period; ?>&ep=<?php echo $periodl; ?>" class="period-card-link">
            <div class="period-card">
              <div class="card-icon">
                <i class="fas fa-history"></i>
              </div>
              
              <h3><i class="fas fa-calendar-minus"></i> Previous Year</h3>
              <p>View complete evaluation results and performance reports for the previous calendar year</p>
              
              <div style="margin-top: auto;">
                <div class="info-text" style="text-align: center; background: var(--light-color); padding: 10px; border-radius: 8px; margin-bottom: 1rem;">
                  <i class="fas fa-calendar"></i> 
                  <?php 
                  $prevYear = date('Y') - 1;
                  echo "January {$prevYear} - December {$prevYear}";
                  ?>
                </div>
                <button class="btn-modern" style="background: linear-gradient(135deg, #6b7280 0%, #4b5563 100%);">
                  <i class="fas fa-chart-bar"></i> View Previous Year Report
                </button>
              </div>
            </div>
          </a>
          
          <!-- Current Year Card - FIXED: Now includes parameters for the full current year -->
          <a href="./Ratings.php?sp=<?php echo $currentYearStart; ?>&ep=<?php echo $currentYearEnd; ?>" class="period-card-link">
            <div class="period-card">
              <div class="card-icon">
                <i class="fas fa-chart-line"></i>
              </div>
              
              <h3><i class="fas fa-calendar-check"></i> Current Year</h3>
              <p>View evaluation results and performance reports for the current calendar year up to present</p>
              
              <div style="margin-top: auto;">
                <div class="info-text" style="text-align: center; background: var(--light-color); padding: 10px; border-radius: 8px; margin-bottom: 1rem;">
                  <i class="fas fa-calendar"></i> 
                  <?php 
                  echo "January {$currentYear} - " . date('F Y');
                  ?>
                </div>
                <button class="btn-modern">
                  <i class="fas fa-chart-pie"></i> View Current Year Report
                </button>
              </div>
            </div>
          </a>
        </div>
        
        <!-- Specialized Reports Section -->
        
        
        <!-- Additional Information -->
        <div style="text-align: center; margin-top: 3rem; padding: 2rem; background: white; border-radius: var(--border-radius); box-shadow: var(--box-shadow-light);">
          <h4 style="color: var(--dark-color); margin-bottom: 1rem; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;">
            <i class="fas fa-info-circle" style="color: var(--primary-color);"></i> About Results & Grading
          </h4>
          <p style="color: var(--gray-color); max-width: 800px; margin: 0 auto; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;">
            Select a time period to view comprehensive evaluation reports, performance analytics, and grading summaries. 
            Reports include individual performance scores, store-wise comparisons, and overall grading distributions.
          </p>
        </div>
      </div>
    </div>

    <!-- Additional JavaScript for form validation -->
    <script>
      document.addEventListener('DOMContentLoaded', function() {
        // Add animation to form elements
        document.querySelectorAll('.form-control-modern').forEach(function(element) {
          element.addEventListener('focus', function() {
            this.parentElement.classList.add('focused');
          });
          element.addEventListener('blur', function() {
            this.parentElement.classList.remove('focused');
          });
        });
        
        // Form validation for custom period
        document.querySelector('form[name="pickPeriod"]').addEventListener('submit', function(e) {
          const startPeriod = document.querySelector('input[name="sPeriod"]');
          const endPeriod = document.querySelector('input[name="ePeriod"]');
          
          if (!startPeriod.value || !endPeriod.value) {
            e.preventDefault();
            alert('Please select both start and end periods');
            return false;
          }
          
          if (startPeriod.value > endPeriod.value) {
            e.preventDefault();
            alert('Start period must be before end period');
            return false;
          }
          
          return true;
        });
      });
    </script>

  </body>

  </html>

<?php } else {
  header("location: signin.php");
} ?>