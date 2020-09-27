<?php

$thumbnailSize = 600;


// Make the pictures bigger

function printJournalAndForm($targetdir,$extraHtml = ''){

    $orig = $targetdir . '/' . $_GET['c'];
    $journalFile = $targetdir . '/' . $_GET['c'] . '_journals.html';

    $journalsAndForm = '<div class="journalheader"><span class="fa fa-close closejournal"/><span class="headertext">Please leave a journal entry below</span><div>' . $extraHtml;

    if(is_file($journalFile)){
        $journalsAndForm .= file_get_contents($journalFile);
    }

    $journalsAndForm .= "
    <form class='newjournal' onsubmit='return postNewJournal()'>
        <input type='hidden' name='targetfile' value='{$_GET['c']}'/>
		<label>Journal Date: </label><input type='date' name='journaldate'/>
        <textarea name='thejournal' placeholder='Type your journal here.'></textarea>
        <input type='submit' value='Add Journal Entry'/>
    </form>
    ";

    print $journalsAndForm;

    exit();
}

function processJournalForm($targetdir){

    $journalFile = $targetdir . '/' . $_POST['targetfile'] . '_journal.txt';

    $_GET['c'] = $_POST['targetfile'];

    $newJournal = str_replace("\r","\n",strip_tags($_POST['thejournal']));

    $date = date('F j, Y',strtotime($_POST['journaldate']));

    if(file_exists($journalFile)){
        $existingJournal = file_get_contents($journalFile) . "\n\n";
    }else{
        $existingJournal = '';
    }

    $existingJournal .= "=========== $date ==========\n$newJournal";

    try {
        file_put_contents($journalFile,$existingJournal);
    } catch (Exception $e){
        http_response_code(500);
        print "Error: Couldn't write journal file";
        exit();
    }

    printJournalAndForm($targetdir,'<div class="success">Success! Journal added!</div>');

}

if(isset($_POST['thejournal'])){
    processJournalForm($targetdir);
    exit();
}

if(isset($_GET['c'])){
    printJournalAndForm($targetdir);
}

$journalFiles = glob("$targetdir/*_journal.txt");
$journalFiles = str_replace('_journal.txt','',$journalFiles);
$journalFiles = array_map('basename',$journalFiles);

$journalsCode = "
<style type='text/css'>

.fa-pencil-square-o, .fa-pencil-square {
	position: relative;
	float: left;
	margin-right: 5px;
	margin-bottom: 3px;
	cursor: pointer;
 }

.fa-pencil-square {
    color: #2f72c7;
}

.journalButton:active {
    border-style: groove;
}



.journal {
    margin: 15px 0;
    padding: 10px 5px;
    border-radius: 4px;
    border: 1px outset #ccc;
}

.journal:nth-child(even) {
    background-color: #dbe6e6;
}

.journal:nth-child(odd) {
    background-color: #f5f7f7;
}

.newjournal {
    margin-top: 20px;
}

.newjournal input[type=text],
    .newjournal textarea {
    width: calc(100% - 8px);
    padding: 4px;
    border-radius: 4px;
}

.newjournal textarea {
    height: 100px;
    resize:vertical;
}

.newjournal input[type=submit] {
    margin-top: 4px;
    border: solid 2px #666;
    padding: 4px;
    border-radius: 3px;
    background-color: #eee;
    cursor: pointer;
    margin-bottom: 10px;
}

.newjournal input[type=submit]:hover {
    background-color: #ccc;
}

.newjournal input[type=submit]:active {
    border: inset;
}

.journalheader .headertext {
    padding-left: 20px;
}

.fa-close {
    cursor: pointer;
}


#journalboxwrap {
    position: fixed;
    top: 0px;
    bottom: 0px;
    right: 0px;
    left: 0px;
    overflow: hidden;
    margin: 0;
    background-color: rgba(0,0,0,0.8);
    z-index: 1000;
    text-align: center;
    padding: 20px;
    display: none;
}

#journalbox {
    text-align: left;
    margin: auto;
    height: calc(100% - 30px);
    display: block;
    background-color: white;
    opacity: 1;
    z-index: 1001;
    border-radius: 15px;
    padding: 15px;
    max-width: 500px;
    overflow: auto;
    display: inline-block;
    width: 500px;
}

#journalbox .success {
    font-size: 20px;
    margin: 10px 0;
    background-color: #ffff7e;
    padding: 5px;
    border-radius: 4px;
    border: 2px solid #ccc;
}

