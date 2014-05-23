<?php

/*
PHP Bedrock API client.
Version: 0.1.0

@author Dave Longley

New BSD License (3-clause)
Copyright (c) 2010-2014, Digital Bazaar, Inc.
All rights reserved.

Redistribution and use in source and binary forms, with or without
modification, are permitted provided that the following conditions are met:

Redistributions of source code must retain the above copyright
notice, this list of conditions and the following disclaimer.

Redistributions in binary form must reproduce the above copyright
notice, this list of conditions and the following disclaimer in the
documentation and/or other materials provided with the distribution.

Neither the name of Digital Bazaar, Inc. nor the names of its
contributors may be used to endorse or promote products derived from
this software without specific prior written permission.

THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
DISCLAIMED. IN NO EVENT SHALL DIGITAL BAZAAR BE LIABLE FOR ANY
DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
(INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
(INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
*/

require_once('jsonld.php');

class BedrockException extends Exception {};
class BedrockKeyRegistrationException extends Exception {};
class BedrockFormatException extends Exception {};
class BedrockUnknownAlgorithmException extends Exception {};
class BedrockParseException extends Exception {};
class BedrockSecurityException extends Exception {};
class BedrockMissingHeader extends BedrockSecurityException {};
class BedrockAsymmetricCipherException extends BedrockSecurityException {};

// use secure document loader
jsonld_set_document_loader('jsonld_default_secure_document_loader');

/**
 * Retrieves a JSON-LD object over HTTP.
 *
 * @param string $url the URL to HTTP GET.
 * @param assoc $options the options to use.
 *          [cache] a cache interface to use.
 *          See bedrock_http_request for HTTP options.
 * @param stdClass $cache a cache interface to use.
 *
 * @return stdClass the JSON-LD object.
 */
function bedrock_get_jsonld($url, $options=array()) {
  $rval = false;

  $cache = isset($options['cache']) ? $options['cache'] : null;

  // use cache if available
  if($cache) {
    $rval = $cache->get($url);
  }
  if($rval === false) {
    // retrieve response
    // TODO: allow custom GET function
    $rval = bedrock_default_get_jsonld($url, $options);
    $rval = jsonld_decode($rval['data']);
    if($rval === null) {
      throw new BedrockParseException(
        "Invalid response from '$url': Malformed JSON.");
    }

    // cache response
    if($cache) {
      $cache->set($url, $rval);
    }
  }

  return $rval;
}

/**
 * HTTP POSTs a JSON-LD object.
 *
 * @param string $url the URL to HTTP POST to.
 * @param stdClass $obj the JSON-LD object.
 * @param assoc $options the options to use.
 *          See bedrock_http_request for HTTP options.
 *
 * @return stdClass the JSON-LD response.
 */
function bedrock_post_jsonld($url, $obj, $options=array()) {
  try {
    $data = jsonld_encode($obj);
    // TODO: allow alternate POST function
    $rval = bedrock_default_post_jsonld($url, $data, $options);
  } catch(Exception $e) {
    throw new BedrockException(
      "Error while trying to POST to '$url': " . $e->getMessage());
  }

  // decode response
  $rval = jsonld_decode($rval);
  if($rval === null) {
    throw new BedrockParseException(
      "Invalid response from '$url': Malformed JSON.");
  }
  return $rval;
}

/**
 * Check if a JSON-LD object has a specific type.
 *
 * @param stdClass $obj the JSON-LD object.
 * @param string $type the type to check for.
 *
 * @return true if type found, false if not.
 */
function bedrock_jsonld_has_type($obj, $type) {
  $rval = false;

  if(property_exists($obj, 'type')) {
    $types = is_array($obj->type) ? $obj->type : array($obj->type);
    $length = count($types);
    for($t = 0; $t < $length && !$rval; ++$t) {
      $rval = ($types[$t] == $type);
    }
  }

  return $rval;
}

/**
 * Gets a remote public key.
 *
 * @param string $id the ID for the public key.
 * @param stdClass $cache a cache to use to set/get the public key.
 * @param assoc $options the options to use.
 *          See bedrock_get_request for HTTP and cache options.
 *
 * @return stdClass the public key.
 */
function bedrock_get_public_key($id, $options=array()) {
  // retrieve public key
  $key = bedrock_get_jsonld($id, $options);
  if(!property_exists($key, 'publicKeyPem')) {
    throw new BedrockUnknownFormatException(
      'Could not get public key. Unknown format.');
  }
  return $key;
}

