var async = require('async');
var forge = require('node-forge');
var path = require('path');
var th = require('telehash');

// Hashnames
var idpHashname = '0cecc709d28affde008a56a3d10bac2681ff62183518393ee842cb9e158907d3';

// the demo email, password, and private key
var identityEmail = 'bob@example.com';
var identityPassword = 'reallyLong1234Passphrase';

// generate the identity's distributed identifier
var md = forge.md.sha256.create().update(identityEmail + identityPassword);
var identityHash = md.digest().toHex()

// the identity information
var identityInfo = {};

// open credential query channel
var ocQueryChannel = 'ocQuery';

/******************** Packet Handlers *********************/

function rpPacketHandler(err, packet, chan, callback) {
  // check for error
  if(err) {
    return console.log('rp: packet error', err);
  }
  var message = packet.js;

  // received packet
  console.log('rp received:', message);

  if(message.type === 'QueryResponse' &&
    message.query === identityHash) {
    // decrypt the response
    // derive the key and iv from the sha-256 of the email+password
    var tmpBuffer = md.digest();
    var key = tmpBuffer.getBytes(16);
    var iv = tmpBuffer.getBytes(16);

    var cipher = forge.aes.createDecryptionCipher(key, 'CTR');
    var data = forge.util.decode64(message.data);
    cipher.start(iv);
    cipher.update(forge.util.createBuffer(data));
    cipher.finish();

    // extract the public and private key for the identity
    var decrypted = JSON.parse(cipher.output.data);
    identityInfo = {
      '@context': 'https://w3id.org/identity/v1',
      id: message.idpDocument,
      did: 'id:' + message.query,
      publicKeyPem: decrypted.publicKeyPem,
      privateKeyPem: decrypted.privateKeyPem
    }

    console.log('rp identity:', identityInfo);
  }

  // send an ack and recieve subsequent packets
  callback(true);
}

/***************** Relying Party Init ******************/
var hashnameFile = path.join(process.cwd(), 'hashname-ic-bob.json');
th.init({id: hashnameFile}, function(err, hashname) {
  if(err) {
    return console.log('rp debug: startup failed', err);
  }

  async.auto({
    joinNetwork: [function(callback) {
      // join the query channel
      hashname.listen(ocQueryChannel, rpPacketHandler);
      console.log('rp debug: listening on '+ ocQueryChannel);
      callback();
    }],
    queryIdentity: ['joinNetwork', function(callback) {
      var query = {
        js: {
          '@context': 'https://w3id.org/identity/v1',
          type: 'Query',
          query: identityHash
        }
      };
      // query the IdP network for a particular identity
      hashname.start(idpHashname, ocQueryChannel, query, rpPacketHandler);
      console.log('rp debug: sending query for', identityEmail);
      callback();
    }]
  }, function(err, results) {
    if(err) {
      return console.log('rp error:', err);
    }
    console.log('rp debug: RP is online');
  });
});
