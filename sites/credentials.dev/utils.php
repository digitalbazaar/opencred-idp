<?php
include 'bedrock.php';

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
 * Writes an identity to the database.
 *
 * @param name the shortname for the identity.
 * @param identity the identity object to write.
 * @return TRUE if the write was successful, FALSE otherwise.
 */
function write_identity($name, $identity) {
  $filename = dirname(__FILE__) . '/db/'. $name . '.jsonld';

  return file_put_contents($filename, json_encode($identity,
    JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES), LOCK_EX);
}

/**
 * Performs a signature using the Credential Issuer's key.
 *
 * @param $jsonld the JSON-LD document to sign.
 * @return a signed JSON-LD document
 */
function credentials_sign($jsonld) {
  $privateKey = json_decode(file_get_contents('privatekey.jsonld'), true);
  $options = array();
  $options['key'] = $privateKey['privateKeyPem'];
  $options['keyId'] = $privateKey['id'];
  $jsonld_object = (object)$jsonld;
  bedrock_sign($jsonld_object, $options);

  return (array)$jsonld_object;
}

?>