/**
 * Signs the given header according to the HTTP Signatures draft spec.
 *
 * See: http://tools.ietf.org/html/draft-cavage-http-signatures-01
 *
 * @param assoc $request the request to use.
 *          method the HTTP request method, eg: 'GET'.
 *          url the HTTP request url, eg: '/foo'.
 *          version the HTTP request version, eg: 1.1.
 *          [headers] the request headers.
 * @param assoc $options the options to use.
 *          keyId the key identifier.
 *          key the key to sign with in PEM format.
 *          [algorithm] the algorithm to use (default: 'rsa-sha256').
 *          [headers] the headers to sign (default: ['(request-line)', 'date']).
 *
 * @return assoc the signed request.
 */
function bedrock_http_request_sign($request, $options=array()) {
  // TODO: validate request and options

  // build normalized headers
  $headers = array();
  foreach($request['headers'] as $name => $value) {
    $headers[strtolower($name)] = $value;
  }

  // set defaults
  if(!isset($request['version'])) {
    $request['version'] = 1.1;
  }
  if(!isset($options['algorithm'])) {
    $options['algorithm'] = 'rsa-sha256';
  }
  if(!isset($options['headers'])) {
    $options['headers'] = array('(request-line)', 'date');
  }
  if(!isset($headers['date'])) {
    $request['headers']['Date'] = $headers['date'] = date_format(
      date_create('now', new DateTimeZone('UTC')), DateTime::RFC1123);
  }
  if(!isset($headers['host'])) {
    $request['headers']['Host'] = $headers['host'] = bedrock_parse_url_host(
      $request['url']);
  }
  $options['algorithm'] = strtolower($options['algorithm']);

  // check algorithm
  switch($options['algorithm']) {
  case 'rsa-sha256':
    break;
  default:
    throw new BedrockUnknownAlgorithmException(
      'Could not create http-signature. Unknown algorithm: "' .
      $options['algorithm'] . '"');
  }

  $to_sign = '';
  foreach($options['headers'] as $header) {
    if($to_sign !== '') {
      $to_sign .= "\n";
    }
    $header = strtolower($header);

    if($header === '(request-line)' || $header === 'request-line') {
      $path = bedrock_parse_url_absolute_path($request['url']);
      $version = sprintf("HTTP/%1.1f", $request['version']);
      $to_sign .= "{$request['method']} $path $version";
    } else if(isset($headers[$header])) {
      $to_sign .= "$header: {$headers[$header]}";
    } else {
      throw new BedrockMissingHeaderException(
        'Could not create http-signature. Header missing: "' . $header . "'");
    }
  }

  // generate base64-encoded signature
  bedrock_raw_sign($to_sign, $signature, $options['key']);
  $signature = base64_encode($signature);

  // add 'Authorization' header
  $request['headers']['Authorization'] = sprintf(
    'Signature keyId="%s",algorithm="%s",headers="%s",signature="%s"',
    $options['keyId'], $options['algorithm'], join(' ', $options['headers']),
    $signature);

  return $request;
}

/**
 * Decrypts a JSON-encoded, encrypted, digitally-signed JSON-LD message.
 *
 * @param string $encrypted the message to decrypt.
 * @param string $key the PEM-encoded private key to decrypt the message.
 *
 * @return stdClass the decrypted JSON-LD object.
 */
