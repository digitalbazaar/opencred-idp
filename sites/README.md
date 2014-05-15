Setting up a Test Environment
=============================

To setup a test environment,  you will need the following pieces of software:

* Apache2 (>= 2.4.7)
* PHP5 (>= 5.5.8)

Installing the Personal Identity Provider
-----------------------------------------

To run the Personal Identity Provider, you will need to perform the following
commands:

# Create a host alias for `idp.dev` in your `/etc/hosts` file and point it to your local machine.
# Copy `idp.dev.conf` to `/etc/apache2/sites-available/`.
# Set BASEDIR in `/etc/apache2/sites-available/idp.dev.conf` to the proper directory.
# Enable the site: `a2ensite idp.dev`.
# Reload the Apache2 config: `service restart apache2`
# Open a web browser to `http://idp.dev/`

