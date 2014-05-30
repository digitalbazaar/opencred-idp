Setting up a Test Environment
=============================

To setup a test environment, you will need the following pieces of software:

* apache2 (>= 2.4.7)
* php5 (>= 5.5.8)
* php5-json (>= 1.3.1)
* php5-curl (>= 5.5.3)

Installing the Personal Identity Provider
-----------------------------------------

To run the Personal Identity Provider, you will need to perform the following
commands:

1. Create a host alias for `idp.dev` in your `/etc/hosts` file and point it to your local machine.
1. Copy `idp.dev.conf` to `/etc/apache2/sites-available/`.
1. Set BASEDIR in `/etc/apache2/sites-available/idp.dev.conf` to the proper directory.
1. Enable the site: `a2ensite idp.dev`.
1. Reload the Apache2 config: `service restart apache2`
1. Open a web browser to `http://idp.dev/`

Installing the Login Mixnet
---------------------------

To run the Login Mixnet, you will need to perform the following commands:

1. Create a host alias for `login.dev` in your `/etc/hosts` file and point it to your local machine.
1. Copy `login.dev.conf` to `/etc/apache2/sites-available/`.
1. Set BASEDIR in `/etc/apache2/sites-available/login.dev.conf` to the proper directory.
1. Enable the site: `a2ensite login.dev`.
1. Reload the Apache2 config: `service restart apache2`
1. Open a web browser to `http://login.dev/`

Installing the Credential Issuer
--------------------------------

To run the Credential issuer, you will need to perform the following commands:

1. Create a host alias for `credentials.dev` in your `/etc/hosts` file and point it to your local machine.
1. Copy `credentials.dev.conf` to `/etc/apache2/sites-available/`.
1. Set BASEDIR in `/etc/apache2/sites-available/credentials.dev.conf` to the proper directory.
1. Enable the site: `a2ensite credentials.dev`.
1. Reload the Apache2 config: `service restart apache2`
1. Open a web browser to `http://credentials.dev/`
