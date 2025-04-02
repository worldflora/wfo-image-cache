<?php

require_once('../config.php');
require_once('../includes/WfoImageCache.php');

// the actual iiif image server

// $path_parts come from the enclosing index.php

// are we serving json.info metadata or the image itself?

// a request for metadata looks like this
//{scheme}://{server}{/prefix}/{identifier}/info.json

if(count($path_parts) == 4 && $path_parts[3] = 'info.json'){
    render_info_json($path_parts);
}else{
    render_image($path_parts);
}


function render_image($path_parts){

    $cache = new WfoImageCache();

    // https://iiif.io/api/image/3.0/#21-image-request-uri-syntax
    // {scheme}://{server}{/prefix}/{identifier}/{region}/{size}/{rotation}/{quality}.{format}
    // https://example.org/image-service/abcd1234/full/max/0/default.jpg

    $prefix = $path_parts[1];
    $id = $path_parts[2];
    $region = $path_parts[3];
    $size = $path_parts[4];
    $rotate = $path_parts[5];
    list($quality, $format) = explode('.', $path_parts[6]);


    // prefix must be wfo or they are bad
    if($prefix != 'wfo'){
        http_response_code(400);
        echo "<h1>Bad Request</h1>\n";
        echo "<p>The only prefix currently accepted is 'wfo'</p>\n";
        exit;
    }

    if($region != 'full'){
        http_response_code(400);
        echo "<h1>Bad Request</h1>\n";
        echo "<p>Only 'full' region is supported on this Level0 server.</p>\n";
        exit;
    }



    // size can be max or w,h or ,h or w,
    // an exercise for the reader is to combine these regex into one!
    if(
        !preg_match('/^,[0-9]+$/', $size) 
        &&
        !preg_match('/^[0-9]+,$/', $size)
        &&
        !preg_match('/^[0-9]+,[0-9]+/', $size)
        && 
        $size != 'max'
        ){
            http_response_code(400);
            echo "<h1>Bad Request</h1>\n";
            echo "<p>Unrecognised size '$size'.</p>\n";
            exit;
        }
    
    // but we think only in terms h for sized file 
    // so we need to convert what they are saying 
    if($size != 'max'){

        $supported_size = false;

        list($width, $height) = explode(',', $size);

        // if they have provided a width we need to see if 
        // it is a supported size file
        if($width){

            // get the size of the original so we have the proportions
            $file_path = $cache->getImageFilePath($id, 'max');
            list($original_width, $original_height) = getimagesize($file_path);

            // see if the width,height combo is one of our standard
            // sizes
            foreach (WFO_IMAGE_HEIGHTS as $h) {
                $percent = $h/$original_height;
                $derivative_width = (int)round($width * $percent);
                $derivative_height = (int)round($height * $percent);
                if($width == $derivative_width && (!$height || $height == $derivative_height)){
                    $supported_size = true;
                    $size = $derivative_height;
                }
            }

            // it may also be the size of the original
            if(!$supported_size){
                if($width == $original_width && (!$height || $height == $original_height)){
                    $supported_size = true;
                    $size = 'max'; // we will return the orginal
                }
            }

        }else{

            // they didn' specify a width.
            // did they ask for one of our standard heights
            if(in_array($height, WFO_IMAGE_HEIGHTS)){
                $supported_size = true;
                $size = $height;
            
            }else{
                // the height isn't one of our prefered ones so lets
                // see if it is the height of the original
                $file_path = $cache->getImageFilePath($id, 'max');
                list($original_width, $original_height) = getimagesize($file_path);
                if($height == $original_height){
                    $supported_size = true;
                    $size = 'max'; // we will return the orginal
                }
            }

        } // width not specified

    }else{// size not set to max
        $supported_size = true;
    }

    if(!$supported_size){
        http_response_code(400);
        echo "<h1>Bad Request</h1>\n";
        echo "<p>Unsupported size for this image.</p>\n";
        exit;
    }

    $file_path = $cache->getImageFilePath($id, $size);

    // if the file doesn't exist but the size is in the config then
    // try and create it. This allows us to add derivative sizes if we want
    // to
    if(!file_exists($file_path) && in_array($size, WFO_IMAGE_HEIGHTS)){
        $cache->generateDerivative($id, $size);
    }


    if($file_path && file_exists($file_path)){
        header('Content-Type: image/jpeg');
        readfile($file_path);
    }else{
        print_r($path_parts);
        echo 'not found';
    }

}


function render_info_json($path_parts){

    $cache = new WfoImageCache();
    
    $prefix = $path_parts[1];
    $id = $path_parts[2];

    // does the 
    list($width, $height) = $cache->getImageSize($id);

    if(!$width || !$height){
        header('HTTP/1.0 404 Not Found', true, 404);
        echo "Image not Found";
        exit;
    }

    // we pretend that nothing is less than 1,000 pixels in size (longest side)
    // and will add black borders to smaller originals to achieve this
    if($width < 1000 && $height < 1000){
        if($width > $height) $percent = 1000/$width;
        else $percent = 1000/$height;
        $width = (int)round($width * $percent);
        $height = (int)round($height * $percent);
    }

    $sizes = array();
    foreach (WFO_IMAGE_HEIGHTS as $size) {
        $percent = $size/$height;
        $sizes[] = (object)array(
            "width" => (int)round($width * $percent),
            "height" => (int)round($height * $percent)
        );
    }


    // add the original in if it isn't in there already
    $original_size = (object)array(
        "width" => $width,
        "height" => $height
    );

    if(!in_array($original_size, $sizes)){
        $sizes[] = $original_size;
    }


    // need to add the original size if it is different

    $out = (object)array(

            "@context" => "http://iiif.io/api/image/3/context.json",
            "id" => "https://{$_SERVER['HTTP_HOST']}/server/{$prefix}/{$id}",
            "type" => "ImageService3",
            "protocol" => "http://iiif.io/api/image",
            "profile" => "level0",
            "width" => $width,
            "height" => $height,
            "sizes" => $sizes
    );


    header('Content-Type: application/ld+json;profile="http://iiif.io/api/image/3/context.json"');

    echo json_encode($out);

}





