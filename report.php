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

// Handle Excel download requests
if (isset($_POST['download_excel']) || isset($_POST['download_all'])) {
    $selected_year = $_POST['year'];
    $download_all = isset($_POST['download_all']);
    
    // Get KPI data for download
    $kpi_query = "SELECT 
                    attribute_id,
                    attribute_name,
                    category,
                    process_kpi,
                    is_valued
                  FROM attribute 
                  WHERE process_kpi IS NOT NULL 
                  AND process_kpi != ''
                  AND is_valued = 1
                  ORDER BY category, attribute_name";
    
    $kpi_result = mysqli_query($conn, $kpi_query);
    
    $kpis = [];
    
    if ($kpi_result && mysqli_num_rows($kpi_result) > 0) {
        while ($row = mysqli_fetch_assoc($kpi_result)) {
            $process_kpi = $row['process_kpi'];
            $category = $row['category'];
            
            // Determine target
            $target = '> 0%';
            $target_value = 0;
            $higher_is_better = true;
            
            if (stripos($process_kpi, 'Blend Gain') !== false) {
                $target = '> 0.2%';
                $target_value = 0.2;
                $higher_is_better = true;
            } elseif (stripos($process_kpi, 'Tea Wastage') !== false) {
                $target = '< 0.1%';
                $target_value = 0.1;
                $higher_is_better = false;
            } elseif (stripos($process_kpi, 'PM Wastage') !== false || stripos($process_kpi, 'PM Damage') !== false) {
                $target = '< 1.0%';
                $target_value = 1.0;
                $higher_is_better = false;
            } elseif (stripos($process_kpi, 'Discipline') !== false || stripos($process_kpi, 'late comings') !== false) {
                $target = '0';
                $target_value = 0;
                $higher_is_better = false;
            }
            
            // Get stores - combine from category AND records for this attribute
            $stores = [];
            
            // First, get stores from category table
            $cat_query = "SELECT store_1, store_2, store_3, store_4, store_5, store_6 
                          FROM category WHERE category_name = '$category'";
            $cat_result = mysqli_query($conn, $cat_query);
            if ($cat_result && mysqli_num_rows($cat_result) > 0) {
                $cat_row = mysqli_fetch_assoc($cat_result);
                foreach ($cat_row as $store) {
                    if ($store && $store != '0' && $store != '' && $store !== NULL) {
                        $stores[] = $store;
                    }
                }
            }
            
            // ALSO get stores from records for this SPECIFIC attribute (includes store 73)
            $store_query = "SELECT DISTINCT store 
                           FROM records 
                           WHERE attribute_id = {$row['attribute_id']}
                           AND store IS NOT NULL 
                           AND store != '' 
                           AND store != '0'
                           ORDER BY CAST(store AS UNSIGNED)";
            $store_result = mysqli_query($conn, $store_query);
            if ($store_result && mysqli_num_rows($store_result) > 0) {
                while ($store_row = mysqli_fetch_assoc($store_result)) {
                    $stores[] = $store_row['store'];
                }
            }
            
            // Remove duplicate stores
            $stores = array_unique($stores);
            
            $kpi_key = preg_replace('/[^a-zA-Z0-9]/', '_', $process_kpi) . '_' . $row['attribute_id'];
            
            $kpis[$kpi_key] = [
                'category' => $category,
                'attribute_id' => $row['attribute_id'],
                'name' => $row['attribute_name'],
                'process_kpi' => $process_kpi,
                'stores' => $stores,
                'target' => $target,
                'target_value' => $target_value,
                'higher_is_better' => $higher_is_better
            ];
        }
    }
    
    // Get all months
    $months = [];
    for ($i = 1; $i <= 12; $i++) {
        $month_num = sprintf("%02d", $i);
        $months[] = "$selected_year-$month_num";
    }
    
    // Month names
    $month_names = [
        '01' => 'January', '02' => 'February', '03' => 'March',
        '04' => 'April', '05' => 'May', '06' => 'June',
        '07' => 'July', '08' => 'August', '09' => 'September',
        '10' => 'October', '11' => 'November', '12' => 'December'
    ];
    
    // Function to get KPI value for download - NO category filter
    function getDownloadKPIValue($conn, $kpi, $store, $month) {
        $attribute_id = $kpi['attribute_id'];
        
        $query = "SELECT 
                    AVG(value) as avg_value,
                    SUM(CASE WHEN negative = 1 THEN 1 ELSE 0 END) as neg_count
                  FROM records 
                  WHERE store = '$store' 
                  AND period LIKE '$month%'
                  AND attribute_id = $attribute_id";
        
        $result = mysqli_query($conn, $query);
        if ($result && mysqli_num_rows($result) > 0) {
            $row = mysqli_fetch_assoc($result);
            
            if (stripos($kpi['process_kpi'], 'Discipline') !== false || 
                stripos($kpi['process_kpi'], 'late comings') !== false) {
                return $row['neg_count'] > 0 ? $row['neg_count'] : 0;
            } else {
                return $row['avg_value'] !== null ? round($row['avg_value'], 3) : '-';
            }
        }
        
        $query2 = "SELECT AVG(value) as avg_value 
                   FROM evaluation 
                   WHERE store = '$store' 
                   AND period LIKE '$month%'
                   AND attribute_id = $attribute_id
                   AND value > 0";
        
        $result2 = mysqli_query($conn, $query2);
        if ($result2 && mysqli_num_rows($result2) > 0) {
            $row2 = mysqli_fetch_assoc($result2);
            return $row2['avg_value'] !== null ? round($row2['avg_value'], 3) : '-';
        }
        
        return '-';
    }
    
    // Start Excel output
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="KPI_Report_' . $selected_year . '_' . date('Y-m-d') . '.xls"');
    header('Cache-Control: max-age=0');
    
    echo '<html>';
    echo '<head>';
    echo '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">';
    echo '<style>';
    echo 'td { border: 1px solid #000; padding: 5px; }';
    echo 'th { background: #4CAF50; color: white; border: 1px solid #000; padding: 8px; text-align: center; }';
    echo '.kpi-header { background: #2196F3; color: white; font-size: 16px; font-weight: bold; text-align: left; padding: 10px; }';
    echo '.avg-row { background: #FFC107; font-weight: bold; }';
    echo '.store-header { background: #9C27B0; color: white; }';
    echo '.month-col { background: #E3F2FD; font-weight: bold; }';
    echo '</style>';
    echo '</head>';
    echo '<body>';
    
    // Report Title
    echo '<h2>KPI Performance Report - ' . $selected_year . '</h2>';
    echo '<p>Generated on: ' . date('Y-m-d H:i:s') . '</p>';
    echo '<br>';
    
    if ($download_all) {
        // Download ALL KPIs
        foreach ($kpis as $kpi_key => $kpi) {
            $stores = $kpi['stores'];
            
            // KPI Header
            echo '<h3>' . $kpi['name'] . '</h3>';
            echo '<p><strong>Category:</strong> ' . $kpi['category'] . ' | <strong>Process KPI:</strong> ' . $kpi['process_kpi'] . ' | <strong>Target:</strong> ' . $kpi['target'] . '</p>';
            
            // Create table for this KPI
            echo '<table border="1">';
            
            // Table Header
            echo '<tr>';
            echo '<th>Month</th>';
            foreach ($stores as $store) {
                echo '<th>Store ' . $store . '</th>';
            }
            echo '</tr>';
            
            // Store totals for averages
            $store_totals = array_fill_keys($stores, []);
            
            // Data rows for each month
            foreach ($months as $month) {
                $month_num = substr($month, -2);
                $month_name = $month_names[$month_num];
                
                echo '<tr>';
                echo '<td class="month-col">' . $month_name . '</td>';
                
                foreach ($stores as $store) {
                    $value = getDownloadKPIValue($conn, $kpi, $store, $month);
                    echo '<td>' . $value . '</td>';
                    
                    if ($value !== '-') {
                        $store_totals[$store][] = floatval($value);
                    }
                }
                
                echo '</tr>';
            }
            
            // Average row
            echo '<tr class="avg-row">';
            echo '<td><strong>Average</strong></td>';
            
            foreach ($stores as $store) {
                $filtered = array_filter($store_totals[$store]);
                $avg = empty($filtered) ? '-' : round(array_sum($filtered) / count($filtered), 3);
                echo '<td><strong>' . $avg . '</strong></td>';
            }
            
            echo '</tr>';
            echo '</table>';
            echo '<br><br>';
        }
    } else {
        // Download single KPI (current selected)
        $current_kpi = $kpis[$_POST['kpi_key']];
        $stores = $current_kpi['stores'];
        
        // KPI Header
        echo '<h3>' . $current_kpi['name'] . '</h3>';
        echo '<p><strong>Category:</strong> ' . $current_kpi['category'] . ' | <strong>Process KPI:</strong> ' . $current_kpi['process_kpi'] . ' | <strong>Target:</strong> ' . $current_kpi['target'] . '</p>';
        
        // Create table
        echo '<table border="1">';
        
        // Table Header
        echo '<table>';
        echo '<th>Month</th>';
        foreach ($stores as $store) {
            echo '<th>Store ' . $store . '</th>';
        }
        echo '</tr>';
        
        // Store totals for averages
        $store_totals = array_fill_keys($stores, []);
        
        // Data rows for each month
        foreach ($months as $month) {
            $month_num = substr($month, -2);
            $month_name = $month_names[$month_num];
            
            echo '<tr>';
            echo '<td class="month-col">' . $month_name . '</td>';
            
            foreach ($stores as $store) {
                $value = getDownloadKPIValue($conn, $current_kpi, $store, $month);
                echo '<td>' . $value . '</td>';
                
                if ($value !== '-') {
                    $store_totals[$store][] = floatval($value);
                }
            }
            
            echo '</table>';
        }
        
        // Average row
        echo '<tr class="avg-row">';
        echo '<td><strong>Average</strong></td>';
        
        foreach ($stores as $store) {
            $filtered = array_filter($store_totals[$store]);
            $avg = empty($filtered) ? '-' : round(array_sum($filtered) / count($filtered), 3);
            echo '<td><strong>' . $avg . '</strong></td>';
        }
        
        echo '</tr>';
        echo '</table>';
    }
    
    echo '</body>';
    echo '</html>';
    exit();
}

