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

  if (isset($_POST['submitPeriod']) && isset($_POST['sPeriod']) && isset($_POST['ePeriod']) && !empty($_POST['sPeriod']) && !empty($_POST['ePeriod'])) {
    $eNum = $_POST['empNumber'];
    $sPeriod = $_POST['sPeriod'];
    $ePeriod = $_POST['ePeriod'];
    echo "<script>
          window.location.href='./pdf.php?id=$eNum&sp=$sPeriod&ep=$ePeriod';
          </script>";
  } else if (isset($_POST['submitPeriod'])) {
    $eNum = $_POST['empNumber'];
    echo "<script>
          window.location.href='./rating.php?id=$eNum';
          </script>";
  }

  if (isset($_GET['id'])) {
    $id = $_GET['id'];

    $sql = "SELECT * FROM employee WHERE emp_id ='$id'";
    $result = $conn->query($sql);
    $row = $result->fetch_assoc();
?>

    <!DOCTYPE html>
    <html lang="en">

    <head>
      <meta charset="UTF-8">
      <meta http-equiv="X-UA-Compatible" content="IE=edge">
      <meta name="viewport" content="width=device-width, initial-scale=1.0">
      <title>Details</title>
      <link rel="icon" type="image/x-icon" href="./img/jb.png">

      <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta1/dist/css/bootstrap.min.css" />
      <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta1/dist/js/bootstrap.bundle.min.js" />
      <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.2.1/jquery.min.js" />
      <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/boxicons@latest/css/boxicons.min.css" />

      <link rel="stylesheet" href="./style.css" />
      <script src="./script.js"></script>

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
              <a href="./Warehouses.php" class="nav_link"> <i class='bx bx-grid-alt nav_icon'></i> <span class="nav_name">Evaluate by Warehouse</span> </a>
              <a href="./periodRatings.php" class="nav_link active"> <i class='bx bxs-star-half'></i> <span class="nav_name">Results & Grading</span> </a>
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

      <div class="height-100 container mt-5 mb-3" style="padding-top: 2vh;">
        <div class="card p-12 mb-12 <?php if ($_SESSION['isAdmin']) {
                                      echo "Admin";
                                    } else if ($_SESSION["isEvaluator"]) {
                                      echo "Evaluator";
                                    } else {
                                      echo "Guest";
                                    } ?>">
          <center>
            <h3><b><?php echo $row['emp_name'] ?></b></h3>
            <h5><b>Emp No: </b><?php echo $row['emp_id'] ?></h5>
            <p><?php echo $row['current_category'] ?></p>
            <p><?php echo $row['current_store'] ?></p>
            <!-- </center> -->
            <?php
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
            ?>
            <h4>
              <?php
              $id = $row['emp_id'];
              $period = date('Y-m');
              $rate4 = grade($id, $period, $conn);
              echo $period . " Average Earning : " . number_format($rate4, 2);
              $period4 = $period; ?>
            </h4>
            <h4 style="font-weight: bold; color: #ff0000;">
              <?php
              $id = $row['emp_id'];
              $period = date('Y');
              $rate1 = grade($id, $period, $conn);
              echo $period . " Average Earning : " . number_format($rate1, 2);
              $period1 = $period; ?>
            </h4>
            <h4 style="font-weight: bold;">
              <?php
              $id = $row['emp_id'];
              $period = date('Y');
              $period = date('Y', strtotime($period . ' -1 years'));
              $rate2 = grade($id, $period, $conn);
              echo $period . " Average Earning : " . number_format($rate2, 2);
              $period2 = $period; ?>
            </h4>
            <?php if (isset($_GET['sp']) && isset($_GET['ep'])) {
              $speriod = $_GET['sp'];
              $eperiod = $_GET['ep'];
              $id = $row['emp_id'];
              $serate = gradese($id, $speriod, $eperiod, $conn); ?>
              <h4 style="color: #aaa;">
                <?php
                echo $speriod . " to " . $eperiod . " Average Rating : " . number_format($serate, 2); ?>
              </h4>
            <?php } ?>
            <?php
            $period = date('Y');
            $period = date('Y', strtotime($period . ' -2 years'));
            $rate3 = grade($id, $period, $conn);
            $period3 = $period;
            $period = date('Y-m');
            $period = date('Y-m', strtotime($period . ' -1 months'));
            $rate5 = grade($id, $period, $conn);
            $period5 = $period;
            $period = date('Y-m');
            $period = date('Y-m', strtotime($period . ' -2 months'));
            $rate6 = grade($id, $period, $conn);
            $period6 = $period;
            $period = date('Y-m');
            $period = date('Y-m', strtotime($period . ' -3 months'));
            $rate7 = grade($id, $period, $conn);
            $period7 = $period;
            $period = date('Y-m');
            $period = date('Y-m', strtotime($period . ' -4 months'));
            $rate8 = grade($id, $period, $conn);
            $period8 = $period;
            $period = date('Y-m');
            $period = date('Y-m', strtotime($period . ' -5 months'));
            $rate9 = grade($id, $period, $conn);
            $period9 = $period;
            $period = date('Y-m');
            $period = date('Y-m', strtotime($period . ' -6 months'));
            $rate10 = grade($id, $period, $conn);
            $period10 = $period;
            $period = date('Y-m');
            $period = date('Y-m', strtotime($period . ' -7 months'));
            $rate11 = grade($id, $period, $conn);
            $period11 = $period;
            $period = date('Y-m');
            $period = date('Y-m', strtotime($period . ' -8 months'));
            $rate12 = grade($id, $period, $conn);
            $period12 = $period;
            $period = date('Y-m');
            $period = date('Y-m', strtotime($period . ' -9 months'));
            $rate13 = grade($id, $period, $conn);
            $period13 = $period;
            $period = date('Y-m');
            $period = date('Y-m', strtotime($period . ' -10 months'));
            $rate14 = grade($id, $period, $conn);
            $period14 = $period;
            $period = date('Y-m');
            $period = date('Y-m', strtotime($period . ' -11 months'));
            $rate15 = grade($id, $period, $conn);
            $period15 = $period;
            ?>
          </center>
        </div>

        <?php
        $dataPoints = array(
          array("label" => $period3, "y" => $rate3),
          array("label" => $period2, "y" => $rate2),
          array("label" => $period1, "y" => $rate1),
        );

        $lineDataPoints = array(
          array("y" => $rate15, "label" => $period15),
          array("y" => $rate14, "label" => $period14),
          array("y" => $rate13, "label" => $period13),
          array("y" => $rate12, "label" => $period12),
          array("y" => $rate11, "label" => $period11),
          array("y" => $rate10, "label" => $period10),
          array("y" => $rate9, "label" => $period9),
          array("y" => $rate8, "label" => $period8),
          array("y" => $rate7, "label" => $period7),
          array("y" => $rate6, "label" => $period6),
          array("y" => $rate5, "label" => $period5),
          array("y" => $rate4, "label" => $period4),
        );
        ?>
        <script>
          window.onload = function() {

            var chart = new CanvasJS.Chart("chartContainer", {
              // animationEnabled: true,
              theme: "dark2", // "light1", "light2", "dark1", "dark2"
              title: {
                text: "Performance of Last Three Years"
              },
              axisY: {
                title: "% Earning",
                minimum: 0,
                maximum: 100
              },
              data: [{
                type: "column",
                dataPoints: <?php echo json_encode($dataPoints, JSON_NUMERIC_CHECK); ?>
              }]
            });
            chart.render();

            var lineChart = new CanvasJS.Chart("lineChartContainer", {
              theme: "dark2",
              title: {
                text: "Last 12 Month Performance"
              },
              axisY: {
                title: "% Earning",
                minimum: 0,
                maximum: 100
              },
              data: [{
                type: "line",
                dataPoints: <?php echo json_encode($lineDataPoints, JSON_NUMERIC_CHECK); ?>
              }]
            });
            lineChart.render();
          }
        </script>
        <script src="https://canvasjs.com/assets/script/canvasjs.min.js"></script>

        <div class="col-md-12 row" style="margin-top: 4%;">
          <div class="col-md-6">
            <div class="card" id="chartContainer" style="margin-top: 10px; margin-left: 20px; height: 320px;"></div>
          </div>
          <div class="col-md-6">
            <div class="card" id="lineChartContainer" style="margin-top: 10px; margin-left: 20px; height: 320px;"></div>
          </div>
        </div>

        <?php
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
        ?>

        <div class="col-md-12">
          <div class="card p-12 mb-12 <?php if ($_SESSION['isAdmin']) {
                                        echo "Admin";
                                      } else if ($_SESSION["isEvaluator"]) {
                                        echo "Evaluator";
                                      } else {
                                        echo "Guest";
                                      } ?>" style="margin-top: 2%;">
            <center>
              <?php if (isset($_GET['sp']) && isset($_GET['ep'])) {
                $speriod = $_GET['sp'];
                $eperiod = $_GET['ep']; ?>
                <h3 style="padding-top: 2vh;">Summary of <?php echo $speriod . " to " . $eperiod ?></h3>
              <?php } else { ?>
                <h3 style="padding-top: 2vh;">Summary of <?php echo date('Y') ?></h3>
              <?php } ?>
            </center>
            <table style="margin: 8px; border: 1px solid #666; text-align: center;">
              <tr style="border: 1px solid #666;">
                <!-- <th style="border: 1px solid #666;">Period</th> -->
                <th style="border: 1px solid #666;">Attribute ID</th>
                <th style="border: 1px solid #666;">Attribute</th>
                <th style="border: 1px solid #666;">Category</th>
                <th style="border: 1px solid #666;">Store</th>
                <!-- <th style="border: 1px solid #666;" colspan="5"><?php echo $rowmp['period'] ?></th> -->
                <!-- </tr>
              <tr style="border: 1px solid #666;"> -->
                <th style="border: 1px solid #666;">Positive</th>
                <th style="border: 1px solid #666;">Negative</th>
                <!-- <th style="border: 1px solid #666;">Neglected</th> -->
                <th style="border: 1px solid #666;">Value</th>
                <th style="border: 1px solid #666;">Mark</br>(Weighted)</th>
                <th style="border: 1px solid #666;">Level</th>
              </tr>
              <?php
              $CS = $row['current_store'];
              if (isset($_GET['sp']) && isset($_GET['ep'])) {
                $speriod = $_GET['sp'];
                $eperiod = $_GET['ep'];
                $sqlm = "SELECT * FROM (evaluation E JOIN attribute A ON E.attribute_id = A.attribute_id) WHERE E.emp_id = '$id' AND E.store = '$CS' AND period BETWEEN '$speriod' AND '$eperiod' ORDER BY A.attribute_id";
              } else {
                $year = date('Y');
                $sqlm = "SELECT DISTINCT * FROM (evaluation E JOIN attribute A ON E.attribute_id = A.attribute_id) WHERE E.emp_id = '$id' AND E.store = '$CS' AND period LIKE '$year%' ORDER BY A.attribute_id";
              }
              $resultm = $conn->query($sqlm);
              $emplo[20][9] = null;
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
                    }
                    if (isset($emplo[$j][7])) {
                      $emplo[$j][7] += $rowm['value'];
                      $emplo[$j][7] /= 2;
                    }
                    $emplo[$j][8] += $score;
                    $emplo[$j][8] /= 2;
                    $in = 1;
                    break;
                  }
                }
                if ($in == 0) {
                  $emplo[$i][0] = $rowm['attribute_id'];
                  $emplo[$i][1] = $rowm['attribute_name'];
                  if ($rowm['category'] == 'Common') {
                    $emplo[$i][2] = $rowm['category'];
                  } else {
                    $emplo[$i][2] = $row['current_category'];
                  }
                  $emplo[$i][3] = $rowm['store'];
                  if ($rowm['neutral'] || $rowm['positive'] || $rowm['negative']) {
                    $emplo[$i][4] = $rowm['positive'];
                    $emplo[$i][5] = $rowm['negative'];
                    $emplo[$i][6] = $rowm['neutral'];
                  }
                  if ($rowm['value']) {
                    $emplo[$i][7] = $rowm['value'];
                  }
                  $emplo[$i][8] = $score;
                  $i++;
                }
              } ?>

              <?php $k = 0;
              while (isset($emplo[$k][0])) { ?>
                <tr style="border: 1px solid #666;">
                  <td style="border: 1px solid #666;"><?php echo $emplo[$k][0] ?></td>
                  <td style="border: 1px solid #666; text-align:left; padding-left: 10px;"><?php echo $emplo[$k][1] ?></td>
                  <td style="border: 1px solid #666;"><?php echo $emplo[$k][2] ?></td>
                  <td style="border: 1px solid #666;"><?php echo $emplo[$k][3] ?></td>
                  <td style="border: 1px solid #666;"><?php if (isset($emplo[$k][4])) {
                                                        echo $emplo[$k][4];
                                                      } ?></td>
                  <td style="border: 1px solid #666;"><?php if (isset($emplo[$k][5])) {
                                                        echo $emplo[$k][5];
                                                      } ?></td>
                  <td style="border: 1px solid #666;"><?php if (isset($emplo[$k][7])) {
                                                        echo number_format($emplo[$k][7], 3);
                                                      } ?></td>
                  <td style="border: 1px solid #666;"><?php echo number_format($emplo[$k][8], 3); ?></td>
                  <td style="border: 1px solid #666;"><?php if ($emplo[$k][8] >= 4.5) {
                                                        echo 'Outstanding';
                                                      } else if ($emplo[$k][8] >= 3.5) {
                                                        echo 'Good';
                                                      } else if ($emplo[$k][8] >= 2.5) {
                                                        echo 'Acceptable';
                                                      } else if ($emplo[$k][8] >= 1.5) {
                                                        echo 'Barely Acceptable';
                                                      } else if ($emplo[$k][8] >= 0.5) {
                                                        echo 'Unsatisfactory';
                                                      } else {
                                                        echo 'Worst';
                                                      } ?>
                  </td>
                </tr>
              <?php $k++;
              } ?>
            </table>
          </div>
        </div>

        <div class="col-md-12">
          <div class="card p-12 mb-12 <?php if ($_SESSION['isAdmin']) {
                                        echo "Admin";
                                      } else if ($_SESSION["isEvaluator"]) {
                                        echo "Evaluator";
                                      } else {
                                        echo "Guest";
                                      } ?>" style="margin-top: 2%;">
            <center>
              <h3 style="padding-top: 2vh;">Latest Evaluations Added</h3>
            </center>
            <table style="margin: 8px; border: 1px solid #666; text-align: center;">
              <tr style="border: 1px solid #666;">
                <th style="border: 1px solid #666;"><a style="color:#ccf;" href="./rating.php?id=<?php echo $id ?><?php if (isset($_GET['sp']) && isset($_GET['ep'])) {
                                                                                                                    $sp = $_GET['sp'];
                                                                                                                    $ep = $_GET['ep'];
                                                                                                                    echo "&sp=" . $sp . "&ep=" . $ep;
                                                                                                                  } ?>&filter=Time">Time</a></th>
                <th style="border: 1px solid #666;"><a style="color:#ccf;" href="./rating.php?id=<?php echo $id ?><?php if (isset($_GET['sp']) && isset($_GET['ep'])) {
                                                                                                                    $sp = $_GET['sp'];
                                                                                                                    $ep = $_GET['ep'];
                                                                                                                    echo "&sp=" . $sp . "&ep=" . $ep;
                                                                                                                  } ?>&filter=Period">Period</a></th>
                <th style="border: 1px solid #666;">Attribute</th>
                <th style="border: 1px solid #666;">Category</th>
                <th style="border: 1px solid #666;">Store</th>
                <th style="border: 1px solid #666;">Positive</th>
                <th style="border: 1px solid #666;">Negative</th>
                <th style="border: 1px solid #666;">value</th>
                <th style="border: 1px solid #666;">Mark</th>
                <th style="border: 1px solid #666;">Comment</th>
                <th style="border: 1px solid #666;">Actions</th>
              </tr>
              <?php
              $empcat = $row['current_category'];
              $evaluator = $_SESSION["id"];
              if (isset($_GET['filter']) && $_GET['filter'] == "Period") {
                $sql = "SELECT * FROM ((records R JOIN attribute A ON R.attribute_id = A.attribute_id) JOIN evaluator V ON R.evaluator_id = V.evaluator_id) WHERE R.emp_id = '$id' and R.evaluator_id = '$evaluator' ORDER BY period DESC LIMIT 10;";
                $result = $conn->query($sql);
              } else {
                $sql = "SELECT * FROM ((records R JOIN attribute A ON R.attribute_id = A.attribute_id) JOIN evaluator V ON R.evaluator_id = V.evaluator_id) WHERE R.emp_id = '$id' and R.evaluator_id = '$evaluator' ORDER BY time DESC LIMIT 10;";
                $result = $conn->query($sql);
              }
              while ($row = $result->fetch_assoc()) {
              ?>
                <tr style="border: 1px solid #666;">
                  <td style="border: 1px solid #666;"><?php echo $row['time'] ?></td>
                  <td style="border: 1px solid #666;"><?php echo $row['period'] ?></td>
                  <td style="border: 1px solid #666; text-align:left; padding-left: 10px;"><?php echo $row['attribute_name'] ?></td>
                  <td style="border: 1px solid #666;"><?php if ($row['category'] == 'Common') {
                                                        echo $row['category'];
                                                      } else {
                                                        echo $empcat;
                                                      } ?></td>
                  <td style="border: 1px solid #666;"><?php echo $row['store'] ?></td>
                  <td style="border: 1px solid #666;"><?php echo $row['positive'] ?></td>
                  <td style="border: 1px solid #666;"><?php echo $row['negative'] ?></td>
                  <td style="border: 1px solid #666;"><?php echo $row['value'] ?></td>
                  <td style="border: 1px solid #666;"><?php echo grade2($row, $conn) ?></td>
                  <td style="text-align: left; padding-left: 5px; border: 1px solid #666;"><?php echo $row['comment'] ?></td>
                  <td style="border: 1px solid #666;"><b><a style="color: #03e3fc;" href="./evaluation.php?evlId=<?php echo $row['record_id'] ?>"> <span style="width: 70px; padding-bottom: 3px;"><i class='bx bx-pencil'></i>&nbsp;<span>Edit</span></span></a></b></td>
                </tr>
              <?php } ?>
            </table>
            <?php if (isset($_GET['sp']) && isset($_GET['ep'])) {
              $sp = $_GET['sp'];
              $ep = $_GET['ep'];
            ?>
              <a href="./all_evaluations.php?id=<?php echo $id ?>&sp=<?php echo $sp ?>&ep=<?php echo $ep ?>">
              <?php } else { ?>
                <a href="./all_evaluations.php?id=<?php echo $id ?>">
                <?php } ?>
                <center>
                  <button class="showAll <?php if ($_SESSION['isAdmin']) {
                                            echo "Admin";
                                          } else if ($_SESSION["isEvaluator"]) {
                                            echo "Evaluator";
                                          } else {
                                            echo "Guest";
                                          } ?>" style="border: none; background-color:#fff;">
                    Show All<br>
                    <img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAB4AAAAeCAYAAAA7MK6iAAAAAXNSR0IArs4c6QAAAXhJREFUSEvtlbtxwzAMhoFGtUfIBvEI8QQZIXJBS50zStyJYhFlg2zgjOBskBFcs2GOOTIn8QgC0uWixipFAB9/vIiw0ocrceEG/rfMF1Nd1/Wmqqqzc+5kjBkkt1JK1Yh4tNbuhmG4Uj4kOEIBYOudnXN7Dh6grwF2KcFJ8OFw8AqfxjcuwRNodHvr+77OqeYUfwDAPQcnoJ/W2gcq3ZIaF+FLoF4IO8eh1ln4TwDEWNOYmKLSaMSCvSEFz9ROBBUpjsEFcDFUBG7b9q7rui9G+QQ69pk9x95BKbVFxDMivmut9wR8Ag1j+Oic2xljLrPBEQoAm9BEQwYO45FJZv9agpPN1TTNi3PuOL4xIk7g/izOaW7hIOJJa/08a4F4YyLYLzwGzNkBALm1RM3FwZdAReCS8vB4pLu4qHTWAqHgmdqJoGLFTC3Zl2h2c+UcltY0jSXa1alTAhendzKa1Gbh/gc4UA89579IMRdUcn4DS7L0JzarpfobmFzyH+ztlXMAAAAASUVORK5CYII=" />
                  </button>
                </center>
                </a>
          </div>
        </div>
      </div>
      <div class="floating-container">
        <div class="floating-button <?php if ($_SESSION['isAdmin']) {
                                      echo "Admin";
                                    } else if ($_SESSION["isEvaluator"]) {
                                      echo "Evaluator";
                                    } else {
                                      echo "Guest";
                                    } ?>" style="margin-bottom: 8vh;">
          <?php if (isset($_GET['sp']) && isset($_GET['ep'])) {
            $sp = $_GET['sp'];
            $ep = $_GET['ep'];
          ?>
            <a href="./pdf.php?id=<?php echo $id ?>&sp=<?php echo $sp ?>&ep=<?php echo $ep ?>">
            <?php } else { ?>
              <a href="./pdf.php?id=<?php echo $id ?>">
              <?php } ?>
              <i class='bx bx-printer'></i></a>
        </div>
        <div class="floating-button <?php if ($_SESSION['isAdmin']) {
                                      echo "Admin";
                                    } else if ($_SESSION["isEvaluator"]) {
                                      echo "Evaluator";
                                    } else {
                                      echo "Guest";
                                    } ?>">
          <?php if (isset($_GET['sp']) && isset($_GET['ep'])) {
            $sp = $_GET['sp'];
            $ep = $_GET['ep'];
          ?>
            <a href="./Rankings.php?sp=<?php echo $sp ?>&ep=<?php echo $ep ?>">
            <?php } else { ?>
              <a href="./Rankings.php">
              <?php } ?>
              <i class='bx bx-crown'></i></a>
        </div>
      </div>

      <!-- <form name="pickPeriod" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST" style="width: 50vh; margin-left: 5rem; margin-bottom: 1rem;">
        <div <?php if ($_SESSION['isAdmin'] == 0 && $_SESSION["isEvaluator"] == 0) {
                echo "Hidden";
              } ?>>
          <table>
            <tr>
              <td>
                <label for="sPeriod">From:&nbsp;&nbsp;</label>
              </td>
              <td>
                <input type="month" name="sPeriod" class="form-control" />
              </td>
              <td>&nbsp;&nbsp;</td>
              <td>
                <label for="ePeriod">To:&nbsp;&nbsp;</label>
              </td>
              <td>
                <input type="month" name="ePeriod" class="form-control" value="<?= date('Y-m') ?>" />
              </td>
              <td>
                <input type="text" name="empNumber" value="<?php echo $id ?>" hidden />
                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
              </td>
              <td>
                <button type="submit" name="submitPeriod" id="submitPeriod" style="border: none; height: 2.4rem; width: 7.5rem; border-radius: 5px;"><i class='bx bxs-download'></i>&nbsp;&nbsp;Download</button>
              </td>
            </tr>
          </table>
        </div>
      </form> -->
    </body>

    </html>

<?php }
} else {
  header("location: signin.php");
} ?>