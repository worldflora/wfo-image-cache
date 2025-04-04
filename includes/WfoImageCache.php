<?php

class WfoImageCache{

    private ?string $inFilePath = null;
    private ?string $outFilePath = null;
    private int $offset = 0;
    private ?int $created = null; 
    private ?string $lastError = null;
    private string $mode = 'add';

    public function __construct($file_dir = null, $mode = 'add'){

        $this->inFilePath = $file_dir . 'in.csv';
        $this->outFilePath = $file_dir . 'out.csv';
        $this->created = time();
        $this->mode = $mode;

    }

    public function importNext(){

        $in = fopen($this->inFilePath, 'r');

        // if the offset is 0 we are doing the first page
        if($this->offset == 0){
            
            // create an output file
            $out = fopen($this->outFilePath, 'w');

            $header = fgetcsv($in);
            $header[] = 'wfo_image_id';

            foreach (WFO_IMAGE_HEIGHTS as $size) {
                $header[] = 'wfo_image_' . $size;
            }

            $header[] = 'wfo_image_original';

            fputcsv($out, $header);

            $this->offset++;

        }else{            
            // just open the out
            $out = fopen($this->outFilePath, 'a');
            
            // seek forward to the offset
            for($i=0; $i < $this->offset; $i++) fgetcsv($in);

        }

        $row_processed = null;

        // get the next row
        $row = fgetcsv($in);
        if($row){

            $uri = $row[1];

            if($this->mode == 'remove'){
                if($this->removeImage($uri)){
                    $row[] = 'REMOVED';
                    $row_processed = 'REMOVED';
                }

            }else{

                // we are adding or checking
                if($this->mode == 'add') $id = $this->addImage($uri);
                if($this->mode == 'check') $id = $this->checkImage($uri);

                if($id){
                    
                    $row_processed = $id;
    
                    // add columns to the end of the file with links in
                    $row[] = $id;
                    foreach (WFO_IMAGE_HEIGHTS as $size) {
                        $row[] = "https://{$_SERVER['HTTP_HOST']}/server/wfo/{$id}/full/,{$size}/0/default.jpg";
                    }
                    $row[] = "https://{$_SERVER['HTTP_HOST']}/server/wfo/{$id}/full/max/0/default.jpg";
    
                }else{
                    
                    $row_processed = 'FAILED';
    
                    $row[] = 'FAILED: ' . $this->getLastError();
                    foreach (WFO_IMAGE_HEIGHTS as $size) {
                        $row[] = "-";
                    }
                    $row[] = "-";
    
                }


            }


            // keep a note of how it went
            fputcsv($out, $row);

            $this->offset++;

        }

        fclose($in);
        fclose($out);

        return $row_processed;

    }

    /**
     */
    public function __sleep(){
        // the fields we perist
        return array('inFilePath', 'outFilePath', 'offset', 'created', 'lastError', 'mode');
    }
    
    /**
     */
    public function __wakeup(){
    }

    /**
     * Will download and store new image
     * 
     * @return true on success false on failure
     */
    public function addImage($image_uri){

        $id = $this->getImageId($image_uri);
        $uri = trim($image_uri);
        $original_path = $this->getImageDirPath($id, true) . $id . '.jpg';

        // no original path no go
        if(!$id || !$uri){
            $this->lastError = "No path for URL $uri";
            return null;
        } 

        // we only add an image if it doesn't exit
        if(!file_exists($original_path)){

            // put this is a try/catch so that we can continue
            // if we have a duff image
            try {
                file_put_contents($original_path, file_get_contents($uri));
                $this->generateDerivatives($id);
                $this->lastError = null;
                return $id;
            } catch (Exception $e) {
                $this->lastError = $e->getMessage();
                return null;
            }

        }else{
            // the file exists so we try and generate derivatives
            // if they derivatives are already there then they will be skipped
            $this->generateDerivatives($id);
            return $id;
        }

    }