// Get selected year from URL (default to current year)
$selected_year = isset($_GET['year']) ? $_GET['year'] : date('Y');

// Get all distinct years from records
$years_query = "SELECT DISTINCT YEAR(period) as year FROM records ORDER BY year DESC";
$years_result = mysqli_query($conn, $years_query);

// Dynamically get KPI attributes from database using process_kpi
$kpi_query = "SELECT 
                attribute_id,
                attribute_name,
                category,
                process_kpi,
                is_valued
              FROM attribute 
              WHERE process_kpi IS NOT NULL 
              AND process_kpi != ''
              AND is_valued = 1
              ORDER BY category, attribute_name";

$kpi_result = mysqli_query($conn, $kpi_query);

$kpis = [];
$colors = ['#28a745', '#dc3545', '#ffc107', '#17a2b8', '#6f42c1', '#fd7e14', '#20c997', '#e83e8c'];

if ($kpi_result && mysqli_num_rows($kpi_result) > 0) {
    $color_index = 0;
    while ($row = mysqli_fetch_assoc($kpi_result)) {
        $process_kpi = $row['process_kpi'];
        $category = $row['category'];
        
        // Determine target based on process_kpi content
        $target = '> 0%';
        $target_value = 0;
        $higher_is_better = true;
        
        if (stripos($process_kpi, 'Blend Gain') !== false) {
            $target = '> 0.2%';
            $target_value = 0.2;
            $higher_is_better = true;
        } elseif (stripos($process_kpi, 'Tea Wastage') !== false) {
            $target = '< 0.1%';
            $target_value = 0.1;
            $higher_is_better = false;
        } elseif (stripos($process_kpi, 'PM Wastage') !== false || stripos($process_kpi, 'PM Damage') !== false) {
            $target = '< 1.0%';
            $target_value = 1.0;
            $higher_is_better = false;
        } elseif (stripos($process_kpi, 'Discipline') !== false || stripos($process_kpi, 'late comings') !== false) {
            $target = '0';
            $target_value = 0;
            $higher_is_better = false;
        }
        
        // Get stores - combine from category AND records for this attribute
        $stores = [];
        
        // First, get stores from category table
        $cat_query = "SELECT store_1, store_2, store_3, store_4, store_5, store_6 
                      FROM category WHERE category_name = '$category'";
        $cat_result = mysqli_query($conn, $cat_query);
        if ($cat_result && mysqli_num_rows($cat_result) > 0) {
            $cat_row = mysqli_fetch_assoc($cat_result);
            foreach ($cat_row as $store) {
                if ($store && $store != '0' && $store != '' && $store !== NULL) {
                    $stores[] = $store;
                }
            }
        }
        
        // ALSO get stores from records for this SPECIFIC attribute (includes store 73)
        $store_query = "SELECT DISTINCT store 
                       FROM records 
                       WHERE attribute_id = {$row['attribute_id']}
                       AND store IS NOT NULL 
                       AND store != '' 
                       AND store != '0'
                       ORDER BY CAST(store AS UNSIGNED)";
        $store_result = mysqli_query($conn, $store_query);
        if ($store_result && mysqli_num_rows($store_result) > 0) {
            while ($store_row = mysqli_fetch_assoc($store_result)) {
                $stores[] = $store_row['store'];
            }
        }
        
        // Remove duplicate stores
        $stores = array_unique($stores);
        
        $kpi_key = preg_replace('/[^a-zA-Z0-9]/', '_', $process_kpi) . '_' . $row['attribute_id'];
        
        $kpis[$kpi_key] = [
            'category' => $category,
            'attribute_id' => $row['attribute_id'],
            'name' => $row['attribute_name'],
            'process_kpi' => $process_kpi,
            'stores' => $stores,
            'target' => $target,
            'target_value' => $target_value,
            'higher_is_better' => $higher_is_better,
            'color' => $colors[$color_index % count($colors)]
        ];
        
        $color_index++;
    }
}

