<?php
include "config.php";
session_start();
require_once('tcpdf/tcpdf.php');

$pdf = new TCPDF('L', PDF_UNIT, 'A4', true, 'UTF-8', false);

$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('Your Name');
$pdf->SetTitle('Table Report');

$pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

$pdf->SetFont('times', '', 10);
$pdf->AddPage();

$id = $_GET["id"];
$evl = $_SESSION["id"];

$sql = "SELECT * FROM employee WHERE emp_id ='$id'";
$result = $conn->query($sql);
$row = $result->fetch_assoc();

$html = '<h1 style="text-align: center;">JB Tea Dip EE Report ';
if (isset($_GET['sp']) && isset($_GET['ep'])) {
  $html .= ' of <span  style="color: #f00;">' . $row['emp_id'] . '</span> (' . $_GET['sp'] . ' to ' . $_GET['ep'] . ')</h1></br></br>';
} else {
  $html .= ' of <span  style="color: #f00;">' . $row['emp_id'] . '</span> (Until ' . date('Y-m-d') . ')</h1></br></br>';
}
$html .= '<h3><b>' . $row['emp_name'] . '</b></h3>';
$html .= '<h5><b>Emp No: </b>' . $row['emp_id'] . '</h5>';
$html .= '<p>' . $row['current_category'] . '</p>';
$html .= '<p> Store No: ' . $row['current_store'] . '</p></br>';

