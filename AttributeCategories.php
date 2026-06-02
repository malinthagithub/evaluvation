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

  $sql = "SELECT DISTINCT category FROM attribute";
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
    <title>Attributes Categories | JB Employee Evaluation</title>
    <link rel="icon" type="image/x-icon" href="./img/jb.png">

    <!-- Modern UI Libraries -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta1/dist/css/bootstrap.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta1/dist/js/bootstrap.bundle.min.js" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.2.1/jquery.min.js" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/boxicons@latest/css/boxicons.min.css" />

    <link rel="stylesheet" href="./style.css" />
    <script src="./script.js"></script>

    <style>
      /* Modern UI Styles - Only for page content */
      .attributes-content {
        padding-top: 12vh;
        padding-bottom: 5vh;
        min-height: 100vh;
       
      }

      .attributes-header {
        text-align: center;
        margin-bottom: 3rem;
        padding: 0 1rem;
      }

      .attributes-header h1 {
        font-size: 2.5rem;
        font-weight: 700;
        color: #1f2937;
        margin-bottom: 1rem;
        position: relative;
        display: inline-block;
      }

      .attributes-header h1:after {
        content: '';
        position: absolute;
        bottom: -10px;
        left: 50%;
        transform: translateX(-50%);
        width: 80px;
        height: 4px;
        background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
        border-radius: 2px;
      }

      .attributes-header p {
        color: #6b7280;
        font-size: 1.1rem;
        max-width: 600px;
        margin: 1.5rem auto 0;
        line-height: 1.6;
      }
