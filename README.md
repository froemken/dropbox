# TYPO3 Extension `fal_dropbox`

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

## Setup

1. Create a new file storage record on pid 0 called "Dropbox"
2. On Tab "Configuration" choose "Dropbox". FlexForm reloads
   1. GoTo: https://www.dropbox.com/developers
   2. Choose "App console" on the left
   3. Click on button "Create app" on the upper right
   4. Choose "Dropbox API App"
   5. Now you can decide, if you want your app to work in its own folder. Or, if you want to have full access to all of your files
   6. Give it a name
   7. Save app with "Create App"
   8. After saving, you see your newly created App. Choose it.
   9. Click it and allow your app to connect to your dropbox account

3.) Have fun with FAL Dropbox

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
