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

    // Get year from URL parameter or use current year
    $selected_year = isset($_GET['year']) ? intval($_GET['year']) : $current_year;

    if (isset($_POST['submit'])) {
        $smName = mysqli_real_escape_string($conn, $_POST['smName']);
        $left5 = $_POST['5left'] !== '' ? $_POST['5left'] : NULL;
        $right5 = $_POST['5right'] !== '' ? $_POST['5right'] : NULL;
        $left4 = $_POST['4left'] !== '' ? $_POST['4left'] : NULL;
        $right4 = $_POST['4right'] !== '' ? $_POST['4right'] : NULL;
        $left3 = $_POST['3left'] !== '' ? $_POST['3left'] : NULL;
        $right3 = $_POST['3right'] !== '' ? $_POST['3right'] : NULL;
        $left2 = $_POST['2left'] !== '' ? $_POST['2left'] : NULL;
        $right2 = $_POST['2right'] !== '' ? $_POST['2right'] : NULL;
        $left1 = $_POST['1left'] !== '' ? $_POST['1left'] : NULL;
        $right1 = $_POST['1right'] !== '' ? $_POST['1right'] : NULL;
        $left0 = $_POST['0left'] !== '' ? $_POST['0left'] : NULL;
        $right0 = $_POST['0right'] !== '' ? $_POST['0right'] : NULL;
        $year = $_POST['year'];

        // Simplified INSERT query with all fields
        $sql = "INSERT INTO scoring_method(
                    sm_name, 
                    5_left, 5_right, 
                    4_left, 4_right, 
                    3_left, 3_right, 
                    2_left, 2_right, 
                    1_left, 1_right, 
                    0_left, 0_right, 
                    `year`
                ) VALUES (
                    '$smName',
                    " . ($left5 !== NULL ? "'$left5'" : "NULL") . ",
                    " . ($right5 !== NULL ? "'$right5'" : "NULL") . ",
                    " . ($left4 !== NULL ? "'$left4'" : "NULL") . ",
                    " . ($right4 !== NULL ? "'$right4'" : "NULL") . ",
                    " . ($left3 !== NULL ? "'$left3'" : "NULL") . ",
                    " . ($right3 !== NULL ? "'$right3'" : "NULL") . ",
                    " . ($left2 !== NULL ? "'$left2'" : "NULL") . ",
                    " . ($right2 !== NULL ? "'$right2'" : "NULL") . ",
                    " . ($left1 !== NULL ? "'$left1'" : "NULL") . ",
                    " . ($right1 !== NULL ? "'$right1'" : "NULL") . ",
                    " . ($left0 !== NULL ? "'$left0'" : "NULL") . ",
                    " . ($right0 !== NULL ? "'$right0'" : "NULL") . ",
                    '$year'
                )";

        $result = $conn->query($sql);

        if ($result == TRUE) {
            echo "<script>
            alert('Scoring Method Added Successfully for $year!');
            window.location.href='ScoringMethods.php?year=$year';
            </script>";
        } else {
            echo "Error: " . $sql . "<br>" . $conn->error;
        }
    }
?>

