<?php
include "config.php";
session_start();

if (isset($_SESSION["user"])) {

  // Handle system freeze/unfreeze
  if (isset($_POST['toggle_freeze'])) {
    $new_status = $_POST['freeze_status'] == '1' ? '0' : '1';
    $sql = "UPDATE system_settings SET evaluation_frozen = '$new_status' WHERE id = 1";
    mysqli_query($conn, $sql);
    header("Location: index.php");
    exit();
  }
  
  // Check if system is frozen
  $freeze_query = "SELECT evaluation_frozen FROM system_settings WHERE id = 1";
  $freeze_result = mysqli_query($conn, $freeze_query);
  if ($freeze_result && mysqli_num_rows($freeze_result) > 0) {
    $freeze_data = mysqli_fetch_assoc($freeze_result);
    $is_frozen = $freeze_data['evaluation_frozen'];
  } else {
    // Create default settings if not exists
    $create_sql = "INSERT INTO system_settings (id, evaluation_frozen) VALUES (1, '0')";
    mysqli_query($conn, $create_sql);
    $is_frozen = '0';
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
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard</title>
  <link rel="icon" type="image/x-icon" href="./img/jb.png">

  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta1/dist/css/bootstrap.min.css" rel="stylesheet" />
  <!-- Bootstrap JS Bundle -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta1/dist/js/bootstrap.bundle.min.js"></script>
  <!-- jQuery -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
  <!-- Boxicons -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/boxicons@latest/css/boxicons.min.css" />
  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />

  <link rel="stylesheet" href="./style.css" />
  <script src="./script.js"></script>
  
  <style>
    :root {
      --primary-color: #2563eb;
      --primary-light: #3b82f6;
      --secondary-color: #7c3aed;
      --success-color: #059669;
      --info-color: #0891b2;
      --warning-color: #d97706;
      --danger-color: #dc2626;
      --dark-color: #1e293b;
      --light-color: #f8fafc;
      --record-color: #f97316; /* Orange color for Records */
      
      --glass-bg: rgba(255, 255, 255, 0.85);
      --glass-border: rgba(255, 255, 255, 0.18);
      --glass-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.15);
      --hover-shadow: 0 12px 40px 0 rgba(31, 38, 135, 0.25);
      --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
      
      --freeze-color: #ef4444;
      --unfreeze-color: #10b981;
    }
    
    body {
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      min-height: 100vh;
      overflow-x: hidden;
      color: #334155;
    }
    
    .dashboard-card {
      background: var(--glass-bg);
      backdrop-filter: blur(20px);
      -webkit-backdrop-filter: blur(20px);
      border-radius: 16px;
      border: 1px solid var(--glass-border);
      box-shadow: var(--glass-shadow);
      transition: var(--transition);
      overflow: hidden;
      height: 100%;
      position: relative;
    }
    
    .dashboard-card::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      height: 4px;
      z-index: 1;
    }
    
    .dashboard-card:hover {
      transform: translateY(-8px);
      box-shadow: var(--hover-shadow);
    }
    
    .header {
      background: linear-gradient(135deg, #807878ff 0%, #d5c9c9ff 100%) !important;
      color: white !important;
      box-shadow: 0 2px 15px rgba(0, 0, 0, 0.2);
      border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }

    .header h5 {
      color: white !important;
      text-shadow: 0 1px 2px rgba(0, 0, 0, 0.2);
    }
     .header i {
      color: white !important;
    }
    
    .dashboard-card .card-icon {
      width: 70px;
      height: 70px;
      border-radius: 16px;
      display: flex;
      align-items: center;
      justify-content: center;
      margin-bottom: 20px;
      font-size: 28px;
      color: white;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }
    
    .dashboard-card .card-title {
      font-weight: 700;
      font-size: 1.4rem;
      margin-bottom: 12px;
      color: var(--dark-color);
    }
    
    .dashboard-card .card-description {
      color: #64748b;
      font-size: 0.95rem;
      line-height: 1.6;
      margin-bottom: 30px;
    }
    
    .dashboard-card .card-link {
      position: absolute;
      bottom: 0;
      left: 0;
      right: 0;
      padding: 16px 20px;
      background: rgba(37, 99, 235, 0.05);
      color: var(--primary-color);
      font-weight: 600;
      text-decoration: none;
      transition: var(--transition);
      display: flex;
      align-items: center;
      justify-content: space-between;
      border-top: 1px solid rgba(0, 0, 0, 0.05);
    }
    
    .dashboard-card:hover .card-link {
      background: rgba(37, 99, 235, 0.1);
    }
    
    .dashboard-card .card-link i {
      transition: var(--transition);
    }
    
    .dashboard-card:hover .card-link i {
      transform: translateX(6px);
    }
    
    .card-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
      gap: 25px;
      padding: 20px 0;
    }
    
    .welcome-section {
      background: var(--glass-bg);
      backdrop-filter: blur(20px);
      -webkit-backdrop-filter: blur(20px);
      border-radius: 16px;
      border: 1px solid var(--glass-border);
      padding: 30px;
      margin-bottom: 35px;
      box-shadow: var(--glass-shadow);
      position: relative;
      overflow: hidden;
    }
    
    .welcome-section::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
      opacity: 0.1;
      z-index: -1;
    }
    
    .welcome-section h2 {
      font-weight: 700;
      margin-bottom: 8px;
      color: var(--dark-color);
    }
    
    .welcome-section p {
      color: #64748b;
      margin-bottom: 0;
      font-size: 1.1rem;
    }
    
    /* Individual card colors */
    .card-1::before { background: var(--primary-color); }
    .card-1 .card-icon { background: var(--primary-color); }
    
    .card-2::before { background: var(--secondary-color); }
    .card-2 .card-icon { background: var(--secondary-color); }
    
    .card-3::before { background: var(--success-color); }
    .card-3 .card-icon { background: var(--success-color); }
    
    .card-4::before { background: var(--info-color); }
    .card-4 .card-icon { background: var(--info-color); }
    
    .card-5::before { background: var(--warning-color); }
    .card-5 .card-icon { background: var(--warning-color); }
    
    .card-6::before { background: var(--danger-color); }
    .card-6 .card-icon { background: var(--danger-color); }
    
    .card-7::before { background: #7e22ce; }
    .card-7 .card-icon { background: #7e22ce; }
    
    .card-8::before { background: #0d9488; }
    .card-8 .card-icon { background: #0d9488; }
    
    /* NEW: Card 9 for Records */
    .card-9::before { background: var(--record-color); }
    .card-9 .card-icon { background: var(--record-color); }
    
    /* Role-based styling */
    .Admin .welcome-section::before { background: linear-gradient(135deg, var(--primary-color), var(--secondary-color)); }
    .Evaluator .welcome-section::before { background: linear-gradient(135deg, var(--success-color), var(--info-color)); }
    .Guest .welcome-section::before { background: linear-gradient(135deg, var(--warning-color), var(--danger-color)); }
    
    /* Freeze/Unfreeze button */
    .freeze-btn-container {
      display: flex;
      justify-content: flex-end;
      margin-bottom: 20px;
    }
    
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
      background-color: var(--freeze-color);
      color: white;
    }
    
    .freeze-btn.unfrozen {
      background-color: var(--unfreeze-color);
      color: white;
    }
    
    .freeze-btn:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 16px rgba(0, 0, 0, 0.15);
    }
    
    .freeze-btn i {
      font-size: 16px;
    }
    
    /* Disabled/frozen state */
    .card-frozen {
      position: relative;
      opacity: 0.6;
      cursor: not-allowed !important;
    }
    
    .card-frozen:hover {
      transform: none !important;
    }
    
    .card-frozen::after {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: rgba(255, 255, 255, 0.7);
      z-index: 2;
      border-radius: 16px;
    }
    
    .frozen-overlay {
      position: absolute;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%);
      background: var(--freeze-color);
      color: white;
      padding: 8px 16px;
      border-radius: 20px;
      font-weight: 600;
      font-size: 12px;
      z-index: 3;
      display: flex;
      align-items: center;
      gap: 6px;
      box-shadow: 0 4px 8px rgba(239, 68, 68, 0.3);
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
    
    /* Responsive adjustments */
    @media (max-width: 768px) {
      .card-grid {
        grid-template-columns: 1fr;
      }
      
      .welcome-section {
        padding: 20px;
      }
      
      .freeze-btn-container {
        justify-content: center;
      }
    }
    
    /* Subtle hover animation */
    @keyframes subtle-float {
      0% { transform: translateY(0px); }
      50% { transform: translateY(-5px); }
      100% { transform: translateY(0px); }
    }
    
    .dashboard-card {
      animation: subtle-float 8s ease-in-out infinite;
    }
    
    .dashboard-card:nth-child(2) { animation-delay: 0.2s; }
    .dashboard-card:nth-child(3) { animation-delay: 0.4s; }
    .dashboard-card:nth-child(4) { animation-delay: 0.6s; }
    .dashboard-card:nth-child(5) { animation-delay: 0.8s; }
    .dashboard-card:nth-child(6) { animation-delay: 1s; }
    .dashboard-card:nth-child(7) { animation-delay: 1.2s; }
    .dashboard-card:nth-child(8) { animation-delay: 1.4s; }
    .dashboard-card:nth-child(9) { animation-delay: 1.6s; } /* NEW: Added for Records card */
    
    /* Custom scrollbar */
    ::-webkit-scrollbar {
      width: 6px;
    }
    
    ::-webkit-scrollbar-track {
      background: rgba(0, 0, 0, 0.05);
      border-radius: 10px;
    }
    
    ::-webkit-scrollbar-thumb {
      background: var(--primary-color);
      border-radius: 10px;
    }
    
    ::-webkit-scrollbar-thumb:hover {
      background: var(--primary-light);
    }
  </style>
  
  <script>
    function back() {
      history.back();
    }

    function forward() {
      history.forward();
    }
    
    function toggleFreezeSystem() {
      const currentStatus = <?php echo $is_frozen; ?>;
      const actionName = currentStatus == '1' ? 'Unfreeze' : 'Freeze';
      
      if(confirm(`Are you sure you want to ${actionName} the evaluation system?`)) {
        // Create a form and submit it
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '';
        
        const freezeInput = document.createElement('input');
        freezeInput.type = 'hidden';
        freezeInput.name = 'freeze_status';
        freezeInput.value = currentStatus;
        
        const toggleInput = document.createElement('input');
        toggleInput.type = 'hidden';
        toggleInput.name = 'toggle_freeze';
        toggleInput.value = '1';
        
        form.appendChild(freezeInput);
        form.appendChild(toggleInput);
        document.body.appendChild(form);
        form.submit();
      }
    }
    
    function checkIfFrozen(event, isFrozen) {
      if(isFrozen == '1') {
        event.preventDefault();
        event.stopPropagation();
        alert('Evaluation system is currently frozen. Please contact administrator.');
        return false;
      }
      return true;
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
    <div class="header_toggle"> <i class='bx bx-menu' id="header-toggle"></i>
    </div>
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
          <!-- NEW: Records link in navigation -->
          <a href="./records.php" class="nav_link"> <i class='bx bx-history nav_icon'></i> <span class="nav_name">Records</span> </a>
        </div>
      </div>
      <div>
        <?php if ($_SESSION['isAdmin']): ?>
         
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

  <div class="height-100 container mt-5 mb-5" style="padding-top: 2vh;">
    <!-- System Status Alert -->
    <?php if ($is_frozen == '1'): ?>
      <div class="alert alert-warning alert-dismissible fade show" role="alert">
        <i class='bx bx-lock me-2'></i>
        <strong>System Frozen!</strong> Evaluation features are currently disabled. Contact administrator to unfreeze.
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    <?php endif; ?>
    
    <!-- Admin Control Panel -->
    <?php if ($_SESSION['isAdmin']): ?>
      <div class="freeze-btn-container">
        <form method="POST" action="" style="margin: 0;">
          <input type="hidden" name="freeze_status" value="<?php echo $is_frozen; ?>">
          <button type="submit" name="toggle_freeze" class="freeze-btn <?php echo $is_frozen == '1' ? 'frozen' : 'unfrozen'; ?>">
            <i class='bx <?php echo $is_frozen == '1' ? 'bx-lock-open' : 'bx-lock'; ?>'></i>
            <?php echo $is_frozen == '1' ? 'Evaluation System Frozen' : 'Evaluation System Active'; ?>
          </button>
        </form>
      </div>
    <?php endif; ?>
    
    <!-- Welcome Section -->
    <div class="welcome-section">
      <div class="row align-items-center">
        <div class="col-md-8">
          <h2>Welcome back, <?php echo $_SESSION["user"]; ?>!</h2>
          <p>Manage your employee evaluation system efficiently with our modern dashboard</p>
          <?php if ($is_frozen == '1'): ?>
            <div class="mt-3">
              <span class="badge bg-danger px-3 py-2">
                <i class='bx bx-lock me-1'></i> System Frozen
              </span>
            </div>
          <?php endif; ?>
        </div>
        <div class="col-md-4 text-end">
          <i class='bx bxs-dashboard' style="font-size: 70px; color: var(--primary-color); opacity: 0.7;"></i>
        </div>
      </div>
    </div>
    
    <!-- Dashboard Cards -->
    <div class="card-grid">
      <a href="./Categories.php" class="text-decoration-none">
        <div class="dashboard-card p-4 card-1">
          <div class="card-icon">
            <i class='bx bx-category'></i>
          </div>
          <h3 class="card-title">Categories</h3>
          <p class="card-description">Add, edit, and delete evaluation categories to organize your assessment criteria.</p>
          <div class="card-link">
            <span>Manage Categories</span>
            <i class='bx bx-chevron-right'></i>
          </div>
        </div>
      </a>
      
      <a href="./AttributeCategories.php" class="text-decoration-none">
        <div class="dashboard-card p-4 card-2">
          <div class="card-icon">
            <i class='bx bx-spreadsheet'></i>
          </div>
          <h3 class="card-title">Attributes</h3>
          <p class="card-description">Define and manage the specific attributes used for employee evaluation.</p>
          <div class="card-link">
            <span>Manage Attributes</span>
            <i class='bx bx-chevron-right'></i>
          </div>
        </div>
      </a>
      
      <a href="./ScoringMethods.php" class="text-decoration-none">
        <div class="dashboard-card p-4 card-3">
          <div class="card-icon">
            <i class='bx bx-tachometer'></i>
          </div>
          <h3 class="card-title">Marking Schemes</h3>
          <p class="card-description">Configure scoring methods and evaluation criteria for consistent assessments.</p>
          <div class="card-link">
            <span>Manage Schemes</span>
            <i class='bx bx-chevron-right'></i>
          </div>
        </div>
      </a>
      
      <a href="./Evaluators.php" class="text-decoration-none">
        <div class="dashboard-card p-4 card-4">
          <div class="card-icon">
            <i class='bx bxs-user-detail'></i>
          </div>
          <h3 class="card-title">Evaluators</h3>
          <p class="card-description">Manage users who have permission to evaluate employees in the system.</p>
          <div class="card-link">
            <span>Manage Evaluators</span>
            <i class='bx bx-chevron-right'></i>
          </div>
        </div>
      </a>
      
      <a href="./Employees.php" class="text-decoration-none">
        <div class="dashboard-card p-4 card-5">
          <div class="card-icon">
            <i class='bx bx-user'></i>
          </div>
          <h3 class="card-title">Evaluatees</h3>
          <p class="card-description">Add, edit, and manage employees who will be evaluated in the system.</p>
          <div class="card-link">
            <span>Manage Evaluatees</span>
            <i class='bx bx-chevron-right'></i>
          </div>
        </div>
      </a>
      
      <!-- Evaluate by Individual Card (with freeze condition) -->
      <?php if ($is_frozen == '1'): ?>
        <!-- Frozen state -->
        <a href="javascript:void(0)" class="text-decoration-none card-frozen" onclick="alert('Evaluation system is currently frozen. Please contact administrator.')">
          <div class="dashboard-card p-4 card-6">
            <div class="frozen-overlay">
              <i class='bx bx-lock'></i> Frozen
            </div>
            <div class="card-icon">
              <i class='bx bx-bar-chart-alt-2'></i>
            </div>
            <h3 class="card-title">Evaluate an Individual</h3>
            <p class="card-description">Select and evaluate employees individually.</p>
            <div class="card-link">
              <span>Individual Evaluation</span>
              <i class='bx bx-chevron-right'></i>
            </div>
          </div>
        </a>
      <?php else: ?>
        <!-- Active state -->
        <a href="./namely_evaluation.php" class="text-decoration-none">
          <div class="dashboard-card p-4 card-6">
            <div class="card-icon">
              <i class='bx bx-bar-chart-alt-2'></i>
            </div>
            <h3 class="card-title">Evaluate an Individual</h3>
            <p class="card-description">Select and evaluate employees individually.</p>
            <div class="card-link">
              <span>Individual Evaluation</span>
              <i class='bx bx-chevron-right'></i>
            </div>
          </div>
        </a>
      <?php endif; ?>
      
      <!-- Evaluate by Warehouse Card (with freeze condition) -->
      <?php if ($is_frozen == '1'): ?>
        <!-- Frozen state -->
        <a href="javascript:void(0)" class="text-decoration-none card-frozen" onclick="alert('Evaluation system is currently frozen. Please contact administrator.')">
          <div class="dashboard-card p-4 card-7">
            <div class="frozen-overlay">
              <i class='bx bx-lock'></i> Frozen
            </div>
            <div class="card-icon">
              <i class='bx bx-grid-alt'></i>
            </div>
            <h3 class="card-title">Evaluate a Warehouse</h3>
            <p class="card-description">Select and evaluate group of employees attached to their warehouse.</p>
            <div class="card-link">
              <span>Warehouse Evaluation</span>
              <i class='bx bx-chevron-right'></i>
            </div>
          </div>
        </a>
      <?php else: ?>
        <!-- Active state -->
        <a href="./Warehouses.php" class="text-decoration-none">
          <div class="dashboard-card p-4 card-7">
            <div class="card-icon">
              <i class='bx bx-grid-alt'></i>
            </div>
            <h3 class="card-title">Evaluate a Warehouse</h3>
            <p class="card-description">Select and evaluate group of employees attached to their warehouse.</p>
            <div class="card-link">
              <span>Warehouse Evaluation</span>
              <i class='bx bx-chevron-right'></i>
            </div>
          </div>
        </a>
      <?php endif; ?>
      
      <a href="./periodRatings.php" class="text-decoration-none">
        <div class="dashboard-card p-4 card-8">
          <div class="card-icon">
            <i class='bx bxs-star-half'></i>
          </div>
          <h3 class="card-title">Results & Grading</h3>
          <p class="card-description">View, analyze, and manage evaluation results and performance data.</p>
          <div class="card-link">
            <span>View Results</span>
            <i class='bx bx-chevron-right'></i>
          </div>
        </div>
      </a>
      
      <!-- NEW: Records Card -->
        <a href="report.php" class="text-decoration-none">
        <div class="dashboard-card p-4 card-9">
          <div class="card-icon">
            <i class='fas fa-chart-pie'></i>
          </div>
          <h3 class="card-title">KPI Dashboard</h3>
          <p class="card-description">Detailed analytics and insights about evaluator performance and KPI tracking.</p>
          <div class="card-link">
            <span>View KPI Dashboard</span>
            <i class='bx bx-chevron-right'></i>
          </div>
        </div>
      </a>
      <a href="./records.php" class="text-decoration-none">
        <div class="dashboard-card p-4 card-9">
          <div class="card-icon">
            <i class='bx bx-history'></i>
          </div>
          <h3 class="card-title">Incident Log</h3>
          <p class="card-description">View all evaluation records with complete history and detailed information.</p>
          <div class="card-link">
            <span>View Incident Log</span>
            <i class='bx bx-chevron-right'></i>
          </div>
        </div>
      </a>
        <a href="evaluator_incident_report.php" class="text-decoration-none">
        <div class="dashboard-card p-4 card-9">
          <div class="card-icon">
            <i class='fas fa-chart-bar'></i>
          </div>
          <h3 class="card-title">Evaluator Performance Analysis</h3>
          <p class="card-description">View detailed breakdown of incidents marked by each evaluator, including positive and negative counts for each incident type (A, B, C, etc.).</p>
          <div class="card-link">
            <span>View Evaluator Performance</span>
            <i class='bx bx-chevron-right'></i>
          </div>
        </div>
      </a>
       
    </div>
  </div>
</body>

</html>

<?php } else {
  header("location: signin.php");
} ?>