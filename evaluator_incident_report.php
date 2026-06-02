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

// Check if user is logged in
if (!isset($_SESSION["user"])) {
    header("location: signin.php");
    exit();
}

// Handle period selection
if (isset($_POST['submitPeriod']) && !empty($_POST['sPeriod']) && !empty($_POST['ePeriod'])) {
    $sPeriod = $_POST['sPeriod'];
    $ePeriod = $_POST['ePeriod'];
    header("Location: evaluator_incident_report.php?sp=$sPeriod&ep=$ePeriod");
    exit();
}

// Get period from URL or default to current year
$start_period = isset($_GET['sp']) ? $_GET['sp'] : date('Y-m');
$end_period = isset($_GET['ep']) ? $_GET['ep'] : date('Y-m');

// Handle signout
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

// Get unique evaluators from records table for the selected period
$evaluator_query = "
    SELECT DISTINCT r.evaluator_id, COALESCE(e.evaluator_name, r.evaluator_id) as evaluator_name
    FROM records r
    LEFT JOIN evaluator e ON r.evaluator_id = e.evaluator_id
    WHERE r.period BETWEEN ? AND ?
    ORDER BY evaluator_name
";

$evaluator_stmt = mysqli_prepare($conn, $evaluator_query);
mysqli_stmt_bind_param($evaluator_stmt, "ss", $start_period, $end_period);
mysqli_stmt_execute($evaluator_stmt);
$evaluator_result = mysqli_stmt_get_result($evaluator_stmt);

// Now, for each evaluator, get their record data
$evaluator_data = [];
$monthly_data = [];
$total_records = 0;

while ($evaluator = mysqli_fetch_assoc($evaluator_result)) {
    $evaluator_id = $evaluator['evaluator_id'];
    $evaluator_name = $evaluator['evaluator_name'];
    
    // Query to get record counts by category for this evaluator
    $query = "
        SELECT 
            category AS incident_type,
            period AS month,
            SUM(COALESCE(positive, 0)) AS positive_count,
            SUM(COALESCE(negative, 0)) AS negative_count,
            SUM(COALESCE(positive, 0) + COALESCE(negative, 0) + COALESCE(neutral, 0)) AS total_count,
            COUNT(*) AS record_count
        FROM records 
        WHERE evaluator_id = ? 
        AND period BETWEEN ? AND ?
        GROUP BY category, period
        ORDER BY period, category
    ";
    
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "sss", $evaluator_id, $start_period, $end_period);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $types_data = [];
    $total_positive = 0;
    $total_negative = 0;
    $total_incidents = 0;
    
    while ($row = mysqli_fetch_assoc($result)) {
        $month = $row['month'];
        $type = $row['incident_type'];
        $total_records += $row['record_count'];
        
        // Store by type
        if (!isset($types_data[$type])) {
            $types_data[$type] = [
                'positive' => 0,
                'negative' => 0,
                'total' => 0
            ];
        }
        $types_data[$type]['positive'] += (int)$row['positive_count'];
        $types_data[$type]['negative'] += (int)$row['negative_count'];
        $types_data[$type]['total'] += (int)$row['total_count'];
        
        // Store monthly data
        if (!isset($monthly_data[$month])) {
            $monthly_data[$month] = [
                'total_incidents' => 0,
                'total_positive' => 0,
                'total_negative' => 0,
                'evaluators' => []
            ];
        }
        if (!isset($monthly_data[$month]['evaluators'][$evaluator_id])) {
            $monthly_data[$month]['evaluators'][$evaluator_id] = [
                'name' => $evaluator_name,
                'types' => []
            ];
        }
        if (!isset($monthly_data[$month]['evaluators'][$evaluator_id]['types'][$type])) {
            $monthly_data[$month]['evaluators'][$evaluator_id]['types'][$type] = [
                'positive' => 0,
                'negative' => 0,
                'total' => 0
            ];
        }
        $monthly_data[$month]['evaluators'][$evaluator_id]['types'][$type]['positive'] += (int)$row['positive_count'];
        $monthly_data[$month]['evaluators'][$evaluator_id]['types'][$type]['negative'] += (int)$row['negative_count'];
        $monthly_data[$month]['evaluators'][$evaluator_id]['types'][$type]['total'] += (int)$row['total_count'];
        
        $monthly_data[$month]['total_incidents'] += (int)$row['total_count'];
        $monthly_data[$month]['total_positive'] += (int)$row['positive_count'];
        $monthly_data[$month]['total_negative'] += (int)$row['negative_count'];
        
        $total_positive += (int)$row['positive_count'];
        $total_negative += (int)$row['negative_count'];
        $total_incidents += (int)$row['total_count'];
    }
    
    // Only add evaluator if they have data
    if (!empty($types_data)) {
        $evaluator_data[$evaluator_id] = [
            'name' => $evaluator_name,
            'types' => $types_data,
            'total_positive' => $total_positive,
            'total_negative' => $total_negative,
            'total_incidents' => $total_incidents
        ];
    }
}

