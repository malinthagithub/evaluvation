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
        color: black;
        font-size: 1rem;
        margin-bottom: 0;
      }

      /* Modern Store Cards */
      .store-card {
        background: white;
        border-radius: var(--radius-xl);
        padding: 1.75rem;
        height: 100%;
        border: none;
        box-shadow: var(--shadow-md);
        transition: var(--transition);
        position: relative;
        overflow: hidden;
        text-decoration: none !important;
        display: block;
        color: inherit;
      }

      .store-card:hover {
        transform: translateY(-8px);
        box-shadow: var(--shadow-lg);
        text-decoration: none;
        color: inherit;
      }

      .store-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(90deg, var(--primary-color), var(--secondary-color));
        border-radius: var(--radius-xl) var(--radius-xl) 0 0;
      }

      .store-card.Admin::before {
        background: linear-gradient(90deg, #667eea, #764ba2);
      }

      .store-card.Evaluator::before {
        background: linear-gradient(90deg, #4CAF50, #2E7D32);
      }

      .store-card.Guest::before {
        background: linear-gradient(90deg, #757575, #424242);
      }

      .store-category {
        display: inline-block;
        padding: 0.4rem 1rem;
        background: var(--primary-light);
        color: var(--primary-color);
        border-radius: var(--radius-md);
        font-size: 0.85rem;
        font-weight: 600;
        margin-bottom: 1.5rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
      }

      .store-card.Admin .store-category {
        background: rgba(102, 126, 234, 0.1);
        color: #667eea;
      }

      .store-card.Evaluator .store-category {
        background: rgba(76, 175, 80, 0.1);
        color: #4CAF50;
      }

      .store-card.Guest .store-category {
        background: rgba(117, 117, 117, 0.1);
        color: #757575;
      }

      .store-number {
        font-size: 2.25rem;
        font-weight: 800;
        color: var(--dark-color);
        margin-bottom: 0.5rem;
        line-height: 1.2;
      }

      .store-label {
        font-size: 0.9rem;
        color: var(--gray-color);
        text-transform: uppercase;
        letter-spacing: 0.5px;
        font-weight: 600;
        margin-bottom: 1.5rem;
        display: block;
      }

      /* Employee Stats */
      .employee-stats {
        margin-top: 1.5rem;
        padding-top: 1.5rem;
        border-top: 1px solid var(--border-color);
      }

      .stats-header {
        font-size: 0.9rem;
        font-weight: 600;
        color: var(--dark-color);
        margin-bottom: 1rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
      }

      .stats-header i {
        font-size: 1rem;
      }

      .stats-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 1rem;
      }

      .stat-item {
        text-align: center;
      }

      .stat-value {
        font-size: 1.25rem;
        font-weight: 700;
        color: var(--dark-color);
        display: block;
        line-height: 1.2;
      }

      .stat-label {
        font-size: 0.75rem;
        color: var(--gray-color);
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-top: 0.25rem;
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

      /* Store Icon */
      .store-icon {
        width: 60px;
        height: 60px;
        border-radius: var(--radius-lg);
        background: linear-gradient(135deg, var(--primary-light), rgba(67, 97, 238, 0.1));
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 1.5rem;
        font-size: 1.8rem;
        color: var(--primary-color);
      }

      .store-card.Admin .store-icon {
        background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1));
        color: #667eea;
      }

      .store-card.Evaluator .store-icon {
        background: linear-gradient(135deg, rgba(76, 175, 80, 0.1), rgba(46, 125, 50, 0.1));
        color: #4CAF50;
      }

      .store-card.Guest .store-icon {
        background: linear-gradient(135deg, rgba(117, 117, 117, 0.1), rgba(66, 66, 66, 0.1));
        color: #757575;
      }

      /* Action Button */
      .store-action {
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
      }

      .store-card:hover .store-action {
        opacity: 1;
        transform: translateY(0);
      }

      .store-card.Admin .store-action {
        background: #667eea;
      }

      .store-card.Evaluator .store-action {
        background: #4CAF50;
      }

      .store-card.Guest .store-action {
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
        .store-card {
          padding: 1.5rem;
        }
        
        .store-number {
          font-size: 2rem;
        }
        
        .stats-grid {
          grid-template-columns: repeat(3, 1fr);
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
        
        .store-card {
          margin-bottom: 1.5rem;
        }
        
        .store-action {
          opacity: 1;
          transform: translateY(0);
        }
      }

      @media (max-width: 576px) {
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
        
        .stat-label {
          margin-top: 0;
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

      .store-card {
        animation: fadeInUp 0.5s ease-out;
      }

      .store-card:nth-child(1) { animation-delay: 0.1s; }
      .store-card:nth-child(2) { animation-delay: 0.2s; }
      .store-card:nth-child(3) { animation-delay: 0.3s; }
      .store-card:nth-child(4) { animation-delay: 0.4s; }
      .store-card:nth-child(5) { animation-delay: 0.5s; }
      .store-card:nth-child(6) { animation-delay: 0.6s; }
      .store-card:nth-child(7) { animation-delay: 0.7s; }
      .store-card:nth-child(8) { animation-delay: 0.8s; }
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
            <a href="./Ratings.php?id=<?php echo $row['store_number'] ?>" class="text-decoration-none">
              <div class="store-card <?php if ($_SESSION['isAdmin']) {
                                          echo "Admin";
                                        } else if ($_SESSION["isEvaluator"]) {
                                          echo "Evaluator";
                                        } else {
                                          echo "Guest";
                                        } ?>">
                
                <!-- Store Icon -->
                <div class="store-icon">
                  <i class='bx bx-store'></i>
                </div>
                
                <!-- Store Category -->
                <div class="store-category">
                  <?php echo $row['category_name'] ?>
                </div>
                
                <!-- Store Number -->
                <div class="store-number">
                  Store <?php echo $row['store_number'] ?>
                </div>
                
                <div class="store-label">
                  Warehouse Location
                </div>
                
                <!-- Employee Statistics -->
                <div class="employee-stats">
                  <div class="stats-header">
                    <i class='bx bx-user'></i>
                    <span>Employee Statistics</span>
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
                
                <!-- Action Button -->
                <div class="store-action">
                  <i class='bx bx-chevron-right'></i>
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
        const storeCards = document.querySelectorAll('.store-card');
        
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