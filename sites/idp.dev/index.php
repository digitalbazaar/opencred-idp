<?php
include 'utils.php';
session_start();

// redirect to the identity page if already logged in
if($_SESSION['name']) {
  $identity_url =
    strstr(full_url($_SERVER) , 'create', true) . $_SESSION['name'];
  header('Location: '. $identity_url);
  die();
};

// attempt a login if login information was POSTed
if(!empty($_POST)) {
  if($_POST['name'] && $_POST['passphrase']) {
    $filename = dirname(__FILE__) . '/db/'. $_POST['name'] . '.jsonld';
    $identity_json = file_get_contents($filename);

    // ensure that the account doesn't already exist
    if(!$identity_json) {
      $error = true;
      $error_message = 'Login was unsuccessful, please check the name and '.
        'passphrase that you entered.';
    } else {
      $identity = json_decode($identity_json, true);
      if(!password_verify($_POST['passphrase'], $identity['sysPasswordHash'])) {
        $error = true;
        $error_message = 'Login was unsuccessful, please check the name and '.
          'passphrase that you entered.';
      } else {
        // set the login cookie
        $_SESSION['name'] = $_POST['name'];
        session_write_close();

        // redirect to the identity
        header('Location: '. $identity['id']);
        die();
      }
    }
  }
}

session_write_close();
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="">
    <meta name="author" content="">

    <title>Cover Template for Bootstrap</title>

    <!-- Latest compiled and minified CSS -->
    <link rel="stylesheet" href="//netdna.bootstrapcdn.com/bootstrap/3.1.1/css/bootstrap.min.css">

    <!-- Optional theme -->
    <link rel="stylesheet" href="//netdna.bootstrapcdn.com/bootstrap/3.1.1/css/bootstrap-theme.min.css">

    <!-- Custom styles for this template -->
    <link href="cover.css" rel="stylesheet">

    <!-- Just for debugging purposes. Don't actually copy this line! -->
    <!--[if lt IE 9]><script src="../../assets/js/ie8-responsive-file-warning.js"></script><![endif]-->

    <!-- HTML5 shim and Respond.js IE8 support of HTML5 elements and media queries -->
    <!--[if lt IE 9]>
      <script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
      <script src="https://oss.maxcdn.com/libs/respond.js/1.4.2/respond.min.js"></script>
    <![endif]-->
  </head>

  <body>

    <div class="site-wrapper">

      <div class="site-wrapper-inner">

        <div class="cover-container">

          <div class="masthead clearfix">
            <div class="inner">
              <h3 class="masthead-brand">Personal Identity Provider</h3>
              <ul class="nav masthead-nav">
                <li class="active"><a href="./">Login</a></li>
                <li><a href="create">Create</a></li>
                <li><a href="admin">Admin</a></li>
              </ul>
            </div>
          </div>

          <div class="inner cover">

            <form class="form-signin" role="form" action="" method="POST">
              <h2 class="form-signin-heading">Login</h2>
              <p class="lead">Login to your personal identity provider.</p>
              <input type="text" name="name" class="form-control" placeholder="Short name" required autofocus>
              <input type="password" name="passphrase" class="form-control" placeholder="Passphrase" required>
              <button class="btn btn-lg lead btn-primary btn-block" type="submit">Login</button>
            </form>
            <?php if($error) echo '<div class="alert alert-danger">'.$error_message.'</div>' ?>
          </div>

    <div class="container">


    </div> <!-- /container -->


          <div class="mastfoot">
            <div class="inner">
              <p>Cover template for <a href="http://getbootstrap.com">Bootstrap</a>, by <a href="https://twitter.com/mdo">@mdo</a>.</p>
            </div>
          </div>

        </div>

      </div>

    </div>

    <!-- Bootstrap core JavaScript
    ================================================== -->
    <!-- Placed at the end of the document so the pages load faster -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.0/jquery.min.js"></script>
    <!-- Latest compiled and minified JavaScript -->
    <script src="//netdna.bootstrapcdn.com/bootstrap/3.1.1/js/bootstrap.min.js"></script>
  </body>
</html>