// Sort monthly data by month
ksort($monthly_data);

// Get all distinct categories
$category_query = "SELECT DISTINCT category FROM records WHERE category IS NOT NULL ORDER BY category";
$category_result = mysqli_query($conn, $category_query);
$categories = [];
while ($cat = mysqli_fetch_assoc($category_result)) {
    $categories[] = $cat['category'];
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Evaluator Performance Report | JB Employee Evaluation</title>
    <link rel="icon" type="image/x-icon" href="./img/jb.png">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta1/dist/css/bootstrap.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta1/dist/js/bootstrap.bundle.min.js" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.2.1/jquery.min.js" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/boxicons@latest/css/boxicons.min.css" />
    
    <!-- Modern Font Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />

    <link rel="stylesheet" href="./style.css" />
    <script src="./script.js"></script>
    
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

        .report-wrapper {
            margin-top: 12vh;
            padding: 2rem;
            min-height: calc(100vh - 12vh);
        }

        .report-container {
            max-width: 1400px;
            margin: 0 auto;
        }

        .page-header {
            margin-bottom: 2rem;
        }

        .page-header h1 {
            color: var(--dark-color);
            font-weight: 700;
            font-size: 2.2rem;
            margin-bottom: 1rem;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .period-badge {
            display: inline-block;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            padding: 10px 20px;
            border-radius: 50px;
            font-weight: 600;
            margin-bottom: 1rem;
            box-shadow: var(--box-shadow-light);
        }

        /* Period Filter Form */
        .period-filter {
            background: white;
            border-radius: var(--border-radius);
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: var(--box-shadow);
        }

        .period-filter h3 {
            color: var(--dark-color);
            font-size: 1.3rem;
            margin-bottom: 1.5rem;
            font-weight: 600;
        }

        .filter-form {
            display: flex;
            flex-wrap: wrap;
            gap: 1.5rem;
            align-items: flex-end;
        }

        .filter-group {
            flex: 1;
            min-width: 200px;
        }

        .filter-group label {
            display: block;
            margin-bottom: 8px;
            color: var(--dark-color);
            font-weight: 600;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .filter-control {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid var(--light-gray);
            border-radius: 12px;
            font-size: 15px;
            transition: var(--transition);
        }

        .filter-control:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.1);
        }

        .filter-btn {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            border: none;
            padding: 12px 30px;
            font-size: 15px;
            font-weight: 600;
            border-radius: 12px;
            cursor: pointer;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }

        .filter-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(67, 97, 238, 0.3);
        }

        /* Month Navigation */
        .month-nav {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
            flex-wrap: wrap;
        }

        .month-btn {
            padding: 10px 20px;
            background: white;
            border: 2px solid var(--light-gray);
            border-radius: 50px;
            color: var(--dark-color);
            font-weight: 600;
            text-decoration: none;
            transition: var(--transition);
        }

        .month-btn:hover {
            border-color: var(--primary-color);
            color: var(--primary-color);
        }

        .month-btn.active {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            border-color: transparent;
            color: white;
        }

        .report-table {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            overflow: hidden;
            margin-bottom: 2rem;
        }

        .report-table table {
            width: 100%;
            border-collapse: collapse;
        }

        .report-table th {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            font-weight: 600;
            padding: 15px;
            text-align: center;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .report-table td {
            padding: 12px 15px;
            border-bottom: 1px solid var(--light-gray);
            text-align: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .report-table tr:last-child td {
            border-bottom: none;
        }

        .report-table tbody tr:hover {
            background-color: var(--light-color);
        }

        .month-header {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            color: white;
            font-weight: 700;
            font-size: 1.1rem;
        }

        .month-header td {
            padding: 15px;
            text-align: left;
        }

        .evaluator-name {
            font-weight: 700;
            color: var(--dark-color);
            text-align: left !important;
            font-size: 16px;
            vertical-align: middle;
            background-color: #f8f9fa;
        }

        /* CRITICAL FIX: Bold black horizontal line across ALL columns */
        .last-row-of-evaluator td {
            border-bottom: 3px solid #000 !important;
        }
        
        /* This ensures the evaluator cell also gets the border */
        .last-row-of-evaluator td.evaluator-name {
            border-bottom: 3px solid #000 !important;
        }

        .positive-badge {
            color: var(--success-color);
            font-weight: 600;
        }

        .negative-badge {
            color: var(--danger-color);
            font-weight: 600;
        }

        .total-row {
            background-color: var(--light-color);
            font-weight: 700;
        }

        .summary-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-top: 2rem;
        }

        .summary-card {
            background: white;
            border-radius: var(--border-radius);
            padding: 1.5rem;
            box-shadow: var(--box-shadow);
            text-align: center;
        }

        .summary-card h3 {
            color: var(--dark-color);
            font-size: 1.1rem;
            margin-bottom: 1rem;
            font-weight: 600;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .summary-card .number {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--primary-color);
        }
.report-table td.evaluator-name {
    border-bottom: 3px solid black;
}
        .back-button {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 12px 25px;
            background: linear-gradient(135deg, #6b7280 0%, #4b5563 100%);
            color: white;
            text-decoration: none;
            border-radius: 12px;
            font-weight: 600;
            margin-bottom: 2rem;
            transition: var(--transition);
        }

        .back-button:hover {
            transform: translateX(-5px);
            color: white;
        }

        .no-data {
            padding: 3rem;
            text-align: center;
            color: var(--gray-color);
        }

        .export-btn {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 12px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
            transition: var(--transition);
            margin-left: auto;
        }

        .export-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(16, 185, 129, 0.3);
        }

        .table-responsive {
            overflow-x: auto;
        }

        .stats-highlight {
            font-size: 0.9rem;
            color: var(--gray-color);
            margin-top: 0.5rem;
        }

        /* Combined Rate Bar Styles - Clean bar without icons */
        .combined-rate-bar {
            width: 100%;
            min-width: 200px;
        }
        
        .rate-bar-track {
            width: 100%;
            height: 30px;
            background-color: var(--light-gray);
            border-radius: 6px;
            overflow: hidden;
            display: flex;
            box-shadow: inset 0 1px 2px rgba(0,0,0,0.1);
        }
        
        .positive-segment {
            background: linear-gradient(135deg, var(--success-color) 0%, #22c55e 100%);
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 12px;
            font-weight: 700;
            transition: width 0.3s ease;
        }
        
        .negative-segment {
            background: linear-gradient(135deg, var(--danger-color) 0%, #dc2626 100%);
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 12px;
            font-weight: 700;
            transition: width 0.3s ease;
        }
        
        .rate-labels {
            display: flex;
            justify-content: space-between;
            margin-top: 5px;
            font-size: 11px;
            font-weight: 600;
        }
        
        .positive-label {
            color: var(--success-color);
        }
        
        .negative-label {
            color: var(--danger-color);
        }

        @media (max-width: 768px) {
            .report-wrapper {
                padding: 1rem;
            }
            
            .filter-form {
                flex-direction: column;
            }
            
            .filter-group {
                width: 100%;
            }
            
            .combined-rate-bar {
                min-width: 150px;
            }
            
            .rate-bar-track {
                height: 25px;
            }
            
            .positive-segment, .negative-segment {
                font-size: 10px;
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
                        <a href="javascript:void(0)" class="nav_link disabled">
                            <i class='bx bx-bar-chart-alt-2 nav_icon'></i> 
                            <span class="nav_name">Evaluate by Individual <small class="text-danger">(Frozen)</small></span>
                        </a>
                        <a href="javascript:void(0)" class="nav_link disabled">
                            <i class='bx bx-grid-alt nav_icon'></i> 
                            <span class="nav_name">Evaluate by Warehouse <small class="text-danger">(Frozen)</small></span>
                        </a>
                    <?php else: ?>
                        <a href="./namely_evaluation.php" class="nav_link"> <i class='bx bx-bar-chart-alt-2 nav_icon'></i> <span class="nav_name">Evaluate by Individual</span> </a>
                        <a href="./Warehouses.php" class="nav_link"> <i class='bx bx-grid-alt nav_icon'></i> <span class="nav_name">Evaluate by Warehouse</span> </a>
                    <?php endif; ?>
                    
                    <a href="./periodRatings.php" class="nav_link"> <i class='bx bxs-star-half'></i> <span class="nav_name">Results & Grading</span> </a>
                   
                </div>
            </div>
            <div>
                <?php if ($_SESSION['isAdmin']): ?>
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

    <div class="report-wrapper">
        <div class="report-container">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; margin-bottom: 1rem;">
                <a href="./index.php" class="back-button">
                    <i class="fas fa-arrow-left"></i> Back to Results
                </a>
                
                <?php if (!empty($evaluator_data)): ?>
                <button class="export-btn" onclick="exportToExcel()">
                    <i class="fas fa-file-excel"></i> Export to Excel
                </button>
                <?php endif; ?>
            </div>
            
            <div class="page-header">
                <h1><i class="fas fa-chart-bar me-3" style="color: var(--primary-color);"></i>Evaluator Performance Report</h1>
                <div class="period-badge">
                    <i class="fas fa-calendar-alt me-2"></i>
                    Period: <?php echo $start_period; ?> to <?php echo $end_period; ?>
                </div>
            </div>

            <!-- Period Filter -->
            <div class="period-filter">
                <h3><i class="fas fa-filter me-2"></i>Filter by Period</h3>
                <form method="POST" class="filter-form">
                    <div class="filter-group">
                        <label><i class="fas fa-calendar-day me-2"></i>Start Period (YYYY-MM)</label>
                        <input type="month" name="sPeriod" class="filter-control" value="<?php echo $start_period; ?>" required>
                    </div>
                    <div class="filter-group">
                        <label><i class="fas fa-calendar-week me-2"></i>End Period (YYYY-MM)</label>
                        <input type="month" name="ePeriod" class="filter-control" value="<?php echo $end_period; ?>" required>
                    </div>
                    <div class="filter-group">
                        <button type="submit" name="submitPeriod" class="filter-btn">
                            <i class="fas fa-search"></i> Generate Report
                        </button>
                    </div>
                </form>
            </div>

            <!-- Month Navigation -->
            <?php if (!empty($monthly_data) && count($monthly_data) > 1): ?>
            <div class="month-nav">
                <a href="?sp=<?php echo $start_period; ?>&ep=<?php echo $end_period; ?>" class="month-btn <?php echo !isset($_GET['month']) ? 'active' : ''; ?>">
                    <i class="fas fa-chart-pie me-2"></i>Summary (All Months)
                </a>
                <?php foreach ($monthly_data as $month => $data): ?>
                <a href="?sp=<?php echo $start_period; ?>&ep=<?php echo $end_period; ?>&month=<?php echo $month; ?>" class="month-btn <?php echo (isset($_GET['month']) && $_GET['month'] == $month) ? 'active' : ''; ?>">
                    <i class="fas fa-calendar-alt me-2"></i><?php echo date('F Y', strtotime($month . '-01')); ?>
                </a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <?php if (empty($evaluator_data)): ?>
                <div class="no-data">
                    <i class="fas fa-chart-pie" style="font-size: 4rem; color: var(--light-gray); margin-bottom: 1rem;"></i>
                    <h3>No Data Available</h3>
                    <p>There are no records found for the selected period: <?php echo $start_period; ?> to <?php echo $end_period; ?></p>
                    <p>Please try a different period or check if data exists in the records table.</p>
                </div>
            <?php else: ?>
                <!-- Main Report Table -->
                <div class="report-table">
                    <div class="table-responsive">
                        <table id="reportTable">
                            <thead>
                                <tr>
                                    <th>Evaluator</th>
                                    <th>Category</th>
                                    <th>Total Incidents</th>
                                    <th>Positive</th>
                                    <th>Negative</th>
                                    <th>Positive / Negative Rate</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (isset($_GET['month']) && isset($monthly_data[$_GET['month']])): ?>
                                    <!-- Show specific month data -->
                                    <?php 
                                    $month = $_GET['month'];
                                    $month_data = $monthly_data[$month];
                                    ?>
                                    <tr class="month-header">
                                        <td colspan="6">
                                            <i class="fas fa-calendar-alt me-2"></i>
                                            <?php echo date('F Y', strtotime($month . '-01')); ?> - Detailed Report
                                        </td>
                                    </tr>
                                    <?php 
                                    $evaluator_index = 0;
                                    $total_evaluators = count($month_data['evaluators']);
                                    foreach ($month_data['evaluators'] as $evaluator_id => $evaluator): 
                                        $evaluator_index++;
                                        $first_row = true;
                                        $row_count = count($evaluator['types']);
                                        $row_num = 0;
                                        $evaluator_total_positive = 0;
                                        $evaluator_total_negative = 0;
                                        $evaluator_total = 0;
                                        
                                        foreach ($evaluator['types'] as $type => $counts) {
                                            $evaluator_total_positive += $counts['positive'];
                                            $evaluator_total_negative += $counts['negative'];
                                            $evaluator_total += $counts['total'];
                                        }
                                        $evaluator_positive_rate = $evaluator_total > 0 ? round(($evaluator_total_positive / $evaluator_total) * 100, 1) : 0;
                                        $evaluator_negative_rate = $evaluator_total > 0 ? round(($evaluator_total_negative / $evaluator_total) * 100, 1) : 0;
                                        
                                        foreach ($evaluator['types'] as $type => $counts):
                                            $row_num++;
                                            $is_last_row = ($row_num == $row_count);
                                            $type_positive_rate = $counts['total'] > 0 ? round(($counts['positive'] / $counts['total']) * 100, 1) : 0;
                                            $type_negative_rate = $counts['total'] > 0 ? round(($counts['negative'] / $counts['total']) * 100, 1) : 0;
                                            ?>
                                            <tr class="<?php echo $is_last_row ? 'last-row-of-evaluator' : ''; ?>">
                                                <?php if ($first_row): ?>
                                                    <td class="evaluator-name" rowspan="<?php echo $row_count; ?>">
                                                        <i class="fas fa-user-circle me-2" style="color: var(--primary-color);"></i>
                                                        <?php echo htmlspecialchars($evaluator['name']); ?>
                                                        <div class="stats-highlight">
                                                            <small>Total: <?php echo $evaluator_total; ?> | 
                                                            <span class="positive-badge">P: <?php echo $evaluator_positive_rate; ?>%</span> | 
                                                            <span class="negative-badge">N: <?php echo $evaluator_negative_rate; ?>%</span></small>
                                                        </div>
                                                    </td>
                                                    <?php $first_row = false; ?>
                                                <?php endif; ?>
                                                <td><strong><?php echo htmlspecialchars($type ?: 'N/A'); ?></strong></td>
                                                <td><?php echo $counts['total']; ?></td>
                                                <td class="positive-badge"><?php echo $counts['positive']; ?></td>
                                                <td class="negative-badge"><?php echo $counts['negative']; ?></td>
                                                <td>
                                                    <div class="combined-rate-bar">
                                                        <div class="rate-bar-track">
                                                            <div class="positive-segment" style="width: <?php echo $type_positive_rate; ?>%;">
                                                                <?php if($type_positive_rate > 15): ?><?php echo $type_positive_rate; ?>%<?php endif; ?>
                                                            </div>
                                                            <div class="negative-segment" style="width: <?php echo $type_negative_rate; ?>%;">
                                                                <?php if($type_negative_rate > 15): ?><?php echo $type_negative_rate; ?>%<?php endif; ?>
                                                            </div>
                                                        </div>
                                                        <div class="rate-labels">
                                                            <span class="positive-label">Positive <?php echo $type_positive_rate; ?>%</span>
                                                            <span class="negative-label">Negative <?php echo $type_negative_rate; ?>%</span>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endforeach; ?>
                                    
                                    <!-- Monthly Summary -->
                                    <tr class="total-row" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); color: white;">
                                        <td colspan="2" style="text-align: right;"><strong>Monthly Total:</strong></td>
                                        <td><strong><?php echo $month_data['total_incidents']; ?></strong></td>
                                        <td><strong><?php echo $month_data['total_positive']; ?></strong></td>
                                        <td><strong><?php echo $month_data['total_negative']; ?></strong></td>
                                        <td>
                                            <?php 
                                            $month_positive_rate = $month_data['total_incidents'] > 0 ? round(($month_data['total_positive'] / $month_data['total_incidents']) * 100, 1) : 0;
                                            $month_negative_rate = $month_data['total_incidents'] > 0 ? round(($month_data['total_negative'] / $month_data['total_incidents']) * 100, 1) : 0;
                                            ?>
                                            <div class="combined-rate-bar">
                                                <div class="rate-bar-track">
                                                    <div class="positive-segment" style="width: <?php echo $month_positive_rate; ?>%;">
                                                        <?php if($month_positive_rate > 15): ?><?php echo $month_positive_rate; ?>%<?php endif; ?>
                                                    </div>
                                                    <div class="negative-segment" style="width: <?php echo $month_negative_rate; ?>%;">
                                                        <?php if($month_negative_rate > 15): ?><?php echo $month_negative_rate; ?>%<?php endif; ?>
                                                    </div>
                                                </div>
                                                <div class="rate-labels">
                                                    <span class="positive-label">Positive <?php echo $month_positive_rate; ?>%</span>
                                                    <span class="negative-label">Negative <?php echo $month_negative_rate; ?>%</span>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                    
                                <?php else: ?>
                                    <!-- Show summary of all months -->
                                    <?php 
                                    $evaluator_index = 0;
                                    $total_evaluators = count($evaluator_data);
                                    foreach ($evaluator_data as $evaluator_id => $evaluator): 
                                        $evaluator_index++;
                                        $first_row = true;
                                        $row_count = count($evaluator['types']);
                                        $row_num = 0;
                                        $evaluator_positive_rate = $evaluator['total_incidents'] > 0 ? round(($evaluator['total_positive'] / $evaluator['total_incidents']) * 100, 1) : 0;
                                        $evaluator_negative_rate = $evaluator['total_incidents'] > 0 ? round(($evaluator['total_negative'] / $evaluator['total_incidents']) * 100, 1) : 0;
                                        
                                        foreach ($evaluator['types'] as $type => $counts):
                                            $row_num++;
                                            $is_last_row = ($row_num == $row_count);
                                            $type_positive_rate = $counts['total'] > 0 ? round(($counts['positive'] / $counts['total']) * 100, 1) : 0;
                                            $type_negative_rate = $counts['total'] > 0 ? round(($counts['negative'] / $counts['total']) * 100, 1) : 0;
                                            ?>
                                            <tr class="<?php echo $is_last_row ? 'last-row-of-evaluator' : ''; ?>">
                                                <?php if ($first_row): ?>
                                                    <td class="evaluator-name" rowspan="<?php echo $row_count; ?>">
                                                        <i class="fas fa-user-circle me-2" style="color: var(--primary-color);"></i>
                                                        <?php echo htmlspecialchars($evaluator['name']); ?>
                                                        <div class="stats-highlight">
                                                            <small>Total: <?php echo $evaluator['total_incidents']; ?> | 
                                                            <span class="positive-badge">P: <?php echo $evaluator_positive_rate; ?>%</span> | 
                                                            <span class="negative-badge">N: <?php echo $evaluator_negative_rate; ?>%</span></small>
                                                        </div>
                                                    </td>
                                                    <?php $first_row = false; ?>
                                                <?php endif; ?>
                                                <td><strong><?php echo htmlspecialchars($type ?: 'N/A'); ?></strong></td>
                                                <td><?php echo $counts['total']; ?></td>
                                                <td class="positive-badge"><?php echo $counts['positive']; ?></td>
                                                <td class="negative-badge"><?php echo $counts['negative']; ?></td>
                                                <td>
                                                    <div class="combined-rate-bar">
                                                        <div class="rate-bar-track">
                                                            <div class="positive-segment" style="width: <?php echo $type_positive_rate; ?>%;">
                                                                <?php if($type_positive_rate > 15): ?><?php echo $type_positive_rate; ?>%<?php endif; ?>
                                                            </div>
                                                            <div class="negative-segment" style="width: <?php echo $type_negative_rate; ?>%;">
                                                                <?php if($type_negative_rate > 15): ?><?php echo $type_negative_rate; ?>%<?php endif; ?>
                                                            </div>
                                                        </div>
                                                        <div class="rate-labels">
                                                            <span class="positive-label">Positive <?php echo $type_positive_rate; ?>%</span>
                                                            <span class="negative-label">Negative <?php echo $type_negative_rate; ?>%</span>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Summary Cards -->
                <div class="summary-cards">
                    <div class="summary-card">
                        <h3><i class="fas fa-users me-2" style="color: var(--primary-color);"></i>Active Evaluators</h3>
                        <div class="number"><?php echo count($evaluator_data); ?></div>
                    </div>
                    
                    <div class="summary-card">
                        <h3><i class="fas fa-chart-line me-2" style="color: var(--primary-color);"></i>Total Records</h3>
                        <?php 
                        $grand_total = array_sum(array_column($evaluator_data, 'total_incidents'));
                        ?>
                        <div class="number"><?php echo $grand_total; ?></div>
                    </div>
                    
                    <div class="summary-card">
                        <h3><i class="fas fa-check-circle me-2" style="color: var(--success-color);"></i>Total Positive</h3>
                        <?php 
                        $grand_positive = array_sum(array_column($evaluator_data, 'total_positive'));
                        ?>
                        <div class="number" style="color: var(--success-color);"><?php echo $grand_positive; ?></div>
                    </div>
                    
                    <div class="summary-card">
                        <h3><i class="fas fa-times-circle me-2" style="color: var(--danger-color);"></i>Total Negative</h3>
                        <?php 
                        $grand_negative = array_sum(array_column($evaluator_data, 'total_negative'));
                        ?>
                        <div class="number" style="color: var(--danger-color);"><?php echo $grand_negative; ?></div>
                    </div>
                </div>

                <!-- Monthly Breakdown -->
                <?php if (!empty($monthly_data) && !isset($_GET['month'])): ?>
                <div style="margin-top: 3rem;">
                    <h3 style="color: var(--dark-color); margin-bottom: 1.5rem;">
                        <i class="fas fa-calendar-alt me-2" style="color: var(--primary-color);"></i>
                        Monthly Performance Overview
                    </h3>
                    <div class="report-table">
                        <div class="table-responsive">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Month</th>
                                        <th>Total Records</th>
                                        <th>Positive</th>
                                        <th>Negative</th>
                                        <th>Positive / Negative Rate</th>
                                        <th>Active Evaluators</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($monthly_data as $month => $data): ?>
                                    <?php 
                                    $month_positive_rate = $data['total_incidents'] > 0 ? round(($data['total_positive'] / $data['total_incidents']) * 100, 1) : 0;
                                    $month_negative_rate = $data['total_incidents'] > 0 ? round(($data['total_negative'] / $data['total_incidents']) * 100, 1) : 0;
                                    ?>
                                    <tr>
                                        <td><strong><?php echo date('F Y', strtotime($month . '-01')); ?></strong></td>
                                        <td><?php echo $data['total_incidents']; ?></td>
                                        <td class="positive-badge"><?php echo $data['total_positive']; ?></td>
                                        <td class="negative-badge"><?php echo $data['total_negative']; ?></td>
                                        <td>
                                            <div class="combined-rate-bar">
                                                <div class="rate-bar-track">
                                                    <div class="positive-segment" style="width: <?php echo $month_positive_rate; ?>%;">
                                                        <?php if($month_positive_rate > 15): ?><?php echo $month_positive_rate; ?>%<?php endif; ?>
                                                    </div>
                                                    <div class="negative-segment" style="width: <?php echo $month_negative_rate; ?>%;">
                                                        <?php if($month_negative_rate > 15): ?><?php echo $month_negative_rate; ?>%<?php endif; ?>
                                                    </div>
                                                </div>
                                                <div class="rate-labels">
                                                    <span class="positive-label">Positive <?php echo $month_positive_rate; ?>%</span>
                                                    <span class="negative-label">Negative <?php echo $month_negative_rate; ?>%</span>
                                                </div>
                                            </div>
                                        </td>
                                        <td><?php echo count($data['evaluators']); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function exportToExcel() {
            var table = document.getElementById('reportTable');
            if (!table) {
                alert('No data to export');
                return;
            }
            
            // Clone the table to remove progress bars for export
            var exportTable = table.cloneNode(true);
            exportTable.querySelectorAll('.rate-bar-track').forEach(function(track) {
                var positiveLabel = track.parentElement.querySelector('.positive-label') ? track.parentElement.querySelector('.positive-label').innerText : '0%';
                var negativeLabel = track.parentElement.querySelector('.negative-label') ? track.parentElement.querySelector('.negative-label').innerText : '0%';
                track.parentElement.innerHTML = positiveLabel + ' | ' + negativeLabel;
            });
            
            var html = exportTable.outerHTML;
            var blob = new Blob([html], { type: 'application/vnd.ms-excel' });
            var link = document.createElement('a');
            link.href = URL.createObjectURL(blob);
            link.download = 'evaluator_report_<?php echo $start_period; ?>_to_<?php echo $end_period; ?>.xls';
            link.click();
        }
    </script>
</body>

</html>