// Month names array
$month_names = [
    '01' => 'January',
    '02' => 'February',
    '03' => 'March',
    '04' => 'April',
    '05' => 'May',
    '06' => 'June',
    '07' => 'July',
    '08' => 'August',
    '09' => 'September',
    '10' => 'October',
    '11' => 'November',
    '12' => 'December'
];

// Get all months for the selected year
$months = [];
for ($i = 1; $i <= 12; $i++) {
    $month_num = sprintf("%02d", $i);
    $months[] = "$selected_year-$month_num";
}

// Function to get KPI value - NO category filter
function getKPIValue($conn, $kpi, $store, $month) {
    $attribute_id = $kpi['attribute_id'];
    
    // Check records table - removed category filter to get data from ANY category
    $query = "SELECT 
                AVG(value) as avg_value,
                SUM(CASE WHEN negative = 1 THEN 1 ELSE 0 END) as neg_count
              FROM records 
              WHERE store = '$store' 
              AND period LIKE '$month%'
              AND attribute_id = $attribute_id";
    
    $result = mysqli_query($conn, $query);
    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        
        if (stripos($kpi['process_kpi'], 'Discipline') !== false || 
            stripos($kpi['process_kpi'], 'late comings') !== false) {
            return $row['neg_count'] > 0 ? $row['neg_count'] : 0;
        } else {
            return $row['avg_value'] !== null ? round($row['avg_value'], 3) : '-';
        }
    }
    
    // Check evaluation table - removed category filter
    $query2 = "SELECT AVG(value) as avg_value 
               FROM evaluation 
               WHERE store = '$store' 
               AND period LIKE '$month%'
               AND attribute_id = $attribute_id
               AND value > 0";
    
    $result2 = mysqli_query($conn, $query2);
    if ($result2 && mysqli_num_rows($result2) > 0) {
        $row2 = mysqli_fetch_assoc($result2);
        return $row2['avg_value'] !== null ? round($row2['avg_value'], 3) : '-';
    }
    
    return '-';
}

