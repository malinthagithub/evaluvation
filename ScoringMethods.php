<?php
include "config.php";
session_start();

// Get current year
$current_year = date('Y');

// Check if system is frozen
$freeze_query = "SELECT evaluation_frozen FROM system_settings WHERE id = 1";
$freeze_result = mysqli_query($conn, $freeze_query);
if ($freeze_result && mysqli_num_rows($freeze_result) > 0) {
    $freeze_data = mysqli_fetch_assoc($freeze_result);
    $is_frozen = $freeze_data['evaluation_frozen'];
} else {
    $is_frozen = '0';
}

// Handle copy schemes action
if (isset($_POST['copy_schemes']) && $_SESSION["isAdmin"]) {
    $from_year = intval($_POST['from_year']);
    $to_year = intval($_POST['to_year']);
    
    if ($from_year != $to_year) {
        // Check if schemes already exist in target year
        $check_query = "SELECT COUNT(*) as count FROM scoring_method WHERE `year` = '$to_year'";
        $check_result = mysqli_query($conn, $check_query);
        $check_row = mysqli_fetch_assoc($check_result);
        
        if ($check_row['count'] > 0) {
            $copy_message = "Warning: Target year $to_year already has marking schemes. No schemes were copied.";
            $copy_message_type = "warning";
        } else {
            // Copy schemes from source year to target year
            $copy_query = "INSERT INTO scoring_method (`sm_name`, `year`, `5_left`, `5_right`, `4_left`, `4_right`, `3_left`, `3_right`, `2_left`, `2_right`, `1_left`, `1_right`, `0_left`, `0_right`)
                           SELECT `sm_name`, '$to_year', `5_left`, `5_right`, `4_left`, `4_right`, `3_left`, `3_right`, `2_left`, `2_right`, `1_left`, `1_right`, `0_left`, `0_right`
                           FROM scoring_method WHERE `year` = '$from_year'";
            
            if (mysqli_query($conn, $copy_query)) {
                $copied_count = mysqli_affected_rows($conn);
                $copy_message = "Successfully copied $copied_count marking scheme(s) from $from_year to $to_year.";
                $copy_message_type = "success";
            } else {
                $copy_message = "Error copying schemes: " . mysqli_error($conn);
                $copy_message_type = "danger";
            }
        }
    } else {
        $copy_message = "Source and target years cannot be the same.";
        $copy_message_type = "warning";
    }
}

