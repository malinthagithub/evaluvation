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

  $sql = "SELECT * FROM employee";
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
    <title>Employees</title>
    <link rel="icon" type="image/x-icon" href="./img/jb.png">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta1/dist/css/bootstrap.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta1/dist/js/bootstrap.bundle.min.js" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.2.1/jquery.min.js" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/boxicons@latest/css/boxicons.min.css" />

    <link rel="stylesheet" href="./style.css" />
    <script src="./script.js"></script>

    <style>
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

      /* Modern Card Styles */
      .modern-card {
        border: none;
        border-radius: 16px;
        overflow: hidden;
        transition: all 0.3s ease;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        position: relative;
        height: 100%;
        margin-bottom: 24px;
      }
      
      .modern-card:hover {
        transform: translateY(-8px);
        box-shadow: 0 12px 24px rgba(0, 0, 0, 0.15);
      }
      
      .card-header-gradient {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 1.5rem;
        position: relative;
        overflow: hidden;
      }
      
      .card-header-gradient::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: linear-gradient(45deg, rgba(255,255,255,0.1) 0%, transparent 100%);
      }
      
      .employee-avatar {
        width: 70px;
        height: 70px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.2);
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 1rem;
        font-size: 1.8rem;
        border: 3px solid rgba(255, 255, 255, 0.3);
      }
      
      .employee-name {
        font-size: 1.3rem;
        font-weight: 700;
        margin-bottom: 0.5rem;
        text-align: center;
      }
      
      .employee-id {
        font-size: 0.9rem;
        opacity: 0.9;
        text-align: center;
        margin-bottom: 0.5rem;
      }
      
      .employee-gender {
        display: inline-block;
        padding: 0.2rem 0.8rem;
        background: rgba(255, 255, 255, 0.2);
        border-radius: 20px;
        font-size: 0.8rem;
        margin: 0 auto 1rem;
        text-align: center;
      }
      
      .card-body-modern {
        padding: 1.5rem;
        background: white;
      }
      
      .details-section {
        text-align: center;
        margin-bottom: 1.5rem;
      }
      
      .details-label {
        font-size: 0.85rem;
        color: #6c757d;
        margin-bottom: 0.3rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
      }
      
      .details-value {
        font-size: 1.1rem;
        font-weight: 600;
        color: #212529;
        margin-bottom: 0.5rem;
      }
      
      .status-badge {
        position: absolute;
        top: 1rem;
        right: 1rem;
        padding: 0.3rem 0.8rem;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        z-index: 1;
      }
      
      .status-active {
        background: rgba(61, 40, 167, 0.15);
        color: #041006ff;
      }
      
      .status-inactive {
        background: rgba(255, 193, 7, 0.15);
        color: #ffc107;
      }
      
      .card-actions-modern {
        display: flex;
        justify-content: space-between;
        padding: 1rem 1.5rem;
        background: #f8f9fa;
        border-top: 1px solid #e9ecef;
      }
      
      .action-btn-modern {
        padding: 0.5rem 0.8rem;
        border-radius: 8px;
        font-weight: 600;
        font-size: 0.85rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s ease;
        text-decoration: none;
        border: none;
        cursor: pointer;
      }
      
      .action-btn-modern i {
        margin-right: 5px;
        font-size: 1rem;
      }
      
      .btn-edit-modern {
        background: #007bff;
        color: white;
      }
      
      .btn-edit-modern:hover {
        background: #0056b3;
        color: white;
        transform: translateY(-2px);
      }
      
      .btn-delete-modern {
        background: #dc3545;
        color: white;
      }
      
      .btn-delete-modern:hover {
        background: #c82333;
        color: white;
        transform: translateY(-2px);
      }
      
      .btn-enable-modern {
        background: #28a745;
        color: white;
      }
      
      .btn-enable-modern:hover {
        background: #218838;
        color: white;
        transform: translateY(-2px);
      }
      
      .btn-disable-modern {
        background: #ffc107;
        color: #212529;
      }
      
      .btn-disable-modern:hover {
        background: #e0a800;
        color: #212529;
        transform: translateY(-2px);
      }
      
      /* Style based on user role */
      .modern-card.Admin .card-header-gradient {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      }
      
      .modern-card.Evaluator .card-header-gradient {
        background: linear-gradient(135deg, #4CAF50 0%, #2E7D32 100%);
      }
      
      .modern-card.Guest .card-header-gradient {
        background: linear-gradient(135deg, #757575 0%, #424242 100%);
      }
      
      /* Empty state */
      .empty-state {
        text-align: center;
        padding: 4rem 2rem;
        background: white;
        border-radius: 15px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
      }
      
      .empty-state-icon {
        font-size: 4rem;
        color: #e9ecef;
        margin-bottom: 1.5rem;
      }
      
      .empty-state-title {
        font-size: 1.5rem;
        font-weight: 600;
        margin-bottom: 0.5rem;
        color: #6c757d;
      }

      /* Floating button */
      .floating-container {
        position: fixed;
        bottom: 30px;
        right: 30px;
        z-index: 1000;
      }

      .floating-button {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.8rem;
        color: white;
        text-decoration: none;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
        transition: all 0.3s ease;
      }

      .floating-button.Admin {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      }

      .floating-button.Evaluator {
        background: linear-gradient(135deg, #4CAF50 0%, #2E7D32 100%);
      }

      .floating-button.Guest {
        background: linear-gradient(135deg, #757575 0%, #424242 100%);
      }

      .floating-button:hover {
        transform: scale(1.1);
        box-shadow: 0 6px 25px rgba(0, 0, 0, 0.3);
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
            <a href="./Employees.php" class="nav_link active"> <i class='bx bx-user nav_icon'></i><span class="nav_name">Evaluatees</span> </a>
            
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
      <?php if ($result->num_rows > 0) { ?>
        <div class="row">
          <?php while ($row = $result->fetch_assoc()) { 
            $isActive = !empty($row['current_store']) && !empty($row['current_category']);
            ?>
            <div class="col-md-3">
              <div class="modern-card <?php if ($_SESSION['isAdmin']) {
                                          echo "Admin";
                                        } else if ($_SESSION["isEvaluator"]) {
                                          echo "Evaluator";
                                        } else {
                                          echo "Guest";
                                        } ?>">
                
                <!-- Status Badge -->
                <div class="status-badge <?php echo $isActive ? 'status-active' : 'status-inactive'; ?>">
                  <?php echo $isActive ? 'Active' : 'Inactive'; ?>
                </div>
                
                <!-- Card Header with Employee Info -->
                <div class="card-header-gradient">
                  <div class="employee-avatar">
                    <i class='bx bxs-user'></i>
                  </div>
                  <h3 class="employee-name"><?php echo $row['emp_name'] ?></h3>
                  <div class="employee-id">ID: <?php echo $row['emp_id'] ?></div>
                  <div class="employee-gender"><?php echo $row['gender'] ?></div>
                </div>
                
                <!-- Card Body with Details -->
                <div class="card-body-modern">
                  <div class="details-section">
                    <div class="details-label">Current Store</div>
                    <div class="details-value">
                      <?php echo !empty($row['current_store']) ? 'Store ' . $row['current_store'] : 'Not Assigned'; ?>
                    </div>
                  </div>
                  
                  <div class="details-section">
                    <div class="details-label">Current Category</div>
                    <div class="details-value">
                      <?php echo !empty($row['current_category']) ? $row['current_category'] : 'Not Assigned'; ?>
                    </div>
                  </div>
                  
                  <!-- If evaluator, show evaluate button -->
                  <?php if ($_SESSION["isEvaluator"] && $isActive) { ?>
                   
                  <?php } ?>
                </div>
                
                <!-- Admin Actions -->
                <?php if ($_SESSION["isAdmin"]) { ?>
                  <div class="card-actions-modern">
                    <a href="./employee.php?id=<?php echo $row['emp_id'] ?>" class="action-btn-modern btn-edit-modern">
                      <i class='bx bx-pencil'></i> Edit
                    </a>
                    
                    <?php if ($isActive) { ?>
                      <a href="./delete.php?disId=<?php echo $row['emp_id'] ?>" class="action-btn-modern btn-disable-modern">
                        <i class='bx bx-low-vision'></i> Disable
                      </a>
                    <?php } else { ?>
                      <a href="./employee.php?id=<?php echo $row['emp_id'] ?>" class="action-btn-modern btn-enable-modern">
                        <i class='bx bx-check-circle'></i> Enable
                      </a>
                    <?php } ?>
                    
                    <a href="./delete.php?empId=<?php echo $row['emp_id'] ?>" class="action-btn-modern btn-delete-modern" onclick="return confirm('Are you sure you want to delete this employee?');">
                      <i class='bx bx-trash'></i> Delete
                    </a>
                  </div>
                <?php } ?>
              </div>
            </div>
          <?php } ?>
        </div>
      <?php } else { ?>
        <!-- Empty State -->
        <div class="empty-state">
          <div class="empty-state-icon">
            <i class='bx bx-user-x'></i>
          </div>
          <h3 class="empty-state-title">No Employees Found</h3>
          <p class="text-muted">There are no employees in the system yet.</p>
          
          <?php if ($_SESSION["isAdmin"]) { ?>
            <a href="./employee.php" class="btn btn-primary mt-3">
              <i class='bx bx-plus me-2'></i>
              Add First Employee
            </a>
          <?php } ?>
        </div>
      <?php } ?>

    </div>
    
    <?php if ($_SESSION["isAdmin"]) { ?>
      <div class="floating-container">
        <a href="./employee.php" class="floating-button <?php if ($_SESSION['isAdmin']) {
                                      echo "Admin";
                                    } else if ($_SESSION["isEvaluator"]) {
                                      echo "Evaluator";
                                    } else {
                                      echo "Guest";
                                    } ?>">
          <i class='bx bx-plus'></i>
        </a>
      </div>
    <?php } ?>
    
    <script>
      // Add animation to cards
      document.addEventListener('DOMContentLoaded', function() {
        const cards = document.querySelectorAll('.modern-card');
        cards.forEach((card, index) => {
          setTimeout(() => {
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
          }, index * 100);
        });
      });
    </script>
  </body>

  </html>

<?php } else {
  header("location: signin.php");
} ?>