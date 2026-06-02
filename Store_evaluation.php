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

  // Initialize variables to avoid warnings
  $store = $att = $rowfind = $rowa = null;
  $resultfind = $resulta = $resultatt = $lastEvaluationsResult = null;

  // Calculate allowed period ranges based on user role
  $current_date = new DateTime();
  $current_month = $current_date->format('Y-m');
  $previous_month = (clone $current_date)->modify('-1 month')->format('Y-m');
  
  // For Admin: current month and previous year
  if ($_SESSION['isAdmin']) {
    $min_date = (clone $current_date)->modify('-1 year')->format('Y-m');
    $max_date = $current_month;
  } 
  // For Evaluator: current month and previous month only
  else if ($_SESSION["isEvaluator"]) {
    $min_date = (clone $current_date)->modify('-1 month')->format('Y-m');
    $max_date = $current_month;
  } 
  // For Guest or other roles
  else {
    $min_date = (clone $current_date)->modify('-1 year')->format('Y-m');
    $max_date = $current_month;
  }

  if (isset($_GET['st']) and $_GET['att']) {

    $store = $conn->real_escape_string($_GET['st']);
    $att = $conn->real_escape_string($_GET['att']);

    $sql = "SELECT * FROM store WHERE store_number ='$store'";
    $resultfind = $conn->query($sql);

    $sqla = "SELECT * FROM attribute WHERE attribute_id ='$att'";
    $resulta = $conn->query($sqla);
    
    if ($resulta && $resulta->num_rows > 0) {
      $rowa = $resulta->fetch_assoc();
    }

    // Get last evaluations for this store
    $lastEvaluationsSql = "SELECT r.*, a.attribute_name, e.emp_name 
                          FROM records r 
                          JOIN attribute a ON r.attribute_id = a.attribute_id 
                          JOIN employee e ON r.emp_id = e.emp_id 
                          WHERE r.store = '$store' 
                          ORDER BY r.time DESC 
                          LIMIT 5";
    $lastEvaluationsResult = $conn->query($lastEvaluationsSql);

    if ($resultfind && $resultfind->num_rows == 1) {
      $rowfind = $resultfind->fetch_assoc();
      $category1 = $rowfind['category_name'];

      if ($category1 == "Flavoring") {
        $sqlatt = "SELECT * FROM attribute WHERE category ='Packing' OR category ='Common'";
        $resultatt = $conn->query($sqlatt);
      } else {
        $sqlatt = "SELECT * FROM attribute WHERE category ='$category1' OR category ='Common'";
        $resultatt = $conn->query($sqlatt);
      }
    } else {
      header("location: Warehouses.php");
      exit();
    }
  }

  // Handle confirmation POST request for valued attributes
  if (isset($_POST['confirmed_action'])) {
    if ($_POST['confirmed_action'] == 'update') {
      // Process the update after user confirmation
      $storeNumber = $conn->real_escape_string($_POST['storeNumber']);
      $category = $conn->real_escape_string($_POST['category']);
      $attribute = $conn->real_escape_string($_POST['attribute']);
      $period = $conn->real_escape_string($_POST['period']);
      $comment = $conn->real_escape_string(isset($_POST['comment']) ? $_POST['comment'] : '');
      $evaluator = $_SESSION["id"];

      // Validate attribute exists
      $sqlv = "SELECT * FROM attribute WHERE attribute_id ='$attribute'";
      $resultv = $conn->query($sqlv);
      if ($resultv && $resultv->num_rows > 0) {
        $rowv = $resultv->fetch_assoc();
        $attributeId = $rowv['attribute_id'];

        // Get all employees in this store
        $empSql = "SELECT * FROM employee WHERE current_store = '$storeNumber'";
        $empResult = $conn->query($empSql);
        
        // DELETE OLD RECORDS FIRST for each employee
        while ($empRow = $empResult->fetch_assoc()) {
          $empNumber = $empRow['emp_id'];
          
          $deleteSql = "DELETE FROM evaluation WHERE emp_id = '$empNumber' AND period = '$period' AND attribute_id = '$attributeId'";
          $conn->query($deleteSql);
          
          $deleteRecordsSql = "DELETE FROM records WHERE emp_id = '$empNumber' AND period = '$period' AND attribute_id = '$attributeId'";
          $conn->query($deleteRecordsSql);
        }

        // Now continue with normal processing
        $isConfirmedUpdate = true;
      } else {
        echo "<script>alert('Invalid attribute selected!'); window.location.href='Warehouses.php';</script>";
        exit();
      }
    }
  }

  // Handle regular form submission
  if (isset($_POST['submit'])) {

    $store = $conn->real_escape_string($_POST['storeNumber']);
    $storeNumber = $conn->real_escape_string($_POST['storeNumber']);
    $category = $conn->real_escape_string($_POST['category']);
    $period = $conn->real_escape_string($_POST['period']);
    $comment = $conn->real_escape_string(isset($_POST['comment']) ? $_POST['comment'] : '');
    $evaluator = $_SESSION["id"];
    
    // Get the attribute from either POST or GET
    if (isset($_POST['attribute'])) {
      $attribute = $conn->real_escape_string($_POST['attribute']);
    } elseif (isset($_GET['att'])) {
      $attribute = $conn->real_escape_string($_GET['att']);
    } else {
      echo "<script>alert('No attribute selected!'); window.location.href='Warehouses.php';</script>";
      exit();
    }

    // Validate attribute exists
    $sqlv = "SELECT * FROM attribute WHERE attribute_id ='$attribute'";
    $resultv = $conn->query($sqlv);
    
    if (!$resultv || $resultv->num_rows == 0) {
      echo "<script>alert('Invalid attribute selected!'); window.location.href='Warehouses.php';</script>";
      exit();
    }
    
    $rowv = $resultv->fetch_assoc();
    $attributeId = $rowv['attribute_id'];

    // Check if this is a confirmed update request
    if (!isset($isConfirmedUpdate)) {
      // Check if any employee in this store already has this valued attribute evaluation
      if ($rowv['is_valued'] == 1) {
        $checkDuplicateSql = "SELECT e.* FROM evaluation e 
                             JOIN employee emp ON e.emp_id = emp.emp_id 
                             WHERE emp.current_store = '$store' 
                             AND e.period = '$period' 
                             AND e.attribute_id = '$attributeId' 
                             LIMIT 1";
        $checkDuplicateResult = $conn->query($checkDuplicateSql);
        
        // Show confirmation for valued attributes with existing records
        if ($checkDuplicateResult && $checkDuplicateResult->num_rows > 0) {
          // Store form data in session for confirmation page
          $_SESSION['confirmation_data'] = array(
            'storeNumber' => $storeNumber,
            'category' => $category,
            'attribute' => $attribute,
            'period' => $period,
            'comment' => $comment,
            'evaluator' => $evaluator,
            'mark' => isset($_POST['mark']) ? $_POST['mark'] : '',
            'status' => isset($_POST['status']) ? $_POST['status'] : '',
            'value' => isset($_POST['value']) ? $_POST['value'] : '',
            'lateComings' => isset($_POST['lateComings']) ? $_POST['lateComings'] : '',
            'absets' => isset($_POST['absets']) ? $_POST['absets'] : '',
            'conducts' => isset($_POST['conducts']) ? $_POST['conducts'] : '',
            'attributeId' => $attributeId,
            'rowv' => $rowv
          );
          
          // Show confirmation page
          $show_confirmation = true;
        } else {
          // No duplicate found OR it's a non-valued attribute, process directly
          $process_directly = true;
        }
      } else {
        // Non-valued attribute, process directly
        $process_directly = true;
      }
    } else {
      // This is a confirmed update, process directly
      $process_directly = true;
    }

    // If we need to process the evaluation (no confirmation needed or confirmed)
    if (isset($process_directly) && $process_directly) {
      
      // Get all employees in the store
      $sqlep = "SELECT * FROM employee WHERE current_store = '$store'";
      $resultep = $conn->query($sqlep);
      
      // Track if all queries were successful
      $allQueriesSuccessful = true;
      $errorMessage = '';
      
      while ($rowep = $resultep->fetch_assoc()) {
        $empNumber = $rowep['emp_id'];
        $empName = $rowep['emp_name'];

        // Check if employee has evaluations for this period
        $sql1 = "SELECT * FROM evaluation WHERE emp_id = '$empNumber' and period = '$period'";
        $result1 = $conn->query($sql1);
        
        // Insert default neutral values for non-valued attributes if no evaluation exists
        if ($result1 && $result1->num_rows == 0) {
          $sql2 = "SELECT * FROM employee WHERE emp_id ='$empNumber'";
          $result2 = $conn->query($sql2);
          if ($result2 && $result2->num_rows > 0) {
            $row2 = $result2->fetch_assoc();
            $empCategory = $row2['current_category'];
            if ($empCategory == "Flavoring") {
              $sql3 = "SELECT * FROM attribute WHERE category ='Packing' OR category = 'Common'";
              $result3 = $conn->query($sql3);
            } else {
              $sql3 = "SELECT * FROM attribute WHERE category ='$empCategory' OR category = 'Common'";
              $result3 = $conn->query($sql3);
            }
            if ($result3) {
              while ($row3 = $result3->fetch_assoc()) {
                if ($row3['attribute_id'] != $attribute && $row3['is_valued'] == 0) {
                  $att = $row3['attribute_id'];
                  $sql4 = "INSERT INTO evaluation(emp_id, period, category, store, attribute_id, positive, neutral, negative)
                  VALUES ('$empNumber','$period','$category','$storeNumber','$att','0','1','0')";
                  if (!$conn->query($sql4)) {
                    $allQueriesSuccessful = false;
                    $errorMessage .= "Error inserting default evaluation: " . $conn->error . "<br>";
                  }
                }
              }
            }
          }
        }

        // Handle the actual evaluation insertion/update
        if ($rowv['is_valued'] == 0) {
          $mark = isset($_POST['mark']) ? $_POST['mark'] : '+1';

          $presql = "SELECT * FROM evaluation WHERE emp_id = '$empNumber' and period = '$period' and store = '$storeNumber' and attribute_id ='$attributeId'";
          $preresult = $conn->query($presql);
          
          if (!$preresult || $preresult->num_rows == 0) {
            if ($mark == '+1') {
              $sql = "INSERT INTO evaluation(emp_id, period, category, store, attribute_id, positive, neutral, negative)
              VALUES ('$empNumber','$period','$category','$storeNumber','$attributeId','1','0','0')";
            } else if ($mark == '0') {
              $sql = "INSERT INTO evaluation(emp_id, period, category, store, attribute_id, positive, neutral, negative)
              VALUES ('$empNumber','$period','$category','$storeNumber','$attributeId','0','1','0')";
            } else if ($mark == '-1') {
              $sql = "INSERT INTO evaluation(emp_id, period, category, store, attribute_id, positive, neutral, negative)
              VALUES ('$empNumber','$period','$category','$storeNumber','$attributeId','0','0','1')";
            } else if ($mark == '-2') {
              $sql = "INSERT INTO evaluation(emp_id, period, category, store, attribute_id, positive, neutral, negative, status)
              VALUES ('$empNumber','$period','$category','$storeNumber','$attributeId','0','0','0','-2')";
            }
          } else {
            $prerow = $preresult->fetch_assoc();
            $id = $prerow['evaluation_id'];
            if ($mark == '+1') {
              $sql = "UPDATE evaluation
              SET positive = positive + 1
              WHERE evaluation_id = '$id'";
            } else if ($mark == '0') {
              $sql = "UPDATE evaluation
              SET neutral = neutral + 1
              WHERE evaluation_id = '$id'";
            } else if ($mark == '-1') {
              $sql = "UPDATE evaluation
              SET negative = negative + 1
              WHERE evaluation_id = '$id'";
            } else if ($mark == '-2') {
              $sql = "UPDATE evaluation
              SET neutral = neutral + 1
              WHERE evaluation_id = '$id'";
            }
          }
        } else if ($rowv['is_valued'] == 1) {
          if ($rowv['scoring_method'] == 'Discipline') {
            $lateComings = isset($_POST['lateComings']) ? intval($_POST['lateComings']) : 0;
            $absets = isset($_POST['absets']) ? intval($_POST['absets']) : 0;
            $conducts = isset($_POST['conducts']) ? intval($_POST['conducts']) : 0;
            $value = $lateComings * (1) + $absets * (5) + $conducts * (-5);
          } else {
            if (isset($_POST['status']) && ($_POST['status'] == -1 || $_POST['status'] == -2)) {
              $status = $_POST['status'];
              $value = NULL;
            } else if (isset($_POST['status']) && $_POST['status'] == 0) {
              $value = isset($_POST['value']) ? $_POST['value'] : 0;
              $status = NULL;
            } else {
              $value = isset($_POST['value']) ? $_POST['value'] : 0;
              $status = NULL;
            }
          }

          $presql = "SELECT * FROM evaluation WHERE emp_id = '$empNumber' and period = '$period' and store = '$storeNumber' and attribute_id ='$attributeId'";
          $preresult = $conn->query($presql);
          if (!$preresult || $preresult->num_rows == 0) {
            if (isset($status) && !isset($value)) {
              $sql = "INSERT INTO evaluation(emp_id, period, category, store, attribute_id, status) 
              VALUES ('$empNumber','$period','$category','$storeNumber','$attributeId','$status')";
            } else if (isset($value) && !isset($status)) {
              $sql = "INSERT INTO evaluation(emp_id, period, category, store, attribute_id, value) 
              VALUES ('$empNumber','$period','$category','$storeNumber','$attributeId','$value')";
            } else {
              $sql = "INSERT INTO evaluation(emp_id, period, category, store, attribute_id, value, status) 
              VALUES ('$empNumber','$period','$category','$storeNumber','$attributeId','$value','$status')";
            }
          } else {
            $prerow = $preresult->fetch_assoc();
            $id = $prerow['evaluation_id'];
            
            // For valued attributes, calculate average if updating
            if (isset($value) && $prerow['value'] !== null) {
              $newValue = ($value + $prerow['value']) / 2.0;
              $sql = "UPDATE evaluation
              SET value = '$newValue', status = NULL
              WHERE evaluation_id = '$id'";
            } else if (isset($value)) {
              $sql = "UPDATE evaluation
              SET value = '$value', status = NULL
              WHERE evaluation_id = '$id'";
            } else if (isset($status)) {
              $sql = "UPDATE evaluation
              SET value = NULL, status = '$status'
              WHERE evaluation_id = '$id'";
            }
          }
        }

        // Execute the evaluation query
        if (isset($sql) && !$conn->query($sql)) {
          $allQueriesSuccessful = false;
          $errorMessage .= "Error for employee $empNumber: " . $conn->error . "<br>";
        }

        // Insert into records table
        if ($rowv['is_valued'] == 0) {
          $mark = isset($_POST['mark']) ? $_POST['mark'] : '+1';
          if ($mark == '+1') {
            $sqlr = "INSERT INTO records(emp_id, emp_name, period, evaluator_id, category, store, attribute_id, positive, neutral, negative, comment)
            VALUES ('$empNumber','$empName','$period','$evaluator','$category','$storeNumber','$attributeId','1','0','0','$comment')";
          } else if ($mark == '0') {
            $sqlr = "INSERT INTO records(emp_id, emp_name, period, evaluator_id, category, store, attribute_id, positive, neutral, negative, comment)
            VALUES ('$empNumber','$empName','$period','$evaluator','$category','$storeNumber','$attributeId','0','1','0','$comment')";
          } else if ($mark == '-1') {
            $sqlr = "INSERT INTO records(emp_id, emp_name, period, evaluator_id, category, store, attribute_id, positive, neutral, negative, comment)
            VALUES ('$empNumber','$empName','$period','$evaluator','$category','$storeNumber','$attributeId','0','0','1','$comment')";
          } else if ($mark == '-2') {
            $sqlr = "INSERT INTO records(emp_id, emp_name, period, evaluator_id, category, store, attribute_id, positive, neutral, negative, comment)
            VALUES ('$empNumber','$empName','$period','$evaluator','$category','$storeNumber','$attributeId','0','0','0','$comment')";
          }
        } else if ($rowv['is_valued'] == 1) {
          if ($rowv['scoring_method'] == 'Discipline') {
            $lateComings = isset($_POST['lateComings']) ? intval($_POST['lateComings']) : 0;
            $absets = isset($_POST['absets']) ? intval($_POST['absets']) : 0;
            $conducts = isset($_POST['conducts']) ? intval($_POST['conducts']) : 0;
            $value = $lateComings * (1) + $absets * (5) + $conducts * (-5);
          } else {
            if (isset($_POST['status']) && ($_POST['status'] == -1 || $_POST['status'] == -2)) {
              $status = $_POST['status'];
            } else if (isset($_POST['status']) && $_POST['status'] == 0) {
              $value = isset($_POST['value']) ? $_POST['value'] : 0;
            } else {
              $value = isset($_POST['value']) ? $_POST['value'] : 0;
            }
          }
          
          if (isset($status) && !isset($value)) {
            $sqlr = "INSERT INTO records(emp_id, emp_name, period, evaluator_id, category, store, attribute_id, status, comment) 
            VALUES ('$empNumber','$empName','$period','$evaluator','$category','$storeNumber','$attributeId','$status','$comment')";
          } else if (isset($value) && !isset($status)) {
            $sqlr = "INSERT INTO records(emp_id, emp_name, period, evaluator_id, category, store, attribute_id, value, comment) 
            VALUES ('$empNumber','$empName','$period','$evaluator','$category','$storeNumber','$attributeId','$value','$comment')";
          } else {
            $sqlr = "INSERT INTO records(emp_id, emp_name, period, evaluator_id, category, store, attribute_id, value, status, comment) 
            VALUES ('$empNumber','$empName','$period','$evaluator','$category','$storeNumber','$attributeId','$value','$status','$comment')";
          }
        }

        // Execute the records query
        if (isset($sqlr) && !$conn->query($sqlr)) {
          $allQueriesSuccessful = false;
          $errorMessage .= "Error in records for employee $empNumber: " . $conn->error . "<br>";
        }
      }

      // Clear session data
      if (isset($_SESSION['confirmation_data'])) {
        unset($_SESSION['confirmation_data']);
      }

      if ($allQueriesSuccessful) {
        $message = isset($isConfirmedUpdate) ? "Store Evaluation Updated Successfully!" : "Store Evaluation Added Successfully!";
        echo "<script>alert('$message');
        window.location.href='Warehouses.php';
        </script>";
      } else {
        echo "<script>
        alert('Some errors occurred:\\n" . addslashes($errorMessage) . "');
        window.location.href='Warehouses.php';
        </script>";
      }
    }
  }
