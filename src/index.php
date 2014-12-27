<?php
/**
 * Stuporglue's Single-File PHP Gallery
 *
 * 
 * Copyright (C) 2014, Michael Moore <stuporglue@gmail.com>
 *
 * Licensed under the MIT license. 
 *
 * Quick Start: 
 * Place this file in the top-level directory where you want photo galleries to appear. 
 *
 * Features: 
    * Supports multiple directories
    * Mobile Friendly
    * Fullscreen
    * Supports image titles/captions
    * Generates thumbnails if directories are writable and PHP-GD is enabled
    * Supports videos (coming...) and images
    * Slideshow support

    Instructions:
        * Titles/Captions
 */



// The base directory for the photos
// You can change this if they're somewhere else...
$path = __DIR__;


/**
 * Verify that the requested path is within the $path
 *
 * @param $path The path to check our request within
 * @return String -- The full path to the target directory
 */
function getTargetPath($path){
    // Find and validate the target path
    $targetdir = $path  . (isset($_REQUEST['d']) ? '/' . $_REQUEST['d'] : '');
    while(strpos($targetdir,'..') !== FALSE){
        // Get rid of double dots and make sure that our path is a subdirectory of the $path directory
        // Can't use realpath because symlinks might make something a valid path 
        preg_replace('|/.*?/../|','');
    }

    $valid = strpos(realpath($targetdir),realpath($path)) !== FALSE && is_dir($targetdir);

    if(!$valid){
        header("HTTP/1.0 404 Not Found");
        print "Directory not found";
        exit();
    }

    return $targetdir;
}

/**
 * Make a string pretty for printing
 * @param $name The name to pretty print
 * @return A pretty name string
 */
function prettyName($name){
    $name = basename($name);
    $name = preg_split('/([^\w-]|[_])/',$name);
    $name = array_map('ucfirst',$name);
    $name = implode(' ',$name);
    return $name;
}

/**
 * Get the Table of Contents for the navigation
 * @param $path The base path
 * @return An html string for the nav
 */
function getNav($path,$relpath){
    $objects = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path), RecursiveIteratorIterator::SELF_FIRST);
    $dots = Array('.','..');
    $wrapup = Array();
    foreach($objects as $name => $object){
        if($object->isDir() && !in_array($object->getBaseName(),$dots)){
            $name = explode('/',str_replace($path . '/','',$name));
            $wraplink = &$wrapup;
            $part = array_shift($name);
            while(is_array($wraplink[$part])){
                $wraplink = &$wraplink[$part];
                $part = array_shift($name);
            }
            $wraplink[$part] = Array();
        }
    }

    $html = "";
    $rel = array_filter(explode('/',$relpath));
    $subpath = Array();
    foreach($rel as $idx => $pathpart){
        $wrapup = &$wrapup[$pathpart];

        $class='parent';
        if(count($wrapup) === 0){
            $class = '';
        }

        $html .= "<span class='$class'><a href='?d=" . implode('/',$subpath) . "'>" . prettyName($pathpart) . "</a></span>";
        $subpath[] = $pathpart;
    }

    if(count($wrapup) > 0){
        $html .= "<select id='navchange'>";
        $html .= "<option value='" . (strlen($relpath) > 0 ? implode('/',$rel) . "/" : '') . "'>" . '--' . "</option>\n";
        foreach($wrapup as $pathpart => $children){
            $html .= "<option value='" . (strlen($relpath) > 0 ? implode('/',$rel) . "/" : '') .  "$pathpart'>" . prettyName($pathpart) . "</option>\n";
        }
        $html .= "</select>\n";
    }

    return $html;
}

/**
 * Get an array of all media in the target directory, with titles if available
 * @param $targetdir The directory to get media from
 *
 * Get titles for each photo. Titles are in a file named "titles.csv" in each directory. 
 * The first column is the file name, the second column is the title/caption to use
 *
 * Return an array where the key is the filename and the value is the title. If no
 * title is found the filename is used.
 */
function getMedia($targetdir){
    $media = Array();
    $html = "";
    $globby = "$targetdir/*.*";
    $files = glob($globby);
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    foreach($files as $filename){
        if(preg_match('/(.*)_thumb\.([a-z]{3})/',$filename)){
            continue;
        }

        $mime = finfo_file($finfo,$filename);
        if(strpos($mime,'image') === 0 || strpos($mime,'video')){
            $media[] = $filename;
        }
    }

    $titles = Array();

    if(is_file($targetdir . '/titles.csv')){
        $fh = fopen($targetdir . '/titles.csv','r');
        while(!feof($fh)){
            $line = fgetcsv($fh);
            if(count($line) === 2){
                $titles[$line[0]] = $line[1];
            }
        }
        fclose($fh);
    }

    foreach($media as $filename){
        $filename = basename($filename);
        if(!isset($titles[$filename])){
            $titles[$filename] = $filename;
        }
    }

    return $titles;
}