function bedrock_decrypt_secure_message($encrypted, $key) {
  // convert message from json
  $msg = jsonld_decode($json_message);
  if($msg === NULL) {
    throw new BedrockParseException('The message contains malformed JSON.');
  }

  if(!property_exists($encrypted, 'cipherAlgorithm') ||
    $encrypted->cipherAlgorithm !== 'rsa-sha256-aes-128-cbc') {
    $algorithm = $encrypted->cipherAlgorithm;
    throw new BedrockUnknownAlgorithmException(
      'Unknown encryption algorithm "' . $algorithm . '"');
  }

  // load private key from PEM
  $pkey = openssl_pkey_get_private($key);
  if($pkey === false) {
    throw new BedrockSecurityException('Failed to load the private key.');
  }

  // decrypt symmetric key (be lenient with padding)
  $encryption_key = base64_decode($encrypted->cipherKey);
  if(openssl_private_decrypt(
    $encryption_key, $skey, $pkey, OPENSSL_PKCS1_OAEP_PADDING) === false) {
    if(openssl_private_decrypt($encryption_key, $skey, $pkey) === false) {
      throw new BedrockAsymmetricCipherException(
        'Failed to decrypt the encryption key.');
    }
  }

  // decrypt IV
  $enc_iv = base64_decode($encrypted->initializationVector);
  if(openssl_private_decrypt(
    $enc_iv, $iv, $pkey, OPENSSL_PKCS1_OAEP_PADDING) === false) {
    if(openssl_private_decrypt($enc_iv, $iv, $pkey) === false) {
      throw new BedrockAsymmetricCipherException(
        'Failed to decrypt the initialization vector (IV).');
    }
  }

  // free private key resource
  openssl_pkey_free($pkey);

  // decrypt data
  $data = openssl_decrypt($encrypted->cipherData, 'aes128', $skey, false, $iv);
  if($data === false) {
    throw new BedrockSecurityException(
      'Failed to decrypt the encrypted message due to an incorrect ' .
      'symmetric key or an invalid initialization vector (IV).');
  }

  // decode and verify JSON message
  $rval = jsonld_decode($data);
  bedrock_verify($rval);
  return $rval;
}

/**
 * Generates a hash of the JSON-LD encoded data.
 *
 * @param stdClass $obj the JSON-LD object to hash.
 *
 * @return the SHA-1 hash of the encoded JSON data.
 */
function bedrock_hash($obj) {
  // SHA-1 hash JSON
  $options = array('format' => 'application/nquads');
  return 'urn:sha256:' . hash('sha256', jsonld_normalize($obj, $options));
}

/**
 * Signs a JSON-LD object, adding a signature field to it. If a signature
 * date is not provided then the current date will be used.
 *
 * @param stdClass $obj the JSON-LD object to sign.
 * @param assoc $options the options to use.
 *          key the private key in PEM format.
 *          keyId the ID of the public key associated with the private key.
 *          [date] the ISO8601-formatted signature creation date.
 *          [domain] a domain to restrict the signature to.
 *          [nonce] a nonce to use.
 */
function bedrock_sign($obj, $options) {
  if(isset($options['date'])) {
    $date = $options['date'];
  } else {
    $date = date_format(
      date_create('now', new DateTimeZone('UTC')), DateTime::W3C);
  }

  if(isset($options['nonce'])) {
    $nonce = $options['nonce'];
  }

  // generate base64-encoded signature
  $nquads = jsonld_normalize($obj, array('format' => 'application/nquads'));
  $data = (isset($nonce) ? $nonce : '') . $date . $nquads;
  if(isset($domain)) {
    $data .= "@$domain";
  }
  bedrock_raw_sign($data, $signature, $options['key']);
  $signature = base64_encode($signature);

  // add signature to object
  $obj->signature = (object)array(
    'type' => 'GraphSignature2012',
    'created' => $date,
    'creator' => $options['keyId'],
    'signatureValue' => $signature
  );
  // add optional domain
  if(isset($domain)) {
    $obj->signature->domain = $domain;
  }
  // add optional nonce
  if(isset($nonce)) {
    $obj->signature->nonce = $nonce;
  }
}

/**
 * Verifies a JSON-LD digitally signed object.
 *
 * @param stdClass $obj the JSON-LD object to verify.
 * @param assoc $options the options to use.
 *          [check_nonce($nonce, $options)] a function to call to check to see
 *            if the nonce (null if none) used to sign the message is valid.
 *          [check_domain($domain, $options)] a function to call to check to
 *            see if the domain used (null if none) is valid.
 *          [check_key($key, $options)] a function to call to check to see if
 *            the key used to sign the message is trusted.
 *          [check_key_owner($owner, $key, $options)] a function to call to
 *            check to see if the key's owner is trusted.
 *          See bedrock_http_request for HTTP options.
 *
 * @return true if verified, false if not (exception thrown).
 */
