# TYPO3 Extension `dropbox`

![Build Status](https://github.com/froemken/dropbox/workflows/CI/badge.svg)

## 1 What does it do?

`dropbox` is an Extension for TYPO3 >= 10.4.0.
It extends TYPO3's FAL (File Abstraction Layer) to show files from your
Dropbox Account in file list module of TYPO3.

## 2 Installation

### Installation using Composer

The recommended way to install the extension is using Composer.

Run the following command within your Composer based TYPO3 project:

```
composer require stefanfroemken/dropbox
```

### Installation as extension from TER

Download and install `dropbox` with the extension manager.

## Create Dropbox App

To give TYPO3 access to your Dropbox files, you need a Dropbox app. As long as this app is under development,
up to 5 devices can connect to this app:

1. GoTo: https://www.dropbox.com/developers
2. Choose "App console" on the upper right
3. Click the blue button "Create app"
4. Choose the "Scoped Access"
5. Decide, if you want your app to work within its own folder or, if you want to have full access to all of your files
6. Give it a name
7. Save app with "Create App"
8. Open your newly created app
9. On tab "settings" you will find app key and app secret
10. Open new tab and start configuring TYPO3

## Configure TYPO3

1. Create a new file storage record on pid 0 and give it a name like "Dropbox"
2. On Tab "Configuration" choose "Dropbox" (FlexForm reloads)
3. Click the + icon right of the access token field to start the wizard
4. Enter app key and app secret from your new Dropbox app
5. Click the link to retrieve a Dropbox auth code
   1. It will open a new browser tab, where you have to allow TYPO3 to access your app
   2. After confirmation, you will see the auth code
   3. Copy auth code over to dropbox configuration wizard
6. Click next button in wizard
7. In background my extension calls dropbox API to get access token
8. On success the access token will automatically in configuration record
9. Save the configuration record
10. On success, you will see a green panel with some useful information
    about your free disk space of your Dropbox account

Have fun using your dropbox files in TYPO3.

**ToDo:**

* rename folder
* delete folder

** Done:**

* you can create folders
* you can navigate through the folders
* move files
* copy files
* create references to tt_content records
* show image in PopUp
* rename file
* creation of thumbs works
* copy files to upload folder
