<?php
include "config.php";
session_start();

// Function to insert/update attribute scoring history
function saveAttributeScoringHistory($conn, $attribute_id, $scoring_method, $year) {
    // Get the scoring_method_id from the scoring_method table
    $sql_get_sm = "SELECT sm_id FROM scoring_method WHERE sm_name = '$scoring_method' AND year = '$year'";
    $result_sm = mysqli_query($conn, $sql_get_sm);
    
    if ($result_sm && mysqli_num_rows($result_sm) > 0) {
        $row_sm = mysqli_fetch_assoc($result_sm);
        $scoring_method_id = $row_sm['sm_id'];
        
        // Check if entry already exists for this attribute and year
        $sql_check = "SELECT id FROM attribute_scoring_history 
                      WHERE attribute_id = '$attribute_id' AND year = '$year'";
        $result_check = mysqli_query($conn, $sql_check);
        
        if ($result_check && mysqli_num_rows($result_check) > 0) {
            // Update existing record
            $sql_update = "UPDATE attribute_scoring_history 
                          SET scoring_method_id = '$scoring_method_id'
                          WHERE attribute_id = '$attribute_id' AND year = '$year'";
            mysqli_query($conn, $sql_update);
        } else {
            // Insert new record
            $sql_insert = "INSERT INTO attribute_scoring_history 
                          (attribute_id, scoring_method_id, year, is_current) 
                          VALUES ('$attribute_id', '$scoring_method_id', '$year', '0')";
            mysqli_query($conn, $sql_insert);
        }
        
        // Set is_current flag for current year
        if ($year == date('Y')) {
            // First, set all records for this attribute to not current
            $sql_reset = "UPDATE attribute_scoring_history 
                         SET is_current = '0' 
                         WHERE attribute_id = '$attribute_id'";
            mysqli_query($conn, $sql_reset);
            
            // Then set current year as current
            $sql_current = "UPDATE attribute_scoring_history 
                           SET is_current = '1' 
                           WHERE attribute_id = '$attribute_id' AND year = '$year'";
            mysqli_query($conn, $sql_current);
        }
        
        return true;
    }
    return false;
}

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

  $sqlfind = "SELECT * FROM category";
  $resultfind = $conn->query($sqlfind);

  // MODIFIED: Only get scoring methods for current year
  $current_year = date('Y');
  $sqlfind1 = "SELECT * FROM scoring_method WHERE year = '$current_year' ORDER BY sm_name";
  $resultfind1 = $conn->query($sqlfind1);

  if (isset($_POST['submit'])) {

    $attName = $_POST['attName'];
    $weight = $_POST['weight'];
    $category = $_POST['category'];
    $csf = $_POST['csf'];
    $processObjective = $_POST['processObjective'];
    $processKPI = $_POST['processKPI'];
    $measuringMethod = $_POST['measuringMethod'];
    $isValued = $_POST['isValued'];

    $PLE = $OE = $QM = $PE = 0;
    foreach ($_POST['check_list'] as $checkbox) {
      if ($checkbox == 'PLE') {
        $PLE = 1;
      }
      if ($checkbox == 'OE') {
        $OE = 1;
      }
      if ($checkbox == 'QM') {
        $QM = 1;
      }
      if ($checkbox == 'PE') {
        $PE = 1;
      }
    }

    $sql = "INSERT INTO attribute(attribute_name, weightage, category, csf, process_objective, process_kpi, PLE, OE, QM, PE, scoring_method, is_valued)
      VALUES ('$attName','$weight','$category','$csf','$processObjective','$processKPI','$PLE','$OE','$QM','$PE','$measuringMethod','$isValued')";

    $result = $conn->query($sql);

    if ($result == TRUE) {
      // Get the newly inserted attribute ID
      $new_attribute_id = mysqli_insert_id($conn);
      
      // Get current year
      $current_year = date('Y');
      
      // Save to history table
      saveAttributeScoringHistory($conn, $new_attribute_id, $measuringMethod, $current_year);
      
      echo "<script>alert('Attribute Added Successfully!');
      window.location.href='AttributeCategories.php';
      </script>";
    } else {
      echo "Error:" . $sql . "<br>" . $conn->error;
    }
  }
