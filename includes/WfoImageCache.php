<?php

class WfoImageCache{


    public function __construct(){

    }

    /**
     */
    public function __sleep(){
        // the fields we perist
        return array();
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
        if( file_put_contents($original_path, file_get_contents($uri)) ){
            $this->generateDerivatives($id);
            return true;
        }else{
            return false;
        }
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
        if($create) @mkdir($path, 0777, true);
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

} // end class