function bedrock_verify($obj, $options=array()) {
  $rval = false;

  if(!isset($options['check_key'])) {
    $options['check_key'] = 'bedrock_check_key';
  }

  // frame message to retrieve signature
  $frame = (object)array(
    '@context' => bedrock_get_default_jsonld_context_url(),
    'signature' => (object)array(
      'type' => 'GraphSignature2012',
      'created' => new stdClass(),
      'creator' => new stdClass(),
      'domain' => new stdClass(),
      'nonce' => new stdClass(),
      'signatureValue' => new stdClass(),
    )
  );
  $obj = jsonld_frame($obj, $frame);
  if(count($obj->{'@graph'}) === 0 ||
    $obj->{'@graph'}[0]->signature === null) {
    throw new BedrockSecurityException(
      'The message is not digitally signed using a known algorithm.');
  }

  // save signature property and remove from object
  $result = $obj->{'@graph'}[0];
  $sprop = $result->signature;
  unset($result->signature);

  // check the message nonce
  $nonce = null;
  if($sprop->nonce !== null) {
    $nonce = $sprop->nonce;
    $valid_nonce = false;
  } else {
    $valid_nonce = true;
  }
  if(isset($options['check_nonce'])) {
    $valid_nonce = call_user_func($options['check_nonce'], $nonce, $options);
  }
  if(!$valid_nonce) {
    throw new BedrockSecurityException('The message nonce is invalid.');
  }

  // check the message domain
  $domain = null;
  if($sprop->domain !== null) {
    $domain = $sprop->domain;
    $valid_domain = false;
  } else {
    $valid_domain = true;
  }
  if(isset($options['check_domain'])) {
    $valid_domain = call_user_func($options['check_domain'], $domain, $options);
  }
  if(!$valid_domain) {
    throw new BedrockSecurityException('The message domain is invalid.');
  }

  // ensure signature timestamp is +/- 15 minutes
  $now = date_create('now', new DateTimeZone('UTC'))->getTimestamp();
  $time = date_create($sprop->created, new DateTimeZone('UTC'))->getTimestamp();
  if($time < ($now - 15*60) || $time > ($now + 15*60)) {
    throw new BedrockSecurityException(
      'The message digital signature timestamp is out of range.');
  }

  // fetch the public key for the signature
  $key = bedrock_get_public_key($sprop->creator, $options);
  $pem = $key->publicKeyPem;

  // ensure key has not been revoked
  if(property_exists($key, 'revoked')) {
    throw new BedrockSecurityException(
      'The message was signed with a key that has been revoked.');
  }

  // see if key is trusted
  if(!call_user_func($options['check_key'], $key, $options)) {
    throw new BedrockSecurityException(
      'The message was not signed with a trusted key.');
  }

  // normalize and serialize the object
  $nquads = jsonld_normalize($obj, array('format' => 'application/nquads'));

  // produce data to hash
  $data = '';
  if($nonce !== null) {
    $data .= $nonce;
  }
  $data .= $sprop->created . $nquads;
  if($domain !== null) {
    $data .= "@$domain";
  }

  // decode the signature value
  $sig = base64_decode($sprop->signatureValue);

  // verify the signature
  $rc = bedrock_raw_verify($data, $sig, $pem);
  if($rc === 1) {
    $rval = true;
  } else if($rc === -1) {
    // throw exception, error while trying to verify
    throw new BedrockSecurityException(
      'Low-level API error: ' . openssl_error_string());
  } else {
    throw new BedrockSecurityException(
      'The digital signature on the message is invalid.');
  }

  return $rval;
}

/**
 * Checks to see if the given key is trusted.
 *
 * @param stdClass $key the public key to check.
 * @param assoc $options the options to use.
 *          [check_key_owner($owner, $key)] a custom method to return whether
 *            or not the key owner is trusted.
 *          See bedrock_http_request for HTTP options.
 *
 * @return true if trusted, false if not.
 */
function bedrock_check_key($key, $options) {
  $owner = bedrock_get_jsonld($key->owner, $options);

  // frame owner
  $frame = (object)array(
    '@context' => bedrock_get_default_jsonld_context_url(),
    'publicKey' => (object)array('@embed' => false)
  );
  $owner = jsonld_frame($owner, $frame);
  if(count($owner->{'@graph'}) !== 0) {
    // TODO: try all returned identities
    $owner = $owner->{'@graph'}[0];
  }

  if(!JsonLdProcessor::hasValue($owner, 'publicKey', $key->id)) {
    throw new BedrockSecurityException(
      'The public key is not owned by its declared owner.');
  }

  if(isset($options['check_key_owner'])) {
    return call_user_func($options['check_key_owner'], $owner, $key, $options);
  }

  return true;
}