?>

  <!DOCTYPE html>
  <html>

  <head>
    <meta charset="utf-8" />
    <?php if (isset($_GET["id"])) { ?>
      <title>Update Attribute</title>
    <?php } else { ?>
      <title>Add Attribute</title>
    <?php } ?>
    <link rel="icon" type="image/x-icon" href="./img/jb.png">
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />

    <link rel="stylesheet" href="fonts/material-design-iconic-font/css/material-design-iconic-font.css" />

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta1/dist/css/bootstrap.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta1/dist/js/bootstrap.bundle.min.js" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.2.1/jquery.min.js" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/boxicons@latest/css/boxicons.min.css" />

    <link rel="stylesheet" href="./style.css" />
    <script src="./script.js"></script>
    <link rel="stylesheet" href="css/style.css" />

    <!-- CSS for Update Attribute Form Only (Split Layout) -->
    <style>
        /* CSS for Update Attribute Form Only */
        .update-form-wrapper {
            min-height: calc(100vh - 80px);
            padding: 2rem;
            margin-top: 80px;
        }

        .update-form-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.08);
            overflow: hidden;
            max-width: 1100px;
            margin: 0 auto;
        }

        .update-form-header {
           background: linear-gradient(135deg, #598a38ff 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            text-align: center;
        }

        .update-form-header h3 {
            margin: 0;
            font-size: 1.8rem;
            font-weight: 600;
            color: white;
        }

        .update-form-header p {
            margin: 0.5rem 0 0 0;
            opacity: 0.9;
            font-size: 0.95rem;
            color: white;
        }

        .update-form-content {
            padding: 2rem;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
        }

        @media (max-width: 992px) {
            .update-form-content {
                grid-template-columns: 1fr;
                gap: 1.5rem;
            }
        }

        .update-form-left, .update-form-right {
            display: flex;
            flex-direction: column;
        }

        .update-form-section {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            border-left: 4px solid #4361ee;
        }

        .update-form-section-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: #2b2d42;
            margin: 0 0 1rem 0;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #e0e7ff;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .update-form-section-title i {
            color: #4361ee;
            font-size: 1.2rem;
        }

        .update-form-group {
            margin-bottom: 1.5rem;
        }

        .update-form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #2b2d42;
            font-size: 0.95rem;
        }

        .update-form-input {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            background: white;
        }

        .update-form-input:focus {
            outline: none;
            border-color: #4361ee;
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.1);
        }

        .update-select-wrapper {
            position: relative;
        }

        .update-select-wrapper select {
            appearance: none;
            cursor: pointer;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='%236c757d' viewBox='0 0 16 16'%3E%3Cpath d='M7.247 11.14 2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 1rem center;
            background-size: 16px;
            padding-right: 2.5rem;
        }

        /* Weightage Slider Styling */
        .update-weightage-container {
            background: linear-gradient(135deg, #e0e7ff, #f0f7ff);
            padding: 1.5rem;
            border-radius: 10px;
            margin-top: 1rem;
        }

        .update-weightage-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .update-weightage-value {
            background: #4361ee;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 600;
            font-size: 1.1rem;
            min-width: 60px;
            text-align: center;
            box-shadow: 0 2px 10px rgba(67, 97, 238, 0.2);
        }

        .update-slider-labels {
            display: flex;
            justify-content: space-between;
            margin-top: 0.75rem;
            font-size: 0.85rem;
            color: #6c757d;
        }

        .update-range-input {
            width: 100%;
            height: 10px;
            -webkit-appearance: none;
            background: linear-gradient(to right, #4361ee, #ff9f1c);
            border-radius: 5px;
            outline: none;
        }

        .update-range-input::-webkit-slider-thumb {
            -webkit-appearance: none;
            width: 28px;
            height: 28px;
            background: white;
            border: 3px solid #4361ee;
            border-radius: 50%;
            cursor: pointer;
            box-shadow: 0 3px 8px rgba(0,0,0,0.3);
            transition: all 0.3s ease;
        }

        .update-range-input::-webkit-slider-thumb:hover {
            transform: scale(1.15);
            box-shadow: 0 5px 15px rgba(0,0,0,0.4);
        }

        /* Radio Button Group */
        .update-radio-group {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }

        .update-radio-option {
            display: flex;
            align-items: center;
            padding: 0.75rem;
            background: white;
            border-radius: 8px;
            border: 2px solid #e9ecef;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .update-radio-option:hover {
            border-color: #4361ee;
            background: #e0e7ff;
            transform: translateX(5px);
        }

        .update-radio-option input[type="radio"] {
            margin-right: 1rem;
            width: 20px;
            height: 20px;
            cursor: pointer;
        }

        .update-radio-option label {
            margin: 0;
            font-weight: 500;
            color: #2b2d42;
            cursor: pointer;
            flex: 1;
            font-size: 0.95rem;
        }

        /* Checkbox Group */
        .update-checkbox-group {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
            margin-top: 0.5rem;
        }

        @media (max-width: 576px) {
            .update-checkbox-group {
                grid-template-columns: 1fr;
            }
        }

        .update-checkbox-option {
            display: flex;
            align-items: center;
            padding: 0.75rem;
            background: white;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .update-checkbox-option:hover {
            border-color: #4361ee;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        .update-checkbox-option input[type="checkbox"] {
            margin-right: 0.75rem;
            width: 18px;
            height: 18px;
            cursor: pointer;
        }

        .update-checkbox-option label {
            margin: 0;
            font-weight: 500;
            color: #2b2d42;
            cursor: pointer;
            font-size: 0.95rem;
        }

        /* Form Actions */
        .update-form-actions {
            grid-column: 1 / -1;
            display: flex;
            justify-content: center;
            gap: 1rem;
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 2px solid #e0e7ff;
        }

        .update-btn {
            padding: 0.75rem 2.5rem;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .update-btn-primary {
            background: linear-gradient(135deg, #4361ee, #3a0ca3);
            color: white;
        }

        .update-btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(67, 97, 238, 0.3);
        }

        .update-btn-secondary {
            background: white;
            color: #2b2d42;
            border: 2px solid #e9ecef;
        }

        .update-btn-secondary:hover {
            border-color: #e71d36;
            color: #e71d36;
            transform: translateY(-2px);
        }

        /* Tooltip */
        .update-tooltip {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 18px;
            height: 18px;
            background: #e0e7ff;
            color: #4361ee;
            border-radius: 50%;
            font-size: 0.75rem;
            margin-left: 0.5rem;
            cursor: help;
        }

        /* Status Indicators */
        .update-status-indicator {
            display: inline-block;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            margin-right: 0.5rem;
        }

        .update-status-active {
            background: #2ec4b6;
            box-shadow: 0 0 8px rgba(46, 196, 182, 0.5);
        }

        .update-status-inactive {
            background: #e71d36;
            box-shadow: 0 0 8px rgba(231, 29, 54, 0.5);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .update-form-wrapper {
                padding: 1rem;
            }
            
            .update-form-content {
                padding: 1.5rem;
            }
            
            .update-form-header {
                padding: 1.5rem;
            }
            
            .update-btn {
                width: 100%;
                margin-bottom: 0.5rem;
            }
            
            .update-form-actions {
                flex-direction: column;
            }
        }

        /* Animation */
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .update-form-section {
            animation: fadeIn 0.5s ease forwards;
        }

        .update-form-left .update-form-section:nth-child(1) { animation-delay: 0.1s; }
        .update-form-left .update-form-section:nth-child(2) { animation-delay: 0.2s; }
        .update-form-right .update-form-section:nth-child(1) { animation-delay: 0.3s; }
        .update-form-right .update-form-section:nth-child(2) { animation-delay: 0.4s; }
        .update-form-actions { animation-delay: 0.5s; }
    </style>

    <!-- CSS for Add Attribute Form (Split Layout) -->
    <style>
        /* CSS for Add Attribute Form Only with Split Layout */
        .add-form-wrapper {
           
            min-height: calc(100vh - 80px);
            padding: 2rem;
            margin-top: 80px;
        }

        .add-form-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.08);
            overflow: hidden;
            max-width: 1100px;
            margin: 0 auto;
        }

        .add-form-header {
            background: linear-gradient(135deg, #598a38ff 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            text-align: center;
        }

        .add-form-header h3 {
            margin: 0;
            font-size: 1.8rem;
            font-weight: 600;
            color: white;
        }

        .add-form-header p {
            margin: 0.5rem 0 0 0;
            opacity: 0.9;
            font-size: 0.95rem;
            color: white;
        }

        .add-form-content {
            padding: 2rem;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
        }

        @media (max-width: 992px) {
            .add-form-content {
                grid-template-columns: 1fr;
                gap: 1.5rem;
            }
        }

        .add-form-left, .add-form-right {
            display: flex;
            flex-direction: column;
        }

        .add-form-section {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            border-left: 4px solid #2ec4b6;
        }

        .add-form-section-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: #2b2d42;
            margin: 0 0 1rem 0;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #d4f4f1;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .add-form-section-title i {
            color: #2ec4b6;
            font-size: 1.2rem;
        }

        .add-form-group {
            margin-bottom: 1.5rem;
        }

        .add-form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #2b2d42;
            font-size: 0.95rem;
        }

        .add-form-input {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            background: white;
        }

        .add-form-input:focus {
            outline: none;
            border-color: #2ec4b6;
            box-shadow: 0 0 0 3px rgba(46, 196, 182, 0.1);
        }

        .add-select-wrapper {
            position: relative;
        }

        .add-select-wrapper select {
            appearance: none;
            cursor: pointer;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='%236c757d' viewBox='0 0 16 16'%3E%3Cpath d='M7.247 11.14 2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 1rem center;
            background-size: 16px;
            padding-right: 2.5rem;
        }

        /* Weightage Slider Styling */
        .add-weightage-container {
            background: linear-gradient(135deg, #d4f4f1, #e6f7ff);
            padding: 1.5rem;
            border-radius: 10px;
            margin-top: 1rem;
        }

        .add-weightage-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .add-weightage-value {
            background: #2ec4b6;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 600;
            font-size: 1.1rem;
            min-width: 60px;
            text-align: center;
            box-shadow: 0 2px 10px rgba(46, 196, 182, 0.2);
        }

        .add-slider-labels {
            display: flex;
            justify-content: space-between;
            margin-top: 0.75rem;
            font-size: 0.85rem;
            color: #6c757d;
        }

        .add-range-input {
            width: 100%;
            height: 10px;
            -webkit-appearance: none;
            background: linear-gradient(to right, #2ec4b6, #ff9f1c);
            border-radius: 5px;
            outline: none;
        }

        .add-range-input::-webkit-slider-thumb {
            -webkit-appearance: none;
            width: 28px;
            height: 28px;
            background: white;
            border: 3px solid #2ec4b6;
            border-radius: 50%;
            cursor: pointer;
            box-shadow: 0 3px 8px rgba(0,0,0,0.3);
            transition: all 0.3s ease;
        }

        .add-range-input::-webkit-slider-thumb:hover {
            transform: scale(1.15);
            box-shadow: 0 5px 15px rgba(0,0,0,0.4);
        }

        /* Radio Button Group */
        .add-radio-group {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }

        .add-radio-option {
            display: flex;
            align-items: center;
            padding: 0.75rem;
            background: white;
            border-radius: 8px;
            border: 2px solid #e9ecef;
            cursor: pointer;
            transition: all 0.3s ease;
        }
.nav_link.disabled {
        opacity: 0.5 !important;
        cursor: not-allowed !important;
      }
      
      .nav_link.disabled:hover {
        color: inherit !important;
        background-color: inherit !important;
      }
      
        .add-radio-option:hover {
            border-color: #2ec4b6;
            background: #d4f4f1;
            transform: translateX(5px);
        }

        .add-radio-option input[type="radio"] {
            margin-right: 1rem;
            width: 20px;
            height: 20px;
            cursor: pointer;
        }

        .add-radio-option label {
            margin: 0;
            font-weight: 500;
            color: #2b2d42;
            cursor: pointer;
            flex: 1;
            font-size: 0.95rem;
        }

        /* Checkbox Group */
        .add-checkbox-group {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
            margin-top: 0.5rem;
        }

        @media (max-width: 576px) {
            .add-checkbox-group {
                grid-template-columns: 1fr;
            }
        }

        .add-checkbox-option {
            display: flex;
            align-items: center;
            padding: 0.75rem;
            background: white;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .add-checkbox-option:hover {
            border-color: #2ec4b6;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        .add-checkbox-option input[type="checkbox"] {
            margin-right: 0.75rem;
            width: 18px;
            height: 18px;
            cursor: pointer;
        }

        .add-checkbox-option label {
            margin: 0;
            font-weight: 500;
            color: #2b2d42;
            cursor: pointer;
            font-size: 0.95rem;
        }

        /* Form Actions */
        .add-form-actions {
            grid-column: 1 / -1;
            display: flex;
            justify-content: center;
            gap: 1rem;
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 2px solid #d4f4f1;
        }

        .add-btn {
            padding: 0.75rem 2.5rem;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .add-btn-primary {
            background: linear-gradient(135deg, #2ec4b6, #20a39e);
            color: white;
        }

        .add-btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(46, 196, 182, 0.3);
        }

        .add-btn-secondary {
            background: white;
            color: #2b2d42;
            border: 2px solid #e9ecef;
        }

        .add-btn-secondary:hover {
            border-color: #e71d36;
            color: #e71d36;
            transform: translateY(-2px);
        }

        /* Tooltip */
        .add-tooltip {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 18px;
            height: 18px;
            background: #d4f4f1;
            color: #2ec4b6;
            border-radius: 50%;
            font-size: 0.75rem;
            margin-left: 0.5rem;
            cursor: help;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .add-form-wrapper {
                padding: 1rem;
            }
            
            .add-form-content {
                padding: 1.5rem;
            }
            
            .add-form-header {
                padding: 1.5rem;
            }
            
            .add-btn {
                width: 100%;
                margin-bottom: 0.5rem;
            }
            
            .add-form-actions {
                flex-direction: column;
            }
        }

        /* Animation */
        @keyframes fadeInAdd {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .add-form-section {
            animation: fadeInAdd 0.5s ease forwards;
        }

        .add-form-left .add-form-section:nth-child(1) { animation-delay: 0.1s; }
        .add-form-left .add-form-section:nth-child(2) { animation-delay: 0.2s; }
        .add-form-right .add-form-section:nth-child(1) { animation-delay: 0.3s; }
        .add-form-right .add-form-section:nth-child(2) { animation-delay: 0.4s; }
        .add-form-actions { animation-delay: 0.5s; }
    </style>

    <script type="text/javascript">
      function validate() {
        var letters = /^[A-Z./:;!`"|<>_-?+@#%^&*~, a-z0-9]+$/;
        if (!document.addForm.attName.value.match(letters)) {
          alert("Containing Invalid Characters in Attribute Name");
          return false;
        }
        if (!document.addForm.csf.value.match(letters)) {
          alert("Containing Invalid Characters in CSF");
          return false;
        }
        if (!document.addForm.processObjective.value.match(letters)) {
          alert("Containing Invalid Characters in Process Objective");
          return false;
        }
        if (!document.addForm.processKPI.value.match(letters)) {
          alert("Containing Invalid Characters in Process KPI");
          return false;
        }

        return (true);
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

    <?php if (isset($_GET["id"])) { ?>
    <!-- Update Attribute Form with New Layout: Basic Info + Evaluation Method (Left) and Process Details + Assign Evaluators (Right) -->
    <div class="update-form-wrapper">
      <?php
        $id = $_GET["id"];
        $sql = "SELECT * FROM attribute WHERE attribute_id ='$id'";
        $result = $conn->query($sql);
        $row = $result->fetch_assoc();
      ?>
      
      <div class="update-form-container">
        <div class="update-form-header">
          <h3>Update Attribute</h3>
          <p>Modify attribute details and settings</p>
        </div>
        
        <form name="addForm" enctype="multipart/form-data" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" onsubmit="return validate()" method="POST">
          <div class="update-form-content">
            <input type="hidden" name="attId" value="<?php echo $row['attribute_id'] ?>" />
            
            <!-- Left Column: Basic Information + Evaluation Method -->
            <div class="update-form-left">
              <!-- Basic Information Section -->
              <div class="update-form-section">
                <div class="update-form-section-title">
                  <i class='bx bx-info-circle'></i> Basic Information
                </div>
                
                <div class="update-form-group">
                  <label class="update-form-label">Attribute Name <span class="update-tooltip" title="Enter the name of the attribute">?</span></label>
                  <input type="text" name="attName" class="update-form-input" value="<?php echo htmlspecialchars($row['attribute_name']) ?>" required placeholder="Enter attribute name">
                </div>

                <div class="update-form-group">
                  <label class="update-form-label">Weightage <span class="update-tooltip" title="Adjust the importance weight of this attribute">?</span></label>
                  <div class="update-weightage-container">
                    <div class="update-weightage-header">
                      <span>Importance Level:</span>
                      <span class="update-weightage-value" id="slider_value"><?php echo $row['weightage'] ?></span>
                    </div>
                    <input type="range" name="weight" id="weight" min="1" max="10" step="1" 
                           oninput="document.getElementById('slider_value').innerHTML = this.value" 
                           class="update-range-input" 
                           value="<?php echo $row['weightage'] ?>">
                    <div class="update-slider-labels">
                      <span>Low Priority</span>
                      <span>Medium Priority</span>
                      <span>High Priority</span>
                    </div>
                  </div>
                </div>

                <div class="update-form-group">
                  <label class="update-form-label">Category</label>
                  <div class="update-select-wrapper">
                    <select name="category" class="update-form-input" required>
                      <option value="">Select Category</option>
                      <?php 
                      if ($resultfind->num_rows > 0) {
                        $resultfind->data_seek(0);
                        while ($rowfind = $resultfind->fetch_assoc()) { ?>
                          <option value="<?php echo $rowfind['category_name'] ?>" <?php if ($rowfind['category_name'] == $row['category']) { echo "selected"; } ?>>
                            <?php echo $rowfind['category_name'] ?>
                          </option>
                      <?php } 
                      } ?>
                    </select>
                  </div>
                </div>

                <div class="update-form-group">
                  <label class="update-form-label">Marking Scheme <span style="color: #4361ee; font-size: 0.8rem;">(<?php echo $current_year; ?> only)</span></label>
                  <div class="update-select-wrapper">
                    <select name="measuringMethod" class="update-form-input" required>
                      <option value="">Select Marking Scheme for <?php echo $current_year; ?></option>
                      <?php 
                      // MODIFIED: Use the filtered result that only contains current year schemes
                      if ($resultfind1->num_rows > 0) {
                        $resultfind1->data_seek(0);
                        while ($rowfind1 = $resultfind1->fetch_assoc()) { ?>
                          <option value="<?php echo $rowfind1['sm_name'] ?>" <?php if ($rowfind1['sm_name'] == $row['scoring_method']) { echo "selected"; } ?>>
                            <?php echo $rowfind1['sm_name'] ?> (<?php echo $rowfind1['year']; ?>)
                          </option>
                      <?php } 
                      } else { ?>
                          <option value="" disabled>No marking schemes available for <?php echo $current_year; ?></option>
                      <?php } ?>
                    </select>
                  </div>
                </div>
              </div>

              <!-- Evaluation Method Section -->
              <div class="update-form-section">
                <div class="update-form-section-title">
                  <i class='bx bx-task'></i> Evaluation Method
                  <span class="update-status-indicator <?php echo $row['is_valued'] ? 'update-status-active' : 'update-status-inactive'; ?>"></span>
                </div>
                
                <div class="update-radio-group">
                  <div class="update-radio-option">
                    <input type="radio" id="valued1" name="isValued" value="1" <?php if ($row['is_valued']) { echo "checked"; } ?>>
                    <label for="valued1">
                      <strong>Result/Value for a Period</strong>
                      <br>
                      <small style="color: #666; font-weight: normal;">Evaluation based on measurable results over a period</small>
                    </label>
                  </div>
                  <div class="update-radio-option">
                    <input type="radio" id="valued0" name="isValued" value="0" <?php if (!$row['is_valued']) { echo "checked"; } ?>>
                    <label for="valued0">
                      <strong>Critical Incident Method</strong>
                      <br>
                      <small style="color: #666; font-weight: normal;">Evaluation based on specific critical incidents</small>
                    </label>
                  </div>
                </div>
              </div>
            </div>

            <!-- Right Column: Process Details + Assign Evaluators -->
            <div class="update-form-right">
              <!-- Process Details Section -->
              <div class="update-form-section">
                <div class="update-form-section-title">
                  <i class='bx bx-target-lock'></i> Process Details
                </div>
                
                <div class="update-form-group">
                  <label class="update-form-label">Process Objective</label>
                  <input type="text" name="processObjective" class="update-form-input" value="<?php echo htmlspecialchars($row['process_objective']) ?>" required placeholder="Enter process objective">
                </div>

                <div class="update-form-group">
                  <label class="update-form-label">Process KPI</label>
                  <input type="text" name="processKPI" class="update-form-input" value="<?php echo htmlspecialchars($row['process_kpi']) ?>" required placeholder="Enter process KPI">
                </div>

                <div class="update-form-group">
                  <label class="update-form-label">Critical Success Factor (CSF)</label>
                  <input type="text" name="csf" class="update-form-input" value="<?php echo htmlspecialchars($row['csf']) ?>" required placeholder="Enter critical success factor">
                </div>
              </div>

              <!-- Evaluators Assignment Section -->
              <div class="update-form-section">
                <div class="update-form-section-title">
                  <i class='bx bx-user-check'></i> Assign Evaluators
                </div>
                
                <div class="update-checkbox-group">
                  <div class="update-checkbox-option">
                    <input type="checkbox" id="PLE" name="check_list[]" value="PLE" <?php if ($row['PLE']) { echo "checked"; } ?>>
                    <label for="PLE">
                      <strong>PLE</strong><br>
                      <small style="color: #666; font-weight: normal;">Production Line Evaluator</small>
                    </label>
                  </div>
                  <div class="update-checkbox-option">
                    <input type="checkbox" id="OE" name="check_list[]" value="OE" <?php if ($row['OE']) { echo "checked"; } ?>>
                    <label for="OE">
                      <strong>OE</strong><br>
                      <small style="color: #666; font-weight: normal;">Operations Evaluator</small>
                    </label>
                  </div>
                  <div class="update-checkbox-option">
                    <input type="checkbox" id="QM" name="check_list[]" value="QM" <?php if ($row['QM']) { echo "checked"; } ?>>
                    <label for="QM">
                      <strong>QM</strong><br>
                      <small style="color: #666; font-weight: normal;">Quality Manager</small>
                    </label>
                  </div>
                  <div class="update-checkbox-option">
                    <input type="checkbox" id="PE" name="check_list[]" value="PE" <?php if ($row['PE']) { echo "checked"; } ?>>
                    <label for="PE">
                      <strong>PE</strong><br>
                      <small style="color: #666; font-weight: normal;">Performance Evaluator</small>
                    </label>
                  </div>
                </div>
              </div>
            </div>

            <!-- Form Actions (Full Width) -->
            <div class="update-form-actions">
              <button type="button" class="update-btn update-btn-secondary" onclick="window.location.href='AttributeCategories.php'">
                <i class='bx bx-arrow-back'></i> Back to Attributes
              </button>
              <button type="submit" name="update" class="update-btn update-btn-primary">
                <i class='bx bx-save'></i> Save Changes
              </button>
            </div>
          </div>
        </form>
      </div>
    </div>
    <?php } else { ?>
    <!-- Add Attribute Form with New Layout: Basic Info + Evaluation Method (Left) and Process Details + Assign Evaluators (Right) -->
    <div class="add-form-wrapper">
      <div class="add-form-container">
        <div class="add-form-header">
          <h3>Add New Attribute</h3>
          <p>Create a new attribute for employee evaluation</p>
        </div>
        
        <form name="addForm" enctype="multipart/form-data" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" onsubmit="return validate()" method="POST">
          <div class="add-form-content">
            
            <!-- Left Column: Basic Information + Evaluation Method -->
            <div class="add-form-left">
              <!-- Basic Information Section -->
              <div class="add-form-section">
                <div class="add-form-section-title">
                  <i class='bx bx-info-circle'></i> Basic Information
                </div>
                
                <div class="add-form-group">
                  <label class="add-form-label">Attribute Name <span class="add-tooltip" title="Enter the name of the attribute">?</span></label>
                  <input type="text" name="attName" class="add-form-input" required placeholder="Enter attribute name">
                </div>

                <div class="add-form-group">
                  <label class="add-form-label">Weightage <span class="add-tooltip" title="Adjust the importance weight of this attribute">?</span></label>
                  <div class="add-weightage-container">
                    <div class="add-weightage-header">
                      <span>Importance Level:</span>
                      <span class="add-weightage-value" id="add_slider_value">5</span>
                    </div>
                    <input type="range" name="weight" id="weight" min="1" max="10" step="1" 
                           oninput="document.getElementById('add_slider_value').innerHTML = this.value" 
                           class="add-range-input" 
                           value="5">
                    <div class="add-slider-labels">
                      <span>Low Priority</span>
                      <span>Medium Priority</span>
                      <span>High Priority</span>
                    </div>
                  </div>
                </div>

                <div class="add-form-group">
                  <label class="add-form-label">Category</label>
                  <div class="add-select-wrapper">
                    <select name="category" class="add-form-input" required>
                      <option value="">Select Category</option>
                      <?php 
                      if ($resultfind->num_rows > 0) {
                        $resultfind->data_seek(0);
                        while ($rowfind = $resultfind->fetch_assoc()) { ?>
                          <option value="<?php echo $rowfind['category_name'] ?>">
                            <?php echo $rowfind['category_name'] ?>
                          </option>
                      <?php } 
                      } ?>
                    </select>
                  </div>
                </div>

                <div class="add-form-group">
                  <label class="add-form-label">Marking Scheme <span style="color: #2ec4b6; font-size: 0.8rem;">(<?php echo $current_year; ?> only)</span></label>
                  <div class="add-select-wrapper">
                    <select name="measuringMethod" class="add-form-input" required>
                      <option value="">Select Marking Scheme for <?php echo $current_year; ?></option>
                      <?php 
                      // MODIFIED: Use the filtered result that only contains current year schemes
                      if ($resultfind1->num_rows > 0) {
                        $resultfind1->data_seek(0);
                        while ($rowfind1 = $resultfind1->fetch_assoc()) { ?>
                          <option value="<?php echo $rowfind1['sm_name'] ?>">
                            <?php echo $rowfind1['sm_name'] ?> (<?php echo $rowfind1['year']; ?>)
                          </option>
                      <?php } 
                      } else { ?>
                          <option value="" disabled>No marking schemes available for <?php echo $current_year; ?></option>
                      <?php } ?>
                    </select>
                  </div>
                </div>
              </div>

              <!-- Evaluation Method Section -->
              <div class="add-form-section">
                <div class="add-form-section-title">
                  <i class='bx bx-task'></i> Evaluation Method
                </div>
                
                <div class="add-radio-group">
                  <div class="add-radio-option">
                    <input type="radio" id="add_valued1" name="isValued" value="1">
                    <label for="add_valued1">
                      <strong>Result/Value for a Period</strong>
                      <br>
                      <small style="color: #666; font-weight: normal;">Evaluation based on measurable results over a period</small>
                    </label>
                  </div>
                  <div class="add-radio-option">
                    <input type="radio" id="add_valued0" name="isValued" value="0" checked>
                    <label for="add_valued0">
                      <strong>Critical Incident Method</strong>
                      <br>
                      <small style="color: #666; font-weight: normal;">Evaluation based on specific critical incidents</small>
                    </label>
                  </div>
                </div>
              </div>
            </div>

            <!-- Right Column: Process Details + Assign Evaluators -->
            <div class="add-form-right">
              <!-- Process Details Section -->
              <div class="add-form-section">
                <div class="add-form-section-title">
                  <i class='bx bx-target-lock'></i> Process Details
                </div>
                
                <div class="add-form-group">
                  <label class="add-form-label">Process Objective</label>
                  <input type="text" name="processObjective" class="add-form-input" required placeholder="Enter process objective">
                </div>

                <div class="add-form-group">
                  <label class="add-form-label">Process KPI</label>
                  <input type="text" name="processKPI" class="add-form-input" required placeholder="Enter process KPI">
                </div>

                <div class="add-form-group">
                  <label class="add-form-label">Critical Success Factor (CSF)</label>
                  <input type="text" name="csf" class="add-form-input" required placeholder="Enter critical success factor">
                </div>
              </div>

              <!-- Evaluators Assignment Section -->
              <div class="add-form-section">
                <div class="add-form-section-title">
                  <i class='bx bx-user-check'></i> Assign Evaluators
                </div>
                
                <div class="add-checkbox-group">
                  <div class="add-checkbox-option">
                    <input type="checkbox" id="add_PLE" name="check_list[]" value="PLE">
                    <label for="add_PLE">
                      <strong>PLE</strong><br>
                      <small style="color: #666; font-weight: normal;">Production Line Evaluator</small>
                    </label>
                  </div>
                  <div class="add-checkbox-option">
                    <input type="checkbox" id="add_OE" name="check_list[]" value="OE">
                    <label for="add_OE">
                      <strong>OE</strong><br>
                      <small style="color: #666; font-weight: normal;">Operations Evaluator</small>
                    </label>
                  </div>
                  <div class="add-checkbox-option">
                    <input type="checkbox" id="add_QM" name="check_list[]" value="QM">
                    <label for="add_QM">
                      <strong>QM</strong><br>
                      <small style="color: #666; font-weight: normal;">Quality Manager</small>
                    </label>
                  </div>
                  <div class="add-checkbox-option">
                    <input type="checkbox" id="add_PE" name="check_list[]" value="PE">
                    <label for="add_PE">
                      <strong>PE</strong><br>
                      <small style="color: #666; font-weight: normal;">Performance Evaluator</small>
                    </label>
                  </div>
                </div>
              </div>
            </div>

            <!-- Form Actions (Full Width) -->
            <div class="add-form-actions">
              <button type="button" class="add-btn add-btn-secondary" onclick="window.location.href='AttributeCategories.php'">
                <i class='bx bx-x'></i> Cancel
              </button>
              <button type="submit" name="submit" class="add-btn add-btn-primary">
                <i class='bx bx-plus'></i> Add Attribute
              </button>
            </div>
          </div>
        </form>
      </div>
    </div>
    <?php } ?>

    <?php
    if (isset($_POST['update'])) {

      $attId = $_POST['attId'];
      $attName = $_POST['attName'];
      $weight = $_POST['weight'];
      $category = $_POST['category'];
      $csf = $_POST['csf'];
      $processObjective = $_POST['processObjective'];
      $processKPI = $_POST['processKPI'];
      $measuringMethod = $_POST['measuringMethod'];
      $isValued = $_POST['isValued'];

      $PLE = $OE = $QM = $PE = 0;
      foreach ($_POST['check_list'] as $checkbox) {
        if ($checkbox == 'PLE') {
          $PLE = 1;
        }
        if ($checkbox == 'OE') {
          $OE = 1;
        }
        if ($checkbox == 'QM') {
          $QM = 1;
        }
        if ($checkbox == 'PE') {
          $PE = 1;
        }
      }

      $sql = "UPDATE attribute
      SET attribute_name = '$attName', weightage = '$weight', category = '$category', csf = '$csf', process_objective = '$processObjective', process_kpi = '$processKPI', PLE = '$PLE', OE = '$OE', QM = '$QM', PE = '$PE', scoring_method = '$measuringMethod', is_valued = '$isValued'
      WHERE attribute_id = '$attId'";

      $result = $conn->query($sql);

      if ($result == TRUE) {
        // Get current year
        $current_year = date('Y');
        
        // Save to history table for current year
        saveAttributeScoringHistory($conn, $attId, $measuringMethod, $current_year);
        
        echo "<script>alert('Attribute Updated Successfully!');
        window.location.href='AttributeCategories.php';
        </script>";
      } else {
        echo "Error:" . $sql . "<br>" . $conn->error;
      }
    } ?>

  </body>

  </html>

<?php } else {
  header("location: signin.php");
}
?>