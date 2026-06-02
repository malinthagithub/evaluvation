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

  // MODIFIED: Employee fetching logic based on period
  if (isset($_GET['id'])) {
    // For evaluation page: show all employees with current_store (active)
    $store = $_GET['id'];
    $sql = "SELECT * FROM employee WHERE current_store = '$store' AND current_store IS NOT NULL AND current_store != ''";
  } else {
    // For results page: show employees based on period activity
    if (isset($_GET['sp']) && isset($_GET['ep'])) {
      // Custom period - show employees who have evaluations in that period OR are currently active
      $sPeriod = $_GET['sp'];
      $ePeriod = $_GET['ep'];
      
      $sql = "SELECT DISTINCT e.* 
              FROM employee e
              LEFT JOIN evaluation ev ON e.emp_id = ev.emp_id 
                AND ev.period BETWEEN '$sPeriod' AND '$ePeriod'
              WHERE (ev.emp_id IS NOT NULL 
                OR (e.current_store IS NOT NULL AND e.current_store != '' 
                    AND e.current_category IS NOT NULL AND e.current_category != ''))
              ORDER BY e.emp_name";
    } else {
      // Current year - show employees who have evaluations this year OR are currently active
      $year = date('Y');
      
      $sql = "SELECT DISTINCT e.* 
              FROM employee e
              LEFT JOIN evaluation ev ON e.emp_id = ev.emp_id 
                AND ev.period LIKE '$year%'
              WHERE (ev.emp_id IS NOT NULL 
                OR (e.current_store IS NOT NULL AND e.current_store != '' 
                    AND e.current_category IS NOT NULL AND e.current_category != ''))
              ORDER BY e.emp_name";
    }
  }
  $result = $conn->query($sql);

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
?>

  <!DOCTYPE html>
  <html lang="en">

  <head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Results</title>
    <link rel="icon" type="image/x-icon" href="./img/jb.png">

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
      }

      body {
        background-color: #f5f7fb;
      }

      .height-100 {
        padding-top: 2vh;
        padding-bottom: 2vh;
      }

      .page-header {
        margin-bottom: 2.5rem;
        padding: 0 15px;
      }

      .page-header h1 {
        font-size: 2rem;
        font-weight: 700;
        color: var(--dark-color);
        margin-bottom: 0.5rem;
      }

      .page-header p {
        color:black;
        font-size: 1rem;
        margin-bottom: 0;
      }

      /* Modern Employee Cards with Two Sections */
      .employee-card-split {
        background: white;
        border-radius: var(--radius-xl);
        height: 96%;
        border: none;
        box-shadow: var(--shadow-md);
        transition: var(--transition);
        position: relative;
        overflow: hidden;
        text-decoration: none !important;
        display: block;
        color: inherit;
        margin-bottom: 1.5rem;
      }

      .employee-card-split:hover {
        transform: translateY(-8px);
        box-shadow: var(--shadow-lg);
        text-decoration: none;
        color: inherit;
      }

      /* Top Blue Section */
      .card-top-section {
        background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
        color: white;
        padding: 1.75rem 1.75rem 1.5rem;
        border-radius: var(--radius-xl) var(--radius-xl) 0 0;
        position: relative;
        min-height: 180px;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
      }

      .employee-card-split.Admin .card-top-section {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      }

      .employee-card-split.Evaluator .card-top-section {
        background: linear-gradient(135deg, #4CAF50 0%, #2E7D32 100%);
      }

      .employee-card-split.Guest .card-top-section {
        background: linear-gradient(135deg, #757575 0%, #424242 100%);
      }

      /* Bottom White Section */
      .card-bottom-section {
        padding: 1.75rem;
        background: white;
        border-radius: 0 0 var(--radius-xl) var(--radius-xl);
      }

      /* Employee Avatar in Top Section */
      .employee-avatar-top {
        width: 70px;
        height: 70px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.2);
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 1rem;
        font-size: 1.8rem;
        color: white;
        border: 3px solid rgba(255, 255, 255, 0.3);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
      }

      /* Employee Info in Top Section */
      .employee-name-top {
        font-size: 1.3rem;
        font-weight: 700;
        text-align: center;
        margin-bottom: 0.25rem;
        line-height: 1.3;
      }

      .employee-id-top {
        font-size: 0.95rem;
        opacity: 0.9;
        text-align: center;
        margin-bottom: 0.75rem;
        font-weight: 500;
      }

      .employee-gender-top {
        display: inline-block;
        padding: 0.25rem 0.75rem;
        background: rgba(255, 255, 255, 0.2);
        color: white;
        border-radius: var(--radius-md);
        font-size: 0.8rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 0.5rem;
      }

      /* Current Details in Bottom Section */
      .current-details-split {
        margin-bottom: 1.5rem;
        padding-bottom: 1.5rem;
        border-bottom: 1px solid var(--border-color);
      }

      .details-label-split {
        font-size: 0.85rem;
        color: var(--gray-color);
        text-transform: uppercase;
        letter-spacing: 0.5px;
        font-weight: 600;
        margin-bottom: 0.75rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
      }

      .details-label-split i {
        color: var(--primary-color);
        font-size: 1rem;
      }

      .employee-card-split.Admin .details-label-split i {
        color: #667eea;
      }

      .employee-card-split.Evaluator .details-label-split i {
        color: #4CAF50;
      }

      .employee-card-split.Guest .details-label-split i {
        color: #757575;
      }

      .store-info {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1rem;
      }

      .info-item {
        text-align: center;
      }

      .info-value {
        font-size: 1.1rem;
        font-weight: 700;
        color: var(--dark-color);
        margin-bottom: 0.25rem;
      }

      .info-label {
        font-size: 0.8rem;
        color: var(--gray-color);
        text-transform: uppercase;
        letter-spacing: 0.5px;
      }

      /* Rating Section in Bottom */
      .rating-section-split {
        text-align: center;
      }

      .rating-label-split {
        font-size: 0.85rem;
        color: var(--gray-color);
        text-transform: uppercase;
        letter-spacing: 0.5px;
        font-weight: 600;
        margin-bottom: 0.75rem;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
      }

      .rating-label-split i {
        color: var(--success-color);
        font-size: 1rem;
      }

      .rating-value-split {
        font-size: 2rem;
        font-weight: 800;
        color: var(--success-color);
        line-height: 1.2;
        margin-bottom: 0.25rem;
      }

      .rating-subtext-split {
        font-size: 0.85rem;
        color: var(--gray-color);
        font-weight: 500;
      }

      /* Evaluate Button */
      .evaluate-btn-split {
        position: absolute;
        top: 1rem;
        right: 1rem;
        background: white;
        color: var(--primary-color);
        border: none;
        border-radius: var(--radius-md);
        padding: 0.5rem 1rem;
        font-size: 0.85rem;
        font-weight: 600;
        cursor: pointer;
        transition: var(--transition);
        display: flex;
        align-items: center;
        gap: 0.5rem;
        text-decoration: none;
        z-index: 10;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
      }

      .evaluate-btn-split:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        color: var(--primary-color);
        text-decoration: none;
      }

      .employee-card-split.Admin .evaluate-btn-split {
        color: #667eea;
      }

      .employee-card-split.Evaluator .evaluate-btn-split {
        color: #4CAF50;
      }

      .employee-card-split.Guest .evaluate-btn-split {
        color: #757575;
      }

      /* View Details Button */
      .view-details-btn {
        position: absolute;
        bottom: 1.75rem;
        right: 1.75rem;
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: var(--primary-color);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: var(--transition);
        opacity: 0;
        transform: translateY(10px);
        box-shadow: 0 4px 8px rgba(67, 97, 238, 0.3);
      }

      .employee-card-split:hover .view-details-btn {
        opacity: 1;
        transform: translateY(0);
      }

      .employee-card-split.Admin .view-details-btn {
        background: #667eea;
      }

      .employee-card-split.Evaluator .view-details-btn {
        background: #4CAF50;
      }

      .employee-card-split.Guest .view-details-btn {
        background: #757575;
      }

      /* Empty State */
      .empty-state {
        text-align: center;
        padding: 4rem 2rem;
        background: white;
        border-radius: var(--radius-xl);
        box-shadow: var(--shadow-md);
        grid-column: 1 / -1;
      }

      .empty-state-icon {
        font-size: 4rem;
        color: var(--border-color);
        margin-bottom: 1.5rem;
        opacity: 0.5;
      }

      .empty-state-title {
        font-size: 1.5rem;
        font-weight: 600;
        color: var(--gray-color);
        margin-bottom: 0.5rem;
      }

      .empty-state-text {
        color: var(--gray-color);
        margin-bottom: 1.5rem;
        max-width: 400px;
        margin-left: auto;
        margin-right: auto;
      }

      /* Floating Container */
      .floating-container {
        position: fixed;
        bottom: 2rem;
        right: 2rem;
        z-index: 1000;
      }

      .floating-button-modern {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        box-shadow: 0 4px 15px rgba(67, 97, 238, 0.4);
        cursor: pointer;
        transition: var(--transition);
        text-decoration: none;
      }

      .floating-button-modern:hover {
        transform: scale(1.1) rotate(90deg);
        box-shadow: 0 6px 20px rgba(67, 97, 238, 0.6);
        color: white;
        text-decoration: none;
      }

      .floating-button-modern.Admin {
        background: linear-gradient(135deg, #667eea, #764ba2);
      }

      .floating-button-modern.Evaluator {
        background: linear-gradient(135deg, #4CAF50, #2E7D32);
      }

      .floating-button-modern.Guest {
        background: linear-gradient(135deg, #757575, #424242);
      }

      /* Store History Styles */
      .history-container {
        margin-bottom: 1.5rem;
      }

      .history-list {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
        justify-content: center;
      }

      .history-item {
        display: flex;
        align-items: center;
        background: #f8f9fa;
        border-radius: 20px;
        padding: 0.25rem 0.75rem;
        font-size: 0.85rem;
        border: 1px solid #e9ecef;
      }

      .history-item.current {
        background: #e3f2fd;
        border-color: #90caf9;
        font-weight: 600;
      }

      .history-item.previous {
        background: #fff3e0;
        border-color: #ffb74d;
      }

      .history-year {
        font-weight: 600;
        margin-right: 0.25rem;
      }

      .history-store {
        color: #2c3e50;
      }

      .history-item.current .history-store {
        color: #1565c0;
      }

      .history-item.previous .history-store {
        color: #e65100;
      }

      .history-icon {
        margin-right: 0.25rem;
        font-size: 0.8rem;
      }

      .multi-store-badge {
        display: inline-block;
        background: #10b981;
        color: white;
        font-size: 0.7rem;
        padding: 0.1rem 0.3rem;
        border-radius: 10px;
        margin-left: 0.3rem;
      }

      /* Inactive Employee Notice */
      .inactive-notice {
        background: #fef3c7;
        color: #d97706;
        border: 1px solid #fde68a;
        border-radius: var(--radius-md);
        padding: 0.5rem 1rem;
        font-size: 0.8rem;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        margin-top: 0.5rem;
      }

      .inactive-notice i {
        font-size: 0.9rem;
      }

      /* Responsive Design */
      @media (max-width: 992px) {
        .card-top-section {
          padding: 1.5rem 1.5rem 1.25rem;
          min-height: 170px;
        }
        
        .card-bottom-section {
          padding: 1.5rem;
        }
        
        .employee-name-top {
          font-size: 1.2rem;
        }
        
        .rating-value-split {
          font-size: 1.8rem;
        }
      }

      @media (max-width: 768px) {
        .height-100 {
          padding-top: 1vh;
        }
        
        .page-header {
          margin-bottom: 2rem;
        }
        
        .page-header h1 {
          font-size: 1.75rem;
        }
        
        .view-details-btn {
          opacity: 1;
          transform: translateY(0);
        }
        
        .floating-container {
          bottom: 1.5rem;
          right: 1.5rem;
        }
      }

      @media (max-width: 576px) {
        .employee-avatar-top {
          width: 60px;
          height: 60px;
          font-size: 1.5rem;
        }
        
        .evaluate-btn-split {
          position: relative;
          top: 0;
          right: 0;
          width: 100%;
          justify-content: center;
          margin-top: 1rem;
          margin-bottom: 0.5rem;
        }
        
        .view-details-btn {
          bottom: 1rem;
          right: 1rem;
        }
        
        .store-info {
          grid-template-columns: 1fr;
          gap: 0.75rem;
        }
        
        .history-list {
          flex-direction: column;
          align-items: stretch;
        }
        
        .history-item {
          justify-content: center;
        }
      }

      /* Animation for cards */
      @keyframes fadeInUp {
        from {
          opacity: 0;
          transform: translateY(20px);
        }
        to {
          opacity: 1;
          transform: translateY(0);
        }
      }

      .employee-card-split {
        animation: fadeInUp 0.5s ease-out;
      }

      .employee-card-split:nth-child(1) { animation-delay: 0.1s; }
      .employee-card-split:nth-child(2) { animation-delay: 0.2s; }
      .employee-card-split:nth-child(3) { animation-delay: 0.3s; }
      .employee-card-split:nth-child(4) { animation-delay: 0.4s; }
      .employee-card-split:nth-child(5) { animation-delay: 0.5s; }
      .employee-card-split:nth-child(6) { animation-delay: 0.6s; }
      .employee-card-split:nth-child(7) { animation-delay: 0.7s; }
      .employee-card-split:nth-child(8) { animation-delay: 0.8s; }
      
      /* Period Info Banner */
      .period-info-banner {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 1rem 2rem;
        border-radius: var(--radius-lg);
        margin-bottom: 2rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 1rem;
      }
      
      .period-info-banner i {
        font-size: 1.5rem;
        margin-right: 0.5rem;
      }
      
      .period-info-banner .period-dates {
        font-size: 1.2rem;
        font-weight: 600;
      }
      
      .period-info-banner .period-badge {
        background: rgba(255, 255, 255, 0.2);
        padding: 0.5rem 1.5rem;
        border-radius: 50px;
        font-weight: 600;
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

    <div class="height-100 container mt-5 mb-3">
      <!-- Page Header -->
     <!-- Page Header -->
<div class="page-header">
    <?php if (isset($_GET['id'])): ?>
        <h1>Evaluate an Individual</h1>
        <p>Select an employee to complete the evaluation form</p>
    <?php else: ?>
        <h1>Employee Results & Grading</h1>
        <p>View detailed evaluation results for each employee</p>
    <?php endif; ?>
</div>
      
      <!-- Period Info Banner -->
     <?php if (!isset($_GET['id'])): ?>
    <?php
    if (isset($_GET['sp']) && isset($_GET['ep'])) {
        $sp = $_GET['sp'];
        $ep = $_GET['ep'];
        $startYear = date('Y', strtotime($sp));
        $endYear = date('Y', strtotime($ep));
        $displayPeriod = date('F Y', strtotime($sp)) . ' - ' . date('F Y', strtotime($ep));
    } else {
        $currentYear = date('Y');
        $displayPeriod = "Current Year ($currentYear)";
    }
    ?>
    <div class="period-info-banner">
        <div>
            <i class='bx bx-calendar'></i>
            <span class="period-dates">Showing results for: <?php echo $displayPeriod; ?></span>
        </div>
        <div class="period-badge">
            <i class='bx bx-time'></i> <?php echo isset($_GET['sp']) ? 'Custom Period' : 'Year-to-Date'; ?>
        </div>
    </div>
<?php endif; ?>

<div class="row">
        <?php 
        // Cache array for scoring methods
        $scoringMethodCache = array();

        if ($result->num_rows > 0) {
          while ($row = $result->fetch_assoc()) { 
            // Calculate rating with year-specific scoring methods
            $emp_id = $row['emp_id'];
            $cStore = $row['current_store'];
            $cCategory = $row['current_category'];
            
            // Check if employee is currently active (2026)
            $isCurrentlyActive = !empty($cStore) && !empty($cCategory);
            
            // Get ALL store history for display purposes (both current and previous)
            $all_history_sql = "SELECT * FROM employee_store_history WHERE emp_id = '$emp_id' ORDER BY year DESC";
            $all_history_result = $conn->query($all_history_sql);
            
            // Create an array to store all history years with their stores
            $history_stores = array();
            $history_years = array();
            
            // Add current store to history (as most recent) - only if employee is active
            if ($isCurrentlyActive) {
              $current_year = date('Y');
              $history_stores[$current_year] = array(
                'year' => $current_year,
                'store' => $cStore,
                'is_current' => true
              );
            }
            
            // Add all historical records from database
            while ($history_row = $all_history_result->fetch_assoc()) {
              $year = $history_row['year'];
              $store = $history_row['store'];
              
              // Only add if not already added (avoid duplicates)
              if (!isset($history_stores[$year])) {
                $history_stores[$year] = array(
                  'year' => $year,
                  'store' => $store,
                  'is_current' => false
                );
              }
              $history_years[] = $year;
            }
            
            // Sort by year descending (most recent first)
            krsort($history_stores);
            
            // Get evaluation data for the selected period
            if (isset($_GET['sp']) && isset($_GET['ep'])) {
              $sPeriod = $_GET['sp'];
              $ePeriod = $_GET['ep'];
              
              // Simple query - NO store filtering needed!
              $sqlev = "SELECT * FROM evaluation WHERE emp_id = '$emp_id' AND period BETWEEN '$sPeriod' AND '$ePeriod'";
              $sqlc = "SELECT COUNT(DISTINCT period) AS 'count' FROM evaluation WHERE emp_id = '$emp_id' AND period BETWEEN '$sPeriod' AND '$ePeriod'";
            } else {
              $year = date('Y');
              
              // Simple query - get ALL evaluations for this year from ANY store
              $sqlev = "SELECT * FROM evaluation WHERE emp_id = '$emp_id' AND period LIKE '$year%'";
              $sqlc = "SELECT COUNT(DISTINCT period) AS 'count' FROM evaluation WHERE emp_id = '$emp_id' AND period LIKE '$year%'";
            }
            
            $resultev = $conn->query($sqlev);
            $resultc = $conn->query($sqlc);
            $rowc = $resultc->fetch_assoc();
            $count = $rowc['count'];
            $rate = null;
            
            // Track which stores are used in this period (for display)
            $stores_in_period = array();
            
            while ($rowev = $resultev->fetch_assoc()) {
              // Track store for display
              if (!in_array($rowev['store'], $stores_in_period)) {
                $stores_in_period[] = $rowev['store'];
              }
              
              $attrId = $rowev['attribute_id'];
              
              // Get the year from the evaluation period
              $evalPeriod = $rowev['period'];
              $evalYear = date('Y', strtotime($evalPeriod));
              
              // ========== FIXED: Get scoring method from history table ==========
              // Create cache key using attribute_id and year
              $cacheKey = $attrId . '_' . $evalYear;
              
              // Check if scoring method is in cache
              if (!isset($scoringMethodCache[$cacheKey])) {
                // FIRST: Try to get scoring method from history table
                // This tells us which scoring method was ACTUALLY used for this attribute in that specific year
                $sqlHistory = "SELECT ash.scoring_method_id, sm.* 
                               FROM attribute_scoring_history ash
                               INNER JOIN scoring_method sm ON ash.scoring_method_id = sm.sm_id
                               WHERE ash.attribute_id = '$attrId' 
                               AND ash.year = '$evalYear'";
                $resultHistory = $conn->query($sqlHistory);
                
                if ($resultHistory && $resultHistory->num_rows > 0) {
                  // Found the correct historical scoring method
                  $scoringMethodCache[$cacheKey] = $resultHistory->fetch_assoc();
                } else {
                  // FALLBACK: If no history exists, get attribute's current scoring method
                  // and try to find it for that year (for legacy data)
                  $sqlat = "SELECT scoring_method, weightage FROM attribute WHERE attribute_id = '$attrId'";
                  $resultat = $conn->query($sqlat);
                  if ($resultat && $resultat->num_rows > 0) {
                    $rowat_fallback = $resultat->fetch_assoc();
                    $sm = $rowat_fallback['scoring_method'];
                    
                    // Try to get scoring method for this specific year
                    $sqlsm = "SELECT * FROM scoring_method WHERE sm_name = '$sm' AND year = '$evalYear'";
                    $resultsm = $conn->query($sqlsm);
                    
                    if ($resultsm && $resultsm->num_rows > 0) {
                      $scoringMethodCache[$cacheKey] = $resultsm->fetch_assoc();
                    } else {
                      // Last resort: get any version (but this will cause historical inaccuracy)
                      $sqlsm = "SELECT * FROM scoring_method WHERE sm_name = '$sm' ORDER BY year DESC LIMIT 1";
                      $resultsm = $conn->query($sqlsm);
                      
                      if ($resultsm && $resultsm->num_rows > 0) {
                        $scoringMethodCache[$cacheKey] = $resultsm->fetch_assoc();
                      } else {
                        $scoringMethodCache[$cacheKey] = null;
                      }
                    }
                  } else {
                    $scoringMethodCache[$cacheKey] = null;
                  }
                }
              }
              
              $rowsm = $scoringMethodCache[$cacheKey];
              
              // Skip if no scoring method found
              if (!$rowsm) {
                continue;
              }
              
              // Now get the attribute weightage (needed for calculation)
              $sqlat = "SELECT weightage FROM attribute WHERE attribute_id = '$attrId'";
              $resultat = $conn->query($sqlat);
              $rowat = $resultat->fetch_assoc();
              // ========== END OF FIXED PART ==========

              // Rating calculation logic
              if (isset($rate)) {
                if (isset($rowev['status']) && $rowev['status'] < 0) {
                  if ($rowev['status'] == -1) {
                    $rate = ($rate + $rowat['weightage'] * 3 / 5);
                  } else if ($rowev['status'] == -2) {
                    $rate = $rate;
                  }
                } else if (isset($rowev['value'])) {
                  if (isset($rowsm['5_left']) && isset($rowsm['5_right']) && isset($rowsm['0_left']) && isset($rowsm['0_right'])) {
                    if ($rowev['value'] <= $rowsm['5_left']) {
                      $rate = ($rate + $rowat['weightage']);
                    } else if ($rowev['value'] <= $rowsm['4_left']) {
                      $rate = ($rate + $rowat['weightage'] * 4 / 5);
                    } else if ($rowev['value'] <= $rowsm['3_left']) {
                      $rate = ($rate + $rowat['weightage'] * 3 / 5);
                    } else if ($rowev['value'] <= $rowsm['2_left']) {
                      $rate = ($rate + $rowat['weightage'] * 2 / 5);
                    } else if ($rowev['value'] <= $rowsm['1_left']) {
                      $rate = ($rate + $rowat['weightage'] / 5);
                    } else if ($rowev['value'] <= $rowsm['0_left']) {
                      $rate = ($rate);
                    }
                  } else if (isset($rowsm['5_left']) && isset($rowsm['0_right'])) {
                    if ($rowev['value'] <= $rowsm['5_left']) {
                      $rate = ($rate + $rowat['weightage']);
                    } else if ($rowev['value'] <= $rowsm['4_left']) {
                      $rate = ($rate + $rowat['weightage'] * 4 / 5);
                    } else if ($rowev['value'] <= $rowsm['3_left']) {
                      $rate = ($rate + $rowat['weightage'] * 3 / 5);
                    } else if ($rowev['value'] <= $rowsm['2_left']) {
                      $rate = ($rate + $rowat['weightage'] * 2 / 5);
                    } else if ($rowev['value'] <= $rowsm['1_left']) {
                      $rate = ($rate + $rowat['weightage'] / 5);
                    } else if ($rowev['value'] >= $rowsm['0_right']) {
                      $rate = ($rate);
                    }
                  } else if (isset($rowsm['5_right']) && isset($rowsm['0_left'])) {
                    if ($rowev['value'] >= $rowsm['5_right']) {
                      $rate = ($rate + $rowat['weightage']);
                    } else if ($rowev['value'] >= $rowsm['4_right']) {
                      $rate = ($rate + $rowat['weightage'] * 4 / 5);
                    } else if ($rowev['value'] >= $rowsm['3_right']) {
                      $rate = ($rate + $rowat['weightage'] * 3 / 5);
                    } else if ($rowev['value'] >= $rowsm['2_right']) {
                      $rate = ($rate + $rowat['weightage'] * 2 / 5);
                    } else if ($rowev['value'] >= $rowsm['1_right']) {
                      $rate = ($rate + $rowat['weightage'] / 5);
                    } else if ($rowev['value'] <= $rowsm['0_left']) {
                      $rate = ($rate);
                    }
                  }
                } else {
                  $negative = (int)$rowev['negative'];
                  $positive = (int)$rowev['positive'];

                  // SPECIAL LOGIC ONLY FOR ATTRIBUTE 32 IN 2026
                  if ($attrId == 32 && $evalYear == 2026) {
                    // Score 0
                    if ($negative >= 3 && $positive == 0) {
                      $rate = ($rate + 0);
                    }
                    // Score 1
                    else if ($negative >= 2 && $positive == 0) {
                      $rate = ($rate + $rowat['weightage'] * 1 / 5);
                    }
                    // Score 2
                    else if ($negative >= 1 && $positive == 0) {
                      $rate = ($rate + $rowat['weightage'] * 2 / 5);
                    }
                    // Score 5
                    else if ($negative == 0 && $positive > 10) {
                      $rate = ($rate + $rowat['weightage']);
                    }
                    // Score 4
                    else if ($negative == 0 && $positive > 1) {
                      $rate = ($rate + $rowat['weightage'] * 4 / 5);
                    }
                    // Score 3
                    else if ($negative == 0 && $positive >= 0) {
                      $rate = ($rate + $rowat['weightage'] * 3 / 5);
                    }
                    else {
                      $rate = ($rate);
                    }
                  } else {
                    // OLD GENERAL LOGIC
                    if (($rowev['negative'] == $rowsm['5_left'] && $rowev['positive'] >= $rowsm['5_right'])) {
                      $rate = ($rate + $rowat['weightage']);
                    } else if (($rowev['negative'] == $rowsm['4_left'] && $rowev['positive'] == $rowsm['4_right']) || ($rowev['negative'] == $rowsm['4_left'] + 1 && $rowev['positive'] >= $rowsm['4_right'] + 2)) {
                      $rate = ($rate + $rowat['weightage'] * 4 / 5);
                    } else if (($rowev['negative'] == $rowsm['3_left'] && $rowev['positive'] == $rowsm['3_right']) || ($rowev['negative'] == $rowsm['3_left'] + 1 && $rowev['positive'] >= $rowsm['3_right'] + 1)) {
                      $rate = ($rate + $rowat['weightage'] * 3 / 5);
                    } else if (($rowev['negative'] == $rowsm['2_left'] && $rowev['positive'] == $rowsm['2_right']) || ($rowev['negative'] == $rowsm['2_left'] + 1 && $rowev['positive'] >= $rowsm['2_right'] + 1)) {
                      $rate = ($rate + $rowat['weightage'] * 2 / 5);
                    } else if (($rowev['negative'] == $rowsm['1_left'] && $rowev['positive'] == $rowsm['1_right']) || ($rowev['negative'] == $rowsm['1_left'] + 1 && $rowev['positive'] >= $rowsm['1_right'] + 1)) {
                      $rate = ($rate + $rowat['weightage'] * 1 / 5);
                    } else if (($rowev['negative'] >= $rowsm['0_left'] && $rowev['positive'] >= $rowsm['0_right'])) {
                      $rate = ($rate);
                    }
                  }
                }
              } else {
                // Initial rate calculation
                if (isset($rowev['status']) && $rowev['status'] < 0) {
                  if ($rowev['status'] == -1) {
                    $rate = $rowat['weightage'] * 3 / 5;
                  } else if ($rowev['status'] == -2) {
                    $rate = 0;
                  }
                } else if (isset($rowev['value'])) {
                  if (isset($rowsm['5_left']) && isset($rowsm['5_right']) && isset($rowsm['0_left']) && isset($rowsm['0_right'])) {
                    if ($rowev['value'] <= $rowsm['5_left']) {
                      $rate = $rowat['weightage'];
                    } else if ($rowev['value'] <= $rowsm['4_left']) {
                      $rate = $rowat['weightage'] * 4 / 5;
                    } else if ($rowev['value'] <= $rowsm['3_left']) {
                      $rate = $rowat['weightage'] * 3 / 5;
                    } else if ($rowev['value'] <= $rowsm['2_left']) {
                      $rate = $rowat['weightage'] * 2 / 5;
                    } else if ($rowev['value'] <= $rowsm['1_left']) {
                      $rate = $rowat['weightage'] / 5;
                    } else if ($rowev['value'] <= $rowsm['0_left']) {
                      $rate = 0;
                    }
                  } else if (isset($rowsm['5_left']) && isset($rowsm['0_right'])) {
                    if ($rowev['value'] <= $rowsm['5_left']) {
                      $rate = $rowat['weightage'];
                    } else if ($rowev['value'] <= $rowsm['4_left']) {
                      $rate = $rowat['weightage'] * 4 / 5;
                    } else if ($rowev['value'] <= $rowsm['3_left']) {
                      $rate = $rowat['weightage'] * 3 / 5;
                    } else if ($rowev['value'] <= $rowsm['2_left']) {
                      $rate = $rowat['weightage'] * 2 / 5;
                    } else if ($rowev['value'] <= $rowsm['1_left']) {
                      $rate = $rowat['weightage'] / 5;
                    } else if ($rowev['value'] >= $rowsm['0_right']) {
                      $rate = 0;
                    }
                  } else if (isset($rowsm['5_right']) && isset($rowsm['0_left'])) {
                    if ($rowev['value'] >= $rowsm['5_right']) {
                      $rate = $rowat['weightage'];
                    } else if ($rowev['value'] >= $rowsm['4_right']) {
                      $rate = $rowat['weightage'] * 4 / 5;
                    } else if ($rowev['value'] >= $rowsm['3_right']) {
                      $rate = $rowat['weightage'] * 3 / 5;
                    } else if ($rowev['value'] >= $rowsm['2_right']) {
                      $rate = $rowat['weightage'] * 2 / 5;
                    } else if ($rowev['value'] >= $rowsm['1_right']) {
                      $rate = $rowat['weightage'] / 5;
                    } else if ($rowev['value'] <= $rowsm['0_left']) {
                      $rate = 0;
                    }
                  }
                } else {
                  $negative = (int)$rowev['negative'];
                  $positive = (int)$rowev['positive'];

                  // SPECIAL LOGIC ONLY FOR ATTRIBUTE 32 IN 2026
                  if ($attrId == 32 && $evalYear == 2026) {
                    // Score 0
                    if ($negative >= 3 && $positive == 0) {
                      $rate = 0;
                    }
                    // Score 1
                    else if ($negative >= 2 && $positive == 0) {
                      $rate = $rowat['weightage'] * 1 / 5;
                    }
                    // Score 2
                    else if ($negative >= 1 && $positive == 0) {
                      $rate = $rowat['weightage'] * 2 / 5;
                    }
                    // Score 5
                    else if ($negative == 0 && $positive > 10) {
                      $rate = $rowat['weightage'];
                    }
                    // Score 4
                    else if ($negative == 0 && $positive > 1) {
                      $rate = $rowat['weightage'] * 4 / 5;
                    }
                    // Score 3
                    else if ($negative == 0 && $positive >= 0) {
                      $rate = $rowat['weightage'] * 3 / 5;
                    }
                    else {
                      $rate = 0;
                    }
                  } else {
                    // OLD GENERAL LOGIC
                    if (($rowev['negative'] == $rowsm['5_left'] && $rowev['positive'] >= $rowsm['5_right'])) {
                      $rate = $rowat['weightage'];
                    } else if (($rowev['negative'] == $rowsm['4_left'] && $rowev['positive'] == $rowsm['4_right']) || ($rowev['negative'] == $rowsm['4_left'] + 1 && $rowev['positive'] >= $rowsm['4_right'] + 2)) {
                      $rate = $rowat['weightage'] * 4 / 5;
                    } else if (($rowev['negative'] == $rowsm['3_left'] && $rowev['positive'] == $rowsm['3_right']) || ($rowev['negative'] == $rowsm['3_left'] + 1 && $rowev['positive'] >= $rowsm['3_right'] + 1)) {
                      $rate = $rowat['weightage'] * 3 / 5;
                    } else if (($rowev['negative'] == $rowsm['2_left'] && $rowev['positive'] == $rowsm['2_right']) || ($rowev['negative'] == $rowsm['2_left'] + 1 && $rowev['positive'] >= $rowsm['2_right'] + 1)) {
                      $rate = $rowat['weightage'] * 2 / 5;
                    } else if (($rowev['negative'] == $rowsm['1_left'] && $rowev['positive'] == $rowsm['1_right']) || ($rowev['negative'] == $rowsm['1_left'] + 1 && $rowev['positive'] >= $rowsm['1_right'] + 1)) {
                      $rate = $rowat['weightage'] * 1 / 5;
                    } else if (($rowev['negative'] >= $rowsm['0_left'] && $rowev['positive'] >= $rowsm['0_right'])) {
                      $rate = 0;
                    }
                  }
                }
              }
            }
            
            // Calculate final rating
            if ($count != 0 && isset($rate)) {
              $finalRating = number_format($rate / $count, 2);
            } else {
              $finalRating = "0.00";
            }
            
            // Check if multiple stores in period
            $multiple_stores = count($stores_in_period) > 1;
        ?>
          <div class="col-xl-3 col-lg-4 col-md-6">
            <?php if (isset($_GET['sp']) && isset($_GET['ep'])) {
              $sp = $_GET['sp'];
              $ep = $_GET['ep'];
            ?>
              <a href="./rating.php?id=<?php echo $row['emp_id'] ?>&sp=<?php echo $sp ?>&ep=<?php echo $ep ?>" class="text-decoration-none">
            <?php } else { ?>
              <a href="./rating.php?id=<?php echo $row['emp_id'] ?>" class="text-decoration-none">
            <?php } ?>
              <div class="employee-card-split <?php if ($_SESSION['isAdmin']) {
                                                echo "Admin";
                                              } else if ($_SESSION["isEvaluator"]) {
                                                echo "Evaluator";
                                              } else {
                                                echo "Guest";
                                              } ?>">
                
                <!-- Top Blue Section -->
                <div class="card-top-section">
                  <?php if (isset($_GET['id']) && ($_SESSION["isAdmin"] || $_SESSION["isEvaluator"])) { ?>
                    <a href="./step2.php?id=<?php echo $row['emp_id'] ?>" class="evaluate-btn-split">
                      <i class='bx bx-bar-chart-alt-2'></i>
                      <span>Evaluate</span>
                    </a>
                  <?php } ?>
                  
                  <!-- Employee Avatar -->
                  <div class="employee-avatar-top">
                    <i class='bx bxs-user'></i>
                  </div>
                  
                  <!-- Employee Name -->
                  <h3 class="employee-name-top"><?php echo $row['emp_name'] ?></h3>
                  
                  <!-- Employee ID -->
                  <div class="employee-id-top">ID: <?php echo $row['emp_id'] ?></div>
                  
                  <!-- Gender -->
                  <div class="employee-gender-top"><?php echo $row['gender'] ?></div>
                  
                  <!-- Show inactive notice if employee is currently inactive but has historical data -->
                  <?php if (!$isCurrentlyActive && $count > 0): ?>
                    <div class="inactive-notice">
                      <i class='bx bx-info-circle'></i>
                      <span>Inactive in <?php echo date('Y'); ?> - Showing historical data</span>
                    </div>
                  <?php elseif (!$isCurrentlyActive && $count == 0): ?>
                    <div class="inactive-notice">
                      <i class='bx bx-user-x'></i>
                      <span>Currently Inactive</span>
                    </div>
                  <?php endif; ?>
                </div>
                
                <!-- Bottom White Section -->
                <div class="card-bottom-section">
                  <!-- Current Details -->
                  <div class="current-details-split">
                    <div class="details-label-split">
                      <i class='bx bx-store-alt'></i>
                      <span>Current Assignment</span>
                    </div>
                    
                    <div class="store-info">
                      <div class="info-item">
                        <div class="info-value">
                          <?php echo !empty($row['current_store']) ? $row['current_store'] : 'N/A'; ?>
                          <?php if ($multiple_stores): ?>
                            <span class="multi-store-badge" title="Worked in multiple stores this period: <?php echo implode(', ', $stores_in_period); ?>">
                              <i class='bx bx-store-alt'></i>
                            </span>
                          <?php endif; ?>
                        </div>
                        <div class="info-label">Store No</div>
                      </div>
                      
                      <div class="info-item">
                        <div class="info-value"><?php echo !empty($row['current_category']) ? $row['current_category'] : 'N/A'; ?></div>
                        <div class="info-label">Category</div>
                      </div>
                    </div>
                  </div>
                  
                  <!-- Historical Store Information - BOTH CURRENT AND PREVIOUS -->
                  <div class="history-container">
                    <div class="details-label-split">
                      <i class='bx bx-history'></i>
                      <span>Store History</span>
                    </div>
                    
                    <div class="history-list">
                      <?php 
                      $history_count = 0;
                      foreach ($history_stores as $year => $store_data): 
                        $history_count++;
                        // Only show last 5 years
                        if ($history_count <= 5): 
                      ?>
                        <div class="history-item <?php echo $store_data['is_current'] ? 'current' : 'previous'; ?>" 
                             <?php if ($store_data['is_current']): ?>title="Current Store"<?php endif; ?>>
                          <i class='bx bx-calendar history-icon'></i>
                          <span class="history-year"><?php echo $year ?>:</span>
                          <span class="history-store">Store <?php echo $store_data['store'] ?></span>
                          <?php if ($store_data['is_current']): ?>
                            <i class='bx bx-check-circle ms-1' style="font-size: 0.8rem;"></i>
                          <?php endif; ?>
                        </div>
                      <?php 
                        endif;
                      endforeach; 
                      ?>
                      
                      <?php if ($history_count > 5): ?>
                        <div class="history-item" title="<?php 
                          $more_stores = array_slice($history_stores, 5, null, true);
                          $more_text = [];
                          foreach ($more_stores as $y => $s) {
                            $more_text[] = $y . ': Store ' . $s['store'];
                          }
                          echo implode(', ', $more_text);
                        ?>">
                          <i class='bx bx-dots-horizontal-rounded'></i>
                          <span>+<?php echo $history_count - 5 ?> more years</span>
                        </div>
                      <?php endif; ?>
                    </div>
                  </div>
                  
                  <!-- Show which stores are included in this period -->
                  <?php if ($multiple_stores): ?>
                    <div class="alert alert-info py-2 px-3 mb-3 text-center" style="font-size: 0.9rem;">
                      <i class='bx bx-store'></i>
                      <strong>Includes evaluations from: <?php echo implode(', ', $stores_in_period); ?></strong>
                    </div>
                  <?php endif; ?>
                  
                  <!-- Year's Average Rating -->
                  <?php if (!isset($_GET['id'])) { ?>
                    <div class="rating-section-split">
                      <div class="rating-label-split">
                        <i class='bx bx-trophy'></i>
                        <span>Average Rating</span>
                      </div>
                      <div class="rating-value-split"><?php echo $finalRating ?></div>
                      <div class="rating-subtext-split">Earning Score</div>
                    </div>
                  <?php } ?>
                  
                  <!-- View Details Button -->
                  <div class="view-details-btn">
                    <i class='bx bx-chevron-right'></i>
                  </div>
                </div>
              </div>
            </a>
          </div>
        <?php 
          }
        } else { 
        ?>
          <div class="col-12">
            <div class="empty-state">
              <div class="empty-state-icon">
                <i class='bx bx-user-x'></i>
              </div>
              <h3 class="empty-state-title">No Employees Found</h3>
              <p class="empty-state-text">There are no employees available in the system for the selected period.</p>
            </div>
          </div>
        <?php } ?>
      </div>
    </div>
    
    <?php if (!isset($_GET['id'])) { ?>
      <div class="floating-container">
        <div class="floating-button-modern <?php if ($_SESSION['isAdmin']) {
                                            echo "Admin";
                                          } else if ($_SESSION["isEvaluator"]) {
                                            echo "Evaluator";
                                          } else {
                                            echo "Guest";
                                          } ?>">
          <?php if (isset($_GET['sp']) && isset($_GET['ep'])) {
            $sp = $_GET['sp'];
            $ep = $_GET['ep'];
          ?>
            <a href="./Rankings.php?sp=<?php echo $sp ?>&ep=<?php echo $ep ?>"><i class='bx bx-crown'></i></a>
          <?php } else { ?>
            <a href="./Rankings.php"><i class='bx bx-crown'></i></a>
          <?php } ?>
        </div>
      </div>
    <?php } ?>

    <script>
      // Add hover effect and animation
      document.addEventListener('DOMContentLoaded', function() {
        const employeeCards = document.querySelectorAll('.employee-card-split');
        
        employeeCards.forEach(card => {
          // Add click effect
          card.addEventListener('click', function(e) {
            this.style.transform = 'scale(0.98)';
            setTimeout(() => {
              this.style.transform = '';
            }, 150);
          });
        });
      });
    </script>
  </body>

  </html>

<?php } else {
  header("location: signin.php");
} ?>