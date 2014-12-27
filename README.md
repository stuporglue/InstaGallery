InstaGallery
============

Add this file to the top level of a bunch of photo directories and boom: Instant Photo Gallery

Features
--------
 * Supports nested directories
 * Mobile Friendly
 * Fullscreen
 * Supports image titles/captions
 * Generates thumbnails if directories are writable and PHP-GD is enabled
 * Slideshow support


TODO
----
 * HTML5 Video support
 * mod_rewrite / pretty URL support

Instructions
------------
Simply place this index.php file in the top-level directory of your collection of images.

### Titles / Captions ###
If a directory has a file named *titles.csv* it will be used to load captions for the photos. 
The first column should be the filename (eg. 0001.jpg). The second column should be the caption to use. Any other columns will be ignored


Optimizing
----------
InstaGallery will look for a file named $filename_thumb.ext to use as the thumbnail, eg. 0000.jpg's thumbnail would be 0000_thumb.jpg. If the
thumbnail does not exist InstaGallery will try to create it using PHP's GD functions. If the GD module is not loaded the original image will 
be scaled to fit the div. This will work but will be slow for both the server and the client's browser. If the target directory is not writable
InstaGallery will still generate and send the thumbnail itself. 

In short:
    * Make the image directories writable by the web server
    * Enable GD 

Customizing
-----------
Near the top of InstaGallery are three user-configurable variables. 

* $path can be set to some other directory which will be used to load the photos. This is experamental and probably won't work. 
* $thumbnailSize sets how big the Polaroid styled image squares should be. 
* $bgcolor sets the page's background color. 
* You're welcome to remove the "Gallery by InstaGallery" link at the bottom of the page
