<html>

<head>
  <title>Password Reset</title>
  <link rel="icon" type="image/x-icon" href="./img/jb.png">

  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta1/dist/css/bootstrap.min.css" />
  <link rel="stylesheet" href="css/style.css" />
</head>

<?php
require_once('config.php');
if (isset($_REQUEST['pwdrst'])) {
  $email = $_REQUEST['email'];
  $pwd = md5($_REQUEST['pwd']);
  $cpwd = md5($_REQUEST['cpwd']);
  if ($pwd == $cpwd) {
    $reset_pwd = mysqli_query($conn, "UPDATE evaluator SET password='$pwd' WHERE email='$email'");
    if ($reset_pwd > 0) {
      $msg = 'Your password updated successfully <a href="index.php">Click here</a> to login';
    } else {
      $msg = "Error while updating password.";
    }
  } else {
    $msg = "Password and Confirm Password do not match";
  }
}

if ($_GET['secret']) {
  $email = base64_decode($_GET['secret']);
  $check_details = mysqli_query($conn, "SELECT email FROM evaluator WHERE email='$email'");
  $res = mysqli_num_rows($check_details);
  if ($res > 0) { ?>

    <body style="background-color: #A9D6E599;">
      <div style="background-color: #f9f6f1; margin: 10vh; padding: 5vh;">
        <div>
          <center>
            <h3>Reset Password</h3>
          </center><br />
          <!-- <div class="box"> -->
          <form id="validate_form" method="POST">
            <input type="hidden" name="email" value="<?php echo $email; ?>" />
            <div class="form-group">
              <label for="pwd" style="font-size: 16px; font-weight: bold; color: #666; padding: 2px;">Password</label>
              <input type="password" name="pwd" id="pwd" placeholder="Enter Password" required data-parsley-type="pwd" data-parsley-trigg er="keyup" class="form-control" />
            </div>
            <div class="form-group">
              <label for="cpwd" style="font-size: 16px; font-weight: bold; color: #666; padding: 2px;">Confirm Password</label>
              <input type="password" name="cpwd" id="cpwd" placeholder="Enter Confirm Password" required data-parsley-type="cpwd" data-parsley-trigg er="keyup" class="form-control" />
            </div><br />
            <div class="form-group">
              <input type="submit" id="login" name="pwdrst" value="Reset Password" class="btn btn-success" />
            </div>

            <p class="error"><?php if (!empty($msg)) {
                                echo $msg;
                              } ?></p>
          </form>
          <!-- </div> -->
        </div>
      </div>
  <?php }
} ?>
    </body>

</html>