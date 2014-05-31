<?php
include('../config.php');
include('bedrock.php');

$TOPDIR = realpath(dirname(__FILE__) . '/..');

// check to make sure the site is properly setup
if(!file_exists($TOPDIR . '/config.php')) {
  echo 'Error: You have not setup a config.php file for this website, please ' .
    'see the \'config.php-example\' file distributed with this software ' .
    ' for an example.';
  die();
}

// generate and save a public/private keypair for the site if none exists
if(!file_exists($TOPDIR . '/issuer-key-1.private.jsonld') ||
  !file_exists($TOPDIR . '/issuer-key-1.jsonld') ||
  !file_exists($TOPDIR . '/issuer.jsonld')) {

  $config = array(
    'private_key_bits' => 2048,
    'private_key_type' => OPENSSL_KEYTYPE_RSA,
  );

  // create public/private keypair
  $keypair = openssl_pkey_new($config);

  // export the private key in PEM format
  openssl_pkey_export($keypair, $private_key);

  // Extract the public key from $keypair to $public_key
  $public_key = openssl_pkey_get_details($keypair);
  $public_key = $public_key['key'];

  // create the private key JSON-LD data
  $private_key_jsonld = array();
  $private_key_jsonld['@context'] = 'https://w3id.org/identity/v1';
  $private_key_jsonld['id'] = $GLOBALS['issuer_site'] . 'issuer-key-1';
  $private_key_jsonld['owner'] = $GLOBALS['issuer_site'] . 'issuer';
  $private_key_jsonld['privateKeyPem'] = $private_key;

  // create the public key JSON-LD data
  $public_key_jsonld = array();
  $public_key_jsonld['@context'] = 'https://w3id.org/identity/v1';
  $public_key_jsonld['id'] = $GLOBALS['issuer_site'] . 'issuer-key-1';
  $public_key_jsonld['type'] = 'CryptographicKey';
  $public_key_jsonld['owner'] = $GLOBALS['issuer_site'] . 'issuer';
  $public_key_jsonld['label'] = 'Credential Issuer Signing Key';
  $public_key_jsonld['publicKeyPem'] = $public_key;

  // create the public key JSON-LD data
  $idp_jsonld = array();
  $idp_jsonld['@context'] = 'https://w3id.org/identity/v1';
  $idp_jsonld['id'] = $GLOBALS['issuer_site'] . 'issuer';
  $idp_jsonld['publicKey'] = array(array(
    'id' => $public_key_jsonld['id'],
    'label' => $public_key_jsonld['label']
  ));

  $private_key_written =
    file_put_contents($TOPDIR . '/issuer-key-1.private.jsonld', json_encode(
      $private_key_jsonld, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES), LOCK_EX);
  $public_key_written =
    file_put_contents($TOPDIR . '/issuer-key-1.jsonld', json_encode(
      $public_key_jsonld, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES), LOCK_EX);
  $idp_written =
    file_put_contents($TOPDIR . '/issuer.jsonld', json_encode(
      $idp_jsonld, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES), LOCK_EX);

  if(!$private_key_written) {
    echo 'Failed to write the private key to the filesystem. Please make ' .
      'sure that the web server has write permission to the website directory.';
    die();
  }
  if(!$public_key_written) {
    echo 'Failed to write the public key to the filesystem. Please make ' .
      'sure that the web server has write permission to the website directory.';
    die();
  }
  if(!$idp_written) {
    echo 'Failed to write the IdP identity document to the filesystem. ' .
      'Please make sure that the web server has write permission to the ' .
      'website directory.';
    die();
  }
}

/**
 * Calculates the origin URL based on a given PHP environment.
 */
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

/**
 * Calculates the full URL for a given session.
 */
function full_url($s, $use_forwarded_host=false)
{
  return url_origin($s, $use_forwarded_host) . $s['REQUEST_URI'];
}

/**
 * Performs a signature using the Credential Issuer's key.
 *
 * @param $jsonld the JSON-LD document to sign.
 * @return a signed JSON-LD document
 */
function credential_sign($jsonld) {
  global $TOPDIR;
  $privateKey = json_decode(file_get_contents(
    $TOPDIR . '/issuer-key-1.private.jsonld'), true);
  $options = array();
  $options['key'] = $privateKey['privateKeyPem'];
  $options['keyId'] = $privateKey['id'];
  $jsonld_object = (object)$jsonld;
  bedrock_sign($jsonld_object, $options);

  return (array)$jsonld_object;
}

?>
