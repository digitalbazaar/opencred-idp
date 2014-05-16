<?php
function url_origin($s, $use_forwarded_host=false) {
  $ssl = (!empty($s['HTTPS']) && $s['HTTPS'] == 'on') ? true:false;
  $sp = strtolower($s['SERVER_PROTOCOL']);
  $protocol = substr($sp, 0, strpos($sp, '/')) . (($ssl) ? 's' : '');
  $port = $s['SERVER_PORT'];
  $port = ((!$ssl && $port=='80') || ($ssl && $port=='443')) ? '' : ':'.$port;
  $host = ($use_forwarded_host && isset($s['HTTP_X_FORWARDED_HOST'])) ?
    $s['HTTP_X_FORWARDED_HOST'] : (isset($s['HTTP_HOST']) ?
    $s['HTTP_HOST'] : null);
  $host = isset($host) ? $host : $s['SERVER_NAME'] . $port;
  return $protocol . '://' . $host;
}

function full_url($s, $use_forwarded_host=false)
{
    return url_origin($s, $use_forwarded_host) . $s['REQUEST_URI'];
}

// create the identity if it doesn't already exist
if(!empty($_POST)) {
  if($_POST['name'] && $_POST['passphrase']) {
    $filename = dirname(__FILE__) . '/db/'. $_POST['name'] . '.jsonld';

    // ensure that the account doesn't already exist
    if(file_exists($filename)) {
      $error = true;
      $error_message = 'An identity with that name already exists.';
    } else {
      // initialize the identity
      $identity_url =
        strstr(full_url($_SERVER) , 'create', true) . $_POST['name'];
      $identity = array();
      $identity['@context'] = 'https://w3id.org/identity/v1';
      $identity['id'] = $identity_url;
      $identity['sysBcryptPasswordHash'] =
        password_hash($_POST['passphrase'], PASSWORD_DEFAULT);

      // write the identity file to the database
      $dbdir = dirname(__FILE__) . '/db';
      echo "DBFILE" . $dbdir;
      mkdir($dbdir, 700);
      if(!file_put_contents($filename, json_encode($identity,
        JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES), LOCK_EX)) {
        $error = true;
        $error_message = 'Could not write the identity to the filesystem. ' .
          'Make sure the webserver has write permission to the db/ directory.';
      } else {
        // set the login cookie
        session_start();
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
                <li><a href="./">Login</a></li>
                <li class="active"><a href="create">Create</a></li>
                <li><a href="admin">Admin</a></li>
              </ul>
            </div>
          </div>

          <div class="inner cover">

            <form class="form-signin" role="form" action="create" method="POST">
              <h2 class="form-signin-heading">Create</h2>
              <p class="lead">Create a new identity.</p>
              <input type="text" name="name" class="form-control" placeholder="Short name (examples: frank, julie, rufus)" required autofocus>
              <input type="text" name="passphrase" class="form-control" placeholder="Passphrase (example: 13YellowGorillasEatingCake)" required>
              <button class="btn btn-lg lead btn-primary btn-block" type="submit">Create</button>
              <?php if($error) echo '<div class="alert alert-danger">'.$error_message.'</div>' ?>
            </form>

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

