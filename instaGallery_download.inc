<?php

if ( !empty( $_GET['download'] ) ) {
	$download = $targetdir . '/' . $_GET['download'];
	if ( file_exists( $download ) ) {

		$finfo = finfo_open(FILEINFO_MIME_TYPE);
		$mime = finfo_file($finfo,$filename);

		header('Content-Type: ' . $mime);
		header('Content-Disposition: attachment; filename="'.basename($download).'"');
		header('Content-Length: ' . filesize($download));
		readfile( $download );
		exit();
	} else {
		header("HTTP/1.0 404 Not Found");
		exit();
	}
}

$downloadButtonCode = '

<style type="text/css">
.fa-download, .fa-download-o {
	position: relative;
	float: left;
	margin-right: 5px;
	margin-bottom: 3px;
	cursor: pointer;
 }

</style>

<script>

$(document).ready(function(){
	$(".thumbnailinner").prepend("<span class=\'fa fa-download downloadButton\'>");
	$(".downloadButton").on("click",function(e){
        var outer = $(e.target).closest(".thumbnailwrapouter");
        var filename = outer.attr("id");

        $("body").append("<iframe style=\'display:none;width:0;height:0;\' frameBorder=\'0\' seamless=\'seamless\' src=\'?d=" + relpath + "&download=" + filename + "\'></iframe>\'");
	});
});
</script>

<div id="downloadboxwrap"></div>';

$moreHtml[] = $downloadButtonCode;