//////////
function grade($id, $period, $conn)
{
  $sqlev = "SELECT * FROM evaluation WHERE emp_id = '$id' and period LIKE '$period%'";
  $resultev = $conn->query($sqlev);

  $sqlc = "SELECT COUNT(DISTINCT period) AS 'count' FROM evaluation WHERE emp_id = '$id' and period LIKE '$period%'";
  $resultc = $conn->query($sqlc);
  $rowc = $resultc->fetch_assoc();
  $count = $rowc['count'];

  $rate = null;
  while ($rowev = $resultev->fetch_assoc()) {
    $id = $rowev['attribute_id'];
    $sqlat = "SELECT * FROM attribute WHERE attribute_id = '$id'";
    $resultat = $conn->query($sqlat);
    $rowat = $resultat->fetch_assoc();

    $sm = $rowat['scoring_method'];
    $sqlsm = "SELECT * FROM scoring_method WHERE sm_name = '$sm'";
    $resultsm = $conn->query($sqlsm);
    $rowsm = $resultsm->fetch_assoc();

    if (isset($rate)) {
      if (isset($rowev['status']) && $rowev['status'] < 0) {
        if ($rowev['status'] == -1) {
          $rate = ($rate + $rowat['weightage'] * 3 / 5);
        } else if ($rowev['status'] == -2) {
          $rate = $rate;
        }
      } else if (isset($rowev['value'])) {
        if (isset($rowsm['5_left']) && isset($rowsm['5_right']) && isset($rowsm['0_left']) && isset($rowsm['0_right'])) {
          if ($rowev['value'] <= $rowsm['5_left']) {
            $rate = ($rate + $rowat['weightage']);
          } else if ($rowev['value'] <= $rowsm['4_left']) {
            $rate = ($rate + $rowat['weightage'] * 4 / 5);
          } else if ($rowev['value'] <= $rowsm['3_left']) {
            $rate = ($rate + $rowat['weightage'] * 3 / 5);
          } else if ($rowev['value'] <= $rowsm['2_left']) {
            $rate = ($rate + $rowat['weightage'] * 2 / 5);
          } else if ($rowev['value'] <= $rowsm['1_left']) {
            $rate = ($rate + $rowat['weightage'] / 5);
          } else if ($rowev['value'] <= $rowsm['0_left']) {
            $rate = ($rate);
          }
        } else if (isset($rowsm['5_left']) && isset($rowsm['0_right'])) {
          if ($rowev['value'] <= $rowsm['5_left']) {
            $rate = ($rate + $rowat['weightage']);
          } else if ($rowev['value'] <= $rowsm['4_left']) {
            $rate = ($rate + $rowat['weightage'] * 4 / 5);
          } else if ($rowev['value'] <= $rowsm['3_left']) {
            $rate = ($rate + $rowat['weightage'] * 3 / 5);
          } else if ($rowev['value'] <= $rowsm['2_left']) {
            $rate = ($rate + $rowat['weightage'] * 2 / 5);
          } else if ($rowev['value'] <= $rowsm['1_left']) {
            $rate = ($rate + $rowat['weightage'] / 5);
          } else if ($rowev['value'] >= $rowsm['0_right']) {
            $rate = ($rate);
          }
        } else if (isset($rowsm['5_right']) && isset($rowsm['0_left'])) {
          if ($rowev['value'] >= $rowsm['5_right']) {
            $rate = ($rate + $rowat['weightage']);
          } else if ($rowev['value'] >= $rowsm['4_right']) {
            $rate = ($rate + $rowat['weightage'] * 4 / 5);
          } else if ($rowev['value'] >= $rowsm['3_right']) {
            $rate = ($rate + $rowat['weightage'] * 3 / 5);
          } else if ($rowev['value'] >= $rowsm['2_right']) {
            $rate = ($rate + $rowat['weightage'] * 2 / 5);
          } else if ($rowev['value'] >= $rowsm['1_right']) {
            $rate = ($rate + $rowat['weightage'] / 5);
          } else if ($rowev['value'] <= $rowsm['0_left']) {
            $rate = ($rate);
          }
        }
      } else {
        if (($rowev['negative'] == $rowsm['5_left'] && $rowev['positive'] >= $rowsm['5_right'])) {
          $rate = ($rate + $rowat['weightage']);
        } else if (($rowev['negative'] == $rowsm['4_left'] && $rowev['positive'] == $rowsm['4_right']) || ($rowev['negative'] == $rowsm['4_left'] + 1 && $rowev['positive'] >= $rowsm['4_right'] + 2)) {
          $rate = ($rate + $rowat['weightage'] * 4 / 5);
        } else if (($rowev['negative'] == $rowsm['3_left'] && $rowev['positive'] == $rowsm['3_right']) || ($rowev['negative'] == $rowsm['3_left'] + 1 && $rowev['positive'] >= $rowsm['3_right'] + 1)) {
          $rate = ($rate + $rowat['weightage'] * 3 / 5);
        } else if (($rowev['negative'] == $rowsm['2_left'] && $rowev['positive'] == $rowsm['2_right']) || ($rowev['negative'] == $rowsm['2_left'] + 1 && $rowev['positive'] >= $rowsm['2_right'] + 1)) {
          $rate = ($rate + $rowat['weightage'] * 2 / 5);
        } else if (($rowev['negative'] == $rowsm['1_left'] && $rowev['positive'] == $rowsm['1_right']) || ($rowev['negative'] == $rowsm['1_left'] + 1 && $rowev['positive'] >= $rowsm['1_right'] + 1)) {
          $rate = ($rate + $rowat['weightage'] * 1 / 5);
        } else if (($rowev['negative'] >= $rowsm['0_left'] && $rowev['positive'] >= $rowsm['0_right'])) {
          $rate = ($rate);
        }
      }
    } else {
      if (isset($rowev['status']) && $rowev['status'] < 0) {
        if ($rowev['status'] == -1) {
          $rate = $rowat['weightage'] * 3 / 5;
        } else if ($rowev['status'] == -2) {
          $rate = 0;
        }
      } else if (isset($rowev['value'])) {
        if (isset($rowsm['5_left']) && isset($rowsm['5_right']) && isset($rowsm['0_left']) && isset($rowsm['0_right'])) {
          if ($rowev['value'] <= $rowsm['5_left']) {
            $rate = $rowat['weightage'];
          } else if ($rowev['value'] <= $rowsm['4_left']) {
            $rate = $rowat['weightage'] * 4 / 5;
          } else if ($rowev['value'] <= $rowsm['3_left']) {
            $rate = $rowat['weightage'] * 3 / 5;
          } else if ($rowev['value'] <= $rowsm['2_left']) {
            $rate = $rowat['weightage'] * 2 / 5;
          } else if ($rowev['value'] <= $rowsm['1_left']) {
            $rate = $rowat['weightage'] / 5;
          } else if ($rowev['value'] <= $rowsm['0_left']) {
            $rate = 0;
          }
        } else if (isset($rowsm['5_left']) && isset($rowsm['0_right'])) {
          if ($rowev['value'] <= $rowsm['5_left']) {
            $rate = $rowat['weightage'];
          } else if ($rowev['value'] <= $rowsm['4_left']) {
            $rate = $rowat['weightage'] * 4 / 5;
          } else if ($rowev['value'] <= $rowsm['3_left']) {
            $rate = $rowat['weightage'] * 3 / 5;
          } else if ($rowev['value'] <= $rowsm['2_left']) {
            $rate = $rowat['weightage'] * 2 / 5;
          } else if ($rowev['value'] <= $rowsm['1_left']) {
            $rate = $rowat['weightage'] / 5;
          } else if ($rowev['value'] >= $rowsm['0_right']) {
            $rate = 0;
          }
        } else if (isset($rowsm['5_right']) && isset($rowsm['0_left'])) {
          if ($rowev['value'] >= $rowsm['5_right']) {
            $rate = $rowat['weightage'];
          } else if ($rowev['value'] >= $rowsm['4_right']) {
            $rate = $rowat['weightage'] * 4 / 5;
          } else if ($rowev['value'] >= $rowsm['3_right']) {
            $rate = $rowat['weightage'] * 3 / 5;
          } else if ($rowev['value'] >= $rowsm['2_right']) {
            $rate = $rowat['weightage'] * 2 / 5;
          } else if ($rowev['value'] >= $rowsm['1_right']) {
            $rate = $rowat['weightage'] / 5;
          } else if ($rowev['value'] <= $rowsm['0_left']) {
            $rate = 0;
          }
        }
      } else {
        if (($rowev['negative'] == $rowsm['5_left'] && $rowev['positive'] >= $rowsm['5_right'])) {
          $rate = $rowat['weightage'];
        } else if (($rowev['negative'] == $rowsm['4_left'] && $rowev['positive'] == $rowsm['4_right']) || ($rowev['negative'] == $rowsm['4_left'] + 1 && $rowev['positive'] >= $rowsm['4_right'] + 2)) {
          $rate = $rowat['weightage'] * 4 / 5;
        } else if (($rowev['negative'] == $rowsm['3_left'] && $rowev['positive'] == $rowsm['3_right']) || ($rowev['negative'] == $rowsm['3_left'] + 1 && $rowev['positive'] >= $rowsm['3_right'] + 1)) {
          $rate = $rowat['weightage'] * 3 / 5;
        } else if (($rowev['negative'] == $rowsm['2_left'] && $rowev['positive'] == $rowsm['2_right']) || ($rowev['negative'] == $rowsm['2_left'] + 1 && $rowev['positive'] >= $rowsm['2_right'] + 1)) {
          $rate = $rowat['weightage'] * 2 / 5;
        } else if (($rowev['negative'] == $rowsm['1_left'] && $rowev['positive'] == $rowsm['1_right']) || ($rowev['negative'] == $rowsm['1_left'] + 1 && $rowev['positive'] >= $rowsm['1_right'] + 1)) {
          $rate = $rowat['weightage'] * 1 / 5;
        } else if (($rowev['negative'] >= $rowsm['0_left'] && $rowev['positive'] >= $rowsm['0_right'])) {
          $rate = 0;
        }
      }
    }
  }
  if ($count != 0 || $count) {
    return $rate / $count;
  } else {
    return null;
  }
}

