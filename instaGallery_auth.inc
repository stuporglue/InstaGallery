<?php

$realm = 'Photo Gallery';
$errorMessage = "Sorry, you must log in to view this gallery";
$users = Array(); // Array('username' => 'password');

// function to parse the http auth header
function http_digest_parse($txt)
{
    // protect against missing data
    $needed_parts = array('nonce'=>1, 'nc'=>1, 'cnonce'=>1, 'qop'=>1, 'username'=>1, 'uri'=>1, 'response'=>1);
    $data = array();
    $keys = implode('|', array_keys($needed_parts));

    preg_match_all('@(' . $keys . ')=(?:([\'"])([^\2]+?)\2|([^\s,]+))@', $txt, $matches, PREG_SET_ORDER);

    foreach ($matches as $m) {
        $data[$m[1]] = $m[3] ? $m[3] : $m[4];
        unset($needed_parts[$m[1]]);
    }

    return $needed_parts ? false : $data;
}

if($_REQUEST['a'] == 'logout'){
    header('HTTP/1.0 401 Unauthorized');
    $newUri = str_replace($_SERVER['QUERY_STRING'],'',$_SERVER['REQUEST_URI']);
    header("Location: $newUri");
    exit();
}

if(count($users) > 0){
    if (empty($_SERVER['PHP_AUTH_DIGEST'])) {
        header('HTTP/1.1 401 Unauthorized'); header('WWW-Authenticate: Digest realm="'.$realm.  '",qop="auth",nonce="'.uniqid().'",opaque="'.md5($realm).'"');
        die($errorMessage);
    }


    // analyze the PHP_AUTH_DIGEST variable
    if (!($data = http_digest_parse($_SERVER['PHP_AUTH_DIGEST'])) ||
        !isset($users[$data['username']])){
        header('HTTP/1.1 401 Unauthorized'); header('WWW-Authenticate: Digest realm="'.$realm.  '",qop="auth",nonce="'.uniqid().'",opaque="'.md5($realm).'"');
        die($errorMessage);
    }


    // generate the valid response
    $A1 = md5($data['username'] . ':' . $realm . ':' . $users[$data['username']]);
    $A2 = md5($_SERVER['REQUEST_METHOD'].':'.$data['uri']);
    $valid_response = md5($A1.':'.$data['nonce'].':'.$data['nc'].':'.$data['cnonce'].':'.$data['qop'].':'.$A2);

    if ($data['response'] != $valid_response){
        header('HTTP/1.1 401 Unauthorized'); header('WWW-Authenticate: Digest realm="'.$realm.  '",qop="auth",nonce="'.uniqid().'",opaque="'.md5($realm).'"');
        die($errorMessage);
    }
}

$moreHtml[] = "<span id='logout' style='position:absolute;top:0;right:0;padding:5px;'><a href='?a=logout' class='home'>Log Out</a></span>";
