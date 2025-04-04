<?php

require_once('../config.php');

$out_file_path = "../data/session_data/". session_id() ."/out.csv";
if(file_exists($out_file_path)){
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="wfo_image_upload_batch.csv"');
    readfile($out_file_path);
}else{
    header('HTTP/1.0 404 Not Found', true, 404);
    echo "File not Found";
}