/**
 * Gets the config for an endpoint.
 *
 * @param string $host the endpoint host and port.
 * @param assoc $options the options to use.
 *          [service] the service to use ['bedrock'].
 *          See bedrock_http_request for HTTP options.
 *
 * @return stdClass the endpoint config.
 */
function bedrock_get_endpoint_config($host, $options=array()) {
  $service = isset($options['service']) ? $options['service'] : 'bedrock';

  $host = bedrock_normalize_host_url($host);

  // get config
  $url = "$host/.well-known/$service";
  // FIXME: change 'true' to cache interface
  $config = bedrock_get_jsonld($url, $options);

  // TODO: validate config

  return $config;
}

/**
 * Generates a PEM-encoded key pair.
 *
 * @return array an array with the key pair as 'public_key' and 'private_key'.
 */
function bedrock_create_key_pair() {
  // generate the key pair
  $config = array('private_key_bits' => 2048);
  $key_pair = openssl_pkey_new($config);

  // get private key and public key in PEM format
  openssl_pkey_export($key_pair, $private_key);
  $public_key = openssl_pkey_get_details($key_pair);
  $public_key = $public_key['key'];

  // free the key pair
  openssl_free_key($key_pair);

  return array('public_key' => $public_key, 'private_key' => $private_key);
}

/**
 * Get the endpoint's key registration URL, including the parameters required
 * to register a key.
 *
 * @param assoc $options the options to use.
 *          host the endpoint host and port.
 *          public_key the public key PEM to use.
 *          [label] the label for the key.
 *          [callback] the callback URL for the registration result.
 *          [nonce] the nonce to use.
 *          See bedrock_http_request for HTTP options.
 *
 * @return string the URL for registering the vendor.
 */
function bedrock_get_key_register_url($options) {
  extract($options);

  $options['service'] = 'web-keys';

  // get register URL from endpoint config
  $config = bedrock_get_endpoint_config($host, $options);
  $register_url = $config->publicKeyService;

  $vars = array('public-key' => $public_key);
  if(isset($label)) {
    $vars['public-key-label'] = $label;
  }
  if(isset($callback)) {
    $vars['registration-callback'] = $callback;
  }
  if(isset($nonce)) {
    $vars['response-nonce'] = $nonce;
  }

  // add query parameters to the register URL
  return bedrock_add_query_vars($register_url, $vars);
}

/**
 * Completes the key registration process by verifying the response from the
 * endpoint.
 *
 * @param string $msg the JSON-encoded encrypted registration response message.
 *
 * @return stdClass the Preferences.
 */
function bedrock_register_key($msg) {
  // decrypt message
  $prefs = bedrock_decrypt_secure_message($msg);

  // check message type
  if(bedrock_jsonld_has_type($prefs, 'Error')) {
    throw new BedrockKeyRegistrationException($msg->errorMessage);
  } else if(!bedrock_jsonld_has_type($prefs, 'IdentityPreferences')) {
    // FIXME: use different type?
    throw new BedrockKeyRegistrationException(
      'Invalid registration response from the endpoint.');
  }

  // TODO: document that public key ID should be stored
  // $prefs->public_key

  return $prefs;
}

/**
 * Add query variables to an existing url.
 *
 * @param string $url the url to add the query vars to.
 * @param assoc $qvars the query variables to add, eg: array('foo' => 'bar').
 *
 * @return string the updated url.
 */
function bedrock_add_query_vars($url, $qvars) {
  $parsed = parse_url($url);
  if(isset($parsed['query'])) {
    parse_str($parsed['query'], $query);
    $query = array_merge($query, $qvars);
  } else {
    $query = $qvars;
  }
  $scheme = isset($parsed['scheme']) ? $parsed['scheme'] . '://' : '';
  $host = isset($parsed['host']) ? $parsed['host'] : '';
  $port = isset($parsed['port']) ? ':' . $parsed['port'] : '';
  $path = isset($parsed['path']) ? $parsed['path'] : '';
  $query = (count($query) > 0) ? '?' . http_build_query(
    $query, '', '&', PHP_QUERY_RFC3986) : '';
  $fragment = isset($parsed['fragment']) ? $parsed['fragment'] : '';
  return "$scheme$host$port$path$query$fragment";
}

