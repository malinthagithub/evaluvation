<?php
include "config.php";
session_start();

if (isset($_SESSION["user"])) {
  if (isset($_GET["attId"])) {
    $id = $_GET["attId"];
    $sql = "DELETE FROM attribute WHERE attribute_id = '$id'";
    $result = $conn->query($sql);

    $sqle = "DELETE FROM evaluation WHERE attribute_id = '$id'";
    $resulte = $conn->query($sqle);

    $sqlr = "DELETE FROM records WHERE attribute_id = '$id'";
    $resultr = $conn->query($sqlr);

    if ($result == TRUE && $resulte == TRUE && $resultr == TRUE) {
      echo "<script>alert('Attribute Deleted Successfully!');
      window.location.href='Attributes.php';
      </script>";
    } else {
      echo "Error:" . $sql . "<br>" . $conn->error;
    }
  }

  if (isset($_GET["empId"])) {
    $id = $_GET["empId"];
    $sql = "DELETE FROM employee WHERE emp_id = '$id'";
    $result = $conn->query($sql);

    $sqle = "DELETE FROM evaluation WHERE emp_id = '$id'";
    $resulte = $conn->query($sqle);

    $sqlr = "DELETE FROM records WHERE emp_id = '$id'";
    $resultr = $conn->query($sqlr);

    if ($result == TRUE && $resulte == TRUE && $resultr == TRUE) {
      echo "<script>alert('Employee Deleted Successfully!');
      window.location.href='Employees.php';
      </script>";
    } else {
      echo "Error:" . $sql . "<br>" . $conn->error;
    }
  }

  if (isset($_GET["disId"])) {
    $id = $_GET["disId"];
    $sql = "UPDATE employee e SET e.current_category = '', e.current_store = '' WHERE e.emp_id = '$id'";
    $result = $conn->query($sql);

    if ($result == TRUE) {
      echo "<script>alert('Employee Disabled Successfully!');
      window.location.href='Employees.php';
      </script>";
    } else {
      echo "Error:" . $sql . "<br>" . $conn->error;
    }
  }

  if (isset($_GET["catId"])) {
    $id = $_GET["catId"];

    $sqlc = "SELECT * FROM category WHERE category_id = '$id'";
    $row = $conn->query($sqlc)->fetch_assoc();

    $sql = "DELETE FROM category WHERE category_id = '$id'";
    $result = $conn->query($sql);

    $catName = $row['category_name'];
    $sqls = "DELETE FROM store WHERE category_name = '$catName'";
    $results = $conn->query($sqls);

    $sqla = "DELETE FROM attribute WHERE category = '$catName'";
    $resulta = $conn->query($sqla);

    $sqle = "DELETE FROM employee WHERE current_category = '$catName'";
    $resulte = $conn->query($sqle);

    $sqlev = "DELETE FROM evaluation WHERE category = '$catName'";
    $resultev = $conn->query($sqlev);

    $sqlr = "DELETE FROM records WHERE category = '$catName'";
    $resultr = $conn->query($sqlr);

    if ($result == TRUE && $results == TRUE && $resulta == TRUE && $resulte == TRUE && $resultev == TRUE && $resultr == TRUE) {
      echo "<script>alert('Category Deleted Successfully!');
      window.location.href='Categories.php';
      </script>";
    } else {
      echo "Error:" . $sql . "<br>" . $conn->error;
    }
  }

  if (isset($_GET["evtId"])) {
    $id = $_GET["evtId"];
    $sql = "DELETE FROM evaluator WHERE evaluator_id = '$id'";
    $result = $conn->query($sql);

    if ($result == TRUE) {
      echo "<script>alert('Evaluator Deleted Successfully!');
      window.location.href='Evaluators.php';
      </script>";
    } else {
      echo "Error:" . $sql . "<br>" . $conn->error;
    }
  }

  if (isset($_GET["smId"])) {
    $id = $_GET["smId"];
    $sql = "DELETE FROM scoring_method WHERE sm_id = '$id'";
    $result = $conn->query($sql);

    if ($result == TRUE) {
      echo "<script>alert('Scoring Method Deleted Successfully!');
      window.location.href='ScoringMethods.php';
      </script>";
    } else {
      echo "Error:" . $sql . "<br>" . $conn->error;
    }
  }

  if (isset($_GET["evlId"])) {
    $id = $_GET["evlId"];
    $sqlf = "SELECT * FROM records WHERE record_id = '$id'";
    $resultf = $conn->query($sqlf);
    $rowf = $resultf->fetch_assoc();
    $emp_id = $rowf['emp_id'];
    $period = $rowf['period'];
    $store = $rowf['store'];
    $attribute = $rowf['attribute_id'];

    if (isset($rowf['value'])) {
      $sqlr = "SELECT * FROM records WHERE emp_id = '$emp_id' and period = '$period' and store = '$store' and attribute_id = '$attribute'";
      $resultr = $conn->query($sqlr);

      if ($resultr->num_rows > 1) {
        $sqls = "SELECT * FROM evaluation WHERE emp_id = '$emp_id' and period = '$period' and store = '$store' and attribute_id = '$attribute'";
        $results = $conn->query($sqls);
        $rows = $results->fetch_assoc();
        $value = $rows['value'] * 2 - $rowf['value'];
        $sqlv = "UPDATE evaluation
        SET value = '$value'
        WHERE emp_id = '$emp_id' and period = '$period' and store = '$store' and attribute_id = '$attribute'";
        $resultv = $conn->query($sqlv);
      } else {
        $sqlv = "DELETE FROM evaluation
        WHERE emp_id = '$emp_id' and period = '$period' and store = '$store' and attribute_id = '$attribute'";
        $resultv = $conn->query($sqlv);
      }
    } else if (isset($rowf['status']) && !(isset($rowf['positive']) || isset($rowf['neutral']) || isset($rowf['negative']))) {
      $sqlv = "DELETE FROM evaluation
      WHERE emp_id = '$emp_id' and period = '$period' and store = '$store' and attribute_id = '$attribute'";
      $resultv = $conn->query($sqlv);
    } else {
      $sqls = "SELECT * FROM evaluation WHERE emp_id = '$emp_id' and period = '$period' and store = '$store' and attribute_id = '$attribute'";
      $results = $conn->query($sqls);
      $rows = $results->fetch_assoc();
      $positive = $rows['positive'] - $rowf['positive'];
      // $neutral = $rows['neutral'] - $rowf['neutral'];
      $neutral = 1;
      $negative = $rows['negative'] - $rowf['negative'];

      if (isset($rowf['status'])) {
        $sqlnv = "UPDATE evaluation
        SET positive = '0', neutral = '0', negative = '0', status = '0'
        WHERE emp_id = '$emp_id' and period = '$period' and store = '$store' and attribute_id = '$attribute'";
        $resultnv = $conn->query($sqlnv);
      } else {
        $sqlnv = "UPDATE evaluation
        SET positive = '$positive', neutral = '$neutral', negative = '$negative'
        WHERE emp_id = '$emp_id' and period = '$period' and store = '$store' and attribute_id = '$attribute'";
        $resultnv = $conn->query($sqlnv);
      }
    }

    $sql = "DELETE FROM records WHERE record_id = '$id'";
    $result = $conn->query($sql);

    if ($result == TRUE) {
      echo "<script>alert('Evaluation Deleted Successfully!');
      history.back();
      </script>";
    } else {
      echo "Error:" . $sql . "<br>" . $conn->error;
    }
  }
} else {
  header("location: signin.php");
}