.fa-pencil-square-o, .fa-pencil-square {
	position: relative;
	float: left;
	margin-right: 5px;
	margin-bottom: 3px;
	cursor: pointer;
 }

.fa-pencil-square {
    color: #2f72c7;
}

.journalButton:active {
    border-style: groove;
}



.journal {
    margin: 15px 0;
    padding: 10px 5px;
    border-radius: 4px;
    border: 1px outset #ccc;
}

.journal:nth-child(even) {
    background-color: #dbe6e6;
}

.journal:nth-child(odd) {
    background-color: #f5f7f7;
}

.newjournal {
    margin-top: 20px;
}

.newjournal input[type=text],
    .newjournal textarea {
    width: calc(100% - 8px);
    padding: 4px;
    border-radius: 4px;
}

.newjournal textarea {
    height: 100px;
    resize:vertical;
}

.newjournal input[type=submit] {
    margin-top: 4px;
    border: solid 2px #666;
    padding: 4px;
    border-radius: 3px;
    background-color: #eee;
    cursor: pointer;
    margin-bottom: 10px;
}

.newjournal input[type=submit]:hover {
    background-color: #ccc;
}

.newjournal input[type=submit]:active {
    border: inset;
}

.journalheader .headertext {
    padding-left: 20px;
}

.fa-close {
    cursor: pointer;
}

#journalimgbox {
    height: calc(100% - 130px);
    width: calc(100% - 600px);
    margin-right: 20px;
    vertical-align: top;
    display: inline-block;
}



#journalimgbox {
    height: calc(100% - 55px);
    width: calc(100% - 600px);
    margin-right: 20px;
    vertical-align: top;
    display: inline-block;
}


#journalimgtoolbox {
    padding-top: 1px;
    padding-left: 1px;
    font-size: 40px;
	height: 54px;;
	background-color: white;
    text-align: left;
}

.journalimgaction {
    width: 50px;
    height: 50px;
    border-radius: 5px;
	border: 1px solid black;
    display: inline-block;
    text-align: center;
}

.journalimgaction:hover {
    color: white;
    background-color: black;
    cursor: pointer;
}

.journalimgaction span {
	margin-top: 5px;
}

#journalnavbox {
    float: right;
}

#journalimgwrap {
    overflow: auto;
    height: 100%;
    clear: both;
}


#journalimgwrap img {
    width: 100%;
}

.leftbar:before {
    border-left: 1px solid black;
    display: block;
    float: left;
    position: absolute;
    left: -7px;
    width: 1px;
    height: 50px;
    content: ' ';

}

.leftbar {
	margin-left: 2px;
    position: relative;
}

</style>