/**
 * Gets the host from a url. For example, 'http://example.com:8080/bar?baz'
 * will return 'example.com:8080'.
 *
 * @param string $url the url to get the host from.
 *
 * @return the host.
 */
function bedrock_parse_url_host($url) {
  $parsed = parse_url($url);
  $host = isset($parsed['host']) ? $parsed['host'] : '';
  $port = isset($parsed['port']) ? ':' . $parsed['port'] : '';
  return "$host$port";
}

/**
 * Gets the absolute path from a url. For example,
 * 'http://example.com:8080/bar?baz' will return '/bar?baz'.
 *
 * @param string $url the url to get the absolute path from.
 *
 * @return the absolute path.
 */
function bedrock_parse_url_absolute_path($url) {
  $parsed = parse_url($url);
  if(isset($parsed['path'])) {
    $path = $parsed['path'];
  } else {
    $path = '/';
  }
  if(isset($parsed['query'])) {
    $path .= "?$query";
  }
  return $path;
}

/**
 * Gets query variables from a url.
 *
 * @param string $url the url to get the query vars from.
 *
 * @return array an array of the query vars.
 */
function bedrock_parse_query_vars($url) {
  parse_str(parse_url($url, PHP_URL_QUERY), $query);
  return $query;
}

/**
 * Normalize a host URL. This will perform the following transformations:
 * - Ensure the host starts with http:// or https:// otherewise add https://.
 *
 * @param string $host_url the host URL to normalize.
 *
 * @return the normalized host URL.
 */
function bedrock_normalize_host_url($host_url) {
  $proto = (strpos($host_url, 'https://') === 0 ||
    strpos($host_url, 'http://') === 0)
    ? '' : 'https://';
  return "$proto$host_url";
}

/**
 * Default GET JSON-LD hook.
 *
 * @param string $url the URL to to retrieve.
 * @param assoc $options the options to use.
 *          See bedrock_http_request for HTTP options.
 *
 * @return string the retrieved JSON-LD.
 */
function bedrock_default_get_jsonld($url, $options=array()) {
  $response = bedrock_http_request(array(
    'method' => 'GET',
    'url' => $url,
    'headers' => array(
      'Accept' => 'application/ld+json')), $options);
  if($response['code'] >= 400) {
    // TODO: include result in exception
    throw new Exception("$code $status");
  }
  return $response;
}

/**
 * Default POST JSON-LD hook.
 *
 * @param string $url the URL.
 * @param string $data the JSON-LD data.
 * @param assoc $options the options to use.
 *          See bedrock_http_request for HTTP options.
 *
 * @return string the retrieved JSON-LD.
 */
function bedrock_default_post_jsonld($url, $data, $options=array()) {
  $response = bedrock_http_request(array(
    'method' => 'POST',
    'url' => $url,
    'data' => $data,
    'headers' => array(
      'Accept' => 'application/ld+json',
      'Content-Type' => 'application/ld+json',
      'Content-Length' => strlen($data))), $options);
  if($response['code'] >= 400) {
    // TODO: include result in exception
    throw new Exception("$code $status");
  }
  return $response;
}

/**
 * Performs an HTTP request.
 *
 * @param assoc $request the http request.
 *          method the method to use.
 *          url the url to request.
 *          [version] the version to use (default: 1.1).
 *          [headers] custom headers to send (default: none).
 *          [data] data to send (default: none).
 * @param assoc $options the options to use.
 *          [http] http context options.
 *          [ssl] ssl context options.
 *
 * @return assoc the HTTP response.
 */
