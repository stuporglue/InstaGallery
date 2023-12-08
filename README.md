InstaGallery
============

Add this file to the top level of a bunch of photo directories and boom: Instant Photo Gallery


NOTICE
------

My photo library needs have outgrown InstaGallery. I'm happy to accept simple pull requests, but I'm not going to be updating this code again. 

I have a new photo library project called [Fastback](https://github.com/stuporglue/fastback). It is not quite a simple as InstaGallery but it does handle a lot of photos, and is more mobile friendly. 


Features
--------
 * Supports nested directories
 * Mobile Friendly
 * Fullscreen
 * Supports image titles/captions
 * Generates thumbnails if directories are writable and PHP-GD is enabled
 * Slideshow support
 * Uses HTML5 Video to display video files.

TODO
----
 * mod_rewrite / pretty URL support

Instructions
------------
Simply place index.php in the top-level directory of your collection of images.

### Titles / Captions ###
If a directory has a file named *titles.csv* it will be used to load captions for the photos. 
The first column should be the filename (eg. 0001.jpg). The second column should be the caption to use. Any other columns will be ignored

### Plugins ###

If you need extra functionality you might be able to do it with a plugin.

This *does* make it so it's not a single-file gallery any more, but only if you want some extra features.

Just upload the plugin file(s) into your directory along side of index.php.

Three sample plugins are included: 

 * instaGallery_auth.inc — A sample digest authentication plugin. Edit it to set the usernames and passwords. Note that digest authentication is like a picket fence. It's not actual security but gives some minor level of privacy.
 * instaGallery_comments.inc — Allow visitors to comment on individual photos and files. Requires that the server have write access to the directory the files are in. Creates a simple FILENAME_comments.html file for each file that has comments.
 * instaGallery_download.inc — Allow visitors to easily download individual files.
 * instaGallery_journal.inc – Semi-convenient way to transcribe photos of old journals. Provides some contrast and brightness tools for convenience in case the images aren't great.


#### Custom Plugins ####
Anything named instaGallery_*.inc will be included at the start of the gallery loading. 

The plugin can do whatever it needs and simply appends any HTML it wishes to print to the $moreHtml array. 


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
Near the top of InstaGallery's index.php file are three user-configurable variables. 

* $path can be set to some other directory which will be used to load the photos. This is experamental and probably won't work. 
* $thumbnailSize sets how big the Polaroid styled image squares should be. 
* $bgcolor sets the page's background color. 
* You're welcome to remove the "Gallery by InstaGallery" link at the bottom of the page
