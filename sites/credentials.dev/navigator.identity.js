/*!
 * navigator.identity shim.
 *
 * Copyright (c) 2012-2014 Digital Bazaar, Inc. All rights reserved.
 *
 * @author Dave Longley
 */
(function() {

// assume shim unnecessary
if('identity' in navigator) {
  return;
}

// define navigator identity API
var api = navigator.identity = {};

/**
 * Gets Identity Credentials from the IdP associated with the browser.
 *
 * @param options the options to use.
 *   query a template-based query for the requested credentials.
 *   domain a domain to lock the credentials response to and to be displayed to
 *     the user to help them decide whether or not to transmit their
 *     credentials (typically the Internet domain making the request).
 *   callback a URL for the IdP to POST the credentials response to.
 *   idp the IdP to use (FIXME: remove this option, only for testing).
 */
api.getCredentials = function(options) {
  options = options || {};
  if(!(options.query && (typeof options.query === 'object') &&
    options.query['@context'] === 'https://w3id.org/identity/v1' &&
    options.domain && options.callback)) {
    throw new Error(
      'Could not get identity credentials; invalid parameters given.');
  }

  /* TODO: Get local key and public key URL and, if 'publicKey' is not given,
  include it as well to protect user privacy by attempting to avoid public key
  retrieval. */
  /*var query = options.query;
  if(!('publicKey' in query)) {
    var copy = {};
    for(var prop in query) {
      if(Object.prototype.hasOwnProperty.call(query, prop)) {
        copy[prop] = query[prop];
      }
    }
    query = copy;
    // TODO: get public key URL from local storage
    query.publicKey = '';
  }*/

  // TODO: use telehash to fetch IdP for options.query.email

  // FIXME: use idp option (remove as this is only for testing)
  var idp = options.idp;
  var queryUrl = idp + '?action=query&credentials=true' +
    '&domain=' + encodeURIComponent(options.domain) +
    '&callback=' + encodeURIComponent(options.callback);

  // open popup and send request to IdP
  var query = escapeHtml(JSON.stringify(options.query));
  var form = document.createElement('form');
  form.setAttribute('method', 'post');
  form.setAttribute('action', queryUrl);
  form.innerHTML = '<input type="hidden" name="query" value="' + query + '" />';
  form.submit();
};

function escapeHtml(str) {
  return str.replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;');
}

})();
