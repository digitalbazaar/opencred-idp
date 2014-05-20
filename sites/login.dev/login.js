var telehash = require('telehash');

// seed identity provider hashname
// FIXME: Implement decentralized IdP discovery mechanism
var idpHashname =
  '0cecc709d28affde008a56a3d10bac2681ff62183518393ee842cb9e158907d3';

// identity credentials query channel
var icQueryChannel = 'icQuery';

// the hashname for this client
var hashname;

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
    return console.log('tc: packet error', err);
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
  }

  // send an ack and recieve subsequent packets
  callback(true);
}

/**
 * Initialize the connection to Telehash.
 */
function initTelehash() {
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

/**
 * Attempt to retrieve the IdP mapping and perform a login operation.
 */
function login() {
  var email = $('#email').val();
  var passphrase = $('#passphrase').val();

  // calculate the IdP mapping hash
  console.log(email, passphrase);
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
  console.log('tc debug: sending query for', identityHash);
}

// prevent the form submit button from doing a form post
$(document).ready(function() {
  $('form').on('submit', function(e){
    e.preventDefault();
    return false;
  });
});