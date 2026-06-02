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

  // Initialize variables
  $id = $att = $rowfind = $rowa = null;
  $resultfind = $resulta = $resultatt = $lastEvaluationsResult = null;

  if (isset($_GET['id']) and $_GET['att']) {

    $id = $_GET['id'];
    $att = $_GET['att'];

    $sql = "SELECT * FROM employee WHERE emp_id ='$id'";
    $resultfind = $conn->query($sql);

    $sqla = "SELECT * FROM attribute WHERE attribute_id ='$att'";
    $resulta = $conn->query($sqla);
    $rowa = $resulta->fetch_assoc();

    // Get last evaluations for this employee - ONLY FROM CURRENT YEAR (2026)
    if ($id) {
      $currentYear = date('Y'); // This will be 2026
      $lastEvaluationsSql = "SELECT r.*, a.attribute_name 
                            FROM records r 
                            JOIN attribute a ON r.attribute_id = a.attribute_id 
                            WHERE r.emp_id = '$id' 
                            AND SUBSTRING(r.period, 1, 4) = '$currentYear'
                            ORDER BY r.time DESC 
                            LIMIT 5";
      $lastEvaluationsResult = $conn->query($lastEvaluationsSql);
    }

    if ($resultfind->num_rows == 1) {
      $rowfind = $resultfind->fetch_assoc();
      $category1 = $rowfind['current_category'];

      if ($category1 == "Flavoring") {
        $sqlatt = "SELECT * FROM attribute WHERE category ='Packing' OR category ='Common'";
        $resultatt = $conn->query($sqlatt);
      } else {
        $sqlatt = "SELECT * FROM attribute WHERE category ='$category1' OR category ='Common'";
        $resultatt = $conn->query($sqlatt);
      }
    } else {
      header("location: evaluation.php?id=$id&att=$att");
    }
  }

  if (isset($_POST['submit'])) {

    $empNumber = $_POST['empNumber'];
    $storeNumber = $_POST['storeNumber'];
    $empName = $_POST['empName'];
    $category = $_POST['category'];
    $attribute = $_POST['attribute'];
    $period = $_POST['period'];
    $comment = $_POST['comment'];
    $evaluator = $_SESSION["id"];

    $sql1 = "SELECT * FROM evaluation WHERE emp_id = '$empNumber' and period = '$period'";
    $result1 = $conn->query($sql1);
    $row1 = $result1->fetch_assoc();

    if ($result1->num_rows == 0) {
      $sql2 = "SELECT * FROM employee WHERE emp_id ='$empNumber'";
      $result2 = $conn->query($sql2);
      $row2 = $result2->fetch_assoc();
      $empCategory = $row2['current_category'];
      if ($empCategory == "Flavoring") {
        $sql3 = "SELECT * FROM attribute WHERE category ='Packing' OR category = 'Common'";
        $result3 = $conn->query($sql3);
      } else {
        $sql3 = "SELECT * FROM attribute WHERE category ='$empCategory' OR category = 'Common'";
        $result3 = $conn->query($sql3);
      }
      while ($row3 = $result3->fetch_assoc()) {
        if ($row3['attribute_id'] != $attribute && $row3['is_valued'] == 0) {
          $att = $row3['attribute_id'];
          $sql4 = "INSERT INTO evaluation(emp_id, period, category, store, attribute_id, positive, neutral, negative)
          VALUES ('$empNumber','$period','$category','$storeNumber','$att','0','1','0')";
          $result4 = $conn->query($sql4);
        }
      }
    }

    $sqlv = "SELECT * FROM attribute WHERE attribute_id ='$attribute'";
    $resultv = $conn->query($sqlv);
    $rowv = $resultv->fetch_assoc();

    $attributeId = $rowv['attribute_id'];

    if ($rowv['is_valued'] == 0) {
      $mark = $_POST['mark'];

      $presql = "SELECT * FROM evaluation WHERE emp_id = '$empNumber' and period = '$period' and store = '$storeNumber' and attribute_id ='$attributeId'";
      $preresult = $conn->query($presql);
      $prerow = $preresult->fetch_assoc();
      if ($preresult->num_rows == 0) {
        if ($mark == +1) {
          $sql = "INSERT INTO evaluation(emp_id, period, category, store, attribute_id, positive, neutral, negative)
          VALUES ('$empNumber','$period','$category','$storeNumber','$attributeId','1','0','0')";
        } else if ($mark == 0) {
          $sql = "INSERT INTO evaluation(emp_id, period, category, store, attribute_id, positive, neutral, negative)
          VALUES ('$empNumber','$period','$category','$storeNumber','$attributeId','0','1','0')";
        } else if ($mark == -1) {
          $sql = "INSERT INTO evaluation(emp_id, period, category, store, attribute_id, positive, neutral, negative)
          VALUES ('$empNumber','$period','$category','$storeNumber','$attributeId','0','0','1')";
        } else if ($mark == -2) {
          $sql = "INSERT INTO evaluation(emp_id, period, category, store, attribute_id, positive, neutral, negative, status)
          VALUES ('$empNumber','$period','$category','$storeNumber','$attributeId','0','0','0','-2')";
        }
      } else {
        $id = $prerow['evaluation_id'];
        if ($mark == +1) {
          $mark = $prerow['positive'] + 1;
          $sql = "UPDATE evaluation
          SET positive = '$mark'
          WHERE evaluation_id = '$id'";
        } else if ($mark == 0) {
          $mark = $prerow['neutral'] + 1;
          $sql = "UPDATE evaluation
          SET neutral = '$mark'
          WHERE evaluation_id = '$id'";
        } else if ($mark == -1) {
          $mark = $prerow['negative'] + 1;
          $sql = "UPDATE evaluation
          SET negative = '$mark'
          WHERE evaluation_id = '$id'";
        } else if ($mark == -2) {
          $sql = "UPDATE evaluation
          SET status = '$mark'
          WHERE evaluation_id = '$id'";
        }
      }
    } else if ($rowv['is_valued'] == 1) {
      if ($rowv['scoring_method'] == 'Discipline') {
        $value = intval($_POST['lateComings']) * (1) + intval($_POST['absets']) * (5) + intval($_POST['conducts']) * (-5);
      } else {
        if ($_POST['status'] == -1 || $_POST['status'] == -2) {
          $status = $_POST['status'];
        } else if ($_POST['status'] == 0) {
          $value = $_POST['value'];
        }
      }

      $presql = "SELECT * FROM evaluation WHERE emp_id = '$empNumber' and period = '$period' and store = '$storeNumber' and attribute_id ='$attributeId'";
      $preresult = $conn->query($presql);
      $prerow = $preresult->fetch_assoc();
      if ($preresult->num_rows == 0) {
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
        $id = $prerow['evaluation_id'];
        $value = ($value + $prerow['value']) / 2.00;
        $sql = "UPDATE evaluation
        SET value = '$value'
        WHERE evaluation_id = '$id'";
      }
    }

    if ($rowv['is_valued'] == 0) {
      $mark = $_POST['mark'];
      if ($mark == +1) {
        $sqlr = "INSERT INTO records(emp_id, emp_name, period, evaluator_id, category, store, attribute_id, positive, neutral, negative, comment)
          VALUES ('$empNumber','$empName','$period','$evaluator','$category','$storeNumber','$attributeId','1','0','0','$comment')";
      } else if ($mark == 0) {
        $sqlr = "INSERT INTO records(emp_id, emp_name, period, evaluator_id, category, store, attribute_id, positive, neutral, negative, comment)
          VALUES ('$empNumber','$empName','$period','$evaluator','$category','$storeNumber','$attributeId','0','1','0','$comment')";
      } else if ($mark == -1) {
        $sqlr = "INSERT INTO records(emp_id, emp_name, period, evaluator_id, category, store, attribute_id, positive, neutral, negative, comment)
          VALUES ('$empNumber','$empName','$period','$evaluator','$category','$storeNumber','$attributeId','0','0','1','$comment')";
      } else if ($mark == -2) {
        $sqlr = "INSERT INTO records(emp_id, emp_name, period, evaluator_id, category, store, attribute_id, positive, neutral, negative, status, comment)
          VALUES ('$empNumber','$empName','$period','$evaluator','$category','$storeNumber','$attributeId','0','0','0','-2','$comment')";
      }
    } else if ($rowv['is_valued'] == 1) {
      if ($rowv['scoring_method'] == 'Discipline') {
        $value = intval($_POST['lateComings']) * (1) + intval($_POST['absets']) * (5) + intval($_POST['conducts']) * (-5);
      } else {
        if ($_POST['status'] == -1 || $_POST['status'] == -2) {
          $status = $_POST['status'];
        } else if ($_POST['status'] == 0) {
          $value = $_POST['value'];
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

    if ($preresult->num_rows > 0 && isset($prerow['value'])) {
      echo "<script>
            alert('Already Added a Value for this Attribute.!')
            </script>";
      $result = $conn->query($sql);
      $resultr = $conn->query($sqlr);
    } else {
      $result = $conn->query($sql);
      $resultr = $conn->query($sqlr);
    }

    if ($result == TRUE && $resultr == TRUE) {
      echo "<script>alert('Evaluation Added Successfully!');
      window.location.href='namely_evaluation.php';
      </script>";
    } else {
      echo "<script>
      window.location.href='namely_evaluation.php';
      </script>";
      echo "Error:" . $sql . "<br>" . $conn->error;
      echo "Error:" . $sqlr . "<br>" . $conn->error;
    }
  }

  if (isset($_POST['update'])) {
    $empNum = $_POST['empNumber'];
    $comment = $_POST['comment'];
    $evlId = $_POST['recordId'];

    $sqlu = "UPDATE records
    SET comment = '$comment'
    WHERE record_id = '$evlId'";

    $resultu = $conn->query($sqlu);

    if ($resultu == TRUE) {
      echo "<script>alert('Evaluation Updated Successfully!');
      window.location.href='rating.php?id=$empNum';
      </script>";
    } else {
      echo "Error:" . $sqlu . "<br>" . $conn->error;
    }
  }
?>

<!DOCTYPE html>
<html>

<head>
  <meta charset="utf-8" />
  <title>Employee Evaluation | JB Employee Evaluation</title>
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

  <!-- Modern CSS for Employee Evaluation Form -->
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
      --transition: all 0.3s ease;
    }

    .employee-evaluation-wrapper {
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

    .form-holder label {
      display: block;
      margin-bottom: 10px;
      background-color:;
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
    }

    .form-button:hover {
      transform: translateY(-3px);
      box-shadow: 0 8px 25px rgba(67, 97, 238, 0.4);
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
    }

    .evaluation-item:hover {
      background: #edf2f7;
      transform: translateX(5px);
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

    /* Hide old form elements */
    .wrapper, .editForm {
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
    }

    @media (max-width: 768px) {
      .employee-evaluation-wrapper {
        padding: 1rem;
      }
      .form-container {
        padding: 1.5rem;
      }
      .form-row {
        flex-direction: column;
        gap: 1rem;
      }
      .checkbox-tick {
        flex-direction: column;
        gap: 0.5rem;
      }
      .discipline-inputs {
        grid-template-columns: 1fr;
      }
    }

    .info-text {
      font-size: 12px;
      color: var(--gray-color);
      margin-top: 5px;
    }
  </style>

  <script type="text/javascript">
    function validate() {
      var comment = document.addForm.comment.value;
      var letters = /^[A-Za-z0-9./:;!`"|<>_\-?+@#%^&*~,={}()\[\]$\\ ]+$/;

      if (comment.trim().length > 0) {
        if (!comment.match(letters)) {
          alert("Comment contains invalid characters.");
          return false;
        }
        if (comment.includes("'")) {
          alert("Containing Invalid Characters in Comment.");
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

  <!-- Modern Employee Evaluation Section -->
  <div class="employee-evaluation-wrapper">
    <div class="main-container">
      <!-- Left Side: Employee Evaluation Form -->
      <div class="evaluation-form-container">
        <?php if (isset($_GET['evlId'])) {
          $evlId = $_GET['evlId'];

          $sqlEvl = "SELECT * FROM records WHERE record_id ='$evlId'";
          $resulEvl = $conn->query($sqlEvl);
          $rowEvl = $resulEvl->fetch_assoc();
          
          // *** FIXED: Add recent evaluations query for update section ***
          $currentYear = date('Y'); // 2026
          $lastEvaluationsSql = "SELECT r.*, a.attribute_name 
                                FROM records r 
                                JOIN attribute a ON r.attribute_id = a.attribute_id 
                                WHERE r.emp_id = '" . $rowEvl['emp_id'] . "' 
                                AND SUBSTRING(r.period, 1, 4) = '$currentYear'
                                ORDER BY r.time DESC 
                                LIMIT 5";
          $lastEvaluationsResult = $conn->query($lastEvaluationsSql);
          
          // Also get employee details for display
          $sqlfind = "SELECT * FROM employee WHERE emp_id ='" . $rowEvl['emp_id'] . "'";
          $resultfind = $conn->query($sqlfind);
          $rowfind = $resultfind->fetch_assoc();
          // *** END OF FIX ***
        ?>
          <!-- Update Evaluation Form -->
          <div class="form-container">
            <div class="form-header">
              <h3>Update Evaluation</h3>
              <p>Update evaluation details for employee <?php echo $rowEvl['emp_name']; ?></p>
            </div>
            
            <form name="addForm" enctype="multipart/form-data" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" onsubmit="return validate()" method="POST">
              <input type="text" name="recordId" class="form-control" value="<?php echo $rowEvl['record_id'] ?>" hidden />
              
              <div class="form-row">
                <div class="form-holder">
                  <label for="empNumber"><i class="fas fa-id-card"></i> Employee Number</label>
                  <input type="text" name="empNumber" placeholder="Employee Number" class="form-control" value="<?php echo $rowEvl['emp_id'] ?>" readonly />
                </div>
                <div class="form-holder">
                  <label for="storeNumber"><i class="fas fa-store"></i> Store Number</label>
                  <input type="text" name="storeNumber" placeholder="Store Number" class="form-control" value="<?php echo $rowEvl['store'] ?>" readonly />
                </div>
              </div>
              
              <div class="form-row">
                <div class="form-holder w-100">
                  <label for="empName"><i class="fas fa-user"></i> Employee Name</label>
                  <input type="text" name="empName" placeholder="Employee Name" class="form-control" value="<?php echo $rowEvl['emp_name'] ?>" readonly />
                </div>
              </div>
              
              <div class="form-row">
                <div class="form-holder">
                  <label for="category"><i class="fas fa-folder"></i> Category</label>
                  <select class="form-holder" name="category">
                    <option value="<?php echo $rowEvl['category']; ?>"><?php echo $rowEvl['category']; ?></option>
                  </select>
                </div>
                <div class="form-holder">
                  <label for="attribute"><i class="fas fa-list-check"></i> Attribute</label>
                  <select class="form-holder" name="attribute">
                    <option value="<?php echo $rowEvl['attribute_id'] ?>" selected><?php echo $rowEvl['attribute_id'] ?></option>
                  </select>
                </div>
              </div>
              
              <div class="form-row">
                <div class="form-holder">
                  <label for="period"><i class="fas fa-calendar-alt"></i> Period</label>
                  <input type="month" name="period" class="form-control" value="<?php echo $rowEvl['period'] ?>" readonly />
                </div>
                
                <div class="form-holder" style="align-self: flex-end;">
                  <div class="evaluation-section">
                    <h5><i class="fas fa-star"></i> Current Evaluation</h5>
                    <?php
                    if ($rowEvl['value']) { ?>
                      <div style="background: var(--success-color); color: white; padding: 12px; border-radius: 8px; text-align: center;">
                        <i class="fas fa-chart-line"></i> Value: <?php echo $rowEvl['value']; ?>
                      </div>
                    <?php } else { ?>
                      <div class="checkbox-tick">
                        <?php if ($rowEvl['positive'] == 1) { ?>
                          <label class="positive" style="cursor: default; opacity: 0.8;">
                            <input type="radio" disabled checked />Positive
                            <span class="checkmark"></span>
                          </label>
                        <?php } elseif ($rowEvl['neutral'] == 1) { ?>
                          <label class="neutral" style="cursor: default; opacity: 0.8;">
                            <input type="radio" disabled checked />Neutral
                            <span class="checkmark"></span>
                          </label>
                        <?php } elseif ($rowEvl['negative'] == 1) { ?>
                          <label class="negative" style="cursor: default; opacity: 0.8;">
                            <input type="radio" disabled checked />Negative
                            <span class="checkmark"></span>
                          </label>
                        <?php } else { ?>
                          <label class="neglect" style="cursor: default; opacity: 0.8;">
                            <input type="radio" disabled checked />Neglected
                            <span class="checkmark"></span>
                          </label>
                        <?php } ?>
                      </div>
                    <?php } ?>
                  </div>
                </div>
              </div>
              
              <div class="form-row">
                <div class="form-holder w-100">
                  <label for="comment"><i class="fas fa-comment"></i> Update Comment</label>
                  <textarea class="form-control" name="comment" id="comment" placeholder="Update your comment here... (Max 45 characters)" maxlength="45"><?php echo $rowEvl['comment'] ?></textarea>
                </div>
              </div>
              
              <div class="form-row" style="justify-content: center; margin-top: 2.5rem;">
                <button type="submit" class="form-button" name="update">
                  <i class="fas fa-save"></i> Update Evaluation
                </button>
              </div>
            </form>
          </div>
        <?php } else { 
          // Calculate default period based on is_valued
          // is_valued = 1 -> Previous Month, is_valued = 0 -> Current Month
          $defaultPeriod = date('Y-m'); // Default to current month
          if ($rowa && $rowa['is_valued'] == 1) {
            // For Value-based attributes, default to previous month
            $defaultPeriod = date('Y-m', strtotime('-1 month'));
          }
          // For Critical Incident Based (is_valued = 0), default is current month (already set)
        ?>
          <!-- New Evaluation Form -->
          <div class="form-container">
            <form name="addForm" enctype="multipart/form-data" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" onsubmit="return validate()" method="POST">
              <div class="form-header">
                <h3>Employee Evaluation</h3>
                <p>Evaluate employee <?php echo $rowfind['emp_name']; ?> for the selected attribute</p>
              </div>
              
              <div class="form-row">
                <div class="form-holder">
                  <label for="empNumber"><i class="fas fa-id-card"></i> Employee Number</label>
                  <input type="text" name="empNumber" placeholder="Employee Number" class="form-control" value="<?php echo $rowfind['emp_id'] ?>" readonly />
                </div>
                <div class="form-holder">
                  <label for="storeNumber"><i class="fas fa-store"></i> Store Number</label>
                  <input type="text" name="storeNumber" placeholder="Store Number" class="form-control" value="<?php echo $rowfind['current_store'] ?>" readonly />
                </div>
              </div>
              
              <div class="form-row">
                <div class="form-holder w-100">
                  <label for="empName"><i class="fas fa-user"></i> Employee Name</label>
                  <input type="text" name="empName" placeholder="Employee Name" class="form-control" value="<?php echo $rowfind['emp_name'] ?>" readonly />
                </div>
              </div>
              
              <div class="form-row">
                <div class="form-holder">
                  <label for="category">Category</label>
                  <input type="text" name="category" class="form-control" value="<?php echo $rowfind['current_category']; ?>" readonly />
                </div>
                <div class="form-holder">
                  <label for="period"><i class="fas fa-calendar-alt"></i> Period</label>
                  <input type="month" name="period" class="form-control" value="<?php echo $defaultPeriod; ?>" required />
                  <?php if ($rowa && $rowa['is_valued'] == 1): ?>
                    <div class="info-text">
                      <i class="fas fa-info-circle"></i> Value-based attributes default to previous month
                    </div>
                  <?php endif; ?>
                </div>
              </div>
               
              <div class="form-row">
                <div class="form-holder w-100">
                  <label for="attribute"><i class="fas fa-list-check"></i> Attribute</label>
                  <div style="margin-top: 8px; padding: 8px; background:#f0f0f0; border-radius: 8px; font-size: 14px;">
                    <strong>Selected:</strong> <?php echo $rowa['attribute_name']; ?>
                    <?php if ($rowa['is_valued'] == 1): ?>
                      <span style="color: var(--primary-color); font-weight: bold;"> (Valued Attribute)</span>
                    <?php endif; ?>
                  </div>
                  <input type="hidden" name="attribute" value="<?php echo $att; ?>">
                </div>
              </div>
              
              <div class="form-row">
                <div class="form-holder w-100">
                  <div class="evaluation-section">
                    <h5><i class="fas fa-star"></i> Evaluation</h5>
                    
                    <?php if ($rowa['is_valued'] == 0): ?>
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
                          <input type="radio" name="mark" value="-1" checked/>
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
                        <i class="fas fa-lightbulb"></i> Select the appropriate rating for this employee
                      </div>
                    
                    <?php elseif ($rowa['is_valued'] == 1): ?>
                      
                      <?php if ($rowa['scoring_method'] == 'Discipline'): ?>
                        <div class="form-row">
                          <div class="form-holder w-100">
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
                        <div class="form-row">
                          <div class="form-holder w-100">
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
                              <label for="value">Enter Value</label>
                              <input type="number" step="0.001" name="value" placeholder="<?php echo $rowa['process_kpi']; ?>" class="form-control" required />
                              <?php if (!empty($rowa['process_kpi'])): ?>
                                <div class="info-text">
                                  <i class="fas fa-bullseye"></i> Target: <?php echo $rowa['process_kpi']; ?>
                                </div>
                              <?php endif; ?>
                            </div>
                          </div>
                        </div>
                      <?php endif; ?>
                    <?php endif; ?>
                  </div>
                </div>
              </div>
              
              <div class="form-row">
                <div class="form-holder w-100">
                  <label for="comment"><i class="fas fa-comment"></i> Comment (Optional)</label>
                  <textarea class="form-control" name="comment" id="comment" placeholder="Enter your comment here... (Max 45 characters)" maxlength="45"></textarea>
                </div>
              </div>
              
              <div class="form-row" style="justify-content: center; margin-top: 2.5rem;">
                <button type="submit" class="form-button" name="submit">
                  <i class="fas fa-paper-plane"></i> Submit Evaluation
                </button>
              </div>
            </form>
          </div>
        <?php } ?>
      </div>

      <!-- Right Side: Last Evaluations for Employee -->
      <div class="last-evaluations-container">
        <div class="last-evaluations-card">
          <h4>Recent Evaluations (<?php echo date('Y'); ?>)</h4>
          <?php if (isset($rowfind) && $rowfind): ?>
          <p style="text-align: center; color: var(--gray-color); font-size: 14px; margin-bottom: 1.5rem;">
            <i class="fas fa-user"></i> <?php echo $rowfind['emp_name']; ?>
          </p>
          <?php endif; ?>
          
          <div class="evaluations-list">
            <?php if (isset($lastEvaluationsResult) && $lastEvaluationsResult && $lastEvaluationsResult->num_rows > 0): ?>
              <?php while ($evaluation = $lastEvaluationsResult->fetch_assoc()): ?>
                <div class="evaluation-item">
                  <div class="evaluation-header">
                    <div class="evaluation-attribute">
                      <i class="fas fa-list-check"></i>
                      <?php echo $evaluation['attribute_name']; ?>
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
                  
                  <div class="evaluation-details">
                    <div class="evaluation-rating">
                      <?php if ($evaluation['value'] !== null): ?>
                        <i class="fas fa-chart-line rating-positive"></i>
                        <span class="rating-positive">Value: <?php echo $evaluation['value']; ?></span>
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
                        <?php echo $evaluation['value']; ?>
                      </div>
                    <?php endif; ?>
                  </div>
                  
                  <?php if (!empty($evaluation['comment'])): ?>
                    <div class="evaluation-comment">
                      <i class="fas fa-comment-dots"></i>
                      <span><?php echo $evaluation['comment']; ?></span>
                    </div>
                  <?php endif; ?>
                  
                  <div class="evaluation-period">
                    <i class="fas fa-calendar-alt"></i>
                    Period: <?php echo $evaluation['period']; ?>
                  </div>
                </div>
              <?php endwhile; ?>
            <?php else: ?>
              <div class="no-evaluations">
                <i class="fas fa-clipboard-list"></i>
                <p>No evaluations found for year <?php echo date('Y'); ?>.</p>
              </div>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script>
    document.addEventListener('DOMContentLoaded', function() {
      var statusRadios = document.querySelectorAll('input[name="status"]');
      if (statusRadios.length > 0) {
        statusRadios.forEach(function(radio) {
          if (radio.checked && radio.value != '0') {
            hideInputField();
          }
        });
      }
    });
  </script>

  <?php 
  if (isset($_GET['att'])) {
    $atId = $_GET['att'];
    $resulAt = $conn->query("SELECT * FROM attribute WHERE attribute_id ='$atId'");
    $rowAt = $resulAt->fetch_assoc(); 
  ?>
  <div style="display: none;">
    <!-- Hidden attribute info for reference -->
    <b><?php echo $rowAt['attribute_name'] ?></b>
    <b>Weightage:</b> <?php echo $rowAt['weightage'] ?>
    <b>CSF:</b> <?php echo $rowAt['csf'] ?>
    <b>Process Objective:</b> <?php echo $rowAt['process_objective'] ?>
    <b>Process KPI:</b> <?php echo $rowAt['process_kpi'] ?>
  </div>
  <?php } ?>

</body>
</html>

<?php } else {
  header("location: signin.php");
} ?>