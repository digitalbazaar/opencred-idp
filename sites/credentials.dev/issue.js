/*!
 * Issue credential functionality for credentials website.
 *
 * @author Manu Sporny
 */

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
 * Starts the credential issuing process by attempting to get a credential from
 * the environment.
 */
function checkCredential() {
  console.log('checking credetian');
  if(window.icPatch) {
    post(window.icWriteUrl, {credential: JSON.stringify(window.icPatch)});
  }
}
