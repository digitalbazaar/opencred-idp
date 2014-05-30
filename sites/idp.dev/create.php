<?php
include('config.php');
include('utils/idp.php');

session_start();

if($_SESSION['name']) {
  $identity_url =
    strstr(full_url($_SERVER) , 'create', true) . $_SESSION['name'];
  header('Location: '. $identity_url);
  die();
};

// create the identity if it doesn't already exist
if(!empty($_POST)) {
  if($_POST['name'] && $_POST['passphrase']) {
    // ensure that the account doesn't already exist
    if(get_identity($_POST['name'])) {
      $error = true;
      $error_message = 'An identity with that name already exists.';
    } else if($_POST['passphrase'] !== $_POST['passphrase_verify']) {
      $error = true;
      $error_message = 'The passphrases you entered were not the same. ' .
        'Both the passphrase and the passphrase verification should be the ' .
        'same';
    } else {
      // initialize the identity
      $identity_url =
        strstr(full_url($_SERVER) , 'create', true) . $_POST['name'];
      $identity = array();
      $identity['@context'] = 'https://w3id.org/identity/v1';
      $identity['id'] = $identity_url;
      $identity['sysPasswordHash'] =
        password_hash($_POST['passphrase'], PASSWORD_DEFAULT);

      // write the identity file to the database
      if(!write_identity($_POST['name'], $identity)) {
        $error = true;
        $error_message = 'Could not write the identity to the filesystem. ' .
          'Make sure the webserver has write permission to the db/ directory.';
      } else {
        // set the login cookie
        $_SESSION['name'] = $_POST['name'];
        session_write_close();

        // redirect to the identity
        header('Location: '. $identity_url);
        die();
      }
    }
  }
}
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="">
    <meta name="author" content="">

    <title>Create Identity</title>

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
                <li><a href="./">Home</a></li>
                <li class="active"><a href="create">Create</a></li>
                <li><a href="about">About</a></li>
              </ul>
            </div>
          </div>

          <div class="inner cover">

            <form class="form-signin" role="form" action="create" method="POST">
              <h2 class="form-signin-heading">Create</h2>
              <p class="lead">Create a new identity.</p>
              <input type="text" name="name" class="form-control" placeholder="Short name (examples: frank, julie, rufus)" required autofocus>
              <input type="password" name="passphrase" class="form-control" placeholder="Passphrase (example: 13YellowGorillasEatingCake)" required>
              <input type="password" name="passphrase_verify" class="form-control" placeholder="Verify your passphrase above" required>
              <button class="btn btn-lg lead btn-primary btn-block" type="submit">Create</button>
              <?php if($error) echo '<div class="alert alert-danger">'.$error_message.'</div>' ?>
            </form>

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