function gradese($id, $periods, $periode, $conn)
{
  $sqlev = "SELECT * FROM evaluation WHERE emp_id = '$id' and period BETWEEN '$periods' AND '$periode'";
  $resultev = $conn->query($sqlev);

  $sqlc = "SELECT COUNT(DISTINCT period) AS 'count' FROM evaluation WHERE emp_id = '$id' and period BETWEEN '$periods' AND '$periode'";
  $resultc = $conn->query($sqlc);
  $rowc = $resultc->fetch_assoc();
  $count = $rowc['count'];

  $rate = null;
  while ($rowev = $resultev->fetch_assoc()) {
    $id = $rowev['attribute_id'];
    $sqlat = "SELECT * FROM attribute WHERE attribute_id = '$id'";
    $resultat = $conn->query($sqlat);
    $rowat = $resultat->fetch_assoc();

    $sm = $rowat['scoring_method'];
    $sqlsm = "SELECT * FROM scoring_method WHERE sm_name = '$sm'";
    $resultsm = $conn->query($sqlsm);
    $rowsm = $resultsm->fetch_assoc();

    if (isset($rate)) {
      if (isset($rowev['status']) && $rowev['status'] < 0) {
        if ($rowev['status'] == -1) {
          $rate = ($rate + $rowat['weightage'] * 3 / 5);
        } else if ($rowev['status'] == -2) {
          $rate = $rate;
        }
      } else if (isset($rowev['value'])) {
        if (isset($rowsm['5_left']) && isset($rowsm['5_right']) && isset($rowsm['0_left']) && isset($rowsm['0_right'])) {
          if ($rowev['value'] <= $rowsm['5_left']) {
            $rate = ($rate + $rowat['weightage']);
          } else if ($rowev['value'] <= $rowsm['4_left']) {
            $rate = ($rate + $rowat['weightage'] * 4 / 5);
          } else if ($rowev['value'] <= $rowsm['3_left']) {
            $rate = ($rate + $rowat['weightage'] * 3 / 5);
          } else if ($rowev['value'] <= $rowsm['2_left']) {
            $rate = ($rate + $rowat['weightage'] * 2 / 5);
          } else if ($rowev['value'] <= $rowsm['1_left']) {
            $rate = ($rate + $rowat['weightage'] / 5);
          } else if ($rowev['value'] <= $rowsm['0_left']) {
            $rate = ($rate);
          }
        } else if (isset($rowsm['5_left']) && isset($rowsm['0_right'])) {
          if ($rowev['value'] <= $rowsm['5_left']) {
            $rate = ($rate + $rowat['weightage']);
          } else if ($rowev['value'] <= $rowsm['4_left']) {
            $rate = ($rate + $rowat['weightage'] * 4 / 5);
          } else if ($rowev['value'] <= $rowsm['3_left']) {
            $rate = ($rate + $rowat['weightage'] * 3 / 5);
          } else if ($rowev['value'] <= $rowsm['2_left']) {
            $rate = ($rate + $rowat['weightage'] * 2 / 5);
          } else if ($rowev['value'] <= $rowsm['1_left']) {
            $rate = ($rate + $rowat['weightage'] / 5);
          } else if ($rowev['value'] >= $rowsm['0_right']) {
            $rate = ($rate);
          }
        } else if (isset($rowsm['5_right']) && isset($rowsm['0_left'])) {
          if ($rowev['value'] >= $rowsm['5_right']) {
            $rate = ($rate + $rowat['weightage']);
          } else if ($rowev['value'] >= $rowsm['4_right']) {
            $rate = ($rate + $rowat['weightage'] * 4 / 5);
          } else if ($rowev['value'] >= $rowsm['3_right']) {
            $rate = ($rate + $rowat['weightage'] * 3 / 5);
          } else if ($rowev['value'] >= $rowsm['2_right']) {
            $rate = ($rate + $rowat['weightage'] * 2 / 5);
          } else if ($rowev['value'] >= $rowsm['1_right']) {
            $rate = ($rate + $rowat['weightage'] / 5);
          } else if ($rowev['value'] <= $rowsm['0_left']) {
            $rate = ($rate);
          }
        }
      } else {
        if (($rowev['negative'] == $rowsm['5_left'] && $rowev['positive'] >= $rowsm['5_right'])) {
          $rate = ($rate + $rowat['weightage']);
        } else if (($rowev['negative'] == $rowsm['4_left'] && $rowev['positive'] == $rowsm['4_right']) || ($rowev['negative'] == $rowsm['4_left'] + 1 && $rowev['positive'] >= $rowsm['4_right'] + 2)) {
          $rate = ($rate + $rowat['weightage'] * 4 / 5);
        } else if (($rowev['negative'] == $rowsm['3_left'] && $rowev['positive'] == $rowsm['3_right']) || ($rowev['negative'] == $rowsm['3_left'] + 1 && $rowev['positive'] >= $rowsm['3_right'] + 1)) {
          $rate = ($rate + $rowat['weightage'] * 3 / 5);
        } else if (($rowev['negative'] == $rowsm['2_left'] && $rowev['positive'] == $rowsm['2_right']) || ($rowev['negative'] == $rowsm['2_left'] + 1 && $rowev['positive'] >= $rowsm['2_right'] + 1)) {
          $rate = ($rate + $rowat['weightage'] * 2 / 5);
        } else if (($rowev['negative'] == $rowsm['1_left'] && $rowev['positive'] == $rowsm['1_right']) || ($rowev['negative'] == $rowsm['1_left'] + 1 && $rowev['positive'] >= $rowsm['1_right'] + 1)) {
          $rate = ($rate + $rowat['weightage'] * 1 / 5);
        } else if (($rowev['negative'] >= $rowsm['0_left'] && $rowev['positive'] >= $rowsm['0_right'])) {
          $rate = ($rate);
        }
      }
    } else {
      if (isset($rowev['status']) && $rowev['status'] < 0) {
        if ($rowev['status'] == -1) {
          $rate = $rowat['weightage'] * 3 / 5;
        } else if ($rowev['status'] == -2) {
          $rate = 0;
        }
      } else if (isset($rowev['value'])) {
        if (isset($rowsm['5_left']) && isset($rowsm['5_right']) && isset($rowsm['0_left']) && isset($rowsm['0_right'])) {
          if ($rowev['value'] <= $rowsm['5_left']) {
            $rate = $rowat['weightage'];
          } else if ($rowev['value'] <= $rowsm['4_left']) {
            $rate = $rowat['weightage'] * 4 / 5;
          } else if ($rowev['value'] <= $rowsm['3_left']) {
            $rate = $rowat['weightage'] * 3 / 5;
          } else if ($rowev['value'] <= $rowsm['2_left']) {
            $rate = $rowat['weightage'] * 2 / 5;
          } else if ($rowev['value'] <= $rowsm['1_left']) {
            $rate = $rowat['weightage'] / 5;
          } else if ($rowev['value'] <= $rowsm['0_left']) {
            $rate = 0;
          }
        } else if (isset($rowsm['5_left']) && isset($rowsm['0_right'])) {
          if ($rowev['value'] <= $rowsm['5_left']) {
            $rate = $rowat['weightage'];
          } else if ($rowev['value'] <= $rowsm['4_left']) {
            $rate = $rowat['weightage'] * 4 / 5;
          } else if ($rowev['value'] <= $rowsm['3_left']) {
            $rate = $rowat['weightage'] * 3 / 5;
          } else if ($rowev['value'] <= $rowsm['2_left']) {
            $rate = $rowat['weightage'] * 2 / 5;
          } else if ($rowev['value'] <= $rowsm['1_left']) {
            $rate = $rowat['weightage'] / 5;
          } else if ($rowev['value'] >= $rowsm['0_right']) {
            $rate = 0;
          }
        } else if (isset($rowsm['5_right']) && isset($rowsm['0_left'])) {
          if ($rowev['value'] >= $rowsm['5_right']) {
            $rate = $rowat['weightage'];
          } else if ($rowev['value'] >= $rowsm['4_right']) {
            $rate = $rowat['weightage'] * 4 / 5;
          } else if ($rowev['value'] >= $rowsm['3_right']) {
            $rate = $rowat['weightage'] * 3 / 5;
          } else if ($rowev['value'] >= $rowsm['2_right']) {
            $rate = $rowat['weightage'] * 2 / 5;
          } else if ($rowev['value'] >= $rowsm['1_right']) {
            $rate = $rowat['weightage'] / 5;
          } else if ($rowev['value'] <= $rowsm['0_left']) {
            $rate = 0;
          }
        }
      } else {
        if (($rowev['negative'] == $rowsm['5_left'] && $rowev['positive'] >= $rowsm['5_right'])) {
          $rate = $rowat['weightage'];
        } else if (($rowev['negative'] == $rowsm['4_left'] && $rowev['positive'] == $rowsm['4_right']) || ($rowev['negative'] == $rowsm['4_left'] + 1 && $rowev['positive'] >= $rowsm['4_right'] + 2)) {
          $rate = $rowat['weightage'] * 4 / 5;
        } else if (($rowev['negative'] == $rowsm['3_left'] && $rowev['positive'] == $rowsm['3_right']) || ($rowev['negative'] == $rowsm['3_left'] + 1 && $rowev['positive'] >= $rowsm['3_right'] + 1)) {
          $rate = $rowat['weightage'] * 3 / 5;
        } else if (($rowev['negative'] == $rowsm['2_left'] && $rowev['positive'] == $rowsm['2_right']) || ($rowev['negative'] == $rowsm['2_left'] + 1 && $rowev['positive'] >= $rowsm['2_right'] + 1)) {
          $rate = $rowat['weightage'] * 2 / 5;
        } else if (($rowev['negative'] == $rowsm['1_left'] && $rowev['positive'] == $rowsm['1_right']) || ($rowev['negative'] == $rowsm['1_left'] + 1 && $rowev['positive'] >= $rowsm['1_right'] + 1)) {
          $rate = $rowat['weightage'] * 1 / 5;
        } else if (($rowev['negative'] >= $rowsm['0_left'] && $rowev['positive'] >= $rowsm['0_right'])) {
          $rate = 0;
        }
      }
    }
  }
  if ($count != 0 || $count) {
    return $rate / $count;
  } else {
    return null;
  }
}