function bedrock_http_request($request, $options=array()) {
  // set defaults
  if(!isset($request['version'])) {
    $request['version'] = 1.1;
  }

  $http_options = array(
    'method' => $request['method'],
    'ignore_errors' => true);
  $ssl_options = array(
    'verify_peer' => true,
    'allow_self_signed' => false,
    'cafile' => '/etc/ssl/certs/ca-certificates.crt'
  );

  if(isset($request['version'])) {
    $http_options['protocol_version'] = $request['version'];
  }
  if(isset($request['headers'])) {
    $connection_header = false;
    $header = '';
    foreach($request['headers'] as $name => $value) {
      if(strtolower($name) === 'connection') {
        $connection_header = true;
      }
      $header .= "$name: $value\r\n";
    }
    if(!$connection_header) {
      $header .= "Connection: close\r\n";
    }
    $http_options['header'] = $header;
  }
  if(isset($request['data'])) {
    $http_options['content'] = $request['data'];
  }

  if(isset($options['http'])) {
    $http_options = array_merge($http_options, $options['http']);
  }
  if(isset($options['ssl'])) {
    $ssl_options = array_merge($http_options, $options['ssl']);
  }

  $response = array();

  try {
    $context = stream_context_create(array(
      'http' => $http_options, 'ssl' => $ssl_options));
    $stream = @fopen($request['url'], 'rb', false, $context);
    if($stream === false) {
      throw new Exception(error_get_last()['message']);
    }

    // get meta info and data
    $meta = stream_get_meta_data($stream);
    $data = stream_get_contents($stream);

    // parse response line
    $response_line = $meta['wrapper_data'][0];
    list($version, $code, $status) = explode(' ', $response_line, 3);
    $response['version'] = $version;
    $response['code'] = intval($code);
    $response['status'] = $status;

    // parse response headers
    $headers = array();
    $count = count($meta['wrapper_data']);
    for($i = 1; $i < $count; ++$i) {
      list($name, $value) = explode(':', $meta['wrapper_data'][$i], 2);
      $value = trim($value);
      if(isset($headers[$name])) {
        if(!is_array($headers[$name])) {
          $headers[$name] = array($headers[$name]);
        }
        $headers[$name][] = $value;
      } else {
        $headers[$name] = $value;
      }
    }

    // add headers and data
    $response['headers'] = $headers;
    if($data !== false) {
      $response['data'] = $data;
    }
  } catch(Exception $e) {
    throw new Exception(
      'Could not perform http request: ' .
      "{$request['method']} {$request['url']}', reason=" . $e->getMessage());
  } finally {
    if($stream !== false) {
      fclose($stream);
    }
  }

  return $response;
}

/**
 * A compatibility wrapper for producing a digital signature.
 *
 * @param string $data the data to sign.
 * @param string $signature the signature variable to be set.
 * @param string $pem the private key in PEM format.
 *
 * @return boolean true on success, false on failure.
 */
function bedrock_raw_sign($data, &$signature, $pem) {
  if(defined('OPENSSL_ALGO_SHA256')) {
    return openssl_sign($data, $signature, $pem, OPENSSL_ALGO_SHA256);
  }

  // load private key from PEM
  $pkey = openssl_pkey_get_private($pem);
  if($pkey === false) {
    throw new BedrockSecurityException('Failed to load the private key.');
  }

  // manually do pkcs1 v1.5 encoding (DigestInfo)
  $hash = hash('sha256', $data);
  $digestInfo = pack('H*', '3031300d060960864801650304020105000420' . $hash);

  $rval = openssl_private_encrypt(
    $digestInfo, $signature, $pkey, OPENSSL_PKCS1_PADDING);

  // free private key resource
  openssl_pkey_free($pkey);

  return $rval;
}

/**
 * A compatibility wrapper for verifying a digital signature.
 *
 * @param string $data the data to verify.
 * @param string $signature the signature to verify.
 * @param string $pem the public key in PEM format.
 *
 * @return int 1 if verified, 0 if incorrect, -1 on error.
 */
function bedrock_raw_verify($data, $signature, $pem) {
  if(defined('OPENSSL_ALGO_SHA256')) {
    return openssl_verify($data, $signature, $pem, OPENSSL_ALGO_SHA256);
  }

  // load public key from PEM
  $pkey = openssl_pkey_get_public($pem);
  if($pkey === false) {
    throw new BedrockSecurityException('Failed to load the public key.');
  }

  // decrypt encrypted digest info
  $rval = openssl_public_decrypt(
    $signature, $decrypted, $pkey, OPENSSL_PKCS1_PADDING);
  if(!$rval) {
    return 0;
  }

  // free public key resource
  openssl_pkey_free($pkey);

  // manually do pkcs1 v1.5 encoding (DigestInfo) for comparison
  $hash = hash('sha256', $data);
  $digestInfo = pack('H*', '3031300d060960864801650304020105000420' . $hash);

  // compare decrypted digest info to constructed one
  return ($decrypted === $digestInfo) ? 1 : 0;
}

/**
 * Outputs the given data as a JSON-LD encoded string surrounded by pre tags.
 *
 * @param mixed $data the data to print out.
 * @param string $title an optional title to include.
 */
