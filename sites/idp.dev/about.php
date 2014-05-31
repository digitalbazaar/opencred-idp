<?php
include('config.php');
include('utils/idp.php');

session_start();
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="">
    <meta name="author" content="">

    <title>About</title>

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
                <li><a href="create">Create</a></li>
                <li class="active"><a href="about">About</a></li>
              </ul>
            </div>
          </div>

          <div style="text-align: left;" class="inner cover">
            <h2>What is this site?</h2>
            <p>
This site is a technology demonstration of an Identity Credentials Personal
Identity Provider. It allows you to create and store one or more identity
documents on the site, associate credentials with each identity, and
transmit one or more of those credentials to websites that request the
credentials. The technology is based on a set of specifications that are being
developed as a part of the
<a style="text-decoration: underline;" href="https://web-payments.org/">
Web Payments Community Group</a> work at the World Wide Web Consortium.
The source code is
<a style="text-decoration: underline;"
  href="https://github.com/digitalbazaar/opencred-idp">available on Github</a>.
            </p>
            <h2>What's so special about this technology?</h2>
            <p>
The Web does not have a standards-based, privacy-aware, single sign-on solution.
It also does not have a way to prove who you are to websites in a way that's
easy and secure. The experimental technology on this website, allows you to:
<ul>
  <li>
Bypass the need to create usernames and passwords to login to websites,
replacing the login process with a far more secure mechanism based on
cryptography and digital signatures.
  </li>
  <li>
Be in control of your identities online by ensuring that you own and control
your identity data.
  </li>
  <li>
Associate digitally signed credentials such as a shipping address,
preferred payment service, driver's license, proof of age, and electronic
passports with your identity and then transmit those credentials
to websites that require the information on an as-needed basis.
  </li>
  <li>
Be in control of who can and can't track your login behavior online.
  </li>
</ul>
            <h2>Isn't this just Persona or OpenID Connect?</h2>
            <p>
Yes and no. Mozilla Persona has removed all paid engineers from the project,
so the chances of it becoming a Web standard at this point are quite low.
Persona also doesn't let you store and transmit arbitrary credential
information. OpenID Connect is complex. It is also not privacy aware and
allows the identity providers to track which sites you're visiting.
Transmitting credential information in OpenID Connect is not clearly defined.
That said, this project applauds both the Persona and OpenID work, and does
plan to provide compatability layers for each login mechanism to ensure that
the login mechanism demonstrated on this site will be compatible with
both Persona and OpenID Connect.
            </p>

            </p>
            <h2>Where can I learn more about this technology?</h2>
            <p>
Read the proposal for
<a style="text-decoration: underline;"
href="http://manu.sporny.org/2014/credential-based-login/">
Credential-based login for the Web</a>.
            </p>
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