$html .= '<h4 style="font-weight: bold; color: #ff0000;">';
$id = $row['emp_id'];
$period = date('Y');
$rate1 = grade($id, $period, $conn);
$html .= $period . "'s Average Rating : " . number_format($rate1, 2);
$period1 = $period;
$html .= '</h4>';
$html .= '<h4 style="font-weight: bold;">';
$id = $row['emp_id'];
$period = date('Y');
$period = date('Y', strtotime($period . ' -1 years'));
$rate2 = grade($id, $period, $conn);
$html .= $period . "'s Average Rating : " . number_format($rate2, 2);
$period2 = $period;
$html .= '</h4>';
$html .= '<h4>';
$id = $row['emp_id'];
$period = date('Y-m');
$rate4 = grade($id, $period, $conn);
$html .= $period . "'s Average Rating : " . number_format($rate4, 2);
$period4 = $period;
$html .= '</h4>';
if (isset($_GET['sp']) && isset($_GET['ep'])) {
  $periods = $_GET['sp'];
  $periode = $_GET['ep'];
  $rate5 = gradese($id, $periods, $periode, $conn);
  $html .= $periods . " to " . $periode . " Average Rating : " . number_format($rate5, 2);
  // $period5 = $periods;
  $html .= '</h4>';
}
$html .= '</br>';

