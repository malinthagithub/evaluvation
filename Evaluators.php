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

  $sql = "SELECT * FROM evaluator";
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
    <title>Evaluators</title>
    <link rel="icon" type="image/x-icon" href="./img/jb.png">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta1/dist/css/bootstrap.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta1/dist/js/bootstrap.bundle.min.js" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.2.1/jquery.min.js" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/boxicons@latest/css/boxicons.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />

    <link rel="stylesheet" href="./style.css" />
    <script src="./script.js"></script>

    <style>
      :root {
        --primary-color: #4361ee;
        --success-color: #4cc9f0;
        --warning-color: #f72585;
        --danger-color: #f72585;
        --dark-color: #2b2d42;
        --light-color: #f8f9fa;
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

      .modern-container {
        padding: 30px;
        max-width: 1400px;
        margin: 0 auto;
      }

      .page-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 30px;
        padding: 20px 0;
        border-bottom: 1px solid #e2e8f0;
      }

      .page-title {
        font-size: 1.8rem;
        font-weight: 700;
        color: var(--dark-color);
        display: flex;
        align-items: center;
        gap: 12px;
      }

      .page-title i {
        color: var(--primary-color);
        font-size: 2rem;
      }

      .add-btn {
        background: linear-gradient(135deg, var(--primary-color), #3a56d4);
        color: white;
        border: none;
        padding: 12px 24px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        gap: 8px;
        font-weight: 600;
        transition: all 0.3s ease;
        text-decoration: none;
      }

      .add-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(67, 97, 238, 0.3);
        color: white;
      }

      .evaluators-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
        gap: 25px;
        margin-top: 20px;
      }

      .evaluator-card {
        background: white;
        border-radius: 16px;
        overflow: hidden;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        transition: all 0.3s ease;
        border: 1px solid #e2e8f0;
        position: relative;
      }

      .evaluator-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
        border-color: var(--primary-color);
      }

      .card-header {
        background: linear-gradient(135deg, var(--primary-color), #3a56d4);
        padding: 25px;
        text-align: center;
        position: relative;
        color: white;
      }

      .employee-badge {
        position: absolute;
        top: 15px;
        right: 15px;
        background: rgba(255, 255, 255, 0.2);
        color: white;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 0.85rem;
        font-weight: 500;
        backdrop-filter: blur(10px);
      }

      .avatar-container {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.2);
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 15px;
        font-size: 2.5rem;
        border: 3px solid rgba(255, 255, 255, 0.3);
      }

      .evaluator-name {
        font-size: 1.4rem;
        font-weight: 700;
        margin-bottom: 5px;
        line-height: 1.3;
      }

      .evaluator-id {
        font-size: 0.95rem;
        opacity: 0.9;
        font-weight: 500;
      }

      .card-content {
        padding: 25px;
      }

      .role-badge {
        display: inline-block;
        padding: 6px 15px;
        border-radius: 20px;
        font-size: 0.85rem;
        font-weight: 600;
        margin-bottom: 20px;
      }

      .Admin .role-badge {
        background: linear-gradient(135deg, #4361ee, #3a56d4);
        color: white;
      }

      .Evaluator .role-badge {
        background: linear-gradient(135deg, #4cc9f0, #4895ef);
        color: white;
      }

      .Guest .role-badge {
        background: linear-gradient(135deg, #94a3b8, #64748b);
        color: white;
      }

      .permissions-section {
        margin-bottom: 20px;
      }

      .section-title {
        font-size: 0.9rem;
        color: #64748b;
        font-weight: 600;
        margin-bottom: 12px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
      }

      .permissions-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 12px;
      }

      .permission-item {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 10px;
        background: #f8fafc;
        border-radius: 10px;
        transition: all 0.3s ease;
      }

      .permission-item:hover {
        background: #eef2ff;
        transform: translateX(5px);
      }

      .permission-icon {
        width: 30px;
        height: 30px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: white;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
      }

      .permission-active {
        color: #10b981;
      }

      .permission-inactive {
        color: #ef4444;
      }

      .permission-label {
        font-size: 0.9rem;
        font-weight: 500;
        color: var(--dark-color);
      }

      .gender-section {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 12px;
        background: #f8fafc;
        border-radius: 10px;
        margin-top: 15px;
      }

      .gender-icon {
        color: var(--primary-color);
        font-size: 1.2rem;
      }

      .gender-label {
        font-size: 0.95rem;
        color: var(--dark-color);
        font-weight: 500;
      }

      .card-actions {
        display: flex;
        gap: 10px;
        padding: 20px;
        border-top: 1px solid #e2e8f0;
        background: #f8fafc;
      }

      .action-btn {
        flex: 1;
        padding: 10px;
        border-radius: 8px;
        border: none;
        font-weight: 600;
        font-size: 0.9rem;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        text-decoration: none;
      }

      .edit-btn {
        background: linear-gradient(135deg, #4361ee, #3a56d4);
        color: white;
      }

      .edit-btn:hover {
        background: linear-gradient(135deg, #3a56d4, #2f48b9);
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(67, 97, 238, 0.3);
        color: white;
      }

      .delete-btn {
        background: linear-gradient(135deg, #f72585, #e01e74);
        color: white;
      }

      .delete-btn:hover {
        background: linear-gradient(135deg, #e01e74, #c81a66);
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(247, 37, 133, 0.3);
        color: white;
      }

      .empty-state {
        text-align: center;
        padding: 60px 20px;
        background: white;
        border-radius: 16px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        margin-top: 30px;
      }

      .empty-state i {
        font-size: 4rem;
        color: #cbd5e1;
        margin-bottom: 20px;
      }

      .empty-state h3 {
        color: #64748b;
        margin-bottom: 10px;
        font-size: 1.5rem;
      }

      .empty-state p {
        color: #94a3b8;
        max-width: 400px;
        margin: 0 auto 30px;
      }

      .floating-action-btn {
        position: fixed;
        bottom: 30px;
        right: 30px;
        width: 60px;
        height: 60px;
        border-radius: 50%;
        background: linear-gradient(135deg, var(--primary-color), #3a56d4);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.8rem;
        box-shadow: 0 4px 20px rgba(67, 97, 238, 0.3);
        transition: all 0.3s ease;
        z-index: 100;
        text-decoration: none;
      }

      .floating-action-btn:hover {
        transform: scale(1.1) rotate(90deg);
        box-shadow: 0 8px 30px rgba(67, 97, 238, 0.4);
        color: white;
      }

      .Admin .evaluator-card:hover {
        border-color: #4361ee;
      }

      .Evaluator .evaluator-card:hover {
        border-color: #4cc9f0;
      }

      .Guest .evaluator-card:hover {
        border-color: #94a3b8;
      }

      @media (max-width: 768px) {
        .modern-container {
          padding: 15px;
        }

        .page-header {
          flex-direction: column;
          gap: 15px;
          text-align: center;
        }

        .evaluators-grid {
          grid-template-columns: 1fr;
          gap: 20px;
        }

        .permissions-grid {
          grid-template-columns: 1fr;
        }
      }

      @media (max-width: 480px) {
        .card-actions {
          flex-direction: column;
        }

        .action-btn {
          width: 100%;
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
            <a href="./Evaluators.php" class="nav_link active"> <i class='bx bxs-user-detail nav_icon'></i> <span class="nav_name">Evaluators</span> </a>
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

    <div class="modern-container">
      <div class="page-header">
        <div class="page-title">
          <i class='bx bxs-user-detail'></i>
          Evaluators
        </div>
        <?php if ($_SESSION["isAdmin"]) { ?>
          <a href="./evaluator.php" class="add-btn">
            <i class='bx bx-plus'></i>
            Add New Evaluator
          </a>
        <?php } ?>
      </div>

      <?php if ($result->num_rows > 0) { ?>
        <div class="evaluators-grid">
          <?php while ($row = $result->fetch_assoc()) { 
            // Determine role
            $role = '';
            $roleClass = '';
            if ($row['is_admin']) {
              $role = 'Admin';
              $roleClass = 'Admin';
            } else if ($row['is_evaluator']) {
              $role = 'Evaluator';
              $roleClass = 'Evaluator';
            } else {
              $role = 'Guest';
              $roleClass = 'Guest';
            }
            
            // Get first letter for avatar
            $initials = strtoupper(substr($row['evaluator_name'], 0, 1));
          ?>
            <div class="evaluator-card">
              <div class="card-header">
                <span class="employee-badge">ID: <?php echo $row['evaluator_id']; ?></span>
                <div class="avatar-container">
                  <?php echo $initials; ?>
                </div>
                <h3 class="evaluator-name"><?php echo htmlspecialchars($row['evaluator_name']); ?></h3>
                <p class="evaluator-id">Employee No: <?php echo $row['evaluator_id']; ?></p>
              </div>

              <div class="card-content">
                <div class="<?php echo $roleClass; ?> role-badge">
                  <?php echo $role; ?>
                </div>

                <div class="permissions-section">
                  <div class="section-title">Permissions</div>
                  <div class="permissions-grid">
                    <div class="permission-item">
                      <div class="permission-icon">
                        <i class='bx <?php echo $row['PLE'] ? 'bx-check permission-active' : 'bx-x permission-inactive'; ?>'></i>
                      </div>
                      <span class="permission-label">PLE</span>
                    </div>
                    <div class="permission-item">
                      <div class="permission-icon">
                        <i class='bx <?php echo $row['OE'] ? 'bx-check permission-active' : 'bx-x permission-inactive'; ?>'></i>
                      </div>
                      <span class="permission-label">OE</span>
                    </div>
                    <div class="permission-item">
                      <div class="permission-icon">
                        <i class='bx <?php echo $row['QM'] ? 'bx-check permission-active' : 'bx-x permission-inactive'; ?>'></i>
                      </div>
                      <span class="permission-label">QM</span>
                    </div>
                    <div class="permission-item">
                      <div class="permission-icon">
                        <i class='bx <?php echo $row['PE'] ? 'bx-check permission-active' : 'bx-x permission-inactive'; ?>'></i>
                      </div>
                      <span class="permission-label">PE</span>
                    </div>
                  </div>
                </div>

                <div class="gender-section">
                  <i class='bx <?php echo $row['ev_gender'] == 'Male' ? 'bx-male' : 'bx-female'; ?> gender-icon'></i>
                  <span class="gender-label"><?php echo $row['ev_gender']; ?></span>
                </div>
              </div>

              <?php if ($_SESSION["isAdmin"] || $_SESSION["id"] == $row['evaluator_id']) { ?>
                <div class="card-actions">
                  <a href="./evaluator.php?id=<?php echo $row['evaluator_id'] ?>" class="action-btn edit-btn">
                    <i class='bx bx-pencil'></i>
                    Edit
                  </a>
                  <a href="./delete.php?evtId=<?php echo $row['evaluator_id'] ?>" class="action-btn delete-btn" onclick="return confirm('Are you sure you want to delete this evaluator?');">
                    <i class='bx bx-trash'></i>
                    Delete
                  </a>
                </div>
              <?php } ?>
            </div>
          <?php } ?>
        </div>
      <?php } else { ?>
        <div class="empty-state">
          <i class='bx bxs-user-detail'></i>
          <h3>No Evaluators Found</h3>
          <p>There are no evaluators configured yet. Add new evaluators to get started.</p>
          <?php if ($_SESSION["isAdmin"]) { ?>
            <a href="./evaluator.php" class="add-btn">
              <i class='bx bx-plus'></i>
              Add First Evaluator
            </a>
          <?php } ?>
        </div>
      <?php } ?>
    </div>

    <!-- Floating Action Button -->
    <?php if ($_SESSION["isAdmin"] && $result->num_rows > 0) { ?>
      <a href="./evaluator.php" class="floating-action-btn">
        <i class='bx bx-plus'></i>
      </a>
    <?php } ?>

    <script>
      // Add confirmation for delete action
      document.addEventListener('DOMContentLoaded', function() {
        const deleteButtons = document.querySelectorAll('.action-btn.delete-btn');
        deleteButtons.forEach(button => {
          button.addEventListener('click', function(e) {
            if (!confirm('Are you sure you want to delete this evaluator?')) {
              e.preventDefault();
            }
          });
        });
      });
    </script>
  </body>

  </html>

<?php } else {
  header("location: signin.php");
} ?>