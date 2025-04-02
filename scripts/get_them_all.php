<?php

require_once('../config.php');

$response = $mysqli->query("SELECT * FROM emonocot.image;");

$out = fopen("../data/all/data.csv", 'w');

fputcsv($out, array("id","created","creator","identifier","license","modified",
    "source","title","taxon_id","authority_id","image_id","description","format",
    "subject","locality","location","accessRights","rights","rightsHolder",
    "contributor","publisher","audience","latitude","longitude","references",
    "uri","width","height"));

while($row = $response->fetch_assoc()){

    $file = "../data/all/{$row['id']}.jpg";
    if( file_put_contents($file, file_get_contents($row['identifier'])) ){
        list($width, $height) = getimagesize($file);
        $row[] = $width;
        $row[] = $height;
        fputcsv($out,$row);
    }
    echo "{$row['identifier']}\n";
    
}

fclose($out);