//////////
// $period = date('Y');
// $period = date('Y', strtotime($period . ' -2 years'));
// $rate3 = grade($id, $period, $conn);
// $period3 = $period;
// $period = date('Y-m');
// $period = date('Y-m', strtotime($period . ' -1 months'));
// $rate5 = grade($id, $period, $conn);
// $period5 = $period;
// $period = date('Y-m');
// $period = date('Y-m', strtotime($period . ' -2 months'));
// $rate6 = grade($id, $period, $conn);
// $period6 = $period;
// $period = date('Y-m');
// $period = date('Y-m', strtotime($period . ' -3 months'));
// $rate7 = grade($id, $period, $conn);
// $period7 = $period;
// $period = date('Y-m');
// $period = date('Y-m', strtotime($period . ' -4 months'));
// $rate8 = grade($id, $period, $conn);
// $period8 = $period;
// $period = date('Y-m');
// $period = date('Y-m', strtotime($period . ' -5 months'));
// $rate9 = grade($id, $period, $conn);
// $period9 = $period;
// $period = date('Y-m');
// $period = date('Y-m', strtotime($period . ' -6 months'));
// $rate10 = grade($id, $period, $conn);
// $period10 = $period;
// $period = date('Y-m');
// $period = date('Y-m', strtotime($period . ' -7 months'));
// $rate11 = grade($id, $period, $conn);
// $period11 = $period;
// $period = date('Y-m');
// $period = date('Y-m', strtotime($period . ' -8 months'));
// $rate12 = grade($id, $period, $conn);
// $period12 = $period;
// $period = date('Y-m');
// $period = date('Y-m', strtotime($period . ' -9 months'));
// $rate13 = grade($id, $period, $conn);
// $period13 = $period;
// $period = date('Y-m');
// $period = date('Y-m', strtotime($period . ' -10 months'));
// $rate14 = grade($id, $period, $conn);
// $period14 = $period;
// $period = date('Y-m');
// $period = date('Y-m', strtotime($period . ' -11 months'));
// $rate15 = grade($id, $period, $conn);
// $period15 = $period;

