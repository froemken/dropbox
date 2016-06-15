.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../../Includes.txt

Configuration
=============

.. only:: html

	This chapter describes how to configure the extension.

Create File Storage
-------------------

* Go to list module and choose PID 0 (Rootpage with TYPO3 logo in front).
* Create a new record of type ``File Storage``
* On tab ``General`` choose a name like ``Dropbox``
* On tab ``Configuration`` you have to choose the ``Dropbox`` driver

Driver Configuration
--------------------

To communicate over the Dropbox-API you need an Accesstoken. You can create one in the developer area of www.dropbox.com or you can create a token with help of given appKey and appSecret in my wizard.

Performance
--------------------

.. note::
   At the bottom of the ``Configuration`` tab you will find the option: ``Folder for manipulated and temporary images etc.`` If you keep the default, all temporary images will be transfered over the Dropbox-API which is very slow. So it would be good to move that special folder to a folder on a fast ``file storage``. Set this to ``1:/_processed_/`` if your fileadmin file storage has the UID 1.