?>

  <!DOCTYPE html>
  <html>

  <head>
    <meta charset="utf-8" />
    <title>Store Evaluation | JB Employee Evaluation</title>
    <link rel="icon" type="image/x-icon" href="./img/jb.png">
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />

    <link rel="stylesheet" href="fonts/material-design-iconic-font/css/material-design-iconic-font.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta1/dist/css/bootstrap.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta1/dist/js/bootstrap.bundle.min.js" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.2.1/jquery.min.js" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/boxicons@latest/css/boxicons.min.css" />
    
    <!-- Modern Font Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />

    <link rel="stylesheet" href="./style.css" />
    <script src="./script.js"></script>
    <link rel="stylesheet" href="css/style.css" />
    
    <!-- Add Confirmation Modal CSS -->
    <style>
      .confirmation-modal {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.7);
        display: flex;
        justify-content: center;
        align-items: center;
        z-index: 1000;
      }

      .confirmation-box {
        background: white;
        border-radius: 16px;
        padding: 2.5rem;
        max-width: 500px;
        width: 90%;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        animation: fadeIn 0.3s ease-out;
      }

      .confirmation-header {
        text-align: center;
        margin-bottom: 1.5rem;
      }

      .confirmation-header h4 {
        color: #ef4444;
        font-size: 1.5rem;
        margin-bottom: 0.5rem;
      }

      .confirmation-body {
        text-align: center;
        margin-bottom: 2rem;
      }

      .confirmation-body p {
        color: #1e293b;
        font-size: 1.1rem;
        margin-bottom: 1rem;
        line-height: 1.6;
      }

      .confirmation-actions {
        display: flex;
        justify-content: center;
        gap: 1.5rem;
        margin-top: 2rem;
      }

      .btn-confirm {
        background: #ef4444;
        color: white;
        border: none;
        padding: 12px 30px;
        border-radius: 10px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s;
      }

      .btn-confirm:hover {
        background: #dc2626;
        transform: translateY(-2px);
      }

      .btn-cancel {
        background: #64748b;
        color: white;
        border: none;
        padding: 12px 30px;
        border-radius: 10px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s;
      }

      .btn-cancel:hover {
        background: #475569;
        transform: translateY(-2px);
      }
    </style>

    <!-- Modern CSS for Store Evaluation Form -->
    <style>
      :root {
        --primary-color: #4361ee;
        --primary-dark: #3a56d4;
        --secondary-color: #3f37c9;
        --accent-color: #4cc9f0;
        --success-color: #4ade80;
        --warning-color: #f59e0b;
        --danger-color: #ef4444;
        --light-color: #f8fafc;
        --dark-color: #1e293b;
        --gray-color: #64748b;
        --light-gray: #e2e8f0;
        --border-radius: 16px;
        --box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        --box-shadow-light: 0 5px 20px rgba(0, 0, 0, 0.05);
        --transition: all 0.3s ease;
      }

      .store-evaluation-wrapper {
        margin-top: 12vh;
        padding: 2rem;
        display: flex;
        justify-content: center;
        align-items: flex-start;
        gap: 3rem;
        flex-wrap: wrap;
        min-height: calc(100vh - 12vh);
      }

      .main-container {
        display: flex;
        gap: 3rem;
        width: 100%;
        max-width: 1400px;
        margin: 0 auto;
      }

      .evaluation-form-container {
        flex: 2;
        min-width: 350px;
      }

      .last-evaluations-container {
        flex: 1;
        min-width: 350px;
        position: sticky;
        top: 100px;
        height: fit-content;
      }

      .form-container {
        background: white;
        border-radius: var(--border-radius);
        box-shadow: var(--box-shadow);
        padding: 2.5rem;
        width: 100%;
        transition: var(--transition);
        animation: fadeIn 0.6s ease-out;
      }

      .form-container:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 40px rgba(0, 0, 0, 0.15);
      }

      .form-header {
        text-align: center;
        margin-bottom: 2.5rem;
      }

      .form-header h3 {
        color: var(--dark-color);
        font-weight: 700;
        font-size: 2rem;
        position: relative;
        padding-bottom: 1.2rem;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      }

      .form-header h3::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 50%;
        transform: translateX(-50%);
        width: 100px;
        height: 4px;
        background: linear-gradient(90deg, var(--primary-color), var(--secondary-color));
        border-radius: 2px;
      }

      .form-header p {
        color: var(--gray-color);
        font-size: 1rem;
        margin-top: 0.5rem;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      }

      .form-row {
        display: flex;
        flex-wrap: wrap;
        gap: 1.5rem;
        margin-bottom: 1.8rem;
      }

      .form-holder {
        flex: 1;
        min-width: 220px;
        position: relative;
      }

      .form-holder.w-100 {
        flex: 1 0 100%;
      }

      .form-control {
        width: 100%;
        padding: 15px 18px;
        border: 2px solid var(--light-gray);
        border-radius: 12px;
        font-size: 15px;
        background: var(--light-color);
        transition: var(--transition);
        color: var(--dark-color);
        font-weight: 500;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      }

      .form-control:focus {
        outline: none;
        border-color: var(--primary-color);
        background: white;
        box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.1);
      }

      .form-control:read-only {
        background-color: #f1f8ff;
        color: #5a6c7d;
        cursor: not-allowed;
      }

      .form-control:read-only:focus {
        border-color: var(--light-gray);
        box-shadow: none;
      }

      .form-holder label {
        display: block;
        margin-bottom: 10px;
        color: var(--dark-color);
        font-weight: 600;
        font-size: 14px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      }

      select.form-holder {
        width: 100%;
        padding: 15px 18px;
        border: 2px solid var(--light-gray);
        border-radius: 12px;
        font-size: 15px;
        background: var(--light-color);
        color: var(--dark-color);
        font-weight: 500;
        cursor: pointer;
        transition: var(--transition);
        appearance: none;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='%234a5568' viewBox='0 0 16 16'%3E%3Cpath d='M7.247 11.14 2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 18px center;
        background-size: 16px;
        padding-right: 45px;
      }

      select.form-holder:focus {
        outline: none;
        border-color: var(--primary-color);
        background: white;
        box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.1);
      }

      /* Modern Radio Buttons */
      .checkbox-tick {
        display: flex;
        gap: 1.5rem;
        flex-wrap: wrap;
        padding: 12px 0;
      }

      .checkbox-tick label {
        position: relative;
        padding-left: 35px;
        cursor: pointer;
        font-weight: 600;
        font-size: 14px;
        display: inline-flex;
        align-items: center;
        margin-bottom: 0;
        transition: var(--transition);
        border-radius: 10px;
        padding: 10px 15px 10px 35px;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      }

      .checkbox-tick label:hover {
        background: var(--light-color);
        transform: translateY(-2px);
      }

      .checkbox-tick input[type="radio"] {
        position: absolute;
        opacity: 0;
        width: 0;
        height: 0;
      }

    .checkbox-tick .checkmark {
      position: absolute;
      left: 10px;
      top: 9px;
      height: 22px;
      width: 22px;
      border: 2px solid #cbd5e0;
      border-radius: 50%;
      transition: var(--transition);
    }


      .checkbox-tick input[type="radio"]:checked ~ .checkmark {
        border-color: currentColor;
        transform: scale(1.1);
      }

     .checkbox-tick .checkmark::after {
    content: "";
    position: absolute;
    display: none;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 12px;
    height: 12px;
    border-radius: 50%;
    background: #000000;  /* BOLD BLACK DOT when selected */
}

      .checkbox-tick input[type="radio"]:checked ~ .checkmark::after {
        display: block;
      }

      .checkbox-tick label.positive {
        color: var(--success-color);
      }

      .checkbox-tick label.neutral {
        color: var(--warning-color);
      }

      .checkbox-tick label.negative {
        color: var(--danger-color);
      }

      .checkbox-tick label.neglect {
        color: var(--gray-color);
      }

      /* Textarea */
      textarea.form-control {
        min-height: 110px;
        resize: vertical;
        line-height: 1.6;
        font-family: inherit;
        padding: 15px 18px;
      }

      /* Submit Button */
      .form-button {
        background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
        color: white;
        border: none;
        padding: 17px 45px;
        font-size: 16px;
        font-weight: 600;
        border-radius: 12px;
        cursor: pointer;
        transition: var(--transition);
        box-shadow: 0 5px 20px rgba(67, 97, 238, 0.3);
        text-transform: uppercase;
        letter-spacing: 1px;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        position: relative;
        overflow: hidden;
      }

      .form-button::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
        transition: 0.5s;
      }

      .form-button:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 25px rgba(67, 97, 238, 0.4);
        background: linear-gradient(135deg, var(--primary-dark) 0%, var(--secondary-color) 100%);
      }

      .form-button:hover::before {
        left: 100%;
      }

      .form-button:active {
        transform: translateY(-1px);
      }

      /* Last Evaluations Card */
      .last-evaluations-card {
        background: white;
        border-radius: var(--border-radius);
        box-shadow: var(--box-shadow);
        padding: 2rem;
        width: 100%;
        transition: var(--transition);
        animation: fadeIn 0.6s ease-out 0.2s;
        animation-fill-mode: both;
        max-height: 700px;
        overflow-y: auto;
      }

      .last-evaluations-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 40px rgba(0, 0, 0, 0.15);
      }

      .last-evaluations-card h4 {
        color: var(--dark-color);
        font-weight: 700;
        font-size: 1.6rem;
        margin-bottom: 1.8rem;
        text-align: center;
        position: relative;
        padding-bottom: 1rem;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      }

      .last-evaluations-card h4::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 50%;
        transform: translateX(-50%);
        width: 80px;
        height: 3px;
        background: linear-gradient(90deg, var(--primary-color), var(--accent-color));
        border-radius: 2px;
      }

      .evaluations-list {
        display: flex;
        flex-direction: column;
        gap: 1rem;
      }

      .evaluation-item {
        background: var(--light-color);
        padding: 1.2rem;
        border-radius: 10px;
        border-left: 4px solid var(--primary-color);
        transition: var(--transition);
        position: relative;
      }

      .evaluation-item:hover {
        background: #edf2f7;
        transform: translateX(5px);
      }

      .evaluation-item.pending {
        border-left-color: var(--warning-color);
      }

      .evaluation-item.completed {
        border-left-color: var(--success-color);
      }

      .evaluation-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 10px;
        padding-bottom: 8px;
        border-bottom: 1px dashed var(--light-gray);
      }

      .evaluation-attribute {
        font-weight: 600;
        color: var(--dark-color);
        font-size: 14px;
        display: flex;
        align-items: center;
        gap: 8px;
      }

      .evaluation-date {
        font-size: 12px;
        color: var(--gray-color);
        background: white;
        padding: 4px 10px;
        border-radius: 20px;
        font-weight: 500;
      }

      .evaluation-details {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 8px;
      }

      .evaluation-rating {
        font-size: 15px;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 6px;
      }

      .rating-positive {
        color: var(--success-color);
      }

      .rating-neutral {
        color: var(--warning-color);
      }

      .rating-negative {
        color: var(--danger-color);
      }

      .rating-neglected {
        color: var(--gray-color);
      }

      .evaluation-value {
        font-size: 14px;
        font-weight: 600;
        color: var(--primary-color);
        background: #e0e7ff;
        padding: 4px 12px;
        border-radius: 20px;
      }

      .evaluation-employee {
        font-size: 13px;
        color: var(--dark-color);
        font-weight: 600;
        margin-bottom: 5px;
        display: flex;
        align-items: center;
        gap: 8px;
      }

      .evaluation-comment {
        font-size: 13px;
        color: var(--gray-color);
        margin-top: 8px;
        padding-top: 8px;
        border-top: 1px solid #f1f1f1;
        font-style: italic;
        display: flex;
        align-items: flex-start;
        gap: 8px;
      }

      .evaluation-comment i {
        color: var(--primary-color);
        margin-top: 2px;
      }

      .evaluation-period {
        font-size: 11px;
        color: var(--gray-color);
        margin-top: 5px;
        display: flex;
        align-items: center;
        gap: 5px;
      }

      .no-evaluations {
        text-align: center;
        padding: 2rem;
        color: var(--gray-color);
        font-style: italic;
      }

      .no-evaluations i {
        font-size: 3rem;
        margin-bottom: 1rem;
        color: var(--light-gray);
      }

      /* Store Info */
      .store-info {
        background: linear-gradient(135deg, #e0e7ff, #c7d2fe);
        padding: 1.2rem;
        border-radius: 10px;
        margin-bottom: 1.5rem;
        text-align: center;
      }

      .store-name {
        font-weight: 700;
        color: var(--primary-color);
        font-size: 1.3rem;
        margin-bottom: 0.5rem;
      }

      .store-details {
        display: flex;
        justify-content: center;
        gap: 1.5rem;
        flex-wrap: wrap;
        margin-top: 0.8rem;
      }

      .store-detail {
        background: white;
        padding: 8px 15px;
        border-radius: 8px;
        font-size: 13px;
        font-weight: 600;
        color: var(--primary-color);
        box-shadow: var(--box-shadow-light);
      }

      .store-detail i {
        margin-right: 5px;
        color: var(--secondary-color);
      }

      /* Discipline Inputs */
      .discipline-inputs {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 1.2rem;
        margin-top: 1.2rem;
      }

      .discipline-inputs .form-control {
        background: white;
        text-align: center;
        font-weight: 600;
        padding: 15px 10px;
      }

      .discipline-inputs .form-holder {
        min-width: auto;
      }

      .discipline-inputs label {
        text-align: center;
        font-size: 11px !important;
        margin-bottom: 6px;
      }

      /* Evaluation Section Styling */
      .evaluation-section {
        background: var(--light-color);
        border-radius: 12px;
        padding: 1.8rem;
        margin-top: 1.2rem;
        border: 2px dashed var(--light-gray);
        transition: var(--transition);
      }

      .evaluation-section:hover {
        border-color: var(--primary-color);
        background: #edf2f7;
      }

      .evaluation-section h5 {
        color: var(--dark-color);
        font-size: 14px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 1.2rem;
        font-weight: 600;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        display: flex;
        align-items: center;
        gap: 10px;
      }

      .evaluation-section h5 i {
        color: var(--primary-color);
        font-size: 16px;
      }

      .info-text {
        margin-top: 10px;
        font-size: 13px;
        color: var(--gray-color);
        padding: 10px;
        background: rgba(0, 0, 0, 0.02);
        border-radius: 8px;
        border-left: 3px solid var(--primary-color);
      }

      .info-text i {
        margin-right: 5px;
      }

      /* Warning Badge */
      .warning-badge {
        background: linear-gradient(to right, #fee2e2, #fecaca);
        color: #dc2626;
        padding: 8px 16px;
        border-radius: 8px;
        font-weight: 600;
        margin-top: 15px;
        font-size: 13px;
        display: flex;
        align-items: center;
        gap: 8px;
      }

      /* Scrollbar Styling */
      .last-evaluations-card::-webkit-scrollbar {
        width: 6px;
      }

      .last-evaluations-card::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 10px;
      }

      .last-evaluations-card::-webkit-scrollbar-thumb {
        background: var(--primary-color);
        border-radius: 10px;
      }

      .last-evaluations-card::-webkit-scrollbar-thumb:hover {
        background: var(--primary-dark);
      }

      /* Animation */
      @keyframes fadeIn {
        from {
          opacity: 0;
          transform: translateY(30px);
        }
        to {
          opacity: 1;
          transform: translateY(0);
        }
      }

      /* Hide old form */
      .wrapper, .editForm, .form-row.old, .form-holder.old, .select.old, .image-holder {
        display: none !important;
      }

      /* Responsive Design */
      @media (max-width: 1200px) {
        .main-container {
          flex-direction: column;
        }
        
        .last-evaluations-container {
          position: static;
          margin-top: 2rem;
        }
        
        .evaluation-form-container,
        .last-evaluations-container {
          width: 100%;
        }
      }

      @media (max-width: 768px) {
        .store-evaluation-wrapper {
          padding: 1.5rem;
          margin-top: 10vh;
        }

        .form-container {
          padding: 2rem;
        }

        .form-row {
          flex-direction: column;
          gap: 1.2rem;
        }

        .form-holder {
          min-width: 100%;
        }

        .checkbox-tick {
          flex-direction: column;
          gap: 1rem;
        }

        .discipline-inputs {
          grid-template-columns: 1fr;
        }

        .form-header h3 {
          font-size: 1.7rem;
        }

        .store-details {
          flex-direction: column;
          gap: 0.8rem;
        }

        .evaluation-header {
          flex-direction: column;
          align-items: flex-start;
          gap: 8px;
        }

        .evaluation-date {
          align-self: flex-start;
        }

        .confirmation-actions {
          flex-direction: column;
        }
      }

      @media (max-width: 480px) {
        .store-evaluation-wrapper {
          padding: 1rem;
        }

        .form-container {
          padding: 1.5rem;
        }

        .form-button {
          width: 100%;
          padding: 16px;
        }

        .last-evaluations-card {
          padding: 1.5rem;
        }
      }
    </style>

    <script type="text/javascript">
      function validate() {
        var comment = document.addForm.comment.value;
        var letters = /^[A-Za-z0-9.,\/:;!`'"|<>_\-?+@#%^&*~=+(){}\[\]\s\\\-]+$/;

        if (comment.trim().length > 0) {
          if (!comment.match(letters)) {
            alert("Comment contains invalid characters. Only letters, numbers, spaces, and common punctuation are allowed.");
            return false;
          }
        }
        return true;
      }
      
      function hideInputField() {
        var valueInput = document.querySelector('input[name="value"]');
        if (valueInput) {
          valueInput.style.display = 'none';
          valueInput.removeAttribute('required');
        }
      }

      function showInputField() {
        var valueInput = document.querySelector('input[name="value"]');
        if (valueInput) {
          valueInput.style.display = 'block';
          valueInput.setAttribute('required', 'required');
        }
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

    <!-- Show confirmation modal if duplicate exists for valued attributes -->
    <?php if (isset($show_confirmation) && $show_confirmation): ?>
    <div class="confirmation-modal">
      <div class="confirmation-box">
        <div class="confirmation-header">
          <h4><i class="fas fa-exclamation-triangle"></i> Duplicate Store Evaluation Found</h4>
        </div>
        <div class="confirmation-body">
          <p><strong>Already Added a Value for this Attribute in this Store!</strong></p>
          <p>This is a valued attribute that already has existing records for employees in this store.</p>
          <p>Do you want to DELETE the old records and ADD new ones for ALL employees?</p>
          <p><small>Click CONFIRM to DELETE old and ADD new or CANCEL to keep the old records.</small></p>
        </div>
        <div class="confirmation-actions">
          <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
            <input type="hidden" name="storeNumber" value="<?php echo htmlspecialchars($_SESSION['confirmation_data']['storeNumber']); ?>">
            <input type="hidden" name="category" value="<?php echo htmlspecialchars($_SESSION['confirmation_data']['category']); ?>">
            <input type="hidden" name="attribute" value="<?php echo htmlspecialchars($_SESSION['confirmation_data']['attribute']); ?>">
            <input type="hidden" name="period" value="<?php echo htmlspecialchars($_SESSION['confirmation_data']['period']); ?>">
            <input type="hidden" name="comment" value="<?php echo htmlspecialchars($_SESSION['confirmation_data']['comment']); ?>">
            <?php if (!empty($_SESSION['confirmation_data']['mark'])): ?>
              <input type="hidden" name="mark" value="<?php echo htmlspecialchars($_SESSION['confirmation_data']['mark']); ?>">
            <?php endif; ?>
            <?php if (!empty($_SESSION['confirmation_data']['status'])): ?>
              <input type="hidden" name="status" value="<?php echo htmlspecialchars($_SESSION['confirmation_data']['status']); ?>">
            <?php endif; ?>
            <?php if (!empty($_SESSION['confirmation_data']['value'])): ?>
              <input type="hidden" name="value" value="<?php echo htmlspecialchars($_SESSION['confirmation_data']['value']); ?>">
            <?php endif; ?>
            <?php if (!empty($_SESSION['confirmation_data']['lateComings'])): ?>
              <input type="hidden" name="lateComings" value="<?php echo htmlspecialchars($_SESSION['confirmation_data']['lateComings']); ?>">
            <?php endif; ?>
            <?php if (!empty($_SESSION['confirmation_data']['absets'])): ?>
              <input type="hidden" name="absets" value="<?php echo htmlspecialchars($_SESSION['confirmation_data']['absets']); ?>">
            <?php endif; ?>
            <?php if (!empty($_SESSION['confirmation_data']['conducts'])): ?>
              <input type="hidden" name="conducts" value="<?php echo htmlspecialchars($_SESSION['confirmation_data']['conducts']); ?>">
            <?php endif; ?>
            <input type="hidden" name="confirmed_action" value="update">
            <input type="hidden" name="submit" value="1">
            <button type="submit" class="btn-confirm">CONFIRM (Delete Old & Add New)</button>
          </form>
          <form method="GET" action="Warehouses.php">
            <button type="submit" class="btn-cancel">CANCEL (Keep Old Records)</button>
          </form>
        </div>
      </div>
    </div>
    <?php endif; ?>

    <!-- Modern Store Evaluation Section -->
    <div class="store-evaluation-wrapper" <?php if (isset($show_confirmation) && $show_confirmation) echo 'style="filter: blur(5px);"'; ?>>
      <div class="main-container">
        <!-- Left Side: Store Evaluation Form -->
        <div class="evaluation-form-container">
          <div class="form-container">
            <form name="addForm" enctype="multipart/form-data" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" onsubmit="return validate()" method="POST">
              <div class="form-header">
                <h3>Store Evaluation</h3>
                <p>Evaluate all employees in store <?php echo htmlspecialchars($rowfind['store_number']); ?> for the selected attribute</p>
              </div>
              
              <!-- Store Information -->
              <div class="form-row">
                <div class="form-holder">
                  <label for="storeNumber">Store Number</label>
                  <input type="text" name="storeNumber" placeholder="Store Number" class="form-control" value="<?php echo htmlspecialchars($rowfind['store_number']); ?>" readonly />
                </div>
                <div class="form-holder">
                  <label for="period"> Period</label>
                  <input type="month" 
                         name="period" 
                         class="form-control" 
                         value="<?php 
                           // Set default period based on is_valued
                           if ($rowa && $rowa['is_valued'] == 1) {
                             // Value-based attributes default to previous month
                             echo $previous_month;
                           } else {
                             // Critical Incident Based attributes default to current month
                             echo $current_month;
                           }
                         ?>" 
                         min="<?php echo $min_date; ?>" 
                         max="<?php echo $max_date; ?>" 
                         required />
                  <div style="font-size: 12px; color: var(--gray-color); margin-top: 8px;">
                    <?php if ($rowa && $rowa['is_valued'] == 1): ?>
                      <i class="fas fa-info-circle"></i> Value-based attributes default to previous month (<?php echo $previous_month; ?>)
                    <?php else: ?>
                      <i class="fas fa-info-circle"></i> Critical incident attributes default to current month (<?php echo $current_month; ?>)
                    <?php endif; ?>
                    <?php if ($_SESSION["isEvaluator"]): ?>
                      <br><i class="fas fa-lock"></i> Evaluators can only select current month (<?php echo $current_month; ?>) or previous month (<?php echo $min_date; ?>)
                    <?php else: ?>
                      <br><i class="fas fa-calendar-alt"></i> Admin can select from <?php echo $min_date; ?> to <?php echo $max_date; ?>
                    <?php endif; ?>
                  </div>
                </div>
              </div>
              
              <!-- Category & Attribute -->
              <div class="form-row">
                <div class="form-holder">
                  <label for="category"> Category</label>
                  <input type="text" name="category" class="form-control" value="<?php echo htmlspecialchars($rowfind['category_name']); ?>" readonly />
                </div>
                <div class="form-holder">
                  <label for="attribute"><i class="fas fa-list-check"></i> Attribute</label>
                  
                  <div style="margin-top: 8px; padding: 12px; background: #111214; border-radius: 8px; font-size: 14px; border: 2px solid #e2e8f0;,color:black;">
                    <strong> Selected:</strong> <?php echo htmlspecialchars($rowa['attribute_name']); ?>
                    <?php if ($rowa['is_valued'] == 1): ?>
                      <span style="color: var(--primary-color); font-weight: bold;"> (Valued Attribute)</span>
                    <?php endif; ?>
                  </div>
                  <!-- Hidden input to send attribute ID with form -->
                  <input type="hidden" name="attribute" value="<?php echo htmlspecialchars($att); ?>">
                </div>
              </div>
              
              <!-- Evaluation Section -->
              <div class="form-row">
                <div class="form-holder w-100">
                  <div class="evaluation-section">
                    <h5><i class="fas fa-star"></i> Evaluation for All Employees</h5>
                    
                    <div class="warning-badge">
                      <i class="fas fa-users"></i>
                      This evaluation will be applied to ALL employees in Store <?php echo htmlspecialchars($rowfind['store_number']); ?>
                    </div>
                    
                    <?php if ($rowa['is_valued'] == 0): ?>
                      <!-- Non-Valued Attributes (Positive/Negative/Neglected) -->
                      <div class="checkbox-tick">
                        <label class="positive">
                          <input type="radio" name="mark" value="+1"  />
                          <span class="checkmark"></span>
                          Positive
                        </label>
                        <label class="neutral">
                          <input type="radio" name="mark" value="0" />
                          <span class="checkmark"></span>
                          Neutral
                        </label>
                        <label class="negative">
                          <input type="radio" name="mark" value="-1" checked />
                          <span class="checkmark"></span>
                          Negative
                        </label>
                        <label class="neglect">
                          <input type="radio" name="mark" value="-2" />
                          <span class="checkmark"></span>
                          Neglected
                        </label>
                      </div>
                      <div class="info-text">
                        <i class="fas fa-lightbulb"></i> This rating will be applied to all employees in the store.
                        <br><i class="fas fa-info-circle"></i> <strong>Note:</strong> Non-valued attributes can be evaluated multiple times without confirmation.
                      </div>
                    
                    <?php elseif ($rowa['is_valued'] == 1): ?>
                      <!-- Valued Attributes -->
                      
                      <?php if ($rowa['scoring_method'] == 'Discipline'): ?>
                        <!-- Discipline Scoring Method -->
                        <div class="form-row">
                          <div class="form-holder w-100">
                            <div class="info-text" style="margin-bottom: 1rem;">
                              <i class="fas fa-info-circle"></i> Discipline scores will be calculated for each employee individually.
                            </div>
                            <div class="discipline-inputs">
                              <div class="form-holder">
                                <label for="lateComings" style="color: var(--warning-color);">Late Comings</label>
                                <input type="number" name="lateComings" placeholder="0" class="form-control" min="0" value="0" required />
                              </div>
                              <div class="form-holder">
                                <label for="absets" style="color: var(--danger-color);">Absentees</label>
                                <input type="number" name="absets" placeholder="0" class="form-control" min="0" value="0" required />
                              </div>
                              <div class="form-holder">
                                <label for="conducts" style="color: var(--success-color);">Exemplary Conduct</label>
                                <input type="number" name="conducts" placeholder="0" class="form-control" min="0" value="0" required />
                              </div>
                            </div>
                            <div style="text-align: center; margin-top: 15px; font-size: 12px; color: var(--gray-color);">
                              <i class="fas fa-calculator"></i> Score = (Late × 1) + (Absent × 5) - (Conduct × 5)
                            </div>
                          </div>
                        </div>
                      
                      <?php else: ?>
                        <!-- Other Valued Attributes -->
                        <div class="form-row">
                          <div class="form-holder w-100">
                            <div class="info-text" style="margin-bottom: 1rem;">
                              <i class="fas fa-info-circle"></i> This value will be applied to all employees in the store. If updating, it will be averaged with existing values.
                            </div>
                            <div class="checkbox-tick" style="margin-bottom: 1.5rem;">
                              <label class="neglect">
                                <input type="radio" name="status" value="-2" onclick="hideInputField();" />
                                <span class="checkmark"></span>
                                Neglected
                              </label>
                              <label class="neglect">
                                <input type="radio" name="status" value="-1" onclick="hideInputField();" />
                                <span class="checkmark"></span>
                                No Chance to Perform
                              </label>
                              <label class="neglect" style="color: var(--primary-color);">
                                <input type="radio" name="status" value="0" onclick="showInputField();" checked />
                                <span class="checkmark"></span>
                                Performed
                              </label>
                            </div>
                            
                            <div id="valueInputContainer" style="display: block;">
                              <label for="value">Enter Value for All Employees</label>
                              <input type="number" step="0.001" name="value" placeholder="<?php echo htmlspecialchars($rowa['process_kpi']); ?>" class="form-control" required />
                              <?php if (!empty($rowa['process_kpi'])): ?>
                                <div class="info-text" style="margin-top: 8px;">
                                  <i class="fas fa-bullseye"></i> Target: <?php echo htmlspecialchars($rowa['process_kpi']); ?>
                                </div>
                              <?php endif; ?>
                            </div>
                          </div>
                        </div>
                      <?php endif; ?>
                      <div class="info-text">
                        <i class="fas fa-info-circle"></i> <strong>Note:</strong> Valued attributes require confirmation if already evaluated for this period for any employee in the store.
                      </div>
                    <?php endif; ?>
                  </div>
                </div>
              </div>
              
              <!-- Comment Section -->
              <div class="form-row">
                <div class="form-holder w-100">
                  <label for="comment"><i class="fas fa-comment"></i> Comment for All Evaluations (Optional)</label>
                  <textarea class="form-control" name="comment" id="comment" placeholder="Enter your comment here... This will be applied to all employee records. (Max 45 characters)" maxlength="45"></textarea>
                </div>
              </div>
              
              <!-- Submit Button -->
              <div class="form-row" style="justify-content: center; margin-top: 2.5rem;">
                <button type="submit" class="form-button" name="submit">
                  <i class="fas fa-paper-plane"></i> Submit Store Evaluation
                </button>
              </div>
            </form>
          </div>
        </div>

        <!-- Right Side: Store Information and Last Evaluations -->
        <div class="last-evaluations-container">
          <div class="last-evaluations-card">
            <div class="store-info">
              <div class="store-name">
                <i class="fas fa-store"></i> Store <?php echo htmlspecialchars($rowfind['store_number']); ?>
              </div>
              <div style="color: var(--gray-color); font-size: 14px;">
                <?php echo htmlspecialchars($rowfind['category_name']); ?> Department
              </div>
              
              <?php 
              // Get employee count for this store
              $empCountSql = "SELECT COUNT(*) as emp_count FROM employee WHERE current_store = '" . $rowfind['store_number'] . "'";
              $empCountResult = $conn->query($empCountSql);
              $empCount = 0;
              if ($empCountResult && $empCountRow = $empCountResult->fetch_assoc()) {
                $empCount = $empCountRow['emp_count'];
              }
              ?>
              
              <div class="store-details">
                <div class="store-detail">
                  <i class="fas fa-users"></i> <?php echo $empCount; ?> Employees
                </div>
                <div class="store-detail">
                  <i class="fas fa-building"></i> <?php echo htmlspecialchars($rowfind['category_name']); ?>
                </div>
              </div>
            </div>
            
            <h4>Recent Store Evaluations</h4>
            <p style="text-align: center; color: var(--gray-color); font-size: 14px; margin-bottom: 1.5rem;">
              <i class="fas fa-history"></i> Last 5 evaluations for Store <?php echo htmlspecialchars($rowfind['store_number']); ?>
            </p>
            
            <div class="evaluations-list">
              <?php if ($lastEvaluationsResult && $lastEvaluationsResult->num_rows > 0): ?>
                <?php while ($evaluation = $lastEvaluationsResult->fetch_assoc()): ?>
                  <div class="evaluation-item <?php echo ($evaluation['value'] !== null || $evaluation['positive'] == 1) ? 'completed' : 'pending'; ?>">
                    <div class="evaluation-header">
                      <div class="evaluation-attribute">
                        <i class="fas fa-list-check"></i>
                        <?php echo htmlspecialchars($evaluation['attribute_name']); ?>
                      </div>
                      <div class="evaluation-date">
                        <?php 
                        if (!empty($evaluation['time'])) {
                          $time = strtotime($evaluation['time']);
                          echo date('M d, Y', $time);
                        } else {
                          echo 'N/A';
                        }
                        ?>
                      </div>
                    </div>
                    
                    <div class="evaluation-employee">
                      <i class="fas fa-user"></i>
                      <?php echo htmlspecialchars($evaluation['emp_name']); ?>
                    </div>
                    
                    <div class="evaluation-details">
                      <div class="evaluation-rating">
                        <?php if ($evaluation['value'] !== null): ?>
                          <i class="fas fa-chart-line rating-positive"></i>
                          <span class="rating-positive">Value: <?php echo htmlspecialchars($evaluation['value']); ?></span>
                        <?php elseif ($evaluation['positive'] == 1): ?>
                          <i class="fas fa-thumbs-up rating-positive"></i>
                          <span class="rating-positive">Positive</span>
                        <?php elseif ($evaluation['neutral'] == 1): ?>
                          <i class="fas fa-minus rating-neutral"></i>
                          <span class="rating-neutral">Neutral</span>
                        <?php elseif ($evaluation['negative'] == 1): ?>
                          <i class="fas fa-thumbs-down rating-negative"></i>
                          <span class="rating-negative">Negative</span>
                        <?php elseif (isset($evaluation['status']) && $evaluation['status'] == -2): ?>
                          <i class="fas fa-times rating-neglected"></i>
                          <span class="rating-neglected">Neglected</span>
                        <?php else: ?>
                          <i class="fas fa-question rating-neutral"></i>
                          <span class="rating-neutral">Not Rated</span>
                        <?php endif; ?>
                      </div>
                      
                      <?php if ($evaluation['value'] !== null): ?>
                        <div class="evaluation-value">
                          <i class="fas fa-calculator"></i>
                          <?php echo htmlspecialchars($evaluation['value']); ?>
                        </div>
                      <?php endif; ?>
                    </div>
                    
                    <?php if (!empty($evaluation['comment'])): ?>
                      <div class="evaluation-comment">
                        <i class="fas fa-comment-dots"></i>
                        <span><?php echo htmlspecialchars($evaluation['comment']); ?></span>
                      </div>
                    <?php endif; ?>
                    
                    <div class="evaluation-period">
                      <i class="fas fa-calendar-alt"></i>
                      Period: <?php echo htmlspecialchars($evaluation['period']); ?>
                      <?php if ($evaluation['category']): ?>
                        | <i class="fas fa-folder"></i> <?php echo htmlspecialchars($evaluation['category']); ?>
                      <?php endif; ?>
                    </div>
                  </div>
                <?php endwhile; ?>
              <?php else: ?>
                <div class="no-evaluations">
                  <i class="fas fa-clipboard-list"></i>
                  <p>No previous evaluations found for this store.</p>
                  <p style="font-size: 12px; margin-top: 10px;">
                    This will be the first evaluation for Store <?php echo htmlspecialchars($rowfind['store_number']); ?>.
                  </p>
                </div>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>
    </div>

    <script>
      // Initialize based on current selection
      document.addEventListener('DOMContentLoaded', function() {
        var statusRadios = document.querySelectorAll('input[name="status"]');
        if (statusRadios.length > 0) {
          statusRadios.forEach(function(radio) {
            if (radio.checked && radio.value != '0') {
              hideInputField();
            }
          });
        }
        
        // Add animation to form elements
        document.querySelectorAll('.form-control, select').forEach(element => {
          element.addEventListener('focus', function() {
            this.parentElement.classList.add('focused');
          });
          element.addEventListener('blur', function() {
            this.parentElement.classList.remove('focused');
          });
        });
      });
    </script>

  </body>
  </html>

<?php } else {
  header("location: signin.php");
} ?>