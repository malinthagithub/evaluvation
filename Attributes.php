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

  if (isset($_GET['cat'])) {
    $category = $_GET['cat'];
    $sql = "SELECT * FROM attribute WHERE category = '$category'";
  } else {
    $sql = "SELECT * FROM attribute";
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
    <title>Attributes | JB Employee Evaluation</title>
    <link rel="icon" type="image/x-icon" href="./img/jb.png">

    <!-- Modern UI Libraries -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta1/dist/css/bootstrap.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.2.1/jquery.min.js" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/boxicons@latest/css/boxicons.min.css" />

    <link rel="stylesheet" href="./style.css" />
    <script src="./script.js"></script>

    <style>
      /* Modern Attributes Listing Styles */
      .attributes-listing {
        padding-top: 12vh;
        padding-bottom: 5vh;
        min-height: 100vh;
       
      }

      .listing-header {
        text-align: center;
        margin-bottom: 3rem;
        padding: 0 1.5rem;
        position: relative;
        top: -80px;
      }

      .listing-header h1 {
        font-size: 2.5rem;
        font-weight: 700;
        color: #1f2937;
        margin-bottom: 1rem;
        position: relative;
        display: inline-block;
      }

      .listing-header h1:after {
        content: '';
        position: absolute;
        bottom: -10px;
        left: 50%;
        transform: translateX(-50%);
        width: 100px;
        height: 4px;
        background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
        border-radius: 2px;
      }

      .category-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 0.5rem 1.25rem;
        border-radius: 50px;
        font-size: 1rem;
        font-weight: 600;
        margin-top: 1rem;
        box-shadow: 0 4px 6px rgba(102, 126, 234, 0.2);
      }

      .category-badge i {
        font-size: 0.9rem;
      }

      .attributes-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(500px, 1fr));
        gap: 2rem;
        padding: 0 1.5rem;
        max-width: 1600px;
        margin: 0 auto;
      }

      .attribute-card {
        background: white;
        border-radius: 16px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
        transition: all 0.3s ease;
        overflow: hidden;
        border: 1px solid #e5e7eb;
        position: relative;
        top: -100px;
      }

      .attribute-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.12);
        border-color: #667eea;
      }

      .attribute-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
      }

      .attribute-header {
        padding: 2rem 2rem 1.5rem;
        border-bottom: 1px solid #e5e7eb;
        background: #f9fafb;
      }

      .attribute-title {
        font-size: 1.5rem;
        font-weight: 700;
        color: #1f2937;
        margin-bottom: 0.5rem;
        display: flex;
        align-items: center;
        gap: 0.75rem;
      }

      .attribute-title i {
        color: #4f46e5;
        font-size: 1.25rem;
      }

      .attribute-category {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        background: #e0e7ff;
        color: #4f46e5;
        padding: 0.25rem 0.75rem;
        border-radius: 20px;
        font-size: 0.85rem;
        font-weight: 500;
      }

      .attribute-body {
        padding: 1.5rem 2rem;
      }

      .attribute-details {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 1.5rem;
        margin-bottom: 1.5rem;
      }

      .detail-item {
        display: flex;
        flex-direction: column;
        gap: 0.25rem;
      }

      .detail-label {
        font-size: 0.8rem;
        font-weight: 600;
        color: #6b7280;
        text-transform: uppercase;
        letter-spacing: 0.5px;
      }

      .detail-value {
        font-size: 1rem;
        font-weight: 500;
        color: #1f2937;
        display: flex;
        align-items: center;
        gap: 0.5rem;
      }

      .detail-value.badge-value {
        display: inline-flex;
        background: #f3f4f6;
        padding: 0.375rem 0.75rem;
        border-radius: 6px;
        font-size: 0.9rem;
      }

      .evaluators-section {
        background: #f9fafb;
        border-radius: 10px;
        padding: 1.25rem;
        margin-bottom: 1.5rem;
      }

      .evaluators-section h5 {
        font-size: 0.95rem;
        font-weight: 600;
        color: #374151;
        margin-bottom: 1rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
      }

      .evaluators-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 1rem;
      }

      .evaluator-item {
        text-align: center;
      }

      .evaluator-label {
        font-size: 0.75rem;
        font-weight: 500;
        color: #6b7280;
        margin-bottom: 0.5rem;
      }

      .evaluator-status {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto;
        font-size: 1.25rem;
      }

      .evaluator-status.approved {
        background: #d1fae5;
        color: #059669;
      }

      .evaluator-status.not-approved {
        background: #fee2e2;
        color: #dc2626;
      }

      .attribute-footer {
        padding: 1.5rem 2rem;
        border-top: 1px solid #e5e7eb;
        background: #f9fafb;
        display: flex;
        justify-content: flex-end;
        gap: 1rem;
      }

      .action-btn {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.625rem 1.25rem;
        border-radius: 8px;
        font-size: 0.9rem;
        font-weight: 600;
        text-decoration: none;
        transition: all 0.3s ease;
        border: none;
        cursor: pointer;
      }

      .edit-btn {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
      }

      .edit-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 12px rgba(102, 126, 234, 0.25);
        color: white;
        text-decoration: none;
      }

      .delete-btn {
        background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
        color: white;
      }

      .delete-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 12px rgba(239, 68, 68, 0.25);
        color: white;
        text-decoration: none;
      }

      /* Floating Button */
      .floating-container-modern {
        position: fixed;
        bottom: 30px;
        right: 30px;
        z-index: 1000;
      }

      .floating-button-modern {
        width: 60px;
        height: 60px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3);
        transition: all 0.3s ease;
        cursor: pointer;
        text-decoration: none;
      }

      .floating-button-modern:hover {
        transform: scale(1.1) rotate(90deg);
        box-shadow: 0 15px 35px rgba(102, 126, 234, 0.4);
      }

      .floating-button-modern a {
        color: white;
        text-decoration: none;
        width: 100%;
        height: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
      }

      .floating-button-modern i {
        font-size: 1.5rem;
        font-weight: bold;
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

      /* Freeze Status Badge */
      .freeze-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
        color: white;
        padding: 0.5rem 1.25rem;
        border-radius: 50px;
        font-size: 1rem;
        font-weight: 600;
        margin-top: 1rem;
        box-shadow: 0 4px 6px rgba(239, 68, 68, 0.2);
      }

      /* Empty State */
      .empty-state-modern {
        text-align: center;
        padding: 4rem 2rem;
        background: white;
        border-radius: 16px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
        max-width: 600px;
        margin: 3rem auto;
      }

      .empty-state-icon {
        font-size: 4rem;
        color: #9ca3af;
        margin-bottom: 1.5rem;
        opacity: 0.5;
      }

      .empty-state-title {
        font-size: 1.75rem;
        color: #374151;
        margin-bottom: 1rem;
        font-weight: 600;
      }

      .empty-state-text {
        color: #6b7280;
        margin-bottom: 2rem;
        line-height: 1.6;
        font-size: 1.05rem;
      }

      .empty-state-btn {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border: none;
        padding: 0.875rem 2rem;
        font-size: 1rem;
        font-weight: 600;
        border-radius: 10px;
        cursor: pointer;
        transition: all 0.3s ease;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        box-shadow: 0 4px 6px rgba(102, 126, 234, 0.2);
      }

      .empty-state-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 12px rgba(102, 126, 234, 0.25);
        color: white;
        text-decoration: none;
      }

      /* Responsive */
      @media (max-width: 1200px) {
        .attributes-grid {
          grid-template-columns: repeat(auto-fill, minmax(450px, 1fr));
          gap: 1.75rem;
        }
      }

      @media (max-width: 992px) {
        .attributes-grid {
          grid-template-columns: 1fr;
          max-width: 800px;
        }

        .attribute-details {
          grid-template-columns: 1fr;
          gap: 1rem;
        }
      }

      @media (max-width: 768px) {
        .attributes-listing {
          padding-top: 10vh;
          padding-bottom: 4vh;
        }

        .listing-header h1 {
          font-size: 2rem;
        }

        .attributes-grid {
          padding: 0 1rem;
          gap: 1.5rem;
        }

        .attribute-header {
          padding: 1.5rem 1.5rem 1rem;
        }

        .attribute-body {
          padding: 1.25rem 1.5rem;
        }

        .attribute-footer {
          padding: 1.25rem 1.5rem;
        }

        .evaluators-grid {
          grid-template-columns: repeat(2, 1fr);
          gap: 1.5rem;
        }

        .floating-container-modern {
          bottom: 20px;
          right: 20px;
        }

        .floating-button-modern {
          width: 55px;
          height: 55px;
        }
        
        .system-alert {
          top: 60px;
          width: 95%;
        }
      }

      @media (max-width: 576px) {
        .attributes-listing {
          padding-top: 8vh;
        }

        .listing-header {
          margin-bottom: 2rem;
          padding: 0 1rem;
        }

        .listing-header h1 {
          font-size: 1.75rem;
        }

        .attribute-title {
          font-size: 1.35rem;
        }

        .attribute-header,
        .attribute-body,
        .attribute-footer {
          padding: 1.25rem 1.25rem;
        }

        .evaluators-grid {
          grid-template-columns: 1fr;
          gap: 1rem;
        }

        .attribute-footer {
          flex-direction: column;
        }

        .action-btn {
          width: 100%;
          justify-content: center;
        }

        .floating-container-modern {
          bottom: 15px;
          right: 15px;
        }

        .floating-button-modern {
          width: 50px;
          height: 50px;
        }
      }

      /* Animation */
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

      .animate-fade-in {
        animation: fadeInUp 0.5s ease-out forwards;
      }

      .delay-1 { animation-delay: 0.1s; }
      .delay-2 { animation-delay: 0.2s; }
      .delay-3 { animation-delay: 0.3s; }
      .delay-4 { animation-delay: 0.4s; }

      /* Check/X Icons */
      .check-icon {
        color: #10b981;
        font-size: 1.2rem;
      }

      .x-icon {
        color: #ef4444;
        font-size: 1.2rem;
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
            <a href="./AttributeCategories.php" class="nav_link active"> <i class='bx bx-spreadsheet nav_icon'></i> <span class="nav_name">Attributes</span> </a>
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

    <div class="attributes-listing">
      <div class="listing-header animate-fade-in">
        <h1>
          <?php if (isset($_GET['cat'])) { ?>
            <i class="fas fa-folder-open"></i> <?php echo htmlspecialchars($category); ?> Attributes
          <?php } else { ?>
            <i class="fas fa-layer-group"></i> All Attributes
          <?php } ?>
        </h1>
        <?php if (isset($_GET['cat'])) { ?>
          <div class="category-badge">
            <i class="fas fa-tag"></i> Category: <?php echo htmlspecialchars($category); ?>
          </div>
        <?php } ?>
        <?php if ($is_frozen == '1'): ?>
          <div class="freeze-badge">
            <i class='bx bx-lock'></i> System Frozen
          </div>
        <?php endif; ?>
        <p style="color: #6b7280; margin-top: 1rem; font-size: 1.05rem;">
          <?php echo $result->num_rows; ?> attribute(s) found
        </p>
      </div>

      <?php if ($result->num_rows > 0) { ?>
        <div class="attributes-grid">
          <?php 
          $counter = 0;
          while ($row = $result->fetch_assoc()) { 
            $counter++;
          ?>
            <div class="attribute-card animate-fade-in delay-<?php echo ($counter % 4) + 1; ?>">
              <div class="attribute-header">
                <h3 class="attribute-title">
                  <i class="fas fa-star"></i> <?php echo htmlspecialchars($row['attribute_name']); ?>
                </h3>
                <span class="attribute-category">
                  <i class="fas fa-folder"></i> <?php echo htmlspecialchars($row['category']); ?>
                </span>
              </div>

              <div class="attribute-body">
                <div class="attribute-details">
                  <div class="detail-item">
                    <span class="detail-label">Weightage</span>
                    <span class="detail-value badge-value">
                      <i class="fas fa-weight-hanging"></i> <?php echo htmlspecialchars($row['weightage']); ?>%
                    </span>
                  </div>

                  <div class="detail-item">
                    <span class="detail-label">PO</span>
                    <span class="detail-value">
                      <i class="fas fa-bullseye"></i> <?php echo htmlspecialchars($row['process_objective']); ?>
                    </span>
                  </div>

                  <div class="detail-item">
                    <span class="detail-label">CSF</span>
                    <span class="detail-value">
                      <i class="fas fa-chart-line"></i> <?php echo htmlspecialchars($row['csf']); ?>
                    </span>
                  </div>

                  <div class="detail-item">
                    <span class="detail-label">PKPI</span>
                    <span class="detail-value">
                      <i class="fas fa-tachometer-alt"></i> <?php echo htmlspecialchars($row['process_kpi']); ?>
                    </span>
                  </div>

                  <div class="detail-item">
                    <span class="detail-label">Marking Scheme</span>
                    <span class="detail-value badge-value">
                      <i class="fas fa-calculator"></i> <?php echo htmlspecialchars($row['scoring_method']); ?>
                    </span>
                  </div>

                  <div class="detail-item">
                    <span class="detail-label">Evaluation Type</span>
                    <span class="detail-value badge-value">
                      <?php if ($row['is_valued'] == 1) { ?>
                        <i class="fas fa-chart-bar"></i> Result/Value Based
                      <?php } else { ?>
                        <i class="fas fa-exclamation-triangle"></i> Critical Incident Based
                      <?php } ?>
                    </span>
                  </div>
                </div>

                <div class="evaluators-section">
                  <h5><i class="fas fa-user-check"></i> Authorized Evaluators</h5>
                  <div class="evaluators-grid">
                    <div class="evaluator-item">
                      <div class="evaluator-label">PLE</div>
                      <div class="evaluator-status <?php echo $row['PLE'] ? 'approved' : 'not-approved'; ?>">
                        <?php echo $row['PLE'] ? '<i class="fas fa-check check-icon"></i>' : '<i class="fas fa-times x-icon"></i>'; ?>
                      </div>
                    </div>
                    <div class="evaluator-item">
                      <div class="evaluator-label">OE</div>
                      <div class="evaluator-status <?php echo $row['OE'] ? 'approved' : 'not-approved'; ?>">
                        <?php echo $row['OE'] ? '<i class="fas fa-check check-icon"></i>' : '<i class="fas fa-times x-icon"></i>'; ?>
                      </div>
                    </div>
                    <div class="evaluator-item">
                      <div class="evaluator-label">QM</div>
                      <div class="evaluator-status <?php echo $row['QM'] ? 'approved' : 'not-approved'; ?>">
                        <?php echo $row['QM'] ? '<i class="fas fa-check check-icon"></i>' : '<i class="fas fa-times x-icon"></i>'; ?>
                      </div>
                    </div>
                    <div class="evaluator-item">
                      <div class="evaluator-label">PE</div>
                      <div class="evaluator-status <?php echo $row['PE'] ? 'approved' : 'not-approved'; ?>">
                        <?php echo $row['PE'] ? '<i class="fas fa-check check-icon"></i>' : '<i class="fas fa-times x-icon"></i>'; ?>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <?php if ($_SESSION["isAdmin"]) { ?>
                <div class="attribute-footer">
                  <a href="./attribute.php?id=<?php echo $row['attribute_id'] ?>" class="action-btn edit-btn">
                    <i class="fas fa-edit"></i> Edit Attribute
                  </a>
                  <a href="./delete.php?attId=<?php echo $row['attribute_id'] ?>" class="action-btn delete-btn" onclick="return confirm('Are you sure you want to delete this attribute?')">
                    <i class="fas fa-trash"></i> Delete
                  </a>
                </div>
              <?php } ?>
            </div>
          <?php } ?>
        </div>
      <?php } else { ?>
        <div class="empty-state-modern animate-fade-in">
          <div class="empty-state-icon">
            <i class="fas fa-inbox"></i>
          </div>
          <h3 class="empty-state-title">No Attributes Found</h3>
          <p class="empty-state-text">
            <?php if (isset($_GET['cat'])) { ?>
              No attributes found in the "<?php echo htmlspecialchars($category); ?>" category.
            <?php } else { ?>
              No attributes have been created yet.
            <?php } ?>
          </p>
          <?php if ($_SESSION["isAdmin"]) { ?>
            <a href="./attribute.php" class="empty-state-btn">
              <i class="fas fa-plus"></i> Create Attribute
            </a>
          <?php } ?>
        </div>
      <?php } ?>
    </div>

    <?php if ($_SESSION["isAdmin"]) { ?>
      <div class="floating-container-modern">
        <div class="floating-button-modern">
          <a href="./attribute.php"><i class='bx bx-plus'></i></a>
        </div>
      </div>
    <?php } ?>
  </body>

  </html>

<?php } else {
  header("location: signin.php");
} ?>