<?php

require_once('./config.php');
require_once('./bedrock.php');

// dev mode JSON-LD context
function dev_jsonld_load_document($url) {
	if($url === 'https://w3id.org/bedrock/v1') {
	  return (object)array(
	    'contextUrl' => null,
	    'document' => (object)array(
	      '@context' => bedrock_create_default_jsonld_context()),
	    'documentUrl' => $url
	  );
	}
	return jsonld_default_document_loader($url);
}
jsonld_set_document_loader('dev_jsonld_load_document');

function check_domain($domain, $options) {
  return ($domain === 'examples.bedrockphp.dev');
}

function check_key_owner($owner, $key, $options) {
  try {
    // parse JSON-LD identity service config
    $cfg = jsonld_decode($_COOKIE['session']);
    // ensure owner matches identity service owner
    return $cfg->identityService->owner === $owner->id;
  } catch(Exception $e) {
    return false;
  }
}

// see if an identity is available
if(isset($_POST['identity'])) {
  ?>
<html>
  <body>
    <script src="/navigator.identity.js"></script>
    <script src="/login/login.js"></script>
  <?php

  $identity = $_POST['identity'];

  // make sure to remove magic quotes if in use
  if(get_magic_quotes_gpc()) {
    $identity = stripcslashes($identity);
  }

  /* Note: Check signature on the identity, ensure the domain in the message
  matches the local domain. Checking the signature involves ensuring the
  owner of the key for the signature is the same at the identity listed by
  IdP's identity service. If the key is not IdP's key, then a fallback can
  be done tolook for the key in the credentials to see if it's signed by the
  IdP's key (if so, then use that key from the credentials and its included
  publicKeyPem, do not fetch it in order to preserve privacy). */

  $options = array(
    'check_domain' => 'check_domain',
    'check_key_owner' => 'check_key_owner',
    'ssl' => array('verify_peer' => false));

  // TODO: check the credential signatures (none for this example)
  $url = '/login/index.php?';
  $identity = jsonld_decode($identity);
  try {
    $verified = bedrock_verify($identity, $options);
    if(property_exists($identity, 'id')) {
      $url .= 'id=' . rawurlencode($identity->id);
    } else {
      $url .= 'error=denied';
    }
  } catch(Exception $e) {
    $url .= 'error=invalid';
  }

  ?>
    <script>
      // display that the login was successful/denied and show the ID URL
      closePopup('<?php echo $url; ?>');
    </script>
  <?php
}
else {
  // store idp config in cookie
  $idp = $_POST['idp'];
  $cfg = bedrock_get_endpoint_config($idp, array(
    'service' => 'identity',
    'ssl' => array('verify_peer' => false)));
  $now = date_create('now', new DateTimeZone('UTC'))->getTimeStamp();
  setcookie('session', jsonld_encode($cfg), $now + 3600);
  ?>
<html>
  <body>
    <script src="/navigator.identity.js"></script>
    <script src="/login/login.js"></script>

    <script>login('<?php echo $cfg->identityService->id; ?>');</script>
  <?php
}
?>
  </body>
</html>