    public function checkImage($image_uri){

        $id = $this->getImageId($image_uri);
        $uri = trim($image_uri);
        $original_path = $this->getImageDirPath($id, true) . $id . '.jpg';

        // no original path no go
        if(!$id || !$uri){
            $this->lastError = "No path for URL $uri";
            return null;
        } 

        // Check it is there - and generate deriviatives if they are needed
        if(file_exists($original_path)){

            // the file exists so we try and generate derivatives
            // if they derivatives are already there then they will be skipped
            $this->generateDerivatives($id);
            return $id;

        }else{
            // we don't have it
            return null;
        }

    }

    public function removeImage($image_uri){

        $id = $this->getImageId($image_uri);
        $uri = trim($image_uri);
        $original_path_pattern = $this->getImageDirPath($id, true) . $id . '*.*';

        $files = glob($original_path_pattern);
        foreach ($files as $file) {
           unlink($file);
           error_log($file);
        }
        
        // check we have removed it
        return !file_exists($original_path_pattern . '.jpg');
    }

    public function generateDerivatives($image_id){
        foreach (WFO_IMAGE_HEIGHTS as $size) {
            $this->generateDerivative($image_id, $size);
        }
    }

    public function generateDerivative($image_id, $size){

        $dir = $this->getImageDirPath($image_id, true);
        $original = $dir . $image_id . '.jpg';
        $file = $this->getImageFilePath($image_id, $size, true);
        
        // if the file doesn't exist but should then we try and make it
        if(
            !file_exists($file) // the small file doesn't exist
            && 
            file_exists($original) // full size one exists to be chopped down
            &&
            filesize($original) > 0 // not an empty file created in error
        ){

            // Get new sizes
            list($width, $height) = getimagesize($original);

            $percent = $size/$height;

            $newwidth = (int)round($width * $percent);
            $newheight = (int)round($height * $percent);

            // Load
            $destination = imagecreatetruecolor($newwidth, $newheight);
            $source = imagecreatefromjpeg($original);

            // if the orginal is smaller than the derivative size then we 
            // add a black border. This is because we have stupidly small
            // originals and don't want to standardize on a small size.
            if($percent > 1){
                imagecopyresampled(
                        $destination, // where we are putting the pixels
                        $source, // where they are coming from
                        (int)round(($newwidth - $width) / 2), // x top left IN destination
                        (int)round(($newheight - $height) / 2), // y top left IN destination
                        0, // source x 
                        0, // source y
                        $width, // width IN destintation
                        $height, // height IN destination
                        $width, // width of source
                        $height // height of source
                    );
            }else{
                // Resize
                imagecopyresampled($destination, $source, 0, 0, 0, 0, $newwidth, $newheight, $width, $height);
            }

            // save
            imagejpeg($destination, $file);

        }

    }

    public function removeImageByUri($image_uri){
        $this->removeImageById($this->getImageId($image_uri));
    }

    public function removeImageById($image_id){
        $path = $this->getImageDirPath($image_id, false);
        $files = glob($path . $image_id . '-*'); // all files ignoring the size variant and ending
        foreach ($files as $file) {
            unlink($file);
        }
    }

    public function getImageId($image_uri){
        return md5($image_uri);
    }

    /**
     * Calculates the path to the image
     * directory and creates it if necessary
     */
    public function getImageDirPath($image_id, $create){
        $path = WFO_FILE_CACHE . substr($image_id, 0,1) . '/' . substr($image_id, 1,1) . '/' . substr($image_id, 2,1) . '/'; 
        if($create && !file_exists($path)) mkdir($path, 0777, true);
        return $path;
    }

    public function getImageFilePath($image_id, $size = 'max', $create = false){

        $dir = $this->getImageDirPath($image_id, $create);

        if(!$size) $size = 'max';

        if($size == 'max'){
            $file = $dir . $image_id . '.jpg';
            return $file;
        }

        // we are asking for a smaller file
        $file = $dir . $image_id . '-' . $size . '.jpg';

        return $file;
 
    }

    public function getImageSize($image_id){

        $original_path = $this->getImageDirPath($image_id, false) . $image_id . '.jpg';

        if(!file_exists($original_path)){
            return null;
        }else{
            return getimagesize($original_path);
        }
        
    }

    public function getOffset(){
        return $this->offset;
    }

    public function getLastError(){
        return $this->lastError;
    }

    public function getCreated(){
        return $this->created;
    }

} // end class