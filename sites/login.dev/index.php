<?php
include('config.php');

$query = false;
$response = false;

// check to make sure the site is properly setup
if(!file_exists('config.php')) {
  echo 'Error: You have not setup a config.php file for this website, please ' .
    'see the \'config.php-example\' file distributed with this software ' .
    ' for an example.';
  die();
}

if(!empty($_POST)) {
  if($_GET['action'] === 'query') {
    $request = array();
    $request['credentials'] = $_GET['credentials'];
    $request['domain'] = $_GET['domain'];
    $request['callback'] = $_GET['callback'];
    $request['query'] = json_decode($_POST['query'], true);
    $request['id'] = 'urn:sha256:' . hash('sha256',
      json_encode($request, JSON_UNESCAPED_SLASHES));

    $query = json_encode($request, JSON_UNESCAPED_SLASHES);
  } else if($_POST['response']) {
    $response = json_encode(
      json_decode($_POST['response'], true), JSON_UNESCAPED_SLASHES);
  }
};

?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="">
    <meta name="author" content="">

    <title>Login Hub</title>

    <!-- Latest compiled and minified CSS -->
    <link rel="stylesheet" href="//netdna.bootstrapcdn.com/bootstrap/3.1.1/css/bootstrap.min.css">

    <!-- Optional theme -->
    <link rel="stylesheet" href="//netdna.bootstrapcdn.com/bootstrap/3.1.1/css/bootstrap-theme.min.css">

    <!-- Custom styles for this template -->
    <link href="signin.css" rel="stylesheet">

    <!-- Just for debugging purposes. Don't actually copy this line! -->
    <!--[if lt IE 9]><script src="../../assets/js/ie8-responsive-file-warning.js"></script><![endif]-->

    <!-- HTML5 shim and Respond.js IE8 support of HTML5 elements and media queries -->
    <!--[if lt IE 9]>
      <script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
      <script src="https://oss.maxcdn.com/libs/respond.js/1.4.2/respond.min.js"></script>
    <![endif]-->
  </head>

  <body onload="init();">

    <div class="container">

      <form class="form-signin" role="form" onsubmit="login();">
        <h2 class="form-signin-heading">Login Hub</h2>
        <p>
You are logging into this website using the global Web login system. This system
is used to discover your identity provider using an email and passphrase
that you have previously registered with this system. The system also
protects your privacy by ensuring that your identity provider can't track the
websites that you are logging in to.
        </p>
        <input id="email" type="email" class="form-control" placeholder="Email address (example: bob@gmail.com)" required autofocus>
        <input id="passphrase" type="password" class="form-control" placeholder="Passphrase (example: GreenHeartsBrave55Stars)" required>
        <button class="btn btn-lg btn-primary btn-block" type="submit">Login</button>
        <p id="errorFeedback" style="display: none;" class="alert alert-danger"></p>
        <p style="padding-top: 10px; text-align: center;"><a href="about">Find out more about this website</a></p>
        <p style="display: none;" class="alert alert-error"></p>
      </form>

    </div> <!-- /container -->

    <!-- Bootstrap core JavaScript
    ================================================== -->
    <!-- Placed at the end of the document so the pages load faster -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.0/jquery.min.js"></script>
    <!-- Latest compiled and minified JavaScript -->
    <script src="//netdna.bootstrapcdn.com/bootstrap/3.1.1/js/bootstrap.min.js"></script>
    <script src="async.min.js"></script>
    <script src="scrypt.min.js"></script>
    <script src="forge.min.js"></script>
    <script src="telehash.min.js"></script>
    <script src="login.js"></script>
    <?php if($query) echo '<script>window.icRequest = ' . $query . '</script>'; ?>
    <?php if($response) echo '<script>window.icResponse = ' . $response . ';</script>'; ?>
    <script>
      window.icOptions = {
        loginSite: '<?php echo $GLOBALS['login_site']; ?>'
      };
    </script>

  </body>
</html>