/**
 * Get the list of files from the target directory and generate the appropriate html
 *
 * @param $targetdir The target directory to get media from
 * @param $relpath The relative link for images
 */
function getSlides($targetdir,$relpath){
    $media = getMedia($targetdir);
    $baseurl = dirname($_SERVER['PHP_SELF']);
    $html = '';
    foreach($media as $filename => $title){
            $thumbname = $relpath . '/' . preg_replace('/(.*)\.([a-z]{3})/',"$1" . "_thumb." . "$2",$filename);
            if(!is_file($thumbname)){
                $thumbname = "?d=$relpath&t=$filename";
            }
            $html .= "<div class='thumbnailwrapouter'>";
            $html .= "<span class='thumbnailinner'>\n";
                $html .= "<a href='$baseurl/$relpath/$filename' title='".htmlentities($title)."' class='swipebox thumbnaillink' rel='album' >\n";
                    $html .= "<img src='$thumbname' class='thumbnail'/>\n";
                $html .= "</a>";
            $html .= "</span>\n";
            $html .= "<div class='filename'>". $title ."</div></div>\n";
    }
    if(count($media) === 0){
        return "<div class='error'>No photos found. Try another directory.</div>";
    }
    return $html;

}

/**
 * Print and possibly save a thumbnail image
 */
function printThumbnail($targetdir){
    $orig = $targetdir . '/' . $_GET['t'];
    $thumb = preg_replace('/(.*)\.([a-z]{3})$/',"$1" . "_thumb." . "$2",$orig);

    if(!is_file($orig)){
        header("HTTP/1.0 404 Not Found");
        exit();
    }

    try {
        $image_info = getimagesize($orig);
        switch($image_info[2]){
        case IMAGETYPE_JPEG:
            header('Content-Type: image/jpeg');
            $img = imagecreatefromjpeg($orig);
            $outfunc = 'imagejpeg';
            break;
        case IMAGETYPE_GIF:
            header('Content-Type: image/gif');
            $img = imagecreatefromgif($orig);
            $outfunc = 'imagegif';
            break;
        case IMAGETYPE_PNG:
            header('Content-Type: image/png');
            $img = imagecreatefrompng($orig);
            $outfunc = 'imagepng';
            break;
        default:
            readfile($orig);
            return;
        }   

        $width = $image_info[0];
        $height = $image_info[1];

        if ($width > $height) {
            $newwidth = 300;
            $newheight = floor($height / ($width / 300));
        } else {
            $newheight = 300;
            $newwidth = floor($width / ($height / 300) );
        }   

        if($width < 300 && $height < 300){
            $newwidth = $width;
            $newheight = $height;
        }   

        $tmpimg = imagecreatetruecolor( $newwidth, $newheight );
        imagecopyresampled( $tmpimg, $img, 0, 0, 0, 0, $newwidth, $newheight, $width, $height );
        $outfunc($tmpimg, $thumb);
        if(file_exists($thumb)){
            readfile($thumb);
        }else{
            $outfunc($tmpimg);
        }   
    } catch (Exception $e){
        readfile($orig);
    }
}

$targetdir = getTargetPath($path);
$relpath = trim(str_replace($path,'',$targetdir),'/');

if(isset($_GET['t'])){
    printThumbnail($targetdir);    
    exit();
}

$nav = getNav($path,$relpath);
$slides = getSlides($targetdir,$relpath);

//////////////////////
// HTML Page
$title = "Choose a Photo Collection";

if($targetdir !== './'){
    $title = explode('/',trim($targetdir,'/'));
    $title = array_map('prettyName',$title);
    $title = implode(' | ',$title);
}
?>
<!DOCTYPE HTML>
<html><head>
<title><?=$title?></title>
<link href='//fonts.googleapis.com/css?family=Shadows+Into+Light' rel='stylesheet' type='text/css'>
<link href='//cdn.rawgit.com/brutaldesign/swipebox/master/src/css/swipebox.min.css' rel='stylesheet' type='text/css'>
<link href="//maxcdn.bootstrapcdn.com/font-awesome/4.2.0/css/font-awesome.min.css" rel="stylesheet">
<link rel='stylesheet' href='edit.css'>
</head>
<body>
    <div id='nav'>
        <?=$nav?> 
    </div>
    <div id='slides'>
        <?=$slides?>
    </div>
    <script src='//ajax.googleapis.com/ajax/libs/jquery/2.1.3/jquery.min.js'></script>
    <script src='//cdn.rawgit.com/brutaldesign/swipebox/master/src/js/jquery.swipebox.js'></script>
    <script src='edit.js'></script>
    </body>
</html>
