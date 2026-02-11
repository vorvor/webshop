CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Maintainers


INTRODUCTION
------------

The File Entity Browser module provides a beautiful, modern media library
experience for Drupal. It uses the Masonry and Imagesloaded libraries to create a
responsive, grid-based view of your files, making it easy for content editors
to browse, upload, and select images and other files.

The primary goal of this module is to bring back the intuitive user experience
of the Drupal 7 Media Library, but built on the modern, flexible Entity Browser
and Entity Embed APIs in Drupal 10 and 11.

 * For a full description of the module, visit the project page:
   https://www.drupal.org/project/file_browser

 * To submit bug reports and feature suggestions, or to track changes, visit
   the issue queue:
   https://www.drupal.org/project/issues/file_browser


REQUIREMENTS
------------

This module requires the following modules:

 * Entity Browser - https://www.drupal.org/project/entity_browser
 * Entity Embed - https://www.drupal.org/project/entity_embed
 * Dropzonejs - https://www.drupal.org/project/dropzonejs
 * Embed - https://www.drupal.org/project/embed

This module also requires the following external JavaScript libraries:

 * backbone
 * underscore
 * imagesLoaded
 * Masonry
 * Dropzone

The recommended way to install and manage these libraries is with Composer,
which is handled automatically during installation.


INSTALLATION
------------

It is highly recommended to install the module using Composer. Composer will
handle downloading the module and all of its Drupal module and JavaScript
library dependencies automatically.

RECOMMENDED - USING COMPOSER:
From your project's root directory, run the following command:
composer require drupal/file_browser

RECOMMENDED - DOWNLOAD LIBRARIES WITH COMPOSER
For better library management, you can also use the Wikimedia Composer Merge
Plugin to automatically download and manage the required JavaScript libraries:

1. Install the Wikimedia Composer Libraries plugin:
   composer require wikimedia/composer-merge-plugin

2. Add the following to your project's composer.json:
   {
     "extra": {
       "merge-plugin": {
         "include": [
           "web/modules/contrib/file_browser/composer.libraries.json"
         ]
       }
     }
   }

3. Install the module and libraries:
   composer require drupal/file_browser
   composer update

MANUAL INSTALLATION:
Install as you would normally install a contributed Drupal module. For further
information, see: https://www.drupal.org/docs/extending-drupal/installing-drupal-modules

If you install manually, you must also download the required JavaScript
libraries and place them in your project's /libraries directory. The
specific versions are defined in the module's composer.libraries.json file.


CONFIGURATION
-------------


The module provides a pre-configured "File Browser" that works out-of-the-box.
To use it, you need to configure a field to use the Entity Browser widget.

    1. Download the required libraries in the libraries directory.
       a. Download https://github.com/desandro/imagesloaded/archive/v3.2.0.zip
          and extract the download to /libraries/imagesloaded (or any libraries
          directory if you're using the Libraries module).
       b. Download https://github.com/desandro/masonry/archive/v3.3.2.zip and
          extract the download to /libraries/masonry (or any libraries directory
          if you're using the Libraries module).
       c. Download https://github.com/enyo/dropzone/archive/v4.3.0.zip and
          extract the download to /libraries/dropzone (or any libraries
          directory if you're using the Libraries module).
    2. Navigate to Administration > Extend and enabled the File Entity Browser
       and its dependencies.


1. After installation, navigate to the "Manage form display" page for any
   entity that has a File, Image, or Entity Reference field (for example, the
   Article content type at Administration > Structure > Content types >
   Article > Manage form display).

2. Change the widget for your desired field to "Entity Browser".

3. Click the gear icon on the right to configure the widget settings.

4. In the settings tray, select "File Browser" from the "Entity browser"
   dropdown list.

5. Configure the other widget settings as desired and save.

Your field will now have an "Add media" or "Select entities" button that launches
the File Entity Browser in a modal window.

QUICK START WITH THE EXAMPLE MODULE:
This module includes a sub-module called "File Browser Example". Enable this
module to quickly see a working example. It provides a custom block with
pre-configured fields that use the File Browser.


MAINTAINERS
-----------

 * Samuel Mortenson (samuel.mortenson) -
   https://www.drupal.org/u/samuelmortenson

Supporting organization:
 * Acquia - https://www.drupal.org/acquia
