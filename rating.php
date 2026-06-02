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

  if (isset($_POST['submitPeriod']) && isset($_POST['sPeriod']) && isset($_POST['ePeriod']) && !empty($_POST['sPeriod']) && !empty($_POST['ePeriod'])) {
    $eNum = $_POST['empNumber'];
    $sPeriod = $_POST['sPeriod'];
    $ePeriod = $_POST['ePeriod'];
    echo "<script>
          window.location.href='./pdf.php?id=$eNum&sp=$sPeriod&ep=$ePeriod';
          </script>";
  } else if (isset($_POST['submitPeriod'])) {
    $eNum = $_POST['empNumber'];
    echo "<script>
          window.location.href='./rating.php?id=$eNum';
          </script>";
  }

  if (isset($_GET['id'])) {
    $id = $_GET['id'];

    $sql = "SELECT * FROM employee WHERE emp_id ='$id'";
    $result = $conn->query($sql);
    
    // Check if employee exists
    if ($result && $result->num_rows > 0) {
      $row = $result->fetch_assoc();
    } else {
      die("Employee not found");
    }
    
    // ===== Get store history for display purposes ONLY =====
    $history_sql = "SELECT * FROM employee_store_history WHERE emp_id = '$id' ORDER BY year DESC";
    $history_result = $conn->query($history_sql);
    $history_years = array();
    while ($history_row = $history_result->fetch_assoc()) {
        $history_years[$history_row['year']] = $history_row['store'];
    }
    
    // Cache array for scoring methods and attribute types
    $scoringMethodCache = array();
    $attributeTypeCache = array();
    
    // ===== Helper function to check if attribute is Value-based =====
    function isValueBasedAttribute($attrId, $conn, &$attributeTypeCache) {
        if (isset($attributeTypeCache[$attrId])) {
            return $attributeTypeCache[$attrId];
        }
        
        $sql = "SELECT is_valued FROM attribute WHERE attribute_id = '$attrId'";
        $result = $conn->query($sql);
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $attributeTypeCache[$attrId] = ($row['is_valued'] == 1);
            return $attributeTypeCache[$attrId];
        }
        return false;
    }
    
    // ===== FIXED: grade2 function - uses history table =====
    function grade2($rowev, $conn, &$scoringMethodCache)
    {
      if (!isset($rowev['attribute_id'])) {
        return 0;
      }
      
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
          // Found the correct historical scoring method
          $scoringMethodCache[$cacheKey] = $resultHistory->fetch_assoc();
        } else {
          // FALLBACK: If no history exists, get attribute's current scoring method
          $sqlat = "SELECT scoring_method FROM attribute WHERE attribute_id = '$attrId'";
          $resultat = $conn->query($sqlat);
          if ($resultat && $resultat->num_rows > 0) {
            $rowat_fallback = $resultat->fetch_assoc();
            $sm = $rowat_fallback['scoring_method'];
            
            // Try to get scoring method for this specific year
            $sqlsm = "SELECT * FROM scoring_method WHERE sm_name = '$sm' AND year = '$evalYear'";
            $resultsm = $conn->query($sqlsm);
            
            if ($resultsm && $resultsm->num_rows > 0) {
              $scoringMethodCache[$cacheKey] = $resultsm->fetch_assoc();
            } else {
              // Last resort: get any version
              $sqlsm = "SELECT * FROM scoring_method WHERE sm_name = '$sm' ORDER BY year DESC LIMIT 1";
              $resultsm = $conn->query($sqlsm);
              
              if ($resultsm && $resultsm->num_rows > 0) {
                $scoringMethodCache[$cacheKey] = $resultsm->fetch_assoc();
              } else {
                $scoringMethodCache[$cacheKey] = null;
              }
            }
          } else {
            $scoringMethodCache[$cacheKey] = null;
          }
        }
      }
      
      $rowsm = $scoringMethodCache[$cacheKey];
      
      // Skip if no scoring method found
      if (!$rowsm) {
        return 0;
      }

      if (isset($rowev['status']) && $rowev['status'] < 0) {
        if ($rowev['status'] == -1) {
          $rate = 3;
        } else if ($rowev['status'] == -2) {
          $rate = 0;
        } else {
          $rate = 0;
        }
      } else if (isset($rowev['value'])) {
        if (isset($rowsm['5_left']) && isset($rowsm['5_right']) && isset($rowsm['0_left']) && isset($rowsm['0_right'])) {
          if ($rowev['value'] <= $rowsm['5_left']) {
            $rate = 5;
          } else if ($rowev['value'] <= $rowsm['4_left']) {
            $rate = 4;
          } else if ($rowev['value'] <= $rowsm['3_left']) {
            $rate = 3;
          } else if ($rowev['value'] <= $rowsm['2_left']) {
            $rate = 2;
          } else if ($rowev['value'] <= $rowsm['1_left']) {
            $rate = 1;
          } else {
            $rate = 0;
          }
        } else if (isset($rowsm['5_left']) && isset($rowsm['0_right'])) {
          if ($rowev['value'] <= $rowsm['5_left']) {
            $rate = 5;
          } else if ($rowev['value'] <= $rowsm['4_left']) {
            $rate = 4;
          } else if ($rowev['value'] <= $rowsm['3_left']) {
            $rate = 3;
          } else if ($rowev['value'] <= $rowsm['2_left']) {
            $rate = 2;
          } else if ($rowev['value'] <= $rowsm['1_left']) {
            $rate = 1;
          } else {
            $rate = 0;
          }
        } else if (isset($rowsm['5_right']) && isset($rowsm['0_left'])) {
          if ($rowev['value'] >= $rowsm['5_right']) {
            $rate = 5;
          } else if ($rowev['value'] >= $rowsm['4_right']) {
            $rate = 4;
          } else if ($rowev['value'] >= $rowsm['3_right']) {
            $rate = 3;
          } else if ($rowev['value'] >= $rowsm['2_right']) {
            $rate = 2;
          } else if ($rowev['value'] >= $rowsm['1_right']) {
            $rate = 1;
          } else {
            $rate = 0;
          }
        } else {
          $rate = 0;
        }
      } else {

    $negative = (int)$rowev['negative'];
    $positive = (int)$rowev['positive'];

    // SPECIAL LOGIC ONLY FOR ATTRIBUTE 32 IN YEAR 2026
    if ($attrId == 32 && $evalYear == 2026) {

        if ($negative >= 3 && $positive == 0) {
            $rate = 0;
        }

        else if ($negative >= 2 && $positive == 0) {
            $rate = 1;
        }

        else if ($negative >= 1 && $positive == 0) {
            $rate = 2;
        }

        else if ($negative == 0 && $positive > 10) {
            $rate = 5;
        }

        else if ($negative == 0 && $positive > 1) {
            $rate = 4;
        }

        else if ($negative == 0 && $positive > 0) {
            $rate = 3;
        }

        else if ($negative == 0 && $positive == 0) {
            $rate = 3;
        }

        else {
            $rate = 0;
        }

    } else {

        // OLD GENERAL LOGIC

        if (($rowev['negative'] == $rowsm['5_left'] && $rowev['positive'] >= $rowsm['5_right'])) {
            $rate = 5;
        } else if (($rowev['negative'] == $rowsm['4_left'] && $rowev['positive'] == $rowsm['4_right']) || ($rowev['negative'] == $rowsm['4_left'] + 1 && $rowev['positive'] >= $rowsm['4_right'] + 2)) {
            $rate = 4;
        } else if (($rowev['negative'] == $rowsm['3_left'] && $rowev['positive'] == $rowsm['3_right']) || ($rowev['negative'] == $rowsm['3_left'] + 1 && $rowev['positive'] >= $rowsm['3_right'] + 1)) {
            $rate = 3;
        } else if (($rowev['negative'] == $rowsm['2_left'] && $rowev['positive'] == $rowsm['2_right']) || ($rowev['negative'] == $rowsm['2_left'] + 1 && $rowev['positive'] >= $rowsm['2_right'] + 1)) {
            $rate = 2;
        } else if (($rowev['negative'] == $rowsm['1_left'] && $rowev['positive'] == $rowsm['1_right']) || ($rowev['negative'] == $rowsm['1_left'] + 1 && $rowev['positive'] >= $rowsm['1_right'] + 1)) {
            $rate = 1;
        } else {
            $rate = 0;
        }
    }
}
      return $rate;
    }
    
    // ===== FIXED: Get monthly grade - uses history table =====
    function getMonthlyGrade($id, $period, $conn, &$scoringMethodCache) {
        $sqlev = "SELECT * FROM evaluation WHERE emp_id = '$id' and period = '$period'";
        $resultev = $conn->query($sqlev);

        $total_score = 0;
        $count = 0;
        
        if ($resultev && $resultev->num_rows > 0) {
          while ($rowev = $resultev->fetch_assoc()) {
            $attrId = $rowev['attribute_id'];
            
            // Get the year from evaluation period
            $evalPeriod = $rowev['period'];
            $evalYear = date('Y', strtotime($evalPeriod));
            
            // Create cache key
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
            
            if (!$rowsm) {
              continue;
            }
            
            // Get attribute weightage
            $sqlat = "SELECT weightage FROM attribute WHERE attribute_id = '$attrId'";
            $resultat = $conn->query($sqlat);
            $rowat = $resultat->fetch_assoc();

            if (isset($rowev['status']) && $rowev['status'] < 0) {
              if ($rowev['status'] == -1) {
                $mark = $rowat['weightage'] * 3 / 5;
              } else if ($rowev['status'] == -2) {
                $mark = 0;
              } else {
                $mark = 0;
              }
            } else if (isset($rowev['value'])) {
              if (isset($rowsm['5_left']) && isset($rowsm['5_right']) && isset($rowsm['0_left']) && isset($rowsm['0_right'])) {
                if ($rowev['value'] <= $rowsm['5_left']) {
                  $mark = $rowat['weightage'];
                } else if ($rowev['value'] <= $rowsm['4_left']) {
                  $mark = $rowat['weightage'] * 4 / 5;
                } else if ($rowev['value'] <= $rowsm['3_left']) {
                  $mark = $rowat['weightage'] * 3 / 5;
                } else if ($rowev['value'] <= $rowsm['2_left']) {
                  $mark = $rowat['weightage'] * 2 / 5;
                } else if ($rowev['value'] <= $rowsm['1_left']) {
                  $mark = $rowat['weightage'] / 5;
                } else {
                  $mark = 0;
                }
              } else if (isset($rowsm['5_left']) && isset($rowsm['0_right'])) {
                if ($rowev['value'] <= $rowsm['5_left']) {
                  $mark = $rowat['weightage'];
                } else if ($rowev['value'] <= $rowsm['4_left']) {
                  $mark = $rowat['weightage'] * 4 / 5;
                } else if ($rowev['value'] <= $rowsm['3_left']) {
                  $mark = $rowat['weightage'] * 3 / 5;
                } else if ($rowev['value'] <= $rowsm['2_left']) {
                  $mark = $rowat['weightage'] * 2 / 5;
                } else if ($rowev['value'] <= $rowsm['1_left']) {
                  $mark = $rowat['weightage'] / 5;
                } else {
                  $mark = 0;
                }
              } else if (isset($rowsm['5_right']) && isset($rowsm['0_left'])) {
                if ($rowev['value'] >= $rowsm['5_right']) {
                  $mark = $rowat['weightage'];
                } else if ($rowev['value'] >= $rowsm['4_right']) {
                  $mark = $rowat['weightage'] * 4 / 5;
                } else if ($rowev['value'] >= $rowsm['3_right']) {
                  $mark = $rowat['weightage'] * 3 / 5;
                } else if ($rowev['value'] >= $rowsm['2_right']) {
                  $mark = $rowat['weightage'] * 2 / 5;
                } else if ($rowev['value'] >= $rowsm['1_right']) {
                  $mark = $rowat['weightage'] / 5;
                } else {
                  $mark = 0;
                }
              } else {
                $mark = 0;
              }
            } else {

    $negative = (int)$rowev['negative'];
    $positive = (int)$rowev['positive'];

    // SPECIAL LOGIC FOR ATTRIBUTE 32 IN 2026
    if ($attrId == 32 && $evalYear == 2026) {

        // Score 5
        if ($negative == 0 && $positive > 10) {
            $mark = $rowat['weightage'];
        }

        // Score 4
        else if ($negative == 0 && $positive > 1) {
            $mark = $rowat['weightage'] * 4 / 5;
        }

        // Score 3
        else if ($negative == 0 && $positive >= 0) {
            $mark = $rowat['weightage'] * 3 / 5;
        }

        // Score 2
        else if ($negative >= 1 && $positive == 0) {
            $mark = $rowat['weightage'] * 2 / 5;
        }

        // Score 1
        else if ($negative >= 2 && $positive == 0) {
            $mark = $rowat['weightage'] * 1 / 5;
        }

        // Score 0
        else if ($negative >= 3 && $positive == 0) {
            $mark = 0;
        }

        else {
            $mark = 0;
        }

    } else {

        // OLD GENERAL LOGIC

        if (($rowev['negative'] == $rowsm['5_left'] && $rowev['positive'] >= $rowsm['5_right'])) {
            $mark = $rowat['weightage'];
        } else if (($rowev['negative'] == $rowsm['4_left'] && $rowev['positive'] == $rowsm['4_right']) || ($rowev['negative'] == $rowsm['4_left'] + 1 && $rowev['positive'] >= $rowsm['4_right'] + 2)) {
            $mark = $rowat['weightage'] * 4 / 5;
        } else if (($rowev['negative'] == $rowsm['3_left'] && $rowev['positive'] == $rowsm['3_right']) || ($rowev['negative'] == $rowsm['3_left'] + 1 && $rowev['positive'] >= $rowsm['3_right'] + 1)) {
            $mark = $rowat['weightage'] * 3 / 5;
        } else if (($rowev['negative'] == $rowsm['2_left'] && $rowev['positive'] == $rowsm['2_right']) || ($rowev['negative'] == $rowsm['2_left'] + 1 && $rowev['positive'] >= $rowsm['2_right'] + 1)) {
            $mark = $rowat['weightage'] * 2 / 5;
        } else if (($rowev['negative'] == $rowsm['1_left'] && $rowev['positive'] == $rowsm['1_right']) || ($rowev['negative'] == $rowsm['1_left'] + 1 && $rowev['positive'] >= $rowsm['1_right'] + 1)) {
            $mark = $rowat['weightage'] * 1 / 5;
        } else {
            $mark = 0;
        }
    }
}
            
            $total_score += $mark;
            $count++;
          }
        }
        
        return ($count > 0) ? $total_score : 0;
    }

    // ===== FIXED: Get yearly grade - uses history table =====
    function getYearlyGrade($id, $year, $conn, &$scoringMethodCache)
    {
        $sqlev = "SELECT * FROM evaluation WHERE emp_id = '$id' and period LIKE '$year%'";
        $resultev = $conn->query($sqlev);

        $sqlc = "SELECT COUNT(DISTINCT period) AS 'count' FROM evaluation WHERE emp_id = '$id' and period LIKE '$year%'";
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
            
            // Create cache key
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
            
            if (!$rowsm) {
              continue;
            }
            
            // Get attribute weightage
            $sqlat = "SELECT weightage FROM attribute WHERE attribute_id = '$attrId'";
            $resultat = $conn->query($sqlat);
            $rowat = $resultat->fetch_assoc();

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

    // SPECIAL LOGIC FOR ATTRIBUTE 32 IN 2026
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
        
        return ($count > 0) ? $total_score / $count : 0;
    }

    // ===== FIXED: For custom period with multiple years =====
    function gradese($id, $periods, $periode, $conn, &$scoringMethodCache)
    {
        $sqlev = "SELECT * FROM evaluation WHERE emp_id = '$id' AND period BETWEEN '$periods' AND '$periode'";
        $resultev = $conn->query($sqlev);

        $sqlc = "SELECT COUNT(DISTINCT period) AS 'count' FROM evaluation WHERE emp_id = '$id' AND period BETWEEN '$periods' AND '$periode'";
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
            
            // Create cache key
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
            
            if (!$rowsm) {
              continue;
            }
            
            // Get attribute weightage
            $sqlat = "SELECT weightage FROM attribute WHERE attribute_id = '$attrId'";
            $resultat = $conn->query($sqlat);
            $rowat = $resultat->fetch_assoc();

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

    // SPECIAL LOGIC FOR ATTRIBUTE 32 IN 2026
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
        
        return ($count > 0) ? $total_score / $count : 0;
    }
    
    // Get current store for the employee (for display only)
    $current_store = $row['current_store'];
    
    // ===== Calculate all rates using the fixed functions =====
    $currentYear = date('Y');
    $currentMonth = date('Y-m');
    
    // Get yearly rates for the 3-year chart
    $rate3 = getYearlyGrade($id, ($currentYear - 2), $conn, $scoringMethodCache); // 2024
    $rate16 = getYearlyGrade($id, ($currentYear - 1), $conn, $scoringMethodCache); // 2025
    $rate1 = getYearlyGrade($id, $currentYear, $conn, $scoringMethodCache); // 2026
    
    // Get monthly rates for last 12 months
    $rate4 = getMonthlyGrade($id, $currentMonth, $conn, $scoringMethodCache);
    $rate5 = getMonthlyGrade($id, date('Y-m', strtotime('-1 months')), $conn, $scoringMethodCache);
    $rate6 = getMonthlyGrade($id, date('Y-m', strtotime('-2 months')), $conn, $scoringMethodCache);
    $rate7 = getMonthlyGrade($id, date('Y-m', strtotime('-3 months')), $conn, $scoringMethodCache);
    $rate8 = getMonthlyGrade($id, date('Y-m', strtotime('-4 months')), $conn, $scoringMethodCache);
    $rate9 = getMonthlyGrade($id, date('Y-m', strtotime('-5 months')), $conn, $scoringMethodCache);
    $rate10 = getMonthlyGrade($id, date('Y-m', strtotime('-6 months')), $conn, $scoringMethodCache);
    $rate11 = getMonthlyGrade($id, date('Y-m', strtotime('-7 months')), $conn, $scoringMethodCache);
    $rate12 = getMonthlyGrade($id, date('Y-m', strtotime('-8 months')), $conn, $scoringMethodCache);
    $rate13 = getMonthlyGrade($id, date('Y-m', strtotime('-9 months')), $conn, $scoringMethodCache);
    $rate14 = getMonthlyGrade($id, date('Y-m', strtotime('-10 months')), $conn, $scoringMethodCache);
    $rate15 = getMonthlyGrade($id, date('Y-m', strtotime('-11 months')), $conn, $scoringMethodCache);
    
    // Set period labels
    $period4 = $currentMonth;
    $period5 = date('Y-m', strtotime('-1 months'));
    $period6 = date('Y-m', strtotime('-2 months'));
    $period7 = date('Y-m', strtotime('-3 months'));
    $period8 = date('Y-m', strtotime('-4 months'));
    $period9 = date('Y-m', strtotime('-5 months'));
    $period10 = date('Y-m', strtotime('-6 months'));
    $period11 = date('Y-m', strtotime('-7 months'));
    $period12 = date('Y-m', strtotime('-8 months'));
    $period13 = date('Y-m', strtotime('-9 months'));
    $period14 = date('Y-m', strtotime('-10 months'));
    $period15 = date('Y-m', strtotime('-11 months'));
    $period1 = $currentYear;
    $period3 = $currentYear - 2;
    $period16 = $currentYear - 1;
    $period2 = $currentYear - 1;
    
    // Custom period rate if applicable
    if (isset($_GET['sp']) && isset($_GET['ep'])) {
        $speriod = $_GET['sp'];
        $eperiod = $_GET['ep'];
        $serate = gradese($id, $speriod, $eperiod, $conn, $scoringMethodCache);
    }
    
    // Calculate max value for Y-axis scaling
    $all_values = array($rate3, $rate16, $rate1, $rate4, $rate5, $rate6, $rate7, $rate8, $rate9, $rate10, $rate11, $rate12, $rate13, $rate14, $rate15);
    $max_value = max($all_values);
    
    // Round up to nearest 1 for nice axis intervals
    if ($max_value <= 1) {
        $y_max = 1;
        $y_interval = 0.2;
    } else if ($max_value <= 2) {
        $y_max = 2;
        $y_interval = 0.4;
    } else if ($max_value <= 3) {
        $y_max = 3;
        $y_interval = 0.6;
    } else if ($max_value <= 4) {
        $y_max = 4;
        $y_interval = 0.8;
    } else if ($max_value <= 5) {
        $y_max = 5;
        $y_interval = 1;
    } else if ($max_value <= 6) {
        $y_max = 6;
        $y_interval = 1.2;
    } else if ($max_value <= 7) {
        $y_max = 7;
        $y_interval = 1.4;
    } else if ($max_value <= 8) {
        $y_max = 8;
        $y_interval = 1.6;
    } else if ($max_value <= 9) {
        $y_max = 9;
        $y_interval = 1.8;
    } else if ($max_value <= 10) {
        $y_max = 10;
        $y_interval = 2;
    } else {
        $y_max = ceil($max_value);
        $y_interval = $y_max / 5;
    }