// Function to calculate average
function calculateAverage($values) {
    $filtered = array_filter($values, function($v) {
        return $v !== '-' && $v !== null && $v !== '';
    });
    return empty($filtered) ? '-' : round(array_sum($filtered) / count($filtered), 3);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KPI Performance Dashboard | JB Employee Evaluation</title>
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
    <!-- Bootstrap Datepicker -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/css/bootstrap-datepicker.min.css">
    
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
            --kpi-color: #f97316;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            overflow-x: hidden;
        }
        
        /* Main container */
        .main-container {
            margin-left: 280px;
            padding: 20px;
            transition: all 0.3s;
            width: calc(100% - 280px);
            position: relative;
            left: -120px;
        }
        
        /* Header */
        .dashboard-header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 8px 32px rgba(31, 38, 135, 0.15);
            border: 1px solid rgba(255, 255, 255, 0.18);
        }
        
        .dashboard-header h1 {
            font-size: 2.2rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 10px;
        }
        
        .dashboard-header p {
            color: #64748b;
            font-size: 1.1rem;
        }
        
        /* Year selector */
        .year-selector {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 20px 25px;
            margin-bottom: 25px;
            box-shadow: 0 8px 32px rgba(31, 38, 135, 0.15);
            border: 1px solid rgba(255, 255, 255, 0.18);
            display: flex;
            align-items: center;
            gap: 20px;
            flex-wrap: wrap;
        }
        
        .year-selector label {
            font-weight: 600;
            color: #1e293b;
            font-size: 1.1rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .year-selector .input-group {
            width: auto;
            min-width: 200px;
        }
        
        .year-selector .form-control {
            border-radius: 25px 0 0 25px;
            border: 2px solid var(--primary-color);
            padding: 10px 15px;
            font-weight: 600;
            background: white;
            height: 45px;
        }
        
        .year-selector .input-group-text {
            border-radius: 0 25px 25px 0;
            border: 2px solid var(--primary-color);
            border-left: none;
            background: var(--primary-color);
            color: white;
            padding: 10px 20px;
            height: 45px;
        }
        
        .year-selector button {
            padding: 10px 30px;
            border-radius: 25px;
            border: none;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            box-shadow: 0 5px 15px rgba(37, 99, 235, 0.3);
            height: 45px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .year-selector button:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(37, 99, 235, 0.4);
        }
        
        /* Download buttons */
        .download-section {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 20px 25px;
            margin-bottom: 25px;
            box-shadow: 0 8px 32px rgba(31, 38, 135, 0.15);
            border: 1px solid rgba(255, 255, 255, 0.18);
            display: flex;
            align-items: center;
            gap: 20px;
            flex-wrap: wrap;
        }
        
        .download-label {
            font-weight: 600;
            color: #1e293b;
            font-size: 1.1rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .download-buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .download-btn {
            padding: 10px 25px;
            border-radius: 25px;
            border: none;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 0.95rem;
        }
        
        .download-excel {
            background: #1D6F42;
            color: white;
        }
        
        .download-excel:hover {
            background: #155a32;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(29, 111, 66, 0.3);
        }
        
        .download-all {
            background: linear-gradient(135deg, #6f42c1 0%, #007bff 100%);
            color: white;
        }
        
        .download-all:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(111, 66, 193, 0.3);
        }
        
        /* KPI Tabs */
        .kpi-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 25px;
            flex-wrap: wrap;
        }
        
        .kpi-tab {
            padding: 12px 25px;
            border-radius: 25px;
            font-weight: 600;
            border: none;
            background: white;
            color: var(--primary-color);
            border: 2px solid var(--primary-color);
            transition: all 0.3s;
            cursor: pointer;
            font-size: 0.95rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .kpi-tab:hover {
            background: #e0e7ff;
            transform: translateY(-2px);
        }
        
        .kpi-tab.active {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            border-color: transparent;
        }
        
        /* KPI Card */
        .kpi-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 8px 32px rgba(31, 38, 135, 0.15);
            border: 1px solid rgba(255, 255, 255, 0.18);
        }
        
        .kpi-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #eef2f6;
        }
        
        .kpi-header h3 {
            font-size: 1.3rem;
            font-weight: 700;
            color: #1e293b;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .target-badge {
            padding: 5px 15px;
            border-radius: 20px;
            background: #eef2ff;
            color: var(--primary-color);
            font-weight: 600;
            font-size: 0.9rem;
        }
        
        .process-kpi-badge {
            padding: 5px 15px;
            border-radius: 20px;
            background: #e0e7ff;
            color: #4f46e5;
            font-weight: 600;
            font-size: 0.85rem;
        }
        
        /* Table styles */
        .table-container {
            overflow-x: auto;
            margin-top: 20px;
            border-radius: 10px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.9rem;
            border: 1px solid #e2e8f0;
            background: white;
            border-radius: 10px;
            overflow: hidden;
        }
        
        th {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            padding: 15px 8px;
            text-align: center;
            font-weight: 600;
            white-space: nowrap;
        }
        
        td {
            padding: 12px 8px;
            text-align: center;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .month-col {
            background: #f8fafc;
            font-weight: 700;
            color: #1e293b;
            text-align: left;
            padding-left: 15px;
            font-size: 1rem;
        }
        
        /* Value colors */
        .value-good {
            background: #00C851 !important;
            color: white !important;
            font-weight: 700;
        }
        
        .value-bad {
            background: #ff4444 !important;
            color: white !important;
            font-weight: 700;
        }
        
        .value-warning {
            background: #ffbb33 !important;
            color: black !important;
            font-weight: 700;
        }
        
        .avg-row {
            background: #202529 !important;
            font-weight: 700;
        }
        
        .avg-row td {
            font-weight: 700;
            border-top: 2px solid var(--primary-color);
        }
        
        /* Legend */
        .legend {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 20px 25px;
            margin-bottom: 25px;
            box-shadow: 0 8px 32px rgba(31, 38, 135, 0.15);
            border: 1px solid rgba(255, 255, 255, 0.18);
            display: flex;
            gap: 30px;
            flex-wrap: wrap;
        }
        
        .legend-item {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .legend-color {
            width: 25px;
            height: 25px;
            border-radius: 5px;
            border: 1px solid rgba(0,0,0,0.1);
        }
        
        /* KPI Content */
        .kpi-content {
            display: none;
        }
        
        .kpi-content.active {
            display: block;
            animation: fadeIn 0.5s ease;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        /* Print button */
        .print-btn {
            position: fixed;
            bottom: 30px;
            right: 30px;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            border: none;
            font-size: 24px;
            cursor: pointer;
            box-shadow: 0 5px 20px rgba(67, 97, 238, 0.3);
            transition: all 0.3s;
            z-index: 1000;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .print-btn:hover {
            transform: scale(1.1) translateY(-5px);
            box-shadow: 0 8px 25px rgba(67, 97, 238, 0.4);
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .main-container {
                margin-left: 0;
                padding: 10px;
                width: 100%;
                left: 0;
            }
            
            .kpi-header {
                flex-direction: column;
                gap: 10px;
                text-align: center;
            }
            
            .year-selector, .download-section {
                flex-direction: column;
                align-items: stretch;
            }
            
            .year-selector .input-group {
                width: 100%;
            }
            
            .download-buttons {
                flex-direction: column;
            }
            
            .download-btn {
                width: 100%;
                justify-content: center;
            }
        }
        
        /* No data */
        .no-data {
            text-align: center;
            padding: 50px;
            background: white;
            border-radius: 15px;
            color: #666;
        }
        
        /* Datepicker customization */
        .datepicker {
            padding: 10px;
            border-radius: 10px;
        }
        
        .datepicker table {
            width: 100%;
        }
        
        .datepicker td, .datepicker th {
            padding: 8px;
            text-align: center;
        }
        
        .datepicker .active {
            background: var(--primary-color) !important;
            color: white !important;
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
        
        /* Disabled nav links */
        .nav_link.disabled {
            opacity: 0.5 !important;
            cursor: not-allowed !important;
        }
        
        /* Header styling */
        .header {
            background: linear-gradient(135deg, #807878ff 0%, #d5c9c9ff 100%) !important;
            color: white !important;
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.2);
        }
        
        .header h5 {
            color: white !important;
        }
        
        .header i {
            color: white !important;
        }
        
        /* System Alert */
        .system-alert {
            position: fixed;
            top: 70px;
            left: 50%;
            transform: translateX(-50%);
            width: 90%;
            max-width: 800px;
            z-index: 1000;
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

    <!-- System Status Alert -->
    <?php if ($is_frozen == '1'): ?>
        <div class="system-alert">
            <div class="alert alert-warning alert-dismissible fade show" role="alert">
                <i class='bx bx-lock me-2'></i>
                <strong>System Frozen!</strong> Evaluation features are currently disabled.
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        </div>
    <?php endif; ?>

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
                    <a href="./kpi_dashboard.php" class="nav_link active"> <i class='bx bx-line-chart'></i> <span class="nav_name">KPI Dashboard</span> </a>
                    <a href="./records.php" class="nav_link"> <i class='bx bx-history nav_icon'></i> <span class="nav_name">Records Diary</span> </a>
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
    <div class="main-container">
        <!-- Header -->
        <div class="dashboard-header">
            <h1>
                <i class='bx bx-line-chart' style="color: #4361ee;"></i>
                KPI Performance Dashboard
            </h1>
            <p>Monthly performance metrics for key indicators - <?php echo $selected_year; ?></p>
            <?php if ($is_frozen == '1'): ?>
                <span class="badge bg-danger mt-2">System Frozen</span>
            <?php endif; ?>
            <div class="mt-2">
                <span class="badge bg-info"><?php echo count($kpis); ?> KPIs Loaded from Database</span>
            </div>
        </div>

        <!-- Year Selector -->
        <div class="year-selector">
            <label for="yearPicker"><i class='bx bx-calendar me-2'></i>Select Year:</label>
            <div class="input-group">
                <input type="text" id="yearPicker" class="form-control" value="<?php echo $selected_year; ?>" placeholder="Select Year" readonly>
                <span class="input-group-text"><i class='bx bx-calendar'></i></span>
            </div>
            <button onclick="goToYear()"><i class='bx bx-search me-2'></i>View</button>
        </div>

        <!-- Download Section -->
        <div class="download-section">
            <div class="download-label">
                <i class='bx bx-download'></i>
                Download Excel Report:
            </div>
            <div class="download-buttons">
                <?php if (!empty($kpis)): ?>
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="year" value="<?php echo $selected_year; ?>">
                    <input type="hidden" name="kpi_key" id="download_kpi_key" value="<?php echo array_key_first($kpis); ?>">
                    <button type="submit" name="download_excel" class="download-btn download-excel">
                        <i class='bx bxs-file-excel'></i> Download Current KPI
                    </button>
                </form>
                
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="year" value="<?php echo $selected_year; ?>">
                    <input type="hidden" name="kpi_key" value="all">
                    <button type="submit" name="download_all" class="download-btn download-all">
                        <i class='bx bxs-download'></i> Download All KPIs
                    </button>
                </form>
                <?php endif; ?>
            </div>
        </div>

        <!-- Legend -->
        <div class="legend">
            <div class="legend-item">
                <div class="legend-color" style="background: #00C851;"></div>
                <span><i class='bx bx-check-circle me-1' style="color: #00C851;"></i>Meeting Target (Good)</span>
            </div>
            <div class="legend-item">
                <div class="legend-color" style="background: #ff4444;"></div>
                <span><i class='bx bx-x-circle me-1' style="color: #ff4444;"></i>Below Target (Needs Attention)</span>
            </div>
            <div class="legend-item">
                <div class="legend-color" style="background: #ffbb33;"></div>
                <span><i class='bx bx-error me-1' style="color: #ffbb33;"></i>Discipline Issue</span>
            </div>
        </div>

        <!-- KPI Tabs -->
        <?php if (!empty($kpis)): ?>
        <div class="kpi-tabs">
            <?php 
            $tab_index = 0;
            foreach ($kpis as $key => $kpi): 
                $active = ($tab_index == 0) ? 'active' : '';
            ?>
            <button class="kpi-tab <?php echo $active; ?>" onclick="showTab(<?php echo $tab_index; ?>, '<?php echo $key; ?>')">
                <i class='bx bx-line-chart'></i>
                <?php echo $kpi['name']; ?>
            </button>
            <?php 
                $tab_index++;
            endforeach; 
            ?>
        </div>

        <!-- KPI Tables -->
        <?php 
        $tab_index = 0;
        foreach ($kpis as $key => $kpi):
            $stores = $kpi['stores'];
            $target_value = $kpi['target_value'];
            $higher_is_better = $kpi['higher_is_better'];
            
            // Check if there's any data for this KPI in selected year
            $has_data = false;
            
            // Collect monthly data
            $monthly_data = [];
            $store_totals = array_fill_keys($stores, []);
            
            foreach ($months as $month) {
                $month_num = substr($month, -2);
                $month_name = isset($month_names[$month_num]) ? $month_names[$month_num] : $month_num;
                $month_data = ['month' => $month_name];
                
                foreach ($stores as $store) {
                    $value = getKPIValue($conn, $kpi, $store, $month);
                    $month_data[$store] = $value;
                    
                    if ($value !== '-') {
                        $store_totals[$store][] = floatval($value);
                        $has_data = true;
                    }
                }
                
                $monthly_data[] = $month_data;
            }
            
            // Calculate averages
            $store_averages = [];
            foreach ($stores as $store) {
                $store_averages[$store] = calculateAverage($store_totals[$store]);
            }
        ?>

        <!-- KPI Content -->
        <div class="kpi-content <?php echo $tab_index == 0 ? 'active' : ''; ?>" id="tab-<?php echo $tab_index; ?>" data-kpi-key="<?php echo $key; ?>">
            <div class="kpi-card">
                <div class="kpi-header">
                    <h3>
                        <span style="color: <?php echo $kpi['color']; ?>;">
                            <i class='bx bx-line-chart'></i>
                        </span>
                        <?php echo $kpi['name']; ?>
                        <span class="process-kpi-badge"><?php echo $kpi['process_kpi']; ?></span>
                        <span class="target-badge">Target: <?php echo $kpi['target']; ?></span>
                    </h3>
                </div>

                <?php if ($has_data): ?>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Month</th>
                                <?php foreach ($stores as $store): ?>
                                    <th>Store <?php echo $store; ?></th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($monthly_data as $row): ?>
                            <tr>
                                <td class="month-col"><?php echo $row['month']; ?></td>
                                <?php foreach ($stores as $store): 
                                    $value = $row[$store];
                                    $cell_class = '';
                                    
                                    if ($value !== '-') {
                                        if (stripos($kpi['process_kpi'], 'Discipline') !== false || 
                                            stripos($kpi['process_kpi'], 'late comings') !== false) {
                                            $cell_class = $value > 0 ? 'value-warning' : 'value-good';
                                        } else {
                                            if ($higher_is_better) {
                                                $cell_class = floatval($value) >= $target_value ? 'value-good' : 'value-bad';
                                            } else {
                                                $cell_class = floatval($value) <= $target_value ? 'value-good' : 'value-bad';
                                            }
                                        }
                                    }
                                ?>
                                    <td class="<?php echo $cell_class; ?>">
                                        <?php echo $value; ?>
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr class="avg-row">
                                <td><strong>Average</strong></td>
                                <?php foreach ($stores as $store): ?>
                                    <td><strong><?php echo $store_averages[$store]; ?></strong></td>
                                <?php endforeach; ?>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                <?php else: ?>
                <div class="no-data">
                    <i class='bx bx-data' style="font-size: 48px; color: #ccc;"></i>
                    <h4>No data available for <?php echo $selected_year; ?></h4>
                    <p>There are no records for this KPI in the selected year.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <?php 
            $tab_index++;
        endforeach; 
        ?>
        
        <?php else: ?>
        <div class="no-data">
            <i class='bx bx-error-circle' style="font-size: 48px; color: #ff4444;"></i>
            <h4>No KPIs Found</h4>
            <p>No valued KPIs with process_kpi defined in the database.</p>
        </div>
        <?php endif; ?>

    </div>

    <!-- Print Button -->
    <button class="print-btn" onclick="window.print()" title="Print Dashboard">
        <i class='bx bx-printer'></i>
    </button>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Bootstrap Datepicker -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/js/bootstrap-datepicker.min.js"></script>
    
    <script>
        $(document).ready(function() {
            // Initialize year picker
            $('#yearPicker').datepicker({
                format: "yyyy",
                viewMode: "years",
                minViewMode: "years",
                autoclose: true,
                startDate: '2020',
                endDate: '2030'
            });
        });
        
        // Show selected tab and update download KPI key
        function showTab(index, kpiKey) {
            // Hide all tabs
            document.querySelectorAll('.kpi-content').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Remove active class from all buttons
            document.querySelectorAll('.kpi-tab').forEach(btn => {
                btn.classList.remove('active');
            });
            
            // Show selected tab
            document.getElementById('tab-' + index).classList.add('active');
            
            // Add active class to clicked button
            document.querySelectorAll('.kpi-tab')[index].classList.add('active');
            
            // Update download form's hidden input with current KPI key
            document.getElementById('download_kpi_key').value = kpiKey;
        }
        
        // Go to selected year
        function goToYear() {
            var year = document.getElementById('yearPicker').value;
            if (year) {
                window.location.href = '?year=' + year;
            } else {
                alert('Please select a year from the calendar');
            }
        }
        
        document.getElementById('yearPicker').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                goToYear();
            }
        });
    </script>

    <style>
        @media print {
            .kpi-tabs, .year-selector, .download-section, .print-btn, .legend, .l-navbar, .header {
                display: none !important;
            }
            .main-container {
                margin-left: 0;
                padding: 10px;
                width: 100%;
                left: 0;
            }
            .kpi-content {
                display: block !important;
                page-break-inside: avoid;
            }
            table {
                border: 2px solid #000;
            }
            th {
                background: #f0f0f0 !important;
                color: #000 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
    </style>

</body>
</html>