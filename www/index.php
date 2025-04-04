<?php

// common header file included in all pages
require_once('../config.php');
require_once('../includes/WfoImageCache.php');

// this is the landing page that parses all the other calls
$path_parts = explode('/', parse_url($_SERVER["REQUEST_URI"],  PHP_URL_PATH));
array_shift($path_parts); // lose the first always blank one

if($path_parts[0] == 'server' ){
    // we are viewing a name or taxon
    require_once('iiif_server.php');
}elseif($path_parts[0] == 'upload' ){
    require_once('upload_progress.php');
}elseif($path_parts[0] == 'js' ){
    // allow javascript server directly
    return false;
}elseif($path_parts[0] == 'download_results.php' ){
    // download
    return false;
}else{
    // all else fails render the home page
    require_once('manage.php');
}