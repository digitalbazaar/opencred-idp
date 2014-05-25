<?php
include 'utils.php';
session_start();

print_r(array_keys($_POST));

if(!empty($_SESSION) && !empty($_POST) && $_GET['type']) {
  // set the email address for the identity
  $claim = array();
  $credential = array();

  $claim['about'] = $_SESSION['id'];

  if($_GET['type'] === 'BusinessAddressCredential') {
    $address = array();
    $address['type'] = 'BusinessAddress';
    $address['streetAddress'] = $_POST['streetAddress'];
    $address['addressLocality'] = $_POST['addressLocality'];
    $address['addressRegion'] = $_POST['addressRegion'];
    $address['postalCode'] = $_POST['postalCode'];
    $address['addressCountry'] = $_POST['addressCountry'];
    $claim['address'] = $address;
  }

  $credential['type'] = $_GET['type'];
  $credential['claim'] = $claim;

  $signed = credential_sign($credential);

  echo "<pre>" . json_encode($signed, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES) . "</pre>";
}
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <script src="issue.js"></script>
  </head>

  <body>
  </body>
</html>

