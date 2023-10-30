..  include:: /Includes.rst.txt


..  _configuration:

=============
Configuration
=============

Create File Storage
===================

*   Go to list module and choose PID 0 (Rootpage with TYPO3 logo in front).
*   Create a new record of type ``File Storage``
*   On tab ``General`` choose a name like ``Dropbox``
*   On tab ``Configuration`` you have to choose the ``Dropbox`` driver


Driver Configuration
====================

To communicate over the Dropbox-API you need an Access Token.

#.  Create an App API at Dropbox.com
#.  Copy ``App Key`` and ``App Secret``
#.  Get Access Token from developer area of www.dropbox.com
    or you can create an access token with help of the wizard you can reach over ``GetAccessToken``
#.  Save the record


Create API at dropbox.com
-------------------------

..  rst-class:: bignums

1.  Go to Developer area of dropbox.com

    Simply visit: https://www.dropbox.com/developers

    If this link is not valid anymore go to https://www.dropbox.com/, click the upper left menu icon with the 9 dots.
    Choose the App Center. In the left menu of the `App Center` you will find a link to `Develop Apps`. Now
    you should be in the developer corner of Dropbox.

2.  Create new Dropbox App

    Click the `App Console` button in the upper right corner. Now you see all your apps (if you have created some).
    Click the `Create app` button.

3.  Choose API

    With a free or simple Dropbox account you only have the possibility to choose the API with `Scoped access`.
    The TYPO3 Dropbox extension can only work with this API. Do not choose any other API.

4.  Choose App type

    For security reasons I prefer to choose `App folder`. But if you`re sure, you also can give your app
    full access to all of your Dropbox files.

5.  Give it a name

    Assign a Dropbox global unique name to your new app. Please consider, that words like `dropbox` are not allowed
    as part of the name.

    Confirm your settings with button `Create App`. You will be redirected to detail view of your app.

6.  Configure your new app

    Switch over to tab `Permissions` and activate following permissions:

    *   `files.metadata.read`
    *   `files.content.write`
    *   `files.content.read`

7.  Locate `App key` and `App secret`

    For next section you will need to copy `App key` and `App secret`. from tab `Settings`.

8.  Optional: Generate Access Token

    If you don`t want to use the Wizard, you can click the `Generate` button on tab `Settings`. This will generate
    an access token which you can copy&paste directly into the FAL storage record.


Start Driver Wizard
-------------------

While editing the ``File storage`` click on ``GetAccessToken`` to start the wizard.
Paste in the ``App Key`` and ``App Secret`` from Dropbox App explained above.
Click on ``Get AuthCode Link``

..  figure:: ../../Images/AdministratorManual/dropbox_insert_app_secret.jpg
    :width: 500px
    :align: left
    :alt: Insert app key and app secret

On the next page you have to click on the ``authorization link`` which will open a new tab
where you have to give access to your Dropbox App.

Copy the AuthCode from Dropbox page into the AuthCode field of the Wizard.

..  figure:: ../../Images/AdministratorManual/dropbox_wizard_access_token.jpg
    :width: 500px
    :align: left
    :alt: Get Access Toekn from Dropbox

With a click on ``Get AccessToken`` a further request to dropbox.com will start in the background.
On success the Access Token will automatically inserted in ``File Storage`` record and
the wizard will close.

Save the record. On success we show you some user data.

..  figure:: ../../Images/AdministratorManual/dropbox_connect_success.jpg
    :width: 500px
    :align: left
    :alt: Connection successfully

**Performance**

..  note::

    At the bottom of the ``Configuration`` tab you will find the
    option: ``Folder for manipulated and temporary images etc.``
    If you keep the default, all temporary images will be transferred over
    the Dropbox-API which is very slow.
    So it would be good to move that special folder to a folder on a
    fast ``file storage``. Set this to ``1:/_processed_/dropbox`` if your
    fileadmin file storage has the UID 1.

..  attention::

    After changing the processed folder field to a local storage (f.e. 1
    for fileadmin) you have to delete all ``sys_file_processedfile`` records
    where column "storage" is the UID of your dropbox storage (f.e. UID: 2).
    See: https://forge.typo3.org/issues/84069
