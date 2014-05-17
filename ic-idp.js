var async = require('async');
var forge = require('node-forge');
var path = require('path');
var th = require('telehash');

// open credential query channel
var ocQueryChannel = 'ocQuery';

// the mapping database
var mappingDb = {};

/******************** Packet Handlers *********************/

function idpPacketHandler(err, packet, chan, callback) {
  // check for error
  if(err) {
    return console.log('idp: packet error', err);
  }
  var message = packet.js;

  // received packet
  console.log('idp received:', message);

  if(message.type === 'Query' && 'query' in message) {
    if(message.query in mappingDb) {
      chan.send({js: mappingDb[message.query]});
    }
  }

  // send an ack and recieve subsequent packets
  callback(true);
}

/***************** Identity Provider Init ******************/
var hashnameFile = path.join(process.cwd(), 'hashname-ic-idp.json');
th.init({id: hashnameFile}, function(err, hashname) {
  if(err) {
    return console.log("alpha generation/startup failed", err);
  }

  async.auto({
    initDatabase: function(callback) {
      // initializes the database for demo purposes
      var identityEmail = 'bob@example.com';
      var identityPassword = 'reallyLong1234Passphrase';

      // generate the identity's distributed identifier
      var md =
        forge.md.sha256.create().update(identityEmail + identityPassword);
      var identityHash = md.digest().toHex();

      var queryResponse = {
        '@context': 'https://w3id.org/identity/v1',
        type: 'QueryResponse',
        query: identityHash,
        idpDocument: 'https://example.org/i/bob',
        proofOfWork: [{
          // scrypt-based proof of work that combines the identity hash,
          // created date, and nonce to create a hash w/ a particular difficulty
          // (first X nibbles must be 0)
          id: 'urn:sha256:3e1c72137ed7cb7aa134abdae008aee943fefc539590fde529ecf029743bf974',
          previousProofOfWork: 'urn:sha256:01ba4719c80b6fe911b091a7c05124b64eeece964e09c058ef8f9805daca546b',
          type: 'IdentityProof2014',
          created: '2014-05-14T02:52:03+0000',
          nonce: 'j38f9sa083jf80',
          difficulty: 5
        }]
      };

      // generate a keypair to store w/ the identity
      var keypair = forge.rsa.generateKeyPair({bits: 512});
      var secrets = {};
      secrets.publicKeyPem =
        forge.pki.publicKeyToPem(keypair.publicKey);
      secrets.privateKeyPem =
        forge.pki.privateKeyToPem(keypair.privateKey);

      // derive the key and iv from the sha-256 of the email+password
      // FIXME: switch to bcrypt or scrypt
      var tmpBuffer = md.digest();
      var key = tmpBuffer.getBytes(16);
      var iv = tmpBuffer.getBytes(16);

      // encrypt the secret data
      var cipher = forge.aes.createEncryptionCipher(key, 'CTR');
      cipher.start(iv);
      cipher.update(forge.util.createBuffer(JSON.stringify(secrets)));
      cipher.finish();
      queryResponse.data = forge.util.encode64(cipher.output.bytes());

      mappingDb[identityHash] = queryResponse;
      console.log('idp debug: database initialized.', mappingDb);
      callback();
    },
    joinNetwork: ['initDatabase', function(callback) {
      // join the query channel
      hashname.listen(ocQueryChannel, idpPacketHandler);
      console.log('idp debug: listening on '+ ocQueryChannel);
      callback();
    }]
  }, function(err, results) {
    if(err) {
      return console.log('idp error:', err);
    }
    console.log('idp debug: IdP is online');
  });

});

