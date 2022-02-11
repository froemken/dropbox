.. include:: ../../Includes.txt


.. _installation:

============
Installation
============

The extension needs to be installed like any other extension of TYPO3 CMS:

#. Visit fal_dropbox at `Github <https://github.com/froemken/fal_dropbox>`_

#. You will find a Download-Button where you can select between Download as Zip or Link for cloning this project.

#. Get the extension

   #. **Get it via Zip:** Switch to the Extensionmanager and upload fal_dropbox

   #. **Get it via Git:** If Git is available on your system, switch into
      the typo3conf/ext/ directory and clone it from Github:

      .. code-block:: bash

         git clone https://github.com/froemken/fal_dropbox.git

   #. **Get it via Composer:** If you run TYPO3 in composer mode you can add a new Repository
      into you composer.json:

      .. code-block:: bash

         {
           "repositories": [
             {
               "type": "composer",
               "url": "https://composer.typo3.org/"
             },
             {
               "type": "vcs",
               "url": "https://github.com/froemken/fal_dropbox"
             }
           ],
           "name": "my-vendor/my-typo3-cms-distribution",
           "require": {
             "typo3/cms": "7.6.*",
             "stefanfroemken/dropbox": "0.2.*"
           },
           "extra": {
             "typo3/cms": {
               "cms-package-dir": "{$vendor-dir}/typo3/cms",
               "web-dir": "web"
             }
           }
         }
