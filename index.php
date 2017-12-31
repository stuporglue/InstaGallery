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
 * For more details see https://github.com/stuporglue/InstaGallery
 */


$path = __DIR__;      // The base directory for the photos, defaults to the current directory
$thumbnailSize = 300; // size in pixels
$bgcolor = '#d0d5ee'; // background color


///////////////////////////////////////////////////////////////////////////////////////////
/////   Be careful below here...
///////////////////////////////////////////////////////////////////////////////////////////

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
        die("No double dot paths");
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
    $origName = $name;
    $name = basename($name);
    $name = preg_split('/([^\w-]|[_])/',$name);
    $name = array_map('ucfirst',$name);
    $name = implode(' ',array_filter($name));
    if($name === ''){
        return $origName;
    }
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
            while(@is_array($wraplink[$part])){
                $wraplink = &$wraplink[$part];
                $part = array_shift($name);
            }
            if($part[0] != '.'){ // skip hidden files/directories
                $wraplink[$part] = Array();
            }
        }
    }

    $rel = array_filter(explode('/',$relpath));
    $pathparts = Array();
    $navparts[] = "<span><a href='?d=' class='home'>Home</a></span>";

    $builtPath = Array();
    foreach($rel as $curpart){
        uksort($wrapup,function($a,$b){ return strnatcmp(prettyName($a),prettyName($b)); });
        $html = "\n<select class='navchange'>\n";
        foreach($wrapup as $pathpart => $childparts){
            $html .= "<option value='" . implode('/',$builtPath) . (count($builtPath) > 0 ? '/' : '') . $pathpart . "'". ($pathpart == $curpart ? ' selected' : '')  .">" . prettyName($pathpart) . "</option>";
        }
        $wrapup = &$wrapup[$curpart];
        $builtPath[] = $curpart;
        $html .= "</select>\n";
        $navparts[] = $html;
    }

    if(count($wrapup) > 0){
        $html = "\n<select class='navchange'>\n";
        $html .= "<option value='" . implode('/',$builtPath) ."'>--</option>\n";
        uksort($wrapup,function($a,$b){ return strnatcmp(prettyName($a),prettyName($b)); });
        foreach($wrapup as $pathpart => $childparts){
            $html .= "<option value='" . implode('/',$builtPath) . (count($builtPath) > 0 ? '/' : '') . $pathpart . "'>" . prettyName($pathpart) . "</option>";
        }
        $html .= "</select>\n";
        $navparts[] = $html;
    }

    return "<form>" . implode(' :: ',$navparts) . "</form>";
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
        if(preg_match('/(.*)_thumb\.([A-Za-z0-9]{3})/',$filename)){
            continue;
        }

        $mime = finfo_file($finfo,$filename);

        if(strpos($mime,'image') === 0 || strpos($mime,'video') === 0){
			if ( strpos( $mime, 'x-canon-cr2' ) !== false ) {
				continue;
			}
            $media[] = $filename;
        }
    }

    $titles = Array();

    if(is_file($targetdir . '/titles.csv')){
        $fh = fopen($targetdir . '/titles.csv','r');
        while(!feof($fh)){
            $line = fgetcsv($fh);
            if(count($line) >= 2){
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
function getSlides($targetdir,$relpath,$thumbnailSize){
    $media = getMedia($targetdir);
    $html = '';

    $finfo = finfo_open(FILEINFO_MIME_TYPE);

    foreach($media as $filename => $title){
            $thumbname = $relpath . '/' . preg_replace('/(.*)\.([A-Za-z0-9]{3})/',"$1" . "_thumb." . "$2",$filename);
            if(!is_file($thumbname)){
                $thumbname = "?d=$relpath&amp;t=$filename";
            }
            $html .= "<div id='$filename' class='thumbnailwrapouter'>";
            $html .= "<span class='thumbnailinner'>";
            $filepath = preg_replace('#/+#','/', ($relpath == '' ? '' : $relpath . '/') . $filename);

			$mime = finfo_file($finfo, $filepath);

			if(strpos($mime,'video') === 0){

				if ( filesize( $filepath ) > 5000000 ) {
					$preload_and_poster = 'poster="' . $thumbname . '" preload="none"';
				} else {
					$preload_and_poster = '';
				}
					
				
				$html .= '<video data-mime="' . $mime . '" width="' . ( $thumbnailSize * 0.9 ) . '" height="' . ( $thumbnailSize * 0.7 ). '" controls loop ' . $preload_and_poster . '>';
				$html .= '<source src="' . $filepath . '" type="' . $mime . '">';
				$html .= ' Your browser does not support the video tag.</video>';
			} else {
				$html .= "<a href='$filepath' title='".htmlentities($title)."' class='swipebox thumbnaillink' rel='album' >";
					$html .= "<img data-mime='$mime' src='$thumbname' class='thumbnail'/>";
				$html .= "</a>";
			}

            $html .= "</span>";
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
function printThumbnail($targetdir,$thumbnailSize){
    $orig = $targetdir . '/' . $_GET['t'];
    $thumb = preg_replace('/(.*)\.([A-Za-z0-9]{3})$/',"$1" . "_thumb." . "$2",$orig);

    if(is_file($thumb)){
        readfile($thumb);
        exit();
    }

    if(!is_file($orig)){
        header("HTTP/1.0 404 Not Found");
        exit();
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
	$mime = finfo_file($finfo,$orig);
	if(strpos($mime,'video') === 0){
		$playButton = 'iVBORw0KGgoAAAANSUhEUgAAAFoAAABaCAYAAAA4qEECAAAABHNCSVQICAgIfAhkiAAAAAlwSFlzAAACbAAAAmwByAA4aQAAABl0RVh0U29mdHdhcmUAd3d3Lmlua3NjYXBlLm9yZ5vuPBoAAApzSURBVHic7Z1/TJT3Hcdfdx54HMVjFUEBAXGuSF3abskS61zS1rKsnVvMkg5IO6bJ2FYbTZZuWaym1C2x/WfAnDOYLf6gG11iTbqUSRN6xFRr8QeenPSkQokKOtEpCBwHd/d898fD6XG94349Pw7llXwC9zzf5/P98L4vz/N9ns/3+30MJBcpwErgG1NWAnwdeARIB7429RNgDLgz9XME6AG6gS+mfl4EPBrGPiMGneufBzwJfBdYA5QBVoV8u4BPgRPAceAYSSS8VjwOvA3cAIRGdhtoQP5SH2iswO+Az9FO3HD2OfBbYIGqf7HGZAE1yC1Kb4GD7S5QDyxW64/XggzgHWAU/QWNZCPALuSL7qxiPXAZ/QWM1QaAn6mgh+IsA1rQX7BE7ShQpKw0yvFjkvM8HK8NAy8pqlCCzEe+oEjoL44a1gCkKqZWnGQD7egvhtp2Ern3pAuFyLe5eouglfUAyxVRLgYeB64mGPhstGvAEwroFxXf5MG66MVq/wNWJaxiBJYCV5QOPiUlRRQXF4vi4mJhMpn0FjIaG0DF7l8WCp+TrVarqK+vF0NDQ8LPnTt3RG1trbBarXqLGckuATmKKBvAfBTuXeTm5opLly6JcPT19Yk1a9boLWYk+xSFu367lQ6yra0trMh+PB6PePPNN8W8efP0FnQm+5NCGrMehW9GVq9eHVHkQI4fPy6Kior0FjScScCGREVehpwuUjS4N954IyahhRBieHhYvPzyy3qLGs5uI99XxE2zGoHt2bMnZqH9HDhwQGRkZOgtbChriVfkl9QKau/evXELLURSXyhjPoVkAP3JKrQQ8oWypqYm2S6UV4gxefCOmgEpIbQfm80m8vPz9RY40P4YrcgLkVM7s0JoIYQYGhoS5eXlegvst1FCPOkzhhD6N8yy3JnVaqWpqYlDhw6Rnp4e+QB1SQdei1RoASp054JN6RYdiNPpFE899ZTerXqIoIFAwS36V0BmpG8jmSkpKaG9vZ2amhqMxlD/sJpgBX4xUwEnGnzjarboQFpbW0Vubq5erborUNjAr/w7yIMKHxiee+457HY769ev16P6UuBb/g+BQr+ifSzqs2jRIj744AMaGhqwWCxaV39PU7/Q84Cfah2FVhgMBqqrqzl9+jRPPKFZJgqggimN/UJ/G1ikZQR6UFpaymeffcbWrVsxGDQZsZyDPCz5ntDPalFrMmA2m6mrq6OlpYXFizUZ2/gs3Bf6GS1qTCbKyso4f/48L7zwgtpVPQOy0KnIo+0fOrKzs/nwww+pr69n/vz5alXzPSDFCDzG/XkhDx0Gg4EtW7Zw5swZVq1SZRTBI8AKv9APPatWreLUqVNs3bpVDfePzQkdQFpaGnV1dRw5coSFCxcq6XpO6FBs2LCBc+fOsXbtWqVcPmYEipXy9iCxdOlSbDYbmzdvVsLdciPKzet74DCZTNTV1bFu3bpEXVmNyPnBOcJgMpnYtm0bqakJDUjKmBM6ClavXk1hYWEiLjKMzLK0lR6YzWaWLFlCdnZ2vC4ydEtBzCZcLhejo6Pk5eWRlpYWlw8jctZ2jhk4duwYIN9FLlu2LJ4U2YgReWjBHGGYmJhg//799z6bzWZyc3NjdTMn9Ey43W527NhBb2/vtO3Z2dlYrTH1iu8akVPjcwTR29tLVVUVH3/8ccj9hYWFpKSkROtu2Aj0KRXcg0JzczNVVVX09PSELWMymWLp8n1pQl4WZw5gaGiInTt33rv4RWLBggVkZWVx69atSEW7jcwJDcDp06epqKiIWmQ/+fn5mM3mSMXmhPb5fOzbt49XX32VwcHBmI83Go0UFRVFSvZ2+08dYzyEWZaBgQG2b99OZ2dnQn4sFgtLlizh2rVroXaPAJeMwCTyKloPFc3NzZSXlycssp/FixeTkRHysdEngMc09aEN+L4iNSY5o6Oj7Nq1i5aWuKechKWoqAin04nX6w3cbIP7ww1siteahDgcDiorK1URGSAlJYWCgoLgzdOE7gBuqlJ7EuC/4G3atImBgQFV68rMzOTRRx/1f/wvcB7uC+0DmlSNQCeuX79OdXU1DQ0NSJKkSZ0FBQX+cSJNyBM+p40mbdQkCg1pbW2lsrISu92uab3+Ll9KSsq7/m2mgP1ngAtosBaF2rhcLmprazly5IhuMaSnp/d6PJ4O/+fgB6uHNI5Hcbq6uqisrNRVZAC32z3tDBEsdAPyZKFZhyRJvPfee2zatImrV6/qHc5wampqfeAGU1CBu8AeYLtmISnAjRs32LFjB2fPntU7FACEEH+32+3THj+HysnUMYvSWzabjfLy8qQRGflxxtvBG4NbNMiLM/0F+L3aESXCxMQEu3fvpqkpuXqlHo9nn8Ph+Mo9Sbgs4x+QF3FNSpxOJxUVFUknMnDdYrHUhNoRTmgX8Lpa0fh8vriOkySJxsZGNm7cyOXLydcOPB7P6+3t7XdD7Qt16vBzGPgPoPjcg/7+/piPuX37NjU1NZw4cULpcBTB6/V+4nA4/hluf6QBCptRIXl79OjRmMqfPHmS8vLypBUZGJYkaeNMBaKZA/ZD4N9Rlo2ajz76iLKyshnLTExMUFtby+HDhxFCKFm9kgiPx/OKw+H4x0yFohWvHtiSeEz3ycnJwWazUVpaGnJ/T08P27Zt+8qYimRDkqR9drv9l5HKzYvSnw14HshPKKoAxsbGeP/997FYLOTl5d3LTgwMDNDY2Mhbb70VTXZZV3w+X4fX6/3JzZs3I17dYzkdZCGnZRSdmF9QUEBWVta9edoul0tJ96ohhLjscrme7u7uDpkoDCaW0Xq3kFv1lbgiC0N/fz9utxuXyzWbRB4UQjwfrcgQm9Agrxz2IvKCeoogSRJ9fX2aPZRPFCHEsNvt/oHdbr8Uy3HxjI++AKxFXqhbEcbHx8Ol6pMKIcTNycnJMqfT2RG59HTiHYj+ObLYF+M8/isMDg4yPDyslDs1+NJgMDzd1dV1Kp6DExnxfxlZ7JMJ+JjGlStXglP1SYHP5zs3OTn5dEdHR/hRjxGItnsXDhdwEHl96TUkeFMjSRLj4+OBWWS9EZIkHRgbG9tw8eLFhP7dlLzb+xGwH0hYpfz8/EQm5iiCEGLU5/O91tnZeVAJf4m26EC6gX8hv1lzRSKORkZGyMzMjGWgt6L4fL42r9f7osPhaFPKp1rr3awH/kwCLxwwm82UlJRounadEGLQ4/HUXLhwYa/SvpVs0YF8Afxt6vcnkc/hMeH1evH5fLHOFYmXMUmS/pqenr7h7Nmzqjwi1GIFpwXAr5Hfzhnz+Xv58uWqiS2EGPP5fAfT0tJ2tre331Clkim0fLnvAqAa+Dnym4miwmQysXLlSkXP10KIHo/H867JZKoPzlarhV5vUX4cefG9KqJ4DWlGRgYrViR0fUUIMez1epu9Xu9+p9PZmpCzOND7ddVG5PeArwHWMcPrqvPy8sjJif79MkKIcSHEOUmS2nw+39Gurq6TTA041AO9hQ7GhLwijt/8XcVHDAZDRklJyUKLxWKZyra4DAbDCDDm8XjGjEbjl8AXBoOh2+12X3A6nZ1A0txm/h/m0xSyouKIwwAAAABJRU5ErkJggg==';
		header('Content-Type: image/png');
		print base64_decode($playButton);
		exit();
	}

    // This is going to slow down the user experience...
    if (!extension_loaded('gd') || !function_exists('gd_info')) {
        readfile($orig);
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
            exit();
        }   

        $width = $image_info[0];
        $height = $image_info[1];

        if ($width > $height) {
            // The actual minimum dimension to match the CSS
            $resizeFactor = $thumbnailSize / 0.9;
            $newwidth = $resizeFactor;
            $newheight = floor($height / ($width / $resizeFactor));
        } else {
            // The actual minimum dimension to match the CSS
            $resizeFactor = $thumbnailSize / 0.70;
            $newheight = $resizeFactor;
            $newwidth = floor($width / ($height / $resizeFactor) );
        }   

        $tmpimg = imagecreatetruecolor( $newwidth, $newheight );
        imagecopyresampled($tmpimg, $img, 0, 0, 0, 0, $newwidth, $newheight, $width, $height );
        if(function_exists('imagecrop')){
            $tmpimg = imagecrop($tmpimg,Array(
                'x' => $newwidth / 2 - ($thumbnailSize * 0.9) / 2,
                'y' => $newheight / 2 - ($thumbnailSize * 0.75) / 2,
                'width' => $thumbnailSize * 0.9,
                'height' => $thumbnailSize * 0.75 
            ));
        }
        $outfunc($tmpimg, $thumb);
        
        if(file_exists($thumb)){
            readfile($thumb);
        }else{
            $outfunc($tmpimg);
        }   
    } catch (Exception $e){
        print $e;
        readfile($orig);
    }
}

$targetdir = getTargetPath($path);
$relpath = trim(str_replace($path,'',$targetdir),'/');

/**
 * Include any addons
 */
$moreHtml = Array();
foreach(glob("instaGallery_*.inc") as $plugin){
    include($plugin);
}

/**
 * Print the thumbnail and exit
 */
if(isset($_GET['t'])){
    printThumbnail($targetdir,$thumbnailSize);    
    exit();
}

$nav = getNav($path,$relpath);
$slides = getSlides($targetdir,$relpath,$thumbnailSize);

//////////////////////
// HTML Page
$title = "Choose a Photo Collection";

if($relpath !== './'){
    $title = explode('/',trim($relpath,'/'));
    $title = array_map('prettyName',$title);
    $title = implode(' | ',$title);
}
?>
<!DOCTYPE HTML>
<html><head>
<meta charset='utf-8'>
<title><?=$title?></title>
<link href='//fonts.googleapis.com/css?family=Shadows+Into+Light' rel='stylesheet' type='text/css'>
<link href='//cdn.rawgit.com/brutaldesign/swipebox/master/src/css/swipebox.min.css' rel='stylesheet' type='text/css'>
<link href="//maxcdn.bootstrapcdn.com/font-awesome/4.2.0/css/font-awesome.min.css" rel="stylesheet">
<link rel="stylesheet" href="//maxcdn.bootstrapcdn.com/font-awesome/4.3.0/css/font-awesome.min.css">
<style type='text/css'>
html,body {
    margin: 0;
    padding: 0;
    background: <?=$bgcolor?> url('data:image/gif;base64,R0lGODlhBgAGAIABAP///wAAACH5BAEKAAEALAAAAAAGAAYAAAIJRB6geMuOYAMFADs') fixed;
    height: 100%;
    color: #444;
    text-align: center;
}

#nav {
    position: absolute;
    background: rgba(255,255,255,0.8);
    padding: 3px 10px;
    width: calc(100% - 20px);
    text-align: left;
}

#slides {
    padding-top: 25px;
    max-width: 100%;
    border: 2px beveled black;
}

.error {
    margin: 50px calc(25% - 25px/2);
    padding: 25px;
    border: 5px groove #ccc;
    background-color: rgba(255,255,255,0.6);
}

.thumbnailwrapouter {
    display: inline-block;
    height: <?=$thumbnailSize?>px;
    width: <?=$thumbnailSize?>px;
    margin: 10px;
    background-color: white;
    border: 1px solid #ccc;
    -webkit-box-shadow: 6px 10px 13px -1px rgba(94,94,94,0.7);
    -moz-box-shadow: 6px 10px 13px -1px rgba(94,94,94,0.7);
    box-shadow: 6px 10px 13px -1px rgba(94,94,94,0.7);
}

.thumbnailinner {
    display: inline-block;
    height: calc(<?=$thumbnailSize?>px * 0.75);
    width: calc(<?=$thumbnailSize?>px * 0.9);
    margin-top: 15px;
    overflow: hidden;
}

img.thumbnail {
	width: 100%;
}

.filename {
    margin: 0 10px;
    font-family: 'Shadows Into Light', cursive;
    font-size: calc(<?=$thumbnailSize?>px * 0.1);
    overflow: hidden;
    white-space: nowrap;
    text-overflow: ellipsis;
    display: inline-block;
    max-width: calc(100% - 30px);
}

#ctrlbox {
    z-index: 1000;
    position: absolute;
    top: 0;
    right: 50px;
    height: 50px;
    width: 120px;
    text-align: right;
    color: white;
    font: larger bold;
    cursor: pointer;
}

#ctrlbox i {
    padding: 15px;
}
#footer {
    margin: 30px;
}

