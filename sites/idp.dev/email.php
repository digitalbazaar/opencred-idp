<?php
include('config.php');
include('utils/idp.php');
session_start();

// get the identity data
$identity = get_identity($_SESSION['name']);

// create the identity if it doesn't already exist
if($identity && $_POST['email']) {
  // set the email address for the identity
  $claim = array();
  $credential = array();

  $claim['about'] = $identity['id'];
  $claim['email'] = $_POST['email'];

  $credential['type'] = 'EmailCredential';
  $credential['claim'] = $claim;

  if(!array_key_exists('credential', $identity)) {
    $identity['credential'] = array();
  }

  $signed = idp_sign($credential);
  array_push($identity['credential'], $signed);

  // write the identity file to the database
  if(!write_identity($_SESSION['name'], $identity)) {
    $error = true;
    $error_message = 'Could not write the identity to the filesystem. ' .
      'Make sure the webserver has write permission to the db/ directory.';
  } else {
    // redirect to the identity
    header('Location: '. $identity['id']);
    die();
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
              <ul class="nav masthead-nav">
                <li class="active"><a href=""><?php echo $_SESSION['name']; ?></a></li>
                <li><a href="/" onclick="logout();">Logout</a></li>
              </ul>
            </div>
          </div>

          <div class="inner cover">
            <form class="form-signin" role="form" action="email" method="POST">
              <h2 class="form-signin-heading">Issue Email Credential</h2>
              <p>
In order for you to login with this identity, you must be able to prove to
other websites that you have a valid email address. In a production deployment,
you'd use a trusted 3rd party (like your government, Google, or Facebook) to
send a code to your email address and then enter that code into a web page.
The trusted 3rd party would then digitally sign a credential stating that they
have verified your email address and you'd store that credential with your
identity. In this demo system, this step is simulated and a credential will
be issued for any email address you list below.
</p>
              <input type="text" name="email" class="form-control" placeholder="Your email address" required autofocus>
              <button class="btn btn-lg lead btn-primary btn-block" type="submit">Issue Email Credential</button>
            </form>

            <pre style="text-align: left;"><?php echo json_encode($identity, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES); ?></pre>

          </div>

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

