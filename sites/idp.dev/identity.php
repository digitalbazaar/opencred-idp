<?php
session_start();

// get the identity data
$filename = dirname(__FILE__) . '/db/'. $_SESSION['name'] . '.jsonld';
$identity_json = file_get_contents($filename);
$identity = array();
$registered = false;

if($identity_json) {
  $identity = json_decode($identity_json, true);

  if(array_key_exists('sysRegistered', $identity)) {
    $registered = true;
  }

  $registration_url = 'http://login.dev/register.html?identity=' .
    urlencode($identity['id']);
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

    <title>Identity for <?php echo $_SESSION['name']; ?></title>

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
              <?php if(!$registered) echo '<div class="alert alert-warning">The next step is to register this identity with the global identity network. <a class="alert-link" href="'. $registration_url .'">Click here to register</a>.</div>' ?>
            </div>
          </div>

        <div class="cover-container">

          <div class="masthead clearfix">
            <div class="inner">
              <h3 class="masthead-brand"></h3>
            </div>
          </div>

          <div class="inner cover">

            <h2 class="form-signin-heading"><?php echo $_SESSION['name']; ?></h2>
            <pre style="text-align: left;"><?php echo $identity_json; ?></pre>

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