// $dataPoints = array(
//   array("label" => $period3, "y" => $rate3),
//   array("label" => $period2, "y" => $rate2),
//   array("label" => $period1, "y" => $rate1),
// );

// $lineDataPoints = array(
//   array("y" => $rate15, "label" => $period15),
//   array("y" => $rate14, "label" => $period14),
//   array("y" => $rate13, "label" => $period13),
//   array("y" => $rate12, "label" => $period12),
//   array("y" => $rate11, "label" => $period11),
//   array("y" => $rate10, "label" => $period10),
//   array("y" => $rate9, "label" => $period9),
//   array("y" => $rate8, "label" => $period8),
//   array("y" => $rate7, "label" => $period7),
//   array("y" => $rate6, "label" => $period6),
//   array("y" => $rate5, "label" => $period5),
//   array("y" => $rate4, "label" => $period4),
// );

// $html = '<script src="https://canvasjs.com/assets/script/canvasjs.min.js"></script>';

// $html .= '<div id="chartContainer" style="height: 300px; width: 100%;"></div>';
// $html .= '<div id="lineChartContainer" style="height: 300px; width: 100%;"></div>';
// $html .= '<script>
//     var columnChart = new CanvasJS.Chart("chartContainer", {
//         theme: "light2",
//         title: {
//             text: "Performances of Last Three Year\'s"
//         },
//         axisY: {
//             title: "% Rating"
//         },
//         data: [{
//             type: "column",
//             dataPoints: ' . json_encode($dataPoints, JSON_NUMERIC_CHECK) . '
//         }]
//     });
//     columnChart.render();
//     var columnChartImage = new Image();
//     columnChartImage.src = columnChart.toDataURL();

//     var lineChart = new CanvasJS.Chart("lineChartContainer", {
//         title: {
//             text: "Last 12 Months Performance"
//         },
//         axisY: {
//             title: "% Rating"
//         },
//         data: [{
//             type: "line",
//             dataPoints: ' . json_encode($lineDataPoints, JSON_NUMERIC_CHECK) . '
//         }]
//     });
//     lineChart.render();
//     var lineChartImage = new Image();
//     lineChartImage.src = lineChart.toDataURL();

