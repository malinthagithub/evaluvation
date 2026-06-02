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

$sql = "SELECT * FROM employee";
$result = $conn->query($sql);
$row = $result->fetch_assoc();

if (isset($_GET['sp']) && isset($_GET['ep'])) {
  $html = '<h1 style="text-align: center;">Annual Ranking - ' . $_GET['sp'] . ' to ' . $_GET['ep'] . '</h1>';
} else {
  $html = '<h1 style="text-align: center;">Annual Ranking - ' . date('Y') . '</h1>';
}
$html .= '<h1 style="text-align: center;">Employee Evaluation Scheme</h1>
        <h1 style="text-align: center;">Jafferjee Brothers Tea Division</h1></br></br>';

//////////
if ($result->num_rows > 0) {
  // $emp[$result->num_rows] = array();
  $emp[$result->num_rows][8] = array();
  $i = 0;

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
      return 0;
    }
  }

  function grade2($id, $sPeriod, $ePeriod, $conn)
  {
    $sqlev = "SELECT * FROM evaluation WHERE emp_id = '$id' and period BETWEEN '$sPeriod' AND '$ePeriod'";
    $resultev = $conn->query($sqlev);

    $sqlc = "SELECT COUNT(DISTINCT period) AS 'count' FROM evaluation WHERE emp_id = '$id' and period BETWEEN '$sPeriod' AND '$ePeriod'";
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
      return 0;
    }
  }

  $html .= '<table class="card col-md-12" style="text-align: center; padding: 5px;">
            <tr>
            <th style="padding: 2vh; border: 1px solid #ccc;">Serial No.</th>
            <th style="padding: 2vh; border: 1px solid #ccc;">Employee Number</th>
            <th style="padding: 2vh; border: 1px solid #ccc;">Employee Name</th>
            <th style="padding: 2vh; border: 1px solid #ccc;">Category</th>
            <th style="padding: 2vh; border: 1px solid #ccc;">Store</th>';
  if (isset($_GET['sp']) && isset($_GET['ep'])) {
    $html .= '<th style="padding: 2vh; border: 1px solid #ccc;">';
    $sp = $_GET['sp']; echo(date('Y', strtotime($sp . ' -1 years')));
    $html .= 'Score</th>
            <th style="padding: 2vh; border: 1px solid #ccc;">';
    $sp = $_GET['sp']; echo(date('Y', strtotime($sp . ' -1 years')));
    $html .= 'Rank</th>
            <th style="padding: 2vh; border: 1px solid #ccc;">Current Score</th>
            <th style="padding: 2vh; border: 1px solid #ccc;">Current Rank</th>';
  } else {
    $html .= '<th style="padding: 2vh; border: 1px solid #ccc;">Last Years Score</th>
            <th style="padding: 2vh; border: 1px solid #ccc;">Last Years Rank</th>
            <th style="padding: 2vh; border: 1px solid #ccc;">Current Years Score</th>
            <th style="padding: 2vh; border: 1px solid #ccc;">Current Years Rank</th>';
  }
  $html .= '</tr>';
  $emp = array();

  while ($row = $result->fetch_assoc()) {
    $emp_id = $row['emp_id'];
    $emp_name = $row['emp_name'];
    $current_category = $row['current_category'];
    $current_store = $row['current_store'];

    $period = date('Y');

    if (isset($_GET['sp']) && isset($_GET['ep'])) {
      $sp = $_GET['sp'];
      $ep = $_GET['ep'];
      $current_year_score = grade2($emp_id, $sp, $ep, $conn);
    } else {
      $current_year_score = grade($emp_id, $period, $conn);
    }
    $last_year_score = grade($emp_id, date('Y', strtotime($period . ' -1 years')), $conn);

    $emp[] = array(
      'emp_id' => $emp_id,
      'emp_name' => $emp_name,
      'current_category' => $current_category,
      'current_store' => $current_store,
      'last_year_score' => $last_year_score,
      'current_year_score' => $current_year_score
    );
  }

  usort($emp, function ($a, $b) {
    return $b['last_year_score'] <=> $a['last_year_score'];
  });

  $last_year_rank = 1;
  $last_year_score = null;
  $same_last_year_ranks = 0;

  for ($i = 0; $i < count($emp); $i++) {
    if ($emp[$i]['last_year_score'] != $last_year_score) {
      $last_year_rank += $same_last_year_ranks;
      $same_last_year_ranks = 0;
    }

    $same_last_year_ranks++;
    $last_year_score = $emp[$i]['last_year_score'];
    $emp[$i]['last_year_rank'] = $last_year_rank;
  }

  usort($emp, function ($a, $b) {
    return $b['current_year_score'] <=> $a['current_year_score'];
  });

  $current_year_rank = 1;
  $current_year_score = null;
  $same_current_year_ranks = 0;

  for ($i = 0; $i < count($emp); $i++) {
    if ($emp[$i]['current_year_score'] != $current_year_score) {
      $current_year_rank += $same_current_year_ranks;
      $same_current_year_ranks = 0;
    }

    $same_current_year_ranks++;
    $current_year_score = $emp[$i]['current_year_score'];
    $emp[$i]['current_year_rank'] = $current_year_rank;
  }

  foreach ($emp as $index => $employee) {
    $html .= '<tr>
                <td style="padding: 1vh; border: 1px solid #ccc;">' . $index + 1 . '</td>
                <td style="padding: 1vh; border: 1px solid #ccc;">' . $employee['emp_id'] . '</td>
                <td style="padding: 1vh; border: 1px solid #ccc;">' . $employee['emp_name'] . '</td>
                <td style="padding: 1vh; border: 1px solid #ccc;">' . $employee['current_category'] . '</td>
                <td style="padding: 1vh; border: 1px solid #ccc;">' . $employee['current_store'] . '</td>
                <td style="padding: 1vh; border: 1px solid #ccc;">' . number_format($employee['last_year_score'], 2) . '</td>
                <td style="padding: 1vh; border: 1px solid #ccc;">' . $employee['last_year_rank'] . '</td>
                <td style="padding: 1vh; border: 1px solid #ccc;">' . number_format($employee['current_year_score'], 2) . '</td>
                <td style="padding: 1vh; border: 1px solid #ccc;">' . $employee['current_year_rank'] . '</td>
              </tr>';
  }
  $html .= '</table>';
}

$pdf->writeHTML($html, true, false, true, false, '');
$pdf->Output(date('Y-m-d') . '.pdf', 'I');
