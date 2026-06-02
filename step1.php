<?php
include "config.php";
session_start();

if (isset($_SESSION["user"])) {
  if ($_SESSION["isAdmin"] || $_SESSION["isEvaluator"]) {

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

    if (isset($_POST['submit'])) {

      if ($_POST['empName'] != "" or $_POST['empNumber'] != "") {
        $empName = $_POST['empName'];
        $empNumber = $_POST['empNumber'];

        if ($empName == "") {
          $sql = "SELECT * FROM employee WHERE (emp_id = '$empNumber')";
          $result = $conn->query($sql);
        } else if ($empNumber == "") {
          $sql = "SELECT * FROM employee WHERE (emp_name LIKE '%$empName%')";
          $result = $conn->query($sql);
        } else {
          $sql = "SELECT * FROM employee WHERE (emp_id = '$empNumber') OR (emp_name LIKE '%$empName%')";
          $result = $conn->query($sql);
        }

        $row = $result->fetch_assoc();
        $id = $row['emp_id'];

        if ($result == TRUE && $id != "") {
          header("location: step2.php?id=$id");
        } else {
          // echo "Error:" . $sql . "<br>" . $conn->error;
          echo "<script>alert('Can\'t Find Maching Recored');
        window.location.href='step1.php';
        </script>";
        }
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
          var letters = /^[A-Z./:;!`"|<>_-?+@#%^&*~, a-z0-9]+$/;
          if (!document.addForm.empNumber.value.match(letters)) {
            alert("Containing Invalid Characters in Employee Number");
            return false;
          }
          if (!document.addForm.empName.value.match(letters)) {
            alert("Containing Invalid Characters in Employee Name");
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
        <form class="editForm" name="addForm" enctype="multipart/form-data" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" onsubmit="return validate()" method="POST" id="wizard" style="width: 125vh">
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
                    <input type="text" name="empNumber" placeholder="Emp Number" class="form-control" />
                  </div>
                </div>
                <div class="form-row">
                  <div class="form-holder w-100">
                    <input type="text" name="empName" placeholder="Employee Name" class="form-control" />
                  </div>
                </div>
                <center style="padding-top: 2rem; padding-bottom: 2.5rem">
                  <button type="submit" class="form-button" name="submit" style="border: none; height: 2.5rem; width: 7.5rem"><i class='bx bx-search-alt-2'></i>&nbsp;&nbsp;Find</button>
                </center>
              </div>
          </section>
        </form>
      </div>

    </body>

    </html>
  <?php } else {
    echo "<script>
    history.back();
  </script>";
  } ?>
<?php } else {
  header("location: signin.php");
} ?>