//     // Wait for the images to load before writing the HTML to the PDF
//     Promise.all([columnChartImage.onload, lineChartImage.onload]).then(function() {
//         var chartHTML = document.getElementById("chartContainer").outerHTML;
//         var lineChartHTML = document.getElementById("lineChartContainer").outerHTML;
//         var html = chartHTML + lineChartHTML;
//         console.log(html);
//         // Write the HTML to the PDF
//         $pdf->writeHTML(html, true, false, true, false, "");
//         // Output the PDF
//         $pdf->Output($id . "_" . date("Y-m-d") . ".pdf", "I");
//     });
// </script>';

//////////
function grade2($rowev, $conn)
{
  $sm = $rowev['scoring_method'];
  $sqlsm = "SELECT * FROM scoring_method WHERE sm_name = '$sm'";
  $resultsm = $conn->query($sqlsm);
  $rowsm = $resultsm->fetch_assoc();

  if (isset($rowev['status']) && $rowev['status'] < 0) {
    if ($rowev['status'] == -1) {
      $rate = 3;
    } else if ($rowev['status'] == -2) {
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
      } else if ($rowev['value'] <= $rowsm['0_left']) {
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
      } else if ($rowev['value'] >= $rowsm['0_right']) {
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
      } else if ($rowev['value'] <= $rowsm['0_left']) {
        $rate = 0;
      }
    }
  } else {
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
    } else if (($rowev['negative'] >= $rowsm['0_left'] && $rowev['positive'] >= $rowsm['0_right'])) {
      $rate = 0;
    }
  }
  return $rate;
}

$html .= '<div class="col-md-12">
          <div class="card p-12 mb-12" style="margin-top: 2%;">
            <center>';
if (isset($_GET['sp']) && isset($_GET['ep'])) {
  $periods = $_GET['sp'];
  $periode = $_GET['ep'];
  $html .= '<h3 style="padding-top: 2vh;">Summary of ' . $periods . " to " . $periode . '</h3>';
} else {
  $html .= '<h3 style="padding-top: 2vh;">Summary of this Year</h3>';
}
$html .= '</center>
          <table style="margin: 8px; border: 1px solid #666; text-align: center;">
            <tr style="border: 1px solid #666;">
              <th style="border: 1px solid #666;">Attribute ID</th>
              <th style="border: 1px solid #666;">Attribute</th>
              <th style="border: 1px solid #666;">Categoury</th>
              <th style="border: 1px solid #666;">Store</th>
              <th style="border: 1px solid #666;">Positive</th>
              <th style="border: 1px solid #666;">Negative</th>
              <th style="border: 1px solid #666;">Value</th>
              <th style="border: 1px solid #666;">Mark(AVG)</th>
              <th style="border: 1px solid #666;">Level</th>
            </tr>';

