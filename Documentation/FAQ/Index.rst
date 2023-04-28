..  include:: ../Includes.txt

..  _faq:

===
FAQ
===

My access token expires after 4 hours
=====================================

Please try following way to get an access token:

#.  Visit https://www.dropbox.com/developers
#.  Go to App Console
#.  Open your Dropbox App
#.  On "settings" tab you will find a "Generate" button to get an access token
#.  Copy access token into configuration of your dropbox FAL storage
#.  save


PHP 8.0/8.1 compatibility
=========================

My extension `dropbox` should be compatible with PHP 8.0 and 8.1, but the used PHP SDK
`kunalvarma05/dropbox-php-sdk` installs a complete out-dated `tightenco/collect` in version 5.2 which is not
compatible with PHP 8.0. That's why I have added the complete `tightenco/collect` package into my
`dropbox` extension and updated the out-dated classes on my own.
See: https://github.com/kunalvarma05/dropbox-php-sdk/pull/191


Upload of file XY failed
========================

If you get this error message while uploading a new file, this is because you have missed to activate the
Dropbox Permission `files.content.write` in your Dropbox App. Please visit: https://www.dropbox.com/developers
Choose your App, switch to tab `permission` and activate that option.


Uploaded file could not be moved!
=================================

If you get this error message you have activate the `files.content.write` permission in Developer corner of
Dropbox: https://www.dropbox.com/developers but you have missed to re-authenticate your connection to Dropbox.
Please move to your Dropbox FAL storage and start the Authentication Wizard again.
