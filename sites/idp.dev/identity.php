<?php
include "utils.php";
session_start();

// get the identity data
$identity = get_identity($_SESSION['name']);
$registered = false;

// generate a new nonce for the session if this isn't a POST
if(empty($_POST)) {
  $nonceSource = session_id() . microtime(true);
  $nonce = substr(hash('sha256', $nonceSource), 0, 10);
  $_SESSION['nonce'] = $nonce;
  session_write_close();
}

if($_GET['action'] === 'register') {
  if($_GET['nonce'] !== $_SESSION['nonce']) {
    $error = true;
    $error_message = 'The nonce associated with the request is invalid. ' .
      'Please try registering again.';
  } else {
    $message = json_decode($_POST['message'], true);
    if(!array_key_exists('sysIdpMapping', $identity)) {
      $identity['sysIdpMapping'] = array();
    }
    if(!array_key_exists('sysDeviceKeys', $identity)) {
      $identity['sysDeviceKeys'] = array();
    }

    // append the device key to the set of known device keys
    $deviceKey = array();
    $deviceKey['publicKeyPem'] = $message['publicKeyPem'];
    array_push($identity['sysDeviceKeys'], $deviceKey);

    // append the IdP mapping to the list of known mappings
    unset($message['publicKeyPem']);
    array_push($identity['sysIdpMapping'], $message);

    $identity['sysDeviceKeys'] =
      array_unique($identity['sysDeviceKeys'], SORT_REGULAR);
    $identity['sysIdpMapping'] =
      array_unique($identity['sysIdpMapping'], SORT_REGULAR);

    // store the identity
    write_identity($_SESSION['name'], $identity);

    // Add the mapping to the Telehash network
    $curl = curl_init('http://localhost:42425/register');
    curl_setopt($curl, CURLOPT_HEADER, false);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_HTTPHEADER,
      array("Content-type: application/ld+json"));
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $_POST['message']);
    $response = curl_exec($curl);
    $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    if($status != 201) {
      // FIXME: handle errors where the mapping wasn't added to Telehash
    }
    curl_close($curl);

    // FIXME: Make sure to set - $identity['sysRegistered'] = true;
    $registered = true;
  }
} else if($identity) {
  if(array_key_exists('sysRegistered', $identity)) {
    $registered = true;
  }

  $callback_url = urlencode($identity['id'] . '?action=register&nonce=' .
    $_SESSION['nonce']);
  $registration_url = 'http://login.dev/register?identity=' .
    urlencode($identity['id']) . '&callback=' . $callback_url;
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
              <?php if(!$registered) echo '<div class="alert alert-warning">WARNING: This identity isn\'t active yet! The next step is to register it with the global Web login network. <a class="alert-link" href="'. $registration_url .'">Click here to register</a>.</div>' ?>
              <?php if($error) echo '<div class="alert alert-danger">'.$error_message.'</div>' ?>
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