if (isset($_GET['sp']) && isset($_GET['ep'])) {
  $periods = $_GET['sp'];
  $periode = $_GET['ep'];
  $sqlm = "SELECT * FROM (evaluation E JOIN attribute A ON E.attribute_id = A.attribute_id) WHERE E.emp_id = '$id' and period BETWEEN '$periods' AND '$periode' ORDER BY A.attribute_id";
  $resultm = $conn->query($sqlm);
  $emplo[20][10] = null;
  $i = 0;
  while ($rowm = $resultm->fetch_assoc()) {
    $score = grade2($rowm, $conn);
    $in = 0;
    for ($j = 0; isset($emplo[$j][0]) && $j < 20; $j++) {
      if ($emplo[$j][0] == $rowm['attribute_id']) {
        if (isset($emplo[$j][6]) || isset($emplo[$j][4]) || isset($emplo[$j][5])) {
          $emplo[$j][4] += $rowm['positive'];
          $emplo[$j][5] += $rowm['negative'];
          $emplo[$j][6] += $rowm['neutral'];
          $emplo[$j][9]++;
        }
        if (isset($emplo[$j][7])) {
          $emplo[$j][7] += $rowm['value'];
          $emplo[$j][9]++;
        }
        $emplo[$j][8] += $score;
        $in = 1;
        break;
      }
    }
    if ($in == 0) {
      $emplo[$i][0] = $rowm['attribute_id'];
      $emplo[$i][1] = $rowm['attribute_name'];
      $emplo[$i][2] = $rowm['category'];
      $emplo[$i][3] = $rowm['store'];
      if ($rowm['neutral'] || $rowm['positive'] || $rowm['negative']) {
        $emplo[$i][4] = $rowm['positive'];
        $emplo[$i][5] = $rowm['negative'];
        $emplo[$i][6] = $rowm['neutral'];
      }
      if ($rowm['value']) {
        $emplo[$i][7] = $rowm['value'];
      }
      $emplo[$i][9] = 1;
      $emplo[$i][8] = $score;
      $i++;
    }
  }
} else {
  $year = date('Y');
  $sqlm = "SELECT * FROM (evaluation E JOIN attribute A ON E.attribute_id = A.attribute_id) WHERE E.emp_id = '$id' and period LIKE '$year%' ORDER BY A.attribute_id";
  $resultm = $conn->query($sqlm);
  $emplo[20][10] = null;
  $i = 0;
  while ($rowm = $resultm->fetch_assoc()) {
    $score = grade2($rowm, $conn);
    $in = 0;
    for ($j = 0; isset($emplo[$j][0]) && $j < 20; $j++) {
      if ($emplo[$j][0] == $rowm['attribute_id']) {
        if (isset($emplo[$j][6]) || isset($emplo[$j][4]) || isset($emplo[$j][5])) {
          $emplo[$j][4] += $rowm['positive'];
          $emplo[$j][5] += $rowm['negative'];
          $emplo[$j][6] += $rowm['neutral'];
          $emplo[$j][9]++;
        }
        if (isset($emplo[$j][7])) {
          $emplo[$j][7] += $rowm['value'];
          $emplo[$j][9]++;
        }
        $emplo[$j][8] += $score;
        $in = 1;
        break;
      }
    }
    if ($in == 0) {
      $emplo[$i][0] = $rowm['attribute_id'];
      $emplo[$i][1] = $rowm['attribute_name'];
      $emplo[$i][2] = $rowm['category'];
      $emplo[$i][3] = $rowm['store'];
      if ($rowm['neutral'] || $rowm['positive'] || $rowm['negative']) {
        $emplo[$i][4] = $rowm['positive'];
        $emplo[$i][5] = $rowm['negative'];
        $emplo[$i][6] = $rowm['neutral'];
      }
      if ($rowm['value']) {
        $emplo[$i][7] = $rowm['value'];
      }
      $emplo[$i][9] = 1;
      $emplo[$i][8] = $score;
      $i++;
    }
  }
}

$k = 0;
while (isset($emplo[$k][0])) {
  $html .= '<tr style="border: 1px solid #666;">';
  $html .= '<td style="border: 1px solid #666; col-span: 3;">' . $emplo[$k][0] . '</td>';
  $html .= '<td style="border: 1px solid #666; text-align:left; padding-left: 10px;">' . $emplo[$k][1] . '</td>';
  $html .= '<td style="border: 1px solid #666;">' . $emplo[$k][2] . '</td>';
  $html .= '<td style="border: 1px solid #666;">' . $emplo[$k][3] . '</td>';
  $html .= '<td style="border: 1px solid #666;">';
  if (isset($emplo[$k][4])) {
    $html .= $emplo[$k][4];
  }
  $html .= '</td>';
  $html .= '<td style="border: 1px solid #666;">';
  if (isset($emplo[$k][5])) {
    $html .= $emplo[$k][5];
  }
  $html .= '</td>';
  $html .= '<td style="border: 1px solid #666;">';
  if (isset($emplo[$k][7])) {
    $html .= number_format($emplo[$k][7]/$emplo[$k][9], 3);
  }
  $html .= '</td>';
  $html .= '<td style="border: 1px solid #666;">' . number_format($emplo[$k][8]/$emplo[$k][9], 3) . '</td>';
  $html .= '<td style="border: 1px solid #666;">';
  if ($emplo[$k][8]/$emplo[$k][9] >= 4.5) {
    $html .= 'Outstanding';
  } else if ($emplo[$k][8]/$emplo[$k][9] >= 3.5) {
    $html .= 'Good';
  } else if ($emplo[$k][8]/$emplo[$k][9] >= 2.5) {
    $html .= 'Acceptable';
  } else if ($emplo[$k][8]/$emplo[$k][9] >= 1.5) {
    $html .= 'Barely Acceptable';
  } else if ($emplo[$k][8]/$emplo[$k][9] >= 0.5) {
    $html .= 'Unsatisfactory';
  } else {
    $html .= 'Worst';
  }
  $html .= '</td>';
  $html .= '</tr>';
  $k++;
}
$html .= '</table>';
$html .= '</div>';
$html .= '</div>';

$pdf->writeHTML($html, true, false, true, false, '');
$pdf->Output($id . '_' . $row['emp_name'] . '_' . date('Y-m-d') . '.pdf', 'I');
