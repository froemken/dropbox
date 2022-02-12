.. include:: ../../Includes.txt

========
Updating
========

If you update `dropbox` to a newer version, please read this section carefully!

Update to Version 4.2.0
=======================

A big thank you goes to Georg Ringer, who kindly gave me the extension key "dropbox".
I took the chance and renamed all paths, PHP classes, names and repos to the new extension key "dropbox".

Please execute the `Rename Extension Key Upgrade" wizard from install tool to rename the driver identifier
in all sys_file_storage records of existing Dropbox storages.

Update to Version 4.1.0
=======================

I have added TYPO3 11 compatibility. With this change my ext `dropbox` is not compatible with PHP versions
less than 7.4 anymore.
This version is still TYPO3 10.4 compatible, but needs at least PHP 7.4.