?>

    <!DOCTYPE html>
    <html lang="en">

    <head>
      <meta charset="UTF-8">
      <meta http-equiv="X-UA-Compatible" content="IE=edge">
      <meta name="viewport" content="width=device-width, initial-scale=1.0">
      <title>Details - <?php echo htmlspecialchars($row['emp_name']) ?></title>
      <link rel="icon" type="image/x-icon" href="./img/jb.png">

      <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta1/dist/css/bootstrap.min.css" />
      <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta1/dist/js/bootstrap.bundle.min.js" />
      <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.2.1/jquery.min.js" />
      <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/boxicons@latest/css/boxicons.min.css" />
      <!-- Font Awesome -->
      <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
      <!-- CanvasJS for charts -->
      <script src="https://cdn.canvasjs.com/canvasjs.min.js"></script>

      <link rel="stylesheet" href="./style.css" />
      <script src="./script.js"></script>
      
      <style>
        /* Base styles for both screen and print */
        body {
          font-family: Arial, sans-serif;
        }
        
        .print-btn {
          position: fixed;
          bottom: 20px;
          right: 20px;
          z-index: 1000;
          background-color: #007bff;
          color: white;
          border: none;
          border-radius: 50%;
          width: 60px;
          height: 60px;
          font-size: 24px;
          cursor: pointer;
          box-shadow: 0 4px 8px rgba(0,0,0,0.2);
          transition: all 0.3s;
        }
        
        .print-btn:hover {
          background-color: #0056b3;
          transform: scale(1.1);
        }
        
        #chartContainer, #lineChartContainer {
          width: 100%;
          height: 300px;
        }
        
        .print-header {
          text-align: center;
          margin-bottom: 20px;
          border-bottom: 2px solid #000;
          padding-bottom: 10px;
        }
        
        .summary-table {
          width: 100%;
          margin: 0 auto;
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
        
        /* Store History Badge - for DISPLAY only */
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
        
        /* Store Dropdown Styles */
        .store-dropdown-container {
          display: flex;
          justify-content: center;
          margin-bottom: 20px;
        }
        
        .store-select {
          padding: 8px 15px;
          border-radius: 20px;
          border: 1px solid #4361ee;
          background-color: white;
          color: #4361ee;
          font-weight: 500;
          font-size: 14px;
          cursor: pointer;
          outline: none;
          min-width: 200px;
        }
        
        .store-select:hover, .store-select:focus {
          border-color: #4361ee;
          box-shadow: 0 0 5px rgba(67, 97, 238, 0.3);
        }
        
        /* Store sections - FIXED: All hidden by default */
        .store-section {
          display: none;
        }
        
        .store-section.active {
          display: block;
        }
        
        @keyframes fadeIn {
          from { opacity: 0; transform: translateY(10px); }
          to { opacity: 1; transform: translateY(0); }
        }
        
        .store-header {
          display: flex;
          align-items: center;
          gap: 10px;
          margin-bottom: 15px;
          padding: 15px;
          background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
          color: white;
          border-radius: 10px;
        }
        
        .store-header i {
          font-size: 2rem;
        }
        
        .store-header h4 {
          margin: 0;
          font-weight: 600;
        }
        
        /* Screen-only styles */
        @media screen {
          .no-print {
            display: block;
          }
          
          .print-only {
            display: none !important;
          }
          
          .screen-only {
            display: block;
          }
        
          .summary-table th {
            background-color: #10151b;
            font-weight: bold;
          }
          
          table {
            width: 100%;
            border-collapse: collapse;
          }
          
          th, td {
            border: 1px solid #dee2e6;
            padding: 8px;
            text-align: center;
          }
          
          th {
            background-color: #f8f9fa;
          }
        }
        
        /* Print-only styles */
        @media print {
          .no-print, 
          .screen-only,
          header,
          .l-navbar,
          .print-btn {
            display: none !important;
          }
          
          .print-only {
            display: block !important;
          }
          
          body {
            background: white !important;
            color: black !important;
            font-size: 12pt !important;
            margin: 0 !important;
            padding: 0 !important;
          }
          
          .container, 
          .height-100 {
            width: 100% !important;
            max-width: 100% !important;
            margin: 0 !important;
            padding: 10px !important;
          }
          
          .card {
            border: 1px solid #000 !important;
            box-shadow: none !important;
            margin: 10px 0 !important;
            page-break-inside: avoid;
            background: white !important;
          }
          
          .print-content {
            visibility: visible !important;
            position: relative !important;
          }
          
          .print-section {
            page-break-inside: avoid;
          }
          
          table {
            width: 100% !important;
            border-collapse: collapse !important;
            border: 1px solid #000 !important;
            font-size: 9pt !important;
            table-layout: fixed !important;
          }
          
          th, td {
            border: 1px solid #000 !important;
            padding: 4px !important;
            text-align: center !important;
            vertical-align: middle !important;
            word-wrap: break-word !important;
          }
          
          th {
            background-color: #f0f0f0 !important;
            font-weight: bold !important;
            color: #000 !important;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
          }
          
          * {
            color: #000000 !important;
          }
          
          #chartContainer, #lineChartContainer {
            height: 250px !important;
            page-break-inside: avoid;
            width: 100% !important;
          }
        }
      </style>
    </head>

    <body id="body-pd" class="content <?php 
      if (isset($_SESSION['isAdmin']) && $_SESSION['isAdmin']) {
        echo "Admin";
      } else if (isset($_SESSION["isEvaluator"]) && $_SESSION["isEvaluator"]) {
        echo "Evaluator";
      } else {
        echo "Guest";
      } 
    ?>">
      <header class="header <?php 
        if (isset($_SESSION['isAdmin']) && $_SESSION['isAdmin']) {
          echo "Admin";
        } else if (isset($_SESSION["isEvaluator"]) && $_SESSION["isEvaluator"]) {
          echo "Evaluator";
        } else {
          echo "Guest";
        } 
      ?>" id="header">
        <div class="header_toggle"> <i class='bx bx-menu' id="header-toggle"></i> </div>
        <?php if (isset($_SESSION["user"])) { ?>
          <h5 style="font-weight: bold; text-transform: capitalize;"><?php echo htmlspecialchars($_SESSION["user"]); ?></h5>
        <?php } else { ?>
          <div class="header_img"> <a href="./signin.php"><i class='bx bxs-user nav_icon' style="padding: 1vh;"></i></a> </div>
        <?php } ?>
      </header>

      <!-- System Status Alert -->
      <?php if ($is_frozen == '1'): ?>
        <div class="system-alert">
          <!-- Alert content if needed -->
        </div>
      <?php endif; ?>

      <div class="l-navbar <?php 
        if (isset($_SESSION['isAdmin']) && $_SESSION['isAdmin']) {
          echo "Admin";
        } else if (isset($_SESSION["isEvaluator"]) && $_SESSION["isEvaluator"]) {
          echo "Evaluator";
        } else {
          echo "Guest";
        } 
      ?>" id="nav-bar">
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
              <button type="submit" name="signout" class="nav_link <?php 
                if (isset($_SESSION['isAdmin']) && $_SESSION['isAdmin']) {
                  echo "Admin";
                } else if (isset($_SESSION["isEvaluator"]) && $_SESSION["isEvaluator"]) {
                  echo "Evaluator";
                } else {
                  echo "Guest";
                } 
              ?>" style="background-color: #666; border: none;"> <i class='bx bx-log-out nav_icon'></i> <span class="nav_name">SignOut</span> </button>
            </form>
          </div>
        </nav>
      </div>

      <!-- Add print button -->
      <button class="print-btn no-print" onclick="printReport()" title="Print Full Report">
        <i class='bx bx-printer'></i>
      </button>

      <!-- Main content wrapper -->
      <div class="height-100 container mt-5 mb-3" style="padding-top: 2vh;">
        
        <!-- Employee Summary Card - Always visible -->
        <div class="card p-12 mb-12 print-content <?php 
          if (isset($_SESSION['isAdmin']) && $_SESSION['isAdmin']) {
            echo "Admin";
          } else if (isset($_SESSION["isEvaluator"]) && $_SESSION["isEvaluator"]) {
            echo "Evaluator";
          } else {
            echo "Guest";
          } 
        ?>">
          <div class="print-header">
            <h3><b><?php echo htmlspecialchars($row['emp_name']) ?></b></h3>
            <h5><b>Emp No: </b><?php echo htmlspecialchars($row['emp_id']) ?></h5>
            
            <!-- Store History Section - FOR DISPLAY ONLY -->
           <?php if (!empty($history_years)): ?>
  <div class="mb-3">
    <h6 style="font-weight: 600; margin-bottom: 10px;">Store History</h6>
    <div class="d-flex flex-wrap gap-2 justify-content-center">
      <?php 
      $currentYear = date('Y');
      foreach ($history_years as $hist_year => $hist_store): 
        if ($hist_year != $currentYear):
      ?>
        <span class="history-badge">
          <i class='bx bx-calendar'></i>
          <?php echo $hist_year ?>: Store <?php echo $hist_store ?>
        </span>
      <?php 
        endif;
      endforeach; 
      ?>
      <span class="history-badge">
        <i class='bx bx-current-location'></i>
        Current: Store <?php echo $row['current_store'] ?>
      </span>
    </div>
  </div>

            <?php else: ?>
              <div class="mb-3">
                <h6 style="font-weight: 600; margin-bottom: 10px;">Current Store</h6>
                <div class="d-flex flex-wrap gap-2 justify-content-center">
                  <span class="history-badge current">
                    <i class='bx bx-current-location'></i>
                    Store <?php echo $row['current_store'] ?>
                  </span>
                </div>
              </div>
            <?php endif; ?>
            
            <h6 style="font-weight: 1000; color:rgba(255, 255, 255, 0.5);">Current Details</h6>
            <p><?php echo htmlspecialchars($row['current_category']) ?> - <?php echo htmlspecialchars($row['current_store']) ?></p>
          </div>
          <center>
            <h4>
              <?php
              echo $period4 . " Average Earning : " . number_format($rate4, 2);
              ?>
            </h4>
            <h4 style="font-weight: bold; color: #ff0000;">
              <?php
              echo $period1 . " Average Earning : " . number_format($rate1, 2);
              ?>
            </h4>
            <h4 style="font-weight: bold;">
              <?php
              echo $period2 . " Average Earning : " . number_format($rate16, 2);
              ?>
            </h4>
            <!-- ADDED: Previous Year (2024) Average Rating -->
            <h4 style="font-weight: bold; color: #666;">
              <?php
              echo $period3 . " Average Earning : " . number_format($rate3, 2);
              ?>
            </h4>
            <?php if (isset($_GET['sp']) && isset($_GET['ep'])) { ?>
              <h4 style="color: #aaa;">
                <?php
                echo $speriod . " to " . $eperiod . " Average Rating : " . number_format($serate, 2); ?>
              </h4>
            <?php } ?>
          </center>
        </div>

        <?php
        // Prepare chart data
        $dataPoints = array(
          array("label" => (string)($period3), "y" => (float)$rate3),
          array("label" => (string)($period16), "y" => (float)$rate16),
          array("label" => (string)($period1), "y" => (float)$rate1),
        );

        $lineDataPoints = array(
          array("y" => (float)$rate15, "label" => $period15),
          array("y" => (float)$rate14, "label" => $period14),
          array("y" => (float)$rate13, "label" => $period13),
          array("y" => (float)$rate12, "label" => $period12),
          array("y" => (float)$rate11, "label" => $period11),
          array("y" => (float)$rate10, "label" => $period10),
          array("y" => (float)$rate9, "label" => $period9),
          array("y" => (float)$rate8, "label" => $period8),
          array("y" => (float)$rate7, "label" => $period7),
          array("y" => (float)$rate6, "label" => $period6),
          array("y" => (float)$rate5, "label" => $period5),
          array("y" => (float)$rate4, "label" => $period4),
        );
        ?>
        
        <!-- Charts Section -->
        <div class="col-md-12 row print-content" style="margin-top: 4%;">
          <div class="col-md-6 print-section">
            <div class="card" id="chartContainer" style="margin-top: 10px; margin-left: 20px; height: 320px;"></div>
          </div>
          <div class="col-md-6 print-section">
            <div class="card" id="lineChartContainer" style="margin-top: 10px; margin-left: 20px; height: 320px;"></div>
          </div>
        </div>

        <!-- Summary Tables - Split by Store -->
        <div class="col-md-12 print-content">
          <div class="card p-12 mb-12 print-section <?php 
            if (isset($_SESSION['isAdmin']) && $_SESSION['isAdmin']) {
              echo "Admin";
            } else if (isset($_SESSION["isEvaluator"]) && $_SESSION["isEvaluator"]) {
              echo "Evaluator";
            } else {
              echo "Guest";
            } 
          ?>" style="margin-top: 2%;">
            <center>
              <?php if (isset($_GET['sp']) && isset($_GET['ep'])) {
                $speriod = $_GET['sp'];
                $eperiod = $_GET['ep']; ?>
                <h3 style="padding-top: 2vh;">Summary of <?php echo $speriod . " to " . $eperiod ?></h3>
              <?php } else { ?>
                <h3 style="padding-top: 2vh;">Summary of <?php echo date('Y') ?></h3>
              <?php } ?>
            </center>
            
            <?php            // Get all unique stores for this period
            $CS = $row['current_store'];
            
            // ===== Get ALL evaluations from ALL stores =====
            if (isset($_GET['sp']) && isset($_GET['ep'])) {
              $speriod = $_GET['sp'];
              $eperiod = $_GET['ep'];
              $sqlm = "SELECT * FROM evaluation E WHERE E.emp_id = '$id' AND period BETWEEN '$speriod' AND '$eperiod' ORDER BY E.store, E.attribute_id";
            } else {
              $sqlm = "SELECT * FROM evaluation E WHERE E.emp_id = '$id' ORDER BY E.store, E.attribute_id";
            }
            
            $resultm = $conn->query($sqlm);
            
            // Group data by store
            $store_data = array();
            
            if ($resultm && $resultm->num_rows > 0) {
              while ($rowm = $resultm->fetch_assoc()) {
                $store = $rowm['store'];
                if (!isset($store_data[$store])) {
                  $store_data[$store] = array();
                }
                
                $score = grade2($rowm, $conn, $scoringMethodCache);
                
                // Get attribute details and check if value-based
                $attrId = $rowm['attribute_id'];
                $isValued = isValueBasedAttribute($attrId, $conn, $attributeTypeCache);
                $sqlat = "SELECT attribute_name, category FROM attribute WHERE attribute_id = '$attrId'";
                $resultat = $conn->query($sqlat);
                $rowat = $resultat->fetch_assoc();
                
                $found = false;
                
                // Check if attribute already exists in this store
                foreach ($store_data[$store] as $index => $emp) {
                  if ($emp[0] == $rowm['attribute_id']) {
                    // Update existing entry
                    if (isset($rowm['positive'])) $store_data[$store][$index][4] += $rowm['positive'];
                    if (isset($rowm['negative'])) $store_data[$store][$index][5] += $rowm['negative'];
                    if (isset($rowm['neutral'])) $store_data[$store][$index][6] += $rowm['neutral'];
                    if (isset($rowm['value'])) $store_data[$store][$index][7] += $rowm['value'];
                    $store_data[$store][$index][8] += $score;
                    $store_data[$store][$index][9]++;
                    $store_data[$store][$index][10] = $isValued; // Update value-based flag
                    $found = true;
                    break;
                  }
                }
                
                if (!$found) {
                  // Create new entry
                  $new_entry = array();
                  $new_entry[0] = isset($rowm['attribute_id']) ? $rowm['attribute_id'] : '';
                  $new_entry[1] = isset($rowat['attribute_name']) ? $rowat['attribute_name'] : '';
                  if (isset($rowat['category']) && $rowat['category'] == 'Common') {
                    $new_entry[2] = $rowat['category'];
                  } else {
                    $new_entry[2] = isset($row['current_category']) ? $row['current_category'] : '';
                  }
                  $new_entry[3] = isset($rowm['store']) ? $rowm['store'] : '';
                  $new_entry[4] = isset($rowm['positive']) ? $rowm['positive'] : 0;
                  $new_entry[5] = isset($rowm['negative']) ? $rowm['negative'] : 0;
                  $new_entry[6] = isset($rowm['neutral']) ? $rowm['neutral'] : 0;
                  $new_entry[7] = isset($rowm['value']) ? $rowm['value'] : 0;
                  $new_entry[8] = $score;
                  $new_entry[9] = 1;
                  $new_entry[10] = $isValued; // Store whether this attribute is value-based
                  
                  $store_data[$store][] = $new_entry;
                }
              }
            }
            
            // Get unique stores
            $stores = array_keys($store_data);
            $active_store = isset($_GET['store']) ? $_GET['store'] : (count($stores) > 0 ? $stores[0] : '');
            ?>
            
            <!-- Store Dropdown (Screen only) - Only show if more than 1 store -->
            <?php if (count($stores) > 1): ?>
            <div class="store-dropdown-container no-print">
              <select class="store-select" onchange="showStore(this.value)">
                <option value="all" <?php echo (!isset($_GET['store']) || $_GET['store'] == 'all') ? 'selected' : ''; ?>>
                  <i class='bx bx-globe'></i> All Stores Combined
                </option>
                <?php foreach ($stores as $store): ?>
                  <option value="<?php echo $store; ?>" <?php echo ($store == $active_store && isset($_GET['store']) && $_GET['store'] != 'all') ? 'selected' : ''; ?>>
                    <i class='bx bx-store'></i> Store <?php echo $store; ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            
            <!-- FIXED: Initialize active store on page load -->
            <script>
            document.addEventListener('DOMContentLoaded', function() {
              var dropdown = document.querySelector('.store-select');
              if (dropdown) {
                showStore(dropdown.value);
              }
            });
            </script>
            <?php endif; ?>
            
            <!-- For single store, only show ONE table with store name -->
            <?php if (count($stores) <= 1): ?>
              <!-- Single Store View - Only one table -->
              <?php 
              $single_store = count($stores) > 0 ? $stores[0] : '';
              $store_display_name = !empty($single_store) ? $single_store : 'N/A';
              ?>
              <div class="store-header">
                <i class='bx bx-store'></i>
                <div>
                  <h4>Store <?php echo $store_display_name; ?></h4>
                  <small>Showing evaluations from Store <?php echo $store_display_name; ?></small>
                </div>
              </div>
              
              <div style="overflow-x: auto;">
                <table class="summary-table">
                  <tr>
                    <th>Attribute ID</th>
                    <th>Attribute</th>
                    <th>Category</th>
                    <th>Store</th>
                    <th>Positive</th>
                    <th>Negative</th>
                    <th>Value</th>
                    <th>Score(AVG)</th>
                    <th>Level</th>
                  </tr>
                  <?php
                  if (!empty($store_data) && count($stores) > 0) {
                    $store_key = $stores[0];
                    $data = $store_data[$store_key];
                    
                    foreach ($data as $emp) {
                      if (isset($emp[0]) && !empty($emp[0])) {
                        $avg_mark = ($emp[9] > 0) ? $emp[8] / $emp[9] : 0;
                        $isValuedAttr = isset($emp[10]) ? $emp[10] : false;
                        
                        if ($isValuedAttr) {
                            // Value-based attribute (is_valued = 1) - ALWAYS show value (even if 0)
                            $avg_value = $emp[7] / $emp[9];
                            $display_value = number_format($avg_value, 3);
                        } else {
                            // Critical Incident Based (is_valued = 0) - ALWAYS show blank
                            $display_value = '';
                        }
                        
                        if ($avg_mark >= 4.5) {
                          $level = 'Outstanding';
                        } else if ($avg_mark >= 3.5) {
                          $level = 'Good';
                        } else if ($avg_mark >= 2.5) {
                          $level = 'Acceptable';
                        } else if ($avg_mark >= 1.5) {
                          $level = 'Barely Acceptable';
                        } else if ($avg_mark >= 0.5) {
                          $level = 'Unsatisfactory';
                        } else {
                          $level = 'Worst';
                        }
                  ?>
                  <tr>
                    <td><?php echo htmlspecialchars($emp[0]) ?></td>
                    <td style="text-align: left; padding-left: 10px;"><?php echo htmlspecialchars($emp[1]) ?></td>
                    <td><?php echo htmlspecialchars($emp[2]) ?></td>
                    <td><?php echo htmlspecialchars($emp[3]) ?></td>
                    <td><?php echo $emp[4] ?></td>
                    <td><?php echo $emp[5] ?></td>
                    <td><?php echo $display_value; ?></td>
                    <td><?php echo number_format($avg_mark, 3); ?></td>
                    <td><?php echo $level ?></td>
                  </tr>
                  <?php
                      }
                    }
                  } else {
                  ?>
                  <tr>
                    <td colspan="9" style="text-align: center; padding: 20px;">No evaluation data found.</td>
                  </tr>
                  <?php } ?>
                </table>
              </div>
              
            <?php else: ?>
              <!-- Multiple Stores View - Show dropdown and multiple sections -->
              
              <!-- All Stores Combined Section -->
              <div id="store-all" class="store-section">
                <div class="store-header">
                  <i class='bx bx-globe'></i>
                  <div>
                    <h4>All Stores Combined</h4>
                    <small>Showing data from all stores</small>
                  </div>
                </div>
                
                <div style="overflow-x: auto;">
                  <table class="summary-table">
                    <tr>
                      <th>Attribute ID</th>
                      <th>Attribute</th>
                      <th>Category</th>
                      <th>Store</th>
                      <th>Positive</th>
                      <th>Negative</th>
                      <th>Value</th>
                      <th>Mark(AVG)</th>
                      <th>Level</th>
                    </tr>
                    <?php
                    // Combine all stores for "All" view
                    $all_data = array();
                    foreach ($store_data as $store => $data) {
                      foreach ($data as $entry) {
                        $found = false;
                        foreach ($all_data as $index => $all_entry) {
                          if ($all_entry[0] == $entry[0]) {
                            // Combine
                            $all_data[$index][4] += $entry[4];
                            $all_data[$index][5] += $entry[5];
                            $all_data[$index][6] += $entry[6];
                            $all_data[$index][7] += $entry[7];
                            $all_data[$index][8] += $entry[8];
                            $all_data[$index][9] += $entry[9];
                            // Store becomes "Multiple"
                            $all_data[$index][3] = "Multiple";
                            $all_data[$index][10] = $entry[10]; // Keep value-based flag
                            $found = true;
                            break;
                          }
                        }
                        if (!$found) {
                          $new_entry = $entry;
                          $new_entry[3] = "Multiple";
                          $all_data[] = $new_entry;
                        }
                      }
                    }
                    
                    if (!empty($all_data)) {
                      foreach ($all_data as $emp) {
                        if (isset($emp[0]) && !empty($emp[0])) {
                          $avg_mark = ($emp[9] > 0) ? $emp[8] / $emp[9] : 0;
                          $isValuedAttr = isset($emp[10]) ? $emp[10] : false;
                          
                          if ($isValuedAttr) {
                              // Value-based attribute - ALWAYS show value
                              $avg_value = $emp[7] / $emp[9];
                              $display_value = number_format($avg_value, 3);
                          } else {
                              // Critical Incident Based - ALWAYS show blank
                              $display_value = '';
                          }
                          
                          if ($avg_mark >= 4.5) {
                            $level = 'Outstanding';
                          } else if ($avg_mark >= 3.5) {
                            $level = 'Good';
                          } else if ($avg_mark >= 2.5) {
                            $level = 'Acceptable';
                          } else if ($avg_mark >= 1.5) {
                            $level = 'Barely Acceptable';
                          } else if ($avg_mark >= 0.5) {
                            $level = 'Unsatisfactory';
                          } else {
                            $level = 'Worst';
                          }
                    ?>
                    <tr>
                      <td><?php echo htmlspecialchars($emp[0]) ?></td>
                      <td style="text-align: left; padding-left: 10px;"><?php echo htmlspecialchars($emp[1]) ?></td>
                      <td><?php echo htmlspecialchars($emp[2]) ?></td>
                      <td><?php echo htmlspecialchars($emp[3]) ?></td>
                      <td><?php echo $emp[4] ?></td>
                      <td><?php echo $emp[5] ?></td>
                      <td><?php echo $display_value; ?></td>
                      <td><?php echo number_format($avg_mark, 3); ?></td>
                      <td><?php echo $level ?></td>
                    </tr>
                    <?php
                        }
                      }
                    } else {
                    ?>
                    <tr>
                      <td colspan="9" style="text-align: center; padding: 20px;">No evaluation data found.</td>
                    </tr>
                    <?php } ?>
                  </table>
                </div>
              </div>
              
              <!-- Individual Store Sections -->
              <?php foreach ($store_data as $store => $data): ?>
              <div id="store-<?php echo $store; ?>" class="store-section">
                <div class="store-header">
                  <i class='bx bx-store'></i>
                  <div>
                    <h4>Store <?php echo $store; ?></h4>
                    <small>Showing evaluations from Store <?php echo $store; ?> only</small>
                  </div>
                </div>
                
                <div style="overflow-x: auto;">
                  <table class="summary-table">
                    <tr>
                      <th>Attribute ID</th>
                      <th>Attribute</th>
                      <th>Category</th>
                      <th>Store</th>
                      <th>Positive</th>
                      <th>Negative</th>
                      <th>Value</th>
                      <th>Mark(AVG)</th>
                      <th>Level</th>
                    </tr>
                    <?php
                    if (!empty($data)) {
                      foreach ($data as $emp) {
                        if (isset($emp[0]) && !empty($emp[0])) {
                          $avg_mark = ($emp[9] > 0) ? $emp[8] / $emp[9] : 0;
                          $isValuedAttr = isset($emp[10]) ? $emp[10] : false;
                          
                          if ($isValuedAttr) {
                              // Value-based attribute - ALWAYS show value
                              $avg_value = $emp[7] / $emp[9];
                              $display_value = number_format($avg_value, 3);
                          } else {
                              // Critical Incident Based - ALWAYS show blank
                              $display_value = '';
                          }
                          
                          if ($avg_mark >= 4.5) {
                            $level = 'Outstanding';
                          } else if ($avg_mark >= 3.5) {
                            $level = 'Good';
                          } else if ($avg_mark >= 2.5) {
                            $level = 'Acceptable';
                          } else if ($avg_mark >= 1.5) {
                            $level = 'Barely Acceptable';
                          } else if ($avg_mark >= 0.5) {
                            $level = 'Unsatisfactory';
                          } else {
                            $level = 'Worst';
                          }
                    ?>
                    <tr>
                      <td><?php echo htmlspecialchars($emp[0]) ?></td>
                      <td style="text-align: left; padding-left: 10px;"><?php echo htmlspecialchars($emp[1]) ?></td>
                      <td><?php echo htmlspecialchars($emp[2]) ?></td>
                      <td><?php echo htmlspecialchars($emp[3]) ?></td>
                      <td><?php echo $emp[4] ?></td>
                      <td><?php echo $emp[5] ?></td>
                      <td><?php echo $display_value; ?></td>
                      <td><?php echo number_format($avg_mark, 3); ?></td>
                      <td><?php echo $level ?></td>
                    </tr>
                    <?php
                        }
                      }
                    } else {
                    ?>
                    <tr>
                      <td colspan="9" style="text-align: center; padding: 20px;">No evaluation data found for Store <?php echo $store; ?>.</td>
                    </tr>
                    <?php } ?>
                  </table>
                </div>
              </div>
              <?php endforeach; ?>
            <?php endif; ?>
            
          </div>
        </div>

        <!-- SCREEN VERSION: Shows only 10 records -->
        <div class="col-md-12 print-content screen-only">
          <div class="card p-12 mb-12 print-section <?php 
            if (isset($_SESSION['isAdmin']) && $_SESSION['isAdmin']) {
              echo "Admin";
            } else if (isset($_SESSION["isEvaluator"]) && $_SESSION["isEvaluator"]) {
              echo "Evaluator";
            } else {
              echo "Guest";
            } 
          ?>" style="margin-top: 2%;">
            <center>
              <h3 style="padding-top: 2vh;">Latest Evaluations Added</h3>
            </center>
            <div style="overflow-x: auto;">
              <table class="summary-table">
                <tr>
                  <th><a style="color:#ccf;" href="./rating.php?id=<?php echo $id ?><?php if (isset($_GET['sp']) && isset($_GET['ep'])) {
                                                                                      $sp = $_GET['sp'];
                                                                                      $ep = $_GET['ep'];
                                                                                      echo "&sp=" . $sp . "&ep=" . $ep;
                                                                                    } ?>&filter=Time">Time</a></th>
                  <th><a style="color:#ccf;" href="./rating.php?id=<?php echo $id ?><?php if (isset($_GET['sp']) && isset($_GET['ep'])) {
                                                                                      $sp = $_GET['sp'];
                                                                                      $ep = $_GET['ep'];
                                                                                      echo "&sp=" . $sp . "&ep=" . $ep;
                                                                                    } ?>&filter=Period">Period</a></th>
                  <th>Attribute</th>
                  <th>Category</th>
                  <th>Store</th>
                  <th>Positive</th>
                  <th>Negative</th>
                  <th>Value</th>
                 
                  <th>Comment</th>
                  <th class="no-print">Actions</th>
                </tr>
                <?php
                $empcat = $row['current_category'];
                $evaluator = $_SESSION["id"];
                
                if (isset($_GET['sp']) && isset($_GET['ep'])) {
                  $sp = $_GET['sp'];
                  $ep = $_GET['ep'];
                  
                  if (isset($_GET['filter']) && $_GET['filter'] == "Period") {
                    $sql = "SELECT * FROM ((records R JOIN attribute A ON R.attribute_id = A.attribute_id) JOIN evaluator V ON R.evaluator_id = V.evaluator_id) WHERE R.emp_id = '$id' AND period BETWEEN '$sp' AND '$ep' ORDER BY period DESC LIMIT 10;";
                    $result_records = $conn->query($sql);
                  } else {
                    $sql = "SELECT * FROM ((records R JOIN attribute A ON R.attribute_id = A.attribute_id) JOIN evaluator V ON R.evaluator_id = V.evaluator_id) WHERE R.emp_id = '$id' AND period BETWEEN '$sp' AND '$ep' ORDER BY time DESC LIMIT 10;";
                    $result_records = $conn->query($sql);
                  }
                } else {
                  if (isset($_GET['filter']) && $_GET['filter'] == "Period") {
                    $sql = "SELECT * FROM ((records R JOIN attribute A ON R.attribute_id = A.attribute_id) JOIN evaluator V ON R.evaluator_id = V.evaluator_id) WHERE R.emp_id = '$id' ORDER BY period DESC LIMIT 10;";
                    $result_records = $conn->query($sql);
                  } else {
                    $sql = "SELECT * FROM ((records R JOIN attribute A ON R.attribute_id = A.attribute_id) JOIN evaluator V ON R.evaluator_id = V.evaluator_id) WHERE R.emp_id = '$id' ORDER BY time DESC LIMIT 10;";
                    $result_records = $conn->query($sql);
                  }
                }
                
                if ($result_records && $result_records->num_rows > 0) {
                  while ($row_records = $result_records->fetch_assoc()) {
                    // Add period to the row for grade2 function
                    $row_records['period'] = $row_records['period'];
                ?>
                <tr>
                  <td><?php echo htmlspecialchars($row_records['time']) ?></td>
                  <td><?php echo htmlspecialchars($row_records['period']) ?></td>
                  <td style="text-align: left; padding-left: 10px;"><?php echo htmlspecialchars($row_records['attribute_name']) ?></td>
                  <td><?php if ($row_records['category'] == 'Common') {
                        echo htmlspecialchars($row_records['category']);
                      } else {
                        echo htmlspecialchars($empcat);
                      } ?></td>
                  <td><?php echo htmlspecialchars($row_records['store']) ?></td>
                  <td><?php echo htmlspecialchars($row_records['positive']) ?></td>
                  <td><?php echo htmlspecialchars($row_records['negative']) ?></td>
                  <td><?php echo htmlspecialchars($row_records['value']) ?></td>
                 
                  <td style="text-align: left; padding-left: 5px;"><?php echo htmlspecialchars($row_records['comment']) ?></td>
                  <td class="no-print">
                    <?php if ($_SESSION["isAdmin"] || $_SESSION["id"] == $row_records['evaluator_id']) { ?>
                      <b><a style="color: #03e3fc;" href="./evaluation.php?evlId=<?php echo $row_records['record_id'] ?>"> <span style="width: 70px; padding-bottom: 3px;"><i class='bx bx-pencil'></i>&nbsp;<span>Edit</span></span></a></b>
                    <?php } else { ?>
                      <span style="color: #999;">View Only</span>
                    <?php } ?>
                  </td>
                </tr>
                <?php } 
                } else { ?>
                <tr>
                  <td colspan="11" style="text-align: center; padding: 20px;">No evaluation records found.</td>
                </tr>
                <?php } ?>
              </table>
            </div>
            <div class="no-print">
              <?php if (isset($_GET['sp']) && isset($_GET['ep'])) {
                $sp = $_GET['sp'];
                $ep = $_GET['ep'];
              ?>
                <a href="./all_evaluations.php?id=<?php echo $id ?>&sp=<?php echo $sp ?>&ep=<?php echo $ep ?>">
              <?php } else { ?>
                <a href="./all_evaluations.php?id=<?php echo $id ?>">
              <?php } ?>
                <center>
                  <button class="showAll no-print <?php 
                    if (isset($_SESSION['isAdmin']) && $_SESSION['isAdmin']) {
                      echo "Admin";
                    } else if (isset($_SESSION["isEvaluator"]) && $_SESSION["isEvaluator"]) {
                      echo "Evaluator";
                    } else {
                      echo "Guest";
                    } 
                  ?>" style="border: none; background-color:#fff;">
                    Show All<br>
                    <img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAB4AAAAeCAYAAAA7MK6iAAAAAXNSR0IArs4c6QAAAXhJREFUSEvtlbtxwzAMhoFGtUfIBvEI8QQZIXJBS50zStyJYhFlg2zgjOBskBFcs2GOOTIn8QgC0uWixipFAB9/vIiw0ocrceEG/rfMF1Nd1/Wmqqqzc+5kjBkkt1JK1Yj4tNbuhmG4Uj4kOEIBYOudnXN7Dh6grwF2KcFJ8OFw8AqfxjcuwRNodHvr+77OqeYUfwDAPQcnoJ/W2gcq3ZAaF+FLoF4IO8eh1ln4TwDEWNOYmKLSaMSCvSEFz9ROBBUpjsEFcDFUBG7b9q7rui9G+QQ69pk9x95BKbVFxDMivmut9wR8Ag1j+Oic2xljLrPBEQoAm9BEQwYO45FJZv9agpPN1TTNi3PuOL4xIk7g/izOaW7hIOJJa/08a4F4YyLYLzwGzNkBALm1RM3FwZdAReCS8vB4pLu4qHTWAqHgmdqJoGLFTC3Zl2h2c+UcltY0jSXa1alTB dend7Ka1Gbh/gc4UA89579IMRdUcn4DS7L0JzarpfobmFzyH+ztlXMAAAAASUVORK5CYII=" />
                  </button>
                </center>
                </a>
            </div>
          </div>
        </div>

        <!-- PRINT VERSION: Shows ALL records - by store -->
        <div class="col-md-12 print-content print-only">
          <?php foreach ($store_data as $store => $data): ?>
          <div class="card p-12 mb-12 print-section" style="margin-top: 2%; page-break-inside: avoid;">
            <center>
              <h3 style="padding-top: 2vh;">Store <?php echo $store; ?> - All Evaluations</h3>
            </center>
            <div style="overflow-x: auto;">
              <table class="summary-table">
                <tr>
                  <th width="15%">Time</th>
                  <th width="10%">Period</th>
                  <th width="20%">Attribute</th>
                  <th width="10%">Category</th>
                  <th width="10%">Store</th>
                  <th width="5%">Positive</th>
                  <th width="5%">Negative</th>
                  <th width="10%">Value</th>
                  <th width="5%">Mark</th>
                  <th width="20%">Comment</th>
                </tr>
                <?php
                // Re-fetch records for this specific store
                if (isset($_GET['sp']) && isset($_GET['ep'])) {
                  $sp = $_GET['sp'];
                  $ep = $_GET['ep'];
                  
                  if (isset($_GET['filter']) && $_GET['filter'] == "Period") {
                    $sql_all = "SELECT * FROM ((records R JOIN attribute A ON R.attribute_id = A.attribute_id) JOIN evaluator V ON R.evaluator_id = V.evaluator_id) WHERE R.emp_id = '$id' AND R.store = '$store' AND period BETWEEN '$sp' AND '$ep' ORDER BY period DESC;";
                  } else {
                    $sql_all = "SELECT * FROM ((records R JOIN attribute A ON R.attribute_id = A.attribute_id) JOIN evaluator V ON R.evaluator_id = V.evaluator_id) WHERE R.emp_id = '$id' AND R.store = '$store' AND period BETWEEN '$sp' AND '$ep' ORDER BY time DESC;";
                  }
                } else {
                  if (isset($_GET['filter']) && $_GET['filter'] == "Period") {
                    $sql_all = "SELECT * FROM ((records R JOIN attribute A ON R.attribute_id = A.attribute_id) JOIN evaluator V ON R.evaluator_id = V.evaluator_id) WHERE R.emp_id = '$id' AND R.store = '$store' ORDER BY period DESC;";
                  } else {
                    $sql_all = "SELECT * FROM ((records R JOIN attribute A ON R.attribute_id = A.attribute_id) JOIN evaluator V ON R.evaluator_id = V.evaluator_id) WHERE R.emp_id = '$id' AND R.store = '$store' ORDER BY time DESC;";
                  }
                }
                
                $result_all = $conn->query($sql_all);
                
                if ($result_all && $result_all->num_rows > 0) {
                  while ($row_all = $result_all->fetch_assoc()) {
                    // Add period to the row for grade2 function
                    $row_all['period'] = $row_all['period'];
                    // Check if value should be displayed based on is_valued
                    $isValuedAttr = isValueBasedAttribute($row_all['attribute_id'], $conn, $attributeTypeCache);
                    if ($isValuedAttr) {
                        // Value-based attribute - ALWAYS show value
                        $display_value = isset($row_all['value']) ? htmlspecialchars($row_all['value']) : '0';
                    } else {
                        // Critical Incident Based - ALWAYS show blank
                        $display_value = '';
                    }
                ?>
                <tr>
                  <td><?php echo htmlspecialchars($row_all['time']) ?></td>
                  <td><?php echo htmlspecialchars($row_all['period']) ?></td>
                  <td style="text-align: left; padding-left: 5px;"><?php echo htmlspecialchars($row_all['attribute_name']) ?></td>
                  <td><?php if ($row_all['category'] == 'Common') {
                        echo htmlspecialchars($row_all['category']);
                      } else {
                        echo htmlspecialchars($row['current_category']);
                      } ?></td>
                  <td><?php echo htmlspecialchars($row_all['store']) ?></td>
                  <td><?php echo htmlspecialchars($row_all['positive']) ?></td>
                  <td><?php echo htmlspecialchars($row_all['negative']) ?></td>
                  <td><?php echo $display_value; ?></td>
                  <td><?php echo grade2($row_all, $conn, $scoringMethodCache) ?></td>
                  <td style="text-align: left; padding-left: 3px;"><?php echo htmlspecialchars($row_all['comment']) ?></td>
                </tr>
                <?php } 
                } else { ?>
                <tr>
                  <td colspan="10" style="text-align: center; padding: 20px;">No evaluation records found for Store <?php echo $store; ?>.</td>
                </tr>
                <?php } ?>
              </table>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>

      <script src="https://canvasjs.com/assets/script/canvasjs.min.js"></script>
      <script>
        window.onload = function() {
          <?php
          $dataPoints = array(
            array("label" => (string)$period3, "y" => (float)$rate3),
            array("label" => (string)$period16, "y" => (float)$rate16),
            array("label" => (string)$period1, "y" => (float)$rate1),
          );

          $lineDataPoints = array(
            array("y" => (float)$rate15, "label" => $period15),
            array("y" => (float)$rate14, "label" => $period14),
            array("y" => (float)$rate13, "label" => $period13),
            array("y" => (float)$rate12, "label" => $period12),
            array("y" => (float)$rate11, "label" => $period11),
            array("y" => (float)$rate10, "label" => $period10),
            array("y" => (float)$rate9, "label" => $period9),
            array("y" => (float)$rate8, "label" => $period8),
            array("y" => (float)$rate7, "label" => $period7),
            array("y" => (float)$rate6, "label" => $period6),
            array("y" => (float)$rate5, "label" => $period5),
            array("y" => (float)$rate4, "label" => $period4),
          );
          ?>
          
          var dataPoints = <?php echo json_encode($dataPoints, JSON_NUMERIC_CHECK); ?>;
          var lineDataPoints = <?php echo json_encode($lineDataPoints, JSON_NUMERIC_CHECK); ?>;
          
          var chart = new CanvasJS.Chart("chartContainer", {
            animationEnabled: true,
            theme: "light2",
            title: {
              text: "Performance of Last Three Years"
            },
            axisY: {
              title: "Earning Score",
              minimum: 0,
              maximum: <?php echo $y_max; ?>,
              interval: <?php echo $y_interval; ?>
            },
            data: [{
              type: "column",
              dataPoints: dataPoints,
              color: "#4361ee"
            }]
          });
          chart.render();
          
          var lineChart = new CanvasJS.Chart("lineChartContainer", {
            animationEnabled: true,
            theme: "light2",
            title: {
              text: "Last 12 Month Performance"
            },
            axisY: {
              title: "Earning Score",
              minimum: 0,
              maximum: <?php echo $y_max; ?>,
              interval: <?php echo $y_interval; ?>
            },
            axisX: {
              labelAngle: -45
            },
            data: [{
              type: "line",
              dataPoints: lineDataPoints,
              color: "#10b981",
              lineThickness: 3
            }]
          });
          lineChart.render();
          
          window.chartData = {
            columnData: dataPoints,
            lineData: lineDataPoints
          };
        };
        
        function showStore(storeId) {
          // Hide all store sections
          document.querySelectorAll('.store-section').forEach(function(section) {
            section.classList.remove('active');
          });
          
          // Show selected store section
          document.getElementById('store-' + storeId).classList.add('active');
        }
        
        function printReport() {
          var screenElements = document.querySelectorAll('.screen-only');
          var printElements = document.querySelectorAll('.print-only');
          
          screenElements.forEach(function(el) {
            el.style.display = 'none';
          });
          
          printElements.forEach(function(el) {
            el.style.display = 'block';
          });
          
          if (typeof CanvasJS !== 'undefined' && window.chartData) {
            var printColumnChart = new CanvasJS.Chart("chartContainer", {
              animationEnabled: false,
              theme: "light2",
              title: {
                text: "Performance of Last Three Years",
                fontSize: 14
              },
              axisY: {
                title: "Earning Score",
                minimum: 0,
                maximum: <?php echo $y_max; ?>,
                interval: <?php echo $y_interval; ?>,
                gridThickness: 0,
                titleFontSize: 12,
                labelFontSize: 10
              },
              axisX: {
                labelFontSize: 10
              },
              data: [{
                type: "column",
                dataPoints: window.chartData.columnData,
                color: "#000000"
              }]
            });
            printColumnChart.render();
            
            var printLineChart = new CanvasJS.Chart("lineChartContainer", {
              animationEnabled: false,
              theme: "light2",
              title: {
                text: "Last 12 Month Performance",
                fontSize: 14
              },
              axisY: {
                title: "Earning Score",
                minimum: 0,
                maximum: <?php echo $y_max; ?>,
                interval: <?php echo $y_interval; ?>,
                gridThickness: 0,
                titleFontSize: 12,
                labelFontSize: 10
              },
              axisX: {
                labelFontSize: 10,
                labelAngle: -45
              },
              data: [{
                type: "line",
                dataPoints: window.chartData.lineData,
                color: "#000000",
                lineThickness: 2,
                markerSize: 3
              }]
            });
            printLineChart.render();
          }
          
          setTimeout(function() {
            window.print();
            
            setTimeout(function() {
              screenElements.forEach(function(el) {
                el.style.display = 'block';
              });
              
              printElements.forEach(function(el) {
                el.style.display = 'none';
              });
              
              if (typeof CanvasJS !== 'undefined' && window.chartData) {
                var chart = new CanvasJS.Chart("chartContainer", {
                  animationEnabled: true,
                  theme: "light2",
                  title: {
                    text: "Performance of Last Three Years"
                  },
                  axisY: {
                    title: "Earning Score",
                    minimum: 0,
                    maximum: <?php echo $y_max; ?>,
                    interval: <?php echo $y_interval; ?>
                  },
                  data: [{
                    type: "column",
                    dataPoints: window.chartData.columnData,
                    color: "#4361ee"
                  }]
                });
                chart.render();
                
                var lineChart = new CanvasJS.Chart("lineChartContainer", {
                  animationEnabled: true,
                  theme: "light2",
                  title: {
                    text: "Last 12 Month Performance"
                  },
                  axisY: {
                    title: "Earning Score",
                    minimum: 0,
                    maximum: <?php echo $y_max; ?>,
                    interval: <?php echo $y_interval; ?>
                  },
                  axisX: {
                    labelAngle: -45
                  },
                  data: [{
                    type: "line",
                    dataPoints: window.chartData.lineData,
                    color: "#10b981",
                    lineThickness: 3
                  }]
                });
                lineChart.render();
              }
            }, 500);
          }, 500);
        }
      </script>

    </body>

    </html>

<?php }
} else {
  header("location: signin.php");
}
?>