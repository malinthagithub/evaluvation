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

  if (isset($_GET['id']) and $_GET['att']) {

    $id = $_GET['id'];
    $att = $_GET['att'];

    $sql = "SELECT * FROM employee WHERE emp_id ='$id'";
    $resultfind = $conn->query($sql);

    $sqla = "SELECT * FROM attribute WHERE attribute_id ='$att'";
    $resulta = $conn->query($sqla);
    $rowa = $resulta->fetch_assoc();

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

  // can add + button infront of each feild to add relevent things

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
          $sql = "INSERT INTO evaluation(emp_id, period, category, store, attribute_id, positive, neutral, negative)
          VALUES ('$empNumber','$period','$category','$storeNumber','$attributeId','0','0','0')";
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
          $mark = 0;
          $sql = "UPDATE evaluation
          SET neutral = '$mark'
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
        $sqlr = "INSERT INTO records(emp_id, emp_name, period, evaluator_id, category, store, attribute_id, positive, neutral, negative, comment)
          VALUES ('$empNumber','$empName','$period','$evaluator','$category','$storeNumber','$attributeId','0','0','0','$comment')";
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
    var sout = confirm('Already Added a Value for this Attribute. Are you sure you want to continue?');
    if (sout == true) {
        document.getElementById('execute').click();
    }
    </script>";

      if (isset($_POST['execute'])) {
        $result = $conn->query($sql);
        $resultr = $conn->query($sqlr);
      }
?>
      <form id="executeForm" action="" method="post">
        <input type="hidden" name="execute" value="true">
      </form>
      <button style="display:none;" id="execute" type="submit" form="executeForm"></button>

  <?php
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
  ?>

  <!DOCTYPE html>
  <html>

  <head>
    <meta charset="utf-8" />
    <title>Evaluation</title>
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
        } else {
          return true;
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

    <div class="wrapper" style="margin-top: 10vh;">
      <?php if (isset($_GET['evlId'])) {
        $evlId = $_GET['evlId'];

        $sqlEvl = "SELECT * FROM records WHERE record_id ='$evlId'";
        $resulEvl = $conn->query($sqlEvl);
        $rowEvl = $resulEvl->fetch_assoc();
      ?>
        <form class="editForm" name="addForm" enctype="multipart/form-data" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" onsubmit="return validate()" method="POST" id="wizard" style="width: 125vh">
          <h2></h2>
          <section>
            <div class="inner <?php if ($_SESSION['isAdmin']) {
                                echo "Admin";
                              } else if ($_SESSION["isEvaluator"]) {
                                echo "Evaluator";
                              } else {
                                echo "Guest";
                              } ?>">
              <div class="image-holder">
                <img src="images/form-wizard-1.jpg" alt="" />
              </div>
              <div class="form-content">
                <div class="form-header">
                  <h3>Update Evaluation</h3>
                </div>
                <div class="form-row">
                  <div class="form-holder">
                    <input type="text" name="recordId" class="form-control" value="<?php echo $rowEvl['record_id'] ?>" hidden />
                    <input type="text" name="empNumber" placeholder="Emp Number" class="form-control" value="<?php echo $rowEvl['emp_id'] ?>" readonly />
                  </div>
                  <div class="form-holder">
                    <input type="text" name="storeNumber" placeholder="Store Number" class="form-control" value="<?php echo $rowEvl['store'] ?>" readonly />
                  </div>
                </div>
                <div class="form-row">
                  <div class="form-holder w-100">
                    <input type="text" name="empName" placeholder="Employee Name" class="form-control" value="<?php echo $rowEvl['emp_name'] ?>" readonly />
                  </div>
                </div>
                <div class="form-row">
                  <div class="select">
                    <div class="form-holder">
                      <label for="category" style="color: #000;">Category:</label>
                      <select class="form-holder" name="category" style="border: none; color: #666; padding-top: 6px;">
                        <option value="<?php echo $rowEvl['category']; ?>"><?php echo $rowEvl['category']; ?></option>
                      </select>
                      <div class="select-control"></div>
                    </div>
                  </div>
                </div>
                <div class="form-row">
                  <div class="select" style="width: 100%;">
                    <div class="form-holder w-100">
                      <label for="" style="color: #000;">Attribute:</label>
                      <select class="form-holder" name="attribute" style="border: none; color: #666; padding-top: 6px;">
                        <option value="<?php echo $rowEvl['attribute_id'] ?>" selected><?php echo $rowEvl['attribute_id'] ?></option>
                      </select>
                      <div class="select-control"></div>
                    </div>
                  </div>
                </div>
                <div class="form-row">
                  <div class="form-holder">
                    <label for="" style="color: #000;">Period:</label>
                    <input type="month" name="period" class="form-control" value="<?php echo $rowEvl['period'] ?>" readonly />
                  </div>
                  <center>
                    <div class="form-holder" style="align-self: flex-end; transform: translateY(4px)" <?php
                                                                                                      if ($rowEvl['value']) {
                                                                                                        echo "hidden";
                                                                                                      }
                                                                                                      ?>>
                      <div class="checkbox-tick">
                        <?php
                        if ($rowEvl['positive'] == 0 && $rowEvl['neutral'] == 0 && $rowEvl['negative'] == 0) { ?>
                          <label class="neglect" style="color: #f00;">
                            <input type="radio" name="mark" value="-2" <?php
                                                                        if ($rowEvl['positive'] == 0 && $rowEvl['neutral'] == 0 && $rowEvl['negative'] == 0) {
                                                                          echo "checked";
                                                                        }
                                                                        ?> />Neglected<br />
                            <span class="checkmark"></span>
                          </label><br />
                        <?php }
                        if ($rowEvl['positive'] == 1) { ?>
                          <label class="positive">
                            <input type="radio" name="mark" value="+1" <?php
                                                                        if ($rowEvl['positive'] == 1) {
                                                                          echo "checked";
                                                                        }
                                                                        ?> />Positive<br />
                            <span class="checkmark"></span>
                          </label><br />
                        <?php }
                        if ($rowEvl['neutral'] == 1) { ?>
                          <label class="neutral">
                            <input type="radio" name="mark" value="0" <?php
                                                                      if ($rowEvl['neutral'] == 1) {
                                                                        echo "checked";
                                                                      }
                                                                      ?> />Neutral<br />
                            <span class="checkmark"></span>
                          </label><br />
                        <?php }
                        if ($rowEvl['negative'] == 1) { ?>
                          <label class="negative">
                            <input type="radio" name="mark" value="-1" <?php
                                                                        if ($rowEvl['negative'] == 1) {
                                                                          echo "checked";
                                                                        }
                                                                        ?> />Negative<br />
                            <span class="checkmark"></span>
                          </label>
                        <?php } ?>
                      </div>
                    </div>
                  </center>
                  <div class="form-holder" <?php
                                            if (!$rowEvl['value']) {
                                              echo "hidden";
                                            }
                                            ?>>
                    <label for="" style="color: #000;">Value:</label>
                    <input type="text" name="value" placeholder="" class="form-control" value="<?php if ($rowEvl['value']) {
                                                                                                  echo $rowEvl['value'];
                                                                                                } ?>" <?php
                                                                                                      if ($rowEvl['value']) {
                                                                                                        echo "required";
                                                                                                      }
                                                                                                      ?> />
                  </div>
                </div>
                <div class="form-row">
                  <div class="form-holder w-100">
                    <textarea class="form-control" name="comment" id="comment" placeholder="Comment" maxlength="45"><?php echo $rowEvl['comment'] ?></textarea>
                  </div>
                </div>
                <center style="padding-top: 2rem; padding-bottom: 2.5rem">
                  <input type="submit" class="form-button" name="update" value="Update" style="border: none; height: 2.5rem; width: 7.5rem">
                </center>
              </div>
          </section>
        </form>
      <?php } else { ?>
        <form class="editForm" name="addForm" enctype="multipart/form-data" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" onsubmit="return validate()" method="POST" id="wizard" style="width: 125vh">
          <h2></h2>
          <section>
            <div class="inner <?php if ($_SESSION['isAdmin']) {
                                echo "Admin";
                              } else if ($_SESSION["isEvaluator"]) {
                                echo "Evaluator";
                              } else {
                                echo "Guest";
                              } ?>">
              <div class="image-holder">
                <img src="images/form-wizard-1.jpg" alt="" />
              </div>
              <div class="form-content">
                <div class="form-header">
                  <h3>Evaluation</h3>
                </div>
                <div class="form-row">
                  <div class="form-holder">
                    <input type="text" name="empNumber" placeholder="Emp Number" class="form-control" value="<?php echo $rowfind['emp_id'] ?>" readonly />
                  </div>
                  <div class="form-holder">
                    <input type="text" name="storeNumber" placeholder="Store Number" class="form-control" value="<?php echo $rowfind['current_store'] ?>" readonly />
                  </div>
                </div>
                <div class="form-row">
                  <div class="form-holder w-100">
                    <input type="text" name="empName" placeholder="Employee Name" class="form-control" value="<?php echo $rowfind['emp_name'] ?>" readonly />
                  </div>
                </div>
                <div class="form-row">
                  <div class="select">
                    <div class="form-holder">
                      <label for="" style="color: #000;">Category:</label>
                      <select class="form-holder" name="category" style="border: none; color: #666; padding-top: 6px;">
                        <option value="<?php echo $rowfind['current_category']; ?>"><?php echo $rowfind['current_category']; ?></option>
                      </select>
                      <div class="select-control"></div>
                    </div>
                  </div>
                </div>
                <div class="form-row">
                  <div class="select" style="width: 100%;">
                    <div class="form-holder w-100">
                      <label for="" style="color: #000;">Attribute:</label>
                      <select class="form-holder" name="attribute" style="border: none; color: #666; padding-top: 6px;">
                        <option value="<?php echo $rowa['attribute_id'] ?>" selected><?php echo $rowa['attribute_name'] ?></option>
                      </select>
                      <div class="select-control"></div>
                    </div>
                  </div>
                </div>
                <div class="form-row">
                  <div class="form-holder">
                    <label for="" style="color: #000;">Period:</label>
                    <input type="month" name="period" class="form-control" value="<?= date('Y-m') ?>" />
                  </div>
                  <center>
                    <div class="form-holder" style="align-self: flex-end; transform: translateY(4px)" <?php
                                                                                                      if ($rowa['is_valued'] == 1) {
                                                                                                        echo "hidden";
                                                                                                      }
                                                                                                      ?>>
                      <div class="checkbox-tick">
                        <label class="positive" style="color: #00f;">
                          <input type="radio" name="mark" value="+1" checked />Positive<br />
                          <span class="checkmark" style="color: #00f;"></span>
                        </label><br />
                        <!-- <label class="neutral">
                          <input type="radio" name="mark" value="0" />Neutral<br />
                          <span class="checkmark"></span>
                        </label><br /> -->
                        <label class="negative" style="color: #f00;">
                          <input type="radio" name="mark" value="-1" />Negative<br />
                          <span class="checkmark" style="color: #f00;"></span>
                        </label><br />
                        <label class="neglect" style="color: #000;">
                          <input type="radio" name="mark" value="-2" />Neglected<br />
                          <span class="checkmark" style="color: #000;"></span>
                        </label>
                      </div>
                    </div>
                  </center>
                  <div class="form-holder" <?php
                                            if ($rowa['is_valued'] == 0) {
                                              echo "hidden";
                                            }
                                            ?>>
                    <label for="" style="color: #000;">Value:</label>
                    <div class="checkbox-tick" <?php
                                                if ($rowa['scoring_method'] == 'Discipline') {
                                                  echo "hidden";
                                                } ?>>
                      <label class="neglect" style="color: #f00;">
                        <input type="radio" name="status" value="-2" onclick="hideInputField();" /> Neglected<br />
                        <span class="checkmark" style="color: #f00;"></span>
                      </label>
                      <label class="neglect" style="color: #000;">
                        <input type="radio" name="status" value="-1" onclick="hideInputField();" /> No Chance to Perform<br />
                        <span class="checkmark" style="color: #000;"></span>
                      </label>
                      <label class="neglect" style="color: #00f;">
                        <input type="radio" name="status" value="0" onclick="showInputField();" checked /> Performed<br />
                        <span class="checkmark" style="color: #00f;"></span>
                      </label>
                    </div>
                    <input type="number" step="0.001" name="value" placeholder="<?php echo $rowa['process_kpi'] ?>" class="form-control" <?php
                                                                                                                                          if ($rowa['scoring_method'] == 'Discipline') {
                                                                                                                                            echo "hidden";
                                                                                                                                          } else if ($rowa['is_valued'] == 1) {
                                                                                                                                            echo "required";
                                                                                                                                          }
                                                                                                                                          ?> />
                    <?php if ($rowa['scoring_method'] == 'Discipline') { ?>
                      <input type="number" name="lateComings" placeholder="Late Comings" class="form-control" min="0" />
                      <input type="number" name="absets" placeholder="Absenteesm" class="form-control" min="0" />
                      <input type="number" name="conducts" placeholder="Exemplary Conduct" class="form-control" min="0" />
                    <?php } ?>
                  </div>
                </div>
                <div class="form-row">
                  <div class="form-holder w-100">
                    <textarea class="form-control" name="comment" id="comment" placeholder="Comment" maxlength="45"></textarea>
                  </div>
                </div>
                <center style="padding-top: 2rem; padding-bottom: 2.5rem">
                  <input type="submit" class="form-button" name="submit" value="Submit" style="border: none; height: 2.5rem; width: 7.5rem">
                </center>
              </div>
          </section>
        </form>
      <?php }
      if (isset($_POST['update'])) {

        $empNum = $_POST['empNumber'];
        $comment = $_POST['comment'];
        // $comment->bind_param("s", $comment);
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
      } ?>

    </div>

    <?php $atId = $_GET['att'];
    $resulAt = $conn->query("SELECT * FROM attribute WHERE attribute_id ='$atId'");
    $rowAt = $resulAt->fetch_assoc(); ?>
    <center>
      <label>
        <!-- <b>Attribute ID:</b> <?php echo $rowAt['attribute_id'] ?>
        <br> -->
        <b><?php echo $rowAt['attribute_name'] ?></b>
        <br>
        <b>Weightage:</b> <?php echo $rowAt['weightage'] ?>
        <br>
        <!-- <b>Category:</b> <?php echo $rowAt['category'] ?>
        <br> -->
        <b>CSF:</b> <?php echo $rowAt['csf'] ?>
        <br>
        <b>Process Objective:</b> <?php echo $rowAt['process_objective'] ?>
        <br>
        <b>Process KPI:</b> <?php echo $rowAt['process_kpi'] ?>
        <br>
        <!-- <b>PLE:</b> <?php echo $rowAt['PLE'] ?>&nbsp;&nbsp;
        <b>OE:</b> <?php echo $rowAt['OE'] ?>&nbsp;&nbsp;
        <b>QM:</b> <?php echo $rowAt['QM'] ?>&nbsp;&nbsp;
        <b>PE:</b> <?php echo $rowAt['PE'] ?>
        <br>
        <b>Scoring Method:</b> <?php echo $rowAt['scoring_method'] ?>
        <br>
        <b>Valued or Not(Attribute):</b> <?php echo $rowAt['is_valued'] ?>
        <br> -->
      </label>
    </center>

  </body>

  <script>
    function hideInputField() {
      var valueInput = document.getElementsByName('value')[0];
      valueInput.style.display = 'none';
      valueInput.removeAttribute('required');
    }

    function showInputField() {
      var valueInput = document.getElementsByName('value')[0];
      valueInput.style.display = 'block';
      valueInput.setAttribute('required', 'required');
    }
  </script>

  </html>

<?php } else {
  header("location: signin.php");
} ?>