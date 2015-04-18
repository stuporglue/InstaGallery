<?php

function printCommentsAndForm($targetdir,$extraHtml = ''){

    $orig = $targetdir . '/' . $_GET['c'];
    $commentFile = $targetdir . '/' . $_GET['c'] . '_comments.html';

    $commentsAndForm = '<div class="commentheader"><span class="fa fa-close closecomment"/><span class="headertext">Please Leave a Comment Below</span><div>' . $extraHtml;

    if(is_file($commentFile)){
        $commentsAndForm .= file_get_contents($commentFile);
    }

    $commentsAndForm .= "
    <form class='newcomment' onsubmit='return postNewComment()'>
        <input type='hidden' name='targetfile' value='{$_GET['c']}'/>
        <input type='text' name='yourname' value='' placeholder='Your Name'/>
        <textarea name='thecomment' placeholder='Type your comment here.'></textarea>
        <input type='submit' value='Add a Comment'/>
    </form>
    ";

    print $commentsAndForm;


    exit();
}

function processCommentForm($targetdir){

    $commentFile = $targetdir . '/' . $_POST['targetfile'] . '_comments.html';

    $_GET['c'] = $_POST['targetfile'];

    $name = nl2br(htmlspecialchars(strip_tags($_POST['yourname'])));
    $newComment = nl2br(htmlspecialchars(strip_tags($_POST['thecomment'])));


    $date = date('F j, Y g:i a');

    if(file_exists($commentFile)){
        $existingComments = file_get_contents($commentFile);
    }else{
        $existingComments = '';
    }

    $existingComments .= "<div class='comment'>
    <h3>$name</h3>
    <h3>$date</h3>
    <div class='commentbody'>
    $newComment
    </div>
</div>
";

    try {
        file_put_contents($commentFile,$existingComments);
    } catch (Exception $e){
        http_response_code(500);
        print "Error: Couldn't write comment file";
        exit();
    }

    printCommentsAndForm($targetdir,'<div class="success">Success! Comment added!</div>');

}

if(isset($_POST['thecomment'])){
    processCommentForm($targetdir);
    exit();
}

if(isset($_GET['c'])){
    printCommentsAndForm($targetdir);
}

$commentFiles = glob("$targetdir/*_comments.html");
$commentFiles = str_replace('_comments.html','',$commentFiles);
$commentFiles = array_map('basename',$commentFiles);

$commentsCode = "
<style type='text/css'>

.fa-comments-o, .fa-comments {
     color: #999;
     float: left;
     position: absolute;
     z-index: 1000;
     font-size: 20px;
     margin: 4px 0 0 4px;
     background: #ddd;
     border: 3px ridge black;
     border-radius: 15px;
     padding: 3px;
     cursor: pointer;
 }

.fa-comments {
    color: #2f72c7;
}

.commentButton:active {
    border-style: groove; 
}

#commentsboxwrap {
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

#commentsbox {
    text-align: left;
    margin: auto;
    width: calc(100% - 30px);
    height: calc(100% - 30px);
    display: block;
    background-color: white;
    opacity: 1;
    z-index: 1001;
    border-radius: 15px;
    padding: 15px;
    max-width: 500px;
    overflow: auto;
}


.comment {
    margin: 15px 0;
    padding: 10px 5px;
    border-radius: 4px;
    border: 1px outset #ccc; 
}

.comment:nth-child(even) {
    background-color: #dbe6e6;
}

.comment:nth-child(odd) {
    background-color: #f5f7f7;
}

.newcomment {
    margin-top: 20px;
}

.newcomment input[type=text],
    .newcomment textarea {
    width: calc(100% - 8px);
    padding: 4px;
    border-radius: 4px;
}

.newcomment textarea {
    height: 100px;
    resize:vertical; 
}

.newcomment input[type=submit] {
    margin-top: 4px;
    border: solid 2px #666;
    padding: 4px;
    border-radius: 3px;
    background-color: #eee;
    cursor: pointer;
    margin-bottom: 10px;
}

.newcomment input[type=submit]:hover {
    background-color: #ccc;
}

.newcomment input[type=submit]:active {
    border: inset;
}

.commentheader .headertext {
    padding-left: 20px;
}

.fa-close {
    cursor: pointer;
}


#commentsbox .success {
    font-size: 20px;
    margin: 10px 0;
    background-color: #ffff7e;
    padding: 5px;
    border-radius: 4px;
    border: 2px solid #ccc;
}

</style>

<script>
    var relpath = '$relpath';
    var existingComments = " . json_encode($commentFiles) . ";

    function makeCommentDialog(e){
        var outer = $(e.target).closest('.thumbnailwrapouter');
        var filename = outer.attr('id');
        $.get("?d=" + relpath + "&c=" + filename,function(success){
            $('#commentsbox').html(success);
            $('#commentsboxwrap').show();
        });
    }
    
    function postNewComment(e){
        var vals = $('.newcomment').serialize();
        var prom = $.post(document.location,vals);
        
        prom.then(function(success){
            var pid = $('.newcomment').find('input[type=hidden]').val();
            $(document.getElementById(pid)).find('fa-comments-o').removeClass('fa-comments-o').addClass('fa-comments');
            $('#commentsbox').html(success);
        },function(fail){
            $('#commentsbox').html("There was an error saving your comment. Please let the site owner know.");
        });
    
        return false;
    }

    $(document).ready(function(){
        $('.thumbnailinner').prepend('<span class=\"fa fa-comments-o commentButton\">');
        $('.commentButton').on('click',makeCommentDialog);

        $('#commentsboxwrap').on('click',function(e){
            if(e.target == $('#commentsboxwrap').get(0)){
                $('#commentsboxwrap').hide();
            }
        });
        $('#commentsboxwrap').on('click','.closecomment',function(){
            $('#commentsboxwrap').hide();
        });
        $(document).keyup(function(e) {
            if (e.keyCode == 27) {
                $('#commentsboxwrap').hide();
            } 
        });
    
        var tmp;
        for(var i = 0;i<existingComments.length;i++){
            $(document.getElementById(existingComments[i])).find('.fa-comments-o').removeClass('fa-comments-o').addClass('fa-comments');
        }
    });
</script>
<div id='commentsboxwrap'><div id='commentsbox'></div></div>
";

$moreHtml[] = $commentsCode;
