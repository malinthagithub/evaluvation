<?php
include "config.php";
session_start();

if (!isset($_SESSION["user"])) {
    header("location: signin.php");
    exit();
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

// Handle adding new diary entry
if (isset($_POST['add_entry'])) {
    $entry_date = $_POST['entry_date'];
    $comment = mysqli_real_escape_string($conn, $_POST['comment']);
    $tick = isset($_POST['tick']) ? 1 : 0;
    $evaluator_id = $_SESSION["id"];
    $evaluator_name = $_SESSION["user"];
    
    $sql = "INSERT INTO diary_entries (entry_date, comment, tick, evaluator_id, evaluator_name, created_at) 
            VALUES ('$entry_date', '$comment', '$tick', '$evaluator_id', '$evaluator_name', NOW())";
    
    if (mysqli_query($conn, $sql)) {
        echo "<script>alert('Entry added successfully!'); window.location.href='records.php';</script>";
    } else {
        echo "<script>alert('Error adding entry: " . mysqli_error($conn) . "');</script>";
    }
}

// Handle editing entry
if (isset($_POST['edit_entry'])) {
    $entry_id = $_POST['entry_id'];
    $entry_date = $_POST['entry_date'];
    $comment = mysqli_real_escape_string($conn, $_POST['comment']);
    $tick = isset($_POST['tick']) ? 1 : 0;
    
    $sql = "UPDATE diary_entries SET entry_date='$entry_date', comment='$comment', tick='$tick' WHERE id='$entry_id'";
    
    if (mysqli_query($conn, $sql)) {
        echo "<script>alert('Entry updated successfully!'); window.location.href='records.php';</script>";
    } else {
        echo "<script>alert('Error updating entry: " . mysqli_error($conn) . "');</script>";
    }
}

// Handle deleting entry
if (isset($_GET['delete'])) {
    $entry_id = $_GET['delete'];
    $sql = "DELETE FROM diary_entries WHERE id='$entry_id'";
    
    if (mysqli_query($conn, $sql)) {
        echo "<script>alert('Entry deleted successfully!'); window.location.href='records.php';</script>";
    } else {
        echo "<script>alert('Error deleting entry: " . mysqli_error($conn) . "');</script>";
    }
}

// Get all diary entries
$sql = "SELECT * FROM diary_entries ORDER BY entry_date DESC, created_at DESC";
$result = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Records Diary</title>
    <link rel="icon" type="image/x-icon" href="./img/jb.png">

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta1/dist/css/bootstrap.min.css" rel="stylesheet" />
    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta1/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
    <!-- Boxicons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/boxicons@latest/css/boxicons.min.css" />
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <!-- Datepicker CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/css/bootstrap-datepicker.min.css">
    <!-- Datepicker JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/js/bootstrap-datepicker.min.js"></script>

    <link rel="stylesheet" href="./style.css" />
    <script src="./script.js"></script>
    
    <style>
        :root {
            --primary-color: #2563eb;
            --primary-light: #3b82f6;
            --secondary-color: #7c3aed;
            --success-color: #059669;
            --info-color: #0891b2;
            --warning-color: #d97706;
            --danger-color: #dc2626;
            --dark-color: #1e293b;
            --light-color: #f8fafc;
            --record-color: #f97316;
            
            --glass-bg: rgba(255, 255, 255, 0.95);
            --glass-border: rgba(255, 255, 255, 0.18);
            --glass-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.15);
            --transition: all 0.3s ease;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        
        /* Diary Container */
        .diary-container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
        }
        
        /* Diary Header */
        .diary-header {
            background: var(--glass-bg);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: var(--glass-shadow);
            border: 1px solid var(--glass-border);
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .diary-header::before {
            content: '📔';
            position: absolute;
            right: 20px;
            bottom: 10px;
            font-size: 80px;
            opacity: 0.1;
            transform: rotate(-10deg);
        }
        
        .diary-header h1 {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--dark-color);
            margin-bottom: 10px;
        }
        
        .diary-header p {
            color: #64748b;
            font-size: 1.1rem;
        }
        
        /* Add Entry Card */
        .add-entry-card {
            background: var(--glass-bg);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: var(--glass-shadow);
            border: 1px solid var(--glass-border);
        }
        
        .add-entry-card h3 {
            color: var(--dark-color);
            font-weight: 600;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .add-entry-card h3 i {
            color: var(--record-color);
            font-size: 28px;
        }
        
        /* Form Styles */
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-label {
            font-weight: 600;
            color: var(--dark-color);
            margin-bottom: 8px;
            display: block;
        }
        
        .form-control, .form-select {
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            padding: 12px 15px;
            transition: var(--transition);
            font-size: 14px;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--record-color);
            box-shadow: 0 0 0 3px rgba(249, 115, 22, 0.1);
            outline: none;
        }
        
        textarea.form-control {
            min-height: 100px;
            resize: vertical;
        }
        
        /* Tick Checkbox */
        .tick-checkbox {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 0;
        }
        
        .tick-checkbox input[type="checkbox"] {
            width: 20px;
            height: 20px;
            cursor: pointer;
            accent-color: var(--success-color);
        }
        
        .tick-checkbox label {
            font-weight: 600;
            color: var(--dark-color);
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .tick-checkbox label i {
            color: var(--success-color);
            font-size: 18px;
        }
        
        /* Submit Button */
        .btn-submit {
            background: linear-gradient(135deg, var(--record-color), #fb923c);
            color: white;
            border: none;
            border-radius: 12px;
            padding: 12px 30px;
            font-weight: 600;
            font-size: 16px;
            cursor: pointer;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }
        
        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(249, 115, 22, 0.3);
        }
        
        .btn-submit i {
            font-size: 18px;
        }
        
        /* Entries Container */
        .entries-container {
            display: grid;
            gap: 20px;
        }
        
        /* Entry Card */
        .entry-card {
            background: var(--glass-bg);
            backdrop-filter: blur(10px);
            border-radius: 16px;
            padding: 20px;
            box-shadow: var(--glass-shadow);
            border: 1px solid var(--glass-border);
            transition: var(--transition);
            position: relative;
            overflow: hidden;
        }
        
        .entry-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.2);
        }
        
        .entry-card.ticked {
            border-left: 5px solid var(--success-color);
        }
        
        .entry-card.unticked {
            border-left: 5px solid var(--danger-color);
        }
        
        /* Entry Header */
        .entry-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 2px dashed #e2e8f0;
        }
        
        .entry-date {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .entry-date i {
            font-size: 24px;
            color: var(--record-color);
        }
        
        .entry-date .date {
            font-weight: 700;
            font-size: 1.2rem;
            color: var(--dark-color);
        }
        
        .entry-status {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .status-badge {
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        
        .status-badge.ticked {
            background: rgba(5, 150, 105, 0.1);
            color: var(--success-color);
            border: 1px solid var(--success-color);
        }
        
        .status-badge.unticked {
            background: rgba(220, 38, 38, 0.1);
            color: var(--danger-color);
            border: 1px solid var(--danger-color);
        }
        
        /* Entry Content */
        .entry-content {
            margin-bottom: 15px;
        }
        
        .entry-comment {
            color: #334155;
            line-height: 1.6;
            font-size: 1rem;
            padding: 10px;
            background: rgba(0, 0, 0, 0.02);
            border-radius: 10px;
            white-space: pre-wrap;
        }
        
        /* Entry Footer */
        .entry-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 15px;
            border-top: 1px solid #e2e8f0;
            font-size: 0.9rem;
            color: #64748b;
        }
        
        .evaluator-info {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .evaluator-info i {
            color: var(--info-color);
        }
        
        .entry-actions {
            display: flex;
            gap: 10px;
        }
        
        .btn-action {
            padding: 5px 15px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            border: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        
        .btn-edit {
            background: rgba(37, 99, 235, 0.1);
            color: var(--primary-color);
            border: 1px solid var(--primary-color);
        }
        
        .btn-edit:hover {
            background: var(--primary-color);
            color: white;
        }
        
        .btn-delete {
            background: rgba(220, 38, 38, 0.1);
            color: var(--danger-color);
            border: 1px solid var(--danger-color);
        }
        
        .btn-delete:hover {
            background: var(--danger-color);
            color: white;
        }
        
        /* CUSTOM MODAL - Completely independent from Bootstrap */
        .custom-modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 9999;
            align-items: center;
            justify-content: center;
        }
        
        .custom-modal-overlay.active {
            display: flex;
        }
        
        .custom-modal {
            background: white;
            border-radius: 20px;
            width: 90%;
            max-width: 500px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            animation: modalFadeIn 0.3s ease;
        }
        
        @keyframes modalFadeIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .custom-modal-header {
            background: linear-gradient(135deg, var(--record-color), #fb923c);
            color: white;
            padding: 20px;
            border-radius: 20px 20px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .custom-modal-header h5 {
            margin: 0;
            font-size: 1.25rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .custom-modal-close {
            background: none;
            border: none;
            color: white;
            font-size: 24px;
            cursor: pointer;
            padding: 0;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: background 0.3s;
        }
        
        .custom-modal-close:hover {
            background: rgba(255, 255, 255, 0.2);
        }
        
        .custom-modal-body {
            padding: 25px;
        }
        
        .custom-modal-footer {
            padding: 20px;
            border-top: 1px solid #e2e8f0;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }
        
        /* No Entries */
        .no-entries {
            text-align: center;
            padding: 50px;
            background: var(--glass-bg);
            border-radius: 20px;
            color: #64748b;
        }
        
        .no-entries i {
            font-size: 60px;
            color: var(--record-color);
            margin-bottom: 20px;
            opacity: 0.5;
        }
        
        /* Filter Bar */
        .filter-bar {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        
        .filter-select {
            flex: 1;
            min-width: 150px;
            padding: 10px;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            font-size: 14px;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .diary-header h1 {
                font-size: 2rem;
            }
            
            .entry-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
            
            .filter-bar {
                flex-direction: column;
            }
        }
        
        /* Print button */
        .print-btn {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 1000;
            background-color: var(--record-color);
            color: white;
            border: none;
            border-radius: 50%;
            width: 60px;
            height: 60px;
            font-size: 24px;
            cursor: pointer;
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .print-btn:hover {
            background-color: #fb923c;
            transform: scale(1.1);
        }
    </style>
</head>

<body id="body-pd" class="content <?php 
    if ($_SESSION['isAdmin']) {
        echo "Admin";
    } else if ($_SESSION["isEvaluator"]) {
        echo "Evaluator";
    } else {
        echo "Guest";
    } 
?>">
    
    <!-- Header -->
    <header class="header <?php 
        if ($_SESSION['isAdmin']) {
            echo "Admin";
        } else if ($_SESSION["isEvaluator"]) {
            echo "Evaluator";
        } else {
            echo "Guest";
        } 
    ?>" id="header">
        <div class="header_toggle"> 
            <i class='bx bx-menu' id="header-toggle"></i>
        </div>
        <?php if (isset($_SESSION["user"])) { ?>
            <h5 style="font-weight: bold; text-transform: capitalize;">
                <i class='bx bx-user-circle me-2'></i>
                <?php echo $_SESSION["user"]; ?>
            </h5>
        <?php } ?>
    </header>

    <!-- Navigation -->
    <div class="l-navbar <?php 
        if ($_SESSION['isAdmin']) {
            echo "Admin";
        } else if ($_SESSION["isEvaluator"]) {
            echo "Evaluator";
        } else {
            echo "Guest";
        } 
    ?>" id="nav-bar">
        <nav class="nav">
            <div> 
                <a href="./index.php" class="nav_logo" style="color: #ffffff; font-weight: bold;"> 
                    <i class='bx bxs-dashboard me-2'></i>
                    <span class="nav_logo-name">Employee Evaluation</span> 
                </a>
                <div class="nav_list">
                    <a href="./Categories.php" class="nav_link"> 
                        <i class='bx bx-category nav_icon'></i> 
                        <span class="nav_name">Categories</span> 
                    </a>
                    <a href="./AttributeCategories.php" class="nav_link"> 
                        <i class='bx bx-spreadsheet nav_icon'></i> 
                        <span class="nav_name">Attributes</span> 
                    </a>
                    <a href="./ScoringMethods.php" class="nav_link"> 
                        <i class='bx bx-tachometer nav_icon'></i> 
                        <span class="nav_name">Marking Schemes</span> 
                    </a>
                    <a href="./Evaluators.php" class="nav_link"> 
                        <i class='bx bxs-user-detail nav_icon'></i> 
                        <span class="nav_name">Evaluators</span> 
                    </a>
                    <a href="./Employees.php" class="nav_link"> 
                        <i class='bx bx-user nav_icon'></i>
                        <span class="nav_name">Evaluatees</span> 
                    </a>
                    
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
                        <a href="./namely_evaluation.php" class="nav_link"> 
                            <i class='bx bx-bar-chart-alt-2 nav_icon'></i> 
                            <span class="nav_name">Evaluate by Individual</span> 
                        </a>
                        <a href="./Warehouses.php" class="nav_link"> 
                            <i class='bx bx-grid-alt nav_icon'></i> 
                            <span class="nav_name">Evaluate by Warehouse</span> 
                        </a>
                    <?php endif; ?>
                    
                    <a href="./periodRatings.php" class="nav_link"> 
                        <i class='bx bxs-star-half'></i> 
                        <span class="nav_name">Results & Grading</span> 
                    </a>
                    <a href="./records.php" class="nav_link active"> 
                        <i class='bx bx-history nav_icon'></i> 
                        <span class="nav_name">Incident Diary</span> 
                    </a>
                </div>
            </div>
            <div>
                <form enctype="multipart/form-data" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST">
                    <button type="submit" name="signout" class="nav_link <?php 
                        if ($_SESSION['isAdmin']) {
                            echo "Admin";
                        } else if ($_SESSION["isEvaluator"]) {
                            echo "Evaluator";
                        } else {
                            echo "Guest";
                        } 
                    ?>" style="background-color: #666; border: none; width: 100%;"> 
                        <i class='bx bx-log-out nav_icon'></i> 
                        <span class="nav_name">SignOut</span> 
                    </button>
                </form>
            </div>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="height-100" style="padding: 20px;">
        <div class="diary-container">
            
            <!-- Diary Header -->
            <div class="diary-header">
                <h1>
                    <i class='bx bx-history' style="color: var(--record-color);"></i>
                    Incident Diary/ Special Comments
                </h1>
                <p>Maintain your daily evaluation diary with comments and tick marks</p>
                <?php if ($is_frozen == '1'): ?>
                    <div class="mt-3">
                        <span class="badge bg-danger px-3 py-2">
                            <i class='bx bx-lock me-1'></i> System Frozen
                        </span>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Add Entry Form -->
            <div class="add-entry-card">
                <h3>
                    <i class='bx bx-plus-circle'></i>
                    Add New Diary Entry
                </h3>
                
                <form method="POST" action="" id="addEntryForm">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="form-label">
                                    <i class='bx bx-calendar me-1'></i>
                                    Date
                                </label>
                                <input type="date" name="entry_date" class="form-control" 
                                       value="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                           
                        </div>
                        
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="form-label">
                                    <i class='bx bx-user me-1'></i>
                                    Evaluator
                                </label>
                                <input type="text" class="form-control" 
                                       value="<?php echo $_SESSION["user"]; ?>" disabled>
                                <small class="text-muted">Automatically recorded</small>
                            </div>
                        </div>
                        
                        <div class="col-12">
                            <div class="form-group">
                                <label class="form-label">
                                    <i class='bx bx-comment me-1'></i>
                                    Comment / Note
                                </label>
                                <textarea name="comment" class="form-control" 
                                          placeholder="Write your notes, observations, or comments here..." 
                                          required></textarea>
                            </div>
                        </div>
                        
                        <div class="col-12">
                            <button type="submit" name="add_entry" class="btn-submit">
                                <i class='bx bx-save'></i>
                                Save Entry
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Filter Bar -->
            <div class="filter-bar">
                <select class="filter-select" id="filterStatus" onchange="filterEntries()">
                    <option value="all">All Entries</option>
                    <option value="ticked">Completed (Ticked)</option>
                    <option value="unticked">Not Completed</option>
                </select>
                
                <input type="text" class="filter-select" id="searchInput" 
                       placeholder="Search comments..." onkeyup="filterEntries()">
            </div>

            <!-- Entries List -->
            <div class="entries-container" id="entriesContainer">
                <?php 
                if (mysqli_num_rows($result) > 0) {
                    while ($row = mysqli_fetch_assoc($result)) {
                        $ticked_class = $row['tick'] == 1 ? 'ticked' : 'unticked';
                        $ticked_text = $row['tick'] == 1 ? 'Completed' : 'Pending';
                        $ticked_badge_class = $row['tick'] == 1 ? 'ticked' : 'unticked';
                        $ticked_icon = $row['tick'] == 1 ? 'bx-check-circle' : 'bx-time';
                ?>
                <div class="entry-card <?php echo $ticked_class; ?>" 
                     data-status="<?php echo $row['tick'] == 1 ? 'ticked' : 'unticked'; ?>"
                     data-comment="<?php echo strtolower($row['comment']); ?>">
                    
                    <!-- Entry Header -->
                    <div class="entry-header">
                        <div class="entry-date">
                            <i class='bx bx-calendar'></i>
                            <span class="date"><?php echo date('F j, Y', strtotime($row['entry_date'])); ?></span>
                        </div>
                        
                        <div class="entry-status">
                            <span class="status-badge <?php echo $ticked_badge_class; ?>">
                                <i class='bx <?php echo $ticked_icon; ?>'></i>
                                <?php echo $ticked_text; ?>
                            </span>
                        </div>
                    </div>
                    
                    <!-- Entry Content -->
                    <div class="entry-content">
                        <div class="entry-comment">
                            <i class='bx bx-comment-detail me-2' style="color: var(--record-color);"></i>
                            <?php echo nl2br(htmlspecialchars($row['comment'])); ?>
                        </div>
                    </div>
                    
                    <!-- Entry Footer -->
                    <div class="entry-footer">
                        <div class="evaluator-info">
                            <i class='bx bx-user-circle'></i>
                            <span><?php echo htmlspecialchars($row['evaluator_name']); ?></span>
                            <span class="mx-2">•</span>
                            <i class='bx bx-time'></i>
                            <span><?php echo date('h:i A', strtotime($row['created_at'])); ?></span>
                        </div>
                        
                        <div class="entry-actions">
                            <!-- Edit Button (opens custom modal) -->
                            <button class="btn-action btn-edit" onclick="openCustomModal(<?php echo $row['id']; ?>, '<?php echo $row['entry_date']; ?>', '<?php echo addslashes($row['comment']); ?>', <?php echo $row['tick']; ?>)">
                                <i class='bx bx-edit'></i>
                                Edit
                            </button>
                            
                            <!-- Delete Button -->
                            <button class="btn-action btn-delete" onclick="confirmDelete(<?php echo $row['id']; ?>)">
                                <i class='bx bx-trash'></i>
                                Delete
                            </button>
                        </div>
                    </div>
                </div>
                <?php 
                    }
                } else { 
                ?>
                <div class="no-entries">
                    <i class='bx bx-book-open'></i>
                    <h4>No Diary Entries Yet</h4>
                    <p>Start adding your first entry using the form above.</p>
                </div>
                <?php } ?>
            </div>
        </div>
    </div>

    <!-- CUSTOM MODAL - Completely independent from Bootstrap -->
    <div class="custom-modal-overlay" id="customModalOverlay">
        <div class="custom-modal">
            <div class="custom-modal-header">
                <h5>
                    <i class='bx bx-edit'></i>
                    Edit Diary Entry
                </h5>
                <button class="custom-modal-close" onclick="closeCustomModal()">&times;</button>
            </div>
            
            <form method="POST" action="" id="customEditForm">
                <div class="custom-modal-body">
                    <input type="hidden" name="entry_id" id="custom_edit_entry_id">
                    
                    <div class="form-group">
                        <label class="form-label">
                            <i class='bx bx-calendar me-1'></i>
                            Date
                        </label>
                        <input type="date" name="entry_date" id="custom_edit_entry_date" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">
                            <i class='bx bx-check-circle me-1'></i>
                            Status
                        </label>
                        <div class="tick-checkbox">
                            <input type="checkbox" name="tick" id="custom_edit_tick" value="1">
                            <label for="custom_edit_tick">
                                <i class='bx bx-check'></i>
                                Mark as Completed
                            </label>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">
                            <i class='bx bx-comment me-1'></i>
                            Comment
                        </label>
                        <textarea name="comment" id="custom_edit_comment" class="form-control" rows="4" required></textarea>
                    </div>
                </div>
                
                <div class="custom-modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeCustomModal()">Cancel</button>
                    <button type="submit" name="edit_entry" class="btn btn-primary" style="background: var(--record-color); border: none;">
                        <i class='bx bx-save me-1'></i>
                        Update Entry
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Print Button -->
    <button class="print-btn" onclick="printDiary()" title="Print Diary">
        <i class='bx bx-printer'></i>
    </button>

    <script>
        // Initialize datepicker
        $(document).ready(function() {
            $('.datepicker').datepicker({
                format: 'yyyy-mm-dd',
                autoclose: true,
                todayHighlight: true
            });
        });
        
        // Filter entries by status and search
        function filterEntries() {
            const status = document.getElementById('filterStatus').value;
            const search = document.getElementById('searchInput').value.toLowerCase();
            const entries = document.querySelectorAll('.entry-card');
            
            entries.forEach(entry => {
                const entryStatus = entry.dataset.status;
                const entryComment = entry.dataset.comment;
                
                let statusMatch = status === 'all' || entryStatus === status;
                let searchMatch = search === '' || entryComment.includes(search);
                
                if (statusMatch && searchMatch) {
                    entry.style.display = 'block';
                } else {
                    entry.style.display = 'none';
                }
            });
        }
        
        // Open custom modal with data
        function openCustomModal(id, date, comment, tick) {
            document.getElementById('custom_edit_entry_id').value = id;
            document.getElementById('custom_edit_entry_date').value = date;
            document.getElementById('custom_edit_comment').value = comment;
            document.getElementById('custom_edit_tick').checked = tick == 1;
            
            document.getElementById('customModalOverlay').classList.add('active');
            document.body.style.overflow = 'hidden'; // Prevent background scrolling
        }
        
        // Close custom modal
        function closeCustomModal() {
            document.getElementById('customModalOverlay').classList.remove('active');
            document.body.style.overflow = ''; // Restore scrolling
        }
        
        // Close modal when clicking outside
        document.getElementById('customModalOverlay').addEventListener('click', function(e) {
            if (e.target === this) {
                closeCustomModal();
            }
        });
        
        // Confirm delete
        function confirmDelete(id) {
            if (confirm('Are you sure you want to delete this entry? This action cannot be undone.')) {
                window.location.href = 'records.php?delete=' + id;
            }
        }
        
        // Print diary
       
        
        // Auto-hide alerts after 3 seconds
        setTimeout(function() {
            var alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                alert.style.display = 'none';
            });
        }, 3000);
    </script>

    <style>
        @media print {
            .no-print, .l-navbar, .header, .add-entry-card, .filter-bar, .print-btn, .entry-actions, .custom-modal-overlay {
                display: none !important;
            }
            
            .diary-container {
                max-width: 100%;
                padding: 0;
                margin: 0;
            }
            
            .entry-card {
                break-inside: avoid;
                page-break-inside: avoid;
                border: 1px solid #000;
                box-shadow: none;
                margin-bottom: 10px;
            }
            
            .diary-header {
                background: white;
                border: 1px solid #000;
                box-shadow: none;
            }
        }
    </style>

</body>

</html>