var telehash = require('telehash');

// seed identity provider hashname
// FIXME: Implement decentralized IdP discovery mechanism
var idpHashname =
  'b2df855d484f78054c3df4fa5bfa8e19e39df195c23b7786c2804b6c1f9fe2c8';

// identity credentials query channel
var icQueryChannel = 'icQuery';

// the hashname for this client
var hashname;

/**
 * Retrieves a query parameter by name.
 *
 * @param name the name of the query parameter to retrieve.
 */
function getParameterByName(name) {
  var match = RegExp('[?&]' + name + '=([^&]*)').exec(window.location.search);
  return match && decodeURIComponent(match[1].replace(/\+/g, ' '));
}

/**
 * Performs a browser-based POST (as opposed to an XMLHttpRequest-based one).
 *
 * @param url the URL to POST the given data to.
 * @param params the parameters to POST to the given URL.
 */
function post(url, params) {
  // The rest of this code assumes you are not using a library.
  // It can be made less wordy if you use one.
  var form = document.createElement("form");
  form.setAttribute('method', 'POST');
  form.setAttribute('action', url);

  for(var key in params) {
    if(params.hasOwnProperty(key)) {
      var hiddenField = document.createElement('input');
      hiddenField.setAttribute('type', 'hidden');
      hiddenField.setAttribute('name', key);
      hiddenField.setAttribute('value', params[key]);

      form.appendChild(hiddenField);
    }
  }

  document.body.appendChild(form);
  form.submit();
}

/**
 * Telehash packet handler.
 *
 * @param err an error, if one exists for the packet.
 * @param packet the telehash packet.
 * @param chan the channel that the packet came through.
 * @param callback(err) called after the packet has been processed.
 */
function packetHandler(err, packet, chan, callback) {
  // check for error
  if(err) {
    if(err === 'timeout') {
      var elem = document.getElementById('errorFeedback');
      elem.innerHTML = 'Failed to route the login packet to the Telehash ' +
        'network. Some browser WebSockets implementations are buggy and ' +
        'the only way to fix this error is to close this browser tab and ' +
        'retry the login from the site you just came from: ' +
        '<a href="http://' + getParameterByName('domain') + '">' +
        getParameterByName('domain') +'</a>.';
      elem.removeAttribute('style');
    }
    return console.log('tc packet error:', err);
  }
  var message = packet.js;

  // received packet
  console.log('tc received:', message);

  if(message.type === 'QueryResponse') {
    // decrypt the response
    // derive the key and iv from the sha-256 of the email+password

    // use scrypt to generate a key and iv for encryption/decryption
    // FIXME: Show progress meter when deriving the key
    var email = $('#email').val();
    var passphrase = $('#passphrase').val();
    var scrypt = scrypt_module_factory();
    var scryptKey = forge.util.createBuffer(scrypt.crypto_scrypt(
      scrypt.encode_utf8(email), scrypt.encode_utf8(passphrase),
      16384, 8, 1, 32));
    var key = scryptKey.getBytes(16);
    var iv = scryptKey.getBytes(16);

    var dCipher = forge.aes.createDecryptionCipher(key, 'CTR');
    var data = forge.util.decode64(message.queryResponse);
    dCipher.start(iv);
    dCipher.update(forge.util.createBuffer(data));
    dCipher.finish();

    // extract the public and private key for the identity
    var decrypted = JSON.parse(dCipher.output.data);

    console.log('tc identity:', decrypted);

    // store the request
    var request = window.icRequest;
    localStorage.setItem(
      'request:' + request.id, JSON.stringify(request));
    var idpQueryUrl = decrypted.identityDocument +
      '?action=query&credentials=' + request.credentials + '&domain=' +
      request.domain + '&callback=' +
      encodeURIComponent(
        window.icOptions.loginSite + '?response=' + request.id);

    // re-direct the request to the target IdP
    console.log('TARGET URL:', idpQueryUrl);
    post(idpQueryUrl, {'query': JSON.stringify(request.query)});
  }

  // send an ack and recieve subsequent packets
  callback(true);
}

/**
 * Initialize the page to redirect or connect to Telehash.
 */
function init() {
  // check to see if a response should be posted immediately
  var response = getParameterByName('response');
  if(response) {
    var request = JSON.parse(localStorage.getItem('request:' + response));
    post(request.callback, {response: JSON.stringify(window.icResponse)});
  } else {
    console.log("initializing connection to Telehash...", telehash);
    telehash.init({}, function(err, hn) {
      if(err) {
        return console.log('tc debug: startup failed', err);
      } else {
        hashname = hn;
        console.log('tc debug: startup success', hashname);
      }

      // join the identity credentials query channel
      hashname.listen(icQueryChannel, packetHandler);
      console.log('tc debug: listening on '+ icQueryChannel);
    });
  }
}

/**
 * Attempt to retrieve the IdP mapping and perform a login operation.
 */
function login() {
  var email = $('#email').val();
  var passphrase = $('#passphrase').val();

  // calculate the IdP mapping hash
  var md = forge.md.sha256.create().update(email + passphrase);
  var identityHash = 'urn:sha256:' + md.digest().toHex();

  var query = {
    js: {
      '@context': 'https://w3id.org/identity/v1',
      type: 'Query',
      query: identityHash
    }
  };
  // query the IdP network for a particular identity
  hashname.start(idpHashname, icQueryChannel, query, packetHandler);
  console.log('tc debug: sending query', query, 'to', idpHashname);
}

// prevent the form submit button from doing a form post
$(document).ready(function() {
  $('form').on('submit', function(e){
    e.preventDefault();
    return false;
  });
});