.attributes-content h1 {

    margin-bottom: 0;
 position: relative;
 top: -60px;
  }
      .category-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
        gap: 2rem;
        padding: 0 1.5rem;
        max-width: 1400px;
        margin: 0 auto;
      }

      .category-card-modern {
        background: white;
        border-radius: 16px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
        transition: all 0.3s ease;
        overflow: hidden;
        border: 1px solid #e5e7eb;
        text-decoration: none;
        display: block;
        position: relative;
 top: -60px;
        height: 90%;
      }

      .category-card-modern:hover {
        transform: translateY(-10px);
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
        border-color: #667eea;
      }

      .category-card-modern::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 5px;
        background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
      }

      .category-card-modern.all-attributes::before {
        background: linear-gradient(90deg, #ef4444 0%, #dc2626 100%);
      }

      .card-inner {
        padding: 2.5rem 2rem;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        text-align: center;
        height: 100%;
      }

      .card-icon {
        width: 70px;
        height: 70px;
        background: linear-gradient(135deg, #e0e7ff 0%, #c7d2fe 100%);
        border-radius: 20px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 1.5rem;
        transition: all 0.3s ease;
      }

      .category-card-modern:hover .card-icon {
        transform: scale(1.1) rotate(5deg);
      }

      .category-card-modern.all-attributes .card-icon {
        background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
      }

      .card-icon i {
        font-size: 2rem;
        color: #4f46e5;
        transition: all 0.3s ease;
      }

      .category-card-modern.all-attributes .card-icon i {
        color: #ef4444;
      }

      .category-card-modern:hover .card-icon i {
        color: #4338ca;
      }

      .category-card-modern.all-attributes:hover .card-icon i {
        color: #dc2626;
      }

      .card-title {
        font-size: 1.5rem;
        font-weight: 700;
        color: #1f2937;
        margin-bottom: 0.75rem;
        transition: all 0.3s ease;
      }

      .category-card-modern:hover .card-title {
        color: #4f46e5;
      }

      .category-card-modern.all-attributes:hover .card-title {
        color: #ef4444;
      }

      .card-description {
        color: #6b7280;
        font-size: 0.95rem;
        margin-bottom: 1.5rem;
        line-height: 1.5;
      }

      .card-action {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        color: #4f46e5;
        font-weight: 600;
        font-size: 0.9rem;
        padding: 0.5rem 1.25rem;
        background: #e0e7ff;
        border-radius: 8px;
        transition: all 0.3s ease;
        opacity: 0;
        transform: translateY(10px);
      }

      .category-card-modern:hover .card-action {
        opacity: 1;
        transform: translateY(0);
      }

      .category-card-modern.all-attributes .card-action {
        color: #ef4444;
        background: #fee2e2;
      }

      .card-action i {
        transition: transform 0.3s ease;
      }

      .category-card-modern:hover .card-action i {
        transform: translateX(5px);
      }

      /* Floating Button Modern */
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
        border: none;
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

      /* Responsive */
      @media (max-width: 1200px) {
        .category-grid {
          grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
          gap: 1.75rem;
        }
      }

      @media (max-width: 768px) {
        .attributes-content {
          padding-top: 10vh;
          padding-bottom: 4vh;
        }

        .attributes-header h1 {
          font-size: 2rem;
        }

        .attributes-header p {
          font-size: 1rem;
        }

        .category-grid {
          grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
          gap: 1.5rem;
          padding: 0 1rem;
        }

        .card-inner {
          padding: 2rem 1.5rem;
        }

        .card-icon {
          width: 60px;
          height: 60px;
          border-radius: 16px;
          margin-bottom: 1.25rem;
        }

        .card-icon i {
          font-size: 1.75rem;
        }

        .card-title {
          font-size: 1.35rem;
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
        .attributes-content {
          padding-top: 8vh;
        }

        .attributes-header {
          margin-bottom: 2rem;
        }

        .attributes-header h1 {
          font-size: 1.75rem;
        }

        .category-grid {
          grid-template-columns: 1fr;
          gap: 1.25rem;
          padding: 0 0.75rem;
        }

        .card-inner {
          padding: 1.75rem 1.25rem;
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

    <div class="attributes-content">
      <div class="attributes-header animate-fade-in">
        <h1 >Attributes Categories</h1>
        <?php if ($is_frozen == '1'): ?>
          <div class="mt-3">
            <span class="badge bg-danger px-3 py-2">
              <i class='bx bx-lock me-1'></i> System Frozen
            </span>
          </div>
        <?php endif; ?>
      </div>

      <?php if ($result->num_rows > 0) { ?>
        <div class="category-grid">
          <?php 
          $counter = 0;
          while ($row = $result->fetch_assoc()) { 
            $counter++;
          ?>
            <a href="./Attributes.php?cat=<?php echo urlencode($row['category']) ?>" class="category-card-modern animate-fade-in delay-<?php echo ($counter % 4) + 1; ?>">
              <div class="card-inner">
                <div class="card-icon">
                  <i class="fas fa-folder-open"></i>
                </div>
                <h3 class="card-title"><?php echo htmlspecialchars($row['category']) ?></h3>
                <p class="card-description">Browse and manage all attributes in this category</p>
                <span class="card-action">
                  View Attributes <i class="fas fa-arrow-right"></i>
                </span>
              </div>
            </a>
          <?php } ?>
          
          <a href="./Attributes.php" class="category-card-modern all-attributes animate-fade-in">
            <div class="card-inner">
              <div class="card-icon">
                <i class="fas fa-layer-group"></i>
              </div>
              <h3 class="card-title">All Attributes</h3>
              <p class="card-description">View all attributes across all categories at once</p>
              <span class="card-action">
                View All <i class="fas fa-arrow-right"></i>
              </span>
            </div>
          </a>
        </div>
      <?php } else { ?>
        <div class="empty-state-modern animate-fade-in">
          <div class="empty-state-icon">
            <i class="fas fa-inbox"></i>
          </div>
          <h3 class="empty-state-title">No Categories Found</h3>
          <p class="empty-state-text">There are no attribute categories available. Create your first category to start organizing attributes.</p>
          <?php if ($_SESSION["isAdmin"]) { ?>
            <a href="./attribute.php" class="empty-state-btn">
              <i class="fas fa-plus"></i> Create Category
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