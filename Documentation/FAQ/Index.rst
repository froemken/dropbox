.. include:: ../Includes.txt

.. _faq:

===
FAQ
===

My access token expires after 4 hours
=====================================

Please try following way to get an access token:

#. Visit https://www.dropbox.com/developers
#. Go to App Console
#. Open your Dropbox App
#. On "settings" tab you will find a "Generate" button to get an access token
#. Copy access token into configuration of your dropbox FAL storage
#. save

PHP 8.0/8.1 compatibility
=========================

My extension `dropbox` should be compatible with PHP 8.0 and 8.1, but the used PHP SDK
`kunalvarma05/dropbox-php-sdk` installs a complete out-dated `tightenco/collect` in version 5.2 which is not
compatible with PHP 8.0. Interesting: This package is NOT used by dropbox-php-sdk anymore. Currently the
community waits for this issue to be solved/merged: https://github.com/kunalvarma05/dropbox-php-sdk/pull/191