<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8" />
    <?php if (isset($_GET["id"])) { ?>
        <title>Update Marking Scheme - <?php echo $selected_year; ?></title>
    <?php } else { ?>
        <title>Add Marking Scheme for <?php echo $selected_year; ?></title>
    <?php } ?>
    <link rel="icon" type="image/x-icon" href="./img/jb.png">
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />

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

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html, body {
            height: 100%;
            overflow: hidden;
        }

        .content {
            height: 100vh;
            overflow-y: auto;
        }

        .modern-form-container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 20px 30px;
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.08);
            margin-top: 3vh;
            margin-bottom: 3vh;
            max-height: calc(100vh - 120px);
            overflow-y: auto;
        }

        /* Hide scrollbar for Chrome, Safari and Opera */
        .modern-form-container::-webkit-scrollbar {
            display: none;
        }

        /* Hide scrollbar for IE, Edge and Firefox */
        .modern-form-container {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }

        .form-header-section {
            text-align: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid #eef2ff;
            position: relative;
        }

        .form-header-section h1 {
            color: var(--dark-color);
            font-weight: 700;
            font-size: 1.8rem;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
        }

        .form-header-section h1 i {
            color: var(--primary-color);
            font-size: 2rem;
        }

        .form-header-section p {
            color: #64748b;
            font-size: 1rem;
            max-width: 500px;
            margin: 0 auto;
        }

        .year-indicator {
            display: inline-block;
            padding: 3px 12px;
            background: linear-gradient(135deg, var(--primary-color), #3a56d4);
            color: white;
            border-radius: 20px;
            font-size: 0.9rem;
            margin-left: 10px;
        }

        .year-badge-large {
            position: absolute;
            top: -10px;
            right: 20px;
            background: linear-gradient(135deg, #4361ee, #3a0ca3);
            color: white;
            padding: 5px 20px;
            border-radius: 30px;
            font-size: 1.2rem;
            font-weight: bold;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }

        .form-card {
            background: #f8fafc;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            border: 1px solid #e2e8f0;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            font-weight: 600;
            color: var(--dark-color);
            margin-bottom: 8px;
            display: block;
            font-size: 1rem;
        }

        .form-input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            background: white;
        }

        .form-input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.1);
        }

        .score-grid-form {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin-top: 15px;
        }

        @media (max-width: 768px) {
            .score-grid-form {
                grid-template-columns: 1fr;
            }
        }

        .score-card {
            background: white;
            border-radius: 12px;
            padding: 15px;
            border: 1px solid #e2e8f0;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            height: fit-content;
        }

        .score-card:hover {
            border-color: var(--primary-color);
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.05);
        }

        .score-card::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 4px;
            background: var(--primary-color);
        }

        .score-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 15px;
        }

        .score-badge {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 1.1rem;
            color: white;
            flex-shrink: 0;
        }

        .score-badge-5 { background: linear-gradient(135deg, #4cc9f0, #4361ee); }
        .score-badge-4 { background: linear-gradient(135deg, #4895ef, #3f37c9); }
        .score-badge-3 { background: linear-gradient(135deg, #4361ee, #3a0ca3); }
        .score-badge-2 { background: linear-gradient(135deg, #3a0ca3, #7209b7); }
        .score-badge-1 { background: linear-gradient(135deg, #7209b7, #f72585); }
        .score-badge-0 { background: linear-gradient(135deg, #f72585, #b5179e); }

        .score-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--dark-color);
            margin: 0;
        }

        .score-inputs {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .input-row {
            display: grid;
            grid-template-columns: 1fr auto 1fr;
            gap: 10px;
            align-items: center;
        }

        .input-group {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .input-label {
            font-size: 0.85rem;
            color: #64748b;
            font-weight: 500;
        }

        .input-field {
            padding: 10px 12px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 0.9rem;
            transition: all 0.3s ease;
            width: 100%;
        }

        .input-field:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.1);
        }

        .score-operator {
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--primary-color);
            text-align: center;
            padding: 0 5px;
        }

        .optional-badge {
            background: #f1f5f9;
            color: #64748b;
            padding: 3px 10px;
            border-radius: 15px;
            font-size: 0.75rem;
            font-weight: 500;
            display: inline-block;
            margin-left: 8px;
        }

        .form-actions {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-top: 25px;
            padding-top: 20px;
            border-top: 2px solid #eef2ff;
        }

        .submit-btn {
            background: linear-gradient(135deg, var(--primary-color), #3a56d4);
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
            min-width: 150px;
            justify-content: center;
        }

        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(67, 97, 238, 0.3);
        }

        .cancel-btn {
            background: #f1f5f9;
            color: #64748b;
            border: none;
            padding: 12px 30px;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
            min-width: 150px;
            justify-content: center;
        }

        .cancel-btn:hover {
            background: #e2e8f0;
            color: var(--dark-color);
            transform: translateY(-2px);
            text-decoration: none;
        }

        .info-box {
            background: #eef2ff;
            border-radius: 12px;
            padding: 15px;
            margin-bottom: 20px;
            border-left: 4px solid var(--primary-color);
        }

        .info-box h4 {
            color: var(--dark-color);
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 1rem;
        }

        .info-box p {
            color: #64748b;
            margin: 0;
            font-size: 0.9rem;
        }

        .compact-row {
            display: grid;
            grid-template-columns: 1fr;
            gap: 5px;
        }

        .compact-label {
            font-size: 0.8rem;
            color: #64748b;
        }

        .Admin .score-card::before {
            background: #4361ee;
        }

        .Evaluator .score-card::before {
            background: #4cc9f0;
        }

        .Guest .score-card::before {
            background: #94a3b8;
        }

        .height-100 {
            min-height: calc(100vh - 80px);
            display: flex;
            flex-direction: column;
        }

        @media (max-width: 992px) {
            .modern-form-container {
                margin-top: 2vh;
                margin-bottom: 2vh;
                padding: 15px 20px;
                max-height: calc(100vh - 100px);
            }
            
            .form-header-section h1 {
                font-size: 1.6rem;
            }
        }

        @media (max-width: 576px) {
            .modern-form-container {
                padding: 12px 15px;
                margin: 1vh auto;
                border-radius: 15px;
            }
            
            .form-actions {
                flex-direction: column;
            }
            
            .submit-btn, .cancel-btn {
                width: 100%;
            }
            
            .score-grid-form {
                gap: 10px;
            }
        }
    </style>

    <script type="text/javascript">
        function validate() {
            var letters = /^[A-Z./:;!`"|<>_-?+@#%^&*~, a-z0-9]+$/;
            if (!document.addForm.smName.value.match(letters)) {
                alert("Containing Invalid Characters in Marking Scheme");
                return false;
            }
            
            const fields = [
                '5left', '5right', '4left', '4right', 
                '3left', '3right', '2left', '2right', 
                '1left', '1right', '0left', '0right'
            ];
            
            for (let i = 0; i < fields.length; i++) {
                const field = document.forms["addForm"][fields[i]];
                if (field && field.value && isNaN(field.value)) {
                    alert(`Containing Invalid Non-Numeric Character(s) in ${fields[i]}`);
                    return false;
                }
            }

            return true;
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
        <!-- Alert can be added here if needed -->
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

    <div class="modern-form-container">
        <?php if (isset($_GET["id"])) {
            $id = mysqli_real_escape_string($conn, $_GET["id"]);
            $sql = "SELECT * FROM scoring_method WHERE sm_id = '$id'";
            $result = $conn->query($sql);
            $row = $result->fetch_assoc();
            $scheme_year = $row['year'];
        ?>
            <form name="addForm" onsubmit="return validate()" enctype="multipart/form-data" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST">
                <div class="form-header-section">
                    <h1><i class='bx bx-tachometer'></i> Update Marking Scheme <span class="year-indicator"><?php echo $scheme_year; ?></span></h1>
                    <p>Modify the scoring criteria and thresholds for <?php echo $scheme_year; ?></p>
                </div>

                <div class="info-box">
                    <h4><i class='bx bx-info-circle'></i> Instructions</h4>
                    <p>This scheme is for year <?php echo $scheme_year; ?>. Changes will only affect <?php echo $scheme_year; ?> data.</p>
                </div>

                <div class="form-card">
                    <div class="form-group">
                        <label class="form-label">Scheme Name</label>
                        <input type="hidden" name="smId" value="<?php echo $row['sm_id']; ?>">
                        <input type="hidden" name="year" value="<?php echo $scheme_year; ?>">
                        <input type="text" name="smName" class="form-input" value="<?php echo htmlspecialchars($row['sm_name']); ?>" required placeholder="Enter scheme name">
                    </div>
                </div>

                <div class="form-card">
                    <h3 class="form-label" style="margin-bottom: 20px;">Score Threshold Configuration for <?php echo $scheme_year; ?></h3>
                    
                    <div class="score-grid-form">
                        <?php for ($i = 5; $i >= 0; $i--): ?>
                            <div class="score-card">
                                <div class="score-header">
                                    <div class="score-badge score-badge-<?php echo $i; ?>">
                                        <?php echo $i; ?>
                                    </div>
                                    <h3 class="score-title">Score <?php echo $i; ?></h3>
                                </div>
                                
                                <div class="score-inputs">
                                    <div class="input-row">
                                        <div class="input-group">
                                            <div class="compact-label">Left Value</div>
                                            <input type="text" name="<?php echo $i; ?>left" class="input-field" 
                                                   value="<?php echo isset($row[$i.'_left']) && $row[$i.'_left'] !== NULL ? htmlspecialchars($row[$i.'_left']) : ''; ?>" 
                                                   <?php echo ($i > 0 && $i < 5) ? 'required' : ''; ?>
                                                   placeholder="Value">
                                        </div>
                                        
                                        <div class="score-operator">>=</div>
                                        
                                        <div class="input-group">
                                            <div class="compact-label">Right Value</div>
                                            <input type="text" name="<?php echo $i; ?>right" class="input-field" 
                                                   value="<?php echo isset($row[$i.'_right']) && $row[$i.'_right'] !== NULL ? htmlspecialchars($row[$i.'_right']) : ''; ?>" 
                                                   <?php echo ($i > 0 && $i < 5) ? 'required' : ''; ?>
                                                   placeholder="Value">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endfor; ?>
                    </div>
                </div>

                <div class="form-actions">
                    <a href="ScoringMethods.php?year=<?php echo $scheme_year; ?>" class="cancel-btn">
                        <i class='bx bx-x'></i> Cancel
                    </a>
                    <button type="submit" name="update" class="submit-btn">
                        <i class='bx bx-check'></i> Update Scheme for <?php echo $scheme_year; ?>
                    </button>
                </div>
            </form>
        <?php } else { ?>
            <form name="addForm" onsubmit="return validate()" enctype="multipart/form-data" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST">
                <div class="form-header-section">
                    <h1><i class='bx bx-tachometer'></i> Add New Marking Scheme <span class="year-indicator"><?php echo $selected_year; ?></span></h1>
                    <p>Create a new scoring criteria and thresholds for year <?php echo $selected_year; ?></p>
                </div>

                <div class="info-box">
                    <h4><i class='bx bx-info-circle'></i> Instructions</h4>
                    <p>This scheme will be for year <?php echo $selected_year; ?>. It will not affect previous years' data.</p>
                </div>

                <div class="form-card">
                    <div class="form-group">
                        <label class="form-label">Scheme Name</label>
                        <input type="hidden" name="year" value="<?php echo $selected_year; ?>">
                        <input type="text" name="smName" class="form-input" required placeholder="Enter scheme name (e.g., 'Performance Scoring <?php echo $selected_year; ?>')">
                    </div>
                </div>

                <div class="form-card">
                    <h3 class="form-label" style="margin-bottom: 20px;">Score Threshold Configuration for <?php echo $selected_year; ?></h3>
                    
                    <div class="score-grid-form">
                        <?php for ($i = 5; $i >= 0; $i--): ?>
                            <div class="score-card">
                                <div class="score-header">
                                    <div class="score-badge score-badge-<?php echo $i; ?>">
                                        <?php echo $i; ?>
                                    </div>
                                    <h3 class="score-title">Score <?php echo $i; ?></h3>
                                    <?php if ($i == 5 || $i == 0): ?>
                                        <span class="optional-badge">Optional</span>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="score-inputs">
                                    <div class="input-row">
                                        <div class="input-group">
                                            <div class="compact-label">Left Value</div>
                                            <input type="text" name="<?php echo $i; ?>left" class="input-field" 
                                                   <?php echo ($i > 0 && $i < 5) ? 'required' : ''; ?>
                                                   placeholder="Value">
                                        </div>
                                        
                                        <div class="score-operator">>=</div>
                                        
                                        <div class="input-group">
                                            <div class="compact-label">Right Value</div>
                                            <input type="text" name="<?php echo $i; ?>right" class="input-field" 
                                                   <?php echo ($i > 0 && $i < 5) ? 'required' : ''; ?>
                                                   placeholder="Value">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endfor; ?>
                    </div>
                </div>

                <div class="form-actions">
                    <a href="ScoringMethods.php?year=<?php echo $selected_year; ?>" class="cancel-btn">
                        <i class='bx bx-x'></i> Cancel
                    </a>
                    <button type="submit" name="submit" class="submit-btn">
                        <i class='bx bx-plus'></i> Create Scheme for <?php echo $selected_year; ?>
                    </button>
                </div>
            </form>
        <?php } ?>
    </div>

    <?php 
    if (isset($_POST['update'])) {
        $smId = mysqli_real_escape_string($conn, $_POST['smId']);
        $smName = mysqli_real_escape_string($conn, $_POST['smName']);
        $left5 = $_POST['5left'] !== '' ? $_POST['5left'] : NULL;
        $right5 = $_POST['5right'] !== '' ? $_POST['5right'] : NULL;
        $left4 = $_POST['4left'] !== '' ? $_POST['4left'] : NULL;
        $right4 = $_POST['4right'] !== '' ? $_POST['4right'] : NULL;
        $left3 = $_POST['3left'] !== '' ? $_POST['3left'] : NULL;
        $right3 = $_POST['3right'] !== '' ? $_POST['3right'] : NULL;
        $left2 = $_POST['2left'] !== '' ? $_POST['2left'] : NULL;
        $right2 = $_POST['2right'] !== '' ? $_POST['2right'] : NULL;
        $left1 = $_POST['1left'] !== '' ? $_POST['1left'] : NULL;
        $right1 = $_POST['1right'] !== '' ? $_POST['1right'] : NULL;
        $left0 = $_POST['0left'] !== '' ? $_POST['0left'] : NULL;
        $right0 = $_POST['0right'] !== '' ? $_POST['0right'] : NULL;
        $year = $_POST['year'];

        // Simplified UPDATE query
        $sql = "UPDATE scoring_method SET
                    sm_name = '$smName',
                    5_left = " . ($left5 !== NULL ? "'$left5'" : "NULL") . ",
                    5_right = " . ($right5 !== NULL ? "'$right5'" : "NULL") . ",
                    4_left = " . ($left4 !== NULL ? "'$left4'" : "NULL") . ",
                    4_right = " . ($right4 !== NULL ? "'$right4'" : "NULL") . ",
                    3_left = " . ($left3 !== NULL ? "'$left3'" : "NULL") . ",
                    3_right = " . ($right3 !== NULL ? "'$right3'" : "NULL") . ",
                    2_left = " . ($left2 !== NULL ? "'$left2'" : "NULL") . ",
                    2_right = " . ($right2 !== NULL ? "'$right2'" : "NULL") . ",
                    1_left = " . ($left1 !== NULL ? "'$left1'" : "NULL") . ",
                    1_right = " . ($right1 !== NULL ? "'$right1'" : "NULL") . ",
                    0_left = " . ($left0 !== NULL ? "'$left0'" : "NULL") . ",
                    0_right = " . ($right0 !== NULL ? "'$right0'" : "NULL") . ",
                    `year` = '$year'
                WHERE sm_id = '$smId'";

        $result = $conn->query($sql);

        if ($result == TRUE) {
            echo "<script>
            alert('Scoring Method Updated Successfully for $year!');
            window.location.href='ScoringMethods.php?year=$year';
            </script>";
        } else {
            echo "Error: " . $sql . "<br>" . $conn->error;
        }
    }
    ?>

</body>

</html>

<?php } else {
    header("location: signin.php");
}
?>