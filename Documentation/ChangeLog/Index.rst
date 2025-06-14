..  _changelog:

=========
ChangeLog
=========

Version 6.1.0
=============

*   Add feature to prevent counting files of sub-folders
*   Repair creating new file
*   Prevent class properties where possible (stateless)
*   Use "new" if class has state
*   Prevent additional API calls where possible

Version 6.0.0
=============

*   Add TYPO3 compatibility
*   Remove support for older TYPO3 versions

Version 5.0.2
=============

*   Use new doctrine query methods calls

Version 5.0.1
=============

*   Update documentation

Version 5.0.0
=============

*   Add TYPO3 12 compatibility
*   Remove TYPO3 10 compatibility
*   BUGFIX: Auto refresh AccessToken
*   Get image width/height by dropbox API
*   Render image preview with thumbnail provided by dropbox API
*   Show remaining time of current AccessToken in driver configuration
*   New client factory to use dropbox API nearly everywhere
*   New PathInfoFactory to create path objects for files and folders
*   New FlashMessageHelper to reduce complexity of other classes
*   Update documentation. New configuration section.

Version 4.3.0
=============

*   Use refresh token to build access tokens
*   Remove referred link from user account
*   Add descriptions to fields in config form

Version 4.2.0
=============

*   TASK: Rename ext key `fal_dropbox` to `dropbox`
*   Add Upgrade Wizard to rename driver identifier for existing FAL storages
*   Add a lot more documentation
*   Add new wizard to retrieve access token
*   New ext icon

Version 4.1.0
=============

*   Add TYPO3 11 compatibility
