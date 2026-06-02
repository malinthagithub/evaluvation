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

  $sql = "SELECT * FROM category WHERE category_name != 'Common'";
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
    <title>Categories</title>
    <link rel="icon" type="image/x-icon" href="./img/jb.png">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta1/dist/css/bootstrap.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta1/dist/js/bootstrap.bundle.min.js" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.2.1/jquery.min.js" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/boxicons@latest/css/boxicons.min.css" />
    
    <!-- Font Awesome for enhanced icons -->
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
      
      .page-header {
        background: var(--glass-bg);
        backdrop-filter: blur(20px);
        -webkit-backdrop-filter: blur(20px);
        border-radius: 16px;
        border: 1px solid var(--glass-border);
        padding: 25px 30px;
        margin-bottom: 30px;
        box-shadow: var(--glass-shadow);
        position: relative;
        overflow: hidden;
      }
      
      .page-header::before {
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
      
      .page-header h2 {
        font-weight: 700;
        margin-bottom: 8px;
        color: var(--dark-color);
      }
      
      .page-header p {
        color: #64748b;
        margin-bottom: 0;
        font-size: 1.1rem;
      }
      
      .category-card {
        background: var(--glass-bg);
        backdrop-filter: blur(20px);
        -webkit-backdrop-filter: blur(20px);
        border-radius: 16px;
        border: 1px solid var(--glass-border);
        box-shadow: var(--glass-shadow);
        transition: var(--transition);
        overflow: hidden;
        height: auto;
        min-height: 300px;
        position: relative;
        padding: 30px;
        margin: 20px 0;
      }
      
      .category-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: var(--primary-color);
        z-index: 1;
      }
      
      .category-card:hover {
        transform: translateY(-8px);
        box-shadow: var(--hover-shadow);
      }
      
      .category-header {
        margin-bottom: 25px;
        text-align: center;
        position: relative;
        padding-bottom: 20px;
      }
      
      .category-header::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 50%;
        transform: translateX(-50%);
        width: 80px;
        height: 3px;
        background: linear-gradient(90deg, var(--primary-color), var(--secondary-color));
        border-radius: 3px;
      }
      
      .category-title {
        font-weight: 700;
        font-size: 1.8rem;
        margin-bottom: 0;
        color: var(--dark-color);
        background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
      }
      
      .stores-container {
        margin-top: 20px;
      }
      
      .store-item {
        display: flex;
        align-items: center;
        padding: 12px 15px;
        margin-bottom: 10px;
        background: rgba(37, 99, 235, 0.05);
        border-radius: 10px;
        border-left: 4px solid var(--primary-color);
        transition: var(--transition);
      }
      
      .store-item:hover {
        background: rgba(37, 99, 235, 0.1);
        transform: translateX(5px);
      }
      
      .store-icon {
        width: 40px;
        height: 40px;
        border-radius: 10px;
        background: var(--primary-color);
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 15px;
        color: white;
        font-size: 18px;
      }
      
      .store-name {
        font-weight: 600;
        color: var(--dark-color);
        flex-grow: 1;
      }
      
      .action-buttons {
        position: absolute;
        bottom: 25px;
        right: 25px;
        display: flex;
        gap: 10px;
      }
      
      .btn-edit, .btn-delete {
        padding: 10px 20px;
        border-radius: 10px;
        font-weight: 600;
        position: relative;
        top: 15px;
        font-size: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        text-decoration: none;
        transition: var(--transition);
        border: none;
        cursor: pointer;
      }
      
      .btn-edit {
        background: rgba(37, 99, 235, 0.1);
        color: var(--primary-color);
        border: 1px solid rgba(37, 99, 235, 0.3);
      }
      
      .btn-edit:hover {
        background: rgba(37, 99, 235, 0.2);
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2);
      }
      
      .btn-delete {
        background: rgba(220, 38, 38, 0.1);
        color: var(--danger-color);
        border: 1px solid rgba(220, 38, 38, 0.3);
      }
      
      .btn-delete:hover {
        background: rgba(220, 38, 38, 0.2);
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(220, 38, 38, 0.2);
      }
      
      .floating-add-btn {
        position: fixed;
        bottom: 30px;
        right: 30px;
        width: 70px;
        height: 70px;
        border-radius: 50%;
        background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 28px;
        box-shadow: 0 8px 25px rgba(37, 99, 235, 0.3);
        transition: var(--transition);
        text-decoration: none;
        z-index: 1000;
      }
      
      .floating-add-btn:hover {
        transform: scale(1.1) rotate(90deg);
        box-shadow: 0 12px 35px rgba(37, 99, 235, 0.4);
      }
      
      .empty-state {
        text-align: center;
        padding: 60px 20px;
        background: var(--glass-bg);
        backdrop-filter: blur(20px);
        border-radius: 16px;
        border: 2px dashed var(--glass-border);
      }
      
      .empty-state i {
        font-size: 60px;
        color: var(--primary-color);
        opacity: 0.5;
        margin-bottom: 20px;
      }
      
      .empty-state h3 {
        color: var(--dark-color);
        margin-bottom: 10px;
      }
      
      .empty-state p {
        color: #64748b;
        max-width: 400px;
        margin: 0 auto;
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
      
      /* Role-based styling */
      .Admin .page-header::before { background: linear-gradient(135deg, var(--primary-color), var(--secondary-color)); }
      .Evaluator .page-header::before { background: linear-gradient(135deg, var(--success-color), var(--info-color)); }
      .Guest .page-header::before { background: linear-gradient(135deg, var(--warning-color), var(--danger-color)); }
      
      .Admin .category-header::after { background: linear-gradient(90deg, var(--primary-color), var(--secondary-color)); }
      .Evaluator .category-header::after { background: linear-gradient(90deg, var(--success-color), var(--info-color)); }
      .Guest .category-header::after { background: linear-gradient(90deg, var(--warning-color), var(--danger-color)); }
      
      .Admin .store-item { border-left-color: var(--primary-color); }
      .Evaluator .store-item { border-left-color: var(--success-color); }
      .Guest .store-item { border-left-color: var(--warning-color); }
      
      .Admin .floating-add-btn { background: linear-gradient(135deg, var(--primary-color), var(--secondary-color)); }
      .Evaluator .floating-add-btn { background: linear-gradient(135deg, var(--success-color), var(--info-color)); }
      .Guest .floating-add-btn { background: linear-gradient(135deg, var(--warning-color), var(--danger-color)); }
      
      /* Responsive adjustments */
      @media (max-width: 768px) {
        .category-card {
          padding: 20px;
        }
        
        .action-buttons {
          position: relative;
          bottom: auto;
          right: auto;
          justify-content: center;
          margin-top: 20px;
        }
        
        .floating-add-btn {
          width: 60px;
          height: 60px;
          font-size: 24px;
          bottom: 20px;
          right: 20px;
        }
      }
      
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
            <a href="./Categories.php" class="nav_link active"> <i class='bx bx-category nav_icon'></i> <span class="nav_name">Categories</span> </a>
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

    <div class="height-100 container mt-5 mb-3" style="padding-top: 2vh;">
      <!-- System Status Alert -->
      <?php if ($is_frozen == '1'): ?>
        
      <?php endif; ?>
      
      <!-- Page Header -->
      <div class="page-header">
        <div class="row align-items-center">
          <div class="col-md-8">
            <h2>Evaluation Categories</h2>
            <p>Manage and view all evaluation categories. Categories help organize evaluation criteria by department or role type.</p>
            <?php if ($is_frozen == '1'): ?>
              <div class="mt-3">
                <span class="badge bg-danger px-3 py-2">
                  <i class='bx bx-lock me-1'></i> System Frozen
                </span>
              </div>
            <?php endif; ?>
          </div>
          <div class="col-md-4 text-end">
            <i class='bx bx-category' style="font-size: 70px; color: var(--primary-color); opacity: 0.7;"></i>
          </div>
        </div>
      </div>
      
      <?php if ($result->num_rows > 0) { ?>
        <div class="row">
          <?php while ($row = $result->fetch_assoc()) { ?>
            <div class="col-md-4">
              <div class="category-card">
                <div class="category-header">
                  <h3 class="category-title"><?php echo $row['category_name'] ?></h3>
                </div>
                
                <div class="stores-container">
                  <?php 
                  // Display store information if available
                  $stores = [
                    'store_1' => $row['store_1'],
                    'store_2' => $row['store_2'],
                    'store_3' => $row['store_3'],
                    'store_4' => $row['store_4'],
                    'store_5' => $row['store_5'],
                    'store_6' => $row['store_6']
                  ];
                  
                  foreach ($stores as $storeKey => $storeValue) {
                    if (!empty($storeValue)) { ?>
                      <div class="store-item">
                        <div class="store-icon">
                          <i class='bx bx-store'></i>
                        </div>
                        <div class="store-name"><?php echo $storeValue; ?></div>
                      </div>
                    <?php }
                  } ?>
                </div>
                
                <?php if ($_SESSION["isAdmin"]) { ?>
                  <div class="action-buttons">
                    <a href="./category.php?id=<?php echo $row['category_id'] ?>" class="btn-edit">
                      <i class='bx bx-pencil'></i> Edit
                    </a>
                    <a href="./delete.php?catId=<?php echo $row['category_id'] ?>" class="btn-delete">
                      <i class='bx bx-trash'></i> Delete
                    </a>
                  </div>
                <?php } ?>
              </div>
            </div>
          <?php } ?>
        </div>
      <?php } else { ?>
        <div class="empty-state">
          <i class='bx bx-category'></i>
          <h3>No Categories Found</h3>
          <p>No evaluation categories have been created yet. Add your first category to get started.</p>
          <?php if ($_SESSION["isAdmin"]) { ?>
            <a href="./category.php" class="btn btn-primary mt-3">
              <i class='bx bx-plus'></i> Add First Category
            </a>
          <?php } ?>
        </div>
      <?php } ?>
    </div>
    
    <?php if ($_SESSION["isAdmin"]) { ?>
      <a href="./category.php" class="floating-add-btn <?php if ($_SESSION['isAdmin']) {
                                                          echo "Admin";
                                                        } else if ($_SESSION["isEvaluator"]) {
                                                          echo "Evaluator";
                                                        } else {
                                                          echo "Guest";
                                                        } ?>">
        <i class='bx bx-plus'></i>
      </a>
    <?php } ?>
  </body>

  </html>

<?php } else {
  header("location: signin.php");
} ?>