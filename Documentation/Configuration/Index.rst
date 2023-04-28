..  include:: ../Includes.txt


..  _configuration:

=============
Configuration
=============

Create Dropbox App
==================

To give TYPO3 access to your Dropbox files, you need a Dropbox app. As long as this app is under development,
up to 5 devices can connect to this app:

#.  GoTo: https://www.dropbox.com/developers
#.  Choose "App console" on the upper right
#.  Click the blue button "Create app"
#.  Choose the "Scoped Access"
#.  Decide, if you want your app to work within its own folder or, if you want to have full access to all of your files
#.  Give it a name
#.  Save app with "Create App"
#.  Open your newly created app
#.  On tab "settings" you will find app key and app secret
#.  Open new tab and start configuring TYPO3

Configure TYPO3
===============

#.  Create a new file storage record on pid 0 and give it a name like "Dropbox"
#.  On Tab "Configuration" choose "Dropbox" (FlexForm reloads)
#.  Click the + icon right of the access token field to start the wizard
#.  Enter app key and app secret from your new Dropbox app
#.  Click the link to retrieve a Dropbox auth code
    #.  It will open a new browser tab, where you have to allow TYPO3 to access your app
    #.  After confirmation, you will see the auth code
    #.  Copy auth code over to dropbox configuration wizard
#.  Click next button in wizard
#.  In background my extension calls dropbox API to get access token
#.  On success the access token will automatically in configuration record
#.  Save the configuration record
#.  On success, you will see a green panel with some useful information
    about your free disk space of your Dropbox account

Have fun using your dropbox files in TYPO3.
