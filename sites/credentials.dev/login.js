/*!
 * Login functionality for credential's website.
 *
 * Copyright (c) 2012-2014 Digital Bazaar, Inc. All rights reserved.
 *
 * @author Dave Longley
 * @author Manu Sporny
 */

/**
 * Starts the login process by attempting to get id and email credentials.
 */
function login() {
  try {
    navigator.identity.getCredentials({
      query: {
        '@context': 'https://w3id.org/identity/v1',
        id: '',
        email: ''
      },
      domain: 'credentials.dev',
      callback: 'http://credentials.dev/credentials',
      idp: 'http://login.dev'
    });
  } catch(e) {
    console.log(e.stack);
  }
}

/**
 * Shows a popup and submits the given form in it.
 *
 * @param form the form to submit.
 */
function showPopup(form) {
  var width = 800;
  var height = 600;
  window.open('', 'login',
    'left=' + ((screen.width-width)/2) +
    ',top=' + ((screen.height-height)/2) +
    ',width=' + width +
    ',height=' + height +
    ',resizeable,scrollbars');
  form.target = 'login';
  form.submit();
  return false;
}

/**
 * Closes any pop up window and loads the given url.
 *
 * @param url the URL to load in the parent.
 */
function closePopup(url) {
  if(window.opener === null) {
    window.location = url;
  }
  else {
    window.close();
    window.opener.location = url;
  }
}