if (isset($_SESSION["user"])) {

    // Get year from URL parameter or use current year
    $selected_year = isset($_GET['year']) ? intval($_GET['year']) : $current_year;
    
    // Get available years for dropdown
    $years_query = "SELECT DISTINCT `year` FROM scoring_method ORDER BY `year` DESC";
    $years_result = $conn->query($years_query);
    
    // Get all years for copy dropdown (including years with no schemes)
    $all_years = array();
    $all_years_query = "SELECT DISTINCT `year` FROM scoring_method UNION SELECT DISTINCT YEAR(NOW()) ORDER BY `year` DESC";
    $all_years_result = $conn->query($all_years_query);
    if ($all_years_result) {
        while ($year_row = $all_years_result->fetch_assoc()) {
            $all_years[] = $year_row['year'];
        }
    }
    // Add current year if not present
    if (!in_array($current_year, $all_years)) {
        $all_years[] = $current_year;
    }
    rsort($all_years);
    
    // Get scoring methods for selected year
    $sql = "SELECT * FROM scoring_method WHERE `year` = '$selected_year' ORDER BY sm_id DESC";
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
    <title>Marking Schemes - <?php echo $selected_year; ?></title>
    <link rel="icon" type="image/x-icon" href="./img/jb.png">

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <!-- BoxIcons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/boxicons@latest/css/boxicons.min.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />

    <link rel="stylesheet" href="./style.css" />
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
            z-index: 9999;
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

        .modern-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            overflow: hidden;
            background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
            height: 100%;
            display: flex;
            flex-direction: column;
        }

        .modern-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
        }

        .gradient-header {
            background: linear-gradient(135deg, var(--primary-color), #3a56d4);
            color: white;
            padding: 20px;
            text-align: center;
            position: relative;
        }

        .gradient-header h3 {
            margin: 0;
            font-weight: 600;
            font-size: 1.5rem;
        }

        .year-stamp {
            position: absolute;
            top: 10px;
            right: 10px;
            background: rgba(255, 255, 255, 0.2);
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            color: white;
        }

        .score-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 8px;
            padding: 20px;
            flex: 1;
        }

        .score-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 10px 15px;
            background: #f8fafc;
            border-radius: 10px;
            transition: all 0.2s ease;
            border-left: 4px solid transparent;
        }

        .score-item:hover {
            background: #eef2ff;
            transform: translateX(5px);
        }

        .score-5:hover { border-left-color: #4361ee; }
        .score-4:hover { border-left-color: #4895ef; }
        .score-3:hover { border-left-color: #3f37c9; }
        .score-2:hover { border-left-color: #3a0ca3; }
        .score-1:hover { border-left-color: #7209b7; }
        .score-0:hover { border-left-color: #f72585; }

        .score-label {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .score-circle {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            color: white;
            font-size: 0.9rem;
        }

        .score-5 .score-circle {
            background: linear-gradient(135deg, #4cc9f0, #4361ee);
        }

        .score-4 .score-circle {
            background: linear-gradient(135deg, #4895ef, #3f37c9);
        }

        .score-3 .score-circle {
            background: linear-gradient(135deg, #4361ee, #3a0ca3);
        }

        .score-2 .score-circle {
            background: linear-gradient(135deg, #3a0ca3, #7209b7);
        }

        .score-1 .score-circle {
            background: linear-gradient(135deg, #7209b7, #f72585);
        }

        .score-0 .score-circle {
            background: linear-gradient(135deg, #f72585, #b5179e);
        }

        .score-value {
            font-weight: 500;
            color: var(--dark-color);
            font-size: 0.9rem;
            text-align: right;
            font-family: monospace;
            background: white;
            padding: 4px 10px;
            border-radius: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }

        .card-actions {
            display: flex;
            justify-content: center;
            gap: 10px;
            padding: 15px 20px;
            background: #f8fafc;
            border-top: 1px solid #e2e8f0;
            margin-top: auto;
        }

        .action-btn {
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 8px 16px;
            border-radius: 8px;
            border: none;
            font-weight: 500;
            transition: all 0.2s ease;
            text-decoration: none;
            font-size: 0.9rem;
        }

        .action-btn.edit {
            background: linear-gradient(135deg, #4361ee, #3a56d4);
            color: white;
        }

        .action-btn.edit:hover {
            background: linear-gradient(135deg, #3a56d4, #2f48b9);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(67, 97, 238, 0.3);
            color: white;
        }

        .action-btn.delete {
            background: linear-gradient(135deg, #f72585, #e01e74);
            color: white;
        }

        .action-btn.delete:hover {
            background: linear-gradient(135deg, #e01e74, #c81a66);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(247, 37, 133, 0.3);
            color: white;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
        }

        .empty-state i {
            font-size: 4rem;
            color: #cbd5e1;
            margin-bottom: 20px;
        }

        .empty-state h4 {
            color: #64748b;
            margin-bottom: 10px;
        }

        .empty-state p {
            color: #94a3b8;
            max-width: 400px;
            margin: 0 auto;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding: 20px 0;
            border-bottom: 1px solid #e2e8f0;
            flex-wrap: wrap;
            gap: 15px;
        }

        .header-left {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }

        .page-title {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--dark-color);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .page-title i {
            color: var(--primary-color);
        }

        .year-badge {
            display: inline-block;
            padding: 3px 10px;
            background: linear-gradient(135deg, var(--primary-color), #3a56d4);
            color: white;
            border-radius: 20px;
            font-size: 0.8rem;
            margin-left: 10px;
        }

        .year-selector {
            display: flex;
            align-items: center;
            gap: 10px;
            background: #f8fafc;
            padding: 5px 15px;
            border-radius: 10px;
        }

        .year-dropdown {
            padding: 8px 15px;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            font-size: 0.95rem;
            color: var(--dark-color);
            background: white;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .year-dropdown:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.1);
        }

        .add-btn {
            background: linear-gradient(135deg, var(--primary-color), #3a56d4);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 500;
            transition: all 0.3s ease;
            text-decoration: none;
        }

        .add-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(67, 97, 238, 0.3);
            color: white;
        }

        .copy-btn {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 500;
            transition: all 0.3s ease;
            text-decoration: none;
        }

        .copy-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(16, 185, 129, 0.3);
            color: white;
        }

        .Admin .modern-card {
            border-left: 4px solid #4361ee;
        }

        .Evaluator .modern-card {
            border-left: 4px solid #4cc9f0;
        }

        .Guest .modern-card {
            border-left: 4px solid #94a3b8;
        }

        .floating-container {
            position: fixed;
            bottom: 30px;
            right: 30px;
            z-index: 1000;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .floating-button {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 24px;
            text-decoration: none;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
        }

        .floating-button.Admin {
            background: linear-gradient(135deg, #4361ee, #3a0ca3);
        }

        .floating-button.copy {
            background: linear-gradient(135deg, #10b981, #059669);
        }

        .floating-button:hover {
            transform: scale(1.1);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.3);
        }

        /* Modal Styles */
        .modal {
            display: none !important;
            position: fixed !important;
            top: 0 !important;
            left: 0 !important;
            width: 100% !important;
            height: 100% !important;
            background-color: rgba(0, 0, 0, 0.5) !important;
            z-index: 99999 !important;
            overflow-y: auto !important;
        }
        
        .modal.show {
            display: block !important;
        }
        
        .modal-dialog {
            position: relative !important;
            width: auto !important;
            max-width: 500px !important;
            margin: 30px auto !important;
            pointer-events: none !important;
            transform: none !important;
        }
        
        .modal-dialog-centered {
            min-height: calc(100% - 60px) !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
        }
        
        .modal-content {
            position: relative !important;
            display: flex !important;
            flex-direction: column !important;
            width: 100% !important;
            background-color: #fff !important;
            border-radius: 15px !important;
            border: none !important;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2) !important;
            pointer-events: auto !important;
        }

        .modal-header {
            background: linear-gradient(135deg, var(--primary-color), #3a56d4);
            color: white;
            border-radius: 15px 15px 0 0;
            padding: 20px;
            border-bottom: none;
        }

        .modal-header .btn-close {
            filter: brightness(0) invert(1);
            opacity: 1;
        }

        .modal-body {
            padding: 30px;
        }

        .modal-footer {
            padding: 20px;
            border-top: 1px solid #e2e8f0;
            background: #f8fafc;
            border-radius: 0 0 15px 15px;
        }

        .copy-preview {
            background: #f8fafc;
            border-radius: 10px;
            padding: 15px;
            margin-top: 15px;
            border: 1px solid #e2e8f0;
        }

        .alert {
            border-radius: 10px;
            padding: 15px 20px;
            margin-bottom: 20px;
            border: none;
        }

        .alert-success {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
        }

        .alert-warning {
            background: linear-gradient(135deg, #f59e0b, #d97706);
            color: white;
        }

        .alert-danger {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
        }
        
        /* Ensure proper z-index for nav bar */
        .l-navbar {
            z-index: 1000;
        }
        
        /* Form controls */
        .form-select, .form-control {
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            padding: 10px 15px;
        }
        
        .form-select:focus, .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.1);
            outline: none;
        }
        
        .btn-success {
            background: linear-gradient(135deg, #10b981, #059669);
            border: none;
            padding: 10px 25px;
            border-radius: 10px;
        }
        
        .btn-success:hover {
            background: linear-gradient(135deg, #059669, #047857);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
        }
        
        .btn-secondary {
            background: #64748b;
            border: none;
            padding: 10px 25px;
            border-radius: 10px;
        }
        
        .btn-secondary:hover {
            background: #475569;
        }

        /* Type badges */
        .type-badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 0.7rem;
            font-weight: 600;
            margin-top: 5px;
        }
        
        .type-higher {
            background: #dbeafe;
            color: #1e40af;
        }
        
        .type-lower {
            background: #dcfce7;
            color: #166534;
        }
        
        .type-posneg {
            background: #f3e8ff;
            color: #6b21a8;
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

    <!-- Copy Message Alert -->
    <?php if (isset($copy_message)): ?>
        <div class="system-alert">
            <div class="alert alert-<?php echo $copy_message_type; ?> alert-dismissible fade show" role="alert">
                <i class='bx <?php echo $copy_message_type == 'success' ? 'bx-check-circle' : ($copy_message_type == 'warning' ? 'bx-error' : 'bx-x-circle'); ?> me-2'></i>
                <?php echo $copy_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        </div>
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
                    <a href="./ScoringMethods.php" class="nav_link active"> <i class='bx bx-tachometer nav_icon'></i> <span class="nav_name">Marking Schemes</span> </a>
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

    <div class="height-100 container mt-5 mb-5" style="padding-top: 2vh;">
        <div class="page-header">
            <div class="header-left">
                <div class="page-title">
                    <i class='bx bx-tachometer'></i>
                    Marking Schemes
                    <span class="year-badge"><?php echo $selected_year; ?></span>
                </div>
                
                <!-- Year Selector Dropdown -->
                <div class="year-selector">
                    <i class='bx bx-calendar text-primary'></i>
                    <select id="yearSelect" class="year-dropdown" onchange="changeYear(this.value)">
                        <?php 
                        // Add current year if not in database
                        $years = array();
                        if ($years_result && $years_result->num_rows > 0) {
                            while($year_row = $years_result->fetch_assoc()) {
                                $years[] = $year_row['year'];
                            }
                        }
                        $years[] = $current_year;
                        $years = array_unique($years);
                        rsort($years);
                        
                        foreach($years as $year): 
                        ?>
                            <option value="<?php echo $year; ?>" <?php echo $year == $selected_year ? 'selected' : ''; ?>>
                                <?php echo $year; ?> <?php echo ($year == $current_year) ? '(Current)' : ''; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div class="d-flex gap-2">
                <?php if ($_SESSION["isAdmin"]) { ?>
                    <!-- Copy Schemes Button -->
                    <button type="button" class="copy-btn" onclick="openCopyModal()">
                        <i class='bx bx-copy'></i>
                        Copy from Previous Year
                    </button>
                    
                    <!-- Add New Scheme Button -->
                    <a href="./scoring_method.php?year=<?php echo $selected_year; ?>" class="add-btn">
                        <i class='bx bx-plus'></i>
                        Add New Scheme
                    </a>
                <?php } ?>
            </div>
        </div>

        <?php if ($result->num_rows > 0) { ?>
            <div class="row g-4">
                <?php while ($row = $result->fetch_assoc()) { 
                    // Determine scoring pattern
                    $hasBothEnds = ($row['5_left'] !== NULL && $row['5_right'] !== NULL && $row['0_left'] !== NULL && $row['0_right'] !== NULL);
                    $hasLeftOnly = ($row['5_right'] === NULL && $row['0_left'] === NULL);
                    $hasRightOnly = ($row['5_left'] === NULL && $row['0_right'] === NULL);
                    
                    // Determine type for badge
                    $schemeType = "Standard";
                    $typeClass = "";
                    if ($hasBothEnds) {
                        $schemeType = "Positive/Negative";
                        $typeClass = "type-posneg";
                    } elseif ($hasLeftOnly) {
                        $schemeType = "Lower is Better";
                        $typeClass = "type-lower";
                    } elseif ($hasRightOnly) {
                        $schemeType = "Higher is Better";
                        $typeClass = "type-higher";
                    }
                    
                    // Get scheme name for display in criteria
                    $schemeName = htmlspecialchars($row['sm_name']);
                ?>
                    <div class="col-lg-4 col-md-6">
                        <div class="modern-card">
                            <div class="gradient-header">
                                <h3><?php echo $schemeName; ?></h3>
                                <div class="year-stamp"><?php echo $row['year']; ?></div>
                                <div class="type-badge <?php echo $typeClass; ?>">
                                    <?php echo $schemeType; ?>
                                </div>
                            </div>

                            <div class="score-grid">
                                <?php for ($i = 5; $i >= 0; $i--): 
                                    $left_val = $row[$i.'_left'];
                                    $right_val = $row[$i.'_right'];
                                ?>
                                    <div class="score-item score-<?php echo $i; ?>">
                                        <div class="score-label">
                                            <div class="score-circle"><?php echo $i; ?></div>
                                            <span style="font-weight: 500;">Score <?php echo $i; ?></span>
                                        </div>
                                        <div class="score-value">
                                            <?php 
                                            // PATTERN 1: Positive/Negative (General type with N and P)
                                            if ($hasBothEnds): 
                                                // MODIFIED: Special display format for "Incoming Inspection of PM" scheme
                                                if ($schemeName == "Incoming Inspection of PM") {
                                                    // Display in the requested format for Incoming Inspection of PM
                                                    if ($i == 5) {
                                                        echo "N = " . ($left_val ?? '0') . " & P > " . ($right_val ?? '10');
                                                    } elseif ($i == 4) {
                                                        echo "N = " . ($left_val ?? '0') . " & P > " . ($right_val ?? '1');
                                                    } elseif ($i == 3) {
                                                        echo "N = " . ($left_val ?? '0') . " & P = " . ($right_val ?? '0');
                                                    } elseif ($i == 2) {
                                                        echo "N = " . ($left_val ?? '1') . " & P = " . ($right_val ?? '0');
                                                    } elseif ($i == 1) {
                                                        echo "N = " . ($left_val ?? '2') . " & P = " . ($right_val ?? '0');
                                                    } elseif ($i == 0) {
                                                        echo "N ≥ " . ($left_val ?? '3') . " & P = " . ($right_val ?? '0');
                                                    } else {
                                                        echo "N = " . ($left_val ?? '0') . ", P = " . ($right_val ?? '0');
                                                    }
                                                }
                                                // Special display format for "General" scheme
                                                elseif ($schemeName == "General") {
                                                    // Display in the requested format
                                                    if ($i == 5) {
                                                        echo "N = " . ($left_val ?? '0') . " & P ≥ " . ($right_val ?? '2');
                                                    } elseif ($i == 4) {
                                                        echo "N = " . ($left_val ?? '0') . " & P = " . ($right_val ?? '1');
                                                    } elseif ($i == 3) {
                                                        echo "N = " . ($left_val ?? '0') . " & P = " . ($right_val ?? '0');
                                                    } elseif ($i == 2) {
                                                        echo "N = " . ($left_val ?? '1') . " & P = " . ($right_val ?? '0');
                                                    } elseif ($i == 1) {
                                                        echo "N = " . ($left_val ?? '2') . " & P = " . ($right_val ?? '0');
                                                    } elseif ($i == 0) {
                                                        echo "N ≥ " . ($left_val ?? '3') . " & P = " . ($right_val ?? '0');
                                                    } else {
                                                        echo "N = " . ($left_val ?? '0') . ", P = " . ($right_val ?? '0');
                                                    }
                                                } else {
                                                    // Original display for other Positive/Negative schemes
                                                    if ($i >= 3) {
                                                        echo "N = " . ($left_val ?? '0') . ", P > " . ($right_val ?? '0');
                                                    } else {
                                                        echo "N ≥ " . ($left_val ?? '0') . ", P = " . ($right_val ?? '0');
                                                    }
                                                }
                                            
                                            // PATTERN 2: Higher is Better (Blend Gain type - right side values only)
                                            elseif ($hasRightOnly):
                                                // Special handling for "Blend Gain" scheme
                                                if ($schemeName == "Blend Gain") {
                                                    if ($i == 5) {
                                                        // Score 5: Blend Gain% ≥ value%
                                                        echo $schemeName . "% ≥ " . number_format($right_val, 2) . "%";
                                                    } elseif ($i == 1) {
                                                        // Score 1: UPPER% > Blend Gain% > LOWER%
                                                        $lower = number_format($right_val, 2);
                                                        $upper = number_format($left_val, 2);
                                                        echo $upper . "% > " . $schemeName . "% > " . $lower . "%";
                                                    } elseif ($i == 0) {
                                                        // Score 0: 0.00% = Blend Gain%
                                                        echo "0.00% = " . $schemeName . "%";
                                                    } elseif ($i == 4 || $i == 3 || $i == 2) {
                                                        // For scores 4,3,2: UPPER% > Blend Gain% ≥ LOWER%
                                                        $lower = number_format($right_val, 2);
                                                        $upper = number_format($left_val, 2);
                                                        echo $upper . "% > " . $schemeName . "% ≥ " . $lower . "%";
                                                    } else {
                                                        // Default fallback
                                                        if ($i == 5) {
                                                            echo $schemeName . "% ≥ " . number_format($right_val, 2) . "%";
                                                        } elseif ($i == 0) {
                                                            echo $schemeName . "% < " . number_format($left_val, 2) . "%";
                                                        } else {
                                                            $lower = number_format($right_val, 2);
                                                            $upper = number_format($left_val, 2);
                                                            echo $upper . "% > " . $schemeName . "% ≥ " . $lower . "%";
                                                        }
                                                    }
                                                } else {
                                                    // Default handling for other Higher is Better schemes
                                                    if ($i == 5) {
                                                        echo $schemeName . "% ≥ " . number_format($right_val, 2) . "%";
                                                    } elseif ($i == 0) {
                                                        echo $schemeName . "% < " . number_format($left_val, 2) . "%";
                                                    } else {
                                                        $lower = number_format($right_val, 2);
                                                        $upper = number_format($left_val, 2);
                                                        echo $upper . "% > " . $schemeName . "% ≥ " . $lower . "%";
                                                    }
                                                }
                                            
                                            // PATTERN 3: Lower is Better (Wastage type - left side values only)
                                            elseif ($hasLeftOnly):
                                                // Special handling for "Blending Tea Wastage", "Packing Tea Wastage", and "PM Wastage" schemes
                                                if ($schemeName == "Blending Tea Wastage" || $schemeName == "Packing Tea Wastage" || $schemeName == "PM Wastage") {
                                                    if ($i == 5) {
                                                        // Score 5: 0.00% = PM Wastage% (or Tea Wastage% for tea schemes)
                                                        if ($schemeName == "PM Wastage") {
                                                            echo "0.00% = PM Wastage%";
                                                        } else {
                                                            echo "0.00% = Tea Wastage%";
                                                        }
                                                    } elseif ($i == 4) {
                                                        // Score 4: value% ≥ Wastage% > 0.00%
                                                        $upper = number_format($left_val, 2);
                                                        $lower = number_format($right_val, 2);
                                                        if ($schemeName == "PM Wastage") {
                                                            echo $upper . "% ≥ PM Wastage% > " . $lower . "%";
                                                        } else {
                                                            echo $upper . "% ≥ Tea Wastage% > " . $lower . "%";
                                                        }
                                                    } elseif ($i == 0) {
                                                        if (strpos($schemeName, 'Tea Wastage') !== false) {
                                                            $shortName = 'Tea Wastage';
                                                        } else {
                                                            $shortName = $schemeName;
                                                        }
                                                        echo $shortName . "% > " . number_format($right_val, 2) . "%";
                                                    } else {
                                                        // For other scores: UPPER% ≥ Wastage% > LOWER%
                                                        $upper = number_format($left_val, 2);
                                                        $lower = number_format($right_val, 2);
                                                        
                                                        if ($lower == 0) {
                                                            if ($schemeName == "PM Wastage") {
                                                                echo "PM Wastage% ≤ " . $upper . "%";
                                                            } else {
                                                                echo "Tea Wastage% ≤ " . $upper . "%";
                                                            }
                                                        } else {
                                                            if ($schemeName == "PM Wastage") {
                                                                echo $upper . "% ≥ PM Wastage% > " . $lower . "%";
                                                            } else {
                                                                echo $upper . "% ≥ Tea Wastage% > " . $lower . "%";
                                                            }
                                                        }
                                                    }
                                                } else {
                                                    // Default handling for other Lower is Better schemes
                                                    if ($i == 5) {
                                                        echo $schemeName . "% ≤ " . number_format($left_val, 2) . "%";
                                                    } elseif ($i == 0) {
                                                        echo $schemeName . "% > " . number_format($right_val, 2) . "%";
                                                    } else {
                                                        $upper = number_format($left_val, 2);
                                                        $lower = number_format($right_val, 2);
                                                        
                                                        if ($lower == 0) {
                                                            echo $schemeName . "% ≤ " . $upper . "%";
                                                        } else {
                                                            echo $upper . "% ≥ " . $schemeName . "% > " . $lower . "%";
                                                        }
                                                    }
                                                }
                                            else:
                                                // Fallback for any other pattern
                                                if ($left_val !== NULL && $right_val !== NULL) {
                                                    echo number_format($left_val, 2) . "% - " . number_format($right_val, 2) . "%";
                                                } elseif ($left_val !== NULL) {
                                                    echo "≤ " . number_format($left_val, 2) . "%";
                                                } elseif ($right_val !== NULL) {
                                                    echo "≥ " . number_format($right_val, 2) . "%";
                                                } else {
                                                    echo "Not configured";
                                                }
                                            endif;
                                            ?>
                                        </div>
                                    </div>
                                <?php endfor; ?>
                            </div>

                            <?php if ($_SESSION["isAdmin"]) { ?>
                                <div class="card-actions">
                                    <a href="./scoring_method.php?id=<?php echo $row['sm_id']; ?>&year=<?php echo $row['year']; ?>" class="action-btn edit">
                                        <i class='bx bx-pencil'></i>
                                        Edit
                                    </a>
                                    <a href="./delete.php?smId=<?php echo $row['sm_id']; ?>&year=<?php echo $row['year']; ?>" class="action-btn delete" onclick="return confirm('Are you sure you want to delete this marking scheme?');">
                                        <i class='bx bx-trash'></i>
                                        Delete
                                    </a>
                                </div>
                            <?php } ?>
                        </div>
                    </div>
                <?php } ?>
            </div>
        <?php } else { ?>
            <div class="modern-card">
                <div class="empty-state">
                    <i class='bx bx-tachometer'></i>
                    <h4>No Marking Schemes for <?php echo $selected_year; ?></h4>
                    <p>There are no marking schemes configured for the year <?php echo $selected_year; ?>.</p>
                    <?php if ($_SESSION["isAdmin"]) { ?>
                        <div class="d-flex justify-content-center gap-3 mt-3">
                            <button type="button" class="copy-btn" onclick="openCopyModal()">
                                <i class='bx bx-copy'></i>
                                Copy from Previous Year
                            </button>
                            <a href="./scoring_method.php?year=<?php echo $selected_year; ?>" class="add-btn">
                                <i class='bx bx-plus'></i>
                                Create First Scheme
                            </a>
                        </div>
                    <?php } ?>
                </div>
            </div>
        <?php } ?>
    </div>

    <!-- Copy Schemes Modal -->
    <?php if ($_SESSION["isAdmin"]): ?>
    <div id="copyModal" class="modal">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form method="POST" action="" id="copyForm">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class='bx bx-copy me-2'></i>
                            Copy Marking Schemes
                        </h5>
                        <button type="button" class="btn-close" onclick="closeCopyModal()" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p class="text-muted mb-4">Copy all marking schemes from a previous year to <strong><?php echo $selected_year; ?></strong>.</p>
                        
                        <div class="mb-3">
                            <label for="from_year" class="form-label fw-bold">Copy from Year:</label>
                            <select class="form-select" name="from_year" id="from_year" required>
                                <option value="">Select source year</option>
                                <?php 
                                $available_years = array();
                                $years_query = "SELECT DISTINCT `year` FROM scoring_method ORDER BY `year` DESC";
                                $years_result = $conn->query($years_query);
                                if ($years_result && $years_result->num_rows > 0) {
                                    while($year_row = $years_result->fetch_assoc()) {
                                        if ($year_row['year'] != $selected_year) {
                                            $available_years[] = $year_row['year'];
                                        }
                                    }
                                }
                                
                                // Get count of schemes per year for preview
                                if (!empty($available_years)) {
                                    $years_list = implode(',', $available_years);
                                    $count_query = "SELECT `year`, COUNT(*) as count FROM scoring_method WHERE `year` IN ($years_list) GROUP BY `year`";
                                    $count_result = $conn->query($count_query);
                                    $counts = array();
                                    while ($count_row = $count_result->fetch_assoc()) {
                                        $counts[$count_row['year']] = $count_row['count'];
                                    }
                                }
                                
                                foreach($available_years as $year): 
                                    $scheme_count = isset($counts[$year]) ? $counts[$year] : 0;
                                ?>
                                    <option value="<?php echo $year; ?>" data-count="<?php echo $scheme_count; ?>">
                                        <?php echo $year; ?> (<?php echo $scheme_count; ?> scheme<?php echo $scheme_count != 1 ? 's' : ''; ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <input type="hidden" name="to_year" value="<?php echo $selected_year; ?>">
                        
                        <div id="previewSection" class="copy-preview" style="display: none;">
                            <h6 class="fw-bold mb-3">Preview:</h6>
                            <div id="previewContent"></div>
                        </div>
                        
                        <div class="alert alert-warning mt-3" role="alert">
                            <i class='bx bx-error-circle me-2'></i>
                            <strong>Note:</strong> This will copy all marking schemes from the selected year to <?php echo $selected_year; ?>. 
                            If schemes already exist for <?php echo $selected_year; ?>, they will NOT be overwritten.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" onclick="closeCopyModal()">Cancel</button>
                        <button type="submit" name="copy_schemes" class="btn btn-success" id="copySubmitBtn" disabled>
                            <i class='bx bx-copy me-2'></i>
                            Copy Schemes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
    // Modal functions
    function openCopyModal() {
        document.getElementById('copyModal').classList.add('show');
        document.body.style.overflow = 'hidden';
    }
    
    function closeCopyModal() {
        document.getElementById('copyModal').classList.remove('show');
        document.body.style.overflow = '';
        // Reset form
        document.getElementById('from_year').value = '';
        document.getElementById('previewSection').style.display = 'none';
        document.getElementById('copySubmitBtn').disabled = true;
    }
    
    // Close modal when clicking outside
    window.onclick = function(event) {
        const modal = document.getElementById('copyModal');
        if (event.target == modal) {
            closeCopyModal();
        }
    }
    
    document.addEventListener('DOMContentLoaded', function() {
        const fromYearSelect = document.getElementById('from_year');
        const previewSection = document.getElementById('previewSection');
        const previewContent = document.getElementById('previewContent');
        const copySubmitBtn = document.getElementById('copySubmitBtn');
        
        if (fromYearSelect) {
            fromYearSelect.addEventListener('change', function() {
                const selectedOption = this.options[this.selectedIndex];
                const year = this.value;
                const count = selectedOption.getAttribute('data-count');
                
                if (year) {
                    // Show preview
                    previewSection.style.display = 'block';
                    previewContent.innerHTML = `
                        <div class="d-flex align-items-center gap-2">
                            <i class='bx bx-calendar text-primary'></i>
                            <span>Copying from <strong>${year}</strong> to <strong><?php echo $selected_year; ?></strong></span>
                        </div>
                        <div class="d-flex align-items-center gap-2 mt-2">
                            <i class='bx bx-list-ul text-success'></i>
                            <span>${count} scheme${count != 1 ? 's' : ''} will be copied</span>
                        </div>
                    `;
                    copySubmitBtn.disabled = false;
                } else {
                    previewSection.style.display = 'none';
                    copySubmitBtn.disabled = true;
                }
            });
        }
        
        // Add confirmation before submit
        const copyForm = document.getElementById('copyForm');
        if (copyForm) {
            copyForm.addEventListener('submit', function(e) {
                const fromYearSelect = document.getElementById('from_year');
                const fromYear = fromYearSelect.value;
                const toYear = '<?php echo $selected_year; ?>';
                const count = fromYearSelect.options[fromYearSelect.selectedIndex].getAttribute('data-count');
                
                if (!confirm(`Are you sure you want to copy ${count} scheme${count != 1 ? 's' : ''} from ${fromYear} to ${toYear}?`)) {
                    e.preventDefault();
                }
            });
        }
    });
    </script>
    <?php endif; ?>

    <!-- Floating Action Buttons -->
    <?php if ($_SESSION["isAdmin"]) { ?>
        <div class="floating-container">
            <?php if ($result->num_rows == 0): ?>
                <button class="floating-button copy" onclick="openCopyModal()" title="Copy from Previous Year">
                    <i class='bx bx-copy'></i>
                </button>
            <?php endif; ?>
            <a href="./scoring_method.php?year=<?php echo $selected_year; ?>" class="floating-button Admin" title="Add New Scheme">
                <i class='bx bx-plus'></i>
            </a>
        </div>
    <?php } ?>

    <!-- jQuery and Bootstrap JS -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Function to change year
        function changeYear(year) {
            window.location.href = 'ScoringMethods.php?year=' + year;
        }
        
        // Add confirmation for delete action
        document.addEventListener('DOMContentLoaded', function() {
            const deleteButtons = document.querySelectorAll('.action-btn.delete');
            deleteButtons.forEach(button => {
                button.addEventListener('click', function(e) {
                    if (!confirm('Are you sure you want to delete this marking scheme?')) {
                        e.preventDefault();
                    }
                });
            });
            
            // Auto-dismiss alerts after 5 seconds
            setTimeout(function() {
                const alerts = document.querySelectorAll('.alert');
                alerts.forEach(alert => {
                    if (alert) {
                        alert.style.display = 'none';
                    }
                });
            }, 5000);
        });
    </script>
</body>

</html>

<?php } else {
    header("location: signin.php");
}
?>