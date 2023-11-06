..  include:: /Includes.rst.txt


..  _faq:

===
FAQ
===

Upload of file XY failed
========================

If you get this error message while uploading a new file, this is because you
have missed to activate the Dropbox Permission `files.content.write` in your
Dropbox App. Please visit: https://www.dropbox.com/developers
Choose your App, switch to tab `permission` and activate that option.


Uploaded file could not be moved!
=================================

If you get this error message you have to activate the `files.content.write`
permission in Developer corner of Dropbox: https://www.dropbox.com/developers
but you have missed to re-authenticate your connection to Dropbox. Please move
to your Dropbox FAL storage and start the Authentication Wizard again.


Using Dropbox Storage is slow
=============================

I know, but let me explain:

Dropbox delivers an API call to retrieve all folders and files of a given
folder. But this request does not contain any image metadata like image
dimension (width/height) anymore since 2019. As TYPO3 needs this information
to render previews and the image in crop wizard, I have to start a second
request for EACH image in selected folder to retrieve the image dimensions
with help of another API endpoint. And if you have activated the image preview
in filelist module it needs a third dropbox call to retrieve a specific
thumbnail. All that costs a lot of time.

How to solve that?

*   Disable image preview in filelist and file browser. If you have a lot of
    images in your folder that would speed up the listing a lot
*   Good structure in your dropbox folder. Try to keep the amount of folders
    and files within a folder as small as possible
*   Move the temporary folder for dropbox storage to a faster storage
    (local storage/SSD). I have explained it
    in section :ref:`Configure dropbox <configuration>`
*   Keep your files as small as possible in dropbox storage.

Cut and Paste copies files
==========================

That happens if you have deactivated the clipboard. Please activate the
clipboard. You will find a button to activate "moving" files instead of
"copying". If you know paste the file, it will be moved instead.
