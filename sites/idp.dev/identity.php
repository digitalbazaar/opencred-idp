<?php
include "utils.php";
session_start();

// get the identity data
$identity = get_identity($_SESSION['name']);
$icPatch = false;
$icPatchUrl = false;
$registered = false;
$emailCredential = false;
$icResponseUrl = false;
$credentials_url = 'http://credentials.dev/';
$icResponse = false;
$error = false;
$error_message = false;

// initialize page variables based on information in the identity
if($identity) {
  if(array_key_exists('sysRegistered', $identity)) {
    $registered = true;
  }
  if(array_key_exists('credential', $identity)) {
    foreach($identity['credential'] as $credential) {
      if(array_key_exists('type', $credential) &&
        $credential['type'] === 'EmailCredential') {
        $emailCredential = true;
      }
    }
  }

  $callback_url = urlencode($identity['id'] . '?action=register&nonce=' .
  $_SESSION['nonce']);
  $registration_url = 'http://login.dev/register?identity=' .
    urlencode($identity['id']) . '&callback=' . $callback_url;
}

// generate a new nonce for the session if this isn't a POST
if(empty($_POST)) {
  $nonceSource = session_id() . microtime(true);
  $nonce = substr(hash('sha256', $nonceSource), 0, 10);
  $_SESSION['nonce'] = $nonce;
  session_write_close();
}

if(array_key_exists('action', $_GET) && $_GET['action'] === 'register') {
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
    $identity['sysRegistered'] = true;

    // store the identity
    write_identity($_SESSION['name'], $identity);

    // Add the mapping to the Telehash network
    // FIXME: curl isn't always available, use native PHP mechanism instead
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
} else if(array_key_exists('action', $_GET) &&
  $_GET['action'] === 'query' && $identity) {
  // FIXME: Re-direct to login if not already logged in
  $icResponseUrl = $_GET['callback'];
  $response = array();
  $response['@context'] = $identity['@context'];
  $response['id'] = $identity['id'];
  $response['credential'] = array();
  $query = json_decode($_POST['query'], true);

  // build the response
  foreach(array_keys($query) as $key) {
    if($key === '@context') {
      continue;
    }
    foreach($identity['credential'] as $credential) {
      if(array_key_exists($key, $credential['claim'])) {
        $response[$key] = $credential['claim'][$key];

        // add the credential information if it was requested
        if($_GET['credentials'] === 'true') {
          array_push($response['credential'], $credential);
        }
      }
    }
  }

  $icResponse = json_encode($response, JSON_UNESCAPED_SLASHES);

} else if(array_key_exists('action', $_GET) &&
  $_GET['action'] === 'patch' && $identity) {
  $icPatch = json_decode($_POST['credential'], true);
  $icPatchUrl = $identity['id'] . '?action=verify_patch';
} else if(array_key_exists('action', $_GET) &&
  $_GET['action'] === 'verify_patch' && $identity) {

  $icPatch = json_decode($_POST['credential'], true);

  // set the credential entry if it doesn't exist
  if(!array_key_exists('credential', $identity)) {
    $identity['credential'] = array();
  }

  // add the credential and write it to disk
  array_push($identity['credential'], $icPatch['value']);
  write_identity($_SESSION['name'], $identity);

  // redirect to identity page;
  header('Location: ' . $identity['id']);
  die();
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

  <body onload='checkQuery();'>

    <div class="site-wrapper">

      <div class="site-wrapper-inner">

        <div class="cover-container">

          <div class="inner cover">

            <h2 class="form-signin-heading"><?php echo $_SESSION['name']; ?></h2>

            <?php if(!$registered) echo '<div class="alert alert-warning">WARNING: This identity isn\'t active yet! The next step is to register it with the global Web login network. <a class="alert-link" href="'. $registration_url .'">Click here to register</a>.</div>' ?>
            <?php if($registered && !$emailCredential) echo '<div class="alert alert-warning">WARNING: You don\'t have an email credential yet! <a class="alert-link" href="email">Click here to get one</a>.</div>' ?>
            <?php if($registered && $emailCredential && !$icPatch) echo '<div class="alert alert-info">You may now associate credentials with this identity. <a class="alert-link" href="'. $credentials_url .'">Click here to add credentials</a>.</div>' ?>
            <?php if($error) echo '<div class="alert alert-danger">'.$error_message.'</div>' ?>

            <?php if($icPatch) echo '<p>Do you want the following credential to be stored with your identity? <div class="btn-group"><button type="button" class="btn btn-success" onclick="addCredential();">Yes</button><button type="button" class="btn btn-danger" onclick="redirectToIdentity();">No</button></div>'; ?>
            <pre style="margin-top: 10px; text-align: left;"><?php if($icPatch) echo json_encode($icPatch['value'], JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES); else echo json_encode($identity, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES); ?></pre>

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
    <script src="util.js"></script>
    <?php if($icResponseUrl) echo '<script>window.icResponseUrl = \'' . $icResponseUrl . '\';</script>'; ?>
    <?php if($icResponse) echo '<script>window.icResponse = ' . $icResponse . ';</script>'; ?>
    <?php if($icPatch) echo '<script>window.icPatch = ' . json_encode($icPatch, JSON_UNESCAPED_SLASHES) . ';</script>'; ?>
    <?php if($icPatchUrl) echo '<script>window.icPatchUrl = \'' . $icPatchUrl . '\';</script>'; ?>
    </script>
  </body>
</html>
