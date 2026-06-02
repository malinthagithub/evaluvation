<?php
include "config.php";
session_start();

// delete warehouse, employees can't be there
// delete category, warehouses can't be there

if (isset($_SESSION["user"])) {
  if (isset($_GET["attId"])) {
    $id = $_GET["attId"];
    echo "<script>
    var confirmed = confirm('Are you sure you want to delete?');
    if (confirmed) {
      window.location.href = 'deleteConfirmed.php?attId=$id';
    } else {
      window.location.href = 'Attributes.php';
    }
    </script>";
  }

  if (isset($_GET["empId"])) {
    $id = $_GET["empId"];
    echo "<script>
    var confirmed = confirm('Are you sure you want to delete?');
    if (confirmed) {
      window.location.href = 'deleteConfirmed.php?empId=$id';
    } else {
      window.location.href='Employees.php';
    }
    </script>";
  }

  if (isset($_GET["disId"])) {
    $id = $_GET["disId"];
    echo "<script>
    var confirmed = confirm('Are you sure you want to disable?');
    if (confirmed) {
      window.location.href = 'deleteConfirmed.php?disId=$id';
    } else {
      window.location.href='Employees.php';
    }
    </script>";
  }

  if (isset($_GET["catId"])) {
    $id = $_GET["catId"];
    // $sql = "SELECT * FROM category WHERE category_id ='$id'";
    // $result = $conn->query($sql);
    // $row = $result->fetch_assoc();
    // $category = $row['category_name'];
    // $sqls = "SELECT * FROM store WHERE category_name ='$category'";
    // $results = $conn->query($sqls);
    // $rows = $result->fetch_assoc();
    // if ($results->num_rows > 0) {
    //   echo "<script>
    //   alert('This Category has Warehouses');
    //     window.location.href='Categories.php';
    //   </script>";
    // } else {
    echo "<script>
    var confirmed = confirm('Are you sure you want to delete?');
    if (confirmed) {
      window.location.href = 'deleteConfirmed.php?catId=$id';
    } else {
      window.location.href='Categories.php';
    }
    </script>";
    // }
  }

  if (isset($_GET["evtId"])) {
    $id = $_GET["evtId"];
    $evl = $_SESSION["id"];

    if ($_GET["evtId"] != $_SESSION["id"]) {
      echo "<script>
      var confirmed = confirm('Are you sure you want to delete?');
      if (confirmed) {
        window.location.href = 'deleteConfirmed.php?evtId=$id';
      } else {
        window.location.href='Evaluators.php';
      }
    </script>";
    } else {
      echo "<script>
      alert('Cannot delete the current user!');
      window.location.href='Evaluators.php';
    </script>";
    }
  }

  if (isset($_GET["smId"])) {
    $id = $_GET["smId"];
    echo "<script>
    var confirmed = confirm('Are you sure you want to delete?');
    if (confirmed) {
      window.location.href = 'deleteConfirmed.php?smId=$id';
    } else {
      window.location.href='ScoringMethods.php';
    }
    </script>";
  }

  if (isset($_GET["evlId"])) {
    $id = $_GET["evlId"];
    echo "<script>
      var confirmed = confirm('Are you sure you want to delete?');
      if (confirmed) {
        window.location.href = 'deleteConfirmed.php?evlId=$id';
      } else {
        history.back();
      }
    </script>";
  }
} else {
  header("location: signin.php");
}