function bedrock_debug($data, $title='') {
  if($title) {
    $title .= ': ';
  }
  echo "<pre>$title" . jsonld_encode($data, JSON_PRETTY_PRINT) . "</pre>";
}

/**
 * Gets the default JSON-LD context URL for bedrock.
 *
 * @return string the default JSON-LD context URL.
 */
function bedrock_get_default_jsonld_context_url() {
  return 'https://w3id.org/bedrock/v1';
}

/**
 * Creates a default JSON-LD context for bedrock.
 *
 * @return stdClass the default JSON-LD context.
 */
function bedrock_create_default_jsonld_context() {
  return (object)array(
    // aliases
    'id' => '@id',
    'type' => '@type',

    // prefixes
    'bed' => 'https://w3id.org/bedrock#',
    'dc' => 'http://purl.org/dc/terms/',
    'identity' => 'https://w3id.org/identity#',
    'rdf' => 'http://www.w3.org/1999/02/22-rdf-syntax-ns#',
    'rdfs' => 'http://www.w3.org/2000/01/rdf-schema#',
    'sec' => 'https://w3id.org/security#',
    'schema' => 'http://schema.org/',
    'xsd' => 'http://www.w3.org/2001/XMLSchema#',

    // bedrock
    'Identity' => 'bed:Identity',

    // general
    'about' => (object)array('@id' => 'schema:about', '@type' => '@id'),
    'address' => (object)array('@id' => 'schema:address', '@type' => '@id'),
    'addressCountry' => 'schema:addressCountry',
    'addressLocality' => 'schema:addressLocality',
    'addressRegion' => 'schema:addressRegion',
    'comment' => 'rdfs:comment',
    'created' => (object)array(
      '@id' => 'dc:created', '@type' => 'xsd:dateTime'),
    'creator' => (object)array('@id' => 'dc:creator', '@type' => '@id'),
    'description' => 'schema:description',
    'email' => 'schema:email',
    'familyName' => 'schema:familyName',
    'givenName' => 'schema:givenName',
    'image' => (object)array('@id' => 'schema:image', '@type' => '@id'),
    'label' => 'rdfs:label',
    'name' => 'schema:name',
    'postalCode' => 'schema:postalCode',
    'streetAddress' => 'schema:street-address',
    'title' => 'dc:title',
    'url' => (object)array('@id' => 'schema:url', '@type' => '@id'),
    'PostalAddress' => 'schema:PostalAddress',

    // identity
    'identityService' => (object)array(
      '@id' => 'identity:identityService', '@type' => '@id'),

    // security
    'credential' => (object)array('@id' => 'sec:credential', '@type' => '@id'),
    'cipherAlgorithm' => 'sec:cipherAlgorithm',
    'cipherData' => 'sec:cipherData',
    'cipherKey' => 'sec:cipherKey',
    'claim' => (object)array('@id' => 'sec:claim', '@type' => '@id'),
    'digestAlgorithm' => 'sec:digestAlgorithm',
    'digestValue' => 'sec:digestValue',
    'domain' => 'sec:domain',
    'expiration' => (object)array(
      '@id' => 'sec:expiration', '@type' => 'xsd:dateTime'),
    'initializationVector' => 'sec:initializationVector',
    'nonce' => 'sec:nonce',
    'normalizationAlgorithm' => 'sec:normalizationAlgorithm',
    'owner' => (object)array('@id' => 'sec:owner', '@type' => '@id'),
    'password' => 'sec:password',
    'privateKey' => (object)array('@id' => 'sec:privateKey', '@type' => '@id'),
    'privateKeyPem' => 'sec:privateKeyPem',
    'publicKey' => (object)array('@id' => 'sec:publicKey', '@type' => '@id'),
    'publicKeyPem' => 'sec:publicKeyPem',
    'publicKeyService' => (object)array(
      '@id' => 'sec:publicKeyService', '@type' => '@id'),
    'revoked' => (object)array(
      '@id' => 'sec:revoked', '@type' => 'xsd:dateTime'),
    'signature' => 'sec:signature',
    'signatureAlgorithm' => 'sec:signatureAlgorithm',
    'signatureValue' => 'sec:signatureValue',
    'EncryptedMessage' => 'sec:EncryptedMessage',
    'CryptographicKey' => 'sec:Key',
    'GraphSignature2012' => 'sec:GraphSignature2012'
  );
}

/* end of file, omit ?> */
