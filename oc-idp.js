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
      var publicKeyPem = mappingDb[message.query].publicKeyPem;
      var publicKey = forge.pki.publicKeyFromPem(publicKeyPem);
      var unencrypted = JSON.stringify(mappingDb[message.query]);
      //var encrypted = publicKey.encrypt(unencrypted, 'RSA-OAEP');
      //var encryptedBase64 = forge.util.encode64(encrypted);
      var response = {
        js: {
          '@context': 'https://w3id.org/identity/v1',
          type: 'QueryResponse',
          query: message.query,
          response: unencrypted
        }
      };
      chan.send(response);
    }
  }

  // send an ack and recieve subsequent packets
  callback(true);
}

/***************** Identity Provider Init ******************/
var hashnameFile = path.join(process.cwd(), 'hashname-oc-idp.json');
th.init({id: hashnameFile}, function(err, hashname) {
  if(err) {
    return console.log("alpha generation/startup failed", err);
  }
  
  async.auto({
    initDatabase: function(callback, results) {
      // initializes the database for demo purposes
      var identityEmail = 'bob@example.com';
      var identityPassword = 'reallyLong1234Password';
      
      // generate the identity's distributed identifier
      var md = 
        forge.md.sha256.create().update(identityEmail + identityPassword);
      var identityHash = md.digest().toHex()
        
      mappingDb[identityHash] = {
        '@context': 'https://w3id.org/identity/v1',
        'identityDocument': 'https://example.org/i/bob',
        'did': 'id:' + identityHash,
        'publicKeyPem': '-----BEGIN PUBLIC KEY-----\r\nMIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAnRjXJXPXsdDbOnF+shpH\r\nBHBpJc4cxfdZ3rUyRaoEA+iDAvkjChYA4UAF2S5JncIqSy7PgkjDOD3uummcRaa9\r\nJDDEtsEY1XNH6HgeaCft82zQiOA0t+8XpKrQ9dMAvOUWZzeE4neYFI9/AFNiM/mf\r\n6Rpfg6VevUjx2j1fJGj/LJkJ7UhUMmjvQooByN55hMWdZsHzVEBPbLG1oJpbw3aL\r\noIgdjzwM78egnI7+Yw2f6Yt5wh6KIx8Y8mjF6xbEku+A2epZkUFatQCTHw26oSi+\r\nJ0f0Z6pVIitibYo0sZ1/JEXIbS0YgH5W3hhUTKha7e0EyZvbhksPjZ7TLifpucf3\r\nnQIDAQAB\r\n-----END PUBLIC KEY-----\r\n'
      };
      console.log('idp debug: database initialized.');
      callback();
    },
    joinNetwork: ['initDatabase', function(callback, results) {
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

