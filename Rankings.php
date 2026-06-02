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

// Cache array for scoring methods
$scoringMethodCache = array();

if (isset($_SESSION["user"])) {

  if (isset($_GET['id'])) {
    $store = $_GET['id'];
    $sql = "SELECT * FROM employee WHERE current_store = '$store'";
  } else {
    $sql = "SELECT * FROM employee WHERE current_store <> ''";
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
    <title>Rankings</title>
    <link rel="icon" type="image/x-icon" href="./img/jb.png">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta1/dist/css/bootstrap.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta1/dist/js/bootstrap.bundle.min.js" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.2.1/jquery.min.js" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/boxicons@latest/css/boxicons.min.css" />

    <link rel="stylesheet" href="./style.css" />
    <script src="./script.js"></script>
    
    <!-- Additional CSS for disabled links and freeze button -->
    <style>
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
      
      /* Store History Badge */
      .history-badge {
        display: inline-block;
        padding: 0.25rem 0.75rem;
        background: #eef2ff;
        color: #4361ee;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 600;
        margin: 0.2rem;
      }
      
      .history-badge i {
        font-size: 0.9rem;
        margin-right: 0.3rem;
      }
      
      .history-badge.current {
        background: #4361ee;
        color: white;
      }
      
      /* Store info in table */
      .store-history-info {
        font-size: 0.8rem;
        color: #666;
        margin-top: 3px;
      }
      
      .store-tooltip {
        position: relative;
        display: inline-block;
        cursor: help;
      }
      
      .store-tooltip .tooltip-text {
        visibility: hidden;
        width: 200px;
        background-color: #555;
        color: #fff;
        text-align: center;
        border-radius: 6px;
        padding: 5px;
        position: absolute;
        z-index: 1;
        bottom: 125%;
        left: 50%;
        margin-left: -100px;
        opacity: 0;
        transition: opacity 0.3s;
        font-size: 12px;
        white-space: normal;
      }
      
      .store-tooltip:hover .tooltip-text {
        visibility: visible;
        opacity: 1;
      }
      
      /* Store info badge for multiple stores */
      .multi-store-badge {
        display: inline-block;
       
        color: white;
        font-size: 0.7rem;
        padding: 0.1rem 0.3rem;
        border-radius: 10px;
        margin-left: 0.3rem;
      }
      
      /* Primary store badge */
      .primary-store-badge {
        display: inline-block;
       
        color: white;
        font-size: 0.8rem;
        padding: 0.2rem 0.5rem;
        border-radius: 15px;
        margin-left: 0.3rem;
      }
    </style>
    
    <script>
      function printSpecificSection(idOfContentToPrint) {
        var contentToPrint = document.getElementById(idOfContentToPrint);

        var printWindow = window.open('', '_blank');

        printWindow.document.write('<html><head><title>Print</title></head><body>');
        printWindow.document.write(contentToPrint.innerHTML);
        printWindow.document.write('</body></html>');

        printWindow.document.close();

        printWindow.focus();
        printWindow.print();

        printWindow.close();
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
        <div> <a href="./index.php" class="nav_logo" style="color: #ffffff; font-weight: bold;"> JB <span class="nav_logo-name" style="font-weight: normal;">Employee Evaluation</span> </a>
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
            
            <a href="./periodRatings.php" class="nav_link"> <i class='bx bxs-star-half'></i> <span class="nav_name">Results & Grading</span> </a>
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
                                                                } ?>" style="background-color: #666; border: none; width: 100%;"> <i class='bx bx-log-out nav_icon'></i> <span class="nav_name">SignOut</span> </button>
          </form>
        </div>
      </nav>
    </div>
    
    <center id="ptr">
      <h2 style="color: #000;">
        <?php if (isset($_GET['sp']) && isset($_GET['ep'])) {
          echo "Ranking - " . $_GET['sp'] . " to " . $_GET['ep'] . "<br>";
        } else {
          echo "Annual Ranking - " . date('Y') . "<br>";
        } ?>
        Employee Evaluation Scheme<br>
        Jafferjee Brothers Tea Division
      </h2>
      
      <div class="height-100 container mt-5 mb-3">
        <?php if ($result->num_rows > 0) {
          $emp[$result->num_rows] = array();
          $emp[$result->num_rows][8] = array();
          $i = 0;

          // Function to get store history for an employee (for display only)
          function getEmployeeStoreHistory($emp_id, $conn) {
            $history_sql = "SELECT * FROM employee_store_history WHERE emp_id = '$emp_id' ORDER BY year DESC";
            $history_result = $conn->query($history_sql);
            $history_years = array();
            while ($history_row = $history_result->fetch_assoc()) {
                $history_years[$history_row['year']] = $history_row['store'];
            }
            return $history_years;
          }

          // ===== NEW FUNCTION: Get primary store for the evaluation period =====
          function getPrimaryStoreForPeriod($emp_id, $sp, $ep, $conn) {
            // Get the most frequent store during this period
            $store_query = "SELECT store, COUNT(*) as count 
                           FROM evaluation 
                           WHERE emp_id = '$emp_id' 
                           AND period BETWEEN '$sp' AND '$ep'
                           GROUP BY store 
                           ORDER BY count DESC 
                           LIMIT 1";
            $store_result = $conn->query($store_query);
            
            if ($store_result && $store_result->num_rows > 0) {
              $store_row = $store_result->fetch_assoc();
              return $store_row['store'];
            }
            
            // If no evaluations found, return null
            return null;
          }

          // ===== NEW FUNCTION: Get store breakdown for the period =====
          function getStoreBreakdown($emp_id, $sp, $ep, $conn) {
            $store_query = "SELECT store, COUNT(DISTINCT period) as months 
                           FROM evaluation 
                           WHERE emp_id = '$emp_id' 
                           AND period BETWEEN '$sp' AND '$ep'
                           GROUP BY store 
                           ORDER BY months DESC";
            $store_result = $conn->query($store_query);
            $stores = array();
            while ($store_row = $store_result->fetch_assoc()) {
              $stores[] = array(
                'store' => $store_row['store'],
                'months' => $store_row['months']
              );
            }
            return $stores;
          }

          // ===== FIXED: Updated grade function with history table and Attribute 32 special logic =====
          function grade($id, $year, $conn, &$scoringMethodCache)
          {
            $sqlev = "SELECT * FROM evaluation WHERE emp_id = '$id' AND period LIKE '$year%'";
            $resultev = $conn->query($sqlev);

            $sqlc = "SELECT COUNT(DISTINCT period) AS 'count' FROM evaluation WHERE emp_id = '$id' AND period LIKE '$year%'";
            $resultc = $conn->query($sqlc);
            
            if ($resultc && $resultc->num_rows > 0) {
              $rowc = $resultc->fetch_assoc();
              $count = $rowc['count'];
            } else {
              $count = 0;
            }

            $total_score = 0;
            if ($resultev && $resultev->num_rows > 0) {
              while ($rowev = $resultev->fetch_assoc()) {
                $attrId = $rowev['attribute_id'];
                
                // Get the year from evaluation period
                $evalPeriod = $rowev['period'];
                $evalYear = date('Y', strtotime($evalPeriod));
                
                // Create cache key using attribute_id and year
                $cacheKey = $attrId . '_' . $evalYear;
                
                // Check if scoring method is in cache
                if (!isset($scoringMethodCache[$cacheKey])) {
                  // FIRST: Try to get scoring method from history table
                  $sqlHistory = "SELECT ash.scoring_method_id, sm.* 
                                 FROM attribute_scoring_history ash
                                 INNER JOIN scoring_method sm ON ash.scoring_method_id = sm.sm_id
                                 WHERE ash.attribute_id = '$attrId' 
                                 AND ash.year = '$evalYear'";
                  $resultHistory = $conn->query($sqlHistory);
                  
                  if ($resultHistory && $resultHistory->num_rows > 0) {
                    $scoringMethodCache[$cacheKey] = $resultHistory->fetch_assoc();
                  } else {
                    // FALLBACK: Get attribute's current scoring method
                    $sqlat = "SELECT scoring_method, weightage FROM attribute WHERE attribute_id = '$attrId'";
                    $resultat = $conn->query($sqlat);
                    if ($resultat && $resultat->num_rows > 0) {
                      $rowat_fallback = $resultat->fetch_assoc();
                      $sm = $rowat_fallback['scoring_method'];
                      
                      $sqlsm = "SELECT * FROM scoring_method WHERE sm_name = '$sm' AND year = '$evalYear'";
                      $resultsm = $conn->query($sqlsm);
                      
                      if ($resultsm && $resultsm->num_rows == 0) {
                        $sqlsm = "SELECT * FROM scoring_method WHERE sm_name = '$sm' ORDER BY year DESC LIMIT 1";
                        $resultsm = $conn->query($sqlsm);
                      }
                      
                      if ($resultsm && $resultsm->num_rows > 0) {
                        $scoringMethodCache[$cacheKey] = $resultsm->fetch_assoc();
                      } else {
                        $scoringMethodCache[$cacheKey] = null;
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
                
                // Get attribute weightage
                $sqlat = "SELECT weightage FROM attribute WHERE attribute_id = '$attrId'";
                $resultat = $conn->query($sqlat);
                $rowat = $resultat->fetch_assoc();

                // Calculate score for this evaluation
                if (isset($rowev['status']) && $rowev['status'] < 0) {
                  if ($rowev['status'] == -1) {
                    $score = $rowat['weightage'] * 3 / 5;
                  } else if ($rowev['status'] == -2) {
                    $score = 0;
                  } else {
                    $score = 0;
                  }
                } else if (isset($rowev['value'])) {
                  if (isset($rowsm['5_left']) && isset($rowsm['5_right']) && isset($rowsm['0_left']) && isset($rowsm['0_right'])) {
                    if ($rowev['value'] <= $rowsm['5_left']) {
                      $score = $rowat['weightage'];
                    } else if ($rowev['value'] <= $rowsm['4_left']) {
                      $score = $rowat['weightage'] * 4 / 5;
                    } else if ($rowev['value'] <= $rowsm['3_left']) {
                      $score = $rowat['weightage'] * 3 / 5;
                    } else if ($rowev['value'] <= $rowsm['2_left']) {
                      $score = $rowat['weightage'] * 2 / 5;
                    } else if ($rowev['value'] <= $rowsm['1_left']) {
                      $score = $rowat['weightage'] / 5;
                    } else {
                      $score = 0;
                    }
                  } else if (isset($rowsm['5_left']) && isset($rowsm['0_right'])) {
                    if ($rowev['value'] <= $rowsm['5_left']) {
                      $score = $rowat['weightage'];
                    } else if ($rowev['value'] <= $rowsm['4_left']) {
                      $score = $rowat['weightage'] * 4 / 5;
                    } else if ($rowev['value'] <= $rowsm['3_left']) {
                      $score = $rowat['weightage'] * 3 / 5;
                    } else if ($rowev['value'] <= $rowsm['2_left']) {
                      $score = $rowat['weightage'] * 2 / 5;
                    } else if ($rowev['value'] <= $rowsm['1_left']) {
                      $score = $rowat['weightage'] / 5;
                    } else {
                      $score = 0;
                    }
                  } else if (isset($rowsm['5_right']) && isset($rowsm['0_left'])) {
                    if ($rowev['value'] >= $rowsm['5_right']) {
                      $score = $rowat['weightage'];
                    } else if ($rowev['value'] >= $rowsm['4_right']) {
                      $score = $rowat['weightage'] * 4 / 5;
                    } else if ($rowev['value'] >= $rowsm['3_right']) {
                      $score = $rowat['weightage'] * 3 / 5;
                    } else if ($rowev['value'] >= $rowsm['2_right']) {
                      $score = $rowat['weightage'] * 2 / 5;
                    } else if ($rowev['value'] >= $rowsm['1_right']) {
                      $score = $rowat['weightage'] / 5;
                    } else {
                      $score = 0;
                    }
                  } else {
                    $score = 0;
                  }
                } else {
                  $negative = (int)$rowev['negative'];
                  $positive = (int)$rowev['positive'];

                  // SPECIAL LOGIC ONLY FOR ATTRIBUTE 32 IN 2026
                  if ($attrId == 32 && $evalYear == 2026) {
                    // Score 0
                    if ($negative >= 3 && $positive == 0) {
                      $score = 0;
                    }
                    // Score 1
                    else if ($negative >= 2 && $positive == 0) {
                      $score = $rowat['weightage'] * 1 / 5;
                    }
                    // Score 2
                    else if ($negative >= 1 && $positive == 0) {
                      $score = $rowat['weightage'] * 2 / 5;
                    }
                    // Score 5
                    else if ($negative == 0 && $positive > 10) {
                      $score = $rowat['weightage'];
                    }
                    // Score 4
                    else if ($negative == 0 && $positive > 1) {
                      $score = $rowat['weightage'] * 4 / 5;
                    }
                    // Score 3
                    else if ($negative == 0 && $positive >= 0) {
                      $score = $rowat['weightage'] * 3 / 5;
                    }
                    else {
                      $score = 0;
                    }
                  } else {
                    // OLD GENERAL LOGIC
                    if (($rowev['negative'] == $rowsm['5_left'] && $rowev['positive'] >= $rowsm['5_right'])) {
                      $score = $rowat['weightage'];
                    } else if (($rowev['negative'] == $rowsm['4_left'] && $rowev['positive'] == $rowsm['4_right']) || ($rowev['negative'] == $rowsm['4_left'] + 1 && $rowev['positive'] >= $rowsm['4_right'] + 2)) {
                      $score = $rowat['weightage'] * 4 / 5;
                    } else if (($rowev['negative'] == $rowsm['3_left'] && $rowev['positive'] == $rowsm['3_right']) || ($rowev['negative'] == $rowsm['3_left'] + 1 && $rowev['positive'] >= $rowsm['3_right'] + 1)) {
                      $score = $rowat['weightage'] * 3 / 5;
                    } else if (($rowev['negative'] == $rowsm['2_left'] && $rowev['positive'] == $rowsm['2_right']) || ($rowev['negative'] == $rowsm['2_left'] + 1 && $rowev['positive'] >= $rowsm['2_right'] + 1)) {
                      $score = $rowat['weightage'] * 2 / 5;
                    } else if (($rowev['negative'] == $rowsm['1_left'] && $rowev['positive'] == $rowsm['1_right']) || ($rowev['negative'] == $rowsm['1_left'] + 1 && $rowev['positive'] >= $rowsm['1_right'] + 1)) {
                      $score = $rowat['weightage'] * 1 / 5;
                    } else {
                      $score = 0;
                    }
                  }
                }
                
                $total_score += $score;
              }
            }
            
            if ($count > 0) {
              return $total_score / $count;
            } else {
              return 0;
            }
          }

          // ===== FIXED: Updated grade2 function for custom periods with history table and Attribute 32 special logic =====
          function grade2($id, $sPeriod, $ePeriod, $conn, &$scoringMethodCache)
          {
            $sqlev = "SELECT * FROM evaluation WHERE emp_id = '$id' AND period BETWEEN '$sPeriod' AND '$ePeriod'";
            $resultev = $conn->query($sqlev);

            $sqlc = "SELECT COUNT(DISTINCT period) AS 'count' FROM evaluation WHERE emp_id = '$id' AND period BETWEEN '$sPeriod' AND '$ePeriod'";
            $resultc = $conn->query($sqlc);
            
            if ($resultc && $resultc->num_rows > 0) {
              $rowc = $resultc->fetch_assoc();
              $count = $rowc['count'];
            } else {
              $count = 0;
            }

            $total_score = 0;
            if ($resultev && $resultev->num_rows > 0) {
              while ($rowev = $resultev->fetch_assoc()) {
                $attrId = $rowev['attribute_id'];
                
                // Get the year from evaluation period
                $evalPeriod = $rowev['period'];
                $evalYear = date('Y', strtotime($evalPeriod));
                
                // Create cache key using attribute_id and year
                $cacheKey = $attrId . '_' . $evalYear;
                
                // Check if scoring method is in cache
                if (!isset($scoringMethodCache[$cacheKey])) {
                  // FIRST: Try to get scoring method from history table
                  $sqlHistory = "SELECT ash.scoring_method_id, sm.* 
                                 FROM attribute_scoring_history ash
                                 INNER JOIN scoring_method sm ON ash.scoring_method_id = sm.sm_id
                                 WHERE ash.attribute_id = '$attrId' 
                                 AND ash.year = '$evalYear'";
                  $resultHistory = $conn->query($sqlHistory);
                  
                  if ($resultHistory && $resultHistory->num_rows > 0) {
                    $scoringMethodCache[$cacheKey] = $resultHistory->fetch_assoc();
                  } else {
                    // FALLBACK: Get attribute's current scoring method
                    $sqlat = "SELECT scoring_method, weightage FROM attribute WHERE attribute_id = '$attrId'";
                    $resultat = $conn->query($sqlat);
                    if ($resultat && $resultat->num_rows > 0) {
                      $rowat_fallback = $resultat->fetch_assoc();
                      $sm = $rowat_fallback['scoring_method'];
                      
                      $sqlsm = "SELECT * FROM scoring_method WHERE sm_name = '$sm' AND year = '$evalYear'";
                      $resultsm = $conn->query($sqlsm);
                      
                      if ($resultsm && $resultsm->num_rows == 0) {
                        $sqlsm = "SELECT * FROM scoring_method WHERE sm_name = '$sm' ORDER BY year DESC LIMIT 1";
                        $resultsm = $conn->query($sqlsm);
                      }
                      
                      if ($resultsm && $resultsm->num_rows > 0) {
                        $scoringMethodCache[$cacheKey] = $resultsm->fetch_assoc();
                      } else {
                        $scoringMethodCache[$cacheKey] = null;
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
                
                // Get attribute weightage
                $sqlat = "SELECT weightage FROM attribute WHERE attribute_id = '$attrId'";
                $resultat = $conn->query($sqlat);
                $rowat = $resultat->fetch_assoc();

                // Calculate score for this evaluation
                if (isset($rowev['status']) && $rowev['status'] < 0) {
                  if ($rowev['status'] == -1) {
                    $score = $rowat['weightage'] * 3 / 5;
                  } else if ($rowev['status'] == -2) {
                    $score = 0;
                  } else {
                    $score = 0;
                  }
                } else if (isset($rowev['value'])) {
                  if (isset($rowsm['5_left']) && isset($rowsm['5_right']) && isset($rowsm['0_left']) && isset($rowsm['0_right'])) {
                    if ($rowev['value'] <= $rowsm['5_left']) {
                      $score = $rowat['weightage'];
                    } else if ($rowev['value'] <= $rowsm['4_left']) {
                      $score = $rowat['weightage'] * 4 / 5;
                    } else if ($rowev['value'] <= $rowsm['3_left']) {
                      $score = $rowat['weightage'] * 3 / 5;
                    } else if ($rowev['value'] <= $rowsm['2_left']) {
                      $score = $rowat['weightage'] * 2 / 5;
                    } else if ($rowev['value'] <= $rowsm['1_left']) {
                      $score = $rowat['weightage'] / 5;
                    } else {
                      $score = 0;
                    }
                  } else if (isset($rowsm['5_left']) && isset($rowsm['0_right'])) {
                    if ($rowev['value'] <= $rowsm['5_left']) {
                      $score = $rowat['weightage'];
                    } else if ($rowev['value'] <= $rowsm['4_left']) {
                      $score = $rowat['weightage'] * 4 / 5;
                    } else if ($rowev['value'] <= $rowsm['3_left']) {
                      $score = $rowat['weightage'] * 3 / 5;
                    } else if ($rowev['value'] <= $rowsm['2_left']) {
                      $score = $rowat['weightage'] * 2 / 5;
                    } else if ($rowev['value'] <= $rowsm['1_left']) {
                      $score = $rowat['weightage'] / 5;
                    } else {
                      $score = 0;
                    }
                  } else if (isset($rowsm['5_right']) && isset($rowsm['0_left'])) {
                    if ($rowev['value'] >= $rowsm['5_right']) {
                      $score = $rowat['weightage'];
                    } else if ($rowev['value'] >= $rowsm['4_right']) {
                      $score = $rowat['weightage'] * 4 / 5;
                    } else if ($rowev['value'] >= $rowsm['3_right']) {
                      $score = $rowat['weightage'] * 3 / 5;
                    } else if ($rowev['value'] >= $rowsm['2_right']) {
                      $score = $rowat['weightage'] * 2 / 5;
                    } else if ($rowev['value'] >= $rowsm['1_right']) {
                      $score = $rowat['weightage'] / 5;
                    } else {
                      $score = 0;
                    }
                  } else {
                    $score = 0;
                  }
                } else {
                  $negative = (int)$rowev['negative'];
                  $positive = (int)$rowev['positive'];

                  // SPECIAL LOGIC ONLY FOR ATTRIBUTE 32 IN 2026
                  if ($attrId == 32 && $evalYear == 2026) {
                    // Score 0
                    if ($negative >= 3 && $positive == 0) {
                      $score = 0;
                    }
                    // Score 1
                    else if ($negative >= 2 && $positive == 0) {
                      $score = $rowat['weightage'] * 1 / 5;
                    }
                    // Score 2
                    else if ($negative >= 1 && $positive == 0) {
                      $score = $rowat['weightage'] * 2 / 5;
                    }
                    // Score 5
                    else if ($negative == 0 && $positive > 10) {
                      $score = $rowat['weightage'];
                    }
                    // Score 4
                    else if ($negative == 0 && $positive > 1) {
                      $score = $rowat['weightage'] * 4 / 5;
                    }
                    // Score 3
                    else if ($negative == 0 && $positive >= 0) {
                      $score = $rowat['weightage'] * 3 / 5;
                    }
                    else {
                      $score = 0;
                    }
                  } else {
                    // OLD GENERAL LOGIC
                    if (($rowev['negative'] == $rowsm['5_left'] && $rowev['positive'] >= $rowsm['5_right'])) {
                      $score = $rowat['weightage'];
                    } else if (($rowev['negative'] == $rowsm['4_left'] && $rowev['positive'] == $rowsm['4_right']) || ($rowev['negative'] == $rowsm['4_left'] + 1 && $rowev['positive'] >= $rowsm['4_right'] + 2)) {
                      $score = $rowat['weightage'] * 4 / 5;
                    } else if (($rowev['negative'] == $rowsm['3_left'] && $rowev['positive'] == $rowsm['3_right']) || ($rowev['negative'] == $rowsm['3_left'] + 1 && $rowev['positive'] >= $rowsm['3_right'] + 1)) {
                      $score = $rowat['weightage'] * 3 / 5;
                    } else if (($rowev['negative'] == $rowsm['2_left'] && $rowev['positive'] == $rowsm['2_right']) || ($rowev['negative'] == $rowsm['2_left'] + 1 && $rowev['positive'] >= $rowsm['2_right'] + 1)) {
                      $score = $rowat['weightage'] * 2 / 5;
                    } else if (($rowev['negative'] == $rowsm['1_left'] && $rowev['positive'] == $rowsm['1_right']) || ($rowev['negative'] == $rowsm['1_left'] + 1 && $rowev['positive'] >= $rowsm['1_right'] + 1)) {
                      $score = $rowat['weightage'] * 1 / 5;
                    } else {
                      $score = 0;
                    }
                  }
                }
                
                $total_score += $score;
              }
            }
            
            if ($count > 0) {
              return $total_score / $count;
            } else {
              return 0;
            }
          } ?>

          <table class="card col-md-12 <?php if ($_SESSION['isAdmin']) {
                                          echo "Admin";
                                        } else if ($_SESSION["isEvaluator"]) {
                                          echo "Evaluator";
                                        } else {
                                          echo "Guest";
                                        } ?>" style="text-align: center; padding: 5px; width: max-content;">
            <tr>
              <th style="padding: 2vh; border: 1px solid #ccc;">No.</th>
              <th style="padding: 2vh; border: 1px solid #ccc;">Employee Number</th>
              <th style="padding: 2vh; border: 1px solid #ccc;">Employee Name</th>
              <th style="padding: 2vh; border: 1px solid #ccc;">Category</th>
              <th style="padding: 2vh; border: 1px solid #ccc;">Store</th>
              <?php if (isset($_GET['sp']) && isset($_GET['ep'])) { ?>
                <th style="padding: 2vh; border: 1px solid #ccc;"><?php $sp = $_GET['sp']; echo(date('Y', strtotime($sp . ' -1 years'))); ?> Score</th>
                <th style="padding: 2vh; border: 1px solid #ccc;"><?php $sp = $_GET['sp']; echo(date('Y', strtotime($sp . ' -1 years'))); ?> Rank</th>
                <th style="padding: 2vh; border: 1px solid #ccc;">Current Score</th>
                <th style="padding: 2vh; border: 1px solid #ccc;">Current Rank</th>
              <?php } else { ?>
                <th style="padding: 2vh; border: 1px solid #ccc;">Last Year's Score</th>
                <th style="padding: 2vh; border: 1px solid #ccc;">Last Year's Rank</th>
                <th style="padding: 2vh; border: 1px solid #ccc;">Current Year's Score</th>
                <th style="padding: 2vh; border: 1px solid #ccc;">Current Year's Rank</th>
              <?php } ?>
            </tr>

            <?php
            $emp = array();

            while ($row = $result->fetch_assoc()) {
              $emp_id = $row['emp_id'];
              $emp_name = $row['emp_name'];
              $current_category = $row['current_category'];
              $current_store = $row['current_store'];

              // Get store history for this employee (for display only)
              $history_years = getEmployeeStoreHistory($emp_id, $conn);
              
              // Determine which stores are used in the current period
              if (isset($_GET['sp']) && isset($_GET['ep'])) {
                $sp = $_GET['sp'];
                $ep = $_GET['ep'];
                
                // Get primary store for this period
                $primary_store = getPrimaryStoreForPeriod($emp_id, $sp, $ep, $conn);
                
                // Get store breakdown for this period
                $store_breakdown = getStoreBreakdown($emp_id, $sp, $ep, $conn);
                
                // Get all distinct stores in this period
                $store_query = "SELECT DISTINCT store FROM evaluation WHERE emp_id = '$emp_id' AND period BETWEEN '$sp' AND '$ep'";
                $store_result = $conn->query($store_query);
                $stores_in_period = array();
                while ($store_row = $store_result->fetch_assoc()) {
                  $stores_in_period[] = $store_row['store'];
                }
              } else {
                $period = date('Y');
                
                // For current year, get primary store
                $primary_store = getPrimaryStoreForPeriod($emp_id, $period . '-01', $period . '-12', $conn);
                
                // Get store breakdown for current year
                $store_breakdown = getStoreBreakdown($emp_id, $period . '-01', $period . '-12', $conn);
                
                // Get all distinct stores in current year
                $store_query = "SELECT DISTINCT store FROM evaluation WHERE emp_id = '$emp_id' AND period LIKE '$period%'";
                $store_result = $conn->query($store_query);
                $stores_in_period = array();
                while ($store_row = $store_result->fetch_assoc()) {
                  $stores_in_period[] = $store_row['store'];
                }
              }

              if (isset($_GET['sp']) && isset($_GET['ep'])) {
                $sp = $_GET['sp'];
                $ep = $_GET['ep'];
                $current_year_score = grade2($emp_id, $sp, $ep, $conn, $scoringMethodCache);
                $last_year_score = grade($emp_id, date('Y', strtotime($sp . ' -1 years')), $conn, $scoringMethodCache);
              } else {
                $current_year_score = grade($emp_id, $period, $conn, $scoringMethodCache);
                $last_year_score = grade($emp_id, date('Y', strtotime($period . ' -1 years')), $conn, $scoringMethodCache);
              }

              $emp[] = array(
                'emp_id' => $emp_id,
                'emp_name' => $emp_name,
                'current_category' => $current_category,
                'current_store' => $current_store,
                'primary_store' => $primary_store,
                'store_breakdown' => $store_breakdown,
                'last_year_score' => $last_year_score,
                'current_year_score' => $current_year_score,
                'history_years' => $history_years,
                'stores_in_period' => $stores_in_period
              );
            }

            usort($emp, function ($a, $b) {
              return $b['last_year_score'] <=> $a['last_year_score'];
            });

            $last_year_rank = 1;
            $last_year_score = null;
            $same_last_year_ranks = 0;

            for ($i = 0; $i < count($emp); $i++) {
              if ($emp[$i]['last_year_score'] != $last_year_score) {
                $last_year_rank += $same_last_year_ranks;
                $same_last_year_ranks = 0;
              }

              $same_last_year_ranks++;
              $last_year_score = $emp[$i]['last_year_score'];
              $emp[$i]['last_year_rank'] = $last_year_rank;
            }

            usort($emp, function ($a, $b) {
              return $b['current_year_score'] <=> $a['current_year_score'];
            });

            $current_year_rank = 1;
            $current_year_score = null;
            $same_current_year_ranks = 0;

            for ($i = 0; $i < count($emp); $i++) {
              if ($emp[$i]['current_year_score'] != $current_year_score) {
                $current_year_rank += $same_current_year_ranks;
                $same_current_year_ranks = 0;
              }

              $same_current_year_ranks++;
              $current_year_score = $emp[$i]['current_year_score'];
              $emp[$i]['current_year_rank'] = $current_year_rank;
            }

            foreach ($emp as $index => $employee) { 
              // Build store history tooltip
              $store_tooltip = "Store History:<br>";
              if (!empty($employee['history_years'])) {
                foreach ($employee['history_years'] as $year => $store) {
                  $store_tooltip .= $year . ": Store " . $store . "<br>";
                }
              }
              $store_tooltip .= "Current: Store " . $employee['current_store'];
              
              // Check if employee worked in multiple stores in current period
              $multiple_stores = count($employee['stores_in_period']) > 1;
              
              // Determine which store to display (primary store from evaluation period)
              $display_store = $employee['primary_store'];
              $store_display_text = $display_store ? $display_store : $employee['current_store'] . " (current)";
              
              // Build store breakdown text
              $breakdown_text = "";
              if (!empty($employee['store_breakdown'])) {
                $breakdown_parts = array();
                foreach ($employee['store_breakdown'] as $store_info) {
                  $breakdown_parts[] = "Store " . $store_info['store'] . " (" . $store_info['months'] . " months)";
                }
                $breakdown_text = implode(", ", $breakdown_parts);
              }
            ?>
              <tr>
                <td style="padding: 1vh; border: 1px solid #ccc;"><?php echo $index + 1 ?></td>
                <td style="padding: 1vh; border: 1px solid #ccc;"><?php echo $employee['emp_id'] ?></td>
                <td style="padding: 1vh; border: 1px solid #ccc;">
                  <?php if (isset($_GET['sp']) && isset($_GET['ep'])) {
                    $sp = $_GET['sp'];
                    $ep = $_GET['ep']; ?>
                    <a href="./rating.php?id=<?php echo $employee['emp_id'] ?>&sp=<?php echo $sp ?>&ep=<?php echo $ep ?>" style="color: #fff;">
                  <?php } else { ?>
                    <a href="./rating.php?id=<?php echo $employee['emp_id'] ?>" style="color: #fff;">
                  <?php } ?>
                  <?php echo $employee['emp_name'] ?></a>
                </td>
                <td style="padding: 1vh; border: 1px solid #ccc;"><?php echo $employee['current_category'] ?></td>
                <td style="padding: 1vh; border: 1px solid #ccc;">
                  <?php if ($display_store): ?>
                    <span class="primary-store-badge" title="Primary store during evaluation period">
                      <?php echo $display_store ?>
                    </span>
                  <?php else: ?>
                    <span class="store-tooltip">
                      <?php echo $employee['current_store'] ?>
                      <span class="tooltip-text">No evaluations found for this period</span>
                    </span>
                  <?php endif; ?>
                  
                  <?php if ($multiple_stores && !empty($employee['stores_in_period'])): ?>
    <span class="multi-store-badge" title="Store distribution: <?php echo $breakdown_text; ?>">
        <i class='bx bx-store-alt'></i> 
        <?php 
        // Display stores in compact format like "57B/66"
        $store_list = array();
        foreach ($employee['stores_in_period'] as $store) {
            $store_list[] = $store;
        }
        echo implode('/', $store_list);
        ?>
    </span>
<?php  endif; ?>
                  
                  <?php if (!empty($employee['history_years'])): ?>
                    <div class="store-history-info">
                     
                      <?php 
                      // Show last 2 years of history as badges
                      $count = 0;
                      foreach ($employee['history_years'] as $year => $store) {
                        if ($count < 2) {
                         
                        }
                        $count++;
                      }
                      if (count($employee['history_years']) > 2) {
                        echo "...";
                      }
                      ?>
                    </div>
                  <?php endif; ?>
                </td>
                <td style="padding: 1vh; border: 1px solid #ccc;"><?php echo number_format($employee['last_year_score'], 2); ?></td>
                <td style="padding: 1vh; border: 1px solid #ccc;"><?php echo $employee['last_year_rank'] ?></td>
                <td style="padding: 1vh; border: 1px solid #ccc;"><?php echo number_format($employee['current_year_score'], 2); ?></td>
                <td style="padding: 1vh; border: 1px solid #ccc;"><?php echo $employee['current_year_rank'] ?></td>
              </tr>
            <?php } ?>
          </table>
    </center>
  <?php  } ?>
  </div>
  <div class="floating-container">
    <button name="printButton" for="printButton" id="printButton" class="floating-button <?php if ($_SESSION['isAdmin']) {
                                                                                            echo "Admin";
                                                                                          } else if ($_SESSION["isEvaluator"]) {
                                                                                            echo "Evaluator";
                                                                                          } else {
                                                                                            echo "Guest";
                                                                                          } ?>" onclick="printSpecificSection('ptr')"><i class='bx bx-printer'></i></button>
  </div>
  </body>

  </html>

<?php } else {
  header("location: signin.php");
}
?>