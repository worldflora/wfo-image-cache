<?php

require_once('../config.php');
require_once('../includes/WfoImageCache.php');

// a chunk of code that shows the progress loading the file uploaded.
$out = array();
$out['timestamp'] = time();

$importer = unserialize(@$_SESSION['importer']);

if($importer){

    $row_processed = $importer->importNext();
    
    // present a nice progress time
    $now = time();
    $elapse =  $now - $importer->getCreated();
    $dt1 = new DateTime("@0");
    $dt2 = new DateTime("@$elapse");
  
    // Calculate the difference between the two timestamps
    $diff = $dt1->diff($dt2);
    
    // Format the difference to display days, hours, minutes, and seconds
    $elapse =  $diff->format('%h hours, %i minutes, %s seconds');
    $progress = number_format($importer->getOffset(), 0);

    if(!$row_processed){
        $out['message'] = "<strong>Finished: </strong>Rows processed: {$progress}  Elapse time: $elapse.";
        $out['complete'] = true;
        $out['level'] = 'success';
        unset($_SESSION['importer']);
    }else{

        $out['message'] = "<p><strong>Importing: </strong>Rows processed: {$progress}  Elapse time: $elapse.</p>";

        if($row_processed == 'FAILED'){
            // display error
            $out['message'] .= "<p>FAILED: ".  $importer->getLastError()  ."</p>";
            $out['level'] = 'danger';
            $out['image'] = null;
        }elseif($row_processed == 'REMOVED'){
            // display removed
            $out['message'] .= "<p>REMOVED {$progress}</p>";
            $out['level'] = 'danger';
            $out['image'] = null;
        }else{
            // display a thumbnail of successful images
            $out['message'] .= "<p>Success</p>";
            $out['image'] = "/server/wfo/{$row_processed}/full/,150/0/default.jpg";
            $out['level'] = 'warning';
        }

        $out['complete'] = false;
        $_SESSION['importer'] = serialize($importer);
    }
}else{
    $out['message'] = "<strong>Error: </strong>Importer object not available.";
    $out['complete'] = true;
    $out['level'] = 'danger';
    unset($_SESSION['importer']);
}

header('Content-Type: application/json');
echo json_encode((object)$out);
exit;