#footer a {
    text-decoration: none;
    color: #555;
    font-weight: bold;
}

select {
    padding:3px;
    margin: 0;
    -webkit-border-radius:4px;
    -moz-border-radius:4px;
    border-radius:4px;
    border: solid 1px #999;
    background: #fff;
    color:#000;
    outline:none;
    display: inline-block;
    cursor:pointer;
}

.home {
    text-decoration: none;
    color: #000
}
.home:hover {
    text-decoration: underline;
}

</style>
    <script src='//ajax.googleapis.com/ajax/libs/jquery/2.1.3/jquery.min.js'></script>
    <script src='//cdn.rawgit.com/brutaldesign/swipebox/master/src/js/jquery.swipebox.js'></script>
</head>
<body>
    <div id='nav'>
        <?=$nav?> 
    </div>
    <div id='slides'>
        <?=$slides?>
    </div>
    <div id='footer'>
        <a href='https://github.com/stuporglue/InstaGallery'>Gallery by InstaGallery</a>
    </div>
    <?php print implode("\n",$moreHtml); ?>
    <script>
        $('a.swipebox').swipebox({
            hideBarsDelay: -1,
            afterOpen: function(){
                var ui = $.swipebox.extend();
                var close = $('#swipebox-close');
                var fs = $('<i class="fa fa-arrows-alt"></i>');
                fs.on('click',function(){
                    var elem = $('#swipebox-overlay')[0];
                    if (elem.requestFullscreen) {
                        elem.requestFullscreen();
                    } else if (elem.msRequestFullscreen) {
                        elem.msRequestFullscreen();
                    } else if (elem.mozRequestFullScreen) {
                        elem.mozRequestFullScreen();
                    } else if (elem.webkitRequestFullscreen) {
                        elem.webkitRequestFullscreen();
                    }
                });

                var pp = $('<i id="ppbutton" class="fa fa-play"></i>');
                pp.on('click',function(e){
                    var button = $(e.target);
                    if(button.hasClass('fa-play')){
                        button.removeClass('fa-play').addClass('fa-pause');
                        button.attr('data-intid',window.setInterval(function(){ui.getNext()},5000));
                    }else{
                        button.removeClass('fa-pause').addClass('fa-play');
                        window.clearInterval(button.attr('data-intid'));
                        button.attr('data-intid','');
                    }
                });
                var ctrlbox = $("<div id='ctrlbox'>");
                ctrlbox.append(pp);
                ctrlbox.append(fs);
                close.after(ctrlbox);

                // Play/pause button
                // Spacebar/Enter advances
                // big Fullscreen
            },
            afterClose: function(){
                window.clearInterval($('#ppbutton').attr('data-intid'));
            }
        });

        $('.navchange').on('change',function(e){
            if(('d=' + e.target.value) != document.location.search){
                document.location.search = 'd=' + e.target.value;
            }
        });

        $(document).on('keyup',function(e){
            if((e.keyCode == 32 || e.keyCode == 13) && $('#swipebox-overlay').length > 0){
                $.swipebox.extend().getNext();
            }
        });
    </script>
    </body>
</html>
