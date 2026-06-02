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

  $sql = "SELECT * FROM store";
  $result = $conn->query($sql);
?>

  <!DOCTYPE html>
  <html lang="en">

  <head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Warehouses</title>
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

      /* Modern Store Cards with Two Sections */
      .store-card-split {
        background: white;
        border-radius: var(--radius-xl);
        height: 100%;
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

      .store-card-split:hover {
        transform: translateY(-8px);
        box-shadow: var(--shadow-lg);
        text-decoration: none;
        color: inherit;
      }

      /* Top Colored Section */
      .store-top-section {
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
        text-align: center;
      }

      .store-card-split.Admin .store-top-section {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      }

      .store-card-split.Evaluator .store-top-section {
        background: linear-gradient(135deg, #4CAF50 0%, #2E7D32 100%);
      }

      .store-card-split.Guest .store-top-section {
        background: linear-gradient(135deg, #757575 0%, #424242 100%);
      }

      /* Bottom White Section */
      .store-bottom-section {
        padding: 1.75rem;
        background: white;
        border-radius: 0 0 var(--radius-xl) var(--radius-xl);
      }

      /* Store Icon in Top Section */
      .store-icon-top {
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

      /* Store Info in Top Section */
      .store-category-top {
        display: inline-block;
        padding: 0.4rem 1rem;
        background: rgba(255, 255, 255, 0.2);
        color: white;
        border-radius: var(--radius-md);
        font-size: 0.85rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 1rem;
      }

      .store-number-top {
        font-size: 2rem;
        font-weight: 800;
        text-align: center;
        margin-bottom: 0.25rem;
        line-height: 1.2;
      }

      .store-label-top {
        font-size: 0.9rem;
        opacity: 0.9;
        text-align: center;
        font-weight: 500;
      }

      /* Supervisor Stats in Bottom Section */
      .supervisor-stats {
        margin-bottom: 1.5rem;
        padding-bottom: 1.5rem;
        border-bottom: 1px solid var(--border-color);
      }

      .stats-label {
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

      .stats-label i {
        color: var(--primary-color);
        font-size: 1rem;
      }

      .store-card-split.Admin .stats-label i {
        color: #667eea;
      }

      .store-card-split.Evaluator .stats-label i {
        color: #4CAF50;
      }

      .store-card-split.Guest .stats-label i {
        color: #757575;
      }

      .stats-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 1rem;
      }

      .stat-item {
        text-align: center;
        padding: 0.75rem;
        background: #f8f9fa;
        border-radius: var(--radius-lg);
      }

      .stat-value {
        font-size: 1.25rem;
        font-weight: 700;
        color: var(--dark-color);
        margin-bottom: 0.25rem;
        display: block;
      }

      .stat-label {
        font-size: 0.8rem;
        color: var(--gray-color);
        text-transform: uppercase;
        letter-spacing: 0.5px;
      }

      .male-stat .stat-value {
        color: #3b82f6;
      }

      .female-stat .stat-value {
        color: #ec4899;
      }

      .total-stat .stat-value {
        color: var(--success-color);
      }

      /* Action Section in Bottom */
      .action-section {
        text-align: center;
      }

      .action-label {
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

      .action-label i {
        color: var(--primary-color);
        font-size: 1rem;
      }

      .store-card-split.Admin .action-label i {
        color: #667eea;
      }

      .store-card-split.Evaluator .action-label i {
        color: #4CAF50;
      }

      .store-card-split.Guest .action-label i {
        color: #757575;
      }

      .action-btn {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        background: var(--primary-color);
        color: white;
        padding: 0.75rem 1.5rem;
        border-radius: var(--radius-lg);
        font-weight: 600;
        text-decoration: none;
        transition: var(--transition);
      }

      .action-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        color: white;
        text-decoration: none;
      }

      .store-card-split.Admin .action-btn {
        background: #667eea;
      }

      .store-card-split.Evaluator .action-btn {
        background: #4CAF50;
      }

      .store-card-split.Guest .action-btn {
        background: #757575;
      }

      /* View Button */
      .view-btn {
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

      .store-card-split:hover .view-btn {
        opacity: 1;
        transform: translateY(0);
      }

      .store-card-split.Admin .view-btn {
        background: #667eea;
      }

      .store-card-split.Evaluator .view-btn {
        background: #4CAF50;
      }

      .store-card-split.Guest .view-btn {
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

      /* Responsive Design */
      @media (max-width: 992px) {
        .store-top-section {
          padding: 1.5rem 1.5rem 1.25rem;
          min-height: 170px;
        }
        
        .store-bottom-section {
          padding: 1.5rem;
        }
        
        .store-number-top {
          font-size: 1.8rem;
        }
        
        .stats-grid {
          grid-template-columns: repeat(3, 1fr);
        }
      }

      @media (max-width: 768px) {
        .height-100 {
          padding-top: 1vh;
        }
        
        .page-header h1 {
          font-size: 1.75rem;
        }
        
        .view-btn {
          opacity: 1;
          transform: translateY(0);
        }
      }

      @media (max-width: 576px) {
        .store-icon-top {
          width: 60px;
          height: 60px;
          font-size: 1.5rem;
        }
        
        .store-number-top {
          font-size: 1.6rem;
        }
        
        .stats-grid {
          grid-template-columns: 1fr;
          gap: 0.75rem;
        }
        
        .stat-item {
          display: flex;
          justify-content: space-between;
          align-items: center;
          text-align: left;
        }
        
        .view-btn {
          bottom: 1rem;
          right: 1rem;
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

      .store-card-split {
        animation: fadeInUp 0.5s ease-out;
      }

      .store-card-split:nth-child(1) { animation-delay: 0.1s; }
      .store-card-split:nth-child(2) { animation-delay: 0.2s; }
      .store-card-split:nth-child(3) { animation-delay: 0.3s; }
      .store-card-split:nth-child(4) { animation-delay: 0.4s; }
      .store-card-split:nth-child(5) { animation-delay: 0.5s; }
      .store-card-split:nth-child(6) { animation-delay: 0.6s; }
      .store-card-split:nth-child(7) { animation-delay: 0.7s; }
      .store-card-split:nth-child(8) { animation-delay: 0.8s; }
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
            <a href="./namely_evaluation.php" class="nav_link"> <i class='bx bx-bar-chart-alt-2 nav_icon'></i> <span class="nav_name">Evaluate by Individual</span> </a>
            <a href="./Warehouses.php" class="nav_link active"> <i class='bx bx-grid-alt nav_icon'></i> <span class="nav_name">Evaluate by Warehouse</span> </a>
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

    <div class="height-100 container mt-5 mb-3">
      <!-- Page Header -->
      <div class="page-header">
        <h1>Warehouse Evaluation</h1>
        <p>Select a store to evaluate employees by warehouse</p>
      </div>

      <div class="row">
        <?php 
        if ($result->num_rows > 0) {
          while ($row = $result->fetch_assoc()) { 
            // Get employee counts for this store
            $store = $row['store_number'];
            $sqlm = "SELECT COUNT(DISTINCT emp_id) AS 'count' FROM employee WHERE current_store = '$store' and gender = 'Male'";
            $resultm = $conn->query($sqlm);
            $rowm = $resultm->fetch_assoc();
            
            $sqlf = "SELECT COUNT(DISTINCT emp_id) AS 'count' FROM employee WHERE current_store = '$store' and gender = 'Female'";
            $resultf = $conn->query($sqlf);
            $rowf = $resultf->fetch_assoc();
            
            $totalEmployees = $rowm['count'] + $rowf['count'];
        ?>
          <div class="col-xl-3 col-lg-4 col-md-6 mb-4">
            <div class="store-card-split <?php if ($_SESSION['isAdmin']) {
                                          echo "Admin";
                                        } else if ($_SESSION["isEvaluator"]) {
                                          echo "Evaluator";
                                        } else {
                                          echo "Guest";
                                        } ?>">
              
              <!-- Top Colored Section -->
              <div class="store-top-section">
                <!-- Store Icon -->
                <div class="store-icon-top">
                  <i class='bx bx-store'></i>
                </div>
                
                <!-- Store Category -->
                <div class="store-category-top"><?php echo $row['category_name'] ?></div>
                
                <!-- Store Number -->
                <div class="store-number-top">Store <?php echo $row['store_number'] ?></div>
                
                <div class="store-label-top">Warehouse Location</div>
              </div>
              
              <!-- Bottom White Section -->
              <div class="store-bottom-section">
                <!-- Supervisor Statistics -->
                <div class="supervisor-stats">
                  <div class="stats-label">
                    <i class='bx bx-user'></i>
                    <span>Supervisor Statistics</span>
                  </div>
                  
                  <div class="stats-grid">
                    <div class="stat-item male-stat">
                      <span class="stat-value"><?php echo $rowm['count'] ?></span>
                      <span class="stat-label">Male</span>
                    </div>
                    
                    <div class="stat-item female-stat">
                      <span class="stat-value"><?php echo $rowf['count'] ?></span>
                      <span class="stat-label">Female</span>
                    </div>
                    
                    <div class="stat-item total-stat">
                      <span class="stat-value"><?php echo $totalEmployees ?></span>
                      <span class="stat-label">Total</span>
                    </div>
                  </div>
                </div>
                
                <!-- Action Section -->
                <div class="action-section">
                  <div class="action-label">
                    <i class='bx bx-bar-chart-alt-2'></i>
                    <span>Evaluate Warehouse</span>
                  </div>
                  <a href="./store_step2.php?st=<?php echo $row['store_number'] ?>" class="action-btn">
                    <i class='bx bx-clipboard'></i>
                    <span>Start Evaluation</span>
                  </a>
                </div>
                
                <!-- View Button -->
                <a href="./store_step2.php?st=<?php echo $row['store_number'] ?>" class="view-btn">
                  <i class='bx bx-chevron-right'></i>
                </a>
              </div>
            </div>
          </div>
        <?php 
          }
        } else { 
        ?>
          <div class="col-12">
            <div class="empty-state">
              <div class="empty-state-icon">
                <i class='bx bx-store-alt'></i>
              </div>
              <h3 class="empty-state-title">No Stores Found</h3>
              <p class="empty-state-text">There are no stores available in the system. Please add stores to get started.</p>
            </div>
          </div>
        <?php } ?>
      </div>
    </div>

    <script>
      // Add hover effect and animation
      document.addEventListener('DOMContentLoaded', function() {
        const storeCards = document.querySelectorAll('.store-card-split');
        
        storeCards.forEach(card => {
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