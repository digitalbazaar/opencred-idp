<?php
include('config.php');
include('utils/credential.php');

session_start();

$credentials = false;
$credentials_json = false;

// attempt a login if login information was POSTed
if(!empty($_POST)) {
  $credentials = json_decode($_POST['response'], true);
  $credentials_json =
    json_encode($credentials, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);

  // update the session information
  $_SESSION['id'] = $credentials['id'];
  if(array_key_exists('email', $credentials)) {
    $_SESSION['email'] = $credentials['email'];
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

    <title>Credential Issuer</title>

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

            <h2 class="form-signin-heading">Login Successful</h2>
            <p>
Your login was successful, you may now go and
<a style="text-decoration: underline" href="./">
issue credentials to yourself</a>.
            </p>
            <p>
These are the login credentials we received from your identity provider:</p>
            <pre style="text-align: left;"><?php echo $credentials_json; ?></pre>
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