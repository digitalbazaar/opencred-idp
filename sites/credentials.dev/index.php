<?php
include 'utils.php';
session_start();

$id = false;
$email = false;

if(!empty($_SESSION)) {
  $id = $_SESSION['id'];
  $email = $_SESSION['email'];
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

  <body><div class="site-wrapper"><div class="site-wrapper-inner"><div class="cover-container">

    <div class="masthead clearfix">
      <div class="inner">
        <h3 class="masthead-brand">Credential Issuer</h3>
        <ul class="nav masthead-nav">
          <?php if(!$email) echo '<li class="active"><a href="#" onclick="login();">Login</a></li>' ?>
          <?php if($email) echo '<li>' . $email; if($id) echo ' (' . $id . ')'; echo '</li>' ?>
        </ul>
      </div>
    </div>

    <div class="inner cover">
      <p>
This website is used to issue credentials to you. A credential is
information that is associated with you like a home address, birthday,
driver's license, or government ID. Typically, this information would be issued
to you by a trusted 3rd party organization. The digital signature on
the credential that is issued to you could be checked by a receiving party to
ensure that the information was assigned to you by a trustworthy organization.
This website is for demonstration purposes, and so the digital signature on
the credential that will be assigned to you is meaningless except for
demonstration purposes.
      </p>
      <div class="panel-group" id="accordion">
        <div class="panel panel-default">
          <div class="panel-heading">
            <h4 class="panel-title">
              <a data-toggle="collapse" data-parent="#accordion" href="#collapseOne">
                Billing Address
              </a>
            </h4>
          </div>
          <div id="collapseOne" class="panel-collapse collapse in">
            <div class="panel-body">
              <p>
A billing address credential is typically assigned to you by an organization
like the United States Postal Service. It asserts that bills requesting
payment may be sent to this address. Since this is a demonstration service,
we will not try to verify your home address before issuing the credential to
you.
              </p>
              <form class="form-horizontal" role="form" onsubmit="requestCredential('address');">
                <div class="form-group">
                  <label class="col-sm-3 control-label" for="streetAddress">Street Address</label>
                  <div class="col-sm-8"><input id="streetAddress" type="text" class="form-control" placeholder="Street address" required value="123 Fake Street"></div>
                </div>
                <div class="form-group">
                  <label class="col-sm-3 control-label" for="addressLocality">Locality</label>
                  <div class="col-sm-8"><input id="addressLocality" type="text" class="form-control" placeholder="City or Town" required value="Smallville"></div>
                </div>
                <div class="form-group">
                  <label class="col-sm-3 control-label" for="addressRegion">Region</label>
                  <div class="col-sm-8"><input id="addressRegion" type="text" class="form-control" placeholder="State or Region" required value="Statey"></div>
                </div>
                <div class="form-group">
                  <label class="col-sm-3 control-label" for="postalCode">Postal Code</label>
                  <div class="col-sm-8"><input id="postalCode" type="text" class="form-control" placeholder="Postal code" required value="93042-0492"></div>
                </div>
                <div class="form-group">
                  <label class="col-sm-3 control-label" for="addressCountry">Country</label>
                  <div class="col-sm-8"><input id="addressCountry" type="text" class="form-control" placeholder="Country" required value="Testlandia"></div>
                </div>
                <button class="btn btn-lg btn-primary btn-block" type="submit">Issue Address Credential</button>
              </form>
            </div>
          </div>
        </div>
        <div class="panel panel-default">
          <div class="panel-heading">
            <h4 class="panel-title">
              <a data-toggle="collapse" data-parent="#accordion" href="#collapseTwo">
                Age Verification
              </a>
            </h4>
          </div>
          <div id="collapseTwo" class="panel-collapse collapse">
            <div class="panel-body">
              <p>
An age verification is a privacy-aware credential that let's a receiver know
that you are at least a certain age without divulging your birth date. This
type of credential is typically assigned to you by a governmental organization
like the Social Security Administration. Since this is a demonstration service,
we will not try to verify your age before issuing the credential to
you.
              </p>
              <form class="form-horizontal" role="form" onsubmit="requestCredential('age');">
                <div class="form-group">
                  <label class="col-sm-3 control-label" for="ageProof">Proof of Age</label>
                  <div class="col-sm-8"><input id="ageProof" type="text" class="form-control" placeholder="You are at least this many years old" required value="18"></div>
                </div>
                <button class="btn btn-lg btn-primary btn-block" type="submit">Issue Age Credential</button>
              </form>
            </div>
          </div>
        </div>
        <div class="panel panel-default">
          <div class="panel-heading">
            <h4 class="panel-title">
              <a data-toggle="collapse" data-parent="#accordion" href="#collapseThree">
                Driver's License
              </a>
            </h4>
          </div>
          <div id="collapseThree" class="panel-collapse collapse">
            <div class="panel-body">
              <p>
A drivers license credential is typically assigned to you by an organization
like your region's Department of Motor Vehicles. It asserts that you are
qualified to operate a motor vehicle in your country. Since this is a
demonstration service, we will not try to verify your driving ability or
home address before issuing the credential to you.
              </p>
              <form class="form-horizontal" role="form" onsubmit="requestCredential('driversLicense');">
                <div class="form-group">
                  <label class="col-sm-3 control-label" for="customerNumber">Customer Number</label>
                  <div class="col-sm-8"><input id="customerNumber" type="text" class="form-control" placeholder="The organization ID number for you" required value="Y84-12-9372"></div>
                </div>
                <div class="form-group">
                  <label class="col-sm-3 control-label" for="name">Name</label>
                  <div class="col-sm-8"><input id="name" type="text" class="form-control" placeholder="Your full name" required value="John Doe"></div>
                </div>
                <div class="form-group">
                  <label class="col-sm-3 control-label" for="gender">Gender</label>
                  <div class="col-sm-8"><input id="gender" type="text" class="form-control" placeholder="'Male' or 'Female'" required value="Male"></div>
                </div>
                <div class="form-group">
                  <label class="col-sm-3 control-label" for="birthday">Date of Birth</label>
                  <div class="col-sm-8"><input id="birthday" type="text" class="form-control" placeholder="When you were born (YYYY/MM/DD)" required value="1975/04/22"></div>
                </div>
                <div class="form-group">
                  <label class="col-sm-3 control-label" for="eyeColor">Eye Color</label>
                  <div class="col-sm-8"><input id="eyeColor" type="text" class="form-control" placeholder="The color of your eyes" required value="Brown"></div>
                </div>
                <div class="form-group">
                  <label class="col-sm-3 control-label" for="hairColor">Hair Color</label>
                  <div class="col-sm-8"><input id="hairColor" type="text" class="form-control" placeholder="The color of your hair" required value="Black"></div>
                </div>
                <div class="form-group">
                  <label class="col-sm-3 control-label" for="height">Height</label>
                  <div class="col-sm-8"><input id="height" type="text" class="form-control" placeholder="How tall you are in feet and inches" required value="5' 5&quot;"></div>
                </div>
                <div class="form-group">
                  <label class="col-sm-3 control-label" for="driverClass">Class</label>
                  <div class="col-sm-8"><input id="driverClass" type="text" class="form-control" placeholder="Vehicle classes that you are allowed to drive" required value="Car, Motorcycle"></div>
                </div>
                <div class="form-group">
                  <label class="col-sm-3 control-label" for="driverRestrictions">Restrictions</label>
                  <div class="col-sm-8"><input id="driverRestrictions" type="text" class="form-control" placeholder="Any restrictions when you drive?" required value="Corrective Lenses (20/50 vision)"></div>
                </div>
                <div class="form-group">
                  <label class="col-sm-3 control-label" for="organDonor">Organ Donor</label>
                  <div class="col-sm-8"><input id="organDonor" type="text" class="form-control" placeholder="'Yes', if you would like to donate your organs if you die" required value="Yes"></div>
                </div>
                <div class="form-group">
                  <label class="col-sm-3 control-label" for="streetAddress">Street Address</label>
                  <div class="col-sm-8"><input id="streetAddress" type="text" class="form-control" placeholder="Street address" required value="123 Fake Street"></div>
                </div>
                <div class="form-group">
                  <label class="col-sm-3 control-label" for="addressLocality">Locality</label>
                  <div class="col-sm-8"><input id="addressLocality" type="text" class="form-control" placeholder="City or Town" required value="Smallville"></div>
                </div>
                <div class="form-group">
                  <label class="col-sm-3 control-label" for="addressRegion">Region</label>
                  <div class="col-sm-8"><input id="addressRegion" type="text" class="form-control" placeholder="State or Region" required value="Statey"></div>
                </div>
                <div class="form-group">
                  <label class="col-sm-3 control-label" for="postalCode">Postal Code</label>
                  <div class="col-sm-8"><input id="postalCode" type="text" class="form-control" placeholder="Postal code" required value="93042-0492"></div>
                </div>
                <div class="form-group">
                  <label class="col-sm-3 control-label" for="addressCountry">Country</label>
                  <div class="col-sm-8"><input id="addressCountry" type="text" class="form-control" placeholder="Country" required value="Testlandia"></div>
                </div>
                <div class="form-group">
                  <label class="col-sm-3 control-label" for="created">Issued</label>
                  <div class="col-sm-8"><input id="created" type="text" class="form-control" placeholder="Date the license was issued (YYYY/MM/DD)" required value="2014-02-11"></div>
                </div>
                <div class="form-group">
                  <label class="col-sm-3 control-label" for="expires">Expires</label>
                  <div class="col-sm-8"><input id="expires" type="text" class="form-control" placeholder="Date the license expires (YYYY/MM/DD)" required value="2018-02-11"></div>
                </div>
                <button class="btn btn-lg btn-primary btn-block" type="submit">Issue Driver's License Credential</button>
              </form>
            </div>
          </div>
        </div>
        <div class="panel panel-default">
          <div class="panel-heading">
            <h4 class="panel-title">
              <a data-toggle="collapse" data-parent="#accordion" href="#collapseFour">
                Passport
              </a>
            </h4>
          </div>
          <div id="collapseFour" class="panel-collapse collapse">
            <div class="panel-body">
              <p>
A passport credential is typically assigned to you by a national government
like the United States of America. It asserts that you have the proper
documents necessary to travel to other countries. Since this is a demonstration
service, we will not try to verify your travel eligibility with your
government before issuing the credential to you.
              </p>
              <form class="form-horizontal" role="form" onsubmit="requestCredential('passport');">
                <div class="form-group">
                  <label class="col-sm-3 control-label" for="passportNumber">Passport Number</label>
                  <div class="col-sm-8"><input id="passportNumber" type="text" class="form-control" placeholder="Your passport ID number" required value="837847822"></div>
                </div>
                <div class="form-group">
                  <label class="col-sm-3 control-label" for="familyName">Family Name</label>
                  <div class="col-sm-8"><input id="familyName" type="text" class="form-control" placeholder="Your family (last) name" required value="Doe"></div>
                </div>
                <div class="form-group">
                  <label class="col-sm-3 control-label" for="givenName">Given Names</label>
                  <div class="col-sm-8"><input id="givenName" type="text" class="form-control" placeholder="Your given (first) name" required value="John Quincy"></div>
                </div>
                <div class="form-group">
                  <label class="col-sm-3 control-label" for="gender">Gender</label>
                  <div class="col-sm-8"><input id="gender" type="text" class="form-control" placeholder="Your sex ('Male' or 'Female')" required value="Male"></div>
                </div>
                <div class="form-group">
                  <label class="col-sm-3 control-label" for="nationality">Nationality</label>
                  <div class="col-sm-8"><input id="nationality" type="text" class="form-control" placeholder="The nation of your citizenship" required value="United States of America"></div>
                </div>
                <div class="form-group">
                  <label class="col-sm-3 control-label" for="birthplace">Place of Birth</label>
                  <div class="col-sm-8"><input id="birthplace" type="text" class="form-control" placeholder="The country where you were born" required value="Canada"></div>
                </div>
                <div class="form-group">
                  <label class="col-sm-3 control-label" for="created">Issued</label>
                  <div class="col-sm-8"><input id="created" type="text" class="form-control" placeholder="Date the passport was issued (YYYY/MM/DD)" required value="2014-03-25"></div>
                </div>
                <div class="form-group">
                  <label class="col-sm-3 control-label" for="expires">Expires</label>
                  <div class="col-sm-8"><input id="expires" type="text" class="form-control" placeholder="Date the passport expires (YYYY/MM/DD)" required value="2024-03-25"></div>
                </div>
                <button class="btn btn-lg btn-primary btn-block" type="submit">Issue Passport Credential</button>
              </form>
            </div>
          </div>
        </div>
      </div>

      <?php if($error) echo '<div class="alert alert-danger">'.$error_message.'</div>' ?>
    </div>

  </div></div></div>

    <!-- Bootstrap core JavaScript
    ================================================== -->
    <!-- Placed at the end of the document so the pages load faster -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.0/jquery.min.js"></script>
    <!-- Latest compiled and minified JavaScript -->
    <script src="//netdna.bootstrapcdn.com/bootstrap/3.1.1/js/bootstrap.min.js"></script>
    <script src="navigator.identity.js"></script>
    <script src="login.js"></script>
  </body>
</html>