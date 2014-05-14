var async = require('async');
var forge = require('node-forge');
var path = require('path');
var th = require('telehash');

// Hashnames
var idpHashname = '0cecc709d28affde008a56a3d10bac2681ff62183518393ee842cb9e158907d3';

// the demo email, password, and private key
var identityEmail = 'bob@example.com';
var identityPassword = 'reallyLong1234Password';
var privateKeyPem = '-----BEGIN RSA PRIVATE KEY-----\r\nMIIEpAIBAAKCAQEAnRjXJXPXsdDbOnF+shpHBHBpJc4cxfdZ3rUyRaoEA+iDAvkj\r\nChYA4UAF2S5JncIqSy7PgkjDOD3uummcRaa9JDDEtsEY1XNH6HgeaCft82zQiOA0\r\nt+8XpKrQ9dMAvOUWZzeE4neYFI9/AFNiM/mf6Rpfg6VevUjx2j1fJGj/LJkJ7UhU\r\nMmjvQooByN55hMWdZsHzVEBPbLG1oJpbw3aLoIgdjzwM78egnI7+Yw2f6Yt5wh6K\r\nIx8Y8mjF6xbEku+A2epZkUFatQCTHw26oSi+J0f0Z6pVIitibYo0sZ1/JEXIbS0Y\r\ngH5W3hhUTKha7e0EyZvbhksPjZ7TLifpucf3nQIDAQABAoIBAC7xIEC5t8cTcJ6r\r\nET+o6HWkHVdFmoVxHvKUVDxKzD5auOMnjNfTsVmdZuH5mdfBECA9EZaNpX/lybL8\r\nIc0SQMxSokU1t/T4KJGHaxaWb9zgNAPicv5PPFJhFGWQMlU/Yw1eop+FOvVR15JC\r\nWELNoYHm9omA3alT5ajf09EuaqlpbGWgICkdahGoKobCkCyF18eJEFv4bNGhptXX\r\nOJOWodz4w5Hf2ZRXxn76sJTrb2eyzOCa+jso83anugevvbtCfyj+caM3d6M02b+C\r\n5arycybTHlE+Z3e8HcUEsT3CbEY3KburIByeM3d0yrn2sjfUxeLGVK8NtbfEzV0M\r\nH7uiYgUCgYEA5yZ44rwx9Ih3sUQjZD9cie/ALeyL3bzRpidw32/tGNBVNMueLNZs\r\nqG2pXmuOrNh1Kt3uBw+BjGHDfiV8YsYE8D+sICVcbC7/CVZNd19rM8PjTVNXoMev\r\nzgNqu2kVD85dHFFeOpfgF+x55T1k2CAV3ErAqNWVCSuIuWRK4NsK0o8CgYEArfxV\r\n7Gf1SZEIoBeWOCXJpvUowT9g+oDzn7xUAVeWMDa4Acosc6+N8QXLXiojh1+LxUwe\r\nRyZzWxPTiQL7UpwxHKsqBNkZ4KUIrSq4sP1r8LrRplsMnPpW55SW9B7L8PETEToj\r\nZZrv1/0h5vbgu3d7tMvTHPjDCP3MxmaOz7GfuRMCgYBmfPZogc0cgU2guXd/wWBE\r\ngJsTQaiaPlgudZpkV3om4GiHKikN9FzlKQpJpSLznF4HDbO2SbfFCKvnSLOoD+is\r\npW6qKiaaiRPnje53GUWtBBPKe0OFNETM8VLnmaYPBg7euW0wSZrAwMcjT19hPIi0\r\nzigyM9EK6dSLbt6MaFKaHQKBgQCOxVlw9GH5K3WrgY94pbGTOuxln++hwL2qX62D\r\nqG8LQ2u9tDzD9dSBayLWM7gR91rH3U2fTzMsEtnsPbEkuh0nDGIftlOg32x+RWdn\r\nfZ3c3kD5xQ9Vpaw4vtscmkT6g6kE4vN3Biw4znTKhd4ml8bAtt2XkZ7iOvqV+ETK\r\ntFSAVwKBgQCXn1I+GjQYDviv4wn1ZBdXB5gR1oi3L6Ioe5ENfzvj8iHkn+cprHBv\r\n0aChYPa7tg/GYkUxEuTZStWF8imrGXlX7YG7S1dCGkGHaX5e+lQi9zbcTSuOYHUS\r\ngyrgvU+BUqoSUvSBf28fWUWkbo8z5p3CIu6Id0jJZI6lWobzRQ8AKQ==\r\n-----END RSA PRIVATE KEY-----\r\n'

// generate the identity's distributed identifier
var md = forge.md.sha256.create().update(identityEmail + identityPassword);
var identityHash = md.digest().toHex()

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

  // send an ack and recieve subsequent packets
  callback(true);
}

/***************** Relying Party Init ******************/
var hashnameFile = path.join(process.cwd(), 'hashname-ic-bob.json');
th.init({id: hashnameFile}, function(err, hashname) {
  if(err) {
    return console.log("beta generation/startup failed", err);
  }
  
  async.auto({
    initKeys: function(callback, results) {
      // FIXME: generate a password-based 16-byte key
      var privateKey = forge.pki.privateKeyFromPem(privateKeyPem);

      console.log('rp debug: generated private key');
      callback();
    },
    joinNetwork: ['initKeys', function(callback, results) {
      // join the query channel
      hashname.listen(ocQueryChannel, rpPacketHandler);
      console.log('rp debug: listening on '+ ocQueryChannel);
      callback();
    }],
    queryIdentity: ['joinNetwork', function(callback, results) {
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
