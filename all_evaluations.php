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

  if (isset($_GET['id'])) {
    $id = $_GET['id'];

    $sql = "SELECT * FROM employee WHERE emp_id ='$id'";
    $result = $conn->query($sql);
    $row = $result->fetch_assoc();
?>

    <!DOCTYPE html>
    <html lang="en">

    <head>
      <meta charset="UTF-8">
      <meta http-equiv="X-UA-Compatible" content="IE=edge">
      <meta name="viewport" content="width=device-width, initial-scale=1.0">
      <title>All Evaluations</title>
      <link rel="icon" type="image/x-icon" href="./img/jb.png">

      <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta1/dist/css/bootstrap.min.css" />
      <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta1/dist/js/bootstrap.bundle.min.js" />
      <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.2.1/jquery.min.js" />
      <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/boxicons@latest/css/boxicons.min.css" />
      <!-- Font Awesome -->
      <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />

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
          margin-bottom: 10px;
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

        /* Print-specific styles */
        @media print {
          body * {
            visibility: hidden;
          }
          .print-area, .print-area * {
            visibility: visible;
          }
          .print-area {
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
            padding: 20px;
          }
          .no-print {
            display: none !important;
          }
          table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
          }
          th, td {
            border: 1px solid #000;
            padding: 6px;
            font-size: 12px;
          }
          .print-header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
          }
          .print-header h3 {
            margin: 5px 0;
          }
          .print-header p {
            margin: 3px 0;
          }
        }
        
        /* Print button styling */
        .print-btn-container {
          text-align: right;
          margin-bottom: 15px;
        }
        
        .print-btn {
          background: #4361ee;
          color: white;
          border: none;
          padding: 8px 16px;
          border-radius: 5px;
          cursor: pointer;
          font-size: 14px;
          display: inline-flex;
          align-items: center;
          gap: 5px;
          transition: background 0.3s;
        }
        
        .print-btn:hover {
          background: #3a56d4;
        }
        
        /* Employee info for print */
        .employee-print-info {
          display: none;
        }
        
        @media print {
          .employee-print-info {
            display: block;
          }
        }
        
        /* Filter active state */
        .active-filter {
          background-color: #4361ee !important;
          color: white !important;
          padding: 5px 10px;
          border-radius: 4px;
        }
        
        .filter-link {
          color: #ccf;
          text-decoration: none;
          padding: 5px 10px;
          border-radius: 4px;
          transition: background-color 0.3s;
        }
        
        .filter-link:hover {
          background-color: rgba(67, 97, 238, 0.1);
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

      <div class="height-100 container mt-5 mb-3" style="padding-top: 2vh;">
        <div class="card p-12 mb-12 <?php if ($_SESSION['isAdmin']) {
                                      echo "Admin";
                                    } else if ($_SESSION["isEvaluator"]) {
                                      echo "Evaluator";
                                    } else {
                                      echo "Guest";
                                    } ?>">
          <center>
            <h3><b><?php echo $row['emp_name'] ?></b></h3>
            <h5><b>Emp No: </b><?php echo $row['emp_id'] ?></h5>
            <p><?php echo $row['current_category'] ?></p>
            <p><?php echo $row['current_store'] ?></p>
          </center>
        </div>

        <!-- Print button -->
        <div class="print-btn-container">
          <button class="print-btn" onclick="printTable()">
            <i class='bx bx-printer'></i>
            Print Table
          </button>
        </div>

        <div class="col-md-12 print-area">
          <!-- Employee info for print (hidden on screen, shown when printing) -->
          <div class="employee-print-info print-header">
            <h2>Employee Evaluation Report</h2>
            <h3><b><?php echo $row['emp_name'] ?></b></h3>
            <h5><b>Emp No: </b><?php echo $row['emp_id'] ?></h5>
            <p><b>Category: </b><?php echo $row['current_category'] ?></p>
            <p><b>Store: </b><?php echo $row['current_store'] ?></p>
            <p><b>Report Generated on: </b><?php echo date('Y-m-d H:i:s'); ?></p>
          </div>

          <div class="card p-12 mb-12 <?php if ($_SESSION['isAdmin']) {
                                        echo "Admin";
                                      } else if ($_SESSION["isEvaluator"]) {
                                        echo "Evaluator";
                                      } else {
                                        echo "Guest";
                                      } ?>" style="margin-top: 2%;">
            <table style="margin: 8px; border: 1px solid #666; text-align: center;">
              <tr style="border: 1px solid #666;">
                <th style="border: 1px solid #666;">
                  <a class="filter-link <?php echo (isset($_GET['filter']) && $_GET['filter'] == 'Time') ? 'active-filter' : '' ?>" 
                     href="./all_evaluations.php?id=<?php echo $id ?><?php if (isset($_GET['sp']) && isset($_GET['ep'])) {
                                                                        $sp = $_GET['sp'];
                                                                        $ep = $_GET['ep'];
                                                                        echo "&sp=" . $sp . "&ep=" . $ep;
                                                                      } ?>&filter=Time">Time</a>
                </th>
                <th style="border: 1px solid #666;">
                  <a class="filter-link <?php echo (isset($_GET['filter']) && $_GET['filter'] == 'Period') ? 'active-filter' : '' ?>" 
                     href="./all_evaluations.php?id=<?php echo $id ?><?php if (isset($_GET['sp']) && isset($_GET['ep'])) {
                                                                        $sp = $_GET['sp'];
                                                                        $ep = $_GET['ep'];
                                                                        echo "&sp=" . $sp . "&ep=" . $ep;
                                                                      } ?>&filter=Period">Period</a>
                </th>
                <th style="border: 1px solid #666;">
                  <a class="filter-link <?php echo (isset($_GET['filter']) && $_GET['filter'] == 'Evaluator') ? 'active-filter' : '' ?>" 
                     href="./all_evaluations.php?id=<?php echo $id ?><?php if (isset($_GET['sp']) && isset($_GET['ep'])) {
                                                                        $sp = $_GET['sp'];
                                                                        $ep = $_GET['ep'];
                                                                        echo "&sp=" . $sp . "&ep=" . $ep;
                                                                      } ?>&filter=Evaluator">Evaluator</a>
                </th>
                <th style="border: 1px solid #666;">
                  <a class="filter-link <?php echo (isset($_GET['filter']) && $_GET['filter'] == 'Attribute') ? 'active-filter' : '' ?>" 
                     href="./all_evaluations.php?id=<?php echo $id ?><?php if (isset($_GET['sp']) && isset($_GET['ep'])) {
                                                                        $sp = $_GET['sp'];
                                                                        $ep = $_GET['ep'];
                                                                        echo "&sp=" . $sp . "&ep=" . $ep;
                                                                      } ?>&filter=Attribute">Attribute</a>
                </th>
                <th style="border: 1px solid #666;">Category</th>
                <th style="border: 1px solid #666;">Store</th>
                <th style="border: 1px solid #666;">Positive</th>
                <!-- <th style="border: 1px solid #666;">Neutral</th> -->
                <th style="border: 1px solid #666;">Negative</th>
                <th style="border: 1px solid #666;">value</th>
                <th style="border: 1px solid #666;">Comment</th>
                <?php if ($_SESSION["isAdmin"]) { ?>
                  <th style="border: 1px solid #666;" class="no-print">Actions</th>
                <?php } ?>
              </tr>
              <?php
              $empcat = $row['current_category'];
              $evaluator = $_SESSION["id"];
              $year = date('Y');
              if (isset($_GET['sp']) && isset($_GET['ep'])) {
                $sp = $_GET['sp'];
                $ep = $_GET['ep'];
                if (isset($_GET['filter']) && $_GET['filter'] == "Time") {
                  // Time filter: Sort by date (oldest first) then by time
                  $sql = "SELECT * FROM ((records R JOIN attribute A ON R.attribute_id = A.attribute_id) JOIN evaluator V ON R.evaluator_id = V.evaluator_id) WHERE R.emp_id = '$id' AND period BETWEEN '$sp' AND '$ep' ORDER BY DATE(R.time) ASC, TIME(R.time) ASC;";
                  $result = $conn->query($sql);
                } else if (isset($_GET['filter']) && $_GET['filter'] == "Evaluator") {
                  // Evaluator filter: Sort by evaluator name (alphabetical) then by date
                  $sql = "SELECT * FROM ((records R JOIN attribute A ON R.attribute_id = A.attribute_id) JOIN evaluator V ON R.evaluator_id = V.evaluator_id) WHERE R.emp_id = '$id' AND period BETWEEN '$sp' AND '$ep' ORDER BY V.evaluator_name ASC, DATE(R.time) ASC, TIME(R.time) ASC;";
                  $result = $conn->query($sql);
                } else if (isset($_GET['filter']) && $_GET['filter'] == "Attribute") {
                  $sql = "SELECT * FROM ((records R JOIN attribute A ON R.attribute_id = A.attribute_id) JOIN evaluator V ON R.evaluator_id = V.evaluator_id) WHERE R.emp_id = '$id' AND period BETWEEN '$sp' AND '$ep' ORDER BY A.attribute_name ASC, DATE(R.time) ASC, TIME(R.time) ASC;";
                  $result = $conn->query($sql);
                } else {
                  // Period filter: Sort by period (oldest first) then by date (oldest first)
                  $sql = "SELECT * FROM ((records R JOIN attribute A ON R.attribute_id = A.attribute_id) JOIN evaluator V ON R.evaluator_id = V.evaluator_id) WHERE R.emp_id = '$id' AND period BETWEEN '$sp' AND '$ep' ORDER BY period ASC, DATE(R.time) ASC, TIME(R.time) ASC;";
                  $result = $conn->query($sql);
                }
              } else {
                if (isset($_GET['filter']) && $_GET['filter'] == "Time") {
                  // Time filter: Sort by date (oldest first) then by time
                  $sql = "SELECT * FROM ((records R JOIN attribute A ON R.attribute_id = A.attribute_id) JOIN evaluator V ON R.evaluator_id = V.evaluator_id) WHERE R.emp_id = '$id' AND period LIKE '$year%' ORDER BY DATE(R.time) ASC, TIME(R.time) ASC;";
                  $result = $conn->query($sql);
                } else if (isset($_GET['filter']) && $_GET['filter'] == "Evaluator") {
                  // Evaluator filter: Sort by evaluator name (alphabetical) then by date
                  $sql = "SELECT * FROM ((records R JOIN attribute A ON R.attribute_id = A.attribute_id) JOIN evaluator V ON R.evaluator_id = V.evaluator_id) WHERE R.emp_id = '$id' AND period LIKE '$year%' ORDER BY V.evaluator_name ASC, DATE(R.time) ASC, TIME(R.time) ASC;";
                  $result = $conn->query($sql);
                } else if (isset($_GET['filter']) && $_GET['filter'] == "Attribute") {
                  $sql = "SELECT * FROM ((records R JOIN attribute A ON R.attribute_id = A.attribute_id) JOIN evaluator V ON R.evaluator_id = V.evaluator_id) WHERE R.emp_id = '$id' AND period LIKE '$year%' ORDER BY A.attribute_name ASC, DATE(R.time) ASC, TIME(R.time) ASC;";
                  $result = $conn->query($sql);
                } else {
                  // Default: Period filter - Sort by period (oldest first) then by date (oldest first)
                  $sql = "SELECT * FROM ((records R JOIN attribute A ON R.attribute_id = A.attribute_id) JOIN evaluator V ON R.evaluator_id = V.evaluator_id) WHERE R.emp_id = '$id' AND period LIKE '$year%' ORDER BY period ASC, DATE(R.time) ASC, TIME(R.time) ASC;";
                  $result = $conn->query($sql);
                }
              }
              while ($row = $result->fetch_assoc()) {
              ?>
                <tr style="border: 1px solid #666;">
                  <td style="border: 1px solid #666;"><?php echo $row['time'] ?></td>
                  <td style="border: 1px solid #666;"><?php echo $row['period'] ?></td>
                  <td style="border: 1px solid #666;"><?php echo $row['evaluator_name'] ?></td>
                  <td style="border: 1px solid #666; text-align:left; padding-left: 10px;"><?php echo $row['attribute_name'] ?></td>
                  <td style="border: 1px solid #666;"><?php if ($row['category'] == 'Common') {
                                                        echo $row['category'];
                                                      } else {
                                                        echo $empcat;
                                                      } ?></td>
                  <td style="border: 1px solid #666;"><?php echo $row['store'] ?></td>
                  <td style="border: 1px solid #666;"><?php echo $row['positive'] ?></td>
                  <!-- <td style="border: 1px solid #666;"><?php echo $row['neutral'] ?></td> -->
                  <td style="border: 1px solid #666;"><?php echo $row['negative'] ?></td>
                  <td style="border: 1px solid #666;"><?php echo $row['value'] ?></td>
                  <td style="text-align: left; padding-left: 5px; border: 1px solid #666;"><?php echo $row['comment'] ?></td>
                  <?php if ($_SESSION["isAdmin"]) { ?>
                    <td style="border: 1px solid #666;" class="no-print"><b><a style="color: #f00;" href="./delete.php?evlId=<?php echo $row['record_id'] ?>"> <span style="width: 70px; padding-bottom: 3px;"><i class='bx bx-trash'></i>&nbsp;Delete</span></a></b></td>
                  <?php } ?>
                </tr>
              <?php } ?>
            </table>
            </a>
          </div>
        </div>
      </div>
      <div class="floating-container no-print">
        <div class="floating-button <?php if ($_SESSION['isAdmin']) {
                                      echo "Admin";
                                    } else if ($_SESSION["isEvaluator"]) {
                                      echo "Evaluator";
                                    } else {
                                      echo "Guest";
                                    } ?>"><a href="./Rankings.php"><i class='bx bx-crown'></i></a></div>
      </div>
      
      <script>
        function printTable() {
          window.print();
        }
      </script>
    </body>

    </html>

<?php }
} else {
  header("location: signin.php");
} ?>