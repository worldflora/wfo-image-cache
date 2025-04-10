<?php

// common header file included in all pages
require_once('../config.php');
require_once('../includes/WfoImageCache.php');

// this is the landing page that parses all the other calls
$path_parts = explode('/', parse_url($_SERVER["REQUEST_URI"],  PHP_URL_PATH));
array_shift($path_parts); // lose the first always blank one

// routing table
if($path_parts[0] == 'server' ){
    // this is a request for an image so it is served without any authentication
    require_once('iiif_server.php');
}elseif(!isset($_SESSION['image_cache_user']) || $path_parts[0] == 'remote_login'){
    // anything beyond an image request they must have authentication
    require_once('header.php');
    require_once('remote_login.php');
    require_once('footer.php');
}elseif($path_parts[0] == 'upload' ){
    require_once('upload_progress.php');
}elseif($path_parts[0] == 'js' ){
    // allow javascript server directly
    // this is in .htaccess on live but needed here for dev
    return false;
}elseif($path_parts[0] == 'download_results.php' ){
    // download - this is in .htaccess on live but needed here for dev
    return false;
}else{
    // all else fails render the home page
    require_once('header.php');
    require_once('manage.php');
    require_once('footer.php');
}