<script>
    var relpath = '$relpath';
    var existingJournal = " . json_encode($journalFiles) . ";

    function makeJournalDialog(e){
        var outer = $(e.target).closest('.thumbnailwrapouter');
        var filename = outer.attr('id');
		var imgpath = relpath + '/' + filename;
		$('#journalboximg').attr('src',imgpath);
		$('#journalboximg').data('curimg',outer);
        $.get(\"?d=\" + relpath + \"&c=\" + filename,function(success){
            $('#journalbox').html(success);
            $('#journalboxwrap').show();
        });
    }
    
    function postNewJournal(e){
        var vals = $('.newjournal').serialize();
        var prom = $.post(document.location,vals);
        
        prom.then(function(success){
            var pid = $('.newjournal').find('input[type=hidden]').val();
            $(document.getElementById(pid)).find('.fa-pencil-square-o').removeClass('fa-pencil-square-o').addClass('fa-pencil-square');
            $('#journalbox').html(success);
        },function(fail){
            $('#journalbox').html(\"There was an error saving your journal. Please let the site owner know.\");
        });
    
        return false;
    }

    $(document).ready(function(){
        $('.thumbnailinner').prepend('<span class=\"fa fa-pencil-square-o journalButton\">');
        $('.journalButton').on('click',makeJournalDialog);

        $('#journalboxwrap').on('click',function(e){
            if(e.target == $('#journalboxwrap').get(0)){
                $('#journalboxwrap').hide();
            }
        });
        $('#journalboxwrap').on('click','.closejournal',function(){
            $('#journalboxwrap').hide();
        });
        $(document).keyup(function(e) {
            if (e.keyCode == 27) {
                $('#journalboxwrap').hide();
            } 
        });
    
        var tmp;
        for(var i = 0;i<existingJournal.length;i++){
            $(document.getElementById(existingJournal[i])).find('.fa-pencil-square-o').removeClass('fa-pencil-square-o').addClass('fa-pencil-square');
        }

		journalImgReset();

		$('#journalimgtoolbox').on('click',function(e){
			// Find which button it was and do the thing.
			console.log(e);
			var action = $(e.target).closest('.journalimgaction');
			var verb = action.attr('id');
			var theimg = $('#journalboximg');
			var imgstyle = $('#journalboximg')[0].style;
			switch(verb){
				case 'jtool-zoomin':
					var w = parseInt(imgstyle.width);
					imgstyle.width = (w + (w/10)) + '%';
					break;
				case 'jtool-zoomout':
					var w = parseInt(imgstyle.width);
					imgstyle.width = (w - (w/11)) + '%';
					break;	

				// Since all of these affect the imgstyle.filter property we'll deal with them in a uniform way.
				case 'jtool-contrast-up':
				case 'jtool-contrast-down':
				case 'jtool-brightness-up':
				case 'jtool-brightness-down':
				case 'jtool-invert-toggle':
					var verbpieces = verb.split('-');
					var filter = imgstyle.filter.split(' ');	
					
					for(var i = 0;i<filter.length;i++){
						// This is the filter to modify.
						if (filter[i].search(verbpieces[1]) === 0){
							var filterval = parseFloat(filter[i].match(/[0-9]+/)[0]);

								switch(verbpieces[2]){
									case 'up':
										filter[i] = verbpieces[1] + '(' + (filterval + (filterval/10)) + '%)';
										break;
									case 'down':
										filter[i] = verbpieces[1] + '(' + (filterval - (filterval/11)) + '%)';
										break;
									case 'toggle':
										filter[i] = verbpieces[1] + '(' + (filterval === 0 ? 100 : 0 ) + '%)';
										break;
								}
						}
					}

					newfilter = filter.join(' ');
					imgstyle.filter = newfilter;

					break;
				case 'jtool-reset':
					journalImgReset();
					break;

				case 'jtool-prev':
					var outer = $('#journalboximg').data('curimg');
					outer = outer.prev('.thumbnailwrapouter');
					if ( outer.length === 0 ) {
						break;
					}
					var filename = outer.attr('id');
					var imgpath = relpath + '/' + filename;
					$('#journalboximg').attr('src',imgpath);
					var outer = $('#journalboximg').data('curimg',outer);
					break;
				case 'jtool-next':
					var outer = $('#journalboximg').data('curimg');
					outer = outer.next('.thumbnailwrapouter');
					if ( outer.length === 0 ) {
						break;
					}
					var filename = outer.attr('id');
					var imgpath = relpath + '/' + filename;
					$('#journalboximg').attr('src',imgpath);
					var outer = $('#journalboximg').data('curimg',outer);
					break;
			}
		});
    });

	function journalImgReset(){
		var imgstyle = $('#journalboximg')[0].style;
		imgstyle.width = '100%';
		imgstyle.filter = 'brightness(100%) contrast(100%) invert(0%)';
	}
</script>
<div id='journalboxwrap'>
	<div id='journalimgbox'>
		<div id='journalimgtoolbox'>
			<div class='journalimgaction' id='jtool-zoomout'><span class='fa fa-minus'></span></div>
			<div class='journalimgaction' id='jtool-zoomin'><span class='fa fa-plus'></span></div>

			<div class='leftbar journalimgaction' id='jtool-contrast-down'><span class='fa fa-cloud-download'></span></div>
			<div class='journalimgaction' id='jtool-contrast-up'><span class='fa fa-cloud-upload'></span></div>

			<div class='leftbar journalimgaction' id='jtool-brightness-down'><span class='fa fa-fire-extinguisher'></span></div>
			<div class='journalimgaction' id='jtool-brightness-up'><span class='fa fa-fire'></span></div>

			<div class='leftbar journalimgaction' id='jtool-invert-toggle'><span class='fa fa-toggle-on'></span></div>
			<div class='leftbar journalimgaction' id='jtool-reset'><span class='fa fa-recycle'></span></div>

			<div id='journalnavbox'>
				<div class='journalimgaction' id='jtool-prev'><span class='fa fa-arrow-left'></span></div>
				<div class='journalimgaction' id='jtool-next'><span class='fa fa-arrow-right'></span></div>
			</div>
		</div>
		<div id='journalimgwrap'><img id='journalboximg'/></div>
	</div>
	<div id='journalbox'></div>
</div>
";

$moreHtml[] = $journalsCode;
