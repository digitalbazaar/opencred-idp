<?php
include('config.php');
include('utils/credential.php');

session_start();

$icPatch = false;
$icWriteUrl = false;

if(!empty($_SESSION) && !empty($_POST) && $_GET['type']) {
  // set the email address for the identity
  $claim = array();
  $credential = array();

  $claim['about'] = $_SESSION['id'];

  if($_GET['type'] === 'BillingAddressCredential') {
    // build the billing address credential
    $address = array();
    $address['type'] = 'BillingAddress';
    $address['streetAddress'] = $_POST['streetAddress'];
    $address['addressLocality'] = $_POST['addressLocality'];
    $address['addressRegion'] = $_POST['addressRegion'];
    $address['postalCode'] = $_POST['postalCode'];
    $address['addressCountry'] = $_POST['addressCountry'];
    $claim['address'] = $address;
  } else if($_GET['type'] === 'PaymentProcessorCredential') {
    // build the payment processor credential
    $claim['paymentProcessor'] = $_POST['paymentProcessor'];
  } else if($_GET['type'] === 'ProofOfAgeCredential') {
    // build the proof of age credential
    $claim['proofOfAge'] = $_POST['proofOfAge'];
  } else if($_GET['type'] === 'DriversLicenseCredential') {
    // build the driver's license credential
    $claim['customerNumber'] = $_POST['customerNumber'];
    $claim['name'] = $_POST['name'];
    $claim['gender'] = $_POST['gender'];
    $claim['birthday'] = $_POST['birthday'];
    $claim['eyeColor'] = $_POST['eyeColor'];
    $claim['hairColor'] = $_POST['hairColor'];
    $claim['height'] = $_POST['height'];
    $claim['driverClass'] = $_POST['driverClass'];
    $claim['driverRestrictions'] = $_POST['driverRestrictions'];
    $claim['organDonor'] = $_POST['organDonor'];
    $credential['issued'] = $_POST['issued'];
    $credential['expires'] = $_POST['expires'];
    $address = array();
    $address['type'] = 'HomeAddress';
    $address['streetAddress'] = $_POST['streetAddress'];
    $address['addressLocality'] = $_POST['addressLocality'];
    $address['addressRegion'] = $_POST['addressRegion'];
    $address['postalCode'] = $_POST['postalCode'];
    $address['addressCountry'] = $_POST['addressCountry'];
    $claim['address'] = $address;
  } else if($_GET['type'] === 'PassportCredential') {
    // build the passport credential
    $claim['passportNumber'] = $_POST['passportNumber'];
    $claim['familyName'] = $_POST['familyName'];
    $claim['givenName'] = $_POST['givenName'];
    $claim['gender'] = $_POST['gender'];
    $claim['nationality'] = $_POST['nationality'];
    $claim['birthplace'] = $_POST['birthplace'];
    $credential['issued'] = $_POST['issued'];
    $credential['expires'] = $_POST['expires'];
  }

  $credential['type'] = $_GET['type'];
  $credential['claim'] = $claim;

  // sign the credential
  $signed = credential_sign($credential);

  // create the patch command for the identity
  $patch = array();
  $patch['op'] = 'add';
  $patch['path'] = 'https://w3id.org/identity#credential';
  $patch['value'] = $signed;

  // set the identity write URL command
  $icWriteUrl = $_SESSION['id'] . '?action=patch';

  $icPatch = json_encode($patch, JSON_UNESCAPED_SLASHES);
}
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <script>
      <?php if($icPatch) echo 'window.icPatch = '. $icPatch .';'; ?>
      <?php if($icWriteUrl) echo 'window.icWriteUrl = \''. $icWriteUrl .'\';'; ?>
    </script>
    <script src="issue.js"></script>
  </head>

  <body onload="checkCredential();">
  </body>
</html>

