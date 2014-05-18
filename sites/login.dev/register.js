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
 * Registers a given identity by generating an encrypted blob that is stored
 * with the identity provider, which will be requested and decrypted when
 * logging in via the mixnet.
 */
function registerId(e) {
  console.log("REGISTER ID");

  // get the identity document
  var identityDocument = getParameterByName('identity');
  var email = $('#email').val();
  var passphrase = $('#passphrase').val();

  console.log("email:", email);
  console.log("passphrase:", passphrase);
  console.log("identity:", identityDocument);

  // calculate the IdP mapping hash
  var md = forge.md.sha256.create().update(email + passphrase);
  var identityHash = md.digest().toHex();

  // create the registration object
  var mapping = {
    type: 'IdentityProviderMapping',
    identityDocument: identityDocument,
    query: 'urn:sha256:' + identityHash
  };

  // use scrypt to generate a key and iv for encryption/decryption
  var scrypt = scrypt_module_factory();
  var scryptKey = forge.util.createBuffer(scrypt.crypto_scrypt(
    scrypt.encode_utf8(email), scrypt.encode_utf8(passphrase),
    16384, 8, 1, 32));
  var key = scryptKey.getBytes(16);
  var iv = scryptKey.getBytes(16);

  // get the device keypair for the identityHash
  var eCipher;
  var deviceKey = {};
  var encryptedDeviceKey = localStorage.getItem(mapping.query);
  if(encryptedDeviceKey) {
    // read the device keypair from localstorage if it exists
    var dCipher = forge.aes.createDecryptionCipher(key, 'CTR');
    var data = forge.util.decode64(encryptedDeviceKey);
    dCipher.start(iv);
    dCipher.update(forge.util.createBuffer(data));
    dCipher.finish();

    // extract the public and private key for the identity
    deviceKey = JSON.parse(dCipher.output.data);
  } else {
    // generate the device keypair and store it if it doesn't exist
    var keypair = forge.rsa.generateKeyPair({bits: 512});
    deviceKey.publicKeyPem =
      forge.pki.publicKeyToPem(keypair.publicKey);
    deviceKey.privateKeyPem =
      forge.pki.privateKeyToPem(keypair.privateKey);

    eCipher = forge.aes.createEncryptionCipher(key, 'CTR');
    eCipher.start(iv);
    eCipher.update(forge.util.createBuffer(JSON.stringify(deviceKey)));
    eCipher.finish();

    localStorage.setItem(
      mapping.query, forge.util.encode64(eCipher.output.bytes()));
  }
  console.log("Device key:", deviceKey);

  // set the device key in the mapping
  mapping.publicKeyPem = deviceKey.publicKeyPem;

  // generate the query response and encrypt it
  var queryResponse = {
    query: 'urn:sha256:' + identityHash,
    identityDocument: identityDocument
  };
  eCipher = forge.aes.createEncryptionCipher(key, 'CTR');
  eCipher.start(iv);
  eCipher.update(forge.util.createBuffer(JSON.stringify(queryResponse)));
  eCipher.finish();
  mapping.queryResponse = forge.util.encode64(eCipher.output.bytes());

  // create the registration object and post it back to the identity doc
  console.log('queryResponse:', queryResponse);
  console.log('POST data:', mapping);
};

// prevent the form submit button from doing a form post
$(document).ready(function() {
  $('form').on('submit', function(e){
    e.preventDefault();
